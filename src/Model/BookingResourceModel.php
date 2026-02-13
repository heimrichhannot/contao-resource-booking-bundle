<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class BookingResourceModel extends Model
{
    protected static $strTable = Table::BOOKING_RESOURCE->value;
}