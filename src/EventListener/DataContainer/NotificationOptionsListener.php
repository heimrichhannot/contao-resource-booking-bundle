<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewRequestNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewResponseNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\OptInRequestNotificationType;
use Terminal42\NotificationCenterBundle\NotificationCenter;

readonly class NotificationOptionsListener
{
    public function __construct(
        private NotificationCenter $nc,
    ) {}

    #[AsCallback(table: Table::BOOKING_ARCHIVE->value, target: 'fields.nc_optInRequest.options')]
    public function getOptInRequestOptions(DataContainer $dc): array
    {
        return $this->nc->getNotificationsForNotificationType(OptInRequestNotificationType::NAME);
    }

    #[AsCallback(table: Table::BOOKING_ARCHIVE->value, target: 'fields.nc_reviewRequest.options')]
    public function getApprovalRequestOptions(DataContainer $dc): array
    {
        return $this->nc->getNotificationsForNotificationType(ReviewRequestNotificationType::NAME);
    }

    #[AsCallback(table: Table::BOOKING_ARCHIVE->value, target: 'fields.nc_reviewApproval.options')]
    #[AsCallback(table: Table::BOOKING_ARCHIVE->value, target: 'fields.nc_reviewRejection.options')]
    public function getApprovalGrantedOptions(DataContainer $dc): array
    {
        return $this->nc->getNotificationsForNotificationType(ReviewResponseNotificationType::NAME);
    }
}