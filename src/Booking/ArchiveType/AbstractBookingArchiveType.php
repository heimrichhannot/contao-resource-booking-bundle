<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType;

abstract class AbstractBookingArchiveType implements BookingArchiveInterface
{
    public static function getName(): string
    {
        $fqcn = static::class;

        $pos = \strrpos($fqcn, '\\');
        $className = $pos === false ? $fqcn : \substr($fqcn, $pos + 1);

        $suffixes = ['BookingArchiveType', 'ArchiveType', 'BookingType', 'Type'];
        foreach ($suffixes as $suffix) {
            if (\str_ends_with($className, $suffix)) {
                $className = \substr($className, 0, -\strlen($suffix));
                break;
            }
        }

        $className = \preg_replace('/\W/', '', $className);
        $snake = \preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $className);
        $snake = \preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $snake);
        $snake = \strtolower($snake);

        return $snake;
    }

    public static function getTemplate(): string
    {
        $name = static::getName();
        return "resource_booking/calendar/{$name}";
    }

    public static function hasTime(): bool
    {
        return false;
    }
}