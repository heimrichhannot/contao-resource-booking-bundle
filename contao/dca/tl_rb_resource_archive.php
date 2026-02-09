<?php

use Contao\DataContainer;
use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

$table = Table::RESOURCE_ARCHIVE->value;
$ctable = Table::RESOURCE->value;

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['__selector__'] = [];
$dca['palettes']['default'] = '{title_legend},title,alias;'
    . '{publishing_legend},published;';

$dca['config'] = [
    'dataContainer' => DC_Table::class,
    'ctable' => [$ctable],
    'enableVersioning' => true,
    'sql' => [
        'keys' => [
            'id' => 'primary',
        ],
    ],
];

$dca['list'] = [
    'label' => [
        'fields' => ['title'],
        'format' => '%s',
    ],
    'sorting' => [
        'mode' => 2,
        'fields' => ['title'],
        'headerFields' => ['title'],
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
        'children' => [
            'href' => "table=$ctable",
            'icon' => 'children.svg',
        ],
        'edit' => [
            'href' => 'act=edit',
            'icon' => 'edit.svg',
        ],
        'copy' => [
            'href' => 'act=copy',
            'icon' => 'copy.svg',
        ],
        'toggle' => 'toggle',
        'delete' => [
            'href' => 'act=delete',
            'icon' => 'delete.svg',
            'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'Confirm delete').'\'))return false;Backend.getScrollOffset()"',
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
    'tstamp' => [
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'title' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
    ],
    'alias' => [
        'search' => true,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => "varchar(255) BINARY NOT NULL default ''"
    ],
    'published' => [
        'toggle' => true,
        'filter' => true,
        'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
        'inputType' => 'checkbox',
        'eval' => ['doNotCopy' => true],
        'sql' => ['type' => 'boolean', 'default' => false],
    ],
];

