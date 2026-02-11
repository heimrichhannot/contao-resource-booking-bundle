var t = Object.defineProperty;
var n = (o, s, i) => s in o ? t(o, s, { enumerable: !0, configurable: !0, writable: !0, value: i }) : o[s] = i;
var e = (o, s, i) => n(o, typeof s != "symbol" ? s + "" : s, i);
/* empty css                                                         */
import "../_virtual/air-datepicker-DlRCcePd.js";
class r {
  /**
   * @param {BookingForm} bookingForm
   */
  constructor(s) {
    e(this, "bookings", []);
    e(this, "resourceArchives", {});
    e(this, "resources", []);
    this.bookingForm = s, this.$mount = s.$mount;
  }
  async init() {
    this.bookingForm.isLoading = !0, await Promise.all([
      this.loadResources(),
      this.loadBookings()
    ]), await this.render(), this.bookingForm.isLoading = !1;
  }
  async render() {
    const s = document.createElement("div");
    s.innerHTML = `
            <div class="calendar-container">
                <div class="calendar"></div>
                <div class="resources">
                    ${this.resources.map((i) => `<div class="resource">${i.title}</div>`).join("")}
                </div>
            </div>
        `, this.$mount.appendChild(s);
  }
  async loadResources() {
    const s = await this.bookingForm.fetchResources();
    this.resourceArchives = {};
    for (const i of s.archives || [])
      this.resourceArchives[i.id] = i;
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
    const i = new r(s);
    return await i.init(), i;
  }
}
export {
  r as default
};
