<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Factory;

use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Event\BookingUuidGeneratedEvent;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class BookingFactory
{
    public function __construct(
        private Connection               $connection,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param BookingArchiveModel $archive The booking archive.
     * @param array{
     *     rb_data: string,
     *     email: string,
     *     FORM_SUBMIT?: string,
     *     REQUEST_TOKEN?: string,
     *     ...string
     * } $data The submitted form data.
     * @param int[] $allowedResources The IDs of the resources that can be booked.
     * @return BookingModel
     * @throws \RuntimeException If the payload is invalid or the booking could not be created.
     */
    public function createFromSubmittedData(
        BookingArchiveModel $archive,
        array               $data,
        array               $allowedResources
    ): BookingModel {
        try {
            $payloadData = (string) ($data['rb_data'] ?? '');
            if (\str_contains($payloadData, '&#')) {
                $payloadData = \html_entity_decode($payloadData);
            }
            $payload = \json_decode($payloadData, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Could not decode JSON payload.');
        }

        if (!\is_array($payload)
            || !($resources = $payload['resources'] ?? null)
            || !\is_array($resources)
            || !($start = $payload['start'] ?? null)
            || !\is_string($start)
            || !($end = $payload['end'] ?? null)
            || !\is_string($end))
        {
            throw new \RuntimeException('JSON payload does not contain required fields (resources, start, end).');
        }

        $start = \DateTimeImmutable::createFromFormat(\DATE_RFC3339_EXTENDED, $start);
        $end = \DateTimeImmutable::createFromFormat(\DATE_RFC3339_EXTENDED, $end);

        if (!$start || !$end) {
            throw new \RuntimeException('Invalid date format for start or end.');
        }

        $email = \html_entity_decode($data['email'] ?? '');
        if (!$email || !\filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('messages.invalid_submission');
        }

        unset($data['rb_data'], $data['email'], $data['FORM_SUBMIT'], $data['REQUEST_TOKEN']);
        \array_walk(
            $data,
            static fn (&$value) => \is_string($value)
                ? ($value = \html_entity_decode($value, \ENT_QUOTES, 'UTF-8'))
                : null /* skip non-string values */
        );

        $booking = new BookingModel();
        $booking->tstamp = \time();
        $booking->pid = $archive->id;
        $booking->email = $email;
        $booking->start = $start->getTimestamp();
        $booking->end = $end->getTimestamp();
        $booking->data = \serialize($data);
        $booking->status = '';
        $booking->uuid = $this->createBookingUuid($booking->row());
        $booking->save();

        $resources = \array_map(static fn (array $value) => (int) ($value['id'] ?? 0), $resources);
        $allowedResources = \array_map('\intval', $allowedResources);
        $resources = \array_intersect($resources, $allowedResources);

        foreach ($resources as $resource) {
            $bookingResource = new BookingResourceModel();
            $bookingResource->tstamp = \time();
            $bookingResource->pid = $booking->id;
            $bookingResource->resourceId = $resource;
            $bookingResource->quantity = 1;
            $bookingResource->save();
        }

        return $booking;
    }

    public function createBookingUuid(array $row): string
    {
        $uuid = Uuid::v4()->toRfc4122();

        $event = $this->eventDispatcher->dispatch(
            new BookingUuidGeneratedEvent(uuid: $uuid, row: $row, testUnique: $this->testUniqueUuid(...))
        );

        if (!$uuid = $event->uuid) {
            throw new \RuntimeException('No UUID generated.');
        }

        if (!$this->testUniqueUuid($uuid)) {
            throw new \RuntimeException('UUID not unique.');
        }

        return $uuid;
    }

    private function testUniqueUuid(string $uuid): bool
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id')
            ->from(Table::BOOKING->value)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->executeQuery();
        $rowCount = $result->rowCount();
        $result->free();
        return $rowCount === 0;
    }
}