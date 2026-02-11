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
 * @typedef {object} api
 * @property {string} bookings
 * @property {string} resources
 */

/**
 * @typedef {object} ref
 * @property {number} booking_archive
 * @property {number} form
 * @property {number[]} resource_archives
 */

/**
 * @typedef {object} selectors
 * @property {string} form
 * @property {string} mount
 * @property {string} dataInput
 */

/**
 * @typedef {object} BookingFormConfig
 * @property {selectors} selectors
 * @property {ref} ref
 * @property {api} api
 */

/**
 * Validates the root configuration object.
 *
 * @param {BookingFormConfig} config
 * @param {string} [contextPath="config"]
 */
export function validateConfig(config, contextPath = "config") {
    // Root object
    assertObject(config, contextPath);
    assertNoUnknownKeys(config, ["api", "selectors", "ref"], contextPath);

    // api
    assertObject(config.api, `${contextPath}.api`);
    assertNoUnknownKeys(config.api, ["bookings", "resources"], `${contextPath}.api`);
    if (!("bookings" in config.api)) throw new TypeError(`${contextPath}.api.bookings is required`);
    if (!("resources" in config.api)) throw new TypeError(`${contextPath}.api.resources is required`);
    assertString(config.api.bookings, `${contextPath}.api.bookings`);
    assertString(config.api.resources, `${contextPath}.api.resources`);

    // selectors
    if (!("selectors" in config)) throw new TypeError(`${contextPath}.selectors is required`);
    assertObject(config.selectors, `${contextPath}.selectors`);
    assertNoUnknownKeys(config.selectors, ["form", "mount", "dataInput"], `${contextPath}.selectors`);

    if (!("form" in config.selectors)) throw new TypeError(`${contextPath}.selectors.form is required`);
    if (!("mount" in config.selectors)) throw new TypeError(`${contextPath}.selectors.mount is required`);
    if (!("dataInput" in config.selectors)) throw new TypeError(`${contextPath}.selectors.dataInput is required`);
    assertString(config.selectors.form, `${contextPath}.selectors.form`);
    assertString(config.selectors.mount, `${contextPath}.selectors.mount`);
    assertString(config.selectors.dataInput, `${contextPath}.selectors.dataInput`);

    // ref
    if (!("ref" in config)) throw new TypeError(`${contextPath}.ref is required`);
    assertObject(config.ref, `${contextPath}.ref`);
    assertNoUnknownKeys(config.ref, ["booking_archive", "form", "resource_archives"], `${contextPath}.ref`);

    if (!("booking_archive" in config.ref)) throw new TypeError(`${contextPath}.ref.booking_archive is required`);
    if (!("form" in config.ref)) throw new TypeError(`${contextPath}.ref.form is required`);
    if (!("resource_archives" in config.ref)) throw new TypeError(`${contextPath}.ref.resource_archives is required`);

    assertInt(config.ref.booking_archive, `${contextPath}.ref.booking_archive`);
    assertInt(config.ref.form, `${contextPath}.ref.form`);
    assertIntArray(config.ref.resource_archives, `${contextPath}.ref.resource_archives`);
}

/**
 * Parses the root configuration from a JSON string.
 *
 * @param {string} jsonString
 * @param {string} [contextPath="config"]
 * @returns {BookingFormConfig}
 */
export function parseConfig(jsonString, contextPath = "config") {
    let parsed;
    try {
        parsed = JSON.parse(jsonString);
    } catch (err) {
        throw new SyntaxError(`${contextPath} is not valid JSON: ${String(err.message ?? err)}`);
    }

    validateConfig(parsed);

    return parsed;
}
