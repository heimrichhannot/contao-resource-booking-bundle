<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\AnythingTokenDefinition;

class OptInRequestNotificationType implements NotificationTypeInterface
{
    use CommonBookingTokensTrait;

    public const NAME = 'huh_rb_opt_in_request';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            ...$this->getCommonBookingTokens(),
            new AnythingTokenDefinition('token', 'The opt-in token to confirm the opt-in'),
        ];
    }
}