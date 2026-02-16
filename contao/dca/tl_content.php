<?php

use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;

$table = 'tl_content';
$bookingArchiveTable = BookingArchiveModel::getTable();
$resourceArchiveTable = ResourceArchiveModel::getTable();

$dca = &$GLOBALS['TL_DCA'][$table];

$dca['palettes']['huh_rb_opt_in'] = '{type_legend},type,headline;{template_legend:hide},customTpl,rb_showPlaceholder;';
$dca['palettes']['huh_rb_form'] = '{type_legend},type,headline;'
    . '{include_legend},rb_bookingArchive,rb_resourceArchives,rb_form;'
    . '{template_legend:hide},customTpl;'
;

$dca['fields']['rb_bookingArchive'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'multiple' => false,
        'chosen' => true,
        'tl_class' => 'w50',
        'mandatory' => true,
        'includeBlankOption' => true,
    ],
    'foreignKey' => "{$bookingArchiveTable}.title",
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'notnull' => true],
];
$dca['fields']['rb_resourceArchives'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'w50', 'mandatory' => true],
    'foreignKey' => "{$resourceArchiveTable}.title",
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];
$dca['fields']['rb_form'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => [
        'multiple' => false,
        'chosen' => true,
        'tl_class' => 'w50',
        'mandatory' => true,
        'includeBlankOption' => true,
    ],
    'foreignKey' => "tl_form.title",
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'notnull' => true],
];
$dca['fields']['rb_showPlaceholder'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'default' => false,
    'eval' => ['tl_class' => 'w50 cbx m12'],
    'sql' => ['type' => 'boolean', 'default' => false, 'notnull' => true],
];
