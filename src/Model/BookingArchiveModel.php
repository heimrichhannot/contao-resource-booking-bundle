<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class BookingArchiveModel extends Model
{
    protected static $strTable = Table::BOOKING_ARCHIVE->value;
}