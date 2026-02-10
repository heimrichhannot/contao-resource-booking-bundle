<?php

use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

$table = BookingModel::getTable();
$ptable = BookingArchiveModel::getTable();

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['default'] = '{title_legend},title,alias;{publishing_legend},published;';

$dca['config'] = [
    'dataContainer' => DC_Table::class,
    'ptable' => $ptable,
    'enableVersioning' => true,
    'sql' => [
        'keys' => [
            'id' => 'primary',
            'pid' => 'index',
        ],
    ],
];

$dca['list'] = [
    'label' => [
        'fields' => ['title'],
        'format' => '%s',
    ],
    'sorting' => [
        'mode' => 4,
        'fields' => ['title'],
        'headerFields' => ['title', 'tstamp'],
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
        'edit'=> [
            'href' => 'act=edit',
            'icon' => 'edit.svg',
        ],
        'delete' => [
            'href' => 'act=delete',
            'icon' => 'delete.svg',
            'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"',
        ],
        'toggle' => [
            'icon' => 'visible.svg',
            'attributes' => 'onclick="Backend.getScrollOffset();"',
        ],
        'show' => [
            'href' => 'act=show',
            'icon' => 'show.svg',
        ],
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
        'inputType' => 'checkbox',
        'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
        'sql' => "char(1) NOT NULL default ''",
    ],
    'title' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
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
    'data' => [
        'exclude' => true,
        'search' => false,
        'sorting' => false,
        'sql' => ['type' => 'blob', 'default' => null, 'notnull' => false],
    ],
    'internalState' => [
        'exclude' => true,
        'search' => false,
        'sorting' => false,
        'sql' => ['type' => 'blob', 'default' => null, 'notnull' => false],
    ],
    'expiresAt' => [
        'exclude' => true,
        'sorting' => true,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
        'sql' => ['type' => 'string', 'length' => 10, 'default' => null, 'notnull' => false],
    ],
];
