function o(r, e) {
  if (r === null || typeof r != "object" || Array.isArray(r))
    throw new TypeError(`${e} must be an object`);
}
function a(r, e) {
  if (typeof r != "string")
    throw new TypeError(`${e} must be a string`);
}
function u(r, e) {
  if (!Number.isInteger(r))
    throw new TypeError(`${e} must be an integer`);
}
function p(r, e) {
  if (!Array.isArray(r))
    throw new TypeError(`${e} must be an array of integers`);
  for (let s = 0; s < r.length; s++)
    if (!Number.isInteger(r[s]))
      throw new TypeError(`${e}[${s}] must be an integer`);
}
function m(r, e, s) {
  for (const i of Object.keys(r))
    if (!e.includes(i))
      throw new TypeError(`${s} has unknown key "${i}"`);
}
function y(r, e = "config") {
  if (o(r, e), m(r, ["api", "selectors", "ref", "limits"], e), o(r.api, `${e}.api`), m(r.api, ["bookings", "resources"], `${e}.api`), !("bookings" in r.api)) throw new TypeError(`${e}.api.bookings is required`);
  if (!("resources" in r.api)) throw new TypeError(`${e}.api.resources is required`);
  if (a(r.api.bookings, `${e}.api.bookings`), a(r.api.resources, `${e}.api.resources`), !("selectors" in r)) throw new TypeError(`${e}.selectors is required`);
  if (o(r.selectors, `${e}.selectors`), m(r.selectors, ["form", "mount", "dataInput"], `${e}.selectors`), !("form" in r.selectors)) throw new TypeError(`${e}.selectors.form is required`);
  if (!("mount" in r.selectors)) throw new TypeError(`${e}.selectors.mount is required`);
  if (!("dataInput" in r.selectors)) throw new TypeError(`${e}.selectors.dataInput is required`);
  if (a(r.selectors.form, `${e}.selectors.form`), a(r.selectors.mount, `${e}.selectors.mount`), a(r.selectors.dataInput, `${e}.selectors.dataInput`), !("ref" in r)) throw new TypeError(`${e}.ref is required`);
  if (o(r.ref, `${e}.ref`), m(r.ref, ["booking_archive", "form", "resource_archives"], `${e}.ref`), !("booking_archive" in r.ref)) throw new TypeError(`${e}.ref.booking_archive is required`);
  if (!("form" in r.ref)) throw new TypeError(`${e}.ref.form is required`);
  if (!("resource_archives" in r.ref)) throw new TypeError(`${e}.ref.resource_archives is required`);
  if (u(r.ref.booking_archive, `${e}.ref.booking_archive`), u(r.ref.form, `${e}.ref.form`), p(r.ref.resource_archives, `${e}.ref.resource_archives`), !("limits" in r)) throw new TypeError(`${e}.limits is required`);
  o(r.limits, `${e}.limits`), m(r.limits, ["min_advance_days", "max_advance_days", "max_duration_days"], `${e}.limits`), u(r.limits.min_advance_days, `${e}.limits.min_advance_days`), u(r.limits.max_advance_days, `${e}.limits.max_advance_days`), u(r.limits.max_duration_days, `${e}.limits.max_duration_days`);
}
function $(r, e = "config") {
  var i;
  let s;
  try {
    s = JSON.parse(r);
  } catch (n) {
    throw new SyntaxError(`${e} is not valid JSON: ${String((i = n.message) != null ? i : n)}`);
  }
  return y(s), s;
}
export {
  $ as parseConfig,
  y as validateConfig
};
