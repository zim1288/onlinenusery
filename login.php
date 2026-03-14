<?php
$pageTitle = 'Login - Green Nursery';
require_once __DIR__ . '/includes/auth_check.php';
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>🌿 Welcome Back</h2>
        <p class="auth-subtitle">Sign in to your Green Nursery account</p>

        <div id="alertBox"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control" placeholder="Enter username or email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" id="loginBtn">Login</button>
        </form>

        <p class="auth-link">Don't have an account? <a href="/register.php">Register here</a></p>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    btn.textContent = 'Logging in...';
    btn.disabled = true;

    const data = new URLSearchParams({
        action: 'login',
        identifier: document.getElementById('identifier').value,
        password: document.getElementById('password').value
    });

    fetch('/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('alertBox').innerHTML = '<div class="alert alert-success">Login successful! Redirecting...</div>';
            setTimeout(() => {
                window.location.href = res.role === 'admin' ? '/admin/index.php' : '/index.php';
            }, 800);
        } else {
            document.getElementById('alertBox').innerHTML = `<div class="alert alert-error">${res.message}</div>`;
            btn.textContent = 'Login';
            btn.disabled = false;
        }
    })
    .catch(() => {
        document.getElementById('alertBox').innerHTML = '<div class="alert alert-error">Something went wrong. Please try again.</div>';
        btn.textContent = 'Login';
        btn.disabled = false;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
