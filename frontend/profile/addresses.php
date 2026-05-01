<?php
declare(strict_types=1);
$page_title = 'Saved Addresses';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$user_id = (int)$user['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $label = trim($_POST['label'] ?? 'Home');
        $address_line = trim($_POST['address_line'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $addr_id = (int)($_POST['address_id'] ?? 0);

        if ($address_line && $city) {
            if ($is_default) {
                $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
            }

            if ($addr_id) {
                $stmt = $pdo->prepare("UPDATE addresses SET label = ?, address_line = ?, city = ?, is_default = ? WHERE address_id = ? AND user_id = ?");
                $stmt->execute([$label, $address_line, $city, $is_default, $addr_id, $user_id]);
                flash_set('success', 'Address updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, label, address_line, city, is_default) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $label, $address_line, $city, $is_default]);
                flash_set('success', 'Address added.');
            }
        }
    } elseif ($action === 'delete') {
        $addr_id = (int)$_POST['address_id'];
        $pdo->prepare("DELETE FROM addresses WHERE address_id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
        flash_set('success', 'Address removed.');
    } elseif ($action === 'set_default') {
        $addr_id = (int)$_POST['address_id'];
        $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
        flash_set('success', 'Default address updated.');
    }
    redirect('profile/addresses.php');
}

$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addresses->execute([$user_id]);
$addrs = $addresses->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h3 style="font-weight: 700; margin-bottom: 0.25rem;">Manage Delivery Addresses</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Save your home, work, and other delivery locations for faster checkout.</p>
    </div>
    <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Address</button>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
    <?php foreach ($addrs as $addr): ?>
        <div class="card" style="margin-bottom: 0;">
            <div class="card-body" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(76, 175, 80, 0.1); color: var(--brand-primary); display: flex; align-items: center; justify-content: center;">
                            <i class="fas <?= $addr['label'] === 'Home' ? 'fa-home' : ($addr['label'] === 'Work' ? 'fa-briefcase' : 'fa-map-marker-alt') ?>"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700;"><?= e($addr['label']) ?></div>
                            <?php if ($addr['is_default']): ?>
                                <span class="badge badge-success" style="font-size: 0.65rem;">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick='openModal(<?= json_encode($addr) ?>)' class="btn btn-secondary btn-sm" style="padding: 0.4rem;"><i class="fas fa-edit"></i></button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this address?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.4rem; color: var(--danger);"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1rem;">
                    <?= e($addr['address_line']) ?><br>
                    <?= e($addr['city']) ?>
                </div>
                <?php if (!$addr['is_default']): ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_default">
                        <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem;">Set as Default</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div id="address-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
    <div class="card" style="width: 100%; max-width: 500px; margin-bottom: 0;">
        <div class="card-header"><h3 id="modal-title" style="font-weight: 700; font-size: 1.125rem;">Add Address</h3></div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="modal-action" value="add">
                <input type="hidden" name="address_id" id="modal-id" value="">
                
                <div class="form-group">
                    <label class="form-label">Label (e.g., Home, Work)</label>
                    <input type="text" name="label" id="modal-label" class="form-control" placeholder="Home" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address Line</label>
                    <textarea name="address_line" id="modal-line" class="form-control" rows="3" placeholder="123 Street, Apt 4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" id="modal-city" class="form-control" placeholder="New York" required>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_default" id="modal-default">
                    <label class="form-label" style="margin-bottom: 0;">Set as default address</label>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">Save Address</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1; justify-content: center;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(addr = null) {
    const modal = document.getElementById('address-modal');
    modal.style.display = 'flex';
    if (addr) {
        document.getElementById('modal-title').innerText = 'Edit Address';
        document.getElementById('modal-action').value = 'edit';
        document.getElementById('modal-id').value = addr.address_id;
        document.getElementById('modal-label').value = addr.label;
        document.getElementById('modal-line').value = addr.address_line;
        document.getElementById('modal-city').value = addr.city;
        document.getElementById('modal-default').checked = !!addr.is_default;
    } else {
        document.getElementById('modal-title').innerText = 'Add Address';
        document.getElementById('modal-action').value = 'add';
        document.getElementById('modal-id').value = '';
        document.getElementById('modal-label').value = '';
        document.getElementById('modal-line').value = '';
        document.getElementById('modal-city').value = '';
        document.getElementById('modal-default').checked = false;
    }
}
function closeModal() {
    document.getElementById('address-modal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
