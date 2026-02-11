function u(r, e) {
  if (r === null || typeof r != "object" || Array.isArray(r))
    throw new TypeError(`${e} must be an object`);
}
function o(r, e) {
  if (typeof r != "string")
    throw new TypeError(`${e} must be a string`);
}
function f(r, e) {
  if (!Number.isInteger(r))
    throw new TypeError(`${e} must be an integer`);
}
function w(r, e) {
  if (!Array.isArray(r))
    throw new TypeError(`${e} must be an array of integers`);
  for (let s = 0; s < r.length; s++)
    if (!Number.isInteger(r[s]))
      throw new TypeError(`${e}[${s}] must be an integer`);
}
function n(r, e, s) {
  for (const i of Object.keys(r))
    if (!e.includes(i))
      throw new TypeError(`${s} has unknown key "${i}"`);
}
function $(r, e = "config") {
  if (u(r, e), n(r, ["api", "selectors", "ref"], e), u(r.api, `${e}.api`), n(r.api, ["bookings", "resources"], `${e}.api`), !("bookings" in r.api)) throw new TypeError(`${e}.api.bookings is required`);
  if (!("resources" in r.api)) throw new TypeError(`${e}.api.resources is required`);
  if (o(r.api.bookings, `${e}.api.bookings`), o(r.api.resources, `${e}.api.resources`), !("selectors" in r)) throw new TypeError(`${e}.selectors is required`);
  if (u(r.selectors, `${e}.selectors`), n(r.selectors, ["form", "mount", "dataInput"], `${e}.selectors`), !("form" in r.selectors)) throw new TypeError(`${e}.selectors.form is required`);
  if (!("mount" in r.selectors)) throw new TypeError(`${e}.selectors.mount is required`);
  if (!("dataInput" in r.selectors)) throw new TypeError(`${e}.selectors.dataInput is required`);
  if (o(r.selectors.form, `${e}.selectors.form`), o(r.selectors.mount, `${e}.selectors.mount`), o(r.selectors.dataInput, `${e}.selectors.dataInput`), !("ref" in r)) throw new TypeError(`${e}.ref is required`);
  if (u(r.ref, `${e}.ref`), n(r.ref, ["booking_archive", "form", "resource_archives"], `${e}.ref`), !("booking_archive" in r.ref)) throw new TypeError(`${e}.ref.booking_archive is required`);
  if (!("form" in r.ref)) throw new TypeError(`${e}.ref.form is required`);
  if (!("resource_archives" in r.ref)) throw new TypeError(`${e}.ref.resource_archives is required`);
  f(r.ref.booking_archive, `${e}.ref.booking_archive`), f(r.ref.form, `${e}.ref.form`), w(r.ref.resource_archives, `${e}.ref.resource_archives`);
}
function a(r, e = "config") {
  var i;
  let s;
  try {
    s = JSON.parse(r);
  } catch (p) {
    throw new SyntaxError(`${e} is not valid JSON: ${String((i = p.message) != null ? i : p)}`);
  }
  return $(s), s;
}
export {
  a as parseConfig,
  $ as validateConfig
};
