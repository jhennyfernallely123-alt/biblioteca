import { usePage } from '@inertiajs/vue3';
import es from '../../lang/es.json';

function pageProps() {
    try {
        return usePage().props ?? {};
    } catch {
        return {};
    }
}

export function useI18n() {
    function t(key, replacements = {}) {
        const dictionary = pageProps().translations ?? {};
        let text = dictionary[key] ?? es[key] ?? key;

        for (const [name, value] of Object.entries(replacements)) {
            text = text.replaceAll(`:${name}`, String(value));
        }

        return text;
    }

    return { t };
}
