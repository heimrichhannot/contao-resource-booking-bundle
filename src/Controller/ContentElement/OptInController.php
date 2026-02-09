<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller\ContentElement;


use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(self::TYPE, template: 'content_element/resource_booking_opt_in')]
class OptInController extends AbstractContentElementController
{
    public const TYPE = 'huh_rb_opt_in';

    public function __construct(
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

        if ($error = $this->checkToken($tokenId)) {
            $template->set('error', $error);
        }

        if (!$error) {
            $template->set('confirmed', true);
        }

        return $template->getResponse();
    }

    private function checkToken(string $tokenId): ?string
    {
        if (!$token = $this->optIn->find($tokenId)) {
            return 'Invalid token ID';
        }

        if ($token->isConfirmed()) {
            return 'Token already confirmed';
        }

        $related = $token->getRelatedRecords();

        if (!\count($related) || \key($related) !== Table::BOOKING->value || !BookingModel::findById(\current($related))) {
            return 'Invalid token';
        }

        $token->confirm();

        return null;
    }
}