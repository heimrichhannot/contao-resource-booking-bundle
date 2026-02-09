<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;


use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(self::TYPE, template: 'content_element/resource_booking_opt_in')]
class OptInController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_opt_in';

    public function __construct(
        private readonly BookingPipeline $pipeline,
        private readonly OptIn $optIn,
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
        return new Response('Manages opt-in for resource booking');
    }

    protected function getFrontendResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (!$tokenId = $request->query->get('rb-token')) {
            return new Response();
        }

        try
        {
            $check = $this->validateToken($tokenId);

            $template->set('confirmed', true);

            $this->pipeline->process($check);
        }
        catch (\Throwable $e)
        {
            $template->set('confirmed', false);
            $template->set('error', $e->getMessage());
        }

        return $template->getResponse();
    }

    private function validateToken(string $tokenId): BookingModel
    {
        if (!$token = $this->optIn->find($tokenId)) {
            throw new \InvalidArgumentException('Invalid token ID');
        }

        if ($token->isConfirmed()) {
            throw new \RuntimeException('Token already confirmed');
        }

        $related = $token->getRelatedRecords();

        if (!\count($related) || \key($related) !== Table::BOOKING->value) {
            throw new \InvalidArgumentException('Invalid token');
        }

        if (!$model = BookingModel::findById(\current($related))) {
            throw new \RuntimeException('Booking not found');
        }

        $token->confirm();

        $model->optedInAt = time();
        $model->optInExpiresAt = null;
        $model->optInToken = null;
        $model->save();

        return $model;
    }
}