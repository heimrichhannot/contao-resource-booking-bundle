<?php

use HeimrichHannot\ResourceBookingBundle\NotificationType\OptInRequestNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewRequestNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewResponseNotificationType;

$lang = &$GLOBALS['TL_LANG']['tl_nc_notification'];

$lang['type'][OptInRequestNotificationType::NAME] = ['Booking request: Opt-in', 'Sent when the user must confirm their email address via opt-in.'];
$lang['type'][ReviewRequestNotificationType::NAME] = ['Booking request: Pending review', 'Sent when a booking request requires approval.'];
$lang['type'][ReviewResponseNotificationType::NAME] = ['Booking request: Reviewed', 'Sent when a booking request has been reviewed.'];
