export type LibraryView = 'mine' | 'shared' | 'all' | 'class';

export type PollView = {
    options: { id: number; label: string; votes: number | null }[];
    total_votes: number | null;
    my_option_id: number | null;
    can_vote: boolean;
};

export type LearningItemView = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    topic: string | null;
    content: string | null;
    url: string | null;
    h5p_player_url: string | null;
    poll: PollView | null;
    folder_id: number | null;
    attachment_name: string | null;
    attachment_size: string | null;
    preview_kind: 'pdf' | 'image' | 'video' | 'audio' | 'office' | null;
    class_name: string;
    class_ids: number[];
    teacher_ids: number[];
    shared_teacher_names: string[];
    author_name: string;
    due_at: string | null;
    max_score: number;
    is_graded: boolean;
    is_owner: boolean;
    can_manage: boolean;
    can_copy: boolean;
    created_at: string;
};

export type FolderView = {
    id: number;
    name: string;
};

export type LibraryOption = {
    id: number;
    label: string;
};

export type TeacherOption = {
    id: number;
    name: string;
};

export type UploadRules = {
    extensions: string[];
    max_megabytes: number;
};

export type GradebookStudent = {
    id: number;
    name: string;
    identifier: string;
};

export type GradebookItem = {
    id: number;
    title: string;
    type_label: string;
    max_score: number;
};

export type GradebookScoreEntry = {
    learning_item_id: number;
    siswa_id: number;
    score: number | null;
};
