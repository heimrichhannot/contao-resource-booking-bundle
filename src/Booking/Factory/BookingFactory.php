<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Factory;

use Contao\Validator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayload;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\OptInStep;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Event\BookingUuidGeneratedEvent;
use HeimrichHannot\ResourceBookingBundle\Exception\BookingUnavailableException;
use HeimrichHannot\ResourceBookingBundle\Exception\InvalidBookingPayloadException;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class BookingFactory
{
    /** Length of tl_rb_booking.email */
    private const MAX_EMAIL_LENGTH = 255;
    /** tl_rb_booking.data is a BLOB (64 KiB) */
    private const MAX_DATA_BYTES = 60000;

    public function __construct(
        private Connection               $connection,
        private EventDispatcherInterface $eventDispatcher,
        private BookingPayloadParser     $payloadParser,
        private BlockingBookingQuery     $blockingBookingQuery,
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
     * @throws InvalidBookingPayloadException If the submitted data is invalid.
     * @throws BookingUnavailableException If a requested resource is already booked in the requested period.
     * @throws \RuntimeException If the booking could not be created.
     */
    public function createFromSubmittedData(
        BookingArchiveModel $archive,
        array               $data,
        array               $allowedResources
    ): BookingModel {
        $timezone = new \DateTimeZone(\date_default_timezone_get());

        $payload = $this->payloadParser->parse(
            raw: \is_string($data['rb_data'] ?? null) ? $data['rb_data'] : '',
            allowedResourceIds: $allowedResources,
            timezone: $timezone,
        );

        $this->assertWithinArchiveLimits($archive, $payload, $timezone);

        $email = \is_string($data['email'] ?? null) ? \html_entity_decode($data['email']) : '';
        if (\strlen($email) > self::MAX_EMAIL_LENGTH
            || !\filter_var($email, \FILTER_VALIDATE_EMAIL)
            || !Validator::isEmail($email))
        {
            throw new InvalidBookingPayloadException('Invalid email address.');
        }

        unset($data['rb_data'], $data['email'], $data['FORM_SUBMIT'], $data['REQUEST_TOKEN']);
        \array_walk(
            $data,
            static fn (&$value) => \is_string($value)
                ? ($value = \html_entity_decode($value, \ENT_QUOTES, 'UTF-8'))
                : null /* skip non-string values */
        );

        $serializedData = \serialize($data);
        if (\strlen($serializedData) > self::MAX_DATA_BYTES) {
            throw new InvalidBookingPayloadException('Submitted form data is too large.');
        }

        // Lock the resources so concurrent requests cannot book the same period between the check and the insert
        return $this->connection->transactional(function () use ($archive, $payload, $email, $serializedData): BookingModel {
            $this->lockResources($payload->resourceIds);

            if ($this->blockingBookingQuery->hasOverlap((int) $archive->id, $payload->resourceIds, $payload->start, $payload->end)) {
                throw new BookingUnavailableException('A requested resource is not available in the requested period.');
            }

            $booking = new BookingModel();
            $booking->tstamp = \time();
            $booking->pid = $archive->id;
            $booking->email = $email;
            $booking->start = $payload->start->getTimestamp();
            $booking->end = $payload->end->getTimestamp();
            $booking->data = $serializedData;
            $booking->status = '';
            // Reserve only temporarily until the opt-in is confirmed, even if the pipeline fails before the opt-in step
            $booking->expiresAt = $archive->requireOptIn ? \time() + OptInStep::RESERVATION_TTL : null;
            $booking->uuid = $this->createBookingUuid($booking->row());
            $booking->save();

            foreach ($payload->resourceIds as $resource) {
                $bookingResource = new BookingResourceModel();
                $bookingResource->tstamp = \time();
                $bookingResource->pid = $booking->id;
                $bookingResource->resourceId = $resource;
                $bookingResource->quantity = 1;
                $bookingResource->save();
            }

            return $booking;
        });
    }

    /**
     * @throws InvalidBookingPayloadException If the period violates the limits configured in the booking archive.
     */
    private function assertWithinArchiveLimits(
        BookingArchiveModel $archive,
        BookingPayload      $payload,
        \DateTimeZone       $timezone,
    ): void {
        $today = new \DateTimeImmutable('today', $timezone);
        $minAdvanceDays = (int) $archive->minAdvanceDays;
        $maxAdvanceDays = (int) $archive->maxAdvanceDays;
        $maxDurationDays = (int) $archive->maxDurationDays;

        if ($maxAdvanceDays < 1 || $maxDurationDays < 1) {
            throw new InvalidBookingPayloadException('Booking archive has no valid limits configured.');
        }

        if ($payload->start < $today->modify("+$minAdvanceDays days")) {
            throw new InvalidBookingPayloadException('Start is earlier than allowed.');
        }

        if ($payload->end > $today->modify("+$maxAdvanceDays days")) {
            throw new InvalidBookingPayloadException('End is later than allowed.');
        }

        if ($payload->getDurationDays() > $maxDurationDays) {
            throw new InvalidBookingPayloadException('Booking period is longer than allowed.');
        }
    }

    /**
     * @param int[] $resourceIds
     */
    private function lockResources(array $resourceIds): void
    {
        $result = $this->connection->executeQuery(
            'SELECT id FROM ' . Table::RESOURCE->value . ' WHERE id IN (?) ORDER BY id FOR UPDATE',
            [$resourceIds],
            [ArrayParameterType::INTEGER],
        );
        $lockedIds = $result->fetchFirstColumn();
        $result->free();

        if (\count($lockedIds) !== \count($resourceIds)) {
            throw new InvalidBookingPayloadException('A requested resource does not exist.');
        }
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