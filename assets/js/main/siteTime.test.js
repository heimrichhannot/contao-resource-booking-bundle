import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';
import { siteDay, siteDaysFromToday, toOffsetISOString } from './siteTime.js';

const originalTz = process.env.TZ;

afterEach(() => {
    if (originalTz === undefined) delete process.env.TZ;
    else process.env.TZ = originalTz;
});

/** @param {Date} date */
const parts = date => [date.getFullYear(), date.getMonth() + 1, date.getDate(), date.getHours(), date.getMinutes()];

test('siteDay returns the day in the site time zone for a browser west of it', () => {
    process.env.TZ = 'America/New_York';
    // Start of a booking on 6 April, stored as midnight in Berlin
    assert.deepEqual(parts(siteDay(new Date('2027-04-05T22:00:00Z'), 'Europe/Berlin')), [2027, 4, 6, 0, 0]);
});

test('siteDay returns the day in the site time zone for a browser east of it', () => {
    process.env.TZ = 'Asia/Tokyo';
    assert.deepEqual(parts(siteDay(new Date('2027-04-05T22:00:00Z'), 'Europe/Berlin')), [2027, 4, 6, 0, 0]);
});

test('siteDaysFromToday counts from the site day, not the browser day', () => {
    process.env.TZ = 'America/New_York';
    // 6 April 01:30 in Berlin, still 5 April in New York
    const now = new Date('2027-04-05T23:30:00Z');
    assert.deepEqual(parts(siteDaysFromToday(1, 'Europe/Berlin', now)), [2027, 4, 7, 0, 0]);
});

test('siteDaysFromToday stays on midnight across a DST switch', () => {
    process.env.TZ = 'Europe/Berlin';
    // Berlin switches to summer time on 28 March 2027
    const now = new Date('2027-03-27T12:00:00Z');
    assert.deepEqual(parts(siteDaysFromToday(2, 'Europe/Berlin', now)), [2027, 3, 29, 0, 0]);
});

for (const [timeZone, expected] of [
    ['Asia/Tokyo', '2027-04-06T00:00:00.000+09:00'],
    ['America/New_York', '2027-04-06T00:00:00.000-04:00'],
    ['Europe/London', '2027-04-06T00:00:00.000+01:00'],
    ['UTC', '2027-04-06T00:00:00.000+00:00'],
    ['Asia/Kolkata', '2027-04-06T00:00:00.000+05:30'],
    ['America/St_Johns', '2027-04-06T00:00:00.000-02:30'],
    ['Pacific/Kiritimati', '2027-04-06T00:00:00.000+14:00'],
]) {
    test(`toOffsetISOString keeps the picked day in ${timeZone}`, () => {
        process.env.TZ = timeZone;
        assert.equal(toOffsetISOString(new Date(2027, 3, 6)), expected);
    });
}
