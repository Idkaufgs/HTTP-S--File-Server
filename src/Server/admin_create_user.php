<?php
require 'config.php';
session_start();
if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}

$error = match($_GET['error'] ?? '') {
    'taken'  => 'That username is already taken.',
    'server' => 'Server error. Please try again.',
    default  => ''
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Create Account</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg: #0f1117; --surface: #1a1d27; --border: #2a2d3a; --accent: #4f9eff; --danger: #ff5f5f; --warn: #f0a500; --success: #3ecf8e; --text: #e8eaf0; --muted: #6b7280; }
        body { font-family: 'IBM Plex Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        .topbar { display: flex; align-items: center; gap: 14px; padding: 14px 24px; background: var(--surface); border-bottom: 1px solid var(--border); }
        .topbar-title { font-family: 'IBM Plex Mono', monospace; font-size: 0.95rem; font-weight: 600; flex: 1; }
        .btn { padding: 7px 14px; border-radius: 5px; font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; font-weight: 600; cursor: pointer; border: none; transition: opacity 0.2s; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.85; }
        .main { padding: 32px; max-width: 480px; margin: 0 auto; }
        .page-title { font-family: 'IBM Plex Mono', monospace; font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
        .page-subtitle { font-size: 0.82rem; color: var(--muted); margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 24px; }
        .message { font-size: 0.83rem; padding: 10px 14px; border-radius: 5px; margin-bottom: 20px; border-left: 3px solid var(--danger); background: rgba(255,95,95,0.1); color: var(--danger); }
        .field { margin-bottom: 18px; }
        label { display: block; font-size: 0.78rem; font-family: 'IBM Plex Mono', monospace; color: var(--muted); margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.05em; }
        input[type=text], select {
            width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 5px;
            padding: 10px 14px; color: var(--text); font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.95rem; transition: border-color 0.2s; outline: none;
        }
        input[type=text]:focus, select:focus { border-color: var(--accent); }
        select option { background: var(--surface); }
        input[type=range] { width: 100%; accent-color: var(--accent); margin-top: 4px; }
        .range-row { display: flex; justify-content: space-between; font-family: 'IBM Plex Mono', monospace; font-size: 0.78rem; color: var(--muted); margin-top: 6px; }
        .hint { font-size: 0.75rem; color: var(--muted); margin-top: 5px; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-title">Admin — Create Account</div>
    <a href="admin_users.php" class="btn btn-ghost">← Users</a>
</div>

<div class="main">
    <div class="page-title">Create Account</div>
    <div class="page-subtitle">// A temporary password will be generated</div>

    <div class="card">
        <?php if ($error): ?>
            <div class="message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_create_user_handler.php">
            <div class="field">
                <label>Username</label>
                <input type="text" name="uname" placeholder="Enter username" required autofocus
                       value="<?= htmlspecialchars($_GET['uname'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Role</label>
                <select name="permission">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="field">
                <label>Storage Limit — <span id="limitLabel">1.0 GB</span></label>
                <input type="range" id="limitSlider" name="storage_limit_gb"
                       min="0.1" max="100" step="0.1" value="1"
                       oninput="document.getElementById('limitLabel').textContent = parseFloat(this.value).toFixed(1) + ' GB'">
                <div class="range-row"><span>0.1 GB</span><span>100 GB</span></div>
                <div class="hint">Max allowed by system shown on user page after creation.</div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;">
                Create Account →
            </button>
        </form>
    </div>
</div>
</body>
</html>
