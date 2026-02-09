<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class ResourceArchiveModel extends Model
{
    protected static $strTable = Table::RESOURCE_ARCHIVE->value;
}