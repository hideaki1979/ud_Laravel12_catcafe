import { useCallback, useEffect, useMemo, useState, type ReactNode } from "react";
import type { AuthContextType, User } from "../types";
import { authApi } from "../api/auth";
import { AuthContext } from "./AuthContext";

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001';


    // 認証状態確認（useCallbackでメモ化）
    const checkAuth = useCallback(async () => {
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
    }, []);

    // 初回マウント時に認証状態を確認
    useEffect(() => {
        checkAuth();
    }, [checkAuth]);

    // SAML認証開始（useCallbackでメモ化）
    const login = useCallback(() => {
        // SAML認証開始（Express Backendにリダイレクト）
        window.location.href = `${baseUrl}/saml/login`;
    }, [baseUrl]);

    // ログアウト（useCallbackでメモ化）
    const logout = useCallback(async () => {
        try {
            await authApi.logout();
        } catch (error) {
            console.error('バックエンドログアウトに失敗:', error);
            // エラーが発生してもSAMLログアウトは続行
        }
        // SAMLログアウト（Express Backendにリダイレクト）
        window.location.href = `${baseUrl}/saml/logout`;
    }, [baseUrl]);

    const value: AuthContextType = useMemo(() => ({
        user,
        loading,
        isAuthenticated: user !== null,
        login,
        logout,
        checkAuth,
    }), [user, loading, login, logout, checkAuth]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
};
