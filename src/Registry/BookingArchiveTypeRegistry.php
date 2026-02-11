<?php

namespace HeimrichHannot\ResourceBookingBundle\Registry;

use HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType\BookingArchiveInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class BookingArchiveTypeRegistry
{
    private array $archiveTypes;

    public function __construct(
        #[TaggedIterator('huh.rb.booking_archive_type', defaultIndexMethod: 'getName')]
        private readonly iterable $archiveTypesIterable,
    ) {}

    private function resolve(): array
    {
        if (isset($this->archiveTypes)) {
            return $this->archiveTypes;
        }

        $this->archiveTypes = [];

        foreach ($this->archiveTypesIterable as $alias => $archiveType)
        {
            if (!$archiveType instanceof BookingArchiveInterface)
            {
                throw new \RuntimeException(\sprintf(
                    'Booking archive type "%s" must implement "%s".',
                    $alias,
                    BookingArchiveInterface::class,
                ));
            }

            if (!$alias || !\preg_match('/^[a-z0-9_-]+$/i', $alias))
            {
                throw new \RuntimeException(\sprintf(
                    'Booking archive type "%s" must define a valid alias.',
                    \get_class($archiveType),
                ));
            }

            $this->archiveTypes[$alias] = $archiveType;
        }

        \ksort($this->archiveTypes, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $this->archiveTypes;
    }

    public function all(): iterable
    {
        return $this->resolve();
    }

    /**
     * @param string $alias
     * @return BookingArchiveInterface|null
     */
    public function get(string $alias): ?BookingArchiveInterface
    {
        if (!$alias) {
            return null;
        }

        return $this->resolve()[$alias] ?? null;
    }

    public function aliases(): array
    {
        return \array_keys($this->resolve());
    }
}