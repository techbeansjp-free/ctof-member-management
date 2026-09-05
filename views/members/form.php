<?php
$isEdit = $member !== null;
$action = $isEdit ? '/members/' . (int)$member['id'] : '/members';
?>
<p class="breadcrumb">
  <a href="<?= $isEdit ? '/members/' . (int)$member['id'] : '/' ?>">← 戻る</a>
</p>

<h1><?= $isEdit ? e($member['name']) . ' を編集' : 'メンバーを登録' ?></h1>

<?php if ($errors !== []): ?>
  <ul class="error">
    <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
  </ul>
<?php endif; ?>

<form class="member-form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>

  <div class="form-cols">
    <div class="field">
      <label for="name">名前 <span class="req">必須</span></label>
      <input id="name" name="name" type="text" maxlength="100" required
             value="<?= e((string)($values['name'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="avatar">アイコン</label>
      <div class="avatar-field">
        <?= avatar_html($isEdit ? $member : ['name' => (string)($values['name'] ?? '?')], 64) ?>
        <div>
          <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
          <p class="hint">JPEG / PNG / WebP・2MB まで。256px の正方形に縮小して保存します</p>
        </div>
      </div>
    </div>
  </div>

  <div class="field">
    <label for="bio">自己紹介文</label>
    <textarea id="bio" name="bio" rows="4" maxlength="500"
              placeholder="得意分野や、任せてほしい領域など"><?= e((string)($values['bio'] ?? '')) ?></textarea>
    <p class="hint">500 文字まで</p>
  </div>

  <div class="field">
    <span class="field-label">スキル<small>該当しないものは「未登録」のままにしてください</small></span>

    <?php foreach ($categories as $c): ?>
      <fieldset class="skill-fieldset">
        <legend><?= e($c['name']) ?></legend>
        <div class="skill-rows">
          <?php foreach ($c['skills'] as $s): ?>
            <?php $current = $levels[$s['id']] ?? 0; ?>
            <label class="skill-row <?= $current > 0 ? 'is-set' : '' ?>">
              <span class="skill-row-name"><?= e($s['name']) ?></span>
              <select name="skill_level[<?= (int)$s['id'] ?>]">
                <option value="">未登録</option>
                <?php foreach (skill_levels() as $lv => $label): ?>
                  <option value="<?= $lv ?>" <?= $current === $lv ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? '更新する' : '登録する' ?></button>
    <a class="btn" href="<?= $isEdit ? '/members/' . (int)$member['id'] : '/' ?>">キャンセル</a>
  </div>
</form>

<?php if ($isEdit): ?>
  <details class="danger">
    <summary>このメンバーを削除する</summary>
    <div class="danger-body">
      <p><?= e($member['name']) ?> と、登録されているスキルをすべて削除します。元に戻せません。</p>
      <form method="post" action="/members/<?= (int)$member['id'] ?>/delete">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-danger">削除する</button>
      </form>
    </div>
  </details>
<?php endif; ?>
