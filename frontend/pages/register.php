<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/backend/includes/init.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($name === '')
        $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Valid email is required.';
    if ($phone === '')
        $errors[] = 'Phone is required.';
    if (strlen($password) < 6)
        $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password2)
        $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, $phone, $hash, 'customer']);
            $userId = (int) db()->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'customer',
            ];

            flash_set('success', 'Account created.');
            redirect('index.php');
        }
    }
}

$hide_nav = true;
$page_title = 'Create Account - ' . APP_NAME;
require APP_ROOT . '/backend/includes/layout_top.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <a class="auth-brand" href="<?= e(url('index.php')) ?>">
            <i class="fas fa-mug-hot"></i>
            <?= e(APP_NAME) ?>
        </a>

        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">Create your profile to place orders and track history.</p>

        <?php if ($errors): ?>
            <div class="alert alert--error" style="border-radius: 12px;">
                <i class="fas fa-circle-exclamation"></i>
                <div>
                    <?= e(implode(' ', $errors)) ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="reg_name">Full name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input id="reg_name" class="form-input" name="name" value="<?= e($name) ?>" required
                            placeholder="Your name" autocomplete="name">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reg_phone">Phone</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input id="reg_phone" class="form-input" name="phone" value="<?= e($phone) ?>" required
                            placeholder="+251..." autocomplete="tel">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label" for="reg_email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input id="reg_email" class="form-input" type="email" name="email" value="<?= e($email) ?>" required
                        placeholder="you@example.com" autocomplete="email">
                </div>
            </div>

            <div class="form-grid" style="margin-top: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="reg_password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input id="reg_password" class="form-input" type="password" name="password" required
                            placeholder="Create a password" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="reg_password2">Confirm</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input id="reg_password2" class="form-input" type="password" name="password2" required
                            placeholder="Confirm password" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary btn-large" type="submit" style="width:100%; margin-top: 1.25rem;">
                Create Account
            </button>

            <p class="muted" style="text-align:center; margin: 1.25rem 0 0;">
                Already have an account?
                <a href="<?= e(url('login.php')) ?>" class="nav-link" style="display:inline; padding:0;">Sign in</a>
            </p>
        </form>
    </div>
</div>

<?php require APP_ROOT . '/backend/includes/layout_bottom.php'; ?>