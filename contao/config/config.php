<?php

use HeimrichHannot\ResourceBookingBundle\Contao\Backend\BookingModule;
use HeimrichHannot\ResourceBookingBundle\Contao\Backend\ResourceModule;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;
use HeimrichHannot\ResourceBookingBundle\Widget\BlobWidget;
use HeimrichHannot\ResourceBookingBundle\Widget\BookedResourcesWidget;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD'] = \array_slice($GLOBALS['BE_MOD'], 0, 1, true)
    + ['huh_rb' => []]
    + \array_slice($GLOBALS['BE_MOD'], 1, null, true);

$GLOBALS['BE_MOD'][ResourceModule::CATEGORY][ResourceModule::NAME] = [
    'tables' => ResourceModule::getTables(),
];
$GLOBALS['BE_MOD'][BookingModule::CATEGORY][BookingModule::NAME] = [
    'tables' => BookingModule::getTables(),
];

/*
 * Models
 */
$GLOBALS['TL_MODELS'][BookingArchiveModel::getTable()] = BookingArchiveModel::class;
$GLOBALS['TL_MODELS'][BookingModel::getTable()] = BookingModel::class;
$GLOBALS['TL_MODELS'][BookingResourceModel::getTable()] = BookingResourceModel::class;
$GLOBALS['TL_MODELS'][ResourceArchiveModel::getTable()] = ResourceArchiveModel::class;
$GLOBALS['TL_MODELS'][ResourceModel::getTable()] = ResourceModel::class;

/*
 * Backend form fields
 */
$GLOBALS['BE_FFL'][BlobWidget::TYPE] = BlobWidget::class;
$GLOBALS['BE_FFL'][BookedResourcesWidget::TYPE] = BookedResourcesWidget::class;
