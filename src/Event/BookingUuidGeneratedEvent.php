<?php

namespace HeimrichHannot\ResourceBookingBundle\Event;

class BookingUuidGeneratedEvent
{
    public function __construct(
        public string $uuid,
        public readonly array $row,
        private readonly \Closure $testUnique,
    ) {}

    public function isUnique(?string $uuid = null): bool
    {
        $uuid ??= $this->uuid;
        return ($this->testUnique)($uuid);
    }
}