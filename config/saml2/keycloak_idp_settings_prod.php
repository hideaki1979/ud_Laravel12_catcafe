<?php

/**
 * Keycloak IdP設定ファイル（本番環境用）
 *
 * このファイルはKeycloakをIdP（Identity Provider）として使用するための本番環境用設定です。
 *
 * 開発環境用設定との主な違い：
 * - すべての署名・検証機能が有効化
 * - HTTPS必須
 * - SP証明書・秘密鍵が必須
 * - strict モードが強制的に有効
 * - プロキシ対応（ロードバランサー使用時）
 *
 * セキュリティ要件：
 * - 本番環境では必ず署名付きメッセージを使用
 * - IdPからのAssertionの署名検証を必須化
 * - SP証明書と秘密鍵の安全な管理
 */
$this_idp_env_id = 'KEYCLOAK';

// KeycloakのベースURL（本番環境：HTTPS必須）
$keycloak_base_url = env('SAML2_' . $this_idp_env_id . '_BASE_URL', 'https://auth.example.com');
// Keycloakのレルム名
$keycloak_realm = env('SAML2_' . $this_idp_env_id . '_REALM', 'lanekocafe');

return $settings = array(

    /*****
     * One Login Settings
     */

    // 厳密モード: 本番環境では必ず true（セキュリティ検証を厳格に実施）
    'strict' => true,

    // デバッグモード: 本番環境では必ず false
    'debug' => false,

    // Service Provider (SP) の設定 = このLaravelアプリケーション
    'sp' => array(

        // NameID フォーマット（persistent: 永続的なユーザー識別子）
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

        // SP の証明書と秘密鍵（本番環境では必須）
        // openssl req -x509 -newkey rsa:4096 -keyout sp.key -out sp.crt -days 3650 -nodes
        'x509cert' => env('SAML2_' . $this_idp_env_id . '_SP_x509', ''),
        'privateKey' => env('SAML2_' . $this_idp_env_id . '_SP_PRIVATEKEY', ''),

        // SP の Entity ID（本番環境のURL）
        'entityId' => env('SAML2_' . $this_idp_env_id . '_SP_ENTITYID', env('APP_URL') . '/saml2/keycloak/metadata'),

        // Assertion Consumer Service (ACS) - IdPからのレスポンスを受け取るエンドポイント
        'assertionConsumerService' => array(
            // URL Location where the <Response> from the IdP will be returned,
            // using HTTP-POST binding.
            'url' => env(
                'SAML2_' . $this_idp_env_id . '_SP_ACS_URL',
                env('APP_URL') . '/saml2/keycloak/acs'
            ),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        // Single Logout Service (SLS) - ログアウト時のエンドポイント
        'singleLogoutService' => array(
            // URL Location where the <Response> from the IdP will be returned,
            // using HTTP-Redirect binding.
            'url' => env(
                'SAML2_' . $this_idp_env_id . '_SP_SLS_URL',
                env('APP_URL') . '/saml2/keycloak/sls'
            ),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
    ),

    // Identity Provider (IdP) の設定 = Keycloak
    'idp' => array(
        // IdP の Entity ID
        'entityId' => env('SAML2_' . $this_idp_env_id . '_IDP_ENTITYID', $keycloak_base_url . '/realms/' . $keycloak_realm),
        // Single Sign-On (SSO) エンドポイント
        'singleSignOnService' => array(
            // URL Target of the IdP where the SP will send the Authentication Request Message,
            // using HTTP-POST binding.
            'url' => env('SAML2_' . $this_idp_env_id . '_IDP_SSO_URL', $keycloak_base_url . '/realms/' . $keycloak_realm . '/protocol/saml'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        // Single Logout Service (SLO) エンドポイント
        'singleLogoutService' => array(
            // URL Location of the IdP where the SP will send the SLO Request,
            // using HTTP-Redirect binding.
            'url' => env('SAML2_' . $this_idp_env_id . '_IDP_SL_URL', $keycloak_base_url . '/realms/' . $keycloak_realm . '/protocol/saml'),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ),
        // IdP の公開鍵証明書（必須）
        // Keycloakの管理画面から取得: Realm Settings > Keys > RS256 の Certificate をコピー
        // 改行なし、BEGIN/ENDヘッダーなしの本文のみを.envに設定
        'x509cert' => env('SAML2_' . $this_idp_env_id . '_IDP_x509', ''),

        // 複数の証明書をサポート（証明書ローテーション時）
        'x509certMulti' => [
            'signing' => [
                0 => env('SAML2_' . $this_idp_env_id . '_IDP_x509', ''),
                // ローテーション時の新しい証明書を追加
                // 1 => env('SAML2_' . $this_idp_env_id . '_IDP_x509_NEW', ''),
            ],
            'encryption' => [
                0 => env('SAML2_' . $this_idp_env_id . '_IDP_x509', ''),
            ],
        ],
    ),



    /***
     * OneLogin 詳細設定（本番環境用セキュリティ設定）
     */
    'security' => array(

        /** SPが送信する際の署名・暗号化設定 */

        // NameID を暗号化するか（本番環境推奨: true）
        'nameIdEncrypted' => env('SAML2_' . $this_idp_env_id . '_NAMEID_ENCRYPTED', false),

        // AuthnRequest（認証リクエスト）に署名するか（本番環境必須: true）
        // 中間者攻撃を防ぐため、本番環境では必ず有効化
        'authnRequestsSigned' => env('SAML2_' . $this_idp_env_id . '_AUTHN_REQUESTS_SIGNED', true),

        // LogoutRequest に署名するか（本番環境推奨: true）
        // 不正なログアウトを防ぐため、本番環境では有効化推奨
        'logoutRequestSigned' => env('SAML2_' . $this_idp_env_id . '_LOGOUT_REQUEST_SIGNED', true),

        // LogoutResponse に署名するか（本番環境推奨: true）
        'logoutResponseSigned' => env('SAML2_' . $this_idp_env_id . '_LOGOUT_RESPONSE_SIGNED', true),

        // メタデータに署名するか（本番環境推奨: true）
        // メタデータの改ざんを防ぐため、本番環境では有効化推奨
        'signMetadata' => env('SAML2_' . $this_idp_env_id . '_SIGN_METADATA', true),

        /** IdPから受信する際の要件（本番環境では厳格に検証） */

        // Response、LogoutRequest、LogoutResponse に署名を要求するか（本番環境必須: true）
        // メッセージの改ざんを防ぐため必須
        'wantMessagesSigned' => env('SAML2_' . $this_idp_env_id . '_WANT_MESSAGES_SIGNED', true),

        // Assertion（認証情報）に署名を要求するか（本番環境必須: true）
        // なりすまし攻撃を防ぐため、本番環境では必ず有効化
        'wantAssertionsSigned' => env('SAML2_' . $this_idp_env_id . '_WANT_ASSERTIONS_SIGNED', true),

        // Assertion の暗号化を要求するか（本番環境推奨: true）
        // ネットワーク上での盗聴を防ぐため推奨（HTTPSと併用）
        'wantAssertionsEncrypted' => env('SAML2_' . $this_idp_env_id . '_WANT_ASSERTIONS_ENCRYPTED', false),

        // NameID の暗号化を要求するか（オプション）
        'wantNameIdEncrypted' => env('SAML2_' . $this_idp_env_id . '_WANT_NAMEID_ENCRYPTED', false),

        // 認証コンテキスト
        // true: パスワード保護されたトランスポートを要求
        // false: 認証コンテキストを送信しない
        // 本番環境では true 推奨
        'requestedAuthnContext' => env('SAML2_' . $this_idp_env_id . '_REQUESTED_AUTHN_CONTEXT', true),

        // 署名アルゴリズム（SHA256推奨、SHA1は非推奨）
        'signatureAlgorithm' => env(
            'SAML2_' . $this_idp_env_id . '_SIGNATURE_ALGORITHM',
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
        ),

        // ダイジェストアルゴリズム（SHA256推奨）
        'digestAlgorithm' => env(
            'SAML2_' . $this_idp_env_id . '_DIGEST_ALGORITHM',
            'http://www.w3.org/2001/04/xmlenc#sha256'
        ),

        // 署名の検証を厳密に行う
        'rejectUnsolicitedResponsesWithInResponseTo' => true,
    ),

    // 連絡先情報（本番環境の実際の連絡先）
    'contactPerson' => array(
        'technical' => array(
            'givenName' => env('SAML2_CONTACT_TECHNICAL_NAME', 'Technical Support'),
            'emailAddress' => env('SAML2_CONTACT_TECHNICAL_EMAIL', 'tech@example.com'),
        ),
        'support' => array(
            'givenName' => env('SAML2_CONTACT_SUPPORT_NAME', 'User Support'),
            'emailAddress' => env('SAML2_CONTACT_SUPPORT_EMAIL', 'support@example.com'),
        ),
    ),

    // Organization information（本番環境の組織情報）
    'organization' => array(
        'ja-JP' => array(
            'name' => env('SAML2_ORGANIZATION_NAME', 'La NekoCafe'),
            'displayname' => env('SAML2_ORGANIZATION_DISPLAYNAME', 'La NekoCafe 猫カフェ'),
            'url' => env('APP_URL', 'https://lanekocafe.example.com'),
        ),
        'en-US' => array(
            'name' => env('SAML2_ORGANIZATION_NAME_EN', 'La NekoCafe'),
            'displayname' => env('SAML2_ORGANIZATION_DISPLAYNAME_EN', 'La NekoCafe Cat Cafe'),
            'url' => env('APP_URL', 'https://lanekocafe.example.com'),
        ),
    ),

    /**
     * 本番環境追加設定
     */

    // プロキシ変数の使用（ロードバランサー使用時は true に設定）
    // ロードバランサー（ALB、Nginx、Cloudflare等）経由でアクセスする場合、
    // X-Forwarded-* ヘッダーを信頼する必要があります
    'proxyVars' => env('SAML2_' . $this_idp_env_id . '_PROXY_VARS', true),

    // 大文字小文字を区別する設定
    'lowercaseUrlencoding' => true,

    /**
 * 本番環境セキュリティチェックリスト
 *
 * ✅ strict モードが true に設定されている
 * ✅ debug モードが false に設定されている
 * ✅ HTTPS URLを使用している
 * ✅ SP証明書と秘密鍵が設定されている
 * ✅ IdP証明書が設定されている
 * ✅ authnRequestsSigned が true
 * ✅ wantAssertionsSigned が true
 * ✅ wantMessagesSigned が true
 * ✅ 強力な署名アルゴリズム（SHA256）を使用
 * ✅ プロキシ設定が適切に設定されている
 */

    /*
     * Interoperable SAML 2.0 Web Browser SSO Profile [saml2int]
     * http://saml2int.org/profile/current
     *
     * 本番環境では以下の設定が推奨されます：
     *
     * 'authnRequestsSigned' => true,     // 本番環境では署名を推奨
     * 'wantAssertionsSigned' => true,    // 必須（なりすまし攻撃防止）
     * 'wantAssertionsEncrypted' => true, // HTTPSと併用でセキュリティ強化
     * 'wantNameIdEncrypted' => false,    // オプション（パフォーマンスと要件次第）
     *
     * 注意事項：
     * - SSL/HTTPSが無効の場合、wantAssertionsEncrypted は必須
     * - 本番環境では必ずHTTPS（SSL/TLS）を使用してください
     * - IdP（Keycloak）側でも対応する設定が必要です
     *   - Client signature required: ON（SPからの署名を要求）
     *   - Encrypt assertions: ON（Assertionの暗号化）
     *   - Sign documents: ON（ドキュメント署名）
     *   - Sign assertions: ON（Assertion署名）
     */

);
