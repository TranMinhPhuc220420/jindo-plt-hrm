export type PaginationMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
};

export type ApiSuccess<T> = {
    success: true;
    data: T;
    message?: string;
    meta?: PaginationMeta;
};

export type ApiErrorBody = {
    success: false;
    message: string;
    error_code?: string;
    errors?: Record<string, string[]>;
    meta?: Record<string, unknown>;
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
};

export type AuthPayload = {
    user: AuthUser;
    permissions: string[];
    employee_id: number | null;
    two_factor_required: boolean;
    locale: string;
    user_locale: string | null;
    company_locale: string;
};

export type LoginPayload =
    | AuthPayload
    | {
          two_factor_required: true;
          challenge_token: string;
      };
