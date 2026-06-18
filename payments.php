<?php
require_once 'db.php';
$db = getDB();
$msg = '';
$prefill_enroll = isset($_GET['enroll_id']) ? (int)$_GET['enroll_id'] : 0;

// RECORD PAYMENT using Stored Procedure
if(isset($_POST['pay'])) {
    $enroll_id = (int)$_POST['enroll_id'];
    $amount    = (float)$_POST['amount'];
    $method    = trim($_POST['method']);

    // MSSQL stored procedure with OUTPUT parameter
    $proc_msg = '';
    $params = [
        [$enroll_id, SQLSRV_PARAM_IN],
        [$amount,    SQLSRV_PARAM_IN],
        [$method,    SQLSRV_PARAM_IN],
        [&$proc_msg, SQLSRV_PARAM_OUT]
    ];
    $stmt = sqlsrv_query($db, "{CALL RecordPayment(?,?,?,?)}", $params);

    if($stmt === false) {
        $errors = sqlsrv_errors();
        $msg = ['type'=>'error','text'=> $errors[0]['message']];
    } else {
        sqlsrv_next_result($stmt);
        if(str_starts_with((string)$proc_msg, 'SUCCESS')) {
            $msg = ['type'=>'success','text'=> $proc_msg];
        } else {
            $msg = ['type'=>'error','text'=> $proc_msg];
        }
    }
}

// Get enrollments with pending/overdue payments
$enrollments_stmt = sqlsrv_query($db, "
    SELECT e.enroll_id, s.full_name AS student_name, c.class_name, c.fee, e.payment_status
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN classes  c ON e.class_id   = c.class_id
    WHERE e.payment_status != 'paid'
    ORDER BY e.payment_status DESC, s.full_name
");
$enrollments_rows = [];
while($row = sqlsrv_fetch_array($enrollments_stmt, SQLSRV_FETCH_ASSOC)) {
    $enrollments_rows[] = $row;
}

// Payment history
$payments_stmt = sqlsrv_query($db, "
    SELECT TOP 20 p.*, s.full_name AS student_name, c.class_name
    FROM payments p
    JOIN enrollments e ON p.enroll_id = e.enroll_id
    JOIN students s ON e.student_id = s.student_id
    JOIN classes  c ON e.class_id   = c.class_id
    ORDER BY p.payment_id DESC
");
$payments_rows = [];
while($row = sqlsrv_fetch_array($payments_stmt, SQLSRV_FETCH_ASSOC)) {
    $payments_rows[] = $row;
}

// Total revenue
$rev_stmt = sqlsrv_query($db, "SELECT COALESCE(SUM(amount),0) AS t FROM payments");
$rev_row  = sqlsrv_fetch_array($rev_stmt, SQLSRV_FETCH_ASSOC);
$total_rev = $rev_row['t'];

include 'header.php';
?>

<div class="main">
    <div class="topbar"><h1>💰 Payments</h1></div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">

        <div class="card">
            <div class="card-header"><span class="card-title">💳 Record Payment</span></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Select Enrollment (Pending/Overdue)</label>
                        <select name="enroll_id" required>
                            <option value="">-- Select Enrollment --</option>
                            <?php foreach($enrollments_rows as $e): ?>
                            <option value="<?= $e['enroll_id'] ?>"
                                <?= $prefill_enroll==$e['enroll_id']?'selected':'' ?>>
                                <?= htmlspecialchars($e['student_name']) ?> - <?= htmlspecialchars($e['class_name']) ?>
                                (Rs.<?= $e['fee'] ?>) [<?= strtoupper($e['payment_status']) ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (Rs.) *</label>
                        <input type="number" name="amount" step="0.01" required placeholder="2500">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="method">
                            <option value="cash">💵 Cash</option>
                            <option value="bank">🏦 Bank Transfer</option>
                            <option value="online">📱 Online</option>
                        </select>
                    </div>
                </div>
                <br>
                <button type="submit" name="pay" class="btn btn-success">Record Payment</button>
            </form>

            <div style="margin-top:16px;padding:12px;background:#e8f5e9;border-radius:6px;font-size:12px;">
                <strong>⚡ Stored Procedure Used:</strong><br>
                <code>EXEC RecordPayment @enroll_id, @amount, @method, @msg OUTPUT</code><br><br>
                The procedure:<br>
                ✅ Inserts payment record<br>
                ✅ Compares amount vs class fee<br>
                ✅ Updates payment status automatically
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Payment History</span>
                <span style="font-weight:700;color:#2e7d32;">Total: Rs. <?= number_format($total_rev,2) ?></span>
            </div>
            <table>
                <thead>
                    <tr><th>#</th><th>Student</th><th>Class</th><th>Amount</th><th>Method</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach($payments_rows as $p): ?>
                <tr>
                    <td><?= $p['payment_id'] ?></td>
                    <td><?= htmlspecialchars($p['student_name']) ?></td>
                    <td><?= htmlspecialchars($p['class_name']) ?></td>
                    <td style="color:#2e7d32;font-weight:600;">Rs. <?= number_format($p['amount'],2) ?></td>
                    <td>
                        <?= $p['method']=='cash'?'💵':($p['method']=='bank'?'🏦':'📱') ?>
                        <?= ucfirst($p['method']) ?>
                    </td>
                    <td><?= $p['payment_date'] instanceof DateTime ? $p['payment_date']->format('Y-m-d') : $p['payment_date'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>