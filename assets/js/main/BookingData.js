export default class BookingData {
    #resources = {};
    #start = null;
    #stop = null;

    constructor() {}

    get resources() {
        return this.#resources;
    }

    get start() {
        return this.#start;
    }

    get stop() {
        return this.#stop;
    }

    useResource(resourceId, capacity) {
        this.#resources[resourceId] = {
            id: resourceId,
            capacity,
        };
    }

    set start(start) {
        this.#start = start;
    }

    set stop(stop) {
        this.#stop = stop;
    }

    toJSON() {
        return JSON.stringify(this.#resources);
    }
}