<?php
require __DIR__ . '/../../includes/init.php';
require_perm('maternal');

$status = get('status', 'ongoing');
$risk = get('risk');
$search = get('q');

$where = ['1=1'];
$params = [];
if ($status !== '') { $where[] = 'p.status = ?'; $params[] = $status; }
if ($risk !== '')   { $where[] = 'p.risk_level = ?'; $params[] = $risk; }
if ($search !== '') {
    $where[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR CONCAT(r.first_name, ' ', r.last_name) LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
$w = implode(' AND ', $where);
$rows = q("SELECT p.*, r.first_name, r.middle_name, r.last_name, r.suffix, r.birthdate, r.contact_no,
                  (SELECT MAX(visit_date) FROM prenatal_visits pv WHERE pv.pregnancy_id = p.pregnancy_id) AS last_visit,
                  (SELECT next_visit FROM prenatal_visits pv WHERE pv.pregnancy_id = p.pregnancy_id ORDER BY visit_date DESC LIMIT 1) AS next_visit,
                  (SELECT COUNT(*) FROM prenatal_visits pv WHERE pv.pregnancy_id = p.pregnancy_id) AS visits
           FROM pregnancies p JOIN residents r ON r.resident_id = p.resident_id
           WHERE $w ORDER BY (p.status = 'ongoing') DESC, p.edd", $params)->fetchAll();

$stats = q('SELECT * FROM v_maternal_child_stats')->fetch();

$page_title = 'Maternal Health';
require __DIR__ . '/../../includes/header.php';
?>
<div class="stats">
    <div class="stat purple"><div class="label">Ongoing pregnancies</div><div class="value"><?= (int)$stats['ongoing_pregnancies'] ?></div></div>
    <div class="stat red"><div class="label">High-risk</div><div class="value"><?= (int)$stats['high_risk_pregnancies'] ?></div></div>
    <div class="stat"><div class="label">Deliveries this year</div><div class="value"><?= (int)$stats['deliveries_this_year'] ?></div></div>
    <div class="stat blue"><div class="label">Prenatal visits this year</div><div class="value"><?= (int)$stats['prenatal_visits_this_year'] ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Pregnancy registry</h3>
        <?php if (can('maternal', 'edit')): ?><a class="btn btn-primary" href="pregnancy_form.php">+ Register pregnancy</a><?php endif; ?>
    </div>
    <form class="toolbar" method="get">
        <div class="grow"><label>Search</label><input type="search" name="q" value="<?= e($search) ?>" placeholder="Mother's name"></div>
        <div><label>Status</label><select name="status"><?= options(['ongoing', 'delivered', 'miscarriage', 'transferred'], $status, 'All') ?></select></div>
        <div><label>Risk</label><select name="risk"><?= options(['Low', 'Moderate', 'High'], $risk, 'All') ?></select></div>
        <button class="btn">Filter</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Mother</th><th>Age</th><th>G / P</th><th>LMP</th><th>EDD</th><th>AOG</th><th>Risk</th><th class="num">Visits</th><th>Next visit</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p):
            $ongoing = $p['status'] === 'ongoing';
            $missed = $ongoing && $p['next_visit'] && $p['next_visit'] < today(); ?>
            <tr class="<?= $p['risk_level'] === 'High' && $ongoing ? 'row-danger' : '' ?>">
                <td><a href="pregnancy_view.php?id=<?= (int)$p['pregnancy_id'] ?>"><strong><?= e(full_name($p)) ?></strong></a>
                    <div class="small muted"><?= e($p['contact_no']) ?></div></td>
                <td><?= age_years($p['birthdate']) ?></td>
                <td>G<?= (int)$p['gravida'] ?> P<?= (int)$p['para'] ?></td>
                <td class="nowrap"><?= fmt_date($p['lmp']) ?></td>
                <td class="nowrap"><?= fmt_date($p['edd']) ?></td>
                <td class="nowrap"><?= $ongoing ? aog_weeks($p['lmp']) . ' wks' : '—' ?></td>
                <td><?= badge($p['risk_level']) ?></td>
                <td class="num"><?= (int)$p['visits'] ?></td>
                <td class="nowrap <?= $missed ? 'text-danger' : '' ?>"><?= $ongoing ? fmt_date($p['next_visit']) . ($missed ? ' (missed)' : '') : '—' ?></td>
                <td><?= badge($p['status']) ?></td>
                <td class="actions"><a class="btn btn-sm" href="pregnancy_view.php?id=<?= (int)$p['pregnancy_id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="11" class="empty">No pregnancies found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
