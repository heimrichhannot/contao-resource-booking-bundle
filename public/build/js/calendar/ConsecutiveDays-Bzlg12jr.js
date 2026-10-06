var y = Object.defineProperty;
var k = (a) => {
  throw TypeError(a);
};
var S = (a, e, t) => e in a ? y(a, e, { enumerable: !0, configurable: !0, writable: !0, value: t }) : a[e] = t;
var u = (a, e, t) => S(a, typeof e != "symbol" ? e + "" : e, t), p = (a, e, t) => e.has(a) || k("Cannot " + t);
var m = (a, e, t) => (p(a, e, "read from private field"), t ? t.call(a) : e.get(a)), D = (a, e, t) => e.has(a) ? k("Cannot add the same private member more than once") : e instanceof WeakSet ? e.add(a) : e.set(a, t), g = (a, e, t, s) => (p(a, e, "write to private field"), s ? s.call(a, t) : e.set(a, t), t);
/* empty css                                                         */
import F from "../_virtual/air-datepicker-DaA9xI0A.js";
import f from "../_virtual/en-CiPB6ox8.js";
import $ from "../_virtual/de-D4mPxDrH.js";
function _(a) {
  return new DOMParser().parseFromString(String(a != null ? a : ""), "text/html").documentElement.textContent;
}
function v(a) {
  const e = /* @__PURE__ */ new Date();
  return e.setDate(e.getDate() + a), e.setHours(0, 0, 0, 0), e;
}
var c;
const h = class h {
  /**
   * @param {BookingForm} bookingForm
   * @param {object} options
   */
  constructor(e, t = {}) {
    D(this, c, null);
    u(this, "bookings", []);
    u(this, "resourceArchives", {});
    u(this, "resources", []);
    this.bookingForm = e, this.$mount = e.$mount, this.options = { ...h.defaults, ...t }, this.labels = this.options.labels || h.defaults.labels;
  }
  static get airLocaleEn() {
    return f;
  }
  static get airLocaleDe() {
    return $;
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
                    <legend></legend>
                </fieldset>
                <div class="rb-picked-dates"></div>
            </div>
            <div class="rb-airdatepicker" data-rb-slot="calendar"></div>
        `;
    const t = e.querySelector(".rb-resources");
    t.querySelector("legend").textContent = _(this.labels.resources);
    for (const s of this.resources) {
      const i = `${this.$mount.id}-r${Number.parseInt(s.id)}`, n = document.createElement("label");
      n.htmlFor = i, n.className = "rb-resource-label";
      const r = document.createElement("input");
      r.type = "checkbox", r.className = "rb-resource-cbx", r.id = i, r.value = String(Number.parseInt(s.id));
      const o = document.createElement("span");
      o.textContent = _(s.title), n.append(r, o), t.append(n);
    }
    this.$mount.appendChild(e), this.$calWrapper = e.querySelector('[data-rb-slot="calendar"]'), this.air = this.initCalendar(this.$calWrapper), this.checkboxes = e.querySelectorAll(".rb-resource-cbx"), this.initCheckboxes(this.checkboxes), this.renderPickedDates();
  }
  renderPickedDates() {
    var i, n, r;
    const e = this.$mount.querySelector(".rb-picked-dates");
    if (!this.air.selectedDates || this.air.selectedDates.length !== 2) {
      e.innerHTML = ((i = this.bookingForm.getTemplate("select-dates")) == null ? void 0 : i.innerHTML) || '<div class="rb-no-dates-picked">Please select the date range you want to book in the calendar.</div>';
      return;
    }
    const [t, s] = this.air.selectedDates;
    if (t > s && ([t, s] = [s, t]), !this.isRangeValid(t, s)) {
      e.innerHTML = ((n = this.bookingForm.getTemplate("invalid-dates")) == null ? void 0 : n.innerHTML) || '<div class="rb-invalid-dates">The selected date range is not valid.</div>';
      return;
    }
    e.innerHTML = (r = this.air.selectedDates) == null ? void 0 : r.map((o) => {
      const l = o.getDate().toString().padStart(2, "0"), d = (o.getMonth() + 1).toString().padStart(2, "0"), b = o.getFullYear();
      return `<div class="rb-picked-date">${l}.${d}.${b}</div>`;
    }).join("");
  }
  initCheckboxes(e) {
    for (const t of e)
      t.addEventListener("change", () => this._onCheckboxChange(t));
  }
  _onCheckboxChange(e) {
    const t = Number.parseInt(e.value);
    let s = this.bookingForm.data.isResourceUsed(t) ? this.getDisabledRanges(t) : null;
    this.bookingForm.data.useResource(t, +!!e.checked), g(this, c, null), s && this._cal_unblockDates(s), this._cal_updateBlockedDates(), this.renderPickedDates();
  }
  initCalendar(e) {
    var o, l;
    const { min_advance_days: t, max_advance_days: s } = this.bookingForm.config.limits, i = v(t), n = v(s), r = new F(e, {
      locale: (l = (o = this.options.airDatepicker) == null ? void 0 : o.locale) != null ? l : f,
      inline: !0,
      range: !0,
      timepicker: !1,
      minDate: i,
      maxDate: n,
      multipleDatesSeparator: "--",
      onSelect: ({ datepicker: d }) => {
        this.renderPickedDates(), this.bookingForm.data.start = d.selectedDates[0] || null, this.bookingForm.data.end = d.selectedDates[1] || null;
      },
      onBeforeSelect: this._cal_onBeforeSelect.bind(this),
      onFocus: this._cal_onFocus.bind(this),
      onRenderCell: this._cal_onRenderCell.bind(this)
    });
    return this._cal_updateBlockedDates(r), r;
  }
  _cal_unblockDates(e) {
    for (const t of e)
      for (let s = t[0]; s <= t[1]; s.setDate(s.getDate() + 1))
        this.air.enableDate(s);
  }
  _cal_updateBlockedDates(e = this.air) {
    var t, s;
    for (const [i, n] of this.disabledRanges) {
      let r = new Date(i);
      for (; r <= n; )
        e.disableDate(new Date(r)), r.setDate(r.getDate() + 1);
    }
    ((t = e.selectedDates) == null ? void 0 : t.length) === 2 && !this.isRangeValid(e.selectedDates[0], e.selectedDates[1]) && (e.clear(), (s = e.$datepicker) == null || s.querySelectorAll(".-day-.-selected-").forEach((i) => i.classList.remove("-selected-")));
  }
  _cal_validateSelection(e, t) {
    var i;
    const s = (i = t.selectedDates[0]) != null ? i : null;
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
    if (e === null && m(this, c))
      return m(this, c);
    const t = [];
    for (const i of this.applicableBookings)
      if (!(e !== null && i.resource_id !== e))
        for (const n of i.blocked) {
          const r = new Date(Number.parseInt(n.start) * 1e3), o = new Date(Number.parseInt(n.end) * 1e3);
          r.setHours(0, 0, 0, 0), o.setHours(23, 59, 59, 999), t.push([r, o]);
        }
    t.sort((i, n) => i[0] - n[0]);
    const s = [];
    for (const i of t) {
      const n = s[s.length - 1];
      !n || i[0] > n[1] ? s.push(i) : i[1] > n[1] && (n[1] = i[1]);
    }
    return g(this, c, s);
  }
  isDateBlocked(e) {
    return this.disabledRanges.some((t) => e >= t[0] && e <= t[1]);
  }
  isRangeValid(e, t) {
    const s = e.getTime(), i = t.getTime(), n = Math.min(s, i), r = Math.max(s, i);
    if (Math.round((r - n) / 864e5) + 1 > this.bookingForm.config.limits.max_duration_days)
      return !1;
    for (const l of this.disabledRanges) {
      const d = l[0] instanceof Date ? l[0].getTime() : l[0], b = l[1] instanceof Date ? l[1].getTime() : l[1];
      if (n <= b && r >= d)
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
    e || this.err(), this.bookings = e.bookings || [], g(this, c, null);
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
    const s = new h(e, t);
    return await s.init(), s;
  }
};
c = new WeakMap(), u(h, "defaults", {
  labels: {
    resources: "Resources"
  },
  airDatepicker: {
    locale: f
  }
});
let R = h;
export {
  R as default
};
