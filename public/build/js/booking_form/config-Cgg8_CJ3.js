function i(o, r) {
  if (o === null || typeof o != "object" || Array.isArray(o))
    throw new TypeError(`${r} must be an object`);
}
function u(o, r) {
  if (typeof o != "string")
    throw new TypeError(`${r} must be a string`);
}
function w(o, r) {
  if (!Number.isInteger(o))
    throw new TypeError(`${r} must be an integer`);
}
function t(o, r) {
  if (!Array.isArray(o))
    throw new TypeError(`${r} must be an array of integers`);
  for (let e = 0; e < o.length; e++)
    if (!Number.isInteger(o[e]))
      throw new TypeError(`${r}[${e}] must be an integer`);
}
function f(o, r, e) {
  for (const s of Object.keys(o))
    if (!r.includes(s))
      throw new TypeError(`${e} has unknown key "${s}"`);
}
function y(o, r = "$root.dataset.huhrbRoot") {
  var s;
  let e;
  try {
    e = JSON.parse(o);
  } catch (n) {
    throw new SyntaxError(`${r} is not valid JSON: ${String((s = n.message) != null ? s : n)}`);
  }
  if (i(e, r), f(e, ["selectors", "ref"], r), !("selectors" in e)) throw new TypeError(`${r}.selectors is required`);
  if (i(e.selectors, `${r}.selectors`), f(e.selectors, ["form", "mount"], `${r}.selectors`), !("form" in e.selectors)) throw new TypeError(`${r}.selectors.form is required`);
  if (!("mount" in e.selectors)) throw new TypeError(`${r}.selectors.mount is required`);
  if (u(e.selectors.form, `${r}.selectors.form`), u(e.selectors.mount, `${r}.selectors.mount`), !("ref" in e)) throw new TypeError(`${r}.ref is required`);
  if (i(e.ref, `${r}.ref`), f(e.ref, ["booking_archive", "form", "resource_archives"], `${r}.ref`), !("booking_archive" in e.ref)) throw new TypeError(`${r}.ref.booking_archive is required`);
  if (!("form" in e.ref)) throw new TypeError(`${r}.ref.form is required`);
  if (!("resource_archives" in e.ref)) throw new TypeError(`${r}.ref.resource_archives is required`);
  return w(e.ref.booking_archive, `${r}.ref.booking_archive`), w(e.ref.form, `${r}.ref.form`), t(e.ref.resource_archives, `${r}.ref.resource_archives`), e;
}
export {
  y as parseRootConfig
};
