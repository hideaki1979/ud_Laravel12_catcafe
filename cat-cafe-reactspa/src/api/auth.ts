import type { AuthCheckResponse, User } from "../types";
import axios from "./axios";

export const authApi = {
    // 認証状態確認
    checkAuth: async (): Promise<AuthCheckResponse> => {
        const response = await axios.get<AuthCheckResponse>('/api/auth/check');
        return response.data;
    },

    // ユーザー情報取得
    getUser: async (): Promise<User> => {
        const response = await axios.get<User>('/api/user');
        return response.data;
    },

    // ログアウト(ローカル)
    logout: async (): Promise<void> => {
        await axios.post('/api/auth/logout');
    },
};

// SAML認証開始（リダイレクト）
export const startSamlLogin = () => {
    window.location.href = 'http://localhost:3001/saml/login';
}

// SAMLログアウト（リダイレクト）
export const startSamlLogout = () => {
    window.location.href = 'http://localhost:3001/saml/logout';
}
