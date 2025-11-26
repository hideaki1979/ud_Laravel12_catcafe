import { useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import { useEffect } from "react";

export default function Callback () {
    const {checkAuth} = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const handleCallback = async () => {
            // 認証状態を再確認
            await checkAuth();
            // ダッシュボードにリダイレクト
            navigate('/dashboard');
        };

        handleCallback();
    }, [checkAuth, navigate]);

    return (
        <div>
            <div>
                <div>認証処理中...</div>
                <div></div>
            </div>
        </div>
    );
}
