import { usePage } from '@inertiajs/vue3';

const page = usePage();
const formatters = new Map<string, Intl.DateTimeFormat>();

// Deadlines follow the school's clock (config app.timezone), not the device's.
function formatter(options: Intl.DateTimeFormatOptions): Intl.DateTimeFormat {
    const timeZone = page.props.timezone || undefined;
    const key = JSON.stringify([timeZone, options]);

    if (!formatters.has(key)) {
        formatters.set(
            key,
            new Intl.DateTimeFormat('id-ID', { ...options, timeZone }),
        );
    }

    return formatters.get(key)!;
}

export function formatDateTime(iso: string | null | undefined): string {
    return iso
        ? formatter({
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          }).format(new Date(iso))
        : '';
}

export function formatDate(iso: string | null | undefined): string {
    return iso
        ? formatter({ day: 'numeric', month: 'short', year: 'numeric' }).format(
              new Date(iso),
          )
        : '';
}

export function formatBytes(bytes: number | null | undefined): string {
    if (!bytes) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );

    return `${(bytes / 1024 ** exponent).toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

export function isPast(iso: string | null | undefined): boolean {
    return !!iso && new Date(iso).getTime() < Date.now();
}
