<?php
function get_current_fy(): ?array
{
    $stmt = db()->query('SELECT * FROM fy WHERE now_flag = 1 LIMIT 1');
    $fy = $stmt->fetch();
    return $fy ?: null;
}

function get_fy_list(): array
{
    $stmt = db()->query('SELECT * FROM fy ORDER BY fiscal_years DESC');
    return $stmt->fetchAll();
}

function get_divisions_for_user(array $user): array
{
    if ((int)$user['office_type'] === 4 && !empty($user['division_id'])) {
        $stmt = db()->prepare('SELECT id, office_name FROM divisions WHERE id = ?');
        $stmt->execute([$user['division_id']]);
        return $stmt->fetchAll();
    }

    if ((int)$user['office_type'] === 3 && !empty($user['circle_id'])) {
        $stmt = db()->prepare('SELECT id, office_name FROM divisions WHERE circle_id = ? ORDER BY office_name');
        $stmt->execute([$user['circle_id']]);
        return $stmt->fetchAll();
    }

    if ((int)$user['office_type'] === 2 && !empty($user['zone_id'])) {
        $stmt = db()->prepare('SELECT id, office_name FROM divisions WHERE zone_id = ? ORDER BY office_name');
        $stmt->execute([$user['zone_id']]);
        return $stmt->fetchAll();
    }

    $stmt = db()->query('SELECT id, office_name FROM divisions ORDER BY office_name');
    return $stmt->fetchAll();
}

function get_latest_records(string $table, int $fy_id, ?array $division_ids = null): array
{
    $sql = "SELECT d.office_name, r.*
        FROM {$table} r
        JOIN (
            SELECT division_id, MAX(month_val) AS max_month
            FROM {$table}
            WHERE fy_id = ?
            GROUP BY division_id
        ) m ON r.division_id = m.division_id AND r.month_val = m.max_month
        JOIN (
            SELECT division_id, month_val, MAX(id) AS max_id
            FROM {$table}
            WHERE fy_id = ?
            GROUP BY division_id, month_val
        ) t ON r.id = t.max_id AND r.division_id = t.division_id AND r.month_val = t.month_val
        JOIN divisions d ON d.id = r.division_id";
    $params = [$fy_id, $fy_id];
    if ($division_ids) {
        $in = implode(',', array_fill(0, count($division_ids), '?'));
        $sql .= " WHERE r.division_id IN ({$in})";
        $params = array_merge($params, $division_ids);
    }
    $sql .= ' ORDER BY d.office_name';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_latest_record_for_division(string $table, int $fy_id, int $division_id): ?array
{
    $stmt = db()->prepare("SELECT MAX(month_val) FROM {$table} WHERE fy_id = ? AND division_id = ?");
    $stmt->execute([$fy_id, $division_id]);
    $month_val = $stmt->fetchColumn();
    if ($month_val === false || $month_val === null) {
        return null;
    }
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE fy_id = ? AND division_id = ? AND month_val = ? ORDER BY created_at DESC, id DESC LIMIT 1");
    $stmt->execute([$fy_id, $division_id, (int)$month_val]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fy_months(string $fy): array
{
    $parts = explode('-', $fy);
    $start_year = (int)$parts[0];
    $end_year = $start_year + 1;
    $months = [];

    $start = new DateTime($start_year . '-07-01');
    for ($i = 0; $i < 12; $i++) {
        $current = clone $start;
        $current->modify('+' . $i . ' months');
        $months[] = [
            'label_index' => $i + 1,
            'label' => $current->format('M/y'),
            'start' => $current->format('Y-m-01 00:00:00'),
            'end' => $current->modify('+1 month')->format('Y-m-01 00:00:00'),
        ];
    }
    return $months;
}

function current_month_val_for_fy(string $fy_label): int
{
    $parts = explode('-', $fy_label);
    $start_year = (int)$parts[0];
    $end_year = $start_year + 1;
    $now = new DateTime();
    $year = (int)$now->format('Y');
    $month = (int)$now->format('n');

    if ($year === $start_year && $month >= 7) {
        return $month - 6;
    }
    if ($year === $end_year && $month <= 6) {
        return $month + 6;
    }
    return 1;
}

function is_month_allowed(string $fy_label, int $month_val): bool
{
    if ($month_val < 1 || $month_val > 12) {
        return false;
    }
    $current = current_month_val_for_fy($fy_label);
    return $month_val <= $current;
}

function fy_month_options(string $fy_label): array
{
    $options = [];
    $months = fy_months($fy_label);
    foreach ($months as $index => $month) {
        $options[] = [
            'value' => $index + 1,
            'label' => $month['label'],
        ];
    }
    return $options;
}

function get_monthly_series(string $table, int $fy_id, int $division_id, string $metric, string $fy_label): array
{
    $series = [];
    $last_value = 0.0;
    $current_val = current_month_val_for_fy($fy_label);
    foreach (fy_months($fy_label) as $month) {
        $month_val = (int)$month['label_index'];
        if ($month_val > $current_val) {
            $series[] = [
                'label' => $month['label'],
                'value' => 0,
            ];
            continue;
        }
        $stmt = db()->prepare("SELECT {$metric} FROM {$table} WHERE fy_id = ? AND division_id = ? AND month_val = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$fy_id, $division_id, $month_val]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            $value = $last_value;
        } else {
            $last_value = (float)$value;
        }
        $series[] = [
            'label' => $month['label'],
            'value' => (float)$value,
        ];
    }
    return $series;
}

function get_monthly_series_all(string $table, int $fy_id, array $division_ids, string $metric, string $fy_label): array
{
    $series = [];
    $last_value = 0.0;
    $current_val = current_month_val_for_fy($fy_label);
    foreach (fy_months($fy_label) as $month) {
        $month_val = (int)$month['label_index'];
        if ($month_val > $current_val) {
            $series[] = ['label' => $month['label'], 'value' => 0];
            continue;
        }
        if (!$division_ids) {
            $series[] = ['label' => $month['label'], 'value' => $last_value];
            continue;
        }
        $in = implode(',', array_fill(0, count($division_ids), '?'));
        $sql = "SELECT SUM(t.{$metric}) FROM {$table} t JOIN (
SELECT division_id, MAX(id) AS max_id FROM {$table} WHERE fy_id = ? AND month_val = ? AND division_id IN ({$in}) GROUP BY division_id
) latest ON latest.max_id = t.id";
        $params = array_merge([$fy_id, $month_val], $division_ids);
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        if ($value === false || $value === null) {
            $value = $last_value;
        } else {
            $last_value = (float)$value;
        }
        $series[] = [
            'label' => $month['label'],
            'value' => (float)$value,
        ];
    }
    return $series;
}

function get_monthly_rows(string $table, int $fy_id, int $division_id, string $fy_label): array
{
    $rows = [];
    foreach (fy_months($fy_label) as $month) {
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE fy_id = ? AND division_id = ? AND created_at >= ? AND created_at < ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$fy_id, $division_id, $month['start'], $month['end']]);
        $row = $stmt->fetch();
        if (!$row) {
            $row = [
                'month' => $month['label'],
                'pkg' => 0,
                'est' => 0,
                'pkg_live' => 0,
                'pkg_eval' => 0,
                'pkg_cont' => 0,
                'cont' => 0,
                'note' => '',
            ];
        } else {
            $row['month'] = $month['label'];
        }
        $rows[] = $row;
    }
    return $rows;
}

function insert_record(string $table, array $data): int
{
    $columns = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
    $stmt = db()->prepare($sql);
    $stmt->execute(array_values($data));
    return (int)db()->lastInsertId();
}

function add_log(int $user_id, string $table, int $record_id, string $summary): void
{
    $stmt = db()->prepare('INSERT INTO logs (user_id, table_name, record_id, summary, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$user_id, $table, $record_id, $summary]);
}

function get_logs_for_user(int $user_id, int $limit = 50): array
{
    $stmt = db()->prepare('SELECT * FROM logs WHERE user_id = ? ORDER BY id DESC LIMIT ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_info_row(): ?array
{
    $stmt = db()->query('SELECT * FROM info ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch();
    return $row ?: null;
}

function save_info_row(?string $video_url, ?string $login_message): void
{
    $existing = get_info_row();
    if ($existing) {
        $stmt = db()->prepare('UPDATE info SET video_tutorial_url = ?, login_message = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$video_url, $login_message, (int)$existing['id']]);
        return;
    }
    $stmt = db()->prepare('INSERT INTO info (video_tutorial_url, login_message, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$video_url, $login_message]);
}

function get_office_name_for_user(array $user): string
{
    $office_type = (int)($user['office_type'] ?? 0);
    if ($office_type === 1) {
        return 'Chief Engineer Office';
    }
    if ($office_type === 2 && !empty($user['zone_id'])) {
        $stmt = db()->prepare('SELECT office_name FROM zones WHERE id = ?');
        $stmt->execute([$user['zone_id']]);
        return $stmt->fetchColumn() ?: 'Zone Office';
    }
    if ($office_type === 3 && !empty($user['circle_id'])) {
        $stmt = db()->prepare('SELECT office_name FROM circles WHERE id = ?');
        $stmt->execute([$user['circle_id']]);
        return $stmt->fetchColumn() ?: 'Circle Office';
    }
    if ($office_type === 4 && !empty($user['division_id'])) {
        $stmt = db()->prepare('SELECT office_name FROM divisions WHERE id = ?');
        $stmt->execute([$user['division_id']]);
        return $stmt->fetchColumn() ?: 'Division Office';
    }
    return 'Office';
}
