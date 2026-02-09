<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;

class ApprovalResponseNotificationType implements NotificationTypeInterface
{
    public const NAME = 'huh_rb_approval_response';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [];
    }
}