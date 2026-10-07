<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;

$lang = &$GLOBALS['TL_LANG'][BookingArchiveModel::getTable()];

###> title_legend ###
$lang['title_legend'] = 'General settings';
$lang['title'] = ['Title', 'The title under which this booking calendar is displayed.'];
$lang['type'] = ['Calendar booking type', 'The type of calendar that should be used for the booking.'];
$lang['alias'] = ['Alias', 'The alias of the calendar.'];
###< title_legend ###

###> limits_legend ###
$lang['limits_legend'] = 'Booking limits';
$lang['minAdvanceDays'] = ['Minimum advance (days)', 'Earliest start of a booking in days from today. 1 = tomorrow at the earliest, 0 = today is allowed.'];
$lang['maxAdvanceDays'] = ['Maximum advance (days)', 'Latest end of a booking in days from today. Must be greater than the minimum advance.'];
$lang['maxDurationDays'] = ['Maximum duration (days)', 'Maximum number of booked days, including the first and the last day.'];
###< limits_legend ###

###> opt_in_legend ###
$lang['opt_in_legend'] = 'Opt-in settings';
$lang['requireOptIn'] = ['Opt-in required', 'Determines whether the user must confirm an email opt-in for the booking.'];
$lang['nc_optInRequest'] = ['Opt-in request (message)', 'The message that is sent when the user requests an opt-in.'];
$lang['jumpToOptInCompleted'] = ['Opt-in page', 'The page to which the user is directed for opt-in confirmation.'];
###< opt_in_legend ###

###> approval_legend ###
$lang['approval_legend'] = 'Approval settings';
$lang['requireReview'] = ['Approval required', 'Determines whether the booking requires approval.'];
$lang['nc_reviewRequest'] = ['Approval request (message)', 'The message that is sent when approval is requested.'];
$lang['nc_reviewApproval'] = ['Approval granted (message)', 'The message that is sent when a booking has been approved.'];
$lang['nc_reviewRejection'] = ['Approval denied (message)', 'The message that is sent when a booking has been rejected.'];
###< approval_legend ###

###> template_legend ###
$lang['template_legend'] = 'Template settings';
$lang['customTpl'] = ['Template', 'The template used for the calendar view.'];
###< template_legend ###

###> publishing_legend ###
$lang['publishing_legend'] = 'Publishing';
$lang['published'] = ['Published', 'Determines whether the calendar is publicly available.'];
###< publishing_legend ###
