<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Message;
use Contao\System;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingProcessor;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The "Process again" operation of the booking list, for bookings whose processing failed after they were confirmed.
 */
readonly class ProcessBookingOperationListener
{
    public const KEY = 'process';

    public function __construct(
        private BookingProcessor          $bookingProcessor,
        private ContaoFramework           $framework,
        private RequestStack              $requestStack,
        private TranslatorInterface       $translator,
        #[Autowire(service: 'contao.csrf.token_manager')]
        private CsrfTokenManagerInterface $tokenManager,
        #[Autowire('%contao.csrf_token_name%')]
        private string                    $tokenName,
    ) {}

    #[AsCallback(Table::BOOKING->value, 'list.operations.' . self::KEY . '.button')]
    public function showOnlyForFailedBookings(DataContainerOperation $operation): void
    {
        if (empty($operation->getRecord()['processingFailed'])) {
            $operation->setHtml('');
        }
    }

    #[AsCallback(Table::BOOKING->value, 'config.onload')]
    public function processOnRequest(?DataContainer $dc = null): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (self::KEY !== $request?->query->get('key')) {
            return;
        }

        // Processing can send emails, so the link must carry the request token Contao adds to backend URLs
        if (!$this->tokenManager->isTokenValid(new CsrfToken($this->tokenName, (string) $request->query->get('rt')))) {
            throw new AccessDeniedException('Invalid request token.');
        }

        $bookingId = $request->query->getInt('id');
        $booking = $this->framework->getAdapter(BookingModel::class)->findByPk($bookingId);

        if (!$booking instanceof BookingModel) {
            throw new AccessDeniedException('Booking not found.');
        }

        $message = $this->framework->getAdapter(Message::class);

        if ($this->bookingProcessor->processPending($booking)) {
            $message->addConfirmation($this->translator->trans('backend.process_succeeded', ['%id%' => $bookingId], 'huh_rb'));
        } else {
            $message->addError($this->translator->trans('backend.process_failed', ['%id%' => $bookingId, '%reason%' => (string) $booking->get('processingError')], 'huh_rb'));
        }

        $this->framework->getAdapter(Controller::class)->redirect($this->framework->getAdapter(System::class)->getReferer());
    }
}
