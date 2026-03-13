<?php
$pageTitle = 'Change Password';
$currentPage = 'change_password';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/icons.php';
requireLogin();

if (!mustChangePassword()) {
    header('Location: /employee/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - SDO San Pedro City</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/img-ref/SDO_Sanpedro_Logo.png">
</head>
<body>

<div class="login-page-bg">
    <div class="change-password-container">
        <div class="change-password-card">
            <div class="change-password-header">
                <div class="change-password-icon"><?= icon('key', '32') ?></div>
                <h1>Change Your Password</h1>
                <p>For your security, you must change your password before continuing. Your current password is your Employee ID.</p>
            </div>

            <div class="login-error" id="cp-error">
                <span class="login-error-icon"><?= icon('x', '16') ?></span>
                <span id="cp-error-text"></span>
            </div>

            <div class="login-error" id="cp-success" style="background:var(--success-bg);border-color:var(--success);color:var(--success);">
                <span class="login-error-icon"><?= icon('check-circle', '16') ?></span>
                <span id="cp-success-text"></span>
            </div>

            <form id="change-password-form" onsubmit="return handleChangePassword(event)">
                <div class="form-group">
                    <label><span class="required">*</span> Current Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><?= icon('lock', '18') ?></span>
                        <input type="password" id="current_password" placeholder="Your Employee ID" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><span class="required">*</span> New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><?= icon('key', '18') ?></span>
                        <input type="password" id="new_password" placeholder="At least 6 characters" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label><span class="required">*</span> Confirm New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><?= icon('key', '18') ?></span>
                        <input type="password" id="confirm_password" placeholder="Re-enter new password" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="cp-btn">
                    <?= icon('check', '16') ?> Update Password
                </button>

                <a href="/logout.php" class="btn btn-secondary" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-top:0.75rem;text-decoration:none;">
                    <?= icon('arrow-left', '16') ?> Back to Login
                </a>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script>
async function handleChangePassword(e) {
    e.preventDefault();
    const btn = document.getElementById('cp-btn');
    const errorDiv = document.getElementById('cp-error');
    const errorText = document.getElementById('cp-error-text');
    const successDiv = document.getElementById('cp-success');
    const successText = document.getElementById('cp-success-text');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner spinner-white"></span> Updating...';
    errorDiv.classList.remove('show');
    successDiv.classList.remove('show');

    const result = await fetchAPI('/api/change_password.php', {
        current_password: document.getElementById('current_password').value,
        new_password: document.getElementById('new_password').value,
        confirm_password: document.getElementById('confirm_password').value
    });

    if (result.success) {
        successText.textContent = result.message;
        successDiv.classList.add('show');
        setTimeout(() => { window.location.href = result.redirect; }, 1500);
    } else {
        errorText.textContent = result.message || 'Failed to change password.';
        errorDiv.classList.add('show');
        btn.disabled = false;
        btn.innerHTML = '<?= icon('check', '16') ?> Update Password';
    }

    return false;
}
</script>
</body>
</html>
