<?php
declare(strict_types=1);
$page_title = 'Security Settings';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Log this visit if it's the first time in this session (simplified)
if (!isset($_SESSION['activity_logged'])) {
    $stmt = $pdo->prepare("INSERT INTO login_activity (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    $_SESSION['activity_logged'] = true;
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    require_csrf();
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stored_hash = $stmt->fetchColumn();

    if (password_verify($old_pass, $stored_hash)) {
        if ($new_pass && $new_pass === $confirm_pass) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$hash, $user_id]);
            flash_set('success', 'Password changed successfully.');
        } else {
            flash_set('error', 'New passwords do not match.');
        }
    } else {
        flash_set('error', 'Current password incorrect.');
    }
    redirect('profile/security.php');
}

$stmt = $pdo->prepare("SELECT * FROM login_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$activities = $stmt->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem;">
    <!-- Password Change -->
    <div class="card">
        <div class="card-header"><h3 style="font-weight: 700; font-size: 1.125rem;">Change Password</h3></div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
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
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Login Activity -->
    <div>
        <div class="card">
            <div class="card-header"><h3 style="font-weight: 700; font-size: 1.125rem;">Recent Security Activity</h3></div>
            <div class="card-body" style="padding: 0;">
                <?php foreach ($activities as $act): ?>
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--bg-main); color: var(--text-muted); display: flex; align-items: center; justify-content: center;">
                            <i class="fas <?= str_contains(strtolower($act['user_agent']), 'mobile') ? 'fa-mobile-alt' : 'fa-laptop' ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.875rem;"><?= e($act['ip_address']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, Y at H:i', strtotime($act['created_at'])) ?></div>
                        </div>
                        <?php if ($act['activity_id'] === $activities[0]['activity_id']): ?>
                            <span class="badge badge-success" style="font-size: 0.6rem;">CURRENT</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card" style="background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.1);">
            <div class="card-body">
                <h4 style="font-weight: 700; color: var(--danger); margin-bottom: 0.5rem;">Delete Account</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">Once you delete your account, there is no going back. Please be certain.</p>
                <button onclick="alert('Account deletion requires support confirmation.')" class="btn btn-secondary" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">Deactivate Account</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
