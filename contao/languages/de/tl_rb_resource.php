<?php

use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;

$lang = &$GLOBALS['TL_LANG'][ResourceModel::getTable()];

$lang['title_legend'] = 'Allgemeine Einstellungen';
$lang['title'] = ['Titel', 'Der Titel unter dem diese Ressource angezeigt wird.'];
$lang['quantity'] = ['Menge', 'Die Anzahl der verfügbaren Einheiten dieser Ressource. 0 für unbegrenzt.'];
