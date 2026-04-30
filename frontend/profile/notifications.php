<?php
declare(strict_types=1);
$page_title = 'Notifications';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Handle Mark as Read
if (isset($_GET['read'])) {
    $notif_id = (int)$_GET['read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?")->execute([$notif_id, $user_id]);
    redirect('profile/notifications.php');
}

// Handle Mark All as Read
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    redirect('profile/notifications.php');
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h3 style="font-weight: 700; margin-bottom: 0.25rem;">Notification Center</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Stay updated with your orders and account activity.</p>
    </div>
    <a href="?mark_all=1" class="btn btn-secondary btn-sm">Mark all as read</a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
            <div style="padding: 4rem; text-align: center;">
                <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"><i class="fas fa-bell-slash"></i></div>
                <p style="color: var(--text-muted);">You're all caught up! No new notifications.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; gap: 1.5rem; background: <?= $notif['is_read'] ? 'transparent' : 'rgba(79, 70, 229, 0.03)' ?>; transition: background 0.2s;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= $notif['is_read'] ? 'var(--bg-main)' : 'var(--primary-soft)' ?>; color: <?= $notif['is_read'] ? 'var(--text-muted)' : 'var(--primary)' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas <?= $notif['type'] === 'success' ? 'fa-check-circle' : ($notif['type'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h4 style="font-weight: 700; font-size: 1rem; color: <?= $notif['is_read'] ? 'var(--text-muted)' : 'var(--text-main)' ?>;"><?= e($notif['title']) ?></h4>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></span>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;"><?= e($notif['message']) ?></p>
                        <?php if (!$notif['is_read']): ?>
                            <a href="?read=<?= $notif['notification_id'] ?>" style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-decoration: none;">Mark as read</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
