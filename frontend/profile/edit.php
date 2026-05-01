<?php
declare(strict_types=1);
$page_title = 'Edit Personal Info';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if ($name && $email) {
        // Handle Avatar Upload
        $avatar_name = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target = __DIR__ . '/../uploads/profile-images/' . $new_name;
                
                // Ensure directory exists
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    if ($avatar_name && file_exists(__DIR__ . '/../uploads/profile-images/' . $avatar_name)) {
                        unlink(__DIR__ . '/../uploads/profile-images/' . $avatar_name);
                    }
                    $avatar_name = $new_name;
                }
            } else {
                flash_set('error', 'Invalid image format.');
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, avatar = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $phone, $bio, $avatar_name, $user_id]);
        
        // Update session
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['avatar'] = $avatar_name;
        
        flash_set('success', 'Profile updated successfully.');
        redirect('profile/edit.php');
    } else {
        flash_set('error', 'Name and email are required.');
    }
}
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <h3 style="font-weight: 700; font-size: 1.125rem;">Personal Information</h3>
        <p style="font-size: 0.875rem; color: var(--text-muted);">Manage your account details and profile identity.</p>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div style="display: flex; gap: 3rem; flex-wrap: wrap;">
                <!-- Avatar Section -->
                <div style="flex: 1; min-width: 200px; text-align: center;">
                    <div style="position: relative; display: inline-block;">
                        <?php if ($user['avatar']): ?>
                            <img src="<?= e(url('uploads/profile-images/' . $user['avatar'])) ?>" alt="" class="avatar-lg">
                        <?php else: ?>
                            <div class="avatar-lg" style="background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800;">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <label for="avatar-input" style="position: absolute; bottom: 5px; right: 5px; background: var(--primary); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatar-input" style="display: none;" onchange="this.form.submit()">
                    </div>
                    <h4 style="margin-top: 1rem; font-weight: 700;"><?= e($user['name']) ?></h4>
                    <p style="font-size: 0.875rem; color: var(--text-muted);"><?= e($user['email']) ?></p>
                </div>

                <!-- Form Section -->
                <div style="flex: 2; min-width: 300px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio / Delivery Notes</label>
                        <textarea name="bio" class="form-control" rows="4" placeholder="Tell us about yourself or add general delivery notes..."><?= e($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= e(url('profile/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
