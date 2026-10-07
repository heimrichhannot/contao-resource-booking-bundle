<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Payload;

use HeimrichHannot\ResourceBookingBundle\Exception\InvalidBookingPayloadException;

/**
 * Parses the `rb_data` field of the booking form.
 *
 * The payload is untrusted input: anything that does not exactly match the format the booking form produces is
 * rejected instead of being corrected.
 */
final readonly class BookingPayloadParser
{
    /** Maximum size of the JSON payload after Contao's entity encoding is decoded */
    public const MAX_PAYLOAD_BYTES = 4096;

    /** Contao encodes '"' as '&#34;', so each character of a valid payload is at most 5 bytes before decoding */
    private const MAX_ENCODED_BYTES_PER_CHAR = 5;
    private const JSON_DEPTH = 4;
    private const PAYLOAD_KEYS = ['end', 'resources', 'start'];
    private const RESOURCE_KEYS = ['id', 'quantity'];
    /** ISO 8601 with milliseconds and Z or the sender's UTC offset, e.g. 2027-04-06T00:00:00.000+09:00 */
    private const DATE_PATTERN = '/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2}\.\d{3})(Z|[+-](?:0\d|1[0-4]):[0-5]\d)$/';
    private const DATE_FORMAT = 'Y-m-d\TH:i:s.v';

    /**
     * @param string        $raw                The submitted form value, possibly HTML-entity-encoded by Contao.
     * @param int[]         $allowedResourceIds IDs of the resources that can be booked with this form.
     * @param \DateTimeZone $timezone           The server's time zone, in which the booked days are returned.
     *
     * @throws InvalidBookingPayloadException
     */
    public function parse(string $raw, array $allowedResourceIds, \DateTimeZone $timezone): BookingPayload
    {
        // Checked before decoding too, so oversized input is not decoded at all
        if ($raw === '' || \strlen($raw) > self::MAX_PAYLOAD_BYTES * self::MAX_ENCODED_BYTES_PER_CHAR) {
            throw new InvalidBookingPayloadException('Payload is empty or too large.');
        }

        // Contao encodes some characters of submitted values as HTML entities
        $json = \str_contains($raw, '&') ? \html_entity_decode($raw, \ENT_QUOTES | \ENT_HTML5, 'UTF-8') : $raw;

        if (\strlen($json) > self::MAX_PAYLOAD_BYTES) {
            throw new InvalidBookingPayloadException('Payload is empty or too large.');
        }

        try {
            $payload = \json_decode($json, true, self::JSON_DEPTH, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidBookingPayloadException('Payload is not valid JSON.');
        }

        if (!\is_array($payload) || !$this->hasExactKeys($payload, self::PAYLOAD_KEYS)) {
            throw new InvalidBookingPayloadException('Payload must contain exactly the keys resources, start and end.');
        }

        $resourceIds = $this->parseResources($payload['resources'], $allowedResourceIds);
        $start = $this->parseDay($payload['start'], $timezone);
        $end = $this->parseDay($payload['end'], $timezone);

        if ($end < $start) {
            throw new InvalidBookingPayloadException('End must not be before start.');
        }

        return new BookingPayload($resourceIds, $start, $end);
    }

    /**
     * @return int[]
     */
    private function parseResources(mixed $resources, array $allowedResourceIds): array
    {
        if (!\is_array($resources) || !$resources || !\array_is_list($resources)) {
            throw new InvalidBookingPayloadException('Resources must be a non-empty list.');
        }

        $allowed = \array_map('\intval', $allowedResourceIds);
        $ids = [];

        foreach ($resources as $resource)
        {
            if (!\is_array($resource) || !$this->hasExactKeys($resource, self::RESOURCE_KEYS)) {
                throw new InvalidBookingPayloadException('Each resource must contain exactly the keys id and quantity.');
            }

            $id = $resource['id'];

            if (!\is_int($id) || $id < 1) {
                throw new InvalidBookingPayloadException('Resource ID must be a positive integer.');
            }

            // Only single units can be booked
            if ($resource['quantity'] !== 1) {
                throw new InvalidBookingPayloadException('Resource quantity must be 1.');
            }

            if (\in_array($id, $ids, true)) {
                throw new InvalidBookingPayloadException('Resource IDs must be unique.');
            }

            if (!\in_array($id, $allowed, true)) {
                throw new InvalidBookingPayloadException('Resource is not bookable with this form.');
            }

            $ids[] = $id;
        }

        return $ids;
    }

    private function parseDay(mixed $value, \DateTimeZone $timezone): \DateTimeImmutable
    {
        if (!\is_string($value) || !\preg_match(self::DATE_PATTERN, $value, $matches)) {
            throw new InvalidBookingPayloadException('Dates must be ISO 8601 timestamps with milliseconds and a UTC offset.');
        }

        [, $day, $time, $offset] = $matches;
        $local = "{$day}T$time";
        $date = \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $local, new \DateTimeZone($offset === 'Z' ? 'UTC' : $offset));

        // Formatting back rejects overflowing values like 2027-02-31 that PHP would silently roll over
        if (!$date || $date->format(self::DATE_FORMAT) !== $local) {
            throw new InvalidBookingPayloadException('Date does not exist.');
        }

        // Older clients send UTC without their offset, so only the server's time zone can tell the day
        if ($offset === 'Z') {
            return $date->setTimezone($timezone)->setTime(0, 0);
        }

        // The booked day is the calendar day the visitor picked in their own time zone
        return \DateTimeImmutable::createFromFormat('!Y-m-d', $day, $timezone);
    }

    private function hasExactKeys(array $array, array $sortedKeys): bool
    {
        $keys = \array_keys($array);
        \sort($keys);

        return $keys === $sortedKeys;
    }
}
