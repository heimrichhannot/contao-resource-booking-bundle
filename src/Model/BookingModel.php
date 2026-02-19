<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use Contao\StringUtil;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

/**
 * @property int $id
 * @property int $pid
 * @property int $tstamp
 * @property string $status
 * @property string $title
 * @property string $email
 * @property string $uuid
 * @property array|string|null $data
 * @property array|string|null $internalState
 * @property int $expiresAt
 * @property int $start
 * @property int $end
 */
class BookingModel extends Model
{
    protected static $strTable = Table::BOOKING->value;

    public function signature(): string
    {
        return \sha1(\json_encode([
            'id'     => $this->id,
            'status' => $this->status,
            'tstamp' => $this->tstamp,
            'row'    => $this->row(),
            'data'   => $this->getData(),
            'state'  => $this->getInternalState(),
        ], \JSON_THROW_ON_ERROR));
    }

    public function getArchive(): ?BookingArchiveModel
    {
        $archive = BookingArchiveModel::findByPk((int) $this->pid);

        if ($archive instanceof BookingArchiveModel) {
            return $archive;
        }

        return null;
    }

    public function getData(): array
    {
        if (!\is_array($this->data)) {
            $this->data = StringUtil::deserialize($this->data, true);
        }

        return $this->data;
    }

    public function getInternalState(): array
    {
        return StringUtil::deserialize($this->internalState, true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getInternalState()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $state = $this->getInternalState();
        $state[$key] = $value;
        $this->internalState = \serialize($state);
        return $this;
    }

    public function unset(string $key): self
    {
        $state = $this->getInternalState();
        unset($state[$key]);
        $this->internalState = \serialize($state);
        return $this;
    }

    public function collectTokens(): array
    {
        $tokens = ['email' => $this->email];

        foreach ($this->row() as $key => $value) {
            if (\in_array($key, ['data', 'internalState'], true)) {
                continue;
            }
            $tokens['booking_' . $key] = $value;
        }

        foreach ($this->getInternalState() as $key => $value) {
            $tokens['internal_' . $key] = $value;
        }

        foreach ($this->getData() as $key => $value) {
            $tokens['data_' . $key] = $value;
        }

        return $tokens;
    }
}