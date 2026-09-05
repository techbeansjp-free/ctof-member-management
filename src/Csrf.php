<?php
declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** hidden input をそのまま出力する */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    /**
     * すべての POST で呼ぶ。不一致なら止める (02_基本設計.md §7)。
     *
     * 設計では 419 としていたが、Apache は未登録のステータスコードを 500 に
     * 差し替えてしまうため 403 を使う。
     */
    public static function verify(): void
    {
        $sent    = (string)($_POST['_token'] ?? '');
        $session = (string)($_SESSION['csrf_token'] ?? '');

        if ($sent === '' || $session === '' || !hash_equals($session, $sent)) {
            abort(403, 'セッションが切れました。もう一度やり直してください。');
        }
    }
}
