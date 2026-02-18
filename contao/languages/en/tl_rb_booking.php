<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

$lang = &$GLOBALS['TL_LANG'][BookingModel::getTable()];

$lang['title_legend'] = 'General settings';
$lang['email'] = ['Email address', 'The email address of the user who made the booking.'];
$lang['uuid'] = ['Booking ID', 'A unique identification number for the booking.'];

$lang['timing_legend'] = 'Time period';
$lang['start'] = ['Start', 'The start date of the booking.'];
$lang['end'] = ['End', 'The end date of the booking.'];

$lang['data_legend'] = 'Booking';
$lang['data'] = ['Booking data', ''];
$lang['_bookedResources'] = ['Booked resources', ''];

$lang['status_legend'] = 'Status settings';
$lang['notifyOnStatusChange'] = ['Notify user on status change', 'Should the user be notified when you approve or reject the booking below? Applies only to "Approved" and "Rejected" statuses.'];
$lang['status'] = ['Status', 'The current status of the booking.'];
