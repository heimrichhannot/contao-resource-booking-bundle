var d = Object.defineProperty;
var h = (r) => {
  throw TypeError(r);
};
var f = (r, e, t) => e in r ? d(r, e, { enumerable: !0, configurable: !0, writable: !0, value: t }) : r[e] = t;
var c = (r, e, t) => f(r, typeof e != "symbol" ? e + "" : e, t), l = (r, e, t) => e.has(r) || h("Cannot " + t);
var a = (r, e, t) => (l(r, e, "read from private field"), t ? t.call(r) : e.get(r)), n = (r, e, t) => e.has(r) ? h("Cannot add the same private member more than once") : e instanceof WeakSet ? e.add(r) : e.set(r, t), u = (r, e, t, s) => (l(r, e, "write to private field"), s ? s.call(r, t) : e.set(r, t), t);
import { validateConfig as m } from "./config-CkxhDHMd.js";
var i, o;
class g {
  /**
   * @param {HTMLElement} $root
   * @param {BookingFormConfig} config
   */
  constructor(e, t) {
    n(this, i, !1);
    n(this, o, {});
    c(this, "loadingClassName", "rb-loading");
    if (m(t), this.$root = e, this.config = t, this.$form = e.querySelector(t.selectors.form), this.$mount = e.querySelector(t.selectors.mount), this.$dataInput = e.querySelector(t.selectors.dataInput), !this.$form) throw new Error(`Form not found via selector "${t.selectors.form}"`);
    if (!this.$mount) throw new Error(`Mount not found via selector "${t.selectors.mount}"`);
    if (!this.$dataInput) throw new Error(`Data input not found via selector "${t.selectors.dataInput}"`);
    for (const s of this.$root.querySelectorAll("[data-rb-template]"))
      a(this, o)[s.dataset.rbTemplate] = s.cloneNode(!0);
    e.bookingForm = this;
  }
  get isLoading() {
    return a(this, i);
  }
  set isLoading(e) {
    u(this, i, e), this.$form.disabled = e, this.$root.classList.toggle(this.loadingClassName, e);
  }
  getTemplate(e) {
    return a(this, o)[e] || null;
  }
  setTemplate(e, t) {
    a(this, o)[e] = t;
  }
  hasTemplate(e) {
    return a(this, o).hasOwnProperty(e);
  }
  async fetch(e, t = {}) {
    return t.headers = new Headers(t.headers || {}), t.headers.set("Accept", "application/json"), t.headers.set("X-Requested-With", "XMLHttpRequest"), t.credentials = "same-origin", t.referrerPolicy = "no-referrer", fetch(e, t);
  }
  async fetchBookings() {
    const e = new URL(this.config.api.bookings, window.location.origin), t = await this.fetch(e);
    if (!t.ok) throw new Error(`Failed to fetch bookings: ${t.statusText}`);
    return t.json();
  }
  /**
   * @return {Promise<{
   *     archives: {id: number, title: string}[]
   *     resources: {id: number, title: string, capacity: number}[]
   * }>}
   */
  async fetchResources() {
    const e = new URL(this.config.api.resources, window.location.origin);
    for (const s of this.config.ref.resource_archives)
      e.searchParams.append("archive[]", s.toString());
    const t = await this.fetch(e);
    if (!t.ok) throw new Error(`Failed to fetch resources: ${t.statusText}`);
    return t.json();
  }
}
i = new WeakMap(), o = new WeakMap();
export {
  g as default
};
