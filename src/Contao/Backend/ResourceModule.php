<?php

namespace HeimrichHannot\ResourceBookingBundle\Contao\Backend;

use HeimrichHannot\ResourceBookingBundle\Contao\Table;

readonly class ResourceModule
{
    public const CATEGORY = 'content';
    public const NAME = 'rb_resource';

    public static function getTables(): array
    {
        return [
            Table::RESOURCE->value,
            Table::RESOURCE_ARCHIVE->value,
        ];
    }
}