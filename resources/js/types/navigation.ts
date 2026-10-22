import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    /** Items without a link are shown as "coming soon". */
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
};
