import { useAuth } from "../hooks/useAuth";

export default function Dashboard() {
    const { user, logout } = useAuth();

    return (
        <div className="min-h-screen bg-gray-100">
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 className="text-gray-900 text-2xl font-bold">
                        🐱 La NekoCafe Dashboard
                    </h1>
                    <button
                        onClick={logout}
                        className="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300"
                    >
                        ログアウト
                    </button>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div className="bg-white rounded-lg shadow p-6 m-6">
                    <h2 className="text-2xl font-semibold mb-4">ようこそ！</h2>
                    <div className="space-y-2">
                        <p className="text-gray-700">
                            <span className="font-semibold">名前:</span> {user?.name}
                        </p>
                        <p className="text-gray-700">
                            <span className="font-semibold">メール:</span> {user?.email}
                        </p>
                        <p className="text-gray-700">
                            <span className="font-semibold">SAML ID:</span> {user?.samlId}
                        </p>
                    </div>
                </div>

                <div className="bg-indigo-100 rounded-lg p-6">
                    <h3 className="text-xl font-semibold mb-4 text-indigo-800">✅ SSO動作確認</h3>
                    <ul className="space-y-2 text-gray-700">
                        <li>✓ Keycloak SAML認証でログイン成功</li>
                        <li>✓ Express Backendとの連携完了</li>
                        <li>✓ セッション管理動作中</li>
                    </ul>
                    <div className="mt-4">
                        <a
                            href={import.meta.env.VITE_LARAVEL_APP_URL || "http://localhost/admin/dashboard"}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-block bg-indigo-800 text-white py-3 px-4 rounded-lg hover:bg-indigo-600 transition duration-300"
                        >
                            Laravel Appを開く（SSO確認）
                        </a>
                    </div>
                </div>
            </main>
        </div>
    );
}
