/**
 * Days are calendar days in the site's time zone (the server's), while the calendar works with Date objects at local
 * midnight of the visitor's browser. These helpers convert between both.
 */

/**
 * @param {Date} instant
 * @param {string} timeZone IANA time zone of the site, e.g. "Europe/Berlin"
 * @return {Date} Local midnight of the calendar day the instant falls on in the site's time zone.
 */
export function siteDay(instant, timeZone) {
    const parts = {};
    const format = new Intl.DateTimeFormat('en-US', { timeZone, year: 'numeric', month: 'numeric', day: 'numeric' });

    for (const { type, value } of format.formatToParts(instant)) {
        parts[type] = value;
    }

    return new Date(Number(parts.year), Number(parts.month) - 1, Number(parts.day));
}

/**
 * @param {number} days
 * @param {string} timeZone IANA time zone of the site
 * @param {Date} [now]
 * @return {Date} Local midnight of the day that is the given number of days from today in the site's time zone.
 */
export function siteDaysFromToday(days, timeZone, now = new Date()) {
    const date = siteDay(now, timeZone);
    date.setDate(date.getDate() + days);
    return date;
}

/**
 * Formats a date as ISO 8601 with milliseconds and the browser's UTC offset, e.g. 2027-04-06T00:00:00.000+09:00.
 * Unlike toISOString(), this keeps the calendar day the visitor picked.
 *
 * @param {Date} date
 * @return {string}
 */
export function toOffsetISOString(date) {
    const pad = (value, length = 2) => String(Math.abs(value)).padStart(length, '0');
    const offset = -date.getTimezoneOffset();
    const sign = offset < 0 ? '-' : '+';

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
        + `T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}.${pad(date.getMilliseconds(), 3)}`
        + `${sign}${pad(Math.trunc(offset / 60))}:${pad(offset % 60)}`;
}
