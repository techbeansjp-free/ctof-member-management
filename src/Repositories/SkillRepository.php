<?php
declare(strict_types=1);

/**
 * スキルマスタは読み取り専用。
 * 画面から増やせない仕様なので、書き込みメソッドを意図的に持たない (02_基本設計.md §10)。
 */
final class SkillRepository
{
    /** カテゴリごとにスキルをまとめて返す */
    public static function categoriesWithSkills(): array
    {
        $rows = Database::pdo()->query(
            'SELECT c.id AS category_id, c.name AS category_name,
                    s.id AS skill_id, s.name AS skill_name
               FROM skill_categories c
               JOIN skills s ON s.category_id = c.id
              ORDER BY c.sort_order, s.sort_order'
        )->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r['category_id'];
            $out[$cid]['id']     = $cid;
            $out[$cid]['name']   = $r['category_name'];
            $out[$cid]['skills'][] = [
                'id'   => (int)$r['skill_id'],
                'name' => $r['skill_name'],
            ];
        }
        return array_values($out);
    }

    /** 入力値の検証に使う。存在するスキル ID の一覧 */
    public static function existingIds(): array
    {
        return array_map(
            'intval',
            Database::pdo()->query('SELECT id FROM skills')->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    /** id => name。絞り込み中のスキル名を表示するために使う */
    public static function namesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT id, name FROM skills WHERE id IN ($ph)");
        foreach ($ids as $i => $v) {
            $stmt->bindValue($i + 1, (int)$v, PDO::PARAM_INT);
        }
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['id']] = $r['name'];
        }
        return $out;
    }
}
