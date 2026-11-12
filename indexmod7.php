<?php
require __DIR__ . '/../../includes/init.php';
require_perm('vaccinations');

$tab = get('tab', 'records') === 'due' ? 'due' : 'records';
$vaccineList = [];
foreach (q('SELECT vaccine_id, vaccine_name FROM vaccines ORDER BY vaccine_name')->fetchAll() as $v) {
    $vaccineList[$v['vaccine_id']] = $v['vaccine_name'];
}

if ($tab === 'records') {
    $search = get('q');
    $vaccine = get('vaccine_id');
    $from = get('from');
    $to = get('to');
    $where = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR CONCAT(r.first_name, ' ', r.last_name) LIKE ? OR v.batch_lot_no LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like);
    }
    if ($vaccine !== '') { $where[] = 'v.vaccine_id = ?'; $params[] = $vaccine; }
    if (valid_date($from)) { $where[] = 'v.date_given >= ?'; $params[] = $from; }
    if (valid_date($to))   { $where[] = 'v.date_given <= ?'; $params[] = $to; }
    $w = implode(' AND ', $where);
    $join = 'FROM vaccinations v JOIN residents r ON r.resident_id = v.resident_id JOIN vaccines vc ON vc.vaccine_id = v.vaccine_id LEFT JOIN users u ON u.user_id = v.administered_by';
    $pg = paginate((int)q("SELECT COUNT(*) $join WHERE $w", $params)->fetchColumn());
    $rows = q("SELECT v.*, vc.vaccine_name, vc.doses_required, r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate, u.full_name AS given_by
               $join WHERE $w ORDER BY v.date_given DESC, v.vaccination_id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params)->fetchAll();
} else {
    $range = (int)get('days', '30');
    if (!in_array($range, [7, 30, 90], true)) $range = 30;
    $rows = q(due_vaccinations_sql() . ' AND v.next_schedule <= CURDATE() + INTERVAL ' . $range . ' DAY ORDER BY v.next_schedule')->fetchAll();
}

$page_title = 'Vaccination Monitoring';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="tabs" style="margin:0;border:0">
            <a href="index.php?tab=records" class="<?= $tab === 'records' ? 'active' : '' ?>">Vaccination records</a>
            <a href="index.php?tab=due" class="<?= $tab === 'due' ? 'active' : '' ?>">Due &amp; overdue doses</a>
        </div>
        <div class="btn-group">
            <?php if (can('vaccinations', 'manage')): ?><a class="btn" href="vaccines.php">Vaccine list</a><?php endif; ?>
            <?php if (can('vaccinations', 'edit')): ?><a class="btn btn-primary" href="form.php">+ Record vaccination</a><?php endif; ?>
        </div>
    </div>


<?php require __DIR__ . '/../../includes/footer.php'; ?>
