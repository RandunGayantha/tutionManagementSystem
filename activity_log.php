<?php
require_once 'db.php';
$db = getDB();

$logs = $db->query("SELECT * FROM activity_log ORDER BY action_time DESC LIMIT 50");

include 'header.php';
?>

<div class="main">
    <div class="topbar">
        <h1>📝 Activity Log</h1>
        <span class="topbar-date">Trigger-generated entries</span>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">All Activity (Auto-logged by Triggers)</span>
            <span style="font-size:13px;color:#888;">Last 50 entries</span>
        </div>

        <div style="background:#fff8e1;border:1px solid #ffe082;padding:12px;border-radius:6px;margin-bottom:16px;font-size:13px;">
            ⚡ <strong>How this works:</strong> These entries are automatically created by MySQL TRIGGERS — not by PHP code.
            <br>Triggers: <code>trg_after_student_insert</code> and <code>trg_after_enrollment_insert</code> write here automatically.
        </div>

        <table>
            <thead>
                <tr><th>#</th><th>Action</th><th>Table</th><th>Description</th><th>Time</th></tr>
            </thead>
            <tbody>
            <?php if($logs->num_rows == 0): ?>
            <tr><td colspan="5" style="text-align:center;color:#888;padding:30px;">
                No activity yet. Add a student or enroll someone to see trigger logs here.
            </td></tr>
            <?php endif; ?>

            <?php while($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?= $log['log_id'] ?></td>
                <td>
                    <?php
                    $colors = ['INSERT'=>'#2e7d32','UPDATE'=>'#f57f17','DELETE'=>'#c62828'];
                    $color  = $colors[$log['action_type']] ?? '#555';
                    ?>
                    <span style="background:<?= $color ?>22;color:<?= $color ?>;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;">
                        <?= $log['action_type'] ?>
                    </span>
                </td>
                <td><code style="font-size:12px;background:#f5f5f5;padding:2px 6px;border-radius:3px;"><?= $log['table_name'] ?></code></td>
                <td><?= htmlspecialchars($log['description']) ?></td>
                <td style="font-size:12px;color:#888;"><?= $log['action_time'] ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>
