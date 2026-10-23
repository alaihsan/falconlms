<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { UserMinus, UserPlus } from '@lucide/vue';
import MemberController from '@/actions/App/Http/Controllers/Courses/MemberController';
import CourseHeader from '@/components/content/CourseHeader.vue';
import { Button } from '@/components/ui/button';
import { useNewActions } from '@/composables/useNewActions';
import { useInitials } from '@/composables/useInitials';
import type { CourseHeader as Course, CreationContext } from '@/types';

type Member = { id: number; name: string; username: string | null };

const props = defineProps<{
    course: Course;
    context: CreationContext;
    teachers: Member[];
    students: Member[];
}>();

const { run } = useNewActions();
const { getInitials } = useInitials();

function remove(member: Member): void {
    if (confirm(`Keluarkan ${member.name} dari kelas?`)) {
        router.delete(
            MemberController.destroy.url({
                course: props.course.id,
                user: member.id,
            }),
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="`Anggota ${course.name}`" />

    <CourseHeader :course="course" />

    <div class="mx-auto grid w-full max-w-3xl gap-8 p-6">
        <section
            v-for="group in [
                { title: 'Guru', members: teachers },
                { title: 'Siswa', members: students },
            ]"
            :key="group.title"
            class="grid gap-2"
        >
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-lg font-medium text-primary">
                    {{ group.title }}
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground"
                        >{{ group.members.length }} orang</span
                    >
                    <Button
                        v-if="course.canManage && group.title === 'Siswa'"
                        size="sm"
                        variant="ghost"
                        @click="run('add_members')"
                    >
                        <UserPlus /> Tambah
                    </Button>
                </div>
            </div>
            <p
                v-if="group.members.length === 0"
                class="py-4 text-sm text-muted-foreground"
            >
                Belum ada.
                <template v-if="course.canManage && course.joinCode"
                    >Bagikan kode kelas
                    <span class="font-mono font-medium">{{
                        course.joinCode
                    }}</span>
                    atau tambahkan lewat username.</template
                >
            </p>
            <ul class="divide-y">
                <li
                    v-for="member in group.members"
                    :key="member.id"
                    class="flex items-center gap-3 py-2"
                    data-test="member"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-full bg-muted text-xs font-medium"
                        >{{ getInitials(member.name) }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm">{{ member.name }}</p>
                        <p
                            v-if="member.username"
                            class="text-xs text-muted-foreground"
                        >
                            {{ member.username }}
                        </p>
                    </div>
                    <button
                        v-if="
                            course.canManage &&
                            !(group.title === 'Guru' && teachers.length <= 1)
                        "
                        type="button"
                        class="rounded p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        :aria-label="`Keluarkan ${member.name}`"
                        @click="remove(member)"
                    >
                        <UserMinus class="size-4" />
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
