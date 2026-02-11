<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;

$lang = &$GLOBALS['TL_LANG'][BookingArchiveModel::getTable()];

$lang['title_legend'] = 'Allgemeine Einstellungen';
$lang['title'] = ['Titel', 'Der Titel unter dem dieser Buchungskalender angezeigt wird.'];
$lang['type'] = ['Art der Kalenderbuchung', 'Der Typ des Kalenders, der für die Buchung verwendet werden soll.'];
$lang['alias'] = ['Alias', 'Der Alias des Kalenders.'];

$lang['opt_in_legend'] = 'Opt-In-Einstellungen';
$lang['requireOptIn'] = ['Opt-In erforderlich', 'Legt fest, ob der Benutzer ein E-Mail-Opt-In für die Buchung bestätigen muss.'];
$lang['nc_optInRequest'] = ['Opt-In-Anfrage Nachricht', 'Die Nachricht, die versendet wird, wenn der Benutzer ein Opt-In anfordert.'];
$lang['jumpToOptInCompleted'] = ['Weiterleitungsseite nach Opt-In', 'Die Seite, auf die der Benutzer zum Opt-In-Bestätigen weitergeleitet wird.'];

$lang['approval_legend'] = 'Freigabe-Einstellungen';
$lang['requireReview'] = ['Freigabe erforderlich', 'Legt fest, ob die Buchung freigegeben werden muss.'];
$lang['nc_reviewRequest'] = ['Freigabe-Anfrage Nachricht', 'Die Nachricht, die versendet wird, wenn eine Freigabe angefordert wird.'];
$lang['nc_reviewApproval'] = ['Freigabe-Bestätigung Nachricht', 'Die Nachricht, die versendet wird, wenn eine Buchung freigegeben wurde.'];
$lang['nc_reviewRejection'] = ['Freigabe-Ablehnungs Nachricht', 'Die Nachricht, die versendet wird, wenn eine Buchung abgelehnt wurde.'];

$lang['publishing_legend'] = 'Veröffentlichung';
$lang['published'] = ['Veröffentlicht', 'Legt fest, ob der Kalender öffentlich nutzbar ist.'];
