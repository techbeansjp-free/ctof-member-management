<?php
declare(strict_types=1);

/**
 * メンバー本人のログイン情報 (member_credentials)。
 * 発行・変更は管理者のみが行う (04_改修_メンバー個人ログイン.md)。
 */
final class MemberCredentialRepository
{
    /** ログインID表示用。無ければ null (= 未発行) */
    public static function findByMemberId(int $memberId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT member_id, login_id FROM member_credentials WHERE member_id = ?'
        );
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** 他のメンバーが同じ login_id を使っていないか。フォームの事前検証に使う */
    public static function loginIdTakenByOther(string $loginId, int $exceptMemberId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM member_credentials WHERE login_id = ? AND member_id != ? LIMIT 1'
        );
        $stmt->execute([$loginId, $exceptMemberId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * ログイン情報を発行・更新する。
     * $password が null なら、既存のパスワードを変更せずログインIDだけ更新する
     * (パスワード欄を空のままにできる = 管理者が毎回パスワードを打ち直さなくてよい)。
     * まだ発行されていないメンバーに $password が null で渡された場合は何もしない
     * (パスワード無しでは行を作れない)。
     */
    public static function upsert(int $memberId, string $loginId, ?string $password): void
    {
        $existing = self::findByMemberId($memberId);

        if ($existing === null) {
            if ($password === null) {
                return;
            }
            $stmt = Database::pdo()->prepare(
                'INSERT INTO member_credentials (member_id, login_id, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$memberId, $loginId, password_hash($password, PASSWORD_DEFAULT)]);
            return;
        }

        if ($password === null) {
            $stmt = Database::pdo()->prepare(
                'UPDATE member_credentials SET login_id = ? WHERE member_id = ?'
            );
            $stmt->execute([$loginId, $memberId]);
            return;
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE member_credentials SET login_id = ?, password_hash = ? WHERE member_id = ?'
        );
        $stmt->execute([$loginId, password_hash($password, PASSWORD_DEFAULT), $memberId]);
    }

    /** ログイン情報を削除し、そのメンバーをログインできない状態に戻す */
    public static function revoke(int $memberId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM member_credentials WHERE member_id = ?');
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
