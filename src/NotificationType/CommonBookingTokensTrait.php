<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;

trait CommonBookingTokensTrait
{
    protected function getCommonBookingTokens(): array
    {
        return [
            new AnythingTokenDefinition('email', 'The email address of the user to opt-in'),
            new AnythingTokenDefinition('booking_*', 'All fields of the booking record, prefixed with "booking_"'),
            new AnythingTokenDefinition('archive_*', 'All archive properties as tokens prefixed with "archive_"'),
            new AnythingTokenDefinition('data_*', 'All custom data properties as tokens prefixed with "data_"'),
            new AnythingTokenDefinition('internal_*', 'All internal state properties as tokens prefixed with "internal_"'),
        ];
    }
}