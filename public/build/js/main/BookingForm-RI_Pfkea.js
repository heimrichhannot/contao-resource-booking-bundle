var m = Object.defineProperty;
var c = (s) => {
  throw TypeError(s);
};
var g = (s, e, t) => e in s ? m(s, e, { enumerable: !0, configurable: !0, writable: !0, value: t }) : s[e] = t;
var d = (s, e, t) => g(s, typeof e != "symbol" ? e + "" : e, t), f = (s, e, t) => e.has(s) || c("Cannot " + t);
var a = (s, e, t) => (f(s, e, "read from private field"), t ? t.call(s) : e.get(s)), n = (s, e, t) => e.has(s) ? c("Cannot add the same private member more than once") : e instanceof WeakSet ? e.add(s) : e.set(s, t), u = (s, e, t, r) => (f(s, e, "write to private field"), r ? r.call(s, t) : e.set(s, t), t);
import { validateConfig as p } from "./config-CkxhDHMd.js";
import w from "./BookingData-C0SyrrwQ.js";
var l, i, o, h;
class y {
  /**
   * @param {HTMLElement} $root
   * @param {BookingFormConfig} config
   */
  constructor(e, t) {
    n(this, l, !1);
    n(this, i, {});
    n(this, o, {});
    n(this, h, null);
    d(this, "loadingClassName", "rb-loading");
    if (p(t), u(this, h, new w()), this.$root = e, this.config = t, this.$form = e.querySelector(t.selectors.form), this.$mount = e.querySelector(t.selectors.mount), this.$dataInput = e.querySelector(t.selectors.dataInput), !this.$form) throw new Error(`Form not found via selector "${t.selectors.form}"`);
    if (!this.$mount) throw new Error(`Mount not found via selector "${t.selectors.mount}"`);
    if (!this.$dataInput) throw new Error(`Data input not found via selector "${t.selectors.dataInput}"`);
    for (const r of this.$root.querySelectorAll("[data-rb-template]"))
      r.dataset.rbTemplate && (a(this, i)[r.dataset.rbTemplate] = r.cloneNode(!0));
    for (const r of this.$root.querySelectorAll("[data-rb-message]"))
      r.dataset.rbMessage && (a(this, o)[r.dataset.rbMessage] = r.innerHTML.trim());
    e.bookingForm = this;
  }
  /** @return {BookingData} */
  get data() {
    return a(this, h);
  }
  get isLoading() {
    return a(this, l);
  }
  set isLoading(e) {
    e = !!e, u(this, l, e), this.$form.disabled = e, this.$root.classList.toggle(this.loadingClassName, e), this._loadingTimeout && clearTimeout(this._loadingTimeout);
  }
  getTemplate(e) {
    return this.hasTemplate(e) && a(this, i)[e] || null;
  }
  setTemplate(e, t) {
    a(this, i)[e] = t;
  }
  hasTemplate(e) {
    return a(this, i).hasOwnProperty(e);
  }
  getMessage(e) {
    return this.hasMessage(e) && a(this, o)[e] || null;
  }
  setMessage(e, t) {
    a(this, o)[e] = t;
  }
  hasMessage(e) {
    return a(this, o).hasOwnProperty(e);
  }
  bind({ loadingTimeout: e = 5e3 } = {}) {
    this.isLoading = this.$root.classList.contains(this.loadingClassName), this.isLoading && (this._loadingTimeout = setTimeout(() => {
      var t;
      this.$root.innerHTML = ((t = this.getTemplate("error-loading")) == null ? void 0 : t.innerHTML) || "Error: Could not load booking form.";
    }, e)), this.$form.addEventListener("submit", this.onSubmit.bind(this));
  }
  onSubmit(e) {
    return this.data.isEmpty ? (e.preventDefault(), alert(this.getMessage("error-submission-empty") || "Please select at least one resource."), !1) : this.data.hasRange ? (this.$dataInput.value = JSON.stringify(this.data), !0) : (e.preventDefault(), alert(this.getMessage("error-submission-no-range") || "Please select a date range."), !1);
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
    for (const r of this.config.ref.resource_archives)
      e.searchParams.append("archive[]", r.toString());
    const t = await this.fetch(e);
    if (!t.ok) throw new Error(`Failed to fetch resources: ${t.statusText}`);
    return t.json();
  }
}
l = new WeakMap(), i = new WeakMap(), o = new WeakMap(), h = new WeakMap();
export {
  y as default
};
