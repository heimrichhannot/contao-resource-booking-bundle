<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;

class ConfirmationNotificationType implements NotificationTypeInterface
{
    public const NAME = 'huh_rb_confirmation';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            new AnythingTokenDefinition('email', 'The email address of the person who made the booking'),
            new AnythingTokenDefinition('booking_*', 'All booking properties as tokens prefixed with "booking_"'),
            new AnythingTokenDefinition('archive_*', 'All archive properties as tokens prefixed with "archive_"'),
            new AnythingTokenDefinition('data_*', 'All custom data properties as tokens prefixed with "data_"'),
            new AnythingTokenDefinition('internal_*', 'All internal state properties as tokens prefixed with "internal_"'),
        ];
    }
}