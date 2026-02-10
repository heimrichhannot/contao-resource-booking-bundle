<?php

namespace HeimrichHannot\ResourceBookingBundle\Model;

use Contao\Model;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

/**
 * @property int $id
 * @property int $tstamp
 * @property string $title
 * @property string $alias
 * @property bool $published
 * @property bool $requireOptIn
 * @property bool $requireReview
 * @property int $nc_optInRequest
 * @property int $nc_reviewRequest
 * @property int $nc_reviewApproval
 * @property int $nc_reviewRejection
 * @property int $jumpToOptInCompleted
 */
class BookingArchiveModel extends Model
{
    protected static $strTable = Table::BOOKING_ARCHIVE->value;
}