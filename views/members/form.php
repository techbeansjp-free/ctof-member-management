<?php
$isEdit = $member !== null;
$action = $isEdit ? '/members/' . (int)$member['id'] : '/members';
$back   = $isEdit ? '/members/' . (int)$member['id'] : '/';
?>
<a class="back" href="<?= e($back) ?>">← 戻る</a>

<form class="editor" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
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
    <a class="btn btn-quiet" href="<?= e($back) ?>">やめる</a>
  </div>
</form>

<?php if ($isEdit): ?>
  <details class="remove">
    <summary>このメンバーを削除する</summary>
    <div class="remove-body">
      <p><?= e($member['name']) ?> と登録済みのスキルをすべて削除します。元に戻せません。</p>
      <form method="post" action="/members/<?= (int)$member['id'] ?>/delete">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-mark">削除する</button>
      </form>
    </div>
  </details>
<?php endif; ?>
