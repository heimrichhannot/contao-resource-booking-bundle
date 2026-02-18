<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;

use Codefog\HasteBundle\Form\Form;
use Codefog\HasteBundle\Util\ArrayPosition;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormModel;
use Contao\PageModel;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(self::TYPE, category: 'includes', template: 'content_element/resource_booking_form')]
class BookingFormController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_form';

    public function __construct(
        private readonly BookingArchiveTypeRegistry $bookingArchiveTypeRegistry,
        private readonly BookingFactory             $bookingFactory,
        private readonly BookingPipeline            $bookingPipeline,
        private readonly ContentUrlGenerator        $contentUrlGenerator,
        private readonly ScopeMatcher               $scopeMatcher,
        private readonly TranslatorInterface        $translator,
    ) {}

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return $this->scopeMatcher->isBackendRequest($request)
            ? $this->getBackendResponse($template, $model, $request)
            : $this->getFrontendResponse($template, $model, $request);
    }

    protected function getBackendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return new Response('Shows a resource booking form');
    }

    protected function getFrontendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $formModel = $model->getRelated('rb_form');
        if (!$formModel instanceof FormModel) {
            throw $this->createNotFoundException('No form found');
        }

        $bookingArchive = $model->getRelated('rb_bookingArchive');
        if (!$bookingArchive instanceof BookingArchiveModel) {
            throw $this->createNotFoundException('No booking archive found');
        }

        if (!$bookingArchiveType = $this->bookingArchiveTypeRegistry->get((string) $bookingArchive->type)) {
            throw $this->createNotFoundException('No booking archive type found');
        }

        if (!$resourceArchivesRaw = $model->getRelated('rb_resourceArchives')) {
            throw $this->createNotFoundException('No resource archives found');
        }

        if (!$bookingArchive->published || !$resourceArchivesRaw->count()) {
            $template->set('disabled', true);
            return $template->getResponse();
        }

        /** @var ResourceArchiveModel[] $resourceArchives */
        $resourceArchives = [];
        $resources = [];

        foreach ($resourceArchivesRaw as $archive)
        {
            if (!$archive instanceof ResourceArchiveModel || !$archive->published) {
                continue;
            }

            $resourceArchives[$archive->id] = $archive;

            $res = ResourceModel::findPublishedByPids([$archive->id])?->getModels() ?? [];

            foreach ($res as $resource)
            {
                if (!$resource instanceof ResourceModel || !$resource->published) {
                    continue;
                }

                $resources[$resource->id] = $resource;
            }
        }

        if (!$resourceArchives) {
            $template->set('disabled', true);
            return $template->getResponse();
        }

        $form = $this->makeHasteForm($model, $formModel);

        if ($form->validate())
        {
            try {
                $booking = $this->bookingFactory->createFromSubmittedData(
                    archive: $bookingArchive,
                    data: $form->fetchAll(),
                    allowedResources: \array_keys($resources)
                );
            } catch (\RuntimeException) {
                $this->addFlash('error', $this->translator->trans('messages.submission_invalid', [], 'huh_rb'));
                return $this->redirect($request->getRequestUri());
            }

            $this->bookingPipeline->process($booking);

            $redirectUrl = $request->getRequestUri();

            if ($formModel->jumpTo && $jumpToPage = PageModel::findByPk($formModel->jumpTo))
            {
                try {
                    $redirectUrl = $this->contentUrlGenerator->generate($jumpToPage);
                } catch (\Exception) {
                    $redirectUrl = $request->getRequestUri();
                }
            }

            return $this->redirect($redirectUrl);
        }

        $formHelper = $form->getHelperObject();
        $template->set('haste_form', $formHelper);

        $template->set('booking_archive', $bookingArchive);
        $template->set('resource_archives', $resourceArchives);
        $template->set('resources', $resources);

        $mountId = 'rb-mount-' . $model->id;
        $template->set('mount_id', $mountId);

        $jsRoot = [
            'api' => [
                'bookings' => $this->generateUrl(
                    route: 'huh_rb.bookings',
                    parameters: ['bookingArchive' => $bookingArchive->id]
                ),
                'resources' => $this->generateUrl(
                    route: 'huh_rb.resources',
                ),
            ],
            'ref' => [
                'booking_archive' => $bookingArchive->id,
                'form' => $formModel->id,
                'resource_archives' => \array_keys($resourceArchives),
            ],
            'selectors' => [
                'form' => "#{$formHelper->formId}",
                'mount' => "#{$mountId}",
                'dataInput' => "#{$formHelper->formId} input[name=rb_data]",
            ],
        ];
        $template->set('js_root_data', $jsRoot);

        $mountTemplate = $bookingArchive->customTpl ?: $bookingArchiveType->getTemplate();
        $mountTemplate = "@Contao/$mountTemplate.html.twig";
        $template->set('mount_template', $mountTemplate);

        return $template->getResponse();
    }

    protected function makeHasteForm(ContentModel $model, FormModel $formModel): Form
    {
        return (new Form('rb-form-' . $model->id, 'POST'))
            ->addContaoHiddenFields()
            ->addFieldsFromFormGenerator(
                $formModel->id,
                static fn (string $fieldName, array $fieldConfig) => isset($fieldConfig['type'])
            )
            ->addFormField('rb_data', [
                'inputType' => 'hidden',
                'value' => \json_encode(['demo' => 'example'], \JSON_THROW_ON_ERROR),
            ], ArrayPosition::first());
    }

    protected function generateUrl(
        string $route,
        array  $parameters = [],
        int    $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $url = parent::generateUrl($route, $parameters, $referenceType);
        if (\str_starts_with($url, '/preview.php/')) {
            $url = \substr($url, \strlen('/preview.php'));
        }
        return $url;
    }
}