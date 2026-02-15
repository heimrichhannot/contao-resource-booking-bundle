export default class BookingData {
    #listeners = {};
    #resources = {};
    #start = null;
    #stop = null;

    get resources() {
        return this.#resources;
    }

    get isEmpty() {
        return Object.keys(this.#resources).length < 1;
    }

    get start() {
        return this.#start;
    }

    get stop() {
        return this.#stop;
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
        this.#start = start;
        this.dispatch('start:changed', { start });
    }

    set stop(stop) {
        this.#stop = stop;
        this.dispatch('stop:changed', { stop });
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
        return JSON.stringify(Object.values(this.#resources));
    }
}