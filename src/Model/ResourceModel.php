<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class ResourceModel extends Model
{
    protected static $strTable = Table::RESOURCE->value;
}