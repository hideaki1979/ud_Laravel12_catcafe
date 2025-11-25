/**
 * Express拡張型定義
 */
import { User as AppUser } from "./user";

declare global {
    namespace Express {
        interface User extends AppUser {}
    }
}

export {};
