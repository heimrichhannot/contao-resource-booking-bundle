import { parseConfig } from "./config.js";
import BookingForm from "./BookingForm.js";
import BookingFormRegistry from "./BookingFormRegistry.js";

export default function init(options = {}) {
    const {
        rootSelector = '[data-huhrb-root]'
    } = options;

    const $$roots = document.querySelectorAll(rootSelector);
    const bookingFormLocator = new BookingFormRegistry();

    for (const $root of $$roots) {
        const bookingForm = initBookingForm($root);
        bookingFormLocator.register(bookingForm);
    }

    return bookingFormLocator;
}

export function initBookingForm($root) {
    if (!$root) return;

    const raw = $root.dataset.huhrbRoot;
    if (!raw) throw new Error(`Missing required data attribute: data-huhrb-root on element`);

    const config = parseConfig(raw, "$root.dataset.huhrbRoot");

    return new BookingForm($root, config);
}