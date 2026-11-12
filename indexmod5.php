<?php
require __DIR__ . '/../../includes/init.php';
require_perm('reports');

$year = (int)get('year', date('Y'));
$years = q("SELECT DISTINCT YEAR(consultation_date) AS y FROM consultations
            UNION SELECT YEAR(date_given) FROM vaccinations UNION SELECT YEAR(CURDATE()) ORDER BY 1 DESC")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($year, array_map('intval', $years), true)) $year = (int)date('Y');
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** Fill a 12-month array from rows of [m => month number, n => value]. */
function by_month(array $rows): array
{
    $out = array_fill(0, 12, 0);
    foreach ($rows as $r) {
        $out[(int)$r['m'] - 1] = (int)$r['n'];
    }
    return $out;
}


<?php require __DIR__ . '/../../includes/footer.php'; ?>

}
