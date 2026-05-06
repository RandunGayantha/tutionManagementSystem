<?php
require_once 'db.php';
$db = getDB();
$msg = '';

if(isset($_POST['add_teacher'])) {
    $name    = $db->real_escape_string(trim($_POST['full_name']));
    $email   = $db->real_escape_string(trim($_POST['email']));
    $phone   = $db->real_escape_string(trim($_POST['phone']));
    $subject = $db->real_escape_string(trim($_POST['subject']));
    $salary  = (float)$_POST['salary'];

    $sql = "INSERT INTO teachers (full_name,email,phone,subject,salary)
            VALUES ('$name','$email','$phone','$subject',$salary)";
    if($db->query($sql)) {
        $msg = ['type'=>'success','text'=>"Teacher '$name' added!"];
    } else {
        $msg = ['type'=>'error','text'=>$db->error];
    }
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($db->query("DELETE FROM teachers WHERE teacher_id=$id")) {
        $msg = ['type'=>'success','text'=>'Teacher deleted.'];
    } else {
        // Trigger error message
        $msg = ['type'=>'error','text'=>'Cannot delete: ' . $db->error];
    }
}

$teachers = $db->query("
    SELECT t.*, COUNT(c.class_id) AS class_count
    FROM teachers t
    LEFT JOIN classes c ON t.teacher_id = c.teacher_id AND c.status='active'
    GROUP BY t.teacher_id
    ORDER BY t.teacher_id DESC
");

include 'header.php';
?>

<div class="main">
    <div class="topbar">
        <h1>👨‍🏫 Teachers</h1>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">

        <div class="card">
            <div class="card-header"><span class="card-title">➕ Add Teacher</span></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Mr./Ms. Name">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="07XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Subject *</label>
                        <select name="subject" required>
                            <option value="">Select Subject</option>
                            <option>Mathematics</option>
                            <option>Science</option>
                            <option>English</option>
                            <option>ICT</option>
                            <option>History</option>
                            <option>Commerce</option>
                            <option>Physics</option>
                            <option>Chemistry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Salary (Rs.)</label>
                        <input type="number" name="salary" placeholder="40000">
                    </div>
                </div>
                <br>
                <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">All Teachers</span></div>
            <table>
                <thead>
                    <tr><th>#</th><th>Name</th><th>Subject</th><th>Phone</th><th>Salary</th><th>Classes</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php while($t = $teachers->fetch_assoc()): ?>
                <tr>
                    <td><?= $t['teacher_id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($t['full_name']) ?></strong><br>
                        <small style="color:#888;"><?= $t['email'] ?></small>
                    </td>
                    <td><?= $t['subject'] ?></td>
                    <td><?= $t['phone'] ?></td>
                    <td>Rs. <?= number_format($t['salary'],2) ?></td>
                    <td><span class="badge badge-active"><?= $t['class_count'] ?> classes</span></td>
                    <td><span class="badge badge-<?= $t['status'] ?>"><?= strtoupper($t['status']) ?></span></td>
                    <td>
                        <a href="?delete=<?= $t['teacher_id'] ?>"
                           onclick="return confirm('Delete this teacher?')"
                           class="btn btn-danger btn-sm">Del</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <small style="color:#888;display:block;margin-top:10px;">
                ⚡ <strong>trg_before_teacher_delete</strong> prevents deleting teachers with active classes
            </small>
        </div>
    </div>
</div>
</body></html>
