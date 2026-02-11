<?php

use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;

$table = ResourceModel::getTable();
$ptable = ResourceArchiveModel::getTable();

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['default'] = '{title_legend},title,quantity;{publishing_legend},published;';

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
        'huh_rb' => [
            'api' => true,
        ],
    ],
    'pid' => [
        'foreignKey' => "$ptable.title",
        'exclude' => true,
        'search' => true,
        'sql' => "int(10) unsigned NOT NULL default '0'",
        'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
        'huh_rb' => [
            'api' => 'archive_id',
        ],
    ],
    'tstamp' => [
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'title' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
        'huh_rb' => [
            'api' => true,
        ],
    ],
    'quantity' => [
        'exclude' => true,
        'sorting' => true,
        'filter' => true,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'natural'],
        'sql' => ['type' => 'integer', 'default' => 1],
        'huh_rb' => [
            'api' => true,
        ],
    ],
];
