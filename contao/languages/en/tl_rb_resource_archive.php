<?php

use HeimrichHannot\ResourceBookingBundle\Model\ResourceArchiveModel;

$lang = &$GLOBALS['TL_LANG'][ResourceArchiveModel::getTable()];

$lang['title_legend'] = 'General settings';
$lang['title'] = ['Title', 'The title under which this resource archive is displayed.'];
$lang['alias'] = ['Alias', 'The alias of the resource archive.'];
$lang['tstamp'] = ['Revision date', 'The date and time of the last revision of the resource archive.'];

$lang['publishing_legend'] = 'Publishing';
$lang['published'] = ['Published', 'Make the resource archive publicly visible on the website.'];
