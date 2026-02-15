<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

class ResourceModel extends Model
{
    protected static $strTable = Table::RESOURCE->value;

    public static function findPublishedByPids(array $pids): ?Model\Collection
    {
        if (!$pids = \array_unique(\array_filter(\array_map('\intval', $pids)))) {
            return null;
        }

        $table = static::$strTable;

        return static::findBy([
            \sprintf("$table.published = ? AND $table.pid IN (%s)", \implode(',', $pids))
        ], [1], [
            'return' => 'Collection'
        ]);
    }
}