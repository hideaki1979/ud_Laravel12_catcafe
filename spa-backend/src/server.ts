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
import { Strategy as SamlStrategy, Profile, VerifyWithoutRequest } from 'passport-saml';
import { samlConfig } from './config/saml';
import type { User, SamlProfile } from './types/user';

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
    secret: process.env.SESSION_SECRET || 'cat-cafe-sso-secret-key',
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

    // ユーザー情報を抽出
    const user: User = {
        id: samlProfile.id || samlProfile.nameID || 'unknown',
        email: samlProfile.email || samlProfile['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'] as string || 'unknown@example.com',
        name: samlProfile.name || samlProfile['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'] as string || 'Unknown User',
        samlId: samlProfile.nameID || 'unknown',
        attributes: samlProfile
    };

    console.log('User authenticated:', user);
    return done(null, user);
};

const samlStrategy = new SamlStrategy(samlConfig, verifyCallback);

passport.use(samlStrategy as any);

// セッションのシリアライズ/デシリアライズ
passport.serializeUser<User>((user, done) => {
    done(null, user)
});

passport.deserializeUser<User>((user, done) => {
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

// ログアウト
app.get('/saml/logout', (req: Request, res: Response) => {
    if (req.isAuthenticated()) {
        // セッションをクリアしてリダイレクト
        // SAMLログアウトは複雑なため、シンプルにローカルログアウトのみ実装
        req.logout((err) => {
            if (err) {
                return res.status(500).json({ error: 'Logout failed' });
            }
            res.json({ success: true, message: 'Logged out successfully' });
        });
    }
    res.redirect(process.env.FRONTEND_URL || 'http://localhost:3000');
});

// ローカルログアウト（セッションのみクリア）
app.post('/api/auth/logout', (req: Request, res: Response) => {
    req.logout((err) => {
        if (err) {
            return res.status(500).json({ error: 'Logout failed' });
        }
        res.json({ success: true, message: 'Logged out successfully' });
    });
});

// ユーザー情報取得API
app.get('/api/user', (req: Request, res: Response) => {
    if (!req.isAuthenticated()) {
        return res.status(401).json({ error: 'Not authenticated' });
    }
    res.json({ user: req.user });
});

// 保護されたAPIエンドポイントの例
app.get('/api/protected', (req: Request, res: Response) => {
    if (!req.isAuthenticated()) {
        return res.status(401).json({ error: 'Authentication required' });
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
