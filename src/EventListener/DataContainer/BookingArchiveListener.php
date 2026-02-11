<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Registry\ArchiveTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingArchiveListener
{
    public function __construct(
        private ArchiveTypeRegistry $typeRegistry,
        private TranslatorInterface $translator,
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