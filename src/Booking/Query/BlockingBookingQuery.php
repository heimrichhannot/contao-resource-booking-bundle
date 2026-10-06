<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

/**
 * The single definition of which bookings block their resources.
 *
 * Used by the booking API (calendar) and by the server-side availability check, so both always agree.
 */
final readonly class BlockingBookingQuery
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * Restricts a query on tl_rb_booking to blocking bookings: not rejected, and not an expired reservation (e.g. an
     * unconfirmed opt-in).
     */
    public function restrict(QueryBuilder $qb, string $alias, ?int $now = null): QueryBuilder
    {
        return $qb
            ->andWhere("($alias.status IS NULL OR $alias.status <> :rb_rejected)")
            ->andWhere("($alias.expiresAt IS NULL OR $alias.expiresAt = '' OR CAST($alias.expiresAt AS UNSIGNED) >= :rb_now)")
            ->setParameter('rb_rejected', FinalState::REJECTED->value)
            ->setParameter('rb_now', $now ?? \time());
    }

    /**
     * Whether any of the resources is blocked on at least one day between start and end (both inclusive).
     *
     * Bookings are compared by day in the time zone of $start, the same way the calendar blocks whole days. Bookings
     * of all booking archives are considered, since a resource cannot be in two places at once.
     *
     * @param int[] $resourceIds
     */
    public function hasOverlap(array $resourceIds, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        if (!$resourceIds) {
            return false;
        }

        $timezone = $start->getTimezone();
        $startDay = $start->setTime(0, 0);
        $endDay = $end->setTime(0, 0);

        // Coarse pre-selection with a margin, the exact comparison by day happens below
        $margin = 2 * 86400;

        $qb = $this->connection->createQueryBuilder()
            ->select('b.start', 'b.end')
            ->from(Table::BOOKING->value, 'b')
            ->innerJoin('b', Table::BOOKING_RESOURCE->value, 'br', 'br.pid = b.id')
            ->where('br.resourceId IN (:resourceIds)')
            ->andWhere('CAST(b.start AS SIGNED) <= :windowEnd')
            ->andWhere('CAST(b.end AS SIGNED) >= :windowStart')
            ->setParameter('resourceIds', \array_map('\intval', $resourceIds), ArrayParameterType::INTEGER)
            ->setParameter('windowStart', $startDay->getTimestamp() - $margin)
            ->setParameter('windowEnd', $endDay->getTimestamp() + $margin);

        $result = $this->restrict($qb, 'b')->executeQuery();
        $rows = $result->fetchAllAssociative();
        $result->free();

        foreach ($rows as $row)
        {
            $blockedStart = (new \DateTimeImmutable('@' . (int) $row['start']))->setTimezone($timezone)->setTime(0, 0);
            $blockedEnd = (new \DateTimeImmutable('@' . (int) $row['end']))->setTimezone($timezone)->setTime(0, 0);

            if ($startDay <= $blockedEnd && $endDay >= $blockedStart) {
                return true;
            }
        }

        return false;
    }
}
