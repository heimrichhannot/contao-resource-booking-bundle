<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class BookingResourceModel extends Model
{
    protected static $strTable = Table::BOOKING_RESOURCE->value;

    public static function findMultipleByPids(array $pids): ?Model\Collection
    {
        if (!$pids = \array_unique(\array_filter(\array_map('\intval', $pids)))) {
            return null;
        }

        return static::findBy([\sprintf('pid IN (%s)', \implode(',', $pids))], [], [
            'return' => 'Collection'
        ]);
    }
}