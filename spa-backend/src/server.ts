/**
 * Node.js Express + SAML 2.0 認証サーバー
 *
 * React SPA用のバックエンドAPI
 * Keycloakとの SAML 2.0 認証を処理
 */

import express, { Request, Response, NextFunction } from 'express';
import session from 'express-session';
import bodyParser from 'body-parser';
import cookieParser from 'cookie-parser';
import cors from 'cors';
import passport from 'passport';
import { Strategy as SamlStrategy, VerifyWithoutRequest } from 'passport-saml';
import type { RequestWithUser } from 'passport-saml/lib/passport-saml/types';
import { samlConfig } from './config/saml';
import type { User, SamlProfile, SerializeUser } from './types/user';

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
const verifyCallback: VerifyWithoutRequest = (profile, done) => {
    console.log('SAML Profile:', JSON.stringify(profile, null, 2));

    if (!profile) {
        return done(new Error('No profile received from SAML'));
    }

    const samlProfile = profile as unknown as SamlProfile;

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

    console.log('User authenticated!', user.name);
    // passport-saml の done コールバックは Record<string, unknown> を期待するため
    // User を object として渡す
    return done(null, user as unknown as Record<string, unknown>);
};

const samlStrategy = new SamlStrategy(samlConfig, verifyCallback);

passport.use(samlStrategy as passport.Strategy);

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
    passport.authenticate('saml', { failureRedirect: '/', failureFlash: true })
);

// SAML Assertion Consumer Service (ACS) - 認証後のコールバック
app.post('/saml/acs',
    passport.authenticate('saml', { failureRedirect: '/' }),
    (req: Request, res: Response) => {
        console.log('SAML ACS Success:', req.user);
        // フロントエンドにリダイレクト
        res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
    }
);

// SAMLメタデータ
// 公式ドキュメント: https://www.passportjs.org/packages/passport-saml/
app.get('/saml/metadata', (_req: Request, res: Response) => {
    res.type('application/xml');
    // decryptionCert と signingCert は省略可能（学習用では不要）
    const metadata = samlStrategy.generateServiceProviderMetadata(null);
    res.send(metadata);
});

// SAML Single Logout (SLO) - SP発行
// IdP（Keycloak）に対してログアウトリクエストを送信し、全てのSPからログアウト
app.get('/saml/logout', (req: Request, res: Response) => {
    if (req.isAuthenticated()) {
        // SAMLログアウトリクエストを生成してIdPに送信
        // 型エラー回避のため、reqを any としてキャスト
        samlStrategy.logout(req as unknown as RequestWithUser, (err: Error | null, requestUrl?: string | null) => {
            if (err) {
                console.error('SAML logout error:', err);
                // エラーが発生してもローカルセッションはクリア
                req.logout(() => {
                    res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
                });
            }

            if (requestUrl) {
                // IdPのログアウトURLにリダイレクト
                req.logout(() => {
                    res.redirect(requestUrl);
                });
            }
            // requestUrlがない場合はフロントエンドにリダイレクト
            req.logout(() => {
                res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
            });

        });
    }
    res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
});

// SAML Single Logout Service (SLS) - IdP発行ログアウトの受信
// IdPから送られてくるログアウトリクエストを処理
app.post('/saml/sls',
    passport.authenticate('saml', { failureRedirect: '/', failureFlash: true }),
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
