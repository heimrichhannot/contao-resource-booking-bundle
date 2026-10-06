<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;


use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\OptInStep;
use HeimrichHannot\ResourceBookingBundle\Exception\OptInException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(self::TYPE, template: 'content_element/resource_booking_opt_in')]
class OptInController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_opt_in';

    public function __construct(
        private readonly BookingPipeline     $pipeline,
        private readonly OptInStep           $optInStep,
        private readonly TranslatorInterface $translator,
        private readonly ScopeMatcher        $scopeMatcher,
        private readonly LoggerInterface     $logger,
    ) {}

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return $this->scopeMatcher->isBackendRequest($request)
            ? $this->getBackendResponse($template, $model, $request)
            : $this->getFrontendResponse($template, $model, $request);
    }

    protected function getBackendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return new Response($this->translator->trans('ce.opt_in.be_label', [], 'huh_rb'));
    }

    protected function getFrontendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (!$tokenId = $request->query->get('rb-token'))
        {
            if ($model->rb_showPlaceholder)
            {
                $template->set('confirmed', false);
                $template->set('placeholder', true);

                return $template->getResponse();
            }

            return new Response();
        }

        $template->set('confirmed', false);

        try
        {
            $booking = $this->optInStep->confirmToken((string) $tokenId);
        }
        catch (OptInException $e)
        {
            $template->set('error', $e->getMessage());
            return $template->getResponse();
        }
        catch (\Throwable $e)
        {
            // Never show internal error details to visitors
            $this->logger->error('Could not confirm booking opt-in.', ['exception' => $e]);
            $template->set('error', $this->translator->trans('messages.opt_in_invalid', [], 'huh_rb'));
            return $template->getResponse();
        }

        $template->set('confirmed', true);

        try
        {
            $template->set('pipeline', $this->pipeline->process($booking));
        }
        catch (\Throwable $e)
        {
            // The opt-in is confirmed, the remaining steps can be retried by the pipeline command
            $this->logger->error('Could not process booking after opt-in.', ['exception' => $e, 'booking' => $booking->id]);
        }

        return $template->getResponse();
    }
}