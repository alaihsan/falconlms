export type Role = 'admin' | 'teacher' | 'student' | 'guardian';

export type User = {
    id: number;
    name: string;
    username: string;
    role: Role;
    role_label: string;
};

export type Auth = {
    user: User | null;
};
