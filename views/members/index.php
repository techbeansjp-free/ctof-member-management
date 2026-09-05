<?php $filtered = $selectedSkills !== [] || $keyword !== '' || $minLevel > 1; ?>
<div class="workspace">

  <form class="filters" method="get" action="/">
    <div class="filters-group">
      <label class="filters-label" for="q">名前</label>
      <input id="q" type="search" name="q" value="<?= e($keyword) ?>" placeholder="氏名の一部">
    </div>

    <div class="filters-group">
      <label class="filters-label" for="level">下限レベル</label>
      <select id="level" name="level">
        <option value="1" <?= $minLevel === 1 ? 'selected' : '' ?>>指定なし</option>
        <option value="2" <?= $minLevel === 2 ? 'selected' : '' ?>>実務経験あり 以上</option>
        <option value="3" <?= $minLevel === 3 ? 'selected' : '' ?>>指導できる</option>
      </select>
      <ul class="legend">
        <?php foreach (skill_levels() as $lv => $label): ?>
          <li><?= level_meter($lv) ?><?= e($label) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="note">選んだスキルに対する条件です</p>
    </div>

    <div class="filters-group">
      <span class="filters-label">スキル <em>選んだすべてを持つ人</em></span>
      <input type="search" placeholder="スキルを探す" data-filter-target="skill-picker" aria-label="スキルを探す">
      <div id="skill-picker" style="margin-top:8px">
        <?php foreach ($categories as $i => $c): ?>
          <?php
            $hasPicked = false;
            foreach ($c['skills'] as $s) {
                if (in_array($s['id'], $selectedSkills, true)) { $hasPicked = true; break; }
            }
          ?>
          <details class="cat" <?= ($hasPicked || $i < 2) ? 'open' : '' ?>>
            <summary><?= e($c['name']) ?></summary>
            <div class="cat-body">
              <?php foreach ($c['skills'] as $s): ?>
                <label class="pick <?= is_latin_token($s['name']) ? 'pick-mono' : '' ?>"
                       data-name="<?= e($s['name']) ?>">
                  <input type="checkbox" name="skill[]" value="<?= (int)$s['id'] ?>"
                         <?= in_array($s['id'], $selectedSkills, true) ? 'checked' : '' ?>>
                  <span><?= e($s['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="filters-actions">
      <button type="submit" class="btn btn-solid btn-wide">絞り込む</button>
      <?php if ($filtered): ?>
        <a class="btn btn-wide" href="/">条件を外す</a>
      <?php endif; ?>
    </div>
  </form>

  <section class="results">
    <div class="results-bar">
      <span class="count">
        <b><?= count($members) ?></b><span>名</span>
        <?php if ($filtered): ?><em>&nbsp;/ 全 <?= (int)$total ?> 名</em><?php endif; ?>
      </span>
      <?php if ($selectedNames !== [] || $minLevel > 1 || $keyword !== ''): ?>
        <ul class="tags">
          <?php foreach ($selectedNames as $sid => $sname): ?>
            <li class="tag <?= is_latin_token($sname) ? 'tag-mono' : '' ?>">
              <?= e($sname) ?>
              <a href="<?= e(url_with(['skill' => array_values(array_diff($selectedSkills, [$sid]))])) ?>"
                 aria-label="<?= e($sname . ' を条件から外す') ?>">×</a>
            </li>
          <?php endforeach; ?>
          <?php if ($minLevel > 1): ?>
            <li class="tag tag-plain"><?= level_meter($minLevel) ?><?= e(level_label($minLevel)) ?> 以上</li>
          <?php endif; ?>
          <?php if ($keyword !== ''): ?>
            <li class="tag tag-plain">名前: <?= e($keyword) ?></li>
          <?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($members === []): ?>
      <div class="blank">
        <p>条件に合うメンバーはいません。</p>
        <p class="blank-sub">スキルを減らすか、下限レベルを下げると見つかることがあります。</p>
        <a class="btn" href="/">条件を外す</a>
      </div>
    <?php else: ?>
      <div class="roster">
        <?php foreach ($members as $m): ?>
          <?php $mid = (int)$m['id']; ?>
          <a class="entry" href="/members/<?= $mid ?>">
            <div class="entry-head">
              <?= avatar_html($m, 30) ?>
              <span class="entry-name"><?= e($m['name']) ?></span>
            </div>
            <ul class="chips">
              <?php foreach ($skillsByMember[$mid] ?? [] as $s): ?>
                <?= skill_chip($s, in_array((int)$s['skill_id'], $selectedSkills, true)) ?>
              <?php endforeach; ?>
            </ul>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</div>
