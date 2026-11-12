<?php
require __DIR__ . '/../../includes/init.php';
require_perm('consultations');

$search = get('q');
$from   = get('from');
$to     = get('to');
$status = get('status');

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR CONCAT(r.first_name, ' ', r.last_name) LIKE ? OR c.chief_complaint LIKE ? OR c.assessment LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if (valid_date($from)) { $where[] = 'DATE(c.consultation_date) >= ?'; $params[] = $from; }
if (valid_date($to))   { $where[] = 'DATE(c.consultation_date) <= ?'; $params[] = $to; }
if ($status !== '')    { $where[] = 'c.status = ?'; $params[] = $status; }
$w = implode(' AND ', $where);

$join = 'FROM consultations c JOIN residents r ON r.resident_id = c.resident_id LEFT JOIN users u ON u.user_id = c.recorded_by';
$pg = paginate((int)q("SELECT COUNT(*) $join WHERE $w", $params)->fetchColumn());
$rows = q("SELECT c.*, r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate, r.sex, u.full_name AS recorder
           $join WHERE $w ORDER BY c.consultation_date DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params)->fetchAll();

$page_title = 'Consultations';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h3>Patient consultations</h3>
        <?php if (can('consultations', 'edit')): ?><a class="btn btn-primary" href="form.php">+ New consultation</a><?php endif; ?>
    </div>
    <form class="toolbar" method="get">
        <div class="grow"><label>Search</label><input type="search" name="q" value="<?= e($search) ?>" placeholder="Patient name, complaint or assessment"></div>
        <div><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
        <div><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
        <div><label>Status</label><select name="status"><?= options(['waiting', 'ongoing', 'completed', 'referred'], $status, 'All') ?></select></div>
        <button class="btn">Filter</button>
        <a class="btn" href="index.php">Reset</a>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Date</th><th>Patient</th><th>Chief complaint</th><th>Assessment</th><th>Recorded by</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <td class="nowrap"><?= fmt_datetime($c['consultation_date']) ?></td>
                <td><a href="<?= e(url('modules/residents/view.php?id=' . $c['resident_id'])) ?>"><?= e(full_name($c)) ?></a>
                    <div class="small muted"><?= e($c['sex']) ?>, <?= e(age_label($c['birthdate'])) ?></div></td>
                <td><?= e($c['chief_complaint']) ?></td>
                <td><?= e($c['assessment'] ?: '—') ?></td>
                <td class="small"><?= e($c['recorder'] ?: '—') ?></td>
                <td><?= badge($c['status']) ?></td>
                <td class="actions">
                    <a class="btn btn-sm" href="view.php?id=<?= (int)$c['consultation_id'] ?>">View</a>
                    <?php if (can('consultations', 'edit')): ?><a class="btn btn-sm" href="form.php?id=<?= (int)$c['consultation_id'] ?>">Edit</a><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty">No consultations found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?= pagination_links($pg) ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
