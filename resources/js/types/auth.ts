export interface User {
    id: number;
    name: string;
    url_handle: string | null;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    last_login_at: string | null;
    roles?: Role[];
    permissions?: Permission[];
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions?: Permission[];
    permissions_count?: number;
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Auth {
    user: User | null;
    roles: Role[];
    permissions: Pick<Permission, 'name'>[];
}
