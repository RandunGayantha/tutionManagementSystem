<?php
require_once 'db.php';
$db = getDB();
$msg = '';

// ADD STUDENT
if(isset($_POST['add_student'])) {
    $name    = $db->real_escape_string(trim($_POST['full_name']));
    $email   = $db->real_escape_string(trim($_POST['email']));
    $phone   = $db->real_escape_string(trim($_POST['phone']));
    $grade   = $db->real_escape_string(trim($_POST['grade']));
    $address = $db->real_escape_string(trim($_POST['address']));

    $sql = "INSERT INTO students (full_name, email, phone, grade, address)
            VALUES ('$name','$email','$phone','$grade','$address')";
    if($db->query($sql)) {
        $msg = ['type'=>'success', 'text'=>"Student '$name' added successfully! (Trigger logged this action)"];
    } else {
        $msg = ['type'=>'error', 'text'=>"Error: " . $db->error];
    }
}

// DELETE STUDENT
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if($db->query("DELETE FROM students WHERE student_id=$id")) {
        $msg = ['type'=>'success', 'text'=>'Student deleted.'];
    }
}

// UPDATE STATUS
if(isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->query("UPDATE students SET status = IF(status='active','inactive','active') WHERE student_id=$id");
    $msg = ['type'=>'success', 'text'=>'Status updated.'];
}

// Search
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
$where  = $search ? "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR grade LIKE '%$search%'" : '';
$students = $db->query("SELECT *, GetStudentTotalPaid(student_id) AS total_paid FROM students $where ORDER BY student_id DESC");

include 'header.php';
?>

<div class="main">
    <div class="topbar">
        <h1>👨‍🎓 Students</h1>
        <span class="topbar-date">Total: <?= $students->num_rows ?> students</span>
    </div>

    <?php if($msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;">

        <!-- Add Student Form -->
        <div class="card">
            <div class="card-header"><span class="card-title">➕ Add New Student</span></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="e.g. Kamal Perera">
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
                        <label>Grade</label>
                        <select name="grade">
                            <option value="">Select Grade</option>
                            <?php for($g=6;$g<=13;$g++): ?>
                            <option value="Grade <?= $g ?>">Grade <?= $g ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="City">
                    </div>
                </div>
                <br>
                <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                <small style="display:block;margin-top:8px;color:#888;">
                    ℹ️ Adding a student fires <strong>trg_after_student_insert</strong> trigger
                </small>
            </form>
        </div>

        <!-- Students List -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">All Students</span>
                <form method="GET" style="display:flex;gap:8px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search name/grade..." style="padding:6px 10px;border:1px solid #ddd;border-radius:5px;font-size:13px;">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if($search): ?><a href="students.php" class="btn btn-sm" style="background:#eee;">Clear</a><?php endif; ?>
                </form>
            </div>
            <div style="overflow-x:auto;max-height:450px;overflow-y:auto;">
            <table>
                <thead>
                    <tr><th>#</th><th>Name</th><th>Grade</th><th>Phone</th><th>Total Paid</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php
                $students->data_seek(0);
                while($s = $students->fetch_assoc()): ?>
                <tr>
                    <td><?= $s['student_id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
                        <small style="color:#888;"><?= $s['email'] ?></small>
                    </td>
                    <td><?= $s['grade'] ?></td>
                    <td><?= $s['phone'] ?></td>
                    <td style="color:#2e7d32;font-weight:600;">Rs. <?= number_format($s['total_paid'],2) ?></td>
                    <td><span class="badge badge-<?= $s['status'] ?>"><?= strtoupper($s['status']) ?></span></td>
                    <td>
                        <a href="?toggle=<?= $s['student_id'] ?>" class="btn btn-warning btn-sm">Toggle</a>
                        <a href="?delete=<?= $s['student_id'] ?>"
                           onclick="return confirm('Delete this student?')"
                           class="btn btn-danger btn-sm">Del</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <small style="color:#888;display:block;margin-top:10px;">
                💡 <strong>GetStudentTotalPaid()</strong> function calculates total paid amount per student
            </small>
        </div>
    </div>
</div>
</body></html>
