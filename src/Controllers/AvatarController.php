<?php
declare(strict_types=1);

/**
 * アイコンは DocumentRoot の外に置き、このコントローラ経由で配信する。
 * 理由は 02_基本設計.md §5 (アップロードファイルを実行させない / 認証を効かせる)。
 */
final class AvatarController
{
    public static function show(string $file): void
    {
        Auth::requireAnyLogin();

        // 保存名は必ず 32桁の16進 + .webp。それ以外は受け付けない (パストラバーサル対策)
        if (preg_match('/\A[0-9a-f]{32}\.webp\z/', $file) !== 1) {
            abort(404, '画像が見つかりません。');
        }

        $path = AVATAR_DIR . '/' . $file;
        if (!is_file($path)) {
            abort(404, '画像が見つかりません。');
        }

        header('Content-Type: image/webp');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }
}
