export type SpaceKind = 'personal_library' | 'school_library' | 'course';

export type NewMenuItem =
    | 'folder'
    | 'upload'
    | 'upload_folder'
    | 'material'
    | 'assignment'
    | 'quiz'
    | 'link';

/**
 * Sent by every content page (see App\Support\CreationContext) so the
 * "+ Baru" button knows where new content is saved.
 */
export type CreationContext = {
    space: { kind: SpaceKind; name: string };
    folder: { id: number; name: string } | null;
    topic: { id: number; name: string } | null;
    allowed: NewMenuItem[];
};
