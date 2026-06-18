<?php
require_once 'db.php';
$db = getDB();

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'views';
$selected_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$student_report   = [];
$student_info     = null;

if($selected_student) {
    $si = sqlsrv_query($db, "SELECT *, dbo.GetStudentTotalPaid(student_id) AS total_paid FROM students WHERE student_id=?", [$selected_student]);
    if($si) $student_info = sqlsrv_fetch_array($si, SQLSRV_FETCH_ASSOC);

    $proc_stmt = sqlsrv_query($db, "EXEC StudentReport ?", [$selected_student]);
    if($proc_stmt !== false) {
        while($row = sqlsrv_fetch_array($proc_stmt, SQLSRV_FETCH_ASSOC)) {
            if($row['enroll_date'] instanceof DateTime) {
                $row['enroll_date'] = $row['enroll_date']->format('Y-m-d');
            }
            $student_report[] = $row;
        }
    }
}

$students_stmt = sqlsrv_query($db, "SELECT * FROM students WHERE status='active' ORDER BY full_name");
$students_rows = [];
while($row = sqlsrv_fetch_array($students_stmt, SQLSRV_FETCH_ASSOC)) $students_rows[] = $row;

include 'header.php';

// Helper to run query and return all rows
function fetchAll($db, $sql, $params=[]) {
    $stmt = empty($params) ? sqlsrv_query($db, $sql) : sqlsrv_query($db, $sql, $params);
    if(!$stmt) return [];
    $rows = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
    return $rows;
}
function fetchOne($db, $sql, $params=[]) {
    $rows = fetchAll($db, $sql, $params);
    return $rows[0] ?? null;
}
?>
<div class="main">
<div class="topbar"><h1>📊 Reports, Views & BI</h1></div>

<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
<?php
$tabs=['views'=>'🗂️ Database Views','bi'=>'📈 BI Queries','student'=>'🎓 Student Report (Cursor)','revenue'=>'💰 Revenue'];
foreach($tabs as $key=>$label):?>
<a href="?tab=<?=$key?>" style="padding:9px 18px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;background:<?=$tab==$key?'#1a237e':'#fff'?>;color:<?=$tab==$key?'#fff':'#1a237e'?>;border:2px solid #1a237e;"><?=$label?></a>
<?php endforeach;?>
</div>

<?php if($tab==='views'): ?>
<div style="background:#e8eaf6;border-left:4px solid #1a237e;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:13px;">
<strong>ℹ️ Database Views</strong> are saved SQL SELECT queries stored as virtual tables. Querying a view always returns live, up-to-date data from the underlying tables.
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">VIEW 1: vw_enrollment_details</span><code style="font-size:12px;background:#f0f0f0;padding:3px 8px;border-radius:4px;">SELECT * FROM vw_enrollment_details</code></div>
<p style="font-size:13px;color:#555;margin-bottom:12px;">Joins students, classes, teachers and payments. Shows complete enrollment overview including balance due.</p>
<div style="overflow-x:auto;"><table>
<thead><tr><th>Enroll#</th><th>Student</th><th>Grade</th><th>Class</th><th>Teacher</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
<tbody>
<?php foreach(fetchAll($db, "SELECT * FROM vw_enrollment_details ORDER BY enroll_id") as $r): ?>
<tr>
<td><?=$r['enroll_id']?></td>
<td><strong><?=htmlspecialchars($r['student_name'])?></strong><br><small style="color:#888;"><?=$r['student_phone']?></small></td>
<td><?=$r['student_grade']?></td>
<td><?=htmlspecialchars($r['class_name'])?><br><small style="color:#888;"><?=$r['subject']?></small></td>
<td><?=htmlspecialchars($r['teacher_name']??'-')?></td>
<td>Rs. <?=number_format($r['class_fee'],2)?></td>
<td style="color:#2e7d32;font-weight:600;">Rs. <?=number_format($r['total_paid'],2)?></td>
<td style="color:<?=$r['balance_due']>0?'#c62828':'#2e7d32'?>;font-weight:600;">Rs. <?=number_format($r['balance_due'],2)?></td>
<td><span class="badge badge-<?=$r['payment_status']?>"><?=strtoupper($r['payment_status'])?></span></td>
</tr>
<?php endforeach;?>
</tbody></table></div>
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">VIEW 2: vw_class_summary</span><code style="font-size:12px;background:#f0f0f0;padding:3px 8px;border-radius:4px;">SELECT * FROM vw_class_summary</code></div>
<p style="font-size:13px;color:#555;margin-bottom:12px;">Per-class stats: enrollment count, capacity utilisation %, revenue collected vs potential revenue.</p>
<table><thead><tr><th>Class</th><th>Teacher</th><th>Fee</th><th>Enrolled</th><th>Capacity</th><th>Fill %</th><th>Revenue</th><th>Potential</th></tr></thead><tbody>
<?php foreach(fetchAll($db, "SELECT * FROM vw_class_summary WHERE status='active' ORDER BY capacity_used_pct DESC") as $r):
$pct=$r['capacity_used_pct'];$bc=$pct>=80?'#c62828':($pct>=50?'#f57f17':'#2e7d32');?>
<tr>
<td><strong><?=htmlspecialchars($r['class_name'])?></strong><br><small style="color:#888;"><?=$r['subject']?></small></td>
<td><?=htmlspecialchars($r['teacher_name']??'-')?></td>
<td>Rs. <?=number_format($r['monthly_fee'],2)?></td>
<td><?=$r['enrolled_count']?></td><td><?=$r['capacity']?></td>
<td><div style="font-weight:600;color:<?=$bc?>"><?=$pct?>%</div><div style="background:#eee;border-radius:3px;height:6px;width:80px;"><div style="background:<?=$bc?>;width:<?=min($pct,100)?>%;height:6px;border-radius:3px;"></div></div></td>
<td style="color:#2e7d32;font-weight:600;">Rs. <?=number_format($r['revenue_collected'],2)?></td>
<td style="color:#555;">Rs. <?=number_format($r['potential_revenue'],2)?></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">VIEW 3: vw_payment_status_summary</span><code style="font-size:12px;background:#f0f0f0;padding:3px 8px;border-radius:4px;">SELECT * FROM vw_payment_status_summary</code></div>
<p style="font-size:13px;color:#555;margin-bottom:12px;">Per-student payment health: paid/pending/overdue counts and outstanding balance.</p>
<table><thead><tr><th>Student</th><th>Grade</th><th>Classes</th><th>Paid</th><th>Pending</th><th>Overdue</th><th>Total Paid</th><th>Total Due</th><th>Outstanding</th></tr></thead><tbody>
<?php foreach(fetchAll($db, "SELECT * FROM vw_payment_status_summary ORDER BY outstanding_balance DESC") as $r): ?>
<tr>
<td><strong><?=htmlspecialchars($r['student_name'])?></strong><br><small style="color:#888;"><?=$r['phone']?></small></td>
<td><?=$r['grade']?></td><td><?=$r['total_classes']?></td>
<td style="color:#2e7d32;font-weight:600;"><?=$r['paid_count']?></td>
<td style="color:#f57f17;font-weight:600;"><?=$r['pending_count']?></td>
<td style="color:#c62828;font-weight:600;"><?=$r['overdue_count']?></td>
<td>Rs. <?=number_format($r['total_paid'],2)?></td>
<td>Rs. <?=number_format($r['total_fees_due'],2)?></td>
<td style="color:<?=$r['outstanding_balance']>0?'#c62828':'#2e7d32'?>;font-weight:700;">Rs. <?=number_format($r['outstanding_balance'],2)?></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>

<div class="card">
<div class="card-header"><span class="card-title">VIEW 4: vw_teacher_workload</span><code style="font-size:12px;background:#f0f0f0;padding:3px 8px;border-radius:4px;">SELECT * FROM vw_teacher_workload</code></div>
<p style="font-size:13px;color:#555;margin-bottom:12px;">Per-teacher stats: classes, students taught, revenue generated and revenue-to-salary ratio.</p>
<table><thead><tr><th>Teacher</th><th>Subject</th><th>Salary</th><th>Classes</th><th>Students</th><th>Revenue</th><th>Rev/Salary</th></tr></thead><tbody>
<?php foreach(fetchAll($db, "SELECT * FROM vw_teacher_workload ORDER BY revenue_generated DESC") as $r): ?>
<tr>
<td><strong><?=htmlspecialchars($r['teacher_name'])?></strong></td>
<td><?=$r['subject']?></td>
<td>Rs. <?=number_format($r['salary'],2)?></td>
<td><?=$r['classes_handled']?></td><td><?=$r['students_taught']?></td>
<td style="color:#2e7d32;font-weight:600;">Rs. <?=number_format($r['revenue_generated'],2)?></td>
<td style="font-weight:700;color:<?=$r['revenue_to_salary_ratio']>=1?'#2e7d32':'#c62828'?>"><?=$r['revenue_to_salary_ratio']?>x</td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>

<?php elseif($tab==='bi'): ?>
<div style="background:#e8f5e9;border-left:4px solid #2e7d32;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:13px;">
<strong>ℹ️ Business Intelligence Queries</strong> use advanced SQL: window functions (SUM OVER, DENSE_RANK), CASE expressions, and HAVING clauses to produce management insights.
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">📅 BI 1: Monthly Revenue Trend</span><small style="color:#888;">Window: SUM() OVER(ORDER BY month)</small></div>
<table><thead><tr><th>Month</th><th>Transactions</th><th>Monthly Revenue</th><th>Avg Payment</th><th>Cumulative Revenue</th></tr></thead><tbody>
<?php foreach(fetchAll($db, "
    SELECT FORMAT(payment_date,'MMMM yyyy') AS ml,
           COUNT(payment_id) AS t,
           SUM(amount) AS rev,
           ROUND(AVG(amount),2) AS avg_p,
           SUM(SUM(amount)) OVER(ORDER BY FORMAT(payment_date,'yyyy-MM')) AS cum
    FROM payments
    GROUP BY FORMAT(payment_date,'yyyy-MM'), FORMAT(payment_date,'MMMM yyyy')
    ORDER BY FORMAT(payment_date,'yyyy-MM')
") as $r): ?>
<tr><td><strong><?=$r['ml']?></strong></td><td><?=$r['t']?></td>
<td style="color:#2e7d32;font-weight:600;">Rs. <?=number_format($r['rev'],2)?></td>
<td>Rs. <?=number_format($r['avg_p'],2)?></td>
<td style="color:#1a237e;font-weight:700;">Rs. <?=number_format($r['cum'],2)?></td></tr>
<?php endforeach;?>
</tbody></table>
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">📚 BI 2: Subject Performance Analysis</span><small style="color:#888;">CASE demand classification</small></div>
<table><thead><tr><th>Subject</th><th>Classes</th><th>Enrollments</th><th>Fill Rate</th><th>Revenue</th><th>Rev/Student</th><th>Demand</th></tr></thead><tbody>
<?php
$dc=['HIGH DEMAND'=>'#2e7d32','MODERATE'=>'#f57f17','LOW DEMAND'=>'#c62828'];
foreach(fetchAll($db, "
    SELECT c.subject,
           COUNT(DISTINCT c.class_id) AS nc,
           COUNT(DISTINCT e.enroll_id) AS ne,
           ROUND(CAST(COUNT(DISTINCT e.enroll_id) AS FLOAT)/NULLIF(SUM(c.max_students),0)*100,1) AS fp,
           COALESCE(SUM(p.amount),0) AS rev,
           ROUND(COALESCE(SUM(p.amount),0)/NULLIF(COUNT(DISTINCT e.enroll_id),0),2) AS rps,
           CASE
               WHEN ROUND(CAST(COUNT(DISTINCT e.enroll_id) AS FLOAT)/NULLIF(SUM(c.max_students),0)*100,1)>=80 THEN 'HIGH DEMAND'
               WHEN ROUND(CAST(COUNT(DISTINCT e.enroll_id) AS FLOAT)/NULLIF(SUM(c.max_students),0)*100,1)>=50 THEN 'MODERATE'
               ELSE 'LOW DEMAND'
           END AS dl
    FROM classes c
    LEFT JOIN enrollments e ON c.class_id=e.class_id
    LEFT JOIN payments p ON e.enroll_id=p.enroll_id
    GROUP BY c.subject
    ORDER BY rev DESC
") as $r): ?>
<tr><td><strong><?=$r['subject']?></strong></td><td><?=$r['nc']?></td><td><?=$r['ne']?></td><td><?=$r['fp']?>%</td>
<td style="color:#2e7d32;font-weight:600;">Rs. <?=number_format($r['rev'],2)?></td>
<td>Rs. <?=number_format($r['rps'],2)?></td>
<td><span class="badge" style="background:<?=$dc[$r['dl']]?>22;color:<?=$dc[$r['dl']]?>"><?=$r['dl']?></span></td></tr>
<?php endforeach;?>
</tbody></table>
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">📊 BI 3: Class Capacity Utilisation</span><small style="color:#888;">Resource planning — CASE status labels</small></div>
<table><thead><tr><th>Class</th><th>Teacher</th><th>Capacity</th><th>Enrolled</th><th>Free Seats</th><th>Utilisation</th><th>Status</th></tr></thead><tbody>
<?php
$sc=['FULL'=>'#c62828','NEAR FULL'=>'#e65100','HEALTHY'=>'#2e7d32','UNDERUTILISED'=>'#f57f17','EMPTY'=>'#757575'];
foreach(fetchAll($db, "
    SELECT c.class_name, c.subject, t.full_name AS teacher, c.max_students,
           COUNT(e.enroll_id) AS enrolled,
           (c.max_students - COUNT(e.enroll_id)) AS free_seats,
           ROUND(CAST(COUNT(e.enroll_id) AS FLOAT)/c.max_students*100,1) AS up,
           CASE
               WHEN COUNT(e.enroll_id)>=c.max_students THEN 'FULL'
               WHEN COUNT(e.enroll_id)>=c.max_students*0.8 THEN 'NEAR FULL'
               WHEN COUNT(e.enroll_id)>=c.max_students*0.5 THEN 'HEALTHY'
               WHEN COUNT(e.enroll_id)>0 THEN 'UNDERUTILISED'
               ELSE 'EMPTY'
           END AS cs
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id=t.teacher_id
    LEFT JOIN enrollments e ON c.class_id=e.class_id
    WHERE c.status='active'
    GROUP BY c.class_id, c.class_name, c.subject, t.full_name, c.max_students
    ORDER BY up DESC
") as $r): ?>
<tr>
<td><strong><?=htmlspecialchars($r['class_name'])?></strong><br><small style="color:#888;"><?=$r['subject']?></small></td>
<td><?=htmlspecialchars($r['teacher']??'-')?></td>
<td><?=$r['max_students']?></td><td><?=$r['enrolled']?></td><td><?=$r['free_seats']?></td>
<td><strong><?=$r['up']?>%</strong><div style="background:#eee;border-radius:3px;height:6px;width:80px;margin-top:3px;"><div style="background:#1a237e;width:<?=min($r['up'],100)?>%;height:6px;border-radius:3px;"></div></div></td>
<td><span class="badge" style="background:<?=$sc[$r['cs']]?>22;color:<?=$sc[$r['cs']]?>"><?=$r['cs']?></span></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>

<div class="card" style="margin-bottom:20px;">
<div class="card-header"><span class="card-title">⚠️ BI 4: Outstanding Payments</span><small style="color:#888;">HAVING clause — collections priority list</small></div>
<?php $outstanding = fetchAll($db, "
    SELECT s.full_name, s.phone, s.grade, c.class_name, c.fee,
           COALESCE(SUM(p.amount),0) AS paid,
           (c.fee - COALESCE(SUM(p.amount),0)) AS balance,
           e.payment_status,
           DATEDIFF(DAY, e.enroll_date, GETDATE()) AS days_old
    FROM enrollments e
    JOIN students s ON e.student_id=s.student_id
    JOIN classes c ON e.class_id=c.class_id
    LEFT JOIN payments p ON e.enroll_id=p.enroll_id
    WHERE e.payment_status IN('pending','overdue')
    GROUP BY s.full_name,s.phone,s.grade,c.class_name,c.fee,e.payment_status,e.enroll_date,e.enroll_id
    HAVING (c.fee - COALESCE(SUM(p.amount),0)) > 0
    ORDER BY balance DESC
");
if(empty($outstanding)): ?>
<p style="text-align:center;color:#2e7d32;padding:20px;font-weight:600;">✅ No outstanding payments!</p>
<?php else: ?>
<table><thead><tr><th>Student</th><th>Grade</th><th>Class</th><th>Fee</th><th>Paid</th><th>Balance Owed</th><th>Status</th><th>Days</th></tr></thead><tbody>
<?php foreach($outstanding as $r): ?>
<tr>
<td><strong><?=htmlspecialchars($r['full_name'])?></strong><br><small style="color:#888;">📞 <?=$r['phone']?></small></td>
<td><?=$r['grade']?></td><td><?=htmlspecialchars($r['class_name'])?></td>
<td>Rs. <?=number_format($r['fee'],2)?></td>
<td style="color:#2e7d32;">Rs. <?=number_format($r['paid'],2)?></td>
<td style="color:#c62828;font-weight:700;">Rs. <?=number_format($r['balance'],2)?></td>
<td><span class="badge badge-<?=$r['payment_status']?>"><?=strtoupper($r['payment_status'])?></span></td>
<td><?=$r['days_old']?>d</td>
</tr>
<?php endforeach;?>
</tbody></table>
<?php endif;?>
</div>

<div class="card">
<div class="card-header"><span class="card-title">🏆 BI 5: Teacher Performance Ranking</span><small style="color:#888;">Window: DENSE_RANK() OVER()</small></div>
<table><thead><tr><th>Rank</th><th>Teacher</th><th>Subject</th><th>Salary</th><th>Classes</th><th>Students</th><th>Revenue</th><th>Rev/Salary</th></tr></thead><tbody>
<?php
$medals=['🥇','🥈','🥉'];
foreach(fetchAll($db, "
    SELECT t.full_name, t.subject, t.salary,
           COUNT(DISTINCT c.class_id) AS cls,
           COUNT(DISTINCT e.enroll_id) AS stu,
           COALESCE(SUM(p.amount),0) AS rev,
           ROUND(COALESCE(SUM(p.amount),0)/NULLIF(t.salary,0),2) AS ratio,
           DENSE_RANK() OVER(ORDER BY COALESCE(SUM(p.amount),0) DESC) AS rnk
    FROM teachers t
    LEFT JOIN classes c ON t.teacher_id=c.teacher_id
    LEFT JOIN enrollments e ON c.class_id=e.class_id
    LEFT JOIN payments p ON e.enroll_id=p.enroll_id
    WHERE t.status='active'
    GROUP BY t.teacher_id, t.full_name, t.subject, t.salary
    ORDER BY rev DESC
") as $r): ?>
<tr>
<td style="font-size:20px;text-align:center;"><?=$medals[$r['rnk']-1]??'#'.$r['rnk']?></td>
<td><strong><?=htmlspecialchars($r['full_name'])?></strong></td>
<td><?=$r['subject']?></td>
<td>Rs. <?=number_format($r['salary'],2)?></td>
<td><?=$r['cls']?></td><td><?=$r['stu']?></td>
<td style="color:#2e7d32;font-weight:700;">Rs. <?=number_format($r['rev'],2)?></td>
<td style="font-weight:700;color:<?=$r['ratio']>=1?'#2e7d32':'#c62828'?>"><?=$r['ratio']?>x</td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>

<?php elseif($tab==='student'): ?>
<div class="card">
<div class="card-header"><span class="card-title">🎓 Student Report — CURSOR Stored Procedure</span></div>
<div style="background:#e8eaf6;padding:12px;border-radius:6px;margin-bottom:16px;font-size:13px;">
<strong>⚡ EXEC StudentReport @student_id</strong> — uses a CURSOR internally to loop through all enrollments for the selected student.
</div>
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;">
<input type="hidden" name="tab" value="student">
<select name="student_id" style="flex:1;padding:9px;border:1px solid #ddd;border-radius:6px;">
<option value="">-- Select a Student --</option>
<?php foreach($students_rows as $s): ?>
<option value="<?=$s['student_id']?>" <?=$selected_student==$s['student_id']?'selected':''?>><?=htmlspecialchars($s['full_name'])?> (<?=$s['grade']?>)</option>
<?php endforeach;?>
</select>
<button type="submit" class="btn btn-primary">Generate Report</button>
</form>
<?php if($student_info): ?>
<div style="background:#e8eaf6;padding:14px;border-radius:8px;margin-bottom:16px;">
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
<div><strong><?=htmlspecialchars($student_info['full_name'])?></strong><br><small><?=$student_info['grade']?></small></div>
<div>📞 <?=$student_info['phone']?><br><small><?=$student_info['email']?></small></div>
<div style="text-align:right;"><div style="font-size:22px;font-weight:700;color:#2e7d32;">Rs. <?=number_format($student_info['total_paid'],2)?></div><small style="color:#888;">GetStudentTotalPaid() function</small></div>
</div></div>
<?php if(count($student_report)): ?>
<table><thead><tr><th>Class</th><th>Subject</th><th>Schedule</th><th>Fee</th><th>Status</th><th>Enrolled</th></tr></thead><tbody>
<?php foreach($student_report as $r): ?>
<tr><td><strong><?=htmlspecialchars($r['class_name'])?></strong></td><td><?=$r['subject']?></td><td><?=$r['schedule']?></td>
<td>Rs. <?=number_format($r['fee'],2)?></td>
<td><span class="badge badge-<?=$r['payment_status']?>"><?=strtoupper($r['payment_status'])?></span></td>
<td><?=$r['enroll_date'] instanceof DateTime ? $r['enroll_date']->format('Y-m-d') : $r['enroll_date']?></td></tr>
<?php endforeach;?>
</tbody></table>
<?php else: ?><p style="color:#888;text-align:center;padding:20px;">No enrollments for this student.</p><?php endif;?>
<?php else: ?><p style="text-align:center;color:#aaa;padding:40px;">Select a student above to generate their report.</p><?php endif;?>
</div>

<?php elseif($tab==='revenue'): ?>
<?php
$stats = fetchOne($db, "
    SELECT
        (SELECT COALESCE(SUM(amount),0) FROM payments) AS tr,
        (SELECT COUNT(*) FROM enrollments WHERE payment_status='paid') AS pc,
        (SELECT COUNT(*) FROM enrollments WHERE payment_status='pending') AS pnc,
        (SELECT COUNT(*) FROM enrollments WHERE payment_status='overdue') AS oc
");
$mr_row = fetchOne($db, "SELECT COALESCE(SUM(amount),0) AS m FROM payments");
$mr = $mr_row['m'];
?>
<div class="stats-grid">
<div class="stat-card green"><div class="stat-number">Rs. <?=number_format($stats['tr'],0)?></div><div class="stat-label">💰 Total Revenue</div></div>
<div class="stat-card"><div class="stat-number"><?=$stats['pc']?></div><div class="stat-label">✅ Paid</div></div>
<div class="stat-card orange"><div class="stat-number"><?=$stats['pnc']?></div><div class="stat-label">⏳ Pending</div></div>
<div class="stat-card purple"><div class="stat-number"><?=$stats['oc']?></div><div class="stat-label">⚠️ Overdue</div></div>
</div>
<div class="card">
<div class="card-header"><span class="card-title">💰 Revenue by Subject — GROUP BY query</span></div>
<table><thead><tr><th>Subject</th><th>Classes</th><th>Enrollments</th><th>Revenue</th><th>Revenue Bar</th></tr></thead><tbody>
<?php foreach(fetchAll($db, "
    SELECT c.subject, COUNT(DISTINCT c.class_id) AS cls, COUNT(e.enroll_id) AS enrl, COALESCE(SUM(p.amount),0) AS rev
    FROM classes c
    LEFT JOIN enrollments e ON c.class_id=e.class_id
    LEFT JOIN payments p ON e.enroll_id=p.enroll_id
    GROUP BY c.subject
    ORDER BY rev DESC
") as $r):
$pct = $mr > 0 ? ($r['rev']/$mr*100) : 0; ?>
<tr><td><strong><?=$r['subject']?></strong></td><td><?=$r['cls']?></td><td><?=$r['enrl']?></td>
<td style="color:#2e7d32;font-weight:700;">Rs. <?=number_format($r['rev'],2)?></td>
<td style="width:200px;"><div style="background:#eee;border-radius:4px;height:10px;"><div style="background:#1a237e;width:<?=round($pct)?>%;height:10px;border-radius:4px;"></div></div><small><?=round($pct)?>%</small></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div>
<?php endif;?>
</div>
</body></html>