import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001';

const axiosInstance = axios.create({
    baseURL: API_BASE_URL,
    withCredentials: true,  // セッションCookieを含める（重要）
    headers: {
        'Content-Type': 'application/json',
    },
});

// リクエストインターセプター（必要に応じて）
axiosInstance.interceptors.request.use(
    (config) => {
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// レスポンスインターセプター（エラーハンドリング）
axiosInstance.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response?.status === 401) {
            // 未認証エラーの場合
            console.error('認証エラー: ログインが必要です');
            // ログインページにリダイレクトして再認証を促す
            window.location.href = "/login";
        }
        return Promise.reject(error);
    }
);

export default axiosInstance;
