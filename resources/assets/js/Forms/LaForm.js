import LaFormErrors from './LaFormErrors';
import LaHttp from './LaHttp';

class LaForm {

    constructor(data) {
        if(typeof data !== 'object') {
            throw new Error('Form data must be object');
        }

        this.lifetimeKeys = [];
        this.original = data;

        this.extend(data);

        this.errors = new LaFormErrors();
        this.busy = false;
        this.successful = false;
        this.submitted = false;
    }

    extend(data) {
        const keys = Object.keys(data);
        let keyIndex,
            key;

        for (keyIndex in keys) {
            key = keys[keyIndex];
            this[key] = data[key];

            if(this.lifetimeKeys.indexOf(key) < 0) {
                this.lifetimeKeys.push(key);
            }
        }
    }

    rebuild(data) {
        this.extend(data);
        this.resetStatus();
    }

    reset() {
        for (let key in this.lifetimeKeys) {
            delete this[key];
        }

        this.extend(this.original);
    }

    toJson() {
        const keys = Object.keys(this.original);
        let keyIndex,
            key,
            toJson = {};

        for (keyIndex in keys) {
            key = keys[keyIndex];
            toJson[key] = this[key];
        }

        return toJson;
    }

    startProcessing() {
        this.errors.forget();
        this.busy = true;
        this.successful = false;
        this.submitted = true;
    }

    finishProcessing() {
        this.busy = false;
        this.successful = true;
    }

    resetStatus() {
        this.errors.forget();
        this.busy = false;
        this.successful = false;
        this.submitted = false;
    }

    setErrors(errors) {
        this.busy = false;
        this.errors.set(errors);
    }

    post(uri) {
        return LaHttp.post(uri, this);
    }

    put(uri) {
        return LaHttp.put(uri, this);
    }

    path(uri) {
        return LaHttp.patch(uri, this);
    }

    delete(uri) {
        return LaHttp.delete(uri, this);
    }
}

export default LaForm;