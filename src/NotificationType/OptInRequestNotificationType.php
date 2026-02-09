<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;

class OptInRequestNotificationType implements NotificationTypeInterface
{
    public const NAME = 'huh_rb_opt_in_request';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [];
    }
}