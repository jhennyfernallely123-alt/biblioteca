const KEY = 'biblioteca:welcome-shown';

export function shouldShowWelcome() {
    if (typeof localStorage === 'undefined') {
        return false;
    }

    if (localStorage.getItem(KEY)) {
        return false;
    }

    localStorage.setItem(KEY, '1');

    return true;
}

export function resetWelcome() {
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem(KEY);
    }
}
