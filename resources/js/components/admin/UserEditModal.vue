<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FalconIcon from '@/components/ui/FalconIcon.vue';
import type { RoleOption, UserItem } from '@/types';

const props = defineProps<{
    open: boolean;
    user: UserItem | null;
    roles: RoleOption[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    name: '',
    email: '',
    username: '',
    role: 'siswa',
    password: '',
    must_change_password: false,
});

watch(
    () => props.user,
    (target) => {
        if (target) {
            form.reset();
            form.clearErrors();
            form.name = target.name;
            form.email = target.email;
            form.username = target.username || '';
            form.role = target.role;
            form.password = '';
            form.must_change_password = target.must_change_password;
        }
    },
    { immediate: true },
);

function submit(): void {
    if (!props.user) return;
    form.put(`/admin/users/${props.user.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
}
</script>

<template>
    <div
        v-if="open && user"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div
            class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3"
            >
                <h3 class="text-base font-bold text-slate-900">
                    Ubah Data Pengguna
                </h3>
                <button
                    type="button"
                    class="p-1 text-slate-400 hover:text-slate-600"
                    @click="emit('close')"
                >
                    <FalconIcon name="x" className="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Nama Lengkap *</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Email *</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Username (Opsional)</label
                    >
                    <input
                        v-model="form.username"
                        type="text"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.username"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.username }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Peran *</label
                    >
                    <select
                        v-model="form.role"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                    >
                        <option
                            v-for="r in roles"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.role"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.role }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700"
                        >Kata Sandi Baru (Kosongkan bila tidak diubah)</label
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Biarkan kosong jika tidak ingin mengubah sandi"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 focus:outline-hidden"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="pt-1">
                    <label
                        class="flex cursor-pointer items-center gap-2 select-none"
                    >
                        <input
                            v-model="form.must_change_password"
                            type="checkbox"
                            class="h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-xs font-medium text-slate-700"
                            >Wajibkan ganti password saat login berikutnya</span
                        >
                    </label>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                    >
                        {{
                            form.processing
                                ? 'Menyimpan...'
                                : 'Perbarui Pengguna'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
