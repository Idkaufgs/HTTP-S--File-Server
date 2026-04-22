<?php
require 'config.php';
session_start();

if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}

$timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset(); session_destroy();
    header("Location: login.php?reason=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

$user_id    = $_SESSION['user_id']    ?? '';
$username   = $_SESSION['username']   ?? 'Unknown';
$permission = $_SESSION['permission'] ?? 'user';
$is_admin   = $permission === 'admin';

function fetch_files(string $user_id): array {
    $url      = FASTAPI_URL . "/files/" . $user_id;
    $response = file_get_contents($url);
    return $response ? (json_decode($response, true) ?? []) : [];
}

function fetch_storage_info(string $user_id): array {
    $url      = FASTAPI_URL . "/admin/user/" . $user_id;
    $response = file_get_contents($url);
    if (!$response) return ['disk_usage' => 0, 'storage_limit' => 1073741824,
                             'disk_usage_fmt' => '0 B', 'storage_limit_fmt' => '1.00 GB'];
    return json_decode($response, true) ?? [];
}

$files        = fetch_files($user_id);
$storage_info = fetch_storage_info($user_id);
$usage_pct    = $storage_info['storage_limit'] > 0
    ? min(100, round($storage_info['disk_usage'] / $storage_info['storage_limit'] * 100, 1))
    : 0;

function format_bytes(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — <?= htmlspecialchars($username) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0f1117;
            --surface:  #1a1d27;
            --surface2: #20243a;
            --border:   #2a2d3a;
            --accent:   #4f9eff;
            --danger:   #ff5f5f;
            --warn:     #f0a500;
            --success:  #3ecf8e;
            --text:     #e8eaf0;
            --muted:    #6b7280;
            --sidebar-w: 300px;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .topbar {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title { font-family: 'IBM Plex Mono', monospace; font-size: 0.95rem; font-weight: 600; flex: 1; }
        .topbar-user  { font-size: 0.82rem; color: var(--muted); font-family: 'IBM Plex Mono', monospace; }

        .btn {
            padding: 7px 14px;
            border-radius: 5px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s, transform 0.1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn:active     { transform: scale(0.97); }
        .btn-ghost      { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-ghost:hover{ border-color: var(--accent); color: var(--accent); }
        .btn-primary    { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.85; }
        .btn-danger     { background: var(--danger); color: #fff; }
        .btn-danger:hover  { opacity: 0.85; }

        .admin-toggle {
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 5px;
            color: var(--warn);
            padding: 7px 12px;
            cursor: pointer;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.82rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .admin-toggle:hover { border-color: var(--warn); background: rgba(240,165,0,0.06); }

        .layout {
            display: flex;
            min-height: calc(100vh - 53px);
        }

        /* Sidebar */
        .admin-sidebar {
            width: 0;
            overflow: hidden;
            background: var(--surface);
            border-right: 1px solid var(--border);
            transition: width 0.3s ease;
            flex-shrink: 0;
        }
        .admin-sidebar.open { width: var(--sidebar-w); }

        .sidebar-inner { width: var(--sidebar-w); padding: 24px 20px; }

        .sidebar-inner h2 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            color: var(--warn);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 22px;
        }

        .sidebar-section { margin-bottom: 28px; }
        .sidebar-section h3 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.68rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 8px;
            transition: border-color 0.2s, background 0.2s;
        }
        .sidebar-btn:hover { border-color: var(--accent); background: rgba(79,158,255,0.06); }

        /* Main */
        .main { flex: 1; padding: 32px; min-width: 0; }

        .page-title    { font-family: 'IBM Plex Mono', monospace; font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
        .page-subtitle { font-size: 0.82rem; color: var(--muted); margin-bottom: 24px; }

        /* Storage bar */
        .storage-bar-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .storage-bar-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: var(--muted);
            font-family: 'IBM Plex Mono', monospace;
            margin-bottom: 10px;
        }
        .storage-bar-track {
            height: 5px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
        }
        .storage-bar-fill {
            height: 100%;
            border-radius: 3px;
            background: var(--accent);
            transition: width 0.6s ease;
        }
        .storage-bar-fill.warn   { background: var(--warn); }
        .storage-bar-fill.danger { background: var(--danger); }

        /* Card / table */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 10px 20px;
            text-align: left;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.7rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 12px 20px;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:last-child td  { border-bottom: none; }
        tr:hover td       { background: rgba(255,255,255,0.02); }

        .file-size { color: var(--muted); font-family: 'IBM Plex Mono', monospace; font-size: 0.78rem; }
        .actions   { display: flex; gap: 8px; }

        .empty-state { padding: 40px; text-align: center; color: var(--muted); font-size: 0.88rem; }

        /* Upload */
        .upload-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
        }
        .upload-section h3 {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }
        .upload-row { display: flex; gap: 12px; align-items: center; }

        input[type=file] {
            flex: 1;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 8px 12px;
            color: var(--text);
            font-size: 0.88rem;
        }

        #progress { font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; color: var(--muted); margin-top: 10px; min-height: 20px; }
        #progress.error   { color: var(--danger); }
        #progress.success { color: var(--success); }
    </style>
</head>
<body>

<div class="topbar">
    <?php if ($is_admin): ?>
        <button class="admin-toggle" onclick="toggleSidebar()">⚙ Admin</button>
    <?php endif; ?>
    <div class="topbar-title">File Server</div>
    <span class="topbar-user"><?= htmlspecialchars($username) ?></span>
    <a href="logout.php" class="btn btn-ghost">Logout</a>
</div>

<div class="layout">

    <?php if ($is_admin): ?>
    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-inner">
            <h2>⚙ Admin Panel</h2>
            <div class="sidebar-section">
                <h3>Accounts</h3>
                <a href="admin_users.php" class="sidebar-btn">👥 View All Users</a>
                <a href="admin_create_user.php" class="sidebar-btn">➕ Create Account</a>
            </div>
            <div class="sidebar-section">
                <h3>System</h3>
                <a href="admin_users.php?filter=force_change" class="sidebar-btn">🔑 Pending Resets</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="main">
        <div class="page-title">Your Files</div>
        <div class="page-subtitle">// <?= htmlspecialchars($username) ?> &mdash; <?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?></div>

        <?php $bar_class = $usage_pct >= 90 ? 'danger' : ($usage_pct >= 70 ? 'warn' : ''); ?>
        <div class="storage-bar-wrap">
            <div class="storage-bar-header">
                <span>Storage</span>
                <span><?= htmlspecialchars($storage_info['disk_usage_fmt'] ?? '—') ?> / <?= htmlspecialchars($storage_info['storage_limit_fmt'] ?? '—') ?> (<?= $usage_pct ?>%)</span>
            </div>
            <div class="storage-bar-track">
                <div class="storage-bar-fill <?= $bar_class ?>" style="width:<?= $usage_pct ?>%"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-header-title">Files</span>
            </div>
            <?php if (empty($files)): ?>
                <div class="empty-state">No files uploaded yet.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Filename</th><th>Size</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?= htmlspecialchars($file['file_name']) ?></td>
                        <td class="file-size"><?= format_bytes($file['file_size']) ?></td>
                        <td>
                            <div class="actions">
                                <a href="download.php?file_id=<?= urlencode($file['file_id']) ?>" class="btn btn-ghost">↓ Download</a>
                                <form method="POST" action="delete.php" style="display:inline"
                                    onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($file['file_name'])) ?>?')">
                                    <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
                                    <button type="submit" class="btn btn-danger">✕ Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="upload-section">
            <h3>Upload File</h3>
            <form id="upload-form">
                <div class="upload-row">
                    <input type="file" id="file-input">
                    <input type="hidden" id="owner-id" value="<?= htmlspecialchars($user_id) ?>">
                    <button type="submit" class="btn btn-primary">↑ Upload</button>
                </div>
                <div id="progress"></div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
}

const CHUNK_SIZE = 8 * 1024 * 1024;

function setProgress(msg, type = '') {
    const el = document.getElementById('progress');
    el.textContent = msg;
    el.className   = type;
}

function validate_available_storage(file, owner_id) {
    const res = await fetch (FASTAPI_URL . "/files/upload/validate_available_storage/" . owner_id, {method: 'GET'});
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error ?? 'Validation Failed')        
    }
    const result = await res.json();
    if ((file.size <= res ))
        return('ok')
    return('not ok')
}

async function uploadSmall(file, owner_id) {
    const form = new FormData();
    form.append('file', file, file.name);
    form.append('owner_id', owner_id);
    const res = await fetch('upload.php', { method: 'POST', body: form });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error ?? 'Upload failed');
    }
}

async function uploadLarge(file, owner_id) {
    const total_chunks = Math.ceil(file.size / CHUNK_SIZE);
    const upload_id    = crypto.randomUUID();

    for (let i = 0; i < total_chunks; i++) {
        const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
        const form  = new FormData();
        form.append('chunk',       chunk, file.name);
        form.append('upload_id',   upload_id);
        form.append('chunk_index', i);
        form.append('owner_id',    owner_id);
        const res = await fetch('upload_chunk.php', { method: 'POST', body: form });
        if (!res.ok) throw new Error(`Chunk ${i} failed`);
        setProgress(`Uploading... ${Math.round(((i+1)/total_chunks)*100)}%`);
    }

    const cf = new FormData();
    cf.append('upload_id',    upload_id);
    cf.append('file_name',    file.name);
    cf.append('total_chunks', total_chunks);
    cf.append('owner_id',     owner_id);
    const res = await fetch('upload_complete.php', { method: 'POST', body: cf });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error ?? 'Upload failed');
    }
}

document.getElementById('upload-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const file     = document.getElementById('file-input').files[0];
    const owner_id = document.getElementById('owner-id').value;
    if (!file) { setProgress('No file selected.', 'error'); return; }
    setProgress('Uploading...');
    try {
        const validation = await validate_available_storage(owner_id)
        if (validation !=== 'ok' && validation === 'not ok') {
            setProgress('Insufficient Storage')
            exit($status)
        } else if (validation === 'ok') {
            continue
        }
        exit($status)
    } catch (err) {
        setProgress(err.message, 'error')
    }
    try {
        file.size <= CHUNK_SIZE ? await uploadSmall(file, owner_id) : await uploadLarge(file, owner_id);
        setProgress('Upload complete!', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        setProgress(err.message, 'error');
    }
});
</script>
</body>
</html>