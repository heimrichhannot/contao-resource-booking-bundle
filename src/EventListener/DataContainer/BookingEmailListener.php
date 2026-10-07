<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Util\EmailAddress;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingEmailListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * The booking's notifications are sent to this address, so it must be exactly one address (see EmailAddress).
     */
    #[AsCallback(Table::BOOKING->value, 'fields.email.save')]
    public function validateEmail(mixed $value, DataContainer $dc): mixed
    {
        if (!EmailAddress::isSingle(\html_entity_decode((string) $value))) {
            throw new \RuntimeException($this->translator->trans('backend.email_invalid', [], 'huh_rb'));
        }

        return $value;
    }
}
