<?php

use HeimrichHannot\ResourceBookingBundle\Controller\ContentElement\BookingFormController;
use HeimrichHannot\ResourceBookingBundle\Controller\ContentElement\OptInController;

$lang = &$GLOBALS['TL_LANG'];

$lang['CTE'][BookingFormController::TYPE] = [
    'Resource booking form [H&H Resource Booking]',
    'Displays a form to book resources.'
];
$lang['CTE'][OptInController::TYPE] = [
    'Resource booking opt-in [H&H Resource Booking]',
    'Processes opt-in tokens in the URL for resource bookings.'
];
