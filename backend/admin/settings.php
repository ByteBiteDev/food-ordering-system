<?php
declare(strict_types=1);
$page_title = 'System Settings';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$admin = current_user();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($name && $email) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $admin['user_id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            flash_set('success', 'Profile updated successfully.');
        }
    } elseif ($action === 'update_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$admin['user_id']]);
        $stored_hash = $stmt->fetchColumn();

        if (password_verify($old_pass, $stored_hash)) {
            if ($new_pass && $new_pass === $confirm_pass) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$hash, $admin['user_id']]);
                flash_set('success', 'Password changed successfully.');
            } else {
                flash_set('error', 'New passwords do not match.');
            }
        } else {
            flash_set('error', 'Current password incorrect.');
        }
    } elseif ($action === 'site_settings') {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        flash_set('success', 'Site settings updated.');
    }
    redirect('admin/settings.php');
}

// Fetch current site settings
$settings_stmt = $pdo->query("SELECT * FROM settings");
$site_settings = [];
while ($row = $settings_stmt->fetch()) {
    $site_settings[$row['setting_key']] = $row['setting_value'];
}
?>
<div class="page-header">
    <div class="page-title">
        <h2>System Settings</h2>
        <p>Configure your administrative profile and site preferences.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Profile Settings -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3>Admin Profile</h3>
            </div>
            <div style="padding: 1.5rem;">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($admin['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= e($admin['email']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            <div style="padding: 1.5rem;">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Site Settings -->
    <div class="card">
        <div class="card-header">
            <h3>Basic Site Settings</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="site_settings">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="settings[site_name]" class="form-control" value="<?= e($site_settings['site_name'] ?? 'Food Ordering System') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="settings[contact_email]" class="form-control" value="<?= e($site_settings['contact_email'] ?? 'admin@example.com') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= e($site_settings['currency_symbol'] ?? '$') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order Amount</label>
                    <input type="number" step="0.01" name="settings[min_order]" class="form-control" value="<?= e($site_settings['min_order'] ?? '0.00') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save Site Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
