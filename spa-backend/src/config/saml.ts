/**
 * SAML 2.0 設定ファイル（Keycloak IdP用）
 *
 * この設定はKeycloakをIdPとして使用するためのSAML設定です。
 */

import type { SamlConfig } from "passport-saml";

const KEYCLOAK_BASE_URL = process.env.SAML2_KEYCLOAK_BASE_URL || 'http://keycloak:8080';
const KEYCLOAK_REALM = process.env.SAML2_KEYCLOAK_REALM || 'lanekocafe';
const SP_BASE_URL = process.env.SP_BASE_URL || 'http://localhost:3001';

export const samlConfig: SamlConfig = {
    // Service Provider (SP) の設定 - このNode.jsアプリケーション
    callbackUrl: `${SP_BASE_URL}/saml/acs`,
    entryPoint: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
    issuer: `${SP_BASE_URL}/saml/metadata`,

    // IdP（Keycloak）の証明書
    // Keycloak管理画面から取得した証明書を設定してください
    // Realm Settings > Keys > RS256 の Certificate
    // 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
    // cert は型定義上必須ですが、wantAssertionsSigned: false の場合は署名検証を行わないため、
    // 環境変数がない場合はダミー値を設定（実際には使用されません）
    cert: process.env.SAML2_KEYCLOAK_IDP_x509 || 'dummy-cert',

    // SAML設定
    identifierFormat: 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

    // セキュリティ設定（学習用のため簡略化）
    // 公式ドキュメント推奨: 'sha256' または 'sha512'（'sha1'は非推奨）
    signatureAlgorithm: 'sha256',
    wantAssertionsSigned: false,

    // Single Logout (SLO) 設定
    // logoutUrl: IdPのログアウトエンドポイント
    logoutUrl: `${KEYCLOAK_BASE_URL}/realms/${KEYCLOAK_REALM}/protocol/saml`,
    // logoutCallbackUrl: IdPからのログアウト応答を受け取るエンドポイント（SLS）
    logoutCallbackUrl: `${SP_BASE_URL}/saml/sls`,

    // 属性マッピング
    disableRequestedAuthnContext: true,
};
