var _ = Object.defineProperty;
var f = (i) => {
  throw TypeError(i);
};
var v = (i, e, t) => e in i ? _(i, e, { enumerable: !0, configurable: !0, writable: !0, value: t }) : i[e] = t;
var d = (i, e, t) => v(i, typeof e != "symbol" ? e + "" : e, t), D = (i, e, t) => e.has(i) || f("Cannot " + t);
var b = (i, e, t) => (D(i, e, "read from private field"), t ? t.call(i) : e.get(i)), m = (i, e, t) => e.has(i) ? f("Cannot add the same private member more than once") : e instanceof WeakSet ? e.add(i) : e.set(i, t), h = (i, e, t, s) => (D(i, e, "write to private field"), s ? s.call(i, t) : e.set(i, t), t);
/* empty css                                                         */
import $ from "../_virtual/air-datepicker-B48dH2ec.js";
import k from "../_virtual/en-CiPB6ox8.js";
import S from "../_virtual/de-D4mPxDrH.js";
var l;
const c = class c {
  /**
   * @param {BookingForm} bookingForm
   * @param {object} options
   */
  constructor(e, t = {}) {
    m(this, l, null);
    d(this, "bookings", []);
    d(this, "resourceArchives", {});
    d(this, "resources", []);
    this.bookingForm = e, this.$mount = e.$mount, this.options = { ...c.defaults, ...t }, this.labels = this.options.labels || c.defaults.labels;
  }
  static get airLocaleEn() {
    return k;
  }
  static get airLocaleDe() {
    return S;
  }
  async init() {
    this.bookingForm.isLoading = !0, await Promise.all([
      this.loadResources(),
      this.loadBookings()
    ]), await this.render(), this.bookingForm.isLoading = !1;
  }
  async render() {
    const e = document.createElement("div");
    e.classList.add("rb-calendar-container"), e.innerHTML = `
            <div class="rb-selection">
                <fieldset class="rb-resources">
                    <legend>${this.labels.resources}</legend>
                    ${this.resources.map((t) => {
      const s = `${this.$mount.id}-r${t.id}`;
      return `
                            <label for="${s}" class="rb-resource-label">
                                <input type="checkbox" class="rb-resource-cbx" id="${s}" value="${t.id}">
                                <span>${t.title}</span>
                            </label>
                        `;
    }).join("")}
                </fieldset>
                <div class="rb-picked-dates"></div>
            </div>
            <div class="rb-airdatepicker" data-rb-slot="calendar"></div>
        `, this.$mount.appendChild(e), this.$calWrapper = e.querySelector('[data-rb-slot="calendar"]'), this.air = this.initCalendar(this.$calWrapper), this.checkboxes = e.querySelectorAll(".rb-resource-cbx"), this.initCheckboxes(this.checkboxes), this.renderPickedDates();
  }
  renderPickedDates() {
    var a, n, r;
    const e = this.$mount.querySelector(".rb-picked-dates");
    if (!this.air.selectedDates || this.air.selectedDates.length !== 2) {
      e.innerHTML = ((a = this.bookingForm.getTemplate("select-dates")) == null ? void 0 : a.innerHTML) || '<div class="rb-no-dates-picked">Please select the date range you want to book in the calendar.</div>';
      return;
    }
    const [t, s] = this.air.selectedDates;
    if (t > s && ([t, s] = [s, t]), !this.isRangeValid(t, s)) {
      e.innerHTML = ((n = this.bookingForm.getTemplate("invalid-dates")) == null ? void 0 : n.innerHTML) || '<div class="rb-invalid-dates">The selected date range is not valid.</div>';
      return;
    }
    e.innerHTML = (r = this.air.selectedDates) == null ? void 0 : r.map((o) => {
      const u = o.getDate().toString().padStart(2, "0"), g = (o.getMonth() + 1).toString().padStart(2, "0"), R = o.getFullYear();
      return `<div class="rb-picked-date">${u}.${g}.${R}</div>`;
    }).join("");
  }
  initCheckboxes(e) {
    for (const t of e)
      t.addEventListener("change", () => this._onCheckboxChange(t));
  }
  _onCheckboxChange(e) {
    const t = Number.parseInt(e.value);
    let s = this.bookingForm.data.isResourceUsed(t) ? this.getDisabledRanges(t) : null;
    this.bookingForm.data.useResource(t, +!!e.checked), h(this, l, null), s && this._cal_unblockDates(s), this._cal_updateBlockedDates(), this.renderPickedDates();
  }
  initCalendar(e) {
    var a, n;
    const t = /* @__PURE__ */ new Date();
    t.setDate(t.getDate() + 1), t.setHours(0, 0, 0, 0);
    const s = new $(e, {
      locale: (n = (a = this.options.airDatepicker) == null ? void 0 : a.locale) != null ? n : k,
      inline: !0,
      range: !0,
      timepicker: !1,
      minDate: t,
      multipleDatesSeparator: "--",
      onSelect: ({ datepicker: r }) => {
        this.renderPickedDates(), this.bookingForm.data.start = r.selectedDates[0] || null, this.bookingForm.data.end = r.selectedDates[1] || null;
      },
      onBeforeSelect: this._cal_onBeforeSelect.bind(this),
      onFocus: this._cal_onFocus.bind(this),
      onRenderCell: this._cal_onRenderCell.bind(this)
    });
    return this._cal_updateBlockedDates(s), s;
  }
  _cal_unblockDates(e) {
    for (const t of e)
      for (let s = t[0]; s <= t[1]; s.setDate(s.getDate() + 1))
        this.air.enableDate(s);
  }
  _cal_updateBlockedDates(e = this.air) {
    var t, s;
    for (const [a, n] of this.disabledRanges) {
      let r = new Date(a);
      for (; r <= n; )
        e.disableDate(new Date(r)), r.setDate(r.getDate() + 1);
    }
    ((t = e.selectedDates) == null ? void 0 : t.length) === 2 && !this.isRangeValid(e.selectedDates[0], e.selectedDates[1]) && (e.clear(), (s = e.$datepicker) == null || s.querySelectorAll(".-day-.-selected-").forEach((a) => a.classList.remove("-selected-")));
  }
  _cal_validateSelection(e, t) {
    var a;
    const s = (a = t.selectedDates[0]) != null ? a : null;
    return s === null || t.selectedDates.length !== 1 ? !0 : this.isRangeValid(e, s);
  }
  _cal_onBeforeSelect({ date: e, datepicker: t }) {
    return this._cal_validateSelection(e, t);
  }
  _cal_onFocus({ date: e, datepicker: t }) {
    const s = this._cal_validateSelection(e, t);
    t.$datepicker.classList.toggle("-disabled-range-", !s);
  }
  _cal_onRenderCell({ date: e }) {
    if (this.isDateBlocked(e))
      return {
        disabled: !0
      };
  }
  get applicableBookings() {
    return this.bookings.filter((e) => this.bookingForm.data.isResourceUsed(e.resource_id));
  }
  get disabledRanges() {
    return this.getDisabledRanges();
  }
  getDisabledRanges(e = null) {
    if (e === null && b(this, l))
      return b(this, l);
    const t = [];
    for (const a of this.applicableBookings)
      if (!(e !== null && a.resource_id !== e))
        for (const n of a.blocked) {
          const r = new Date(Number.parseInt(n.start) * 1e3), o = new Date(Number.parseInt(n.end) * 1e3);
          r.setHours(0, 0, 0, 0), o.setHours(23, 59, 59, 999), t.push([r, o]);
        }
    t.sort((a, n) => a[0] - n[0]);
    const s = [];
    for (const a of t) {
      const n = s[s.length - 1];
      !n || a[0] > n[1] ? s.push(a) : a[1] > n[1] && (n[1] = a[1]);
    }
    return h(this, l, s);
  }
  isDateBlocked(e) {
    return this.disabledRanges.some((t) => e >= t[0] && e <= t[1]);
  }
  isRangeValid(e, t) {
    const s = e.getTime(), a = t.getTime(), n = Math.min(s, a), r = Math.max(s, a);
    for (const o of this.disabledRanges) {
      const u = o[0] instanceof Date ? o[0].getTime() : o[0], g = o[1] instanceof Date ? o[1].getTime() : o[1];
      if (n <= g && r >= u)
        return !1;
    }
    return !0;
  }
  async loadResources() {
    const e = await this.bookingForm.fetchResources();
    e || this.err(), this.resourceArchives = {};
    for (const t of e.archives || [])
      this.resourceArchives[t.id] = t;
    this.resources = e.resources;
  }
  async loadBookings() {
    const e = await this.bookingForm.fetchBookings();
    e || this.err(), this.bookings = e.bookings || [], h(this, l, null);
  }
  err() {
    var e;
    this.$mount.innerHTML = ((e = this.bookingForm.getTemplate("error")) == null ? void 0 : e.innerHTML) || "An error occurred.";
  }
  /**
   * Factory method to create and initialize a ConsecutiveDays instance.
   * @param {BookingForm} bookingForm
   * @param {object} options
   * @return {ConsecutiveDays}
   */
  static async mount(e, t = {}) {
    e.bind();
    const s = new c(e, t);
    return await s.init(), s;
  }
};
l = new WeakMap(), d(c, "defaults", {
  labels: {
    resources: "Resources"
  },
  airDatepicker: {
    locale: k
  }
});
let p = c;
export {
  p as default
};
