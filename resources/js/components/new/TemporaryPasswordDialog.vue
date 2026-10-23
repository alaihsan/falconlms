<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type TemporaryPassword = { username: string; name: string; password: string };

const account = ref<TemporaryPassword | null>(null);

// The server flashes a new temporary password exactly once; it is not stored anywhere.
const stop = router.on('flash', (event) => {
    const flash = (event as CustomEvent).detail?.flash;

    if (flash?.temporaryPassword) {
        account.value = flash.temporaryPassword as TemporaryPassword;
    }
});

onBeforeUnmount(stop);
</script>

<template>
    <Dialog
        :open="account !== null"
        @update:open="(open) => !open && (account = null)"
    >
        <DialogContent class="sm:max-w-sm" data-test="temporary-password">
            <DialogHeader>
                <DialogTitle>Password sementara</DialogTitle>
                <DialogDescription>
                    Berikan kepada {{ account?.name }}. Password ini hanya
                    tampil sekali dan wajib diganti saat login.
                </DialogDescription>
            </DialogHeader>
            <dl class="grid gap-3 rounded-lg bg-muted p-4 text-sm">
                <div>
                    <dt class="text-muted-foreground">Username</dt>
                    <dd class="font-mono text-base">{{ account?.username }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Password sementara</dt>
                    <dd class="font-mono text-2xl tracking-wider">
                        {{ account?.password }}
                    </dd>
                </div>
            </dl>
            <DialogFooter>
                <Button @click="account = null">Selesai</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
