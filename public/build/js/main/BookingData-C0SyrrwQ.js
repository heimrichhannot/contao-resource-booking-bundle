var d = (r) => {
  throw TypeError(r);
};
var l = (r, t, e) => t.has(r) || d("Cannot " + e);
var s = (r, t, e) => (l(r, t, "read from private field"), e ? e.call(r) : t.get(r)), o = (r, t, e) => t.has(r) ? d("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(r) : t.set(r, e), c = (r, t, e, u) => (l(r, t, "write to private field"), u ? u.call(r, e) : t.set(r, e), e);
var h, i, n, a;
class p {
  constructor() {
    o(this, h, {});
    o(this, i, {});
    o(this, n, null);
    o(this, a, null);
  }
  get resources() {
    return s(this, i);
  }
  get isEmpty() {
    return Object.keys(s(this, i)).length < 1;
  }
  get hasRange() {
    return s(this, n) !== null && s(this, a) !== null;
  }
  get start() {
    return s(this, n);
  }
  get end() {
    return s(this, a);
  }
  useResource(t, e) {
    if (e < 1) {
      if (!s(this, i).hasOwnProperty(t)) return;
      delete s(this, i)[t], this.dispatch("resources:removed", { resources: s(this, i), resourceId: t });
    } else
      s(this, i)[t] = {
        id: t,
        quantity: e
      }, this.dispatch("resources:added", { resources: s(this, i), resourceId: t });
    this.dispatch("resources:changed", { resources: s(this, i) });
  }
  isResourceUsed(t) {
    return s(this, i).hasOwnProperty(t) && s(this, i)[t].quantity > 0;
  }
  set start(t) {
    if (!t instanceof Date) throw new Error("start must be a Date object");
    c(this, n, t), this.dispatch("start:changed", { start: t });
  }
  set end(t) {
    if (!t instanceof Date) throw new Error("end must be a Date object");
    c(this, a, t), this.dispatch("end:changed", { end: t });
  }
  on(t, e) {
    s(this, h).hasOwnProperty(t) || (s(this, h)[t] = []), s(this, h)[t].push(e);
  }
  dispatch(t, e = {}) {
    if (s(this, h).hasOwnProperty(t))
      for (const u of s(this, h)[t])
        u(e);
  }
  toJSON() {
    var t, e;
    return {
      resources: Object.values(s(this, i)),
      start: (t = s(this, n)) == null ? void 0 : t.toISOString(),
      end: (e = s(this, a)) == null ? void 0 : e.toISOString()
    };
  }
}
h = new WeakMap(), i = new WeakMap(), n = new WeakMap(), a = new WeakMap();
export {
  p as default
};
