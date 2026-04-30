<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$email = '';
$error = null;

if (is_post()) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT user_id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        $error = 'Invalid email or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'user_id' => (int) $user['user_id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];

        flash_set('success', 'Signed in.');
        redirect(((string) $user['role'] === 'admin') ? 'admin/index.php' : 'index.php');
    }
}

$hide_nav = true;
$page_title = 'Sign In - ' . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <a class="auth-brand" href="<?= e(url('index.php')) ?>">
            <i class="fas fa-mug-hot"></i>
            <?= e(APP_NAME) ?>
        </a>

        <h1 class="auth-title">Sign in</h1>
        <p class="auth-subtitle">Welcome back. Continue to your account.</p>

        <?php if ($error): ?>
            <div class="alert alert--error" style="border-radius: 12px;">
                <i class="fas fa-circle-exclamation"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label" for="login_email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input id="login_email" class="form-input" type="email" name="email" value="<?= e($email) ?>"
                        required placeholder="you@example.com" autocomplete="email">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="login_password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input id="login_password" class="form-input" type="password" name="password" required
                        placeholder="Your password" autocomplete="current-password">
                </div>
            </div>

            <button class="btn btn-primary btn-large" type="submit" style="width:100%;">Sign In</button>

            <p class="muted" style="text-align:center; margin: 1.25rem 0 0;">
                Don't have an account?
                <a href="<?= e(url('register.php')) ?>" class="nav-link" style="display:inline; padding:0;">Create
                    one</a>
            </p>
        </form>
    </div>
</div>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>