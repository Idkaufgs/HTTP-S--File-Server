<?php
require 'config.php';
session_start();

// Must be logged in to change password
if (!($_SESSION['logged_in'] ?? false)) {
    header("Location: login.php");
    exit;
}

$error = match($_GET['error'] ?? '') {
    'missing'   => 'Please fill in all fields.',
    'mismatch'  => 'New passwords do not match.',
    'short'     => 'New password must be at least 8 characters.',
    'same'      => 'New password must differ from your current password.',
    'incorrect' => 'Current password is incorrect.',
    'server'    => 'Could not connect to server. Please try again.',
    default     => ''
};

$forced = $_SESSION['force_password_change'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #0f1117;
            --surface: #1a1d27;
            --border:  #2a2d3a;
            --accent:  #4f9eff;
            --warn:    #f0a500;
            --text:    #e8eaf0;
            --muted:   #6b7280;
            --error:   #ff5f5f;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.3;
            pointer-events: none;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 380px;
            position: relative;
            animation: fadeUp 0.3s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--warn);  /* yellow instead of blue — signals action required */
            border-radius: 8px 8px 0 0;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .title    { font-family: 'IBM Plex Mono', monospace; font-size: 1.3rem; font-weight: 600; margin-bottom: 6px; }
        .subtitle { font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; color: var(--muted); margin-bottom: 16px; }

        .forced-notice {
            font-size: 0.83rem;
            padding: 10px 14px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 3px solid var(--warn);
            background: rgba(240,165,0,0.1);
            color: var(--warn);
        }

        .message {
            font-size: 0.83rem;
            padding: 10px 14px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 3px solid var(--error);
            background: rgba(255,95,95,0.1);
            color: var(--error);
        }

        .field       { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 0.78rem;
            font-family: 'IBM Plex Mono', monospace;
            color: var(--muted);
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 10px 14px;
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
        }

        input:focus        { border-color: var(--accent); }
        input::placeholder { color: var(--muted); }

        button[type=submit] {
            width: 100%;
            padding: 11px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 5px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity 0.2s, transform 0.1s;
        }

        button[type=submit]:hover  { opacity: 0.88; }
        button[type=submit]:active { transform: scale(0.98); }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">File Server</div>
        <div class="subtitle">// change password</div>

        <?php if ($forced): ?>
            <div class="forced-notice">A password change is required before you can continue.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="change_password_handler.php" method="POST">
            <div class="field">
                <label for="old_pwd">Current Password</label>
                <input type="password" id="old_pwd" name="old_pwd"
                       placeholder="Enter current password" required autofocus>
            </div>
            <div class="field">
                <label for="new_pwd">New Password</label>
                <input type="password" id="new_pwd" name="new_pwd"
                       placeholder="Choose a new password" required>
            </div>
            <div class="field">
                <label for="new_pwd_confirm">Confirm New Password</label>
                <input type="password" id="new_pwd_confirm" name="new_pwd_confirm"
                       placeholder="Repeat new password" required>
            </div>
            <button type="submit">Update Password →</button>
        </form>
    </div>
</body>
</html>
