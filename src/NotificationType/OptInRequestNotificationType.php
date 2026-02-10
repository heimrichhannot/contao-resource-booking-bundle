<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;

class OptInRequestNotificationType implements NotificationTypeInterface
{
    public const NAME = 'huh_rb_opt_in_request';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            new AnythingTokenDefinition('email', 'The email address of the user to opt-in'),
            new AnythingTokenDefinition('token', 'The opt-in token to confirm the opt-in'),
            new AnythingTokenDefinition('booking_*', 'All fields of the booking record, prefixed with "booking_"'),
        ];
    }
}