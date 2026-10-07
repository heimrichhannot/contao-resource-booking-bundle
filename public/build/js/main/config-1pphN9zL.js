function a(r, e) {
  if (r === null || typeof r != "object" || Array.isArray(r))
    throw new TypeError(`${e} must be an object`);
}
function i(r, e) {
  if (typeof r != "string")
    throw new TypeError(`${e} must be a string`);
}
function m(r, e) {
  if (!Number.isInteger(r))
    throw new TypeError(`${e} must be an integer`);
}
function $(r, e) {
  if (!Array.isArray(r))
    throw new TypeError(`${e} must be an array of integers`);
  for (let s = 0; s < r.length; s++)
    if (!Number.isInteger(r[s]))
      throw new TypeError(`${e}[${s}] must be an integer`);
}
function u(r, e, s) {
  for (const o of Object.keys(r))
    if (!e.includes(o))
      throw new TypeError(`${s} has unknown key "${o}"`);
}
function p(r, e = "config") {
  if (a(r, e), u(r, ["api", "selectors", "ref", "limits", "timezone"], e), a(r.api, `${e}.api`), u(r.api, ["bookings", "resources"], `${e}.api`), !("bookings" in r.api)) throw new TypeError(`${e}.api.bookings is required`);
  if (!("resources" in r.api)) throw new TypeError(`${e}.api.resources is required`);
  if (i(r.api.bookings, `${e}.api.bookings`), i(r.api.resources, `${e}.api.resources`), !("selectors" in r)) throw new TypeError(`${e}.selectors is required`);
  if (a(r.selectors, `${e}.selectors`), u(r.selectors, ["form", "mount", "dataInput"], `${e}.selectors`), !("form" in r.selectors)) throw new TypeError(`${e}.selectors.form is required`);
  if (!("mount" in r.selectors)) throw new TypeError(`${e}.selectors.mount is required`);
  if (!("dataInput" in r.selectors)) throw new TypeError(`${e}.selectors.dataInput is required`);
  if (i(r.selectors.form, `${e}.selectors.form`), i(r.selectors.mount, `${e}.selectors.mount`), i(r.selectors.dataInput, `${e}.selectors.dataInput`), !("ref" in r)) throw new TypeError(`${e}.ref is required`);
  if (a(r.ref, `${e}.ref`), u(r.ref, ["booking_archive", "form", "resource_archives"], `${e}.ref`), !("booking_archive" in r.ref)) throw new TypeError(`${e}.ref.booking_archive is required`);
  if (!("form" in r.ref)) throw new TypeError(`${e}.ref.form is required`);
  if (!("resource_archives" in r.ref)) throw new TypeError(`${e}.ref.resource_archives is required`);
  if (m(r.ref.booking_archive, `${e}.ref.booking_archive`), m(r.ref.form, `${e}.ref.form`), $(r.ref.resource_archives, `${e}.ref.resource_archives`), !("limits" in r)) throw new TypeError(`${e}.limits is required`);
  if (a(r.limits, `${e}.limits`), u(r.limits, ["min_advance_days", "max_advance_days", "max_duration_days"], `${e}.limits`), m(r.limits.min_advance_days, `${e}.limits.min_advance_days`), m(r.limits.max_advance_days, `${e}.limits.max_advance_days`), m(r.limits.max_duration_days, `${e}.limits.max_duration_days`), !("timezone" in r)) throw new TypeError(`${e}.timezone is required`);
  i(r.timezone, `${e}.timezone`);
}
function y(r, e = "config") {
  var o;
  let s;
  try {
    s = JSON.parse(r);
  } catch (n) {
    throw new SyntaxError(`${e} is not valid JSON: ${String((o = n.message) != null ? o : n)}`);
  }
  return p(s), s;
}
export {
  y as parseConfig,
  p as validateConfig
};
