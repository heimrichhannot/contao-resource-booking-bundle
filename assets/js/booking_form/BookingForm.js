import 'air-datepicker/air-datepicker.css';
import AirDatepicker from 'air-datepicker';
import {validateConfig} from "./config.js";

export default class BookingForm {
    /**
     * @param {HTMLElement} $root
     * @param {BookingFormConfig} config
     */
    constructor($root, config) {
        validateConfig(config);

        this.$root = $root;
        this.config = config;

        this.$form = $root.querySelector(config.selectors.form);
        this.$mount = $root.querySelector(config.selectors.mount);

        this.api = config.api;

        if (!this.$form) throw new Error(`Form not found via selector "${config.selectors.form}"`);
        if (!this.$mount) throw new Error(`Mount not found via selector "${config.selectors.mount}"`);
    }

    init() {

    }
}