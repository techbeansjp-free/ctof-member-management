<?php
$hasFilter = $selectedSkills !== [] || $keyword !== '' || $minLevel > 1;
?>
<div class="layout">

  <aside class="sidebar">
    <form method="get" action="/">
      <div class="field">
        <label for="q">名前で探す</label>
        <input id="q" type="search" name="q" value="<?= e($keyword) ?>" placeholder="例: 田中">
      </div>

      <div class="field">
        <label for="level">レベル下限</label>
        <select id="level" name="level">
          <option value="1" <?= $minLevel === 1 ? 'selected' : '' ?>>すべて</option>
          <option value="2" <?= $minLevel === 2 ? 'selected' : '' ?>>実務経験あり 以上</option>
          <option value="3" <?= $minLevel === 3 ? 'selected' : '' ?>>指導できる</option>
        </select>
        <p class="hint">選択したスキルに対する条件です</p>
      </div>

      <div class="field">
        <span class="field-label">スキル<small>選んだすべてを持つ人を表示</small></span>
        <?php foreach ($categories as $i => $c): ?>
          <?php
            $hasSelected = false;
            foreach ($c['skills'] as $s) {
                if (in_array($s['id'], $selectedSkills, true)) { $hasSelected = true; break; }
            }
          ?>
          <details class="cat" <?= ($hasSelected || $i < 2) ? 'open' : '' ?>>
            <summary><?= e($c['name']) ?></summary>
            <div class="cat-body">
              <?php foreach ($c['skills'] as $s): ?>
                <label class="chk">
                  <input type="checkbox" name="skill[]" value="<?= (int)$s['id'] ?>"
                         <?= in_array($s['id'], $selectedSkills, true) ? 'checked' : '' ?>>
                  <span><?= e($s['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>

      <div class="sidebar-actions">
        <button type="submit" class="btn btn-primary btn-block">絞り込む</button>
        <?php if ($hasFilter): ?>
          <a class="btn btn-block" href="/">条件をクリア</a>
        <?php endif; ?>
      </div>
    </form>
  </aside>

  <section class="results">
    <div class="results-head">
      <h1><?= count($members) ?> 人</h1>
      <?php if ($selectedNames !== []): ?>
        <ul class="chips">
          <?php foreach ($selectedNames as $sid => $sname): ?>
            <li class="chip">
              <?= e($sname) ?>
              <a href="<?= e(url_with(['skill' => array_values(array_diff($selectedSkills, [$sid]))])) ?>"
                 title="この条件を外す">×</a>
            </li>
          <?php endforeach; ?>
          <?php if ($minLevel > 1): ?>
            <li class="chip chip-level"><?= e(level_label($minLevel)) ?> 以上</li>
          <?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($members === []): ?>
      <div class="empty">
        <p>該当するメンバーがいません。</p>
        <p><a class="btn" href="/">条件をクリア</a></p>
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($members as $m): ?>
          <?php $mid = (int)$m['id']; ?>
          <a class="card" href="/members/<?= $mid ?>">
            <div class="card-head">
              <?= avatar_html($m, 36) ?>
              <span class="card-name"><?= e($m['name']) ?></span>
            </div>
            <ul class="badges">
              <?php foreach ($skillsByMember[$mid] ?? [] as $s): ?>
                <li class="badge lv<?= (int)$s['level'] ?>"
                    title="<?= e($s['skill_name'] . ' — ' . level_label($s['level'])) ?>">
                  <?= e($s['skill_name']) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </a>
        <?php endforeach; ?>
      </div>
      <p class="legend">
        <span class="badge lv3">指導できる</span>
        <span class="badge lv2">実務経験あり</span>
        <span class="badge lv1">学習中</span>
      </p>
    <?php endif; ?>
  </section>

</div>
