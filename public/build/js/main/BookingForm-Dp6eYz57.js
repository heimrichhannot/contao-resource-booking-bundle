import { validateConfig as s } from "./config-CkxhDHMd.js";
class n {
  /**
   * @param {HTMLElement} $root
   * @param {BookingFormConfig} config
   */
  constructor(r, e) {
    if (s(e), this.$root = r, this.config = e, this.$form = r.querySelector(e.selectors.form), this.$mount = r.querySelector(e.selectors.mount), this.$dataInput = r.querySelector(e.selectors.dataInput), !this.$form) throw new Error(`Form not found via selector "${e.selectors.form}"`);
    if (!this.$mount) throw new Error(`Mount not found via selector "${e.selectors.mount}"`);
    if (!this.$dataInput) throw new Error(`Data input not found via selector "${e.selectors.dataInput}"`);
    r.bookingForm = this;
  }
  async fetch(r, e = {}) {
    return e.headers = new Headers(e.headers || {}), e.headers.set("Accept", "application/json"), e.headers.set("X-Requested-With", "XMLHttpRequest"), e.credentials = "same-origin", e.referrerPolicy = "no-referrer", fetch(r, e);
  }
  async fetchBookings() {
    const r = new URL(this.config.api.bookings, window.location.origin), e = await this.fetch(r);
    if (!e.ok) throw new Error(`Failed to fetch bookings: ${e.statusText}`);
    return e.json();
  }
  /**
   * @return {Promise<{
   *     archives: {id: number, title: string}[]
   *     resources: {id: number, title: string, capacity: number}[]
   * }>}
   */
  async fetchResources() {
    const r = new URL(this.config.api.resources, window.location.origin);
    for (const t of this.config.ref.resource_archives)
      r.searchParams.append("archive[]", t.toString());
    const e = await this.fetch(r);
    if (!e.ok) throw new Error(`Failed to fetch resources: ${e.statusText}`);
    return e.json();
  }
}
export {
  n as default
};
