<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Fixtures;

use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

/**
 * A booking that works without the Contao framework: keeps the real internal state handling of BookingModel, but
 * stores values without type conversion and counts saves instead of writing to the database.
 */
class FakeBookingModel extends BookingModel
{
    public int $saves = 0;

    public function __construct(array $data = [])
    {
        $this->arrData = $data;
    }

    public function __set($strKey, $varValue)
    {
        $this->arrData[$strKey] = $varValue;
    }

    public function save()
    {
        ++$this->saves;

        return $this;
    }
}
