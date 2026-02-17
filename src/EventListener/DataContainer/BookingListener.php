<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\ReviewStep;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingListener
{
    public function __construct(
        private Connection                 $connection,
        private BookingArchiveTypeRegistry $archiveTypeRegistry,
        private BookingFactory             $bookingFactory,
        private RequestStack               $requestStack,
        private ReviewStep                 $reviewStep,
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

    #[AsCallback(Table::BOOKING->value, 'config.onload')]
    public function onLoadConfig(?DataContainer $dc = null): void
    {
        if (!$dc || !$dc->id || 'edit' !== $this->requestStack->getCurrentRequest()?->query->get('act')) {
            return;
        }

        if (!$booking = BookingModel::findByPk($dc->id)) {
            return;
        }

        if (!$archive = BookingArchiveModel::findByPk($booking->pid)) {
            return;
        }

        if (!$archive->requireReview) {
            return;
        }

        PaletteManipulator::create()
            ->addField('notifyOnStatusChange', 'status', PaletteManipulator::POSITION_BEFORE)
            ->applyToPalette('default', Table::BOOKING->value);
    }

    #[AsCallback(Table::BOOKING->value, 'config.onbeforesubmit')]
    public function onBeforeSubmit(array $record, DataContainer $dc): array
    {
        if (!$dc || !$dc->id) {
            return $record;
        }

        if (!$booking = BookingModel::findByPk($dc->id)) {
            return $record;
        }

        if (!($record['notifyOnStatusChange'] ?? false)) {
            return $record;
        }

        if (!$archive = BookingArchiveModel::findByPk($booking->pid)) {
            return $record;
        }

        if (!$archive->requireReview) {
            return $record;
        }

        $oldStatus = (string) $booking->status;
        $newStatus = (string) ($record['status'] ?? '');

        if ($newStatus && $oldStatus !== $newStatus)
        {
            $finalState = FinalState::tryFrom($newStatus);

            if ($finalState && $finalState->isResolved()) {
                $this->reviewStep->review($booking, $finalState === FinalState::APPROVED);
            }
        }

        return $record;
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