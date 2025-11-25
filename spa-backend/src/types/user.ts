/**
 * ユーザー情報の型定義
 */
export interface User {
    id: string;
    email: string;
    name: string;
    samlId: string;
    attributes: SamlAttributes;
}

/**
 * SAMLプロファイルの型定義
 */
export interface SamlAttributes {
    issuer?: string;
    sessionIndex?: string;
    nameID?: string;
    nameIDFormat?: string;
    [key: string]: unknown;
}


/**
 * SAMLプロファイルの型定義（IdPから受け取る生データ）
 */
export interface SamlProfile {
    id?: string;
    email?: string;
    name?: string;
    nameID?: string;
    issuer?: string;
    sessionIndex?: string;
    nameIDFormat?: string;
    'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'?: string;
    'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'?: string;
    [key: string]: unknown;
}

/**
 * Passportセッションに保存するユーザー情報
 * シリアライズ/デシリアライズ用の型
 */
export interface SerializeUser {
    id: string;
    email: string;
    name: string;
    samlId: string;
}
