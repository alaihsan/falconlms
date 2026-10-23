export type SpaceKind = 'personal_library' | 'school_library' | 'course';

export type NewMenuItem =
    | 'folder'
    | 'upload'
    | 'upload_folder'
    | 'material'
    | 'assignment'
    | 'link'
    | 'topic'
    | 'from_library'
    | 'announcement'
    | 'add_members'
    | 'course'
    | 'join_course'
    | 'user'
    | 'import_users';

export type SpaceRef = { id: number; kind: SpaceKind; name: string };

/**
 * Sent by every page (see App\Support\CreationContext) so the "+ Baru"
 * button knows where new content is saved.
 */
export type CreationContext = {
    space: SpaceRef | null;
    folder: { id: number; name: string } | null;
    topic: { id: number; name: string } | null;
    allowed: NewMenuItem[];
    canAlsoSaveToLibrary: boolean;
};

/** A place new content is saved to: a library folder or a class topic. */
export type Destination = {
    space: SpaceRef;
    folder: { id: number; name: string } | null;
    topic: { id: number; name: string } | null;
};

/** Places the user may save to, for the location picker. */
export type DestinationOptions = {
    libraries: {
        space: SpaceRef;
        folders: { id: number; name: string; label: string; depth: number }[];
    }[];
    courses: { space: SpaceRef; topics: { id: number; name: string }[] }[];
};

export type ItemType = 'material' | 'assignment' | 'file' | 'link';

export type AttachmentPreview = 'image' | 'video' | 'audio' | 'pdf' | null;

export type Attachment = {
    id: number;
    kind: 'file' | 'link';
    name: string;
    size: number | null;
    preview: AttachmentPreview;
    url: string;
};

export type ItemSummary = {
    id: number;
    type: ItemType;
    title: string;
    topicId: number | null;
    folderId: number | null;
    isPublished: boolean;
    publishedAt: string | null;
    createdAt: string | null;
    dueAt: string | null;
    url: string | null;
    file: Attachment | null;
};

export type FolderSummary = { id: number; name: string };

export type Topic = { id: number; name: string };

export type CourseSummary = {
    id: number;
    name: string;
    section: string | null;
    subject: string | null;
};

export type CourseHeader = CourseSummary & {
    joinCode: string | null;
    canManage: boolean;
};

export type SubmissionStatus = 'assigned' | 'draft' | 'turned_in' | 'returned';
