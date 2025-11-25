import { Link } from "react-router-dom";

export default function NotFound() {
    return (
        <div>
            <div>
                <h1>404</h1>
                <p>ページが見つかりません</p>
                <Link to="/">
                    ホームに戻る
                </Link>
            </div>
        </div>
    );
}
