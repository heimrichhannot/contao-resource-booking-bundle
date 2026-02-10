function assertObject(value, path) {
    if (value === null || typeof value !== "object" || Array.isArray(value)) {
        throw new TypeError(`${path} must be an object`);
    }
}

function assertString(value, path) {
    if (typeof value !== "string") {
        throw new TypeError(`${path} must be a string`);
    }
}

function assertInt(value, path) {
    if (!Number.isInteger(value)) {
        throw new TypeError(`${path} must be an integer`);
    }
}

function assertIntArray(value, path) {
    if (!Array.isArray(value)) {
        throw new TypeError(`${path} must be an array of integers`);
    }
    for (let i = 0; i < value.length; i++) {
        if (!Number.isInteger(value[i])) {
            throw new TypeError(`${path}[${i}] must be an integer`);
        }
    }
}

function assertNoUnknownKeys(obj, allowedKeys, path) {
    for (const key of Object.keys(obj)) {
        if (!allowedKeys.includes(key)) {
            throw new TypeError(`${path} has unknown key "${key}"`);
        }
    }
}

/**
 * @typedef {object} selectors
 * @property {string} form
 * @property {string} mount
 */

/**
 * @typedef {object} ref
 * @property {number} booking_archive
 * @property {number} form
 * @property {number[]} resource_archives
 */

/**
 * @typedef {object} BookingFormConfig
 * @property {selectors} selectors
 * @property {ref} ref
 */

/**
 * Parses the root configuration from a JSON string.
 *
 * @param {string} jsonString
 * @param {string} [contextPath="$root.dataset.huhrbRoot"]
 * @returns {BookingFormConfig}
 */
export function parseRootConfig(jsonString, contextPath = "$root.dataset.huhrbRoot") {
    let parsed;
    try {
        parsed = JSON.parse(jsonString);
    } catch (err) {
        throw new SyntaxError(`${contextPath} is not valid JSON: ${String(err.message ?? err)}`);
    }

    // Root object
    assertObject(parsed, contextPath);
    assertNoUnknownKeys(parsed, ["selectors", "ref"], contextPath);

    // selectors
    if (!("selectors" in parsed)) throw new TypeError(`${contextPath}.selectors is required`);
    assertObject(parsed.selectors, `${contextPath}.selectors`);
    assertNoUnknownKeys(parsed.selectors, ["form", "mount"], `${contextPath}.selectors`);

    if (!("form" in parsed.selectors)) throw new TypeError(`${contextPath}.selectors.form is required`);
    if (!("mount" in parsed.selectors)) throw new TypeError(`${contextPath}.selectors.mount is required`);
    assertString(parsed.selectors.form, `${contextPath}.selectors.form`);
    assertString(parsed.selectors.mount, `${contextPath}.selectors.mount`);

    // ref
    if (!("ref" in parsed)) throw new TypeError(`${contextPath}.ref is required`);
    assertObject(parsed.ref, `${contextPath}.ref`);
    assertNoUnknownKeys(parsed.ref, ["booking_archive", "form", "resource_archives"], `${contextPath}.ref`);

    if (!("booking_archive" in parsed.ref)) throw new TypeError(`${contextPath}.ref.booking_archive is required`);
    if (!("form" in parsed.ref)) throw new TypeError(`${contextPath}.ref.form is required`);
    if (!("resource_archives" in parsed.ref)) throw new TypeError(`${contextPath}.ref.resource_archives is required`);

    assertInt(parsed.ref.booking_archive, `${contextPath}.ref.booking_archive`);
    assertInt(parsed.ref.form, `${contextPath}.ref.form`);
    assertIntArray(parsed.ref.resource_archives, `${contextPath}.ref.resource_archives`);

    return parsed;
}
