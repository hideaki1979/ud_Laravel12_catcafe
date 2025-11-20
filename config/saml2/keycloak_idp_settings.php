<?php

/**
 * Keycloak IdP設定ファイル
 *
 * このファイルはKeycloakをIdP（Identity Provider）として使用するための設定です。
 * Keycloakは http://localhost:8080 で起動していることを前提としています。
 */
$this_idp_env_id = 'KEYCLOAK';

// KeycloakのベースURL（Docker Composeで起動）
$keycloak_base_url = env('SAML2_' . $this_idp_env_id . '_BASE_URL', 'http://localhost:8080');
// Keycloakのレルム名（デフォルト：lanekocafe）
$keycloak_realm = env('SAML2_' . $this_idp_env_id . '_REALM', 'lanekocafe');

return $settings = array(

    /*****
     * One Login Settings
     */

    // 厳密モード: 本番環境では true を推奨
    'strict' => env('SAML2_' . $this_idp_env_id . '_STRICT', true),

    // デバッグモード
    'debug' => env('APP_DEBUG', false),

    // Service Provider (SP) の設定 = このLaravelアプリケーション
    'sp' => array(

        // NameID フォーマット（persistent: 永続的なユーザー識別子）
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

        // SP の証明書と秘密鍵（オプション: 署名が必要な場合）
        'x509cert' => env('SAML2_' . $this_idp_env_id . '_SP_x509', ''),
        'privateKey' => env('SAML2_' . $this_idp_env_id . '_SP_PRIVATEKEY', ''),

        // SP の Entity ID（空の場合は自動生成）
        'entityId' => env('SAML2_' . $this_idp_env_id . '_SP_ENTITYID', env('APP_URL') . '/saml2/keycloak/metadata'),

        // Assertion Consumer Service (ACS) - IdPからのレスポンスを受け取るエンドポイント
        'assertionConsumerService' => array(
            // URL Location where the <Response> from the IdP will be returned,
            // using HTTP-POST binding.
            // Leave blank to use the '{idpName}_acs' route, e.g. 'test_acs'
            'url' => env(
                'SAML2_' . $this_idp_env_id . '_SP_ACS_URL',
                env('APP_URL') . '/saml2/keycloak/metadata'
            ),
        ),
        // Single Logout Service (SLS) - ログアウト時のエンドポイント
        'singleLogoutService' => array(
            // URL Location where the <Response> from the IdP will be returned,
            // using HTTP-Redirect binding.
            // Leave blank to use the '{idpName}_sls' route, e.g. 'test_sls'
            'url' => env(
                'SAML2_' . $this_idp_env_id . '_SP_SLS_URL',
                env('APP_URL') . '/saml2/keycloak/sls'
            ),
        ),
    ),

    // Identity Provider (IdP) の設定 = Keycloak
    'idp' => array(
        // IdP の Entity ID
        'entityId' => env('SAML2_' . $this_idp_env_id . '_IDP_ENTITYID', $keycloak_base_url . '/realms/' . $keycloak_realm),
        // Single Sign-On (SSO) エンドポイント
        'singleSignOnService' => array(
            // URL Target of the IdP where the SP will send the Authentication Request Message,
            // using HTTP-Redirect binding.
            'url' => env('SAML2_' . $this_idp_env_id . '_IDP_SSO_URL', $keycloak_base_url . '/realms/' . $keycloak_realm . '/protocol/saml'),
        ),
        // Single Logout Service (SLO) エンドポイント
        'singleLogoutService' => array(
            // URL Location of the IdP where the SP will send the SLO Request,
            // using HTTP-Redirect binding.
            'url' => env('SAML2_' . $this_idp_env_id . '_IDP_SL_URL', $keycloak_base_url . '/realms/' . $keycloak_realm . '/protocol/saml'),
        ),
        // IdP の公開鍵証明書
        // Keycloakの管理画面から取得して .env に設定してください
        // Realm Settings > Keys > RS256 の Certificate をコピー
        'x509cert' => env('SAML2_' . $this_idp_env_id . '_IDP_x509', ''),
        /*
         *  Instead of use the whole x509cert you can use a fingerprint
         *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it)
         */
        // 'certFingerprint' => '',
    ),



    /***
     * OneLogin 詳細設定
     */
    // Security settings
    'security' => array(

        /** SPが送信する際の署名・暗号化設定 */

        // NameID を暗号化するか
        'nameIdEncrypted' => false,

        // AuthnRequest（認証リクエスト）に署名するか            [The Metadata of the SP will offer this info]
        'authnRequestsSigned' => false,

        // LogoutRequest に署名するか
        'logoutRequestSigned' => false,

        // LogoutResponse に署名するか
        'logoutResponseSigned' => false,

        // メタデータに署名するか
        'signMetadata' => false,

        /** IdPから受信する際の要件 */

        // Response、LogoutRequest、LogoutResponse に署名を要求するか
        'wantMessagesSigned' => false,

        // Assertion（認証情報）に署名を要求するか
        'wantAssertionsSigned' => false,

        // NameID の暗号化を要求するか
        'wantNameIdEncrypted' => false,

        // 認証コンテキスト
        // true: パスワード保護されたトランスポートを要求
        // false: 認証コンテキストを送信しない
        // array: 複数の認証コンテキストを指定
        'requestedAuthnContext' => true,

        // 署名アルゴリズム
        'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

        // ダイジェストアルゴリズム
        'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
    ),

    // 連絡先情報
    'contactPerson' => array(
        'technical' => array(
            'givenName' => env('SAML2_CONTACT_NAME', 'Technical Support'),
            'emailAddress' => env('SAML2_CONTACT_EMAIL', 'tech@example.com'),
        ),
        'support' => array(
            'givenName' => env('SAML2_CONTACT_NAME', 'User Support'),
            'emailAddress' => env('SAML2_CONTACT_EMAIL', 'support@example.com'),
        ),
    ),

    // Organization information template, the info in en_US lang is recomended, add more if required
    'organization' => array(
        'ja-JP' => array(
            'name' => env('SAML2_ORGANIZATION_NAME', 'La NekoCafe'),
            'displayname' => env('SAML2_ORGANIZATION_DISPLAYNAME', 'La NekoCafe 猫カフェ'),
            'url' => env('APP_URL', 'http://localhost'),
        ),
    ),

    /* Interoperable SAML 2.0 Web Browser SSO Profile [saml2int]   http://saml2int.org/profile/current

   'authnRequestsSigned' => false,    // SP SHOULD NOT sign the <samlp:AuthnRequest>,
                                      // MUST NOT assume that the IdP validates the sign
   'wantAssertionsSigned' => true,
   'wantAssertionsEncrypted' => true, // MUST be enabled if SSL/HTTPs is disabled
   'wantNameIdEncrypted' => false,
*/

);
