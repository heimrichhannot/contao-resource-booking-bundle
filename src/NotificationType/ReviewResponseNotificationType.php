<?php

namespace HeimrichHannot\ResourceBookingBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;

class ReviewResponseNotificationType implements NotificationTypeInterface
{
    use CommonBookingTokensTrait;

    public const NAME = 'huh_rb_review_response';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return $this->getCommonBookingTokens();
    }
}