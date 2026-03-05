<?php
require 'config.php';
session_start();

if (!($_SESSION['logged_in'] ?? false) || ($_SESSION['permission'] ?? '') !== 'admin') {
    header("Location: dashboard.php"); exit;
}

$filter = $_GET['filter'] ?? '';

$ch = curl_init(FASTAPI_URL . "/admin/users");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$response = curl_exec($ch);
$users    = $response ? (json_decode($response, true) ?? []) : [];

if ($filter === 'force_change') {
    $users = array_filter($users, fn($u) => $u['force_password_change']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Users</title>
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
        .btn { padding: 7px 14px; border-radius: 5px; font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; font-weight: 600; cursor: pointer; border: none; transition: opacity 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.85; }
        .main { padding: 32px; max-width: 1100px; margin: 0 auto; }
        .page-title { font-family: 'IBM Plex Mono', monospace; font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
        .page-subtitle { font-size: 0.82rem; color: var(--muted); margin-bottom: 24px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-header-title { font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 10px 20px; text-align: left; font-family: 'IBM Plex Mono', monospace; font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; border-bottom: 1px solid var(--border); }
        td { padding: 12px 20px; font-size: 0.88rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .mono { font-family: 'IBM Plex Mono', monospace; font-size: 0.8rem; color: var(--muted); }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-family: 'IBM Plex Mono', monospace; font-size: 0.72rem; }
        .badge-admin { background: rgba(240,165,0,0.15); color: var(--warn); }
        .badge-user  { background: rgba(79,158,255,0.1);  color: var(--accent); }
        .badge-warn  { background: rgba(255,95,95,0.1);   color: var(--danger); }
        .storage-mini { width: 100px; height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 6px; }
        .storage-mini-fill { height: 100%; border-radius: 2px; background: var(--accent); }
        .storage-mini-fill.warn   { background: var(--warn); }
        .storage-mini-fill.danger { background: var(--danger); }
        .empty-state { padding: 40px; text-align: center; color: var(--muted); }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-title">Admin — Users <?= $filter === 'force_change' ? '(Pending Resets)' : '' ?></div>
    <a href="admin_create_user.php" class="btn btn-primary">➕ Create Account</a>
    <a href="dashboard.php" class="btn btn-ghost">← Dashboard</a>
</div>

<div class="main">
    <div class="page-title">User Management</div>
    <div class="page-subtitle">// <?= count($users) ?> account<?= count($users) !== 1 ? 's' : '' ?><?= $filter === 'force_change' ? ' — pending password reset' : '' ?></div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-title">Accounts</span>
            <?php if ($filter): ?>
                <a href="admin_users.php" class="btn btn-ghost" style="font-size:0.75rem;padding:4px 10px;">Clear filter</a>
            <?php endif; ?>
        </div>
        <?php if (empty($users)): ?>
            <div class="empty-state">No users found.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Storage</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $pct       = $u['storage_limit'] > 0 ? min(100, round($u['disk_usage'] / $u['storage_limit'] * 100)) : 0;
                $bar_class = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warn' : '');
            ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td>
                    <span class="badge badge-<?= $u['permission'] === 'admin' ? 'admin' : 'user' ?>">
                        <?= $u['permission'] ?>
                    </span>
                </td>
                <td>
                    <div class="storage-mini">
                        <div class="storage-mini-fill <?= $bar_class ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="mono"><?= htmlspecialchars($u['disk_usage_fmt']) ?> / <?= htmlspecialchars($u['storage_limit_fmt']) ?></span>
                </td>
                <td>
                    <?php if ($u['force_password_change']): ?>
                        <span class="badge badge-warn">⚠ Reset Required</span>
                    <?php else: ?>
                        <span class="mono">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="admin_user.php?user_id=<?= urlencode($u['user_id']) ?>" class="btn btn-ghost" style="font-size:0.78rem;padding:5px 10px;">Manage →</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
