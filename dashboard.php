<?php
require 'config.php';
session_start();

if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.html");
    exit;
}

$user_id  = $_SESSION['user_id']  ?? 'Unknown';
$username = $_SESSION['username'] ?? 'Unknown';

function fetch_files(string $user_id): array {
    $url      = FASTAPI_URL . "/files/" . $user_id;
    $response = file_get_contents($url);
    if ($response === false) return [];
    return json_decode($response, true) ?? [];
}

$files = fetch_files($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($username); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        th { background: #eee; }
        tr:nth-child(even) { background: #fafafa; }
        .logout { float: right; margin-top: -40px; }
        .button { padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .button:hover { background: #0056b3; }
        #progress { margin-left: 10px; font-size: 0.9em; color: #555; }
    </style>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <a href="logout.php" class="button logout">Logout</a>

    <h2>Your Files</h2>
    <table>
        <tr>
            <th>Filename</th>
            <th>Size</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($files as $file): ?>
        <tr>
            <td><?= htmlspecialchars($file['file_name']) ?></td>
            <td><?= $file['file_size'] ?> bytes</td>
            <td>
                <!-- Download -->
                <a href="download.php?file_id=<?= urlencode($file['file_id']) ?>">
                    <button>Download</button>
                </a>

                <!-- Delete -->
                <form method="POST" action="delete.php" style="display:inline"
                    onsubmit="return confirm('Delete <?= htmlspecialchars($file['file_name']) ?>?')">
                    <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Upload File</h2>
    <!-- No action/method — JS handles submission entirely -->
    <form id="upload-form">
        <input type="file" id="file-input" name="file">
        <input type="hidden" id="owner-id" value="<?= htmlspecialchars($user_id) ?>">
        <button type="submit">Upload</button>
        <span id="progress"></span>
    </form>

<script>
const CHUNK_SIZE = 8 * 1024 * 1024; // 8MB

async function uploadSmall(file, owner_id) {
    const form = new FormData();
    form.append('file',     file, file.name);
    form.append('owner_id', owner_id);

    const res = await fetch('upload.php', { method: 'POST', body: form });
    return res.ok;
}

async function uploadLarge(file, owner_id) {
    const total_chunks = Math.ceil(file.size / CHUNK_SIZE);
    const upload_id    = crypto.randomUUID();

    for (let i = 0; i < total_chunks; i++) {
        const start = i * CHUNK_SIZE;
        const chunk = file.slice(start, start + CHUNK_SIZE);

        const form = new FormData();
        form.append('chunk',       chunk, file.name);
        form.append('upload_id',   upload_id);
        form.append('chunk_index', i);
        form.append('owner_id',    owner_id);

        const res = await fetch('upload_chunk.php', { method: 'POST', body: form });
        if (!res.ok) return false;

        const pct = Math.round(((i + 1) / total_chunks) * 100);
        document.getElementById('progress').textContent = `Uploading... ${pct}%`;
    }

    const completeForm = new FormData();
    completeForm.append('upload_id',    upload_id);
    completeForm.append('file_name',    file.name);
    completeForm.append('total_chunks', total_chunks);
    completeForm.append('owner_id',     owner_id);

    const res = await fetch('upload_complete.php', { method: 'POST', body: completeForm });
    return res.ok;
}

document.getElementById('upload-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const file     = document.getElementById('file-input').files[0];
    const owner_id = document.getElementById('owner-id').value;

    if (!file) {
        document.getElementById('progress').textContent = 'No file selected.';
        return;
    }

    document.getElementById('progress').textContent = 'Uploading...';

    const ok = file.size <= CHUNK_SIZE
        ? await uploadSmall(file, owner_id)
        : await uploadLarge(file, owner_id);

    if (ok) {
        window.location.href = 'dashboard.php';
    } else {
        document.getElementById('progress').textContent = 'Upload failed!';
    }
});
</script>

</body>
</html>

