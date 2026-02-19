<?php

use Contao\DataContainer;
use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;

$table = BookingModel::getTable();
$ptable = BookingArchiveModel::getTable();
$ctable = BookingResourceModel::getTable();

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['default'] = '{title_legend},email,uuid;{timing_legend},start,end;{data_legend},data,_bookedResources;{status_legend},status;';

$dca['config'] = [
    'dataContainer' => DC_Table::class,
    'ptable' => $ptable,
    'ctable' => [$ctable],
    'enableVersioning' => true,
    'sql' => [
        'keys' => [
            'id' => 'primary',
            'pid' => 'index',
            'uuid' => 'index,unique',
        ],
    ],
];

$dca['list'] = [
    'label' => [
        'fields' => ['email'],
        'format' => '%s',
    ],
    'sorting' => [
        'mode' => DataContainer::MODE_PARENT,
        'flag' => DataContainer::SORT_DESC,
        'fields' => ['start'],
        'disableGrouping' => true,
        'headerFields' => ['title', 'type'],
        'panelLayout' => 'filter;sort,search,limit',
    ],
    'global_operations' => [
        'all' => [
            'href' => 'act=select',
            'class' => 'header_edit_all',
            'attributes' => 'onclick="Backend.getScrollOffset();"',
        ],
    ],
    'operations' => [
        'edit',
        'children',
        'delete',
        'show',
    ],
];

$dca['fields'] = [
    'id' => [
        'sql' => 'int(10) unsigned NOT NULL auto_increment',
    ],
    'pid' => [
        'foreignKey' => "$ptable.title",
        'exclude' => true,
        'search' => true,
        'sql' => "int(10) unsigned NOT NULL default '0'",
        'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
    ],
    'tstamp' => [
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'status' => [
        'exclude' => true,
        'filter' => true,
        'inputType' => 'select',
        'eval' => ['doNotCopy' => true, 'tl_class' => 'clr w100', 'chosen' => true],
        'default' => null,
        'sql' => ['type' => 'string', 'length' => 64, 'default' => null, 'notnull' => false],
    ],
    'email' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'email'],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
    ],
    'uuid' => [
        'exclude' => true,
        'search' => true,
        'inputType' => 'text',
        'eval' => ['mandatory' => false, 'tl_class' => 'w50', 'readonly' => true],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
    ],
    'data' => [
        'exclude' => true,
        'search' => false,
        'sorting' => false,
        'inputType' => 'huh_rb_blobTable',
        'sql' => ['type' => 'blob', 'default' => null, 'notnull' => false],
    ],
    '_bookedResources' => [
        'exclude' => true,
        'search' => false,
        'sorting' => false,
        'inputType' => 'huh_rb_bookedResources',
        'sql' => null,
    ],
    'internalState' => [
        'exclude' => true,
        'search' => false,
        'sorting' => false,
        'sql' => ['type' => 'blob', 'default' => null, 'notnull' => false],
    ],
    'expiresAt' => [
        'exclude' => true,
        'sorting' => false,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
        'sql' => ['type' => 'string', 'length' => 10, 'default' => null, 'notnull' => false],
    ],
    'start' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true],
        'sql' => ['type' => 'string', 'length' => 10, 'default' => null, 'notnull' => false],
    ],
    'end' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true],
        'sql' => ['type' => 'string', 'length' => 10, 'default' => null, 'notnull' => false],
    ],
    'notifyOnStatusChange' => [
        'exclude' => true,
        'inputType' => 'checkbox',
        'eval' => ['tl_class' => 'w100 clr cbx'],
        'default' => true,
        'sql' => ['type' => 'boolean', 'default' => true, 'notnull' => true],
    ],
];
