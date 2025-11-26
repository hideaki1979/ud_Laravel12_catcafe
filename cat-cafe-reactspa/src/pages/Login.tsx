import { useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import { useEffect } from "react";

export default function Login() {
    const { isAuthenticated, login, loading } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        if (isAuthenticated) {
            navigate('/dashboard')
        }
    }, [isAuthenticated, navigate]);

    if (loading) {
        return (
            <div>
                <div>読み込み中...</div>
            </div>
        );
    }

    return (
        <div className="flex items-center justify-center min-h-screen bg-linear-to-br from-blue-100 to-indigo-200">
            <div className="bg-white p-8 rounded-lg shadow-lg max-w-lg w-full">
                <h1 className="text-gray-800 text-2xl font-bold text-center mb-6">
                    🐱 La NekoCafe
                </h1>
                <p className="text-gray-600 text-center mb-8">React SPA with SAML SSO</p>
                <button
                    onClick={login}
                    className="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-800 transition duration-200 font-semibold"
                >
                    Keycloakでログイン
                </button>

                {import.meta.env.DEV && (
                    <div className="mt-6 text-gray-500 text-sm text-center">
                        <p>テストユーザー：</p>
                        <p>testuser / password</p>
                    </div>
                )}
            </div>
        </div>
    )
}
