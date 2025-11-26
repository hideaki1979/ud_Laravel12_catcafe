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
        <div>
            <div>
                <h1>🐱 La NekoCafe</h1>
                <p>React SPA with SAML SSO</p>
            </div>
            <button
                onClick={login}
            >
                Keycloakでログイン
            </button>

            {import.meta.env.DEV && (
                <div>
                    <p>テストユーザー：</p>
                    <p>testuser / test1234</p>
                </div>
            )}
        </div>
    )
}
