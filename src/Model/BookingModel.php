<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

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
}