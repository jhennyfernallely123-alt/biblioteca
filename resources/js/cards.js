export function countdownParts(resetAt, now = new Date()) {
    const remaining = new Date(resetAt).getTime() - now.getTime();

    if (remaining <= 0) {
        return { hours: 0, minutes: 0 };
    }

    const totalMinutes = Math.ceil(remaining / 60000);

    return {
        hours: Math.floor(totalMinutes / 60),
        minutes: totalMinutes % 60,
    };
}
