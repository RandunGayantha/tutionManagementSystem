<?php
require_once 'db.php';
$db = getDB();

// Helper: run query and get first row
function queryFirst($conn, $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

// Get counts for stats
$total_students    = queryFirst($db, "SELECT COUNT(*) as c FROM students WHERE status='active'")['c'];
$total_teachers    = queryFirst($db, "SELECT COUNT(*) as c FROM teachers WHERE status='active'")['c'];
$total_classes     = queryFirst($db, "SELECT COUNT(*) as c FROM classes WHERE status='active'")['c'];
$total_enrollments = queryFirst($db, "SELECT COUNT(*) as c FROM enrollments")['c'];

// Recent enrollments (MSSQL uses TOP instead of LIMIT)
$recent = sqlsrv_query($db, "
    SELECT TOP 6 s.full_name AS student, c.class_name, e.enroll_date, e.payment_status
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN classes  c ON e.class_id   = c.class_id
    ORDER BY e.enroll_date DESC
");

// Payment summary
$pay_stmt = sqlsrv_query($db, "
    SELECT payment_status, COUNT(*) as cnt
    FROM enrollments
    GROUP BY payment_status
");
$pay_summary = [];
while ($row = sqlsrv_fetch_array($pay_stmt, SQLSRV_FETCH_ASSOC)) {
    $pay_summary[] = $row;
}

include 'header.php';
?>

<div class="main">
    <div class="topbar">
        <h1>Dashboard</h1>
        <span class="topbar-date">📅 <?= date('l, d F Y') ?></span>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $total_students ?></div>
            <div class="stat-label">👨‍🎓 Active Students</div>
        </div>
        <div class="stat-card green">
            <div class="stat-number"><?= $total_teachers ?></div>
            <div class="stat-label">👨‍🏫 Teachers</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-number"><?= $total_classes ?></div>
            <div class="stat-label">📖 Active Classes</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-number"><?= $total_enrollments ?></div>
            <div class="stat-label">📋 Total Enrollments</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

        <!-- Recent Enrollments -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Enrollments</span>
                <a href="enrollments.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <table>
                <thead>
                    <tr><th>Student</th><th>Class</th><th>Date</th><th>Payment</th></tr>
                </thead>
                <tbody>
                <?php while($row = sqlsrv_fetch_array($recent, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td><?= $row['enroll_date'] instanceof DateTime ? $row['enroll_date']->format('Y-m-d') : $row['enroll_date'] ?></td>
                    <td><span class="badge badge-<?= $row['payment_status'] ?>"><?= strtoupper($row['payment_status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Payment Summary -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Payment Summary</span>
            </div>
            <?php
            $colors = ['paid'=>'#2e7d32','pending'=>'#f57f17','overdue'=>'#c62828'];
            foreach($pay_summary as $ps):
                $pct = $total_enrollments > 0 ? round($ps['cnt']/$total_enrollments*100) : 0;
            ?>
            <div style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;">
                    <strong><?= strtoupper($ps['payment_status']) ?></strong>
                    <span><?= $ps['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div style="background:#eee;border-radius:4px;height:8px;">
                    <div style="background:<?= $colors[$ps['payment_status']] ?? '#999' ?>;width:<?= $pct ?>%;height:8px;border-radius:4px;"></div>
                </div>
            </div>
            <?php endforeach; ?>

            <hr style="margin:16px 0;border:none;border-top:1px solid #eee;">

            <?php
            $rev_row = queryFirst($db, "SELECT COALESCE(SUM(amount),0) AS total FROM payments");
            $rev = $rev_row['total'];
            ?>
            <div style="text-align:center;">
                <div style="font-size:13px;color:#888;margin-bottom:4px;">Total Revenue Collected</div>
                <div style="font-size:28px;font-weight:700;color:#1a237e;">Rs. <?= number_format($rev,2) ?></div>
            </div>
        </div>
    </div>

    <!-- Classes Overview -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Classes Overview (using GetClassStudentCount function)</span>
            <a href="classes.php" class="btn btn-primary btn-sm">Manage Classes</a>
        </div>
        <table>
            <thead>
                <tr><th>Class</th><th>Subject</th><th>Teacher</th><th>Schedule</th><th>Fee</th><th>Enrolled</th><th>Max</th><th>Full?</th></tr>
            </thead>
            <tbody>
            <?php
            $classes = sqlsrv_query($db, "
                SELECT c.*, t.full_name AS teacher_name,
                       dbo.GetClassStudentCount(c.class_id) AS enrolled_count,
                       dbo.IsClassFull(c.class_id) AS is_full
                FROM classes c
                LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
                WHERE c.status='active'
            ");
            while($cl = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($cl['class_name']) ?></strong></td>
                <td><?= $cl['subject'] ?></td>
                <td><?= htmlspecialchars($cl['teacher_name'] ?? 'N/A') ?></td>
                <td><?= $cl['schedule'] ?></td>
                <td>Rs. <?= number_format($cl['fee'],2) ?></td>
                <td><?= $cl['enrolled_count'] ?></td>
                <td><?= $cl['max_students'] ?></td>
                <td>
                    <?php if($cl['is_full']=='YES'): ?>
                        <span class="badge badge-overdue">FULL</span>
                    <?php else: ?>
                        <span class="badge badge-paid">OPEN</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>