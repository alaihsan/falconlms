export type UserRole = 'admin' | 'guru' | 'siswa';

export type User = {
    id: number;
    name: string;
    email: string;
    username?: string | null;
    role: UserRole;
    role_label: string;
    must_change_password: boolean;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

export type SharedProps = {
    name: string;
    auth: Auth;
    flash: FlashMessages;
    [key: string]: unknown;
};
