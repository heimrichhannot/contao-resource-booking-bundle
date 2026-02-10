import { parseRootConfig as e } from "./config-Cgg8_CJ3.js";
import i from "./BookingForm-D0konfOq.js";
function c(o = {}) {
  const {
    rootSelector: t = "[data-huhrb-root]"
  } = o, r = document.querySelectorAll(t);
  for (const n of r)
    a(n);
  console.debug("[HuH RB] Booking forms initialized.");
}
function a(o) {
  if (!o) return;
  const t = o.dataset.huhrbRoot;
  if (!t) throw new Error("Missing required data attribute: data-huhrb-root on element");
  const r = e(t);
  new i(o, r);
}
export {
  c as default,
  a as initRoot
};
