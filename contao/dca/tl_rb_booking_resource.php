<?php

use Contao\DataContainer;
use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

$table = Table::BOOKING_RESOURCE->value;
$bookingTable = Table::BOOKING->value;
$resourceTable = Table::RESOURCE->value;
$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['default'] = '{title_legend},resourceId,quantity;';

$dca['config'] = [
    'dataContainer' => DC_Table::class,
    'ptable' => $bookingTable,
    'enableVersioning' => false,
    'sql' => [
        'keys' => [
            'id' => 'primary',
            'pid' => 'index',
            'resourceId' => 'index',
            'pid,resourceId' => 'unique',
        ],
    ],
];

$dca['list'] = [
    'label' => [
        'fields' => ['resourceId'],
        'format' => '%s',
    ],
    'sorting' => [
        'mode' => DataContainer::MODE_PARENT,
        'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
        'fields' => ['resourceId'],
        'disableGrouping' => true,
        'headerFields' => ['uuid', 'email', 'start', 'end'],
        'panelLayout' => 'filter;sort,limit',
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
        'show' => [
            'href' => 'act=show',
            'icon' => 'show.svg',
        ],
    ],
];

$dca['fields'] = [
    'id' => [
        'sql' => ['type' => 'integer', 'autoincrement' => true, 'unsigned' => true, 'notnull' => true],
    ],
    'pid' => [
        'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => true],
        'foreignKey' => "{$bookingTable}.id",
    ],
    'tstamp' => [
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'resourceId' => [
        'exclude' => true,
        'load' => 'lazy',
        'inputType' => 'select',
        'eval' => ['chosen' => true, 'tl_class' => 'w50', 'mandatory' => true, 'includeBlankOption' => true],
        'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => true, 'default' => 0],
        'foreignKey' => "{$resourceTable}.title",
    ],
    'quantity' => [
        'exclude' => true,
        'inputType' => 'text',
        'default' => 1,
        'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50', 'mandatory' => true],
        'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => true, 'default' => 1],
    ],
];
