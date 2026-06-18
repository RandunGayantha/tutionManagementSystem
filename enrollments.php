<?php
require_once 'db.php';
$db = getDB();
$msg = '';

// ENROLL STUDENT using Stored Procedure
if(isset($_POST['enroll'])) {
    $sid = (int)$_POST['student_id'];
    $cid = (int)$_POST['class_id'];

    $proc_msg = '';
    $params = [
        [$sid, SQLSRV_PARAM_IN],
        [$cid, SQLSRV_PARAM_IN],
        [&$proc_msg, SQLSRV_PARAM_OUT]
    ];
    $stmt = sqlsrv_query($db, "{CALL EnrollStudent(?,?,?)}", $params);

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

// DELETE ENROLLMENT
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    sqlsrv_query($db, "DELETE FROM enrollments WHERE enroll_id=?", [$id]);
    $msg = ['type'=>'success','text'=>'Enrollment removed.'];
}

$students_stmt = sqlsrv_query($db, "SELECT * FROM students WHERE status='active' ORDER BY full_name");
if($students_stmt === false) {
    $e = sqlsrv_errors();
    die("Students query error: " . $e[0]['message']);
}
$students_rows = [];
while($row = sqlsrv_fetch_array($students_stmt, SQLSRV_FETCH_ASSOC)) {
    $students_rows[] = $row;
}

$classes_stmt = sqlsrv_query($db, "SELECT c.*, 'NO' AS is_full FROM classes c WHERE c.status='active' ORDER BY class_name");
$classes_rows = [];
while($row = sqlsrv_fetch_array($classes_stmt, SQLSRV_FETCH_ASSOC)) {
    $classes_rows[] = $row;
}

// Filter by student
$filter_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if($filter_student) {
    $enrollments_stmt = sqlsrv_query($db, "
        SELECT e.*, s.full_name AS student_name, c.class_name, c.fee, c.subject
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN classes  c ON e.class_id   = c.class_id
        WHERE e.student_id=?
        ORDER BY e.enroll_id DESC
    ", [$filter_student]);
} else {
    $enrollments_stmt = sqlsrv_query($db, "
        SELECT e.*, s.full_name AS student_name, c.class_name, c.fee, c.subject
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN classes  c ON e.class_id   = c.class_id
        ORDER BY e.enroll_id DESC
    ");
}
$enrollments_rows = [];
while($row = sqlsrv_fetch_array($enrollments_stmt, SQLSRV_FETCH_ASSOC)) {
    $enrollments_rows[] = $row;
}

include 'header.php';
?>

<div class="main">
    <div class="topbar">
        <h1>📋 Enrollments</h1>
        <span class="topbar-date">Total: <?= count($enrollments_rows) ?></span>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">

        <div class="card">
            <div class="card-header"><span class="card-title">➕ Enroll Student</span></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Select Student *</label>
                        <select name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($students_rows as $s): ?>
                            <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $s['grade'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label>Select Class *</label>
                        <select name="class_id" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach($classes_rows as $c):
                                $full_label = $c['is_full']=='YES' ? ' [FULL]' : '';
                            ?>
                            <option value="<?= $c['class_id'] ?>" <?= $c['is_full']=='YES'?'style="color:red;"':'' ?>>
                                <?= htmlspecialchars($c['class_name']) ?> - Rs.<?= $c['fee'] ?><?= $full_label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <br>
                <button type="submit" name="enroll" class="btn btn-success">Enroll Now</button>
            </form>

            <div style="margin-top:16px;padding:12px;background:#e8eaf6;border-radius:6px;font-size:12px;">
                <strong>⚡ Stored Procedure Used:</strong><br>
                <code>EXEC EnrollStudent @student_id, @class_id, @msg OUTPUT</code><br><br>
                The procedure:<br>
                ✅ Checks if class is full<br>
                ✅ Checks duplicate enrollment<br>
                ✅ Returns success/error message
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">All Enrollments</span>
                <form method="GET" style="display:flex;gap:8px;">
                    <select name="student_id" style="padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                        <option value="">All Students</option>
                        <?php foreach($students_rows as $s): ?>
                        <option value="<?= $s['student_id'] ?>" <?= $filter_student==$s['student_id']?'selected':'' ?>>
                            <?= htmlspecialchars($s['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if($filter_student): ?><a href="enrollments.php" class="btn btn-sm" style="background:#eee;">Clear</a><?php endif; ?>
                </form>
            </div>

            <div style="overflow-y:auto;max-height:420px;">
            <table>
                <thead>
                    <tr><th>#</th><th>Student</th><th>Class</th><th>Enrolled On</th><th>Fee</th><th>Payment</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach($enrollments_rows as $e): ?>
                <tr>
                    <td><?= $e['enroll_id'] ?></td>
                    <td><strong><?= htmlspecialchars($e['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($e['class_name']) ?><br>
                        <small style="color:#888;"><?= $e['subject'] ?></small></td>
                    <td><?= $e['enroll_date'] instanceof DateTime ? $e['enroll_date']->format('Y-m-d') : $e['enroll_date'] ?></td>
                    <td>Rs. <?= number_format($e['fee'],2) ?></td>
                    <td><span class="badge badge-<?= $e['payment_status'] ?>"><?= strtoupper($e['payment_status']) ?></span></td>
                    <td>
                        <a href="payments.php?enroll_id=<?= $e['enroll_id'] ?>" class="btn btn-success btn-sm">Pay</a>
                        <a href="?delete=<?= $e['enroll_id'] ?>"
                           onclick="return confirm('Remove this enrollment?')"
                           class="btn btn-danger btn-sm">Del</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
</body></html>