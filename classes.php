<?php
require_once 'db.php';
$db = getDB();
$msg = '';

if(isset($_POST['add_class'])) {
    $name    = trim($_POST['class_name']);
    $subject = trim($_POST['subject']);
    $tid     = (int)$_POST['teacher_id'];
    $sched   = trim($_POST['schedule']);
    $max     = (int)$_POST['max_students'];
    $fee     = (float)$_POST['fee'];

    $stmt = sqlsrv_query($db,
        "INSERT INTO classes (class_name,subject,teacher_id,schedule,max_students,fee) VALUES (?,?,?,?,?,?)",
        [$name, $subject, $tid, $sched, $max, $fee]
    );
    if($stmt) {
        $msg = ['type'=>'success','text'=>"Class '$name' created!"];
    } else {
        $errors = sqlsrv_errors();
        $msg = ['type'=>'error','text'=>$errors[0]['message']];
    }
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    sqlsrv_query($db, "UPDATE classes SET status='inactive' WHERE class_id=?", [$id]);
    $msg = ['type'=>'success','text'=>'Class deactivated.'];
}

$teachers_stmt = sqlsrv_query($db, "SELECT * FROM teachers WHERE status='active' ORDER BY full_name");
$teachers_rows = [];
while($row = sqlsrv_fetch_array($teachers_stmt, SQLSRV_FETCH_ASSOC)) {
    $teachers_rows[] = $row;
}

$classes_stmt = sqlsrv_query($db, "
    SELECT c.*, t.full_name AS teacher_name,
           dbo.GetClassStudentCount(c.class_id) AS enrolled,
           dbo.IsClassFull(c.class_id) AS is_full
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
    ORDER BY c.class_id DESC
");
if($classes_stmt === false) {
    // fallback without functions
    $classes_stmt = sqlsrv_query($db, "
        SELECT c.*, t.full_name AS teacher_name, 0 AS enrolled, 'NO' AS is_full
        FROM classes c
        LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
        ORDER BY c.class_id DESC
    ");
}
$classes_rows = [];
while($row = sqlsrv_fetch_array($classes_stmt, SQLSRV_FETCH_ASSOC)) {
    $classes_rows[] = $row;
}

include 'header.php';
?>

<div class="main">
    <div class="topbar"><h1>📖 Classes</h1></div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">

        <div class="card">
            <div class="card-header"><span class="card-title">➕ Add Class</span></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Class Name *</label>
                        <input type="text" name="class_name" required placeholder="e.g. Maths Grade 10">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject">
                            <option>Mathematics</option><option>Science</option>
                            <option>English</option><option>ICT</option>
                            <option>Physics</option><option>Chemistry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teacher</label>
                        <select name="teacher_id">
                            <option value="0">-- No Teacher --</option>
                            <?php foreach($teachers_rows as $t): ?>
                            <option value="<?= $t['teacher_id'] ?>"><?= htmlspecialchars($t['full_name']) ?> (<?= $t['subject'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label>Schedule</label>
                        <input type="text" name="schedule" placeholder="e.g. Mon/Wed 4pm-6pm">
                    </div>
                    <div class="form-group">
                        <label>Max Students</label>
                        <input type="number" name="max_students" value="30" min="1">
                    </div>
                    <div class="form-group">
                        <label>Monthly Fee (Rs.)</label>
                        <input type="number" name="fee" placeholder="2500" step="0.01">
                    </div>
                </div>
                <br>
                <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">All Classes</span></div>
            <table>
                <thead>
                    <tr><th>#</th><th>Class</th><th>Teacher</th><th>Schedule</th><th>Fee</th><th>Enrolled/Max</th><th>Full?</th><th>Status</th><th>Act</th></tr>
                </thead>
                <tbody>
                <?php foreach($classes_rows as $cl): ?>
                <tr>
                    <td><?= $cl['class_id'] ?></td>
                    <td><strong><?= htmlspecialchars($cl['class_name']) ?></strong><br>
                        <small style="color:#888;"><?= $cl['subject'] ?></small></td>
                    <td><?= htmlspecialchars($cl['teacher_name'] ?? '-') ?></td>
                    <td style="font-size:12px;"><?= $cl['schedule'] ?></td>
                    <td>Rs. <?= number_format($cl['fee'],2) ?></td>
                    <td><?= $cl['enrolled'] ?> / <?= $cl['max_students'] ?></td>
                    <td>
                        <?php if($cl['is_full']=='YES'): ?>
                            <span class="badge badge-overdue">FULL</span>
                        <?php else: ?>
                            <span class="badge badge-paid">OPEN</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $cl['status'] ?>"><?= strtoupper($cl['status']) ?></span></td>
                    <td>
                        <a href="?delete=<?= $cl['class_id'] ?>"
                           onclick="return confirm('Deactivate this class?')"
                           class="btn btn-danger btn-sm">Del</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <small style="color:#888;margin-top:10px;display:block;">
                💡 <strong>IsClassFull()</strong> and <strong>GetClassStudentCount()</strong> SQL functions used here
            </small>
        </div>
    </div>
</div>
</body></html>