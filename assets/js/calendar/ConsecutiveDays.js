import 'air-datepicker/air-datepicker.css';
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import localeDe from 'air-datepicker/locale/de';
import { siteDay, siteDaysFromToday } from '../main/siteTime.js';

/**
 * Decodes HTML entities of a stored text without interpreting it as markup.
 * @param {string} text
 * @return {string}
 */
function decodeEntities(text) {
    return new DOMParser().parseFromString(String(text ?? ''), 'text/html').documentElement.textContent;
}

export default class ConsecutiveDays {
    #disabledRanges = null;

    bookings = [];
    resourceArchives = {};
    resources = [];

    static defaults = {
        labels: {
            resources: 'Resources',
        },
        airDatepicker: {
            locale: localeEn,
        }
    };

    static get airLocaleEn() { return localeEn; }
    static get airLocaleDe() { return localeDe; }

    /**
     * @param {BookingForm} bookingForm
     * @param {object} options
     */
    constructor(bookingForm, options = {}) {
        this.bookingForm = bookingForm;
        this.$mount = bookingForm.$mount;
        this.options = { ...ConsecutiveDays.defaults, ...options };
        this.labels = this.options.labels || ConsecutiveDays.defaults.labels;
    }

    async init() {
        this.bookingForm.isLoading = true;

        await Promise.all([
            this.loadResources(),
            this.loadBookings(),
        ]);

        await this.render();

        this.bookingForm.isLoading = false;
    }

    async render() {
        const elm = document.createElement("div");
        elm.classList.add('rb-calendar-container')
        elm.innerHTML = `
            <div class="rb-selection">
                <fieldset class="rb-resources">
                    <legend></legend>
                </fieldset>
                <div class="rb-picked-dates"></div>
            </div>
            <div class="rb-airdatepicker" data-rb-slot="calendar"></div>
        `;

        // Labels and resource titles are data, never markup
        const $fieldset = elm.querySelector('.rb-resources');
        $fieldset.querySelector('legend').textContent = decodeEntities(this.labels.resources);

        for (const r of this.resources) {
            const id = `${this.$mount.id}-r${Number.parseInt(r.id)}`;

            const $label = document.createElement('label');
            $label.htmlFor = id;
            $label.className = 'rb-resource-label';

            const $cbx = document.createElement('input');
            $cbx.type = 'checkbox';
            $cbx.className = 'rb-resource-cbx';
            $cbx.id = id;
            $cbx.value = String(Number.parseInt(r.id));

            const $title = document.createElement('span');
            $title.textContent = decodeEntities(r.title);

            $label.append($cbx, $title);
            $fieldset.append($label);
        }

        this.$mount.appendChild(elm);
        this.$calWrapper = elm.querySelector('[data-rb-slot="calendar"]');
        this.air = this.initCalendar(this.$calWrapper);

        this.checkboxes = elm.querySelectorAll('.rb-resource-cbx');
        this.initCheckboxes(this.checkboxes);

        this.renderPickedDates();
    }

    renderPickedDates() {
        const $pickedDates = this.$mount.querySelector('.rb-picked-dates');

        if (!this.air.selectedDates || this.air.selectedDates.length !== 2) {
            $pickedDates.innerHTML = this.bookingForm.getTemplate('select-dates')?.innerHTML
                || '<div class="rb-no-dates-picked">Please select the date range you want to book in the calendar.</div>'
            return;
        }

        const [start, end] = this.air.selectedDates;
        if (start > end) {
            [start, end] = [end, start];
        }

        if (!this.isRangeValid(start, end)) {
            $pickedDates.innerHTML = this.bookingForm.getTemplate('invalid-dates')?.innerHTML
                || '<div class="rb-invalid-dates">The selected date range is not valid.</div>'
            return;
        }

        $pickedDates.innerHTML = this.air.selectedDates?.map(date => {
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `<div class="rb-picked-date">${day}.${month}.${year}</div>`;
        }).join('');
    }

    initCheckboxes(checkboxes) {
        for (const cbx of checkboxes) {
            cbx.addEventListener('change', () => this._onCheckboxChange(cbx));
        }
    }

    _onCheckboxChange(cbx) {
        const resourceId = Number.parseInt(cbx.value);

        // get previously blocked ranges for this resource (if it was previously selected)
        // so we can unblock them before blocking new ones (if any)
        let blockedRanges = this.bookingForm.data.isResourceUsed(resourceId)
            ? this.getDisabledRanges(resourceId)
            : null;

        // update booking form data
        this.bookingForm.data.useResource(resourceId, +!!cbx.checked);

        // reset cached disabled ranges
        this.#disabledRanges = null

        // unblock previously blocked ranges from this resource
        if (blockedRanges) this._cal_unblockDates(blockedRanges);

        // update blocked ranges
        this._cal_updateBlockedDates();

        this.renderPickedDates();
    }

    initCalendar($elm) {
        const { min_advance_days, max_advance_days } = this.bookingForm.config.limits;
        const { timezone } = this.bookingForm.config;
        const minDate = siteDaysFromToday(min_advance_days, timezone);
        const maxDate = siteDaysFromToday(max_advance_days, timezone);

        const air = new AirDatepicker($elm, {
            locale: this.options.airDatepicker?.locale ?? localeEn,
            inline: true,
            range: true,
            timepicker: false,
            minDate,
            maxDate,
            multipleDatesSeparator: '--',
            onSelect: ({ datepicker }) => {
                this.renderPickedDates();
                this.bookingForm.data.start = datepicker.selectedDates[0] || null;
                this.bookingForm.data.end = datepicker.selectedDates[1] || null;
            },
            onBeforeSelect: this._cal_onBeforeSelect.bind(this),
            onFocus: this._cal_onFocus.bind(this),
            onRenderCell: this._cal_onRenderCell.bind(this),
        });

        this._cal_updateBlockedDates(air);

        return air;
    }

    _cal_unblockDates(blockedRanges) {
        for (const blockedRange of blockedRanges) {
            for (let date = blockedRange[0]; date <= blockedRange[1]; date.setDate(date.getDate() + 1)) {
                this.air.enableDate(date);
            }
        }
    }

    _cal_updateBlockedDates(air = this.air) {
        for (const [start, end] of this.disabledRanges) {
            let currentDate = new Date(start);

            while (currentDate <= end) {
                air.disableDate(new Date(currentDate));
                currentDate.setDate(currentDate.getDate() + 1);
            }
        }

        if (air.selectedDates?.length === 2 && !this.isRangeValid(air.selectedDates[0], air.selectedDates[1])) {
            air.clear();
            air.$datepicker?.querySelectorAll('.-day-.-selected-')
                .forEach(el => el.classList.remove('-selected-'));
        }
    }

    _cal_validateSelection(date, datepicker) {
        // Check if we are in "range selection" mode
        const otherDate = datepicker.selectedDates[0] ?? null;
        if (otherDate === null || datepicker.selectedDates.length !== 1) return true;

        return this.isRangeValid(date, otherDate);
    }

    _cal_onBeforeSelect({ date, datepicker }) {
        // Pure logic: just return the validation result
        return this._cal_validateSelection(date, datepicker);
    }

    _cal_onFocus({ date, datepicker }) {
        const isValid = this._cal_validateSelection(date, datepicker);
        datepicker.$datepicker.classList.toggle('-disabled-range-', !isValid);
    }

    _cal_onRenderCell({ date }) {
        if (this.isDateBlocked(date)) {
            return {
                disabled: true,
            };
        }
    }

    get applicableBookings() {
        return this.bookings.filter(booking => this.bookingForm.data.isResourceUsed(booking.resource_id));
    }

    get disabledRanges() {
        return this.getDisabledRanges();
    }

    getDisabledRanges(resourceId = null) {
        if (resourceId === null && this.#disabledRanges) {
            return this.#disabledRanges;
        }

        const rangesRaw = [];
        for (const booking of this.applicableBookings) {
            // if resourceId is specified, only consider bookings for that resource
            if (resourceId !== null && booking.resource_id !== resourceId) continue;

            // Convert blocked ranges from UNIX timestamps to the whole days they cover in the site's time zone
            for (const blockedRange of booking.blocked) {
                const startDay = siteDay(new Date(Number.parseInt(blockedRange.start) * 1000), this.bookingForm.config.timezone);
                const endDay = siteDay(new Date(Number.parseInt(blockedRange.end) * 1000), this.bookingForm.config.timezone);
                endDay.setHours(23, 59, 59, 999);
                rangesRaw.push([startDay, endDay]);
            }
        }

        // Sort ranges by start date (essential for the merge logic to work)
        rangesRaw.sort((a, b) => a[0] - b[0]);

        const ranges = [];
        for (const currentRange of rangesRaw) {
            const lastMergedRange = ranges[ranges.length - 1];

            // If 'ranges' is empty, or if the current range starts AFTER the last merged range ends...
            if (!lastMergedRange || currentRange[0] > lastMergedRange[1]) {
                // No overlap: push the current range as is
                ranges.push(currentRange);
            } else {
                // Overlap detected: extend the end date of the last merged range
                if (currentRange[1] > lastMergedRange[1]) {
                    lastMergedRange[1] = currentRange[1];
                }
            }
        }

        return this.#disabledRanges = ranges;
    }

    isDateBlocked(date) {
        return this.disabledRanges.some(range => date >= range[0] && date <= range[1]);
    }

    isRangeValid(date, otherDate) {
        const t1 = date.getTime();
        const t2 = otherDate.getTime();
        const selStart = Math.min(t1, t2);
        const selEnd = Math.max(t1, t2);

        // Booked days including the first and the last day (rounded to ignore DST shifts)
        const days = Math.round((selEnd - selStart) / 86400000) + 1;
        if (days > this.bookingForm.config.limits.max_duration_days) {
            return false;
        }

        // Check for Overlap against disabledRanges
        for (const range of this.disabledRanges) {
            const blockedStart = range[0] instanceof Date ? range[0].getTime() : range[0];
            const blockedEnd = range[1] instanceof Date ? range[1].getTime() : range[1];

            if (selStart <= blockedEnd && selEnd >= blockedStart) {
                return false; // Overlap found, invalid selection
            }
        }

        return true;
    }

    async loadResources() {
        const result = await this.bookingForm.fetchResources();
        if (!result) this.err();
        this.resourceArchives = {};
        for (const archive of result.archives || []) {
            this.resourceArchives[archive.id] = archive;
        }
        this.resources = result.resources;
    }

    async loadBookings() {
        const bookings = await this.bookingForm.fetchBookings();
        if (!bookings) this.err();
        this.bookings = bookings.bookings || [];
        this.#disabledRanges = null;
    }

    err() {
        this.$mount.innerHTML = this.bookingForm.getTemplate('error')?.innerHTML || 'An error occurred.';
    }

    /**
     * Factory method to create and initialize a ConsecutiveDays instance.
     * @param {BookingForm} bookingForm
     * @param {object} options
     * @return {ConsecutiveDays}
     */
    static async mount(bookingForm, options = {}) {
        bookingForm.bind();
        const cal = new ConsecutiveDays(bookingForm, options);
        await cal.init();
        return cal;
    }
}