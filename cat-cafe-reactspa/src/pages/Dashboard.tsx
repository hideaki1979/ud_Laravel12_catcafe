import { useAuth } from "../hooks/useAuth";

export default function Dashboard() {
    const { user, logout } = useAuth();

    return (
        <div>
            <header>
                <div>
                    <h1>🐱 La NekoCafe Dashboard</h1>
                    <button
                        onClick={logout}
                    >
                        ログアウト
                    </button>
                </div>
            </header>

            <main>
                <div>
                    <h2>ようこそ！</h2>
                    <div>
                        <p>
                            <span>名前:</span> {user?.name}
                        </p>
                        <p>
                            <span>メール:</span> {user?.email}
                        </p>
                        <p>
                            <span>SAML ID:</span> {user?.samlId}
                        </p>
                    </div>
                </div>

                <div>
                    <h3>✅ SSO動作確認</h3>
                    <ul>
                        <li>✓ Keycloak SAML認証でログイン成功</li>
                        <li>✓ Express Backendとの連携完了</li>
                        <li>✓ セッション管理動作中</li>
                    </ul>
                    <div>
                        <a
                        href="http://localhost/admin/dashboard"
                        target="_blank"
                        rel="noopener noreferrer"
                        >
                            Laravel Appを開く（SSO確認）
                        </a>
                    </div>
                </div>
            </main>
        </div>
    );
}
