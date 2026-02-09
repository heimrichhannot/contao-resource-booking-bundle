<?php

namespace HeimrichHannot\ResourceBookingBundle\Contao;

enum Table: string
{
    case BOOKING = 'tl_rb_booking';
    case BOOKING_ARCHIVE = 'tl_rb_booking_archive';
    case BOOKING_RESOURCE = 'tl_rb_booking_resource';
    case RESOURCE = 'tl_rb_resource';
    case RESOURCE_ARCHIVE = 'tl_rb_resource_archive';
}