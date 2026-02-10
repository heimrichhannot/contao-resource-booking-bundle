<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;


use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\OptInStep;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(self::TYPE, template: 'content_element/resource_booking_opt_in')]
class OptInController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_opt_in';

    public function __construct(
        private readonly BookingPipeline $pipeline,
        private readonly OptInStep       $optInStep,
        private readonly ScopeMatcher    $scopeMatcher,
    ) {}

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return $this->scopeMatcher->isBackendRequest($request)
            ? $this->getBackendResponse($template, $model, $request)
            : $this->getFrontendResponse($template, $model, $request);
    }

    protected function getBackendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return new Response('Manages opt-in for resource booking');
    }

    protected function getFrontendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (!$tokenId = $request->query->get('rb-token')) {
            return new Response();
        }

        $template->set('confirmed', false);

        try
        {
            $check = $this->optInStep->confirmToken($tokenId);
            $template->set('confirmed', true);

            $result = $this->pipeline->process($check);
            $template->set('pipeline', $result);
        }
        catch (\Throwable $e)
        {
            $template->set('error', $e->getMessage());
        }

        return $template->getResponse();
    }
}