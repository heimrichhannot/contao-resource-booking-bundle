<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType\BookingArchiveInterface;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingArchiveListener
{
    public function __construct(
        private BookingArchiveTypeRegistry $typeRegistry,
        private FinderFactory              $finderFactory,
        private TranslatorInterface        $translator,
        private RequestStack               $requestStack,
    ) {}

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'fields.type.options')]
    public function getTypeOptions(?DataContainer $dc = null): array
    {
        $options = [];

        foreach ($this->typeRegistry->all() as $alias => $type) {
            $options[$alias] = $this->translator->trans("booking_archive_types.$alias", [], 'huh_rb');
        }

        return $options;
    }

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'fields.customTpl.options')]
    public function getCustomTplOptions(?DataContainer $dc = null): array
    {
        if (!$dc || !$dc->id) {
            return [];
        }

        $record = $dc->getCurrentRecord();
        if (!$type = $record['type'] ?? null) {
            return [];
        }

        /** @var BookingArchiveInterface $archiveType */
        if (!$archiveType = $this->typeRegistry->get($type)) {
            return [];
        }

        return $this->finderFactory
            ->create()
            ->identifier($archiveType->getTemplate())
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions();
    }

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'fields.minAdvanceDays.save')]
    public function validateMinAdvanceDays(mixed $value, DataContainer $dc): mixed
    {
        $this->assertAdvanceRange((int) $value, (int) $this->submittedOrStored('maxAdvanceDays', $dc));

        return $value;
    }

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'fields.maxAdvanceDays.save')]
    public function validateMaxAdvanceDays(mixed $value, DataContainer $dc): mixed
    {
        $this->assertAdvanceRange((int) $this->submittedOrStored('minAdvanceDays', $dc), (int) $value);

        return $value;
    }

    /**
     * The other field's value of the same submission (also in "edit multiple" mode), or the stored one if it was not
     * submitted.
     */
    private function submittedOrStored(string $field, DataContainer $dc): mixed
    {
        $post = $this->requestStack->getCurrentRequest()?->request;

        return $post?->get($field)
            ?? $post?->get("{$field}_{$dc->id}")
            ?? ($dc->getCurrentRecord() ?? [])[$field]
            ?? null;
    }

    private function assertAdvanceRange(int $minAdvanceDays, int $maxAdvanceDays): void
    {
        if ($maxAdvanceDays <= $minAdvanceDays) {
            throw new \RuntimeException($this->translator->trans('backend.advance_days_invalid', [], 'huh_rb'));
        }
    }

    #[AsCallback(Table::BOOKING_ARCHIVE->value, 'list.label.label')]
    public function getListLabels(array $row, string $label, DataContainer $dc, array $labels): array
    {
        $cteType = $row['published'] ? 'published' : 'unpublished';
        $typeLabel = $this->translator->trans("booking_archive_types.{$row['type']}", [], 'huh_rb');

        return [
            <<<LABEL
            <div class="cte_type {$cteType}" style="margin-bottom: .5rem">[$typeLabel]</div>
            <div>$label</div>
            LABEL,
        ];
    }
}