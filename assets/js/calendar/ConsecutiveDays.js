import 'air-datepicker/air-datepicker.css';
import AirDatepicker from 'air-datepicker';

export default class ConsecutiveDays {
    bookings = [];
    resourceArchives = {};
    resources = [];

    /**
     * @param {BookingForm} bookingForm
     */
    constructor(bookingForm) {
        this.bookingForm = bookingForm;
        this.$mount = bookingForm.$mount;
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
        elm.innerHTML = `
            <div class="calendar-container">
                <div class="calendar"></div>
                <div class="resources">
                    ${this.resources.map(r => {
                        return `<div class="resource">${r.title}</div>`;
                    }).join('')}
                </div>
            </div>
        `;
        this.$mount.appendChild(elm);
    }

    async loadResources() {
        const result = await this.bookingForm.fetchResources();
        this.resourceArchives = {};
        for (const archive of result.archives || []) {
            this.resourceArchives[archive.id] = archive;
        }
        this.resources = result.resources;
    }

    async loadBookings() {
        const bookings = await this.bookingForm.fetchBookings();
        this.bookings = bookings;
    }

    /**
     * Factory method to create and initialize a ConsecutiveDays instance.
     * @param {BookingForm} bookingForm
     * @return {ConsecutiveDays}
     */
    static async mount(bookingForm) {
        const cal = new ConsecutiveDays(bookingForm);
        await cal.init();
        return cal;
    }
}