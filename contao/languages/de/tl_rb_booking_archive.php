<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;

$lang = &$GLOBALS['TL_LANG'][BookingArchiveModel::getTable()];

###> title_legend ###
$lang['title_legend'] = 'Allgemeine Einstellungen';
$lang['title'] = ['Titel', 'Der Titel unter dem dieser Buchungskalender angezeigt wird.'];
$lang['type'] = ['Art der Kalenderbuchung', 'Der Typ des Kalenders, der für die Buchung verwendet werden soll.'];
$lang['alias'] = ['Alias', 'Der Alias des Kalenders.'];
###< title_legend ###

###> opt_in_legend ###
$lang['opt_in_legend'] = 'Opt-In-Einstellungen';
$lang['requireOptIn'] = ['Opt-In erforderlich', 'Legt fest, ob der Benutzer ein E-Mail-Opt-In für die Buchung bestätigen muss.'];
$lang['nc_optInRequest'] = ['Opt-In-Anfrage (Nachricht)', 'Die Nachricht, die versendet wird, wenn der Benutzer ein Opt-In anfordert.'];
$lang['jumpToOptInCompleted'] = ['Opt-In-Seite', 'Die Seite, auf die der Benutzer zum Opt-In-Bestätigen weitergeleitet wird.'];
###< opt_in_legend ###

###> approval_legend ###
$lang['approval_legend'] = 'Freigabe-Einstellungen';
$lang['requireReview'] = ['Freigabe erforderlich', 'Legt fest, ob die Buchung freigegeben werden muss.'];
$lang['nc_reviewRequest'] = ['Freigabe-Anfrage (Nachricht)', 'Die Nachricht, die versendet wird, wenn eine Freigabe angefordert wird.'];
$lang['nc_reviewApproval'] = ['Freigabe erteilt (Nachricht)', 'Die Nachricht, die versendet wird, wenn eine Buchung freigegeben wurde.'];
$lang['nc_reviewRejection'] = ['Freigabe verweigert (Nachricht)', 'Die Nachricht, die versendet wird, wenn eine Buchung abgelehnt wurde.'];
###< approval_legend ###

###> template_legend ###
$lang['template_legend'] = 'Template-Einstellungen';
$lang['customTpl'] = ['Template', 'Das Template, das für die Kalenderansicht verwendet wird.'];
###< template_legend ###

###> publishing_legend ###
$lang['publishing_legend'] = 'Veröffentlichung';
$lang['published'] = ['Veröffentlicht', 'Legt fest, ob der Kalender öffentlich nutzbar ist.'];
###< publishing_legend ###
