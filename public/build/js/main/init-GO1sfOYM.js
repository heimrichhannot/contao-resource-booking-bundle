import { parseConfig as a } from "./config-CkxhDHMd.js";
import s from "./BookingForm-RI_Pfkea.js";
import c from "./BookingFormRegistry-DvS5h1qd.js";
function h(o = {}) {
  const {
    rootSelector: r = "[data-huhrb-root]"
  } = o, t = document.querySelectorAll(r), n = new c();
  for (const e of t) {
    const i = u(e);
    n.register(i);
  }
  return n;
}
function u(o) {
  if (!o) return;
  const r = o.dataset.huhrbRoot;
  if (!r) throw new Error("Missing required data attribute: data-huhrb-root on element");
  const t = a(r, "$root.dataset.huhrbRoot");
  return new s(o, t);
}
export {
  h as default,
  u as initBookingForm
};
