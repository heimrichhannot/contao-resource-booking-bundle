<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Input;
use Contao\InputEncodingMode;
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
 * @property int|string|null $expiresAt Unix timestamp until which an unconfirmed booking reserves its period
 * @property bool|string $processingFailed Whether processing failed after the opt-in, so editors have to process it again
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

    /**
     * Tokens for notifications.
     *
     * Values are encoded like Contao encodes submitted form values ("<" and insert tags), because the stored booking
     * data is decoded and contains user input. Without this, HTML notifications would contain the visitor's raw HTML.
     */
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

        return \array_map(self::encodeTokenValue(...), $tokens);
    }

    private static function encodeTokenValue(mixed $value): mixed
    {
        if (\is_array($value)) {
            return \array_map(self::encodeTokenValue(...), $value);
        }

        return \is_string($value) ? Input::encodeInput($value, InputEncodingMode::encodeLessThanSign) : $value;
    }
}