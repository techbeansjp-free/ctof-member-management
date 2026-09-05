<?php
declare(strict_types=1);

/**
 * メンバー本人による自分の情報の閲覧・編集。
 *
 * すべてのメソッドが Auth::memberId() だけを使い、リクエストから受け取った
 * ID は一切使わない。URL に member_id を含めない設計と合わせて、他人の情報に
 * アクセスする経路自体を作らない (04_改修_メンバー個人ログイン.md)。
 */
final class MyPageController
{
    public static function showLogin(): void
    {
        if (Auth::checkMember()) {
            redirect('/mypage');
        }
        view('login', self::viewData('', null), 'ログイン');
    }

    public static function login(): void
    {
        Csrf::verify();

        $loginId  = trim((string)($_POST['login_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attemptMember($loginId, $password)) {
            redirect('/mypage');
        }

        http_response_code(401);
        view('login', self::viewData($loginId, 'ログインIDまたはパスワードが違います。'), 'ログイン');
    }

    public static function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/mypage/login');
    }

    public static function show(): void
    {
        Auth::requireMember();
        $id     = Auth::memberId();
        $member = MemberRepository::find($id);
        if ($member === null) {
            // ログイン情報が残ったままメンバー本体が消えている異常系。
            // requireMember は通っているのでログアウトさせてから知らせる
            Auth::logout();
            abort(404, 'メンバー情報が見つかりません。管理者にお問い合わせください。');
        }

        view('members/show', [
            'member'  => $member,
            'skills'  => MemberRepository::skillsForMembers([$id])[$id] ?? [],
            'isAdmin' => false,
            'backUrl' => null,
            'editUrl' => '/mypage/edit',
        ], $member['name']);
    }

    public static function editForm(): void
    {
        Auth::requireMember();
        $id     = Auth::memberId();
        $member = MemberRepository::find($id);
        if ($member === null) {
            Auth::logout();
            abort(404, 'メンバー情報が見つかりません。管理者にお問い合わせください。');
        }
        self::renderForm($member, $member, MemberRepository::levelMap($id), []);
    }

    public static function update(): void
    {
        Auth::requireMember();
        Csrf::verify();

        $id     = Auth::memberId();
        $member = MemberRepository::find($id);
        if ($member === null) {
            Auth::logout();
            abort(404, 'メンバー情報が見つかりません。管理者にお問い合わせください。');
        }

        [$name, $bio, $levels, $errors] = MemberProfileForm::validate();
        $avatar = MemberProfileForm::saveAvatar($_FILES['avatar'] ?? null, $errors);

        if ($errors !== []) {
            self::renderForm($member, ['name' => $name, 'bio' => $bio], $levels, $errors);
            return;
        }

        MemberRepository::update($id, $name, $bio, $avatar, $levels);

        if ($avatar !== null && !empty($member['avatar_path'])) {
            MemberProfileForm::deleteAvatarFile((string)$member['avatar_path']);
        }

        flash('保存しました。');
        redirect('/mypage');
    }

    // ------------------------------------------------------------------

    /** @see AuthController::viewData() 対になる導線 (管理者用ログインへ) */
    private static function viewData(string $loginId, ?string $error): array
    {
        return [
            'error'           => $error,
            'loginId'         => $loginId,
            'actionUrl'       => '/mypage/login',
            'subtitle'        => '自分の情報を編集します',
            'otherLoginUrl'   => '/login',
            'otherLoginLabel' => '管理者の方はこちら',
        ];
    }

    private static function renderForm(array $member, array $values, array $levels, array $errors): void
    {
        view('members/form', [
            'member'       => $member,
            'values'       => $values,
            'levels'       => $levels,
            'errors'       => $errors,
            'categories'   => SkillRepository::categoriesWithSkills(),
            // ログイン情報欄・削除ボタンは管理者専用 (isAdmin=false で非表示にする)
            'isAdmin'      => false,
            'actionUrl'    => '/mypage/edit',
            'backUrl'      => '/mypage',
            'deleteUrl'    => null,
            'loginIdValue' => '',
        ], '自分の情報を編集');
    }
}
