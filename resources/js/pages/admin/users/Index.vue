<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { KeyRound, Pencil, Search, Upload, UserPlus } from '@lucide/vue';
import { ref, watch } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserImportController from '@/actions/App/Http/Controllers/Admin/UserImportController';
import FormDialog from '@/components/new/FormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useNewActions } from '@/composables/useNewActions';
import type { CreationContext } from '@/types';

type UserRow = {
    id: number;
    name: string;
    username: string;
    role: string;
    roleLabel: string;
    mustChangePassword: boolean;
};

const props = defineProps<{
    context: CreationContext;
    users: {
        data: UserRow[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: { q: string; role: string };
    roles: { value: string; label: string }[];
}>();

const { run } = useNewActions();
const q = ref(props.filters.q);
const role = ref(props.filters.role);
const editing = ref<UserRow | null>(null);

let timer: ReturnType<typeof setTimeout> | undefined;

watch([q, role], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            UserController.index.url(),
            { q: q.value || undefined, role: role.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function resetPassword(user: UserRow): void {
    if (confirm(`Buat password sementara baru untuk ${user.name}?`)) {
        router.post(
            UserController.resetPassword.url(user.id),
            {},
            { preserveScroll: true },
        );
    }
}

// Pagination labels from Laravel contain HTML entities such as &laquo;.
function label(text: string): string {
    return text.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Pengguna" />

    <div class="mx-auto grid w-full max-w-5xl gap-5 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">
                Pengguna
                <span class="text-base font-normal text-muted-foreground"
                    >({{ users.total }})</span
                >
            </h1>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="UserImportController.create.url()"
                        ><Upload /> Impor CSV</Link
                    >
                </Button>
                <Button @click="run('user')"><UserPlus /> Pengguna baru</Button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <div class="relative flex-1">
                <Search
                    class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="q"
                    placeholder="Cari nama atau username"
                    class="pl-9"
                    data-test="user-search"
                />
            </div>
            <select
                v-model="role"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                aria-label="Peran"
            >
                <option value="">Semua peran</option>
                <option
                    v-for="option in roles"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Username</th>
                        <th class="px-4 py-2 font-medium">Peran</th>
                        <th class="px-4 py-2" />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        data-test="user-row"
                    >
                        <td class="px-4 py-2">
                            {{ user.name }}
                            <span
                                v-if="user.mustChangePassword"
                                class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800"
                                >password sementara</span
                            >
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ user.username }}
                        </td>
                        <td class="px-4 py-2">{{ user.roleLabel }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button
                                type="button"
                                class="rounded p-1.5 text-muted-foreground hover:bg-accent"
                                :aria-label="`Ubah ${user.name}`"
                                @click="editing = user"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="rounded p-1.5 text-muted-foreground hover:bg-accent"
                                :aria-label="`Reset password ${user.name}`"
                                data-test="reset-password"
                                @click="resetPassword(user)"
                            >
                                <KeyRound class="size-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Tidak ada pengguna yang cocok.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="users.links.length > 3"
            class="flex flex-wrap justify-center gap-1"
        >
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in users.links"
                :key="link.label"
                :href="link.url ?? undefined"
                preserve-scroll
                :class="[
                    'rounded-md px-3 py-1 text-sm',
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : link.url
                          ? 'hover:bg-muted'
                          : 'text-muted-foreground',
                ]"
                >{{ label(link.label) }}</component
            >
        </nav>
    </div>

    <FormDialog
        :open="editing !== null"
        title="Ubah pengguna"
        :fields="[
            { name: 'name', label: 'Nama lengkap', required: true },
            {
                name: 'role',
                label: 'Peran',
                type: 'select',
                options: roles,
                required: true,
            },
        ]"
        :initial="{
            name: editing?.name ?? '',
            role: editing?.role ?? 'student',
        }"
        :action="editing ? UserController.update.url(editing.id) : ''"
        :extra="{ _method: 'patch' }"
        submit-label="Simpan"
        @update:open="(open) => !open && (editing = null)"
    />
</template>
