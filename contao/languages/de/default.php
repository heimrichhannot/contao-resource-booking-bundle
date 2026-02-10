<?php

use HeimrichHannot\ResourceBookingBundle\Controller\ContentElement\BookingFormController;
use HeimrichHannot\ResourceBookingBundle\Controller\ContentElement\OptInController;

$lang = &$GLOBALS['TL_LANG'];

$lang['CTE'][BookingFormController::TYPE] = [
    'Ressourcenbuchungsformular [H&H Resource Booking]',
    'Zeigt ein Formular zum Buchen von Ressourcen an.'
];
$lang['CTE'][OptInController::TYPE] = [
    'Ressourcenbuchung Opt-In [H&H Resource Booking]',
    'Zeigt ein Opt-In-Formular zum Buchen von Ressourcen an.'
];
