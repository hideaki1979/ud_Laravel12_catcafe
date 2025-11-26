/**
 * SAML 2.0 設定ファイル（Keycloak IdP用）
 *
 * この設定はKeycloakをIdPとして使用するためのSAML設定です。
 *
 * @node-saml/passport-saml v5.x 対応
 * 公式GitHub: https://github.com/node-saml/passport-saml
 * 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
 */

import type { SamlConfig } from "@node-saml/passport-saml";

const KEYCLOAK_BASE_URL = process.env.KEYCLOAK_BASE_URL || 'http://localhost:8080';
const KEYCLOAK_REALM = process.env.KEYCLOAK_REALM || 'lanekocafe';
const SP_BASE_URL = process.env.SP_BASE_URL || 'http://localhost:3001';

/**
 * IdP証明書をPEM形式に変換
 * Keycloakから取得した証明書はbase64形式なので、PEM形式に変換する必要がある
 * Laravel側は onelogin/php-saml が内部で変換を行うが、
 * Node.js側の @node-saml/passport-saml はPEM形式を要求する
 */
const getIdpCert = (): string => {
    const cert = process.env.SAML_IDP_CERT;
    if (!cert || cert === '') {
        // 証明書が設定されていない場合はエラーを防ぐためダミー証明書を返す
        // wantAssertionsSigned: false の場合は検証されない
        console.warn('Warning: SAML_IDP_CERT is not set. Using dummy certificate.');
        return 'MIIBkTCB+wIJAKHBfpegPjMCMA0GCSqGSIb3DQEBCwUAMBExDzANBgNVBAMMBmR1bW15MTAeFw0yMDAxMDEwMDAwMDBaFw0zMDAxMDEwMDAwMDBaMBExDzANBgNVBAMMBmR1bW15MTBcMA0GCSqGSIb3DQEBAQUAA0sAMEgCQQC6C8r7VhZ3kYQlPJqC0vXmXgNeXXjX7RQKP4kzX4k6K5Y5u5v5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5Q5AgMBAAEwDQYJKoZIhvcNAQELBQADQQA7';
    }

    // 既にPEM形式の場合はそのまま返す
    if (cert.includes('-----BEGIN CERTIFICATE-----')) {
        return cert;
    }

    // base64形式の証明書をPEM形式に変換
    return `-----BEGIN CERTIFICATE-----\n${cert}\n-----END CERTIFICATE-----`;
}

export const samlConfig: SamlConfig = {
    // Service Provider (SP) の設定 - このNode.jsアプリケーション
    callbackUrl: `${SP_BASE_URL}/saml/acs`,
    entryPoint: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
    issuer: `${SP_BASE_URL}/saml/metadata`,

    // IdP（Keycloak）の証明書
    // @node-saml/passport-saml v5.x では "cert" から "idpCert" に変更
    // Keycloak管理画面から取得した証明書を設定してください
    // Realm Settings > Keys > RS256 の Certificate
    // 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
    // idpCert は型定義上必須ですが、wantAssertionsSigned: false の場合は署名検証を行わないため、
    // 環境変数がない場合はダミー値を設定（実際には使用されません）
    idpCert: getIdpCert(),

    // SAML設定
    identifierFormat: 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

    // セキュリティ設定（学習用のため簡略化）
    // 公式ドキュメント推奨: 'sha256' または 'sha512'（'sha1'は非推奨）
    signatureAlgorithm: 'sha256',
    wantAssertionsSigned: false,
    wantAuthnResponseSigned: false,  // SAML Response全体の署名検証をスキップ

    // Single Logout (SLO) 設定
    // logoutUrl: IdPのログアウトエンドポイント
    logoutUrl: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
    // logoutCallbackUrl: IdPからのログアウト応答を受け取るエンドポイント（SLS）
    logoutCallbackUrl: `${SP_BASE_URL}/saml/sls`,

    // 属性マッピング
    disableRequestedAuthnContext: true,
};
