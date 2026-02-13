import 'air-datepicker/air-datepicker.css';
import AirDatepicker from 'air-datepicker';
import localeEn from 'air-datepicker/locale/en';
import localeDe from 'air-datepicker/locale/de';

export default class ConsecutiveDays {
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
            <fieldset class="rb-resources">
                <legend>${this.labels.resources}</legend>
                ${this.resources.map(r => {
                    const id = `${this.$mount.id}-r${r.id}`;
                    return `
                        <label for="${id}" class="rb-resource-label">
                            <input type="checkbox" class="rb-resource-cbx" id="${id}">
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
        });
    }

    async loadResources() {
        const result = await this.bookingForm.fetchResources();
        if (!result) this.unload();
        this.resourceArchives = {};
        for (const archive of result.archives || []) {
            this.resourceArchives[archive.id] = archive;
        }
        this.resources = result.resources;
    }

    async loadBookings() {
        const bookings = await this.bookingForm.fetchBookings();
        if (!bookings) this.unload();
        this.bookings = bookings;
    }

    unload() {
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