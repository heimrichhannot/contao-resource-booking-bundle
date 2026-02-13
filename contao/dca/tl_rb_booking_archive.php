<?php

use Contao\DataContainer;
use Contao\DC_Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

$table = BookingArchiveModel::getTable();
$ctable = BookingModel::getTable();

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['__selector__'] = ['requireOptIn', 'requireReview'];
$dca['palettes']['default'] = '{title_legend},title,type,alias;'
    . '{opt_in_legend},requireOptIn;'
    . '{approval_legend},requireReview;'
    . '{template_legend},customTpl;'
    . '{publishing_legend},published;';

$dca['subpalettes']['requireOptIn'] = 'nc_optInRequest,jumpToOptInCompleted';
$dca['subpalettes']['requireReview'] = 'nc_reviewRequest,nc_reviewApproval,nc_reviewRejection';

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

$fieldRequire = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'default' => false,
    'eval' => ['tl_class' => 'w50 cbx', 'submitOnChange' => true],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$fieldNotification = static fn (bool $mandatory): array => [
    'exclude' => true,
    'filter' => false,
    'inputType' => 'select',
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50', 'mandatory' => $mandatory],
    'sql' => ['type' => 'integer', 'default' => 0, 'unsigned' => true],
];

$fieldJumpTo = [
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'default' => 0,
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'w50'],
    'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'notnull' => true],
];

$dca['fields'] = [
    'id' => [
        'sql' => 'int(10) unsigned NOT NULL auto_increment',
        'huh_rb' => [
            'api' => true,
        ],
    ],
    'tstamp' => [
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'type' => [
        'exclude' => true,
        'filter' => true,
        'sorting' => true,
        'flag' => DataContainer::SORT_ASC,
        'inputType' => 'select',
        'eval' => [
            'mandatory' => true,
            'includeBlankOption' => true,
            'tl_class' => 'w50',
            'multiple' => false,
            'chosen' => true,
            'submitOnChange' => true,
        ],
        'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
    ],
    'title' => [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
        'inputType' => 'text',
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        'huh_rb' => [
            'api' => true,
        ],
    ],
    'alias' => [
        'search' => true,
        'inputType' => 'text',
        'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => "varchar(255) BINARY NOT NULL default ''",
        'huh_rb' => [
            'api' => true,
        ],
    ],
    'published' => [
        'toggle' => true,
        'filter' => true,
        'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
        'inputType' => 'checkbox',
        'eval' => ['doNotCopy' => true],
        'sql' => ['type' => 'boolean', 'default' => false],
    ],
    'customTpl' => [
        'exclude' => true,
        'inputType' => 'select',
        'eval' => ['chosen' => true, 'tl_class' => 'w50'],
        'sql' => [
            'type' => 'string',
            'length' => 128,
            'default' => '',
            'notnull' => true,
            'customSchemaOptions' => ['collation' => 'ascii_bin'],
        ],
    ],
    'requireOptIn' => $fieldRequire,
    'requireReview' => $fieldRequire,
    'nc_optInRequest' => $fieldNotification(mandatory: false),
    'nc_reviewRequest' => $fieldNotification(mandatory: true),
    'nc_reviewApproval' => $fieldNotification(mandatory: true),
    'nc_reviewRejection' => $fieldNotification(mandatory: true),
    'jumpToOptInCompleted' => $fieldJumpTo,
];
