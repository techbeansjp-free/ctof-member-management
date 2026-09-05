<?php
declare(strict_types=1);

final class Auth
{
    private const LOGIN_ID = 'admin';

    /**
     * 管理者が 1 件も無ければ作る。
     * パスワードは .env の ADMIN_PASSWORD (デプロイ時に自動生成される 48 文字)。
     * 初期 SQL では password_hash() を使えないためアプリ側で行う (02_基本設計.md §6)。
     */
    public static function ensureAdminExists(): void
    {
        $pdo = Database::pdo();
        $count = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $password = (string)getenv('ADMIN_PASSWORD');
        if ($password === '') {
            error_log('[skillmap] ADMIN_PASSWORD が未設定のため管理者を作成できません');
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO admin_users (login_id, password_hash) VALUES (?, ?)'
        );
        $stmt->execute([self::LOGIN_ID, password_hash($password, PASSWORD_DEFAULT)]);
    }

    public static function attempt(string $loginId, string $password): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, password_hash FROM admin_users WHERE login_id = ? LIMIT 1'
        );
        $stmt->execute([$loginId]);
        $row = $stmt->fetch();

        if ($row === false || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        // セッション固定攻撃対策
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$row['id'];
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }
}
