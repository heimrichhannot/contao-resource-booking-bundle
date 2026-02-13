<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingListener
{
    public function __construct(
        private Connection                 $connection,
        private BookingArchiveTypeRegistry $archiveTypeRegistry,
        private BookingFactory             $bookingFactory,
        private TranslatorInterface        $translation,
    ) {}

    #[AsCallback(Table::BOOKING->value, 'config.oncreate')]
    public function onCreateConfig(string $table, int $id, array $row, DataContainer $dc): void
    {
        if ($table !== Table::BOOKING->value || !$id || !$dc->id || $dc->table !== $table) {
            return;
        }

        if ($row['uuid'] ?? null) {
            return;
        }

        $uuid = $this->bookingFactory->createBookingUuid($row);

        $this->connection->createQueryBuilder()
            ->update(Table::BOOKING->value)
            ->set('uuid', ':uuid')
            ->where('id = :id')
            ->setParameter('uuid', $uuid)
            ->setParameter('id', $id)
            ->executeStatement();
    }

    #[AsCallback(Table::BOOKING->value, 'fields.status.options')]
    public function getStatusOptions(DataContainer $dc): array
    {
        if (!$dc || !$dc->id || !$row = $dc->getCurrentRecord()) {
            return [];
        }

        $currentStatus = $row['status'] ?? null;
        $finalState = FinalState::tryFrom($currentStatus);

        $options = [];
        if (!$finalState) {
            $options[$currentStatus] = $this->getStatusLabel((string) $currentStatus);
        }

        foreach (FinalState::cases() as $state) {
            $options[$state->value] = $this->translation->trans('status.' . $state->value, [], 'huh_rb');
        }

        return $options;
    }

    private function getStatusLabel(string $status): string
    {
        $finalState = FinalState::tryFrom($status);

        if ($finalState) {
            return $this->translation->trans('status.' . $finalState->value, [], 'huh_rb');
        }

        $transKey = 'status.' . $status;
        $label = $this->translation->trans($transKey, [], 'huh_rb');

        if ($label === $transKey) {
            $label = $this->translation->trans('status.pending', [], 'huh_rb') . " [$status]";
        }

        return $label;
    }

    #[AsCallback(Table::BOOKING->value, 'list.label.label')]
    public function getListLabels(array $row, string $label, DataContainer $dc): string
    {
        if (!$bookingArchiveId = (int) $dc->id) {
            return $label;
        }

        if (!$booking = BookingArchiveModel::findByPk($bookingArchiveId)) {
            return $label;
        }

        if (!$type = $this->archiveTypeRegistry->get($booking->type)) {
            return $label;
        }

        $status = $row['status'] ?? null;
        $statusLabel = $this->getStatusLabel($status);
        $finalState = FinalState::tryFrom($status);

        $cteType = match ($finalState) {
            FinalState::APPROVED => 'published',
            FinalState::REJECTED => 'unpublished',
            default => '',
        };

        $newLabel = $label;

        if ($data = StringUtil::deserialize($row['data'] ?? null, true))
        {
            $newLabel = '';

            if ($org = \trim($data['organization'] ?? $data['org'] ?? $data['company'] ?? null)) {
                $newLabel .= \htmlspecialchars($org) . '<br>';
            }

            $name = ($data['firstname'] ?? null) . ' ' . ($data['lastname'] ?? null) . ' ' . ($data['name'] ?? null);;

            if ($name = \trim($name)) {
                $newLabel .= \htmlspecialchars($name) . '<br>';
            }

            $newLabel .= $label;
        }

        $dateLabel = $this->translation->trans('messages.datetime_incorrect', [], 'huh_rb');

        $start = (int) ($row['start'] ?? null);
        $end = (int) ($row['end'] ?? null);

        if ($start && $end)
        {
            $dateFormat = 'd.m.Y' . ($type->hasTime() ? ' H:i' : '');

            $start = \date($dateFormat, $start);
            $end = \date($dateFormat, $end);

            $dateLabel = "{$start} &ndash; {$end}";
        }

        return <<<LABEL
            <div class="cte_type {$cteType}" style="margin-bottom: .5rem">[$statusLabel]</div>
            <p style="font-variant-numeric: tabular-nums"><strong>{$dateLabel}</strong></p>
            <p style="line-height: 1.4">{$newLabel}</p>
            LABEL;
    }
}