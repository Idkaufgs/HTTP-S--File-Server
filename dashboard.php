<?php
session_start();

// --- 1. Check login ---
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.html");
    exit;
}

// --- 2. Simulate fetching files for the logged-in user ---
// In real backend, replace this with database query or filesystem scan
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Unknown';

$files = [
    // Example static files (replace with DB or filesystem read)
    ["name" => "example1.txt", "size" => "12 KB", "uploaded" => "2026-02-07"],
    ["name" => "example2.jpg", "size" => "1.2 MB", "uploaded" => "2026-02-06"],
    ["name" => "notes.pdf", "size" => "450 KB", "uploaded" => "2026-02-05"]
];
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
            <th>Uploaded</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($files as $file): ?>
        <tr>
            <td><?php echo htmlspecialchars($file['name']); ?></td>
            <td><?php echo htmlspecialchars($file['size']); ?></td>
            <td><?php echo htmlspecialchars($file['uploaded']); ?></td>
            <td>
                <a href="#" class="button">Download</a>
                <a href="#" class="button" style="background:red;">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p style="margin-top:20px;">*This is a placeholder dashboard. Replace `$files` array with your real user file data.*</p>

</body>
</html>
