<?php
$pageTitle = 'Register - Green Nursery';
require_once __DIR__ . '/includes/auth_check.php';
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>🌱 Create Account</h2>
        <p class="auth-subtitle">Join Green Nursery and start your plant journey</p>

        <div id="alertBox"></div>

        <form id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" id="registerBtn">Create Account</button>
        </form>

        <p class="auth-link">Already have an account? <a href="/login.php">Login here</a></p>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('registerBtn');
    const alertBox = document.getElementById('alertBox');

    const password        = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password !== confirmPassword) {
        alertBox.innerHTML = '<div class="alert alert-error">Passwords do not match.</div>';
        return;
    }

    btn.textContent = 'Creating account...';
    btn.disabled = true;

    const data = new URLSearchParams({
        action: 'register',
        username: document.getElementById('username').value,
        email: document.getElementById('email').value,
        password,
        confirm_password: confirmPassword
    });

    fetch('/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alertBox.innerHTML = '<div class="alert alert-success">Account created! Redirecting...</div>';
            const dest = res.role === 'admin' ? '/admin/index.php' : '/index.php';
            setTimeout(() => { window.location.href = dest; }, 1000);
        } else {
            alertBox.innerHTML = `<div class="alert alert-error">${res.message}</div>`;
            btn.textContent = 'Create Account';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alertBox.innerHTML = '<div class="alert alert-error">Something went wrong. Please try again.</div>';
        btn.textContent = 'Create Account';
        btn.disabled = false;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
