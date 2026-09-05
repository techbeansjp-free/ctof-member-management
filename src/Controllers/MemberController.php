<?php
declare(strict_types=1);

final class MemberController
{
    private const MAX_AVATAR_BYTES = 2 * 1024 * 1024; // 2MB
    private const AVATAR_SIZE      = 256;
    private const MAX_BIO_LENGTH   = 500;

    public static function index(): void
    {
        Auth::requireLogin();

        $skillIds = array_values(array_unique(
            array_filter(
                array_map('intval', (array)($_GET['skill'] ?? [])),
                static fn(int $v): bool => $v > 0
            )
        ));

        $minLevel = (int)($_GET['level'] ?? 1);
        if (!in_array($minLevel, [1, 2, 3], true)) {
            $minLevel = 1;
        }
        $keyword = trim((string)($_GET['q'] ?? ''));

        $members = MemberRepository::search($skillIds, $minLevel, $keyword);

        view('members/index', [
            'members'        => $members,
            'skillsByMember' => MemberRepository::skillsForMembers(array_column($members, 'id')),
            'categories'     => SkillRepository::categoriesWithSkills(),
            'selectedSkills' => $skillIds,
            'selectedNames'  => SkillRepository::namesByIds($skillIds),
            'minLevel'       => $minLevel,
            'keyword'        => $keyword,
        ], 'メンバー一覧');
    }

    public static function show(int $id): void
    {
        Auth::requireLogin();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        view('members/show', [
            'member' => $member,
            'skills' => MemberRepository::skillsForMembers([$id])[$id] ?? [],
        ], $member['name']);
    }

    public static function createForm(): void
    {
        Auth::requireLogin();
        self::renderForm(null, ['name' => '', 'bio' => ''], [], []);
    }

    public static function editForm(int $id): void
    {
        Auth::requireLogin();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }
        self::renderForm($member, $member, MemberRepository::levelMap($id), []);
    }

    public static function store(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        [$name, $bio, $levels, $errors] = self::validate();
        $avatar = self::saveAvatar($_FILES['avatar'] ?? null, $errors);

        if ($errors !== []) {
            self::renderForm(null, ['name' => $name, 'bio' => $bio], $levels, $errors);
            return;
        }

        $id = MemberRepository::create($name, $bio, $avatar, $levels);
        flash($name . ' を登録しました。');
        redirect('/members/' . $id);
    }

    public static function update(int $id): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        [$name, $bio, $levels, $errors] = self::validate();
        $avatar = self::saveAvatar($_FILES['avatar'] ?? null, $errors);

        if ($errors !== []) {
            self::renderForm($member, ['name' => $name, 'bio' => $bio], $levels, $errors);
            return;
        }

        MemberRepository::update($id, $name, $bio, $avatar, $levels);

        // 差し替え後に古い画像を消す。DB 更新が成功してから消すこと
        if ($avatar !== null && !empty($member['avatar_path'])) {
            self::deleteAvatarFile((string)$member['avatar_path']);
        }

        flash($name . ' を更新しました。');
        redirect('/members/' . $id);
    }

    public static function destroy(int $id): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        MemberRepository::delete($id);
        if (!empty($member['avatar_path'])) {
            self::deleteAvatarFile((string)$member['avatar_path']);
        }

        flash($member['name'] . ' を削除しました。');
        redirect('/');
    }

    // ------------------------------------------------------------------

    private static function renderForm(?array $member, array $values, array $levels, array $errors): void
    {
        view('members/form', [
            'member'     => $member,
            'values'     => $values,
            'levels'     => $levels,
            'errors'     => $errors,
            'categories' => SkillRepository::categoriesWithSkills(),
        ], $member === null ? 'メンバーを登録' : $member['name'] . ' を編集');
    }

    /** @return array{0:string,1:?string,2:array<int,int>,3:string[]} */
    private static function validate(): array
    {
        $errors = [];

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $errors[] = '名前を入力してください。';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = '名前は 100 文字以内で入力してください。';
        }

        $bio = trim((string)($_POST['bio'] ?? ''));
        if (mb_strlen($bio) > self::MAX_BIO_LENGTH) {
            $errors[] = '自己紹介文は ' . self::MAX_BIO_LENGTH . ' 文字以内で入力してください。';
        }

        // 存在するスキル ID かつ level 1-3 のものだけを採用する。
        // 「未経験」は行を作らない (01_要件定義.md §4)
        $valid  = array_flip(SkillRepository::existingIds());
        $levels = [];
        foreach ((array)($_POST['skill_level'] ?? []) as $skillId => $level) {
            $skillId = (int)$skillId;
            $level   = (int)$level;
            if (isset($valid[$skillId]) && in_array($level, [1, 2, 3], true)) {
                $levels[$skillId] = $level;
            }
        }

        return [$name, $bio === '' ? null : $bio, $levels, $errors];
    }

    /**
     * アップロードされた画像を 256px の正方形 WebP にして保存する。
     * 拡張子は信用せず finfo で判定する (02_基本設計.md §7)。
     *
     * @return string|null 保存したファイル名。アップロードが無ければ null
     */
    private static function saveAvatar(?array $file, array &$errors): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '画像のアップロードに失敗しました。';
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = '画像のアップロードに失敗しました。';
            return null;
        }
        if ($file['size'] > self::MAX_AVATAR_BYTES) {
            $errors[] = '画像は 2MB 以下にしてください。';
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $errors[] = '画像は JPEG / PNG / WebP のいずれかにしてください。';
            return null;
        }

        $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if ($src === false) {
            $errors[] = '画像を読み込めませんでした。';
            return null;
        }

        // 中央を正方形に切り出してから縮小する
        $w    = imagesx($src);
        $h    = imagesy($src);
        $side = min($w, $h);
        $dst  = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled(
            $dst, $src,
            0, 0,
            intdiv($w - $side, 2), intdiv($h - $side, 2),
            self::AVATAR_SIZE, self::AVATAR_SIZE,
            $side, $side
        );

        // 保存名は元のファイル名を使わない
        $filename = bin2hex(random_bytes(16)) . '.webp';
        $ok = imagewebp($dst, AVATAR_DIR . '/' . $filename, 85);
        imagedestroy($src);
        imagedestroy($dst);

        if ($ok === false) {
            $errors[] = '画像を保存できませんでした。';
            return null;
        }
        return $filename;
    }

    private static function deleteAvatarFile(string $filename): void
    {
        if (preg_match('/\A[0-9a-f]{32}\.webp\z/', $filename) !== 1) {
            return;
        }
        $path = AVATAR_DIR . '/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
