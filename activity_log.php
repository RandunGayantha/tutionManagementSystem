<?php
require_once 'db.php';
$db = getDB();
$logs_stmt = sqlsrv_query($db, "SELECT TOP 50 * FROM activity_log ORDER BY action_time DESC");
$logs_rows = [];
if($logs_stmt) {
    while($row = sqlsrv_fetch_array($logs_stmt, SQLSRV_FETCH_ASSOC)) $logs_rows[] = $row;
}
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
            ⚡ <strong>How this works:</strong> These entries are automatically created by SQL Server TRIGGERS — not by PHP code.
            <br>Triggers: <code>trg_after_student_insert</code> and <code>trg_after_enrollment_insert</code> write here automatically.
        </div>
        <table>
            <thead>
                <tr><th>#</th><th>Action</th><th>Table</th><th>Description</th><th>Time</th></tr>
            </thead>
            <tbody>
            <?php if(empty($logs_rows)): ?>
            <tr><td colspan="5" style="text-align:center;color:#888;padding:30px;">
                No activity yet. Add a student or enroll someone to see trigger logs here.
            </td></tr>
            <?php endif; ?>
            <?php foreach($logs_rows as $log): ?>
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
                <td style="font-size:12px;color:#888;"><?= $log['action_time'] instanceof DateTime ? $log['action_time']->format('Y-m-d H:i:s') : $log['action_time'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>