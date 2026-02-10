import { parseConfig } from "./config.js";
import BookingForm from "./BookingForm.js";

export default function init(options = {}) {
    const {
        rootSelector = '[data-huhrb-root]'
    } = options;

    const $$roots = document.querySelectorAll(rootSelector);

    for (const $root of $$roots) {
        initRoot($root);
    }

    console.debug('[HuH RB] Booking forms initialized.')
}

export function initRoot($root) {
    if (!$root) return;

    const raw = $root.dataset.huhrbRoot;
    if (!raw) throw new Error(`Missing required data attribute: data-huhrb-root on element`);

    const config = parseConfig(raw, "$root.dataset.huhrbRoot");

    new BookingForm($root, config);
}