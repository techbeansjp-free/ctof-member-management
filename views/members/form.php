<?php
// 管理者 (MemberController) と本人 (MyPageController) の両方から使う共有ビュー。
// isAdmin=false のときはログイン情報欄と削除ボタンを出さない (04_改修_メンバー個人ログイン.md)。
$isEdit = $member !== null;
?>
<a class="back" href="<?= e($backUrl) ?>">← 戻る</a>

<form class="editor" method="post" action="<?= e($actionUrl) ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <h1><?= $isEdit ? e($member['name']) . ' を編集' : 'メンバーを追加' ?></h1>

  <?php if ($errors !== []): ?>
    <ul class="alert" style="margin-top:0">
      <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="two-up">
    <div class="field">
      <label for="name">名前<span class="need">必須</span></label>
      <input id="name" name="name" type="text" maxlength="100" required
             value="<?= e((string)($values['name'] ?? '')) ?>">
    </div>

    <div class="field">
      <span class="flabel">アイコン</span>
      <div class="avatar-row">
        <?= avatar_html($isEdit ? $member : ['name' => (string)($values['name'] ?? '?')], 40) ?>
        <div>
          <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
          <p class="note">JPEG / PNG / WebP・2MB まで。256px の正方形に縮小して保存します</p>
        </div>
      </div>
    </div>
  </div>

  <div class="field">
    <label for="bio">自己紹介文</label>
    <textarea id="bio" name="bio" rows="3" maxlength="500"
              placeholder="得意分野、任せてほしい領域など"><?= e((string)($values['bio'] ?? '')) ?></textarea>
    <p class="note">500 文字まで</p>
  </div>

  <?php if ($isAdmin): ?>
    <div class="field">
      <span class="flabel">ログイン情報 <em>本人が「マイページ」から編集するためのもの</em></span>
      <div class="two-up">
        <div class="field">
          <label for="login_id">ログインID</label>
          <input id="login_id" name="login_id" type="text" maxlength="64"
                 value="<?= e($loginIdValue) ?>" placeholder="空欄のままなら未発行">
        </div>
        <div class="field">
          <label for="password">パスワード</label>
          <input id="password" name="password" type="password" autocomplete="new-password"
                 placeholder="<?= $loginIdValue !== '' ? '変更する場合のみ入力' : '新規発行時は入力必須' ?>">
        </div>
      </div>
      <p class="note">
        パスワードはハッシュ化して保存するため、設定後にこの画面で確認することはできません。
        設定した値は控えて本人に伝えてください。ログインIDを空にして保存すると、発行を取り消します。
      </p>
    </div>
  <?php endif; ?>

  <div class="field">
    <span class="flabel">スキル <em>持っていないものは「−」のまま</em></span>

    <?php foreach ($categories as $c): ?>
      <div class="catblock">
        <h3><?= e($c['name']) ?></h3>
        <div class="lvrows">
          <?php foreach ($c['skills'] as $s): ?>
            <?php
              $sid     = (int)$s['id'];
              $current = $levels[$sid] ?? 0;
            ?>
            <div class="lvrow <?= $current > 0 ? 'is-set' : '' ?> <?= is_latin_token($s['name']) ? 'lvrow-mono' : '' ?>">
              <span class="lvrow-name" title="<?= e($s['name']) ?>"><?= e($s['name']) ?></span>
              <span class="lvpick" role="group" aria-label="<?= e($s['name'] . ' のレベル') ?>">
                <input type="radio" id="s<?= $sid ?>l0" name="skill_level[<?= $sid ?>]" value=""
                       <?= $current === 0 ? 'checked' : '' ?>>
                <label class="none" for="s<?= $sid ?>l0" title="登録しない">−</label>
                <?php foreach (skill_levels() as $lv => $label): ?>
                  <input type="radio" id="s<?= $sid ?>l<?= $lv ?>" name="skill_level[<?= $sid ?>]" value="<?= $lv ?>"
                         <?= $current === $lv ? 'checked' : '' ?>>
                  <label for="s<?= $sid ?>l<?= $lv ?>" title="<?= e($label) ?>"><?= level_meter($lv) ?></label>
                <?php endforeach; ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="editor-actions">
    <button type="submit" class="btn btn-solid"><?= $isEdit ? '変更を保存' : '追加する' ?></button>
    <a class="btn btn-quiet" href="<?= e($backUrl) ?>">やめる</a>
  </div>
</form>

<?php if ($deleteUrl !== null): ?>
  <details class="remove">
    <summary>このメンバーを削除する</summary>
    <div class="remove-body">
      <p><?= e($member['name']) ?> と登録済みのスキルをすべて削除します。元に戻せません。</p>
      <form method="post" action="<?= e($deleteUrl) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-mark">削除する</button>
      </form>
    </div>
  </details>
<?php endif; ?>
