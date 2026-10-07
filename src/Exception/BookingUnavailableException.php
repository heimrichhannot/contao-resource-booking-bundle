<?php

namespace HeimrichHannot\ResourceBookingBundle\Exception;

/**
 * Thrown when a requested resource is already booked for (part of) the requested period.
 */
class BookingUnavailableException extends \RuntimeException
{
}
