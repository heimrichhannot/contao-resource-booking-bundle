import { validateConfig } from "./config.js";

export default class BookingForm {
    #isLoading = false;
    #templates = {};

    loadingClassName = 'rb-loading';

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
        this.$dataInput = $root.querySelector(config.selectors.dataInput);

        if (!this.$form) throw new Error(`Form not found via selector "${config.selectors.form}"`);
        if (!this.$mount) throw new Error(`Mount not found via selector "${config.selectors.mount}"`);
        if (!this.$dataInput) throw new Error(`Data input not found via selector "${config.selectors.dataInput}"`);

        for (const template of this.$root.querySelectorAll('[data-rb-template]')) {
            this.#templates[template.dataset.rbTemplate] = template.cloneNode(true);
        }

        $root.bookingForm = this;
    }

    get isLoading() {
        return this.#isLoading;
    }

    set isLoading(isLoading) {
        this.#isLoading = isLoading;
        this.$form.disabled = isLoading;
        this.$root.classList.toggle(this.loadingClassName, isLoading);
    }

    getTemplate(name) {
        return this.#templates[name] || null;
    }

    setTemplate(name, html) {
        this.#templates[name] = html;
    }

    hasTemplate(name) {
        return this.#templates.hasOwnProperty(name);
    }

    async fetch(url, options = {}) {
        options.headers = new Headers(options.headers || {});
        options.headers.set('Accept', 'application/json');
        options.headers.set('X-Requested-With', 'XMLHttpRequest');
        options.credentials = 'same-origin';
        options.referrerPolicy = 'no-referrer';

        return fetch(url, options);
    }

    async fetchBookings() {
        const url = new URL(this.config.api.bookings, window.location.origin);
        const res = await this.fetch(url);
        if (!res.ok) throw new Error(`Failed to fetch bookings: ${res.statusText}`);
        return res.json();
    }

    /**
     * @return {Promise<{
     *     archives: {id: number, title: string}[]
     *     resources: {id: number, title: string, capacity: number}[]
     * }>}
     */
    async fetchResources() {
        const url = new URL(this.config.api.resources, window.location.origin);
        for (const archive of this.config.ref.resource_archives) {
            url.searchParams.append('archive[]', archive.toString());
        }
        const res = await this.fetch(url);
        if (!res.ok) throw new Error(`Failed to fetch resources: ${res.statusText}`);
        return res.json();
    }
}