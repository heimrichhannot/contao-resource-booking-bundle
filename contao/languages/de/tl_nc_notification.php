<?php

use HeimrichHannot\ResourceBookingBundle\NotificationType\OptInRequestNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewRequestNotificationType;
use HeimrichHannot\ResourceBookingBundle\NotificationType\ReviewResponseNotificationType;

$lang = &$GLOBALS['TL_LANG']['tl_nc_notification'];

$lang['type'][OptInRequestNotificationType::NAME] = ['Buchungsanfrage Opt-In', 'Wird versendet, wenn der Nutzer seine E-Mail Adresse mittels Opt-In bestätigen muss.'];
$lang['type'][ReviewRequestNotificationType::NAME] = ['Buchungsanfrage Prüfung ausstehend', 'Wird versendet, wenn eine Buchungsanfrage freigegeben werden muss.'];
$lang['type'][ReviewResponseNotificationType::NAME] = ['Buchungsanfrage Geprüft', 'Wird versendet, wenn eine Buchungsanfrage geprüft wurde.'];
