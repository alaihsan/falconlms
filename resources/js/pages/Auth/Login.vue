<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import FalconIcon from '@/components/ui/FalconIcon.vue';

const showPassword = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
}

function fillDemo(loginVal: string, passVal: string): void {
    form.login = loginVal;
    form.password = passVal;
}
</script>

<template>
    <div>
        <Head title="Masuk - Falcon LMS" />

        <GuestLayout>
            <div class="mb-6">
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Masuk ke Akun Anda
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Gunakan email atau username yang diberikan oleh sekolah.
                </p>
            </div>

            <!-- Login Form -->
            <form class="space-y-4" @submit.prevent="submit">
                <!-- Login Identifier (Email or Username) -->
                <div>
                    <label
                        for="login"
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Email atau Username
                    </label>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                        >
                            <FalconIcon name="user" className="w-4 h-4" />
                        </div>
                        <input
                            id="login"
                            v-model="form.login"
                            type="text"
                            required
                            autofocus
                            placeholder="misal: budi@falcon.sch.id atau budi01"
                            class="w-full rounded-xl border bg-white py-2 pr-3 pl-9 text-sm transition focus:ring-3 focus:ring-indigo-100 focus:outline-hidden"
                            :class="
                                form.errors.login
                                    ? 'border-rose-400 focus:border-rose-500'
                                    : 'border-slate-300 focus:border-indigo-600'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.login"
                        class="mt-1 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.login }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label
                        for="password"
                        class="mb-1 block text-xs font-semibold text-slate-700"
                    >
                        Kata Sandi
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
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-xl border bg-white py-2 pr-10 pl-9 text-sm transition focus:ring-3 focus:ring-indigo-100 focus:outline-hidden"
                            :class="
                                form.errors.password
                                    ? 'border-rose-400 focus:border-rose-500'
                                    : 'border-slate-300 focus:border-indigo-600'
                            "
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-400 hover:text-slate-600"
                            @click="showPassword = !showPassword"
                        >
                            {{ showPassword ? 'Sembunyikan' : 'Lihat' }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label
                        class="flex cursor-pointer items-center gap-2 select-none"
                    >
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-xs text-slate-600"
                            >Ingat saya di perangkat ini</span
                        >
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/30 transition hover:bg-indigo-700 active:scale-98 disabled:opacity-60"
                >
                    <span
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    ></span>
                    <span>{{
                        form.processing ? 'Memproses...' : 'Masuk ke LMS'
                    }}</span>
                </button>
            </form>

            <!-- Quick Demo Credentials for Intranet Testing -->
            <div class="mt-6 border-t border-slate-100 pt-5">
                <p
                    class="mb-2 text-center text-[11px] font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Akses Cepat Pengujian Lokal
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-slate-100 p-2 text-center text-[11px] font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                        @click="fillDemo('admin@falcon.sch.id', 'admin123')"
                    >
                        Admin
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-slate-100 p-2 text-center text-[11px] font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                        @click="fillDemo('guru@falcon.sch.id', 'guru123')"
                    >
                        Guru
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-slate-100 p-2 text-center text-[11px] font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700"
                        @click="fillDemo('siswa@falcon.sch.id', 'siswa123')"
                    >
                        Siswa
                    </button>
                </div>
            </div>
        </GuestLayout>
    </div>
</template>
