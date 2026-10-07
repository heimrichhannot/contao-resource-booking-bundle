function a(t, e) {
  const n = {}, o = new Intl.DateTimeFormat("en-US", { timeZone: e, year: "numeric", month: "numeric", day: "numeric" });
  for (const { type: r, value: s } of o.formatToParts(t))
    n[r] = s;
  return new Date(Number(n.year), Number(n.month) - 1, Number(n.day));
}
function u(t, e, n = /* @__PURE__ */ new Date()) {
  const o = a(n, e);
  return o.setDate(o.getDate() + t), o;
}
function c(t) {
  const e = (r, s = 2) => String(Math.abs(r)).padStart(s, "0"), n = -t.getTimezoneOffset(), o = n < 0 ? "-" : "+";
  return `${t.getFullYear()}-${e(t.getMonth() + 1)}-${e(t.getDate())}T${e(t.getHours())}:${e(t.getMinutes())}:${e(t.getSeconds())}.${e(t.getMilliseconds(), 3)}${o}${e(Math.trunc(n / 60))}:${e(n % 60)}`;
}
export {
  a as siteDay,
  u as siteDaysFromToday,
  c as toOffsetISOString
};
