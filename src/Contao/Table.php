<?php

namespace HeimrichHannot\ResourceBookingBundle\Contao;

use Contao\Controller;

enum Table: string
{
    case BOOKING = 'tl_rb_booking';
    case BOOKING_ARCHIVE = 'tl_rb_booking_archive';
    case BOOKING_RESOURCE = 'tl_rb_booking_resource';
    case RESOURCE = 'tl_rb_resource';
    case RESOURCE_ARCHIVE = 'tl_rb_resource_archive';

    private function getDcaFields(): array
    {
        return $GLOBALS['TL_DCA'][$this->value]['fields'];
    }

    public function fields(string $purpose): iterable
    {
        Controller::loadDataContainer($this->value);

        foreach ($this->getDcaFields() as $key => $field) {
            if ($key && \is_array($field) && $field['huh_rb'][$purpose] ?? false) {
                yield $key => $field;
            }
        }
    }

    public function select(string $purpose): iterable
    {
        foreach ($this->fields($purpose) as $key => $field) {
            $var = $field['huh_rb'][$purpose]['alias'] ?? $field['huh_rb'][$purpose] ?? null;
            yield $key => \is_string($var) ? $var : $key;
        }
    }
}