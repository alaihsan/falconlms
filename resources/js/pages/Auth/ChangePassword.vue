<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/password/change', {
        onFinish: () => {
            form.reset('current_password', 'password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <div>
        <Head title="Wajib Ganti Kata Sandi - Falcon LMS" />

        <GuestLayout>
            <div class="mb-5">
                <div
                    class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                >
                    <FalconIcon
                        name="alert"
                        className="w-3.5 h-3.5 text-amber-600"
                    />
                    <span>Langkah Keamanan Awal</span>
                </div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Perbarui Kata Sandi Anda
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Demi keamanan akun dan privasi nilai Anda di Falcon LMS,
                    Anda diwajibkan mengubah kata sandi awal sebelum dapat
                    mengakses kelas.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Current Password -->
                <div>
                    <label
                        for="current_password"
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Kata Sandi Saat Ini (Awal)
                    </label>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="key" className="w-4 h-4" />
                        </div>
                        <input
                            id="current_password"
                            v-model="form.current_password"
                            type="password"
                            required
                            placeholder="Kata sandi saat ini"
                            class="w-full rounded-xl border bg-white py-2 pr-3 pl-9 text-sm transition focus:ring-3 focus:ring-indigo-100 focus:outline-hidden"
                            :class="
                                form.errors.current_password
                                    ? 'border-rose-400 focus:border-rose-500'
                                    : 'border-slate-300 focus:border-indigo-600'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.current_password"
                        class="mt-1 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.current_password }}
                    </p>
                </div>

                <!-- New Password -->
                <div>
                    <label
                        for="password"
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Kata Sandi Baru (Minimal 8 Karakter)
                    </label>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="key" className="w-4 h-4" />
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            placeholder="Minimal 8 karakter unik"
                            class="w-full rounded-xl border bg-white py-2 pr-3 pl-9 text-sm transition focus:ring-3 focus:ring-indigo-100 focus:outline-hidden"
                            :class="
                                form.errors.password
                                    ? 'border-rose-400 focus:border-rose-500'
                                    : 'border-slate-300 focus:border-indigo-600'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label
                        for="password_confirmation"
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Ulangi Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="key" className="w-4 h-4" />
                        </div>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            placeholder="Ketik ulang kata sandi baru"
                            class="w-full rounded-xl border bg-white py-2 pr-3 pl-9 text-sm transition focus:ring-3 focus:ring-indigo-100 focus:outline-hidden"
                            :class="
                                form.errors.password_confirmation
                                    ? 'border-rose-400 focus:border-rose-500'
                                    : 'border-slate-300 focus:border-indigo-600'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.password_confirmation"
                        class="mt-1 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/30 transition hover:bg-indigo-700 active:scale-98 disabled:opacity-60"
                >
                    <span
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></span>
                    <span>{{
                        form.processing
                            ? 'Menyimpan...'
                            : 'Simpan & Masuk ke Beranda'
                    }}</span>
                </button>
            </form>
        </GuestLayout>
    </div>
</template>
