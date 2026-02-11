class r {
  constructor() {
    this.bookingForms = [];
  }
  register(o) {
    this.bookingForms.push(o);
  }
  find(o) {
    const t = typeof o == "string" ? document.getElementById(o) || document.querySelector(o) : o;
    return this.bookingForms.find((s) => s.$mount === t || s.$root === t) || null;
  }
}
export {
  r as default
};
