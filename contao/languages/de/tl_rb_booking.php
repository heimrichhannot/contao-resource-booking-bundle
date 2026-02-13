<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

$lang = &$GLOBALS['TL_LANG'][BookingModel::getTable()];

$lang['title_legend'] = 'Allgemeine Einstellungen';
$lang['email'] = ['E-Mail-Adresse', 'Die E-Mail-Adresse des Benutzers, der die Buchung vorgenommen hat.'];
$lang['uuid'] = ['Buchungsnummer', 'Eine eindeutige Identifikationsnummer der Buchung.'];

$lang['timing_legend'] = 'Zeitraum';
$lang['start'] = ['Beginn', 'Das Startdatum der Buchung.'];
$lang['end'] = ['Ende', 'Das Enddatum der Buchung.'];

$lang['data_legend'] = 'Buchungsdaten';
$lang['data'] = ['Buchungsdaten', ''];
