/**
 * Express拡張型定義
 * Passportのセッション管理用にExpress.Userを拡張
 * アプリケーションのUser型を直接使用することで型安全性を確保
 */
import { User as AppUser } from "./user";

declare global {
    namespace Express {
        // Express.User をアプリケーションのUser型で上書き
        // これによりreq.userが正しい型を持つ
        interface User extends AppUser {}
    }
}

export { };
