<?php
declare(strict_types=1);

final class MemberRepository
{
    /**
     * 一覧の絞り込み (02_基本設計.md §9)。
     *
     * @param int[]  $skillIds 選択されたスキル。すべて持つ人だけを返す (AND 条件)
     * @param int    $minLevel 選択スキルに対するレベル下限。スキル未選択のときは無視される
     * @param string $keyword  氏名の部分一致
     */
    public static function search(array $skillIds, int $minLevel, string $keyword): array
    {
        $params = [];
        $sql = 'SELECT m.id, m.name, m.avatar_path, m.bio FROM members m';

        if ($skillIds !== []) {
            $ph   = implode(',', array_fill(0, count($skillIds), '?'));
            $sql .= " JOIN member_skills ms ON ms.member_id = m.id"
                  . " WHERE ms.skill_id IN ($ph) AND ms.level >= ?";
            foreach ($skillIds as $id) {
                $params[] = (int)$id;
            }
            $params[] = $minLevel;
        } else {
            $sql .= ' WHERE 1 = 1';
        }

        if ($keyword !== '') {
            // LIKE のメタ文字を無効化する。エスケープ文字は | を使う
            $sql .= " AND m.name LIKE ? ESCAPE '|'";
            $params[] = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], $keyword) . '%';
        }

        if ($skillIds !== []) {
            // 選択したスキルを「すべて」持つ人だけ残す。
            // この HAVING を落とすと OR 検索になる。ここが最も間違えやすい。
            $sql .= ' GROUP BY m.id, m.name, m.avatar_path, m.bio'
                  . ' HAVING COUNT(DISTINCT ms.skill_id) = ' . count($skillIds);
        }

        $sql .= ' ORDER BY m.name';

        $stmt = Database::pdo()->prepare($sql);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 複数メンバーのスキルを 1 クエリでまとめて引く。
     * 一覧で N+1 を作らないための入口 (02_基本設計.md §9)。
     *
     * @return array<int, array<int, array>> member_id => スキル配列
     */
    public static function skillsForMembers(array $memberIds): array
    {
        if ($memberIds === []) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT ms.member_id, ms.level,
                    s.id AS skill_id, s.name AS skill_name,
                    c.id AS category_id, c.name AS category_name
               FROM member_skills ms
               JOIN skills s           ON s.id = ms.skill_id
               JOIN skill_categories c ON c.id = s.category_id
              WHERE ms.member_id IN ($ph)
              ORDER BY ms.level DESC, c.sort_order, s.sort_order"
        );
        foreach ($memberIds as $i => $v) {
            $stmt->bindValue($i + 1, (int)$v, PDO::PARAM_INT);
        }
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['member_id']][] = [
                'skill_id'      => (int)$r['skill_id'],
                'skill_name'    => $r['skill_name'],
                'category_id'   => (int)$r['category_id'],
                'category_name' => $r['category_name'],
                'level'         => (int)$r['level'],
            ];
        }
        return $out;
    }

    public static function countAll(): int
    {
        return (int)Database::pdo()->query('SELECT COUNT(*) FROM members')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int,int> skill_id => level */
    public static function levelMap(int $memberId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT skill_id, level FROM member_skills WHERE member_id = ?'
        );
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['skill_id']] = (int)$r['level'];
        }
        return $out;
    }

    /** @param array<int,int> $levels skill_id => level (1-3) */
    public static function create(string $name, ?string $bio, ?string $avatarPath, array $levels): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO members (name, bio, avatar_path) VALUES (?, ?, ?)');
            $stmt->execute([$name, $bio, $avatarPath]);
            $id = (int)$pdo->lastInsertId();
            self::replaceSkills($id, $levels);
            $pdo->commit();
            return $id;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }

    public static function update(int $id, string $name, ?string $bio, ?string $avatarPath, array $levels): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($avatarPath === null) {
                // 画像を差し替えないときは既存の値を維持する
                $stmt = $pdo->prepare('UPDATE members SET name = ?, bio = ? WHERE id = ?');
                $stmt->execute([$name, $bio, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE members SET name = ?, bio = ?, avatar_path = ? WHERE id = ?');
                $stmt->execute([$name, $bio, $avatarPath, $id]);
            }
            self::replaceSkills($id, $levels);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }

    public static function delete(int $id): void
    {
        // member_skills は ON DELETE CASCADE で一緒に消える
        $stmt = Database::pdo()->prepare('DELETE FROM members WHERE id = ?');
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private static function replaceSkills(int $memberId, array $levels): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM member_skills WHERE member_id = ?');
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->execute();

        if ($levels === []) {
            return;
        }
        $ins = $pdo->prepare(
            'INSERT INTO member_skills (member_id, skill_id, level) VALUES (?, ?, ?)'
        );
        foreach ($levels as $skillId => $level) {
            $ins->execute([$memberId, (int)$skillId, (int)$level]);
        }
    }
}
