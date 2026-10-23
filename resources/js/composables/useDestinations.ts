import { ref } from 'vue';
import DestinationController from '@/actions/App/Http/Controllers/Content/DestinationController';
import type { CreationContext, Destination, DestinationOptions } from '@/types';

// Loaded when a location picker opens, shared by every picker.
const options = ref<DestinationOptions | null>(null);
const loading = ref(false);
const failed = ref(false);

async function load(): Promise<void> {
    loading.value = true;
    failed.value = false;

    try {
        const response = await fetch(DestinationController.url(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        options.value = (await response.json()) as DestinationOptions;
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

/**
 * Where the page says new content goes, or null when the page has no place
 * to save to (for example the admin's home page).
 */
export function destinationFromContext(
    context: CreationContext | null | undefined,
): Destination | null {
    return context?.space
        ? {
              space: context.space,
              folder: context.folder,
              topic: context.topic,
          }
        : null;
}

/** "Pustaka Saya › Bab 2 › Latihan" or "Matematika X IPA 1 › Bab 1". */
export function destinationLabel(destination: Destination): string {
    const folder = destination.folder
        ? (options.value?.libraries
              .find((library) => library.space.id === destination.space.id)
              ?.folders.find((f) => f.id === destination.folder?.id)?.label ??
          destination.folder.name)
        : null;

    return [destination.space.name, folder, destination.topic?.name]
        .filter(Boolean)
        .join(' › ');
}

export function useDestinations() {
    return { options, loading, failed, load };
}
