<?php
declare(strict_types=1);

/**
 * 管理者 (admin_users) とメンバー本人 (member_credentials) の認証。
 *
 * 意図的に2系統を1クラスの中でもメソッドレベルで完全に分けている
 * (attemptAdmin/attemptMember, checkAdmin/checkMember, requireAdmin/requireMember)。
 * 1つの attempt()/check() にロールを判定する分岐を持たせると、その分岐を
 * 間違えたときに越権アクセスに直結する。「メンバー用のメソッドは、原理的に
 * 管理者権限を返しようがない」という状態にするための分離
 * (04_改修_メンバー個人ログイン.md)。
 */
final class Auth
{
    private const ADMIN_LOGIN_ID = 'admin';

    // ------------------------------------------------------------------
    // 管理者 (admin_users)
    // ------------------------------------------------------------------

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
        $stmt->execute([self::ADMIN_LOGIN_ID, password_hash($password, PASSWORD_DEFAULT)]);
    }

    public static function attemptAdmin(string $loginId, string $password): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, password_hash FROM admin_users WHERE login_id = ? LIMIT 1'
        );
        $stmt->execute([$loginId]);
        $row = $stmt->fetch();

        if ($row === false || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        // セッション固定攻撃対策。ログイン成功のたびに ID を振り直す。
        // $_SESSION を丸ごと置き換えるのは、メンバーとしてログイン中に管理者
        // ログインした場合に member_id を確実に消すため (2系統を同時に持たせない)。
        session_regenerate_id(true);
        $_SESSION = ['admin_id' => (int)$row['id']];
        return true;
    }

    public static function checkAdmin(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    /**
     * 管理者でなければ弾く。メンバーとしてログイン中なら管理者ログイン画面
     * ではなく自分のマイページへ戻す (すでにログイン済みの人に管理者用の
     * ログインフォームを見せても混乱させるだけなので)。
     */
    public static function requireAdmin(): void
    {
        if (self::checkAdmin()) {
            return;
        }
        redirect(self::checkMember() ? '/mypage' : '/login');
    }

    // ------------------------------------------------------------------
    // メンバー本人 (member_credentials)
    // ------------------------------------------------------------------

    /** 成功したら true。セッションに member_id をセットする */
    public static function attemptMember(string $loginId, string $password): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT member_id, password_hash FROM member_credentials WHERE login_id = ? LIMIT 1'
        );
        $stmt->execute([$loginId]);
        $row = $stmt->fetch();

        if ($row === false || !password_verify($password, $row['password_hash'])) {
            return false;
        }

        // 同じ理由で $_SESSION を丸ごと置き換える (admin_id を確実に消す)
        session_regenerate_id(true);
        $_SESSION = ['member_id' => (int)$row['member_id']];
        return true;
    }

    public static function checkMember(): bool
    {
        return isset($_SESSION['member_id']);
    }

    /** 対称に、管理者としてログイン中なら管理者トップへ戻す */
    public static function requireMember(): void
    {
        if (self::checkMember()) {
            return;
        }
        redirect(self::checkAdmin() ? '/' : '/mypage/login');
    }

    /**
     * ログイン中のメンバー自身の ID。
     * requireMember() を通過した後にだけ呼ぶこと。
     *
     * /mypage 配下のコントローラは、リクエストから受け取った ID を一切使わず
     * 必ずこれを使う。他人の member_id を URL 等から渡す経路自体を作らない
     * ことで IDOR を構造的に防ぐ (04_改修_メンバー個人ログイン.md)。
     */
    public static function memberId(): int
    {
        return (int)($_SESSION['member_id'] ?? 0);
    }

    // ------------------------------------------------------------------
    // 共通
    // ------------------------------------------------------------------

    /**
     * 管理者・メンバーのどちらでもよい認証チェック。
     * アイコン画像のように「認証さえ通っていれば誰が見てもよい」ものに使う。
     * どちらの権限を持っているかの判定はしないので、権限を分けたい処理には使わないこと。
     */
    public static function requireAnyLogin(): void
    {
        if (!self::checkAdmin() && !self::checkMember()) {
            redirect('/login');
        }
    }

    /**
     * admin_id・member_id のどちらが立っていてもまとめて破棄する。
     * 同一ブラウザで管理者とメンバーの両方に同時ログインする状況は想定しない。
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
