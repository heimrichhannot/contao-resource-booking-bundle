<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;

use Codefog\HasteBundle\Form\Form;
use Codefog\HasteBundle\Util\ArrayPosition;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FormModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsContentElement(self::TYPE, category: 'includes', template: 'content_element/resource_booking_form')]
class BookingFormController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_form';

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
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
            throw new NotFoundHttpException('No form found');
        }

        $bookingArchive = $model->getRelated('rb_bookingArchive');
        if (!$bookingArchive instanceof BookingArchiveModel) {
            throw new NotFoundHttpException('No booking archive found');
        }

        if (!$resourceArchivesRaw = $model->getRelated('rb_resourceArchives')) {
            throw new NotFoundHttpException('No resource archives found');
        }

        if (!$bookingArchive->published || !$resourceArchivesRaw->count()) {
            $template->set('disabled', true);
            return $template->getResponse();
        }

        /** @var ResourceArchiveModel[] $resourceArchives */
        $resourceArchives = [];

        foreach ($resourceArchivesRaw as $archive) {
            if ($archive instanceof ResourceArchiveModel && $archive->published) {
                $resourceArchives[$archive->id] = $archive;
            }
        }

        if (!$resourceArchives) {
            $template->set('disabled', true);
            return $template->getResponse();
        }

        $form = $this->makeHasteForm($model, $formModel);
        $formHelper = $form->getHelperObject();
        $template->set('haste_form', $formHelper);

        $mountId = 'rb-mount-' . $model->id;
        $template->set('mount_id', $mountId);

        $jsRoot = [
            'api' => [
                'bookings' => $this->generateUrl('huh_rb.bookings', ['bookingArchive' => $bookingArchive->id]),
            ],
            'ref' => [
                'booking_archive' => $bookingArchive->id,
                'form' => $formModel->id,
                'resource_archives' => \array_keys($resourceArchives),
            ],
            'selectors' => [
                'form' => "#{$formHelper->formId}",
                'mount' => "#{$mountId}",
            ],
        ];
        $template->set('js_root_data', $jsRoot);

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
}