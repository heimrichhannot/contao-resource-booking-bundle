export default class BookingData {
    #listeners = {};
    #resources = {};
    #start = null;
    #end = null;

    get resources() {
        return this.#resources;
    }

    get isEmpty() {
        return Object.keys(this.#resources).length < 1;
    }

    get hasRange() {
        return this.#start !== null && this.#end !== null;
    }

    get start() {
        return this.#start;
    }

    get end() {
        return this.#end;
    }

    useResource(resourceId, quantity) {
        if (quantity < 1) {
            if (!this.#resources.hasOwnProperty(resourceId)) return;
            delete this.#resources[resourceId];
            this.dispatch('resources:removed', { resources: this.#resources, resourceId });
        } else {
            this.#resources[resourceId] = {
                id: resourceId,
                quantity,
            };
            this.dispatch('resources:added', { resources: this.#resources, resourceId });
        }

        this.dispatch('resources:changed', { resources: this.#resources });
    }

    isResourceUsed(resourceId) {
        return this.#resources.hasOwnProperty(resourceId) && this.#resources[resourceId].quantity > 0;
    }

    set start(start) {
        if (!start instanceof Date) throw new Error('start must be a Date object');
        this.#start = start;
        this.dispatch('start:changed', { start });
    }

    set end(end) {
        if (!end instanceof Date) throw new Error('end must be a Date object');
        this.#end = end;
        this.dispatch('end:changed', { end: end });
    }

    on(eventName, callback) {
        if (!this.#listeners.hasOwnProperty(eventName)) {
            this.#listeners[eventName] = [];
        }
        this.#listeners[eventName].push(callback);
    }

    dispatch(eventName, args = {}) {
        if (!this.#listeners.hasOwnProperty(eventName)) return;
        for (const listener of this.#listeners[eventName]) {
            listener(args);
        }
    }

    toJSON() {
        return {
            resources: Object.values(this.#resources),
            start: this.#start?.toISOString(),
            end: this.#end?.toISOString(),
        };
    }
}