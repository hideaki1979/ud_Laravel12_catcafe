/**
 * ユーザー情報の型定義
 */
export interface User {
    id: string;
    email: string;
    name: string;
    samlId: string;
    attributes: Record<string, unknown>;
    [key: string]: unknown; // Passportのシリアライズ用インデックスシグネチャ
}

/**
 * SAMLプロファイルの型定義
 */
export interface SamlProfile {
    id?: string;
    email?: string;
    name?: string;
    nameID?: string;
    [key: string]: unknown;
}
