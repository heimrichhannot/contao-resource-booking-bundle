var d = (i) => {
  throw TypeError(i);
};
var f = (i, t, e) => t.has(i) || d("Cannot " + e);
var s = (i, t, e) => (f(i, t, "read from private field"), e ? e.call(i) : t.get(i)), o = (i, t, e) => t.has(i) ? d("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(i) : t.set(i, e), c = (i, t, e, u) => (f(i, t, "write to private field"), u ? u.call(i, e) : t.set(i, e), e);
import { toOffsetISOString as l } from "./siteTime-DiGu9QJC.js";
var h, r, n, a;
class O {
  constructor() {
    o(this, h, {});
    o(this, r, {});
    o(this, n, null);
    o(this, a, null);
  }
  get resources() {
    return s(this, r);
  }
  get isEmpty() {
    return Object.keys(s(this, r)).length < 1;
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
      if (!s(this, r).hasOwnProperty(t)) return;
      delete s(this, r)[t], this.dispatch("resources:removed", { resources: s(this, r), resourceId: t });
    } else
      s(this, r)[t] = {
        id: t,
        quantity: e
      }, this.dispatch("resources:added", { resources: s(this, r), resourceId: t });
    this.dispatch("resources:changed", { resources: s(this, r) });
  }
  isResourceUsed(t) {
    return s(this, r).hasOwnProperty(t) && s(this, r)[t].quantity > 0;
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
    return {
      resources: Object.values(s(this, r)),
      // With the UTC offset, so the server books the day the visitor picked
      start: s(this, n) ? l(s(this, n)) : void 0,
      end: s(this, a) ? l(s(this, a)) : void 0
    };
  }
}
h = new WeakMap(), r = new WeakMap(), n = new WeakMap(), a = new WeakMap();
export {
  O as default
};
