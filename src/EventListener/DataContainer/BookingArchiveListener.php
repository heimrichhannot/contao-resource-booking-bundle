<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType\BookingArchiveInterface;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingArchiveListener
{
    public function __construct(
        private BookingArchiveTypeRegistry $typeRegistry,
        private FinderFactory              $finderFactory,
        private TranslatorInterface        $translator,
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