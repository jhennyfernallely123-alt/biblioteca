const cache = new Map();

export function coverColor(bookId, coverPath) {
    if (!coverPath) {
        return Promise.resolve(null);
    }

    if (cache.has(bookId)) {
        return cache.get(bookId);
    }

    const promise = new Promise((resolve) => {
        if (typeof Image === 'undefined') {
            resolve(null);
            return;
        }

        const image = new Image();
        image.onload = () => resolve(extractColor(image));
        image.onerror = () => resolve(null);
        image.src = `/covers/${bookId}`;
    });

    cache.set(bookId, promise);

    return promise;
}

function extractColor(image) {
    const canvas = document.createElement('canvas');
    canvas.width = 24;
    canvas.height = 36;

    const context = canvas.getContext('2d', { willReadFrequently: true });

    if (!context) {
        return null;
    }

    context.drawImage(image, 0, 0, canvas.width, canvas.height);

    let pixels;

    try {
        pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
    } catch {
        return null;
    }

    const buckets = new Map();

    for (let index = 0; index < pixels.length; index += 4) {
        const red = pixels[index];
        const green = pixels[index + 1];
        const blue = pixels[index + 2];
        const alpha = pixels[index + 3];

        if (alpha < 200) {
            continue;
        }

        const luminance = 0.2126 * red + 0.7152 * green + 0.0722 * blue;

        if (luminance < 18 || luminance > 242) {
            continue;
        }

        const key = `${red >> 4}-${green >> 4}-${blue >> 4}`;
        const bucket = buckets.get(key) ?? { red: 0, green: 0, blue: 0, count: 0 };

        bucket.red += red;
        bucket.green += green;
        bucket.blue += blue;
        bucket.count += 1;
        buckets.set(key, bucket);
    }

    let dominant = null;

    for (const bucket of buckets.values()) {
        if (!dominant || bucket.count > dominant.count) {
            dominant = bucket;
        }
    }

    if (!dominant) {
        return null;
    }

    const red = Math.round(dominant.red / dominant.count);
    const green = Math.round(dominant.green / dominant.count);
    const blue = Math.round(dominant.blue / dominant.count);
    const brightness = (red * 299 + green * 587 + blue * 114) / 1000;

    return {
        background: `rgb(${red} ${green} ${blue})`,
        text: brightness > 150 ? 'var(--color-tinta)' : 'var(--color-crema)',
    };
}
