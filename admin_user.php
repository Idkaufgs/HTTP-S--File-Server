<?php
require 'config.php';
session_start();

if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}

$target_user_id = $_GET['user_id'] ?? '';
if (!$target_user_id) { header("Location: admin_users.php"); exit; }

$ch = curl_init(FASTAPI_URL . "/admin/user/" . urlencode($target_user_id));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code !== 200 || !$response) { header("Location: admin_users.php"); exit; }
$u = json_decode($response, true);

$usage_pct = $u['storage_limit'] > 0
    ? min(100, round($u['disk_usage'] / $u['storage_limit'] * 100, 1)) : 0;
$bar_class = $usage_pct >= 90 ? 'danger' : ($usage_pct >= 70 ? 'warn' : '');

$notice = match($_GET['notice'] ?? '') {
    'reset_ok'  => ['type' => 'success', 'msg' => 'Password reset. Temp password shown below.'],
    'limit_ok'  => ['type' => 'success', 'msg' => 'Storage limit updated.'],
    'limit_err' => ['type' => 'error',   'msg' => 'Storage limit exceeds maximum allowed.'],
    default     => null
};

$temp_password = $_GET['tmp'] ?? '';

// Max allowed limit for slider
$max_gb = ceil($u['max_allowed_limit'] / 1073741824);
$cur_gb = round($u['storage_limit'] / 1073741824, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — <?= htmlspecialchars($u['username']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117; --surface: #1a1d27; --border: #2a2d3a;
            --accent: #4f9eff; --danger: #ff5f5f; --warn: #f0a500;
            --success: #3ecf8e; --text: #e8eaf0; --muted: #6b7280;
        }
        body { font-family: 'IBM Plex Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        .topbar { display: flex; align-items: center; gap: 14px; padding: 14px 24px; background: var(--surface); border-bottom: 1px solid var(--border); }
        .topbar-title { font-family: 'IBM Plex Mono', monospace; font-size: 0.95rem; font-weight: 600; flex: 1; }
        .btn { padding: 7px 14px; border-radius: 5px; font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; font-weight: 600; cursor: pointer; border: none; transition: opacity 0.2s, transform 0.1s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn:active { transform: scale(0.97); }
        .btn-ghost  { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.85; }
        .btn-danger  { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: 0.85; }
        .btn-warn    { background: var(--warn); color: #000; }
        .btn-warn:hover { opacity: 0.85; }
        .main { padding: 32px; max-width: 800px; margin: 0 auto; }
        .page-title    { font-family: 'IBM Plex Mono', monospace; font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
        .page-subtitle { font-size: 0.82rem; color: var(--muted); margin-bottom: 24px; }
        .notice {
            padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;
            font-size: 0.85rem; border-left: 3px solid;
        }
        .notice.success { background: rgba(62,207,142,0.1); color: var(--success); border-color: var(--success); }
        .notice.error   { background: rgba(255,95,95,0.1);  color: var(--danger);  border-color: var(--danger); }
        .temp-pwd {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1rem;
            background: var(--bg);
            border: 1px solid var(--success);
            border-radius: 5px;
            padding: 10px 16px;
            margin-top: 8px;
            color: var(--success);
            letter-spacing: 0.05em;
        }
        .temp-pwd-warn { font-size: 0.78rem; color: var(--muted); margin-top: 6px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 18px 20px; }
        .stat-label { font-family: 'IBM Plex Mono', monospace; font-size: 0.68rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .stat-value { font-family: 'IBM Plex Mono', monospace; font-size: 1.2rem; font-weight: 600; }
        .stat-value.warn   { color: var(--warn); }
        .stat-value.danger { color: var(--danger); }
        .storage-bar-track { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; margin-top: 10px; }
        .storage-bar-fill  { height: 100%; border-radius: 3px; background: var(--accent); }
        .storage-bar-fill.warn   { background: var(--warn); }
        .storage-bar-fill.danger { background: var(--danger); }
        .section { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        .section-title { font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
        .form-row { display: flex; gap: 12px; align-items: center; }
        label { font-size: 0.82rem; color: var(--muted); margin-bottom: 6px; display: block; }
        input[type=range] { flex: 1; accent-color: var(--accent); }
        .range-value { font-family: 'IBM Plex Mono', monospace; font-size: 0.88rem; min-width: 60px; text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; }
        .badge-admin { background: rgba(240,165,0,0.15); color: var(--warn); }
        .badge-user  { background: rgba(79,158,255,0.1);  color: var(--accent); }
        .badge-warn  { background: rgba(255,95,95,0.1);   color: var(--danger); }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-title">Admin — <?= htmlspecialchars($u['username']) ?></div>
    <a href="admin_users.php" class="btn btn-ghost">← All Users</a>
</div>

<div class="main">
    <div class="page-title"><?= htmlspecialchars($u['username']) ?></div>
    <div class="page-subtitle">
        // <span class="badge badge-<?= $u['permission'] === 'admin' ? 'admin' : 'user' ?>"><?= $u['permission'] ?></span>
        <?php if ($u['force_password_change']): ?>
            &nbsp;<span class="badge badge-warn">⚠ Reset Required</span>
        <?php endif; ?>
    </div>

    <?php if ($notice): ?>
        <div class="notice <?= $notice['type'] ?>">
            <?= htmlspecialchars($notice['msg']) ?>
            <?php if ($temp_password): ?>
                <div class="temp-pwd"><?= htmlspecialchars($temp_password) ?></div>
                <div class="temp-pwd-warn">⚠ Copy this now — it will not be shown again.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid">
        <div class="stat-card">
            <div class="stat-label">Disk Usage</div>
            <div class="stat-value <?= $bar_class ?>"><?= htmlspecialchars($u['disk_usage_fmt']) ?></div>
            <div class="storage-bar-track">
                <div class="storage-bar-fill <?= $bar_class ?>" style="width:<?= $usage_pct ?>%"></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Storage Limit</div>
            <div class="stat-value"><?= htmlspecialchars($u['storage_limit_fmt']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Files</div>
            <div class="stat-value"><?= $u['file_count'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Usage</div>
            <div class="stat-value <?= $bar_class ?>"><?= $usage_pct ?>%</div>
        </div>
    </div>

    <!-- Storage limit -->
    <div class="section">
        <div class="section-title">Storage Limit</div>
        <form method="POST" action="admin_set_limit_handler.php">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
            <label>Limit (max: <?= htmlspecialchars($u['max_allowed_fmt']) ?>)</label>
            <div class="form-row">
                <input type="range" id="limitSlider" name="storage_limit_gb"
                       min="0.1" max="<?= $max_gb ?>" step="0.1"
                       value="<?= $cur_gb ?>"
                       oninput="document.getElementById('limitVal').textContent = parseFloat(this.value).toFixed(1) + ' GB'">
                <span class="range-value" id="limitVal"><?= $cur_gb ?> GB</span>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>

    <!-- Reset password -->
    <div class="section">
        <div class="section-title">Password Reset</div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px;">
            Generates a temporary password and forces the user to change it on next login.
        </p>
        <form method="POST" action="admin_force_reset_handler.php"
              onsubmit="return confirm('Reset password for <?= htmlspecialchars(addslashes($u['username'])) ?>?')">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
            <input type="hidden" name="redirect_user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
            <button type="submit" class="btn btn-warn">🔑 Reset Password</button>
        </form>
    </div>

    <!-- Delete account -->
    <div class="section">
        <div class="section-title">Danger Zone</div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:14px;">
            Permanently deletes this account and all associated files from disk. This cannot be undone.
        </p>
        <form method="POST" action="admin_delete_user_handler.php"
              onsubmit="return confirm('DELETE account and ALL files for <?= htmlspecialchars(addslashes($u['username'])) ?>? This cannot be undone.')">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
            <button type="submit" class="btn btn-danger">✕ Delete Account</button>
        </form>
    </div>
</div>
</body>
</html>
