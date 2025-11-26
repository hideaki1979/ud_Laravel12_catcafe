import { useEffect, useState, type ReactNode } from "react";
import type { AuthContextType, User } from "../types";
import { authApi } from "../api/auth";
import { AuthContext } from "./AuthContext";

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    // 認証状態確認
    const checkAuth = async () => {
        try {
            const result = await authApi.checkAuth();
            if (result.authenticated && result.user) {
                setUser(result.user);
            } else {
                setUser(null);
            }
        } catch (error) {
            console.error('認証状態の確認に失敗:', error);
            setUser(null);
        } finally {
            setLoading(false);
        }
    };

    // 初回マウント時に認証状態を確認
    useEffect(() => {
        checkAuth();
    }, []);

    const login = () => {
        // SAML認証開始（Express Backendにリダイレクト）
        window.location.href = `${import.meta.env.VITE_API_BASE_URL}/saml/login`;
    }

    const logout = () => {
        // SAMLログアウト（Express Backendにリダイレクト）
        window.location.href = `${import.meta.env.VITE_API_BASE_URL}/saml/logout`;
    }

    const value: AuthContextType = {
        user,
        loading,
        isAuthenticated: user !== null,
        login,
        logout,
        checkAuth,
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
};
