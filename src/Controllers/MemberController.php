<?php
declare(strict_types=1);

/** 管理者による全メンバーの閲覧・登録・編集・削除。本人による編集は MyPageController */
final class MemberController
{
    public static function index(): void
    {
        Auth::requireAdmin();

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
            'total'          => MemberRepository::countAll(),
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
        Auth::requireAdmin();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        view('members/show', [
            'member'  => $member,
            'skills'  => MemberRepository::skillsForMembers([$id])[$id] ?? [],
            'isAdmin' => true,
            'backUrl' => '/',
            'editUrl' => '/members/' . $id . '/edit',
        ], $member['name']);
    }

    public static function createForm(): void
    {
        Auth::requireAdmin();
        self::renderForm(null, ['name' => '', 'bio' => ''], [], []);
    }

    public static function editForm(int $id): void
    {
        Auth::requireAdmin();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }
        self::renderForm($member, $member, MemberRepository::levelMap($id), []);
    }

    public static function store(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        [$name, $bio, $levels, $errors] = MemberProfileForm::validate();
        $avatar = MemberProfileForm::saveAvatar($_FILES['avatar'] ?? null, $errors);
        [$loginId, $password, $credentialErrors] = self::validateCredential(null);
        $errors = [...$errors, ...$credentialErrors];

        if ($errors !== []) {
            self::renderForm(null, ['name' => $name, 'bio' => $bio], $levels, $errors, $loginId);
            return;
        }

        $id = MemberRepository::create($name, $bio, $avatar, $levels);
        self::saveCredential($id, $loginId, $password);

        flash($name . ' を登録しました。');
        redirect('/members/' . $id);
    }

    public static function update(int $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        [$name, $bio, $levels, $errors] = MemberProfileForm::validate();
        $avatar = MemberProfileForm::saveAvatar($_FILES['avatar'] ?? null, $errors);
        [$loginId, $password, $credentialErrors] = self::validateCredential($id);
        $errors = [...$errors, ...$credentialErrors];

        if ($errors !== []) {
            self::renderForm($member, ['name' => $name, 'bio' => $bio], $levels, $errors, $loginId);
            return;
        }

        MemberRepository::update($id, $name, $bio, $avatar, $levels);
        self::saveCredential($id, $loginId, $password);

        // 差し替え後に古い画像を消す。DB 更新が成功してから消すこと
        if ($avatar !== null && !empty($member['avatar_path'])) {
            MemberProfileForm::deleteAvatarFile((string)$member['avatar_path']);
        }

        flash($name . ' を更新しました。');
        redirect('/members/' . $id);
    }

    public static function destroy(int $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $member = MemberRepository::find($id);
        if ($member === null) {
            abort(404, 'メンバーが見つかりません。');
        }

        // member_credentials は ON DELETE CASCADE で一緒に消える
        MemberRepository::delete($id);
        if (!empty($member['avatar_path'])) {
            MemberProfileForm::deleteAvatarFile((string)$member['avatar_path']);
        }

        flash($member['name'] . ' を削除しました。');
        redirect('/');
    }

    // ------------------------------------------------------------------

    private static function renderForm(
        ?array $member,
        array $values,
        array $levels,
        array $errors,
        ?string $loginIdValue = null
    ): void {
        $memberId   = $member['id'] ?? null;
        $credential = $memberId !== null ? MemberCredentialRepository::findByMemberId((int)$memberId) : null;

        view('members/form', [
            'member'       => $member,
            'values'       => $values,
            'levels'       => $levels,
            'errors'       => $errors,
            'categories'   => SkillRepository::categoriesWithSkills(),
            'isAdmin'      => true,
            'actionUrl'    => $memberId !== null ? '/members/' . $memberId : '/members',
            'backUrl'      => $memberId !== null ? '/members/' . $memberId : '/',
            'deleteUrl'    => $memberId !== null ? '/members/' . $memberId . '/delete' : null,
            // バリデーション失敗時に入力値を再表示するため、DB の値より優先する
            'loginIdValue' => $loginIdValue ?? ($credential['login_id'] ?? ''),
        ], $member === null ? 'メンバーを追加' : $member['name'] . ' を編集');
    }

    /**
     * ログイン情報の入力を検証する。管理者専用の項目 (04_改修_メンバー個人ログイン.md)。
     *
     * @return array{0:string,1:?string,2:string[]} [ログインID, パスワード(未入力ならnull), エラー]
     */
    private static function validateCredential(?int $memberId): array
    {
        $errors  = [];
        $loginId = trim((string)($_POST['login_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($loginId !== '') {
            if (mb_strlen($loginId) > 64) {
                $errors[] = 'ログインIDは64文字以内で入力してください。';
            }
            if (MemberCredentialRepository::loginIdTakenByOther($loginId, $memberId ?? 0)) {
                $errors[] = 'そのログインIDは既に使われています。';
            }
        }

        // 新規発行 (これまで未発行) にはパスワードが必須。
        // 発行済みメンバーの更新でパスワード欄が空なら「変更しない」の意味になる (呼び出し側で処理)。
        $alreadyIssued = $memberId !== null && MemberCredentialRepository::findByMemberId($memberId) !== null;
        if ($loginId !== '' && $password === '' && !$alreadyIssued) {
            $errors[] = 'ログインIDを設定する場合は、パスワードも入力してください。';
        }

        return [$loginId, $password === '' ? null : $password, $errors];
    }

    private static function saveCredential(int $memberId, string $loginId, ?string $password): void
    {
        if ($loginId === '') {
            // ログインID欄を空にして保存 = 発行を取り消す
            MemberCredentialRepository::revoke($memberId);
            return;
        }
        MemberCredentialRepository::upsert($memberId, $loginId, $password);
    }
}
