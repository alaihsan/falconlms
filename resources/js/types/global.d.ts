import type { Directive } from 'vue';
import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            timezone: string;
            auth: Auth;
            nav: {
                courses: { id: number; name: string }[];
                unreadNotifications: number;
            } | null;
            uploads: { maxBytes: number; maxFiles: number };
            sidebarOpen: boolean;
            breadcrumbs?: BreadcrumbItem[];
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface GlobalDirectives {
        vFocus: Directive<HTMLElement, boolean | undefined>;
    }
}
