<?php

namespace HeimrichHannot\ResourceBookingBundle\Util;

use Contao\Validator;

final class EmailAddress
{
    /**
     * Whether the string is exactly one plain email address.
     *
     * Mailers and the Notification Center split recipients at commas, so a quoted local part like
     * "a,victim@evil.example,b"@example.org, a list or a friendly name would send mail to addresses nobody validated.
     */
    public static function isSingle(string $email): bool
    {
        return '' !== $email
            && !\preg_match('/[\s",;<>]/', $email)
            // Unlike filter_var, accepts umlauts like the form field's rgxp=email does
            && Validator::isEmail($email);
    }
}
