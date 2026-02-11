var r = Object.defineProperty;
var n = (i, s, e) => s in i ? r(i, s, { enumerable: !0, configurable: !0, writable: !0, value: e }) : i[s] = e;
var o = (i, s, e) => n(i, typeof s != "symbol" ? s + "" : s, e);
/* empty css                                                         */
import "../_virtual/air-datepicker-DlRCcePd.js";
class t {
  /**
   * @param {BookingForm} bookingForm
   */
  constructor(s) {
    o(this, "bookings", []);
    o(this, "resourceArchives", {});
    o(this, "resources", []);
    this.bookingForm = s, this.$mount = s.$mount;
  }
  async init() {
    const s = document.createElement("div");
    s.innerHTML = "Lädt...", this.$mount.appendChild(s), await Promise.all([
      this.loadResources(),
      this.loadBookings()
    ]), s.remove(), await this.render();
  }
  async render() {
    const s = document.createElement("div");
    s.innerHTML = `
            <div class="calendar-container">
                <div class="calendar"></div>
                <div class="resources">
                    ${this.resources.map((e) => `<div class="resource">${e.title}</div>`).join("")}
                </div>
            </div>
        `, this.$mount.appendChild(s);
  }
  async loadResources() {
    const s = await this.bookingForm.fetchResources();
    this.resourceArchives = {};
    for (const e of s.archives || [])
      this.resourceArchives[e.id] = e;
    this.resources = s.resources;
  }
  async loadBookings() {
    const s = await this.bookingForm.fetchBookings();
    this.bookings = s;
  }
  /**
   * Factory method to create and initialize a ConsecutiveDays instance.
   * @param {BookingForm} bookingForm
   * @return {ConsecutiveDays}
   */
  static async mount(s) {
    const e = new t(s);
    return await e.init(), e;
  }
}
export {
  t as default
};
