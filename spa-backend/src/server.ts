/**
 * Node.js Express + SAML 2.0 認証サーバー
 *
 * React SPA用のバックエンドAPI
 * Keycloakとの SAML 2.0 認証を処理
 *
 * @node-saml/passport-saml v5.x 対応
 * 公式GitHub: https://github.com/node-saml/passport-saml
 * 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
 */

import express, { Request, Response, NextFunction } from 'express';
import session from 'express-session';
import bodyParser from 'body-parser';
import cookieParser from 'cookie-parser';
import cors from 'cors';
import passport from 'passport';
import { Strategy as SamlStrategy } from '@node-saml/passport-saml';
import type { Profile } from '@node-saml/passport-saml';
import type { VerifyWithoutRequest } from '@node-saml/passport-saml';
import { samlConfig } from './config/saml';
import type { User, SamlProfile, SerializeUser } from './types/user';
import { RequestWithUser } from '@node-saml/passport-saml/lib/types';

const app = express();
const PORT = process.env.PORT || 3001;

// ミドルウェア設定
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cookieParser());

// CORS設定（React SPAからのリクエストを許可）
app.use(cors({
    origin: process.env.FRONTEND_URL || 'http://localhost:3000',
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));

// セッション設定
app.use(session({
    secret: process.env.SESSION_SECRET!,
    resave: false,
    saveUninitialized: false,
    cookie: {
        secure: process.env.NODE_ENV === 'production',
        httpOnly: true,
        maxAge: 24 * 60 * 60 * 1000 // 24時間
    }
}));

// Passport初期化
app.use(passport.initialize());
app.use(passport.session());

// Passport SAML Strategy設定
// メタデータ生成のため、Strategyインスタンスを保持
// 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/

// サインオン時の検証コールバック
const signonVerifyCallback: VerifyWithoutRequest = (profile: Profile | null, done) => {
    if (!profile) {
        return done(new Error('No profile received from SAML'));
    }

    // @node-saml/passport-saml v5.x では Profile 型を使用
    // Profile 型には nameID, issuer, sessionIndex などが含まれる
    const samlProfile = profile as SamlProfile;

    if (!samlProfile.nameID) {
        return done(new Error('SAML nameID not found in profile.'));
    }
    // ユーザー情報を抽出
    const user: User = {
        id: samlProfile.id || samlProfile.nameID || 'unknown',
        email: samlProfile.email || samlProfile['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'] || 'unknown@example.com',
        name: samlProfile.name || samlProfile['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'] || 'Unknown User',
        samlId: samlProfile.nameID || 'unknown',
        attributes: {
            issuer: samlProfile.issuer,
            sessionIndex: samlProfile.sessionIndex,
            nameID: samlProfile.nameID,
            nameIDFormat: samlProfile.nameIDFormat
        }
    };

    if (process.env.NODE_ENV === 'development') {
        console.log('User authenticated!', user);
    }
    // @node-saml/passport-saml の done コールバックは Record<string, unknown> を期待
    return done(null, user as unknown as Record<string, unknown>);
};

// ログアウト時の検証コールバック
// @node-saml/passport-saml v5.x では signonVerify と logoutVerify の両方が必要
const logoutVerifyCallback: VerifyWithoutRequest = (profile: Profile | null, done) => {
    if (!profile) {
        return done(new Error('No profile received for logout'));
    }

    if (process.env.NODE_ENV === 'development') {
        console.log('Logout profile received:', profile.nameID);
    }

    // ログアウト時はユーザー情報を返す（セッションからユーザーを特定するため）
    const user = {
        nameID: profile.nameID,
        nameIDFormat: profile.nameIDFormat,
        sessionIndex: profile.sessionIndex
    };

    return done(null, user);
}

// @node-saml/passport-saml v5.x では 3つの引数が必要:
// 1. options (SamlConfig)
// 2. signonVerify (認証時のコールバック)
// 3. logoutVerify (ログアウト時のコールバック)
const samlStrategy = new SamlStrategy(
    samlConfig, signonVerifyCallback, logoutVerifyCallback
);

// 型の互換性問題を回避するためのキャスト
// @types/passport と @node-saml/passport-saml の @types/express バージョン差異による
passport.use(samlStrategy as unknown as passport.Strategy);

// セッションのシリアライズ/デシリアライズ
passport.serializeUser((user, done) => {
    const serialized: SerializeUser = {
        id: user.id,
        email: user.email,
        name: user.name,
        samlId: user.samlId
    }
    done(null, serialized);
});

passport.deserializeUser((serialized: SerializeUser, done) => {
    // セッションから復元したユーザー情報
    // 必要に応じてDBからフル情報を取得することも可能
    const user: User = {
        ...serialized,
        attributes: {}  // セッションからの復元時は属性は空
    }
    done(null, user);
});

// ========================================
// ルート定義
// ========================================

// ヘルスチェック
app.get('/health', (_req: Request, res: Response) => {
    res.json({
        status: 'healthy',
        service: 'SPA Backend',
        timestamp: new Date().toUTCString()
    });
});

// 認証状態確認
app.get('/api/auth/check', (req: Request, res: Response) => {
    if (req.isAuthenticated()) {
        res.json({
            authenticated: true,
            user: req.user
        });
    } else {
        res.json({
            authenticated: false
        });
    }
});

// SAML認証開始
app.get('/saml/login',
    passport.authenticate('saml', { failureRedirect: '/' })
);

// SAML Assertion Consumer Service (ACS) - 認証後のコールバック
app.post('/saml/acs',
    passport.authenticate('saml', { failureRedirect: '/' }),
    (req: Request, res: Response) => {
        if (process.env.NODE_ENV === 'development') {
            console.log('SAML ACS Success:', (req.user as User)?.id);
        }
        // フロントエンドにリダイレクト
        res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
    }
);

// SAMLメタデータ
// 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
// 公式GitHub: https://github.com/node-saml/passport-saml
app.get('/saml/metadata', (_req: Request, res: Response) => {
    res.type('application/xml');
    // @node-saml/passport-saml v5.x では第1引数 decryptionCert が必須（null可）
    // 第2引数 signingCert は省略可能
    const metadata = samlStrategy.generateServiceProviderMetadata(null);
    res.send(metadata);
});

// SAML Single Logout (SLO) - SP発行
// IdP（Keycloak）に対してログアウトリクエストを送信し、全てのSPからログアウト
// @node-saml/passport-saml v5.x では logout メソッドはコールバックベース
app.get('/saml/logout', (req: Request, res: Response) => {
    if (req.isAuthenticated()) {
        // SAMLログアウトリクエストを生成してIdPに送信
        // logout(req, callback) の形式で呼び出す
        samlStrategy.logout(req as any, (err: Error | null, requestUrl?: string | null) => {
            if (err) {
                console.error('Local logout error:', err);
                // エラーが発生してもローカルセッションはクリア
                req.logout(() => {
                    res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
                });
                return;
            }
            if (requestUrl) {
                // ローカルセッションをクリアしてIdPのログアウトURLにリダイレクト
                req.logout((logoutErr) => {
                    if (logoutErr) {
                        console.error('Local logout error:', logoutErr);
                    }
                    res.redirect(requestUrl);
                });
                return;
            }
            // requestUrlがない場合はローカルログアウトのみ
            req.logout(() => {
                res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
            });
            return;
        });
    }

    res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
});

// SAML Single Logout Service (SLS) - IdP発行ログアウトの受信
// IdPから送られてくるログアウトリクエストを処理
app.post('/saml/sls',
    passport.authenticate('saml', { failureRedirect: '/' }),
    (req: Request, res: Response) => {
        console.log('SAML SLS: Logout request from IdP');
        req.logout(() => {
            res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
        });
    }
);

// ローカルログアウト（セッションのみクリア）
app.post('/api/auth/logout', (req: Request, res: Response) => {
    req.logout((err) => {
        if (err) {
            res.status(500).json({ error: 'Logout failed' });
            return;
        }
        res.json({ success: true, message: 'Logged out successfully' });
    });
});

// ユーザー情報取得API
app.get('/api/user', (req: Request, res: Response) => {
    if (!req.isAuthenticated()) {
        res.status(401).json({ error: 'Not authenticated' });
        return;
    }
    res.json({ user: req.user });
});

// 保護されたAPIエンドポイントの例
app.get('/api/protected', (req: Request, res: Response) => {
    if (!req.isAuthenticated()) {
        res.status(401).json({ error: 'Authentication required' });
        return;
    }
    res.json({
        message: 'This is a protected resource',
        user: req.user
    });
});

// エラーハンドリング
app.use((err: Error, _req: Request, res: Response, _next: NextFunction) => {
    console.error('Error:', err);
    res.status(500).json({ error: 'Internal server error' });
});

// ========================================
// サーバー起動
// ========================================

app.listen(PORT, () => {
    console.log('===========================================');
    console.log('🚀 SPA Backend Server Started');
    console.log('===========================================');
    console.log(`📍 Server URL: http://localhost:${PORT}`);
    console.log(`🔐 SAML Login: http://localhost:${PORT}/saml/login`);
    console.log(`📄 SAML Metadata: http://localhost:${PORT}/saml/metadata`);
    console.log(`🏥 Health Check: http://localhost:${PORT}/health`);
    console.log('===========================================');
    console.log(`⚙️  Keycloak: ${samlConfig.entryPoint}`);
    console.log(`🌐 Frontend: ${process.env.FRONTEND_URL || 'http://localhost:3000'}`);
    console.log('===========================================');
});
