import 'air-datepicker/air-datepicker.css';
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import localeDe from 'air-datepicker/locale/de';

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
        this.bookingForm.data.on('resources:changed', () => this.#disabledRanges = null);

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
            <fieldset class="rb-resources">
                <legend>${this.labels.resources}</legend>
                ${this.resources.map(r => {
                    const id = `${this.$mount.id}-r${r.id}`;
                    return `
                        <label for="${id}" class="rb-resource-label">
                            <input type="checkbox" class="rb-resource-cbx" id="${id}" value="${r.id}">
                            <span>${r.title}</span>
                        </label>
                    `;
                }).join('')}
            </fieldset>
            <div class="rb-airdatepicker" data-rb-slot="calendar"></div>
        `;
        this.$mount.appendChild(elm);
        this.$calWrapper = elm.querySelector('[data-rb-slot="calendar"]');
        this.air = this.initCalendar(this.$calWrapper);

        this.checkboxes = elm.querySelectorAll('.rb-resource-cbx');
        this.initCheckboxes(this.checkboxes);
    }

    initCheckboxes(checkboxes) {
        for (const cbx of checkboxes) {
            cbx.addEventListener('change', () => this._onCheckboxChange(cbx));
        }
    }

    _onCheckboxChange(cbx) {
        const resourceId = parseInt(cbx.value);
        this.bookingForm.data.useResource(resourceId, +!!cbx.checked);
    }

    initCalendar($elm) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);

        return new AirDatepicker($elm, {
            locale: this.options.airDatepicker?.locale ?? localeEn,
            inline: true,
            range: true,
            timepicker: false,
            minDate: tomorrow,
            multipleDatesSeparator: '--',
            onSelect: (formattedDate, date, inst) => {
                console.log('Selected dates:', formattedDate, date);
            },
            onBeforeSelect: this._cal_onBeforeSelect.bind(this),
            onFocus: this._cal_onFocus.bind(this),
            onRenderCell: this._cal_onRenderCell.bind(this),
        });
    }

    _cal_onBeforeSelect({ date, datepicker }) {
        console.log('Before select:', date, datepicker);
        return true;
    }

    _cal_onFocus({ date, datepicker }) {
        console.log('Focus:', date, datepicker);
    }

    _cal_onRenderCell({ date }) {
        console.log('Render cell:', date);
    }

    get applicableBookings() {
        return this.bookings.filter(booking => this.bookingForm.data.isResourceUsed(booking.resource_id));
    }

    get disabledRanges() {
        if (this.#disabledRanges) return this.#disabledRanges;

        const rangesRaw = [];
        for (const booking of this.applicableBookings) {
            // const resourceId = booking.resource_id;
            const blockedRanges = booking.blocked;
            for (const blockedRange of blockedRanges) {
                const startDay = new Date(Number.parseInt(blockedRange.start) * 1000);
                const endDay = new Date(Number.parseInt(blockedRange.end) * 1000);
                startDay.setHours(0, 0, 0, 0);
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
        this.$mount.innerHTML = this.bookingForm.getTemplate('error').innerHTML || 'An error occurred.';
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