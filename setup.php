<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/init.php';

$lockFile = __DIR__ . '/setup.lock';
if (is_file($lockFile)) {
    require __DIR__ . '/backend/includes/layout_top.php';
    echo '<div class="card" style="margin-top:14px;">Setup is locked. Delete <code>setup.lock</code> to rerun (not recommended).</div>';
    require __DIR__ . '/backend/includes/layout_bottom.php';
    exit;
}

function run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Failed to read SQL file: ' . $path);
    }

    // Remove simple line comments
    $lines = preg_split("/\r\n|\n|\r/", $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if (str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

$errors = [];
$success = null;

$adminName = 'Admin';
$adminEmail = 'admin@example.com';
$adminPhone = '0700000000';
$adminPassword = 'Admin@12345';
$importSeed = true;

if (is_post()) {
    verify_csrf();

    $adminName = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPhone = trim((string)($_POST['admin_phone'] ?? ''));
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $importSeed = isset($_POST['import_seed']);

    if ($adminName === '') $errors[] = 'Admin name is required.';
    if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email is required.';
    if ($adminPhone === '') $errors[] = 'Admin phone is required.';
    if (strlen($adminPassword) < 8) $errors[] = 'Admin password must be at least 8 characters.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            run_sql_file($pdo, __DIR__ . '/backend/database/schema.sql');
            if ($importSeed) {
                run_sql_file($pdo, __DIR__ . '/backend/database/seed.sql');
            }

            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$adminEmail]);
            $existing = $stmt->fetch();
            if ($existing) {
                $errors[] = 'Admin email already exists.';
            } else {
                $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$adminName, $adminEmail, $adminPhone, $hash, 'admin']);
                $pdo->commit();

                file_put_contents($lockFile, 'locked ' . date('c'));
                $success = 'Setup completed. You can now login as admin.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Setup failed: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/backend/includes/layout_top.php';
?>

<div class="card" style="margin-top:14px;">
  <h1 style="margin:6px 0 10px;">Project Setup</h1>
  <div class="muted">
    This runs the database schema and creates the first admin account. After success, setup is locked.
    Please change the admin password after your first login.
  </div>

  <?php if ($success): ?>
    <div class="alert alert--success" style="margin-top:14px;"><?= e($success) ?></div>
    <div class="actions">
      <a class="btn btn--primary" href="<?= e(url('login.php')) ?>">Go to Login</a>
      <a class="btn" href="<?= e(url('admin/index.php')) ?>">Go to Admin</a>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="alert alert--error" style="margin-top:14px;"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" style="margin-top:14px;">
      <?= csrf_field() ?>
      <h2 style="margin:6px 0 10px;">Admin Account</h2>
      <label>Name</label>
      <input name="admin_name" value="<?= e($adminName) ?>" required>

      <label>Email</label>
      <input type="email" name="admin_email" value="<?= e($adminEmail) ?>" required>

      <label>Phone</label>
      <input name="admin_phone" value="<?= e($adminPhone) ?>" required>

      <label>Password</label>
      <input type="password" name="admin_password" value="<?= e($adminPassword) ?>" required>

      <label style="display:flex;gap:10px;align-items:center;margin-top:12px;">
        <input type="checkbox" name="import_seed" <?= $importSeed ? 'checked' : '' ?> style="width:auto;">
        Import sample categories/foods
      </label>

      <div class="actions">
        <button class="btn btn--success" type="submit">Run Setup</button>
        <a class="btn" href="<?= e(url('index.php')) ?>">Back to App</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/backend/includes/layout_bottom.php'; ?>
