import type { User } from "./user";

export interface AuthCheckResponse {
    authenticated: boolean;
    user?: User;
}

export interface AuthContextType {
    user: User | null;
    loading: boolean;
    isAuthenticated: boolean;
    login: () => void;
    logout: () => Promise<void>;
    checkAuth: () => Promise<void>;
}
