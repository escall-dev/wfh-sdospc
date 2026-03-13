<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    header('Location: /' . (in_array($role, ['admin', 'superadmin', 'hr_timekeeping']) ? 'admin' : 'employee') . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFH-SDOSPC</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/img-ref/SDO_Sanpedro_Logo.png">
</head>

<body>
    <div class="login-page-bg">
        <div class="login-container">
            <div class="login-left">
                <div class="login-logos">
                    <img src="/assets/img-ref/SDO_Sanpedro_Logo.png" alt="SDO San Pedro City" class="login-logo">
                    <img src="/assets/img-ref/sdoClick.png" alt="SDO Click" class="sdoclick-logo">
                </div>
                <h1 class="desktop-brand">Work From Home</h1>
                <h2 class="desktop-brand">Attendance Portal</h2>
                <h1 class="mobile-brand">SDO of San Pedro City</h1>
                <h2 class="mobile-brand">WFH Portal</h2>
                <p class="login-tagline">"Puso at Galing ang Gabay sa Gawa"</p>
                <p class="login-description">Track your Attendance Seamlessly<br>One Click Real Quick at<br> SDO
                    San Pedro City.</p>
                <p class="login-copyright">&copy; <?= date('Y') ?> Schools Division Office of San Pedro City</p>
                
            </div>
            <div class="login-right">
                <div class="login-form-wrapper">
                    <h1>Welcome Back</h1>
                    <p class="subtitle">Sign in to access your account</p>

                    <div class="login-error" id="login-error">
                        <span class="login-error-icon"><?= icon('x', '16') ?></span>
                        <span id="login-error-text"></span>
                    </div>

                    <form id="login-form" onsubmit="return handleLogin(event)">
                        <div class="form-group">
                            <label>
                                <span class="required">*</span> Employee ID
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon"><?= icon('user', '18') ?></span>
                                <input type="text" id="username" name="username" placeholder="e.g., 01234567" required
                                    autocomplete="username">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><span class="required">*</span> Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><?= icon('lock', '18') ?></span>
                                <input type="password" id="password" name="password" placeholder="Enter your password"
                                    required autocomplete="current-password">
                                <button type="button" class="password-toggle"
                                    data-icon-eye="<?= htmlspecialchars(icon('eye', '18')) ?>"
                                    data-icon-off="<?= htmlspecialchars(icon('eye-off', '18')) ?>"><?= icon('eye-off', '18') ?></button>
                            </div>
                        </div>

                        <div class="remember-me">
                            <label class="remember-me-label">
                                <input type="checkbox" id="remember-me">
                                <span class="remember-me-custom"></span>
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" id="login-btn">
                            Sign In
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script>
        // Load saved credentials on page load
        (function () {
            const saved = JSON.parse(localStorage.getItem('wfh_remember') || 'null');
            if (saved) {
                document.getElementById('username').value = saved.username || '';
                document.getElementById('password').value = saved.password || '';
                document.getElementById('remember-me').checked = true;
            }
        })();

        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('login-btn');
            const errorDiv = document.getElementById('login-error');
            const errorText = document.getElementById('login-error-text');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner spinner-white"></span> Signing in...';
            errorDiv.classList.remove('show');

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember-me').checked;

            if (remember) {
                localStorage.setItem('wfh_remember', JSON.stringify({ username, password }));
            } else {
                localStorage.removeItem('wfh_remember');
            }

            const result = await fetchAPI('/api/login.php', { username, password, device_fp: getDeviceFingerprint() });

            if (result.success) {
                window.location.href = result.redirect;
            } else {
                errorText.textContent = result.message || 'Login failed. Please try again.';
                errorDiv.classList.remove('fade-out');
                errorDiv.classList.add('show');
                btn.disabled = false;
                btn.textContent = 'Sign In';

                // Auto-dismiss after 3 seconds with fade
                setTimeout(() => {
                    errorDiv.classList.add('fade-out');
                    setTimeout(() => {
                        errorDiv.classList.remove('show', 'fade-out');
                    }, 500);
                }, 3000);
            }

            return false;
        }
    </script>
</body>

</html>