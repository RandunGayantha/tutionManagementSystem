<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tuition Class Management System</title>
<style>
/* ===== RESET & BASE ===== */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Segoe UI', sans-serif; background:#f0f2f5; color:#333; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:240px; background:#1a237e; color:#fff;
    position:fixed; top:0; left:0; height:100vh;
    display:flex; flex-direction:column;
    box-shadow: 3px 0 10px rgba(0,0,0,0.3);
}
.sidebar-logo {
    padding:20px; background:#0d1757;
    font-size:16px; font-weight:700; letter-spacing:1px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    line-height:1.4;
}
.sidebar-logo span { display:block; font-size:11px; font-weight:400; opacity:0.7; margin-top:4px; }
.nav-section { padding:10px 0; }
.nav-label {
    padding:8px 20px; font-size:10px; text-transform:uppercase;
    letter-spacing:2px; opacity:0.5; font-weight:600;
}
.nav-item {
    display:block; padding:11px 20px; color:rgba(255,255,255,0.8);
    text-decoration:none; font-size:14px;
    transition:all 0.2s; border-left:3px solid transparent;
}
.nav-item:hover, .nav-item.active {
    background:rgba(255,255,255,0.1); color:#fff;
    border-left-color:#64b5f6;
}
.nav-item i { margin-right:10px; width:16px; display:inline-block; }

/* ===== MAIN CONTENT ===== */
.main { margin-left:240px; padding:25px; min-height:100vh; }

/* ===== TOP BAR ===== */
.topbar {
    background:#fff; padding:14px 24px; border-radius:8px;
    margin-bottom:24px; display:flex; justify-content:space-between;
    align-items:center; box-shadow:0 1px 4px rgba(0,0,0,0.08);
}
.topbar h1 { font-size:20px; color:#1a237e; font-weight:600; }
.topbar-date { font-size:13px; color:#777; }

/* ===== CARDS ===== */
.card {
    background:#fff; border-radius:10px; padding:22px;
    box-shadow:0 1px 6px rgba(0,0,0,0.08); margin-bottom:20px;
}
.card-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:18px; padding-bottom:12px;
    border-bottom:2px solid #e8eaf6;
}
.card-title { font-size:16px; font-weight:600; color:#1a237e; }

/* ===== STAT CARDS ===== */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
.stat-card {
    background:#fff; border-radius:10px; padding:20px;
    box-shadow:0 1px 6px rgba(0,0,0,0.08);
    border-top:4px solid #1a237e; text-align:center;
}
.stat-card.green  { border-top-color:#2e7d32; }
.stat-card.orange { border-top-color:#e65100; }
.stat-card.purple { border-top-color:#6a1b9a; }
.stat-number { font-size:32px; font-weight:700; color:#1a237e; }
.stat-card.green  .stat-number { color:#2e7d32; }
.stat-card.orange .stat-number { color:#e65100; }
.stat-card.purple .stat-number { color:#6a1b9a; }
.stat-label { font-size:13px; color:#888; margin-top:5px; }

/* ===== TABLE ===== */
table { width:100%; border-collapse:collapse; font-size:14px; }
thead { background:#e8eaf6; }
th { padding:11px 14px; text-align:left; font-weight:600; color:#1a237e; font-size:13px; }
td { padding:10px 14px; border-bottom:1px solid #f0f0f0; }
tr:hover td { background:#f5f5ff; }
.badge {
    padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;
}
.badge-paid    { background:#e8f5e9; color:#2e7d32; }
.badge-pending { background:#fff8e1; color:#f57f17; }
.badge-overdue { background:#ffebee; color:#c62828; }
.badge-active  { background:#e3f2fd; color:#1565c0; }
.badge-inactive{ background:#f5f5f5; color:#757575; }

/* ===== FORMS ===== */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-group { display:flex; flex-direction:column; gap:5px; }
.form-group label { font-size:13px; font-weight:600; color:#555; }
.form-group input,
.form-group select,
.form-group textarea {
    padding:9px 12px; border:1px solid #dde; border-radius:6px;
    font-size:14px; outline:none; transition:border 0.2s;
    font-family:inherit;
}
.form-group input:focus,
.form-group select:focus { border-color:#3949ab; box-shadow:0 0 0 2px rgba(57,73,171,0.1); }
.form-full { grid-column:1/-1; }

/* ===== BUTTONS ===== */
.btn {
    padding:9px 18px; border:none; border-radius:6px;
    font-size:14px; font-weight:600; cursor:pointer;
    transition:all 0.2s; text-decoration:none; display:inline-block;
}
.btn-primary { background:#1a237e; color:#fff; }
.btn-primary:hover { background:#0d1757; }
.btn-success { background:#2e7d32; color:#fff; }
.btn-success:hover { background:#1b5e20; }
.btn-danger  { background:#c62828; color:#fff; }
.btn-danger:hover  { background:#b71c1c; }
.btn-warning { background:#f57f17; color:#fff; }
.btn-sm { padding:5px 12px; font-size:12px; }

/* ===== ALERT ===== */
.alert {
    padding:12px 16px; border-radius:6px; margin-bottom:16px;
    font-size:14px; font-weight:500;
}
.alert-success { background:#e8f5e9; color:#2e7d32; border-left:4px solid #2e7d32; }
.alert-error   { background:#ffebee; color:#c62828; border-left:4px solid #c62828; }
.alert-info    { background:#e3f2fd; color:#1565c0; border-left:4px solid #1565c0; }

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .sidebar { width:100%; height:auto; position:relative; }
    .main { margin-left:0; }
    .stats-grid { grid-template-columns:1fr 1fr; }
    .form-grid { grid-template-columns:1fr; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        📚 Tuition CMS
        <span>Class Management System</span>
    </div>

    <div class="nav-section">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='index.php')?'active':'' ?>">
            <i>🏠</i> Dashboard
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Management</div>
        <a href="students.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='students.php')?'active':'' ?>">
            <i>👨‍🎓</i> Students
        </a>
        <a href="teachers.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='teachers.php')?'active':'' ?>">
            <i>👨‍🏫</i> Teachers
        </a>
        <a href="classes.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='classes.php')?'active':'' ?>">
            <i>📖</i> Classes
        </a>
        <a href="enrollments.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='enrollments.php')?'active':'' ?>">
            <i>📋</i> Enrollments
        </a>
        <a href="payments.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='payments.php')?'active':'' ?>">
            <i>💰</i> Payments
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Reports</div>
        <a href="reports.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='reports.php')?'active':'' ?>">
            <i>📊</i> Reports
        </a>
        <a href="activity_log.php" class="nav-item <?= (basename($_SERVER['PHP_SELF'])=='activity_log.php')?'active':'' ?>">
            <i>📝</i> Activity Log
        </a>
    </div>
</div>
