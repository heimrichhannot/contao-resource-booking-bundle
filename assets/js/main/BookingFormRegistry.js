export default class BookingFormRegistry {
    constructor() {
        this.bookingForms = [];
    }

    register(bookingForm) {
        this.bookingForms.push(bookingForm);
    }

    find(elementOrId) {
        const element = typeof elementOrId === "string"
            ? (document.getElementById(elementOrId) || document.querySelector(elementOrId))
            : elementOrId;
        return this.bookingForms.find(bookingForm => bookingForm.$mount === element || bookingForm.$root === element) || null;
    }
}