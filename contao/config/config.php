<?php

use HeimrichHannot\ResourceBookingBundle\Contao\Backend\BookingModule;
use HeimrichHannot\ResourceBookingBundle\Contao\Backend\ResourceModule;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD'][BookingModule::CATEGORY][BookingModule::NAME] = [
    'tables' => BookingModule::getTables(),
];

$GLOBALS['BE_MOD'][ResourceModule::CATEGORY][ResourceModule::NAME] = [
    'tables' => ResourceModule::getTables(),
];

/*
 * Models
 */
$GLOBALS['TL_MODELS'][BookingArchiveModel::getTable()] = BookingArchiveModel::class;
$GLOBALS['TL_MODELS'][BookingModel::getTable()] = BookingModel::class;
$GLOBALS['TL_MODELS'][ResourceArchiveModel::getTable()] = ResourceArchiveModel::class;
$GLOBALS['TL_MODELS'][ResourceModel::getTable()] = ResourceModel::class;
