<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;

$lang = &$GLOBALS['TL_LANG'][BookingResourceModel::getTable()];

$lang['title_legend'] = 'General settings';
$lang['resourceId'] = ['Resource', 'The resource reserved for the booking.'];
$lang['quantity'] = ['Quantity', 'The number of reserved resources.'];
