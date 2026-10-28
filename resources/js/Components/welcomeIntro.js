let shownThisLoad = false;

export function shouldShowWelcome() {
    if (shownThisLoad) {
        return false;
    }

    shownThisLoad = true;

    return true;
}

export function resetWelcome() {
    shownThisLoad = false;
}
