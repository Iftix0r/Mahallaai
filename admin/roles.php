<?php 
require_once 'header.php'; 

$msg = '';
$msgType = '';

// Add new admin/manager
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $fullname = trim($_POST['fullname']);
    
    // Only system can create admins, admin can create managers
    if ($role === 'system' && $adminRole !== 'system') {
        $msg = 'Faqat tizim administratori "system" rol bera oladi!';
        $msgType = 'danger';
    } elseif ($role === 'admin' && !hasRole($adminRole, 'system')) {
        $msg = 'Faqat tizim administratori "admin" rol bera oladi!';
        $msgType = 'danger';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admins (username, password, role, fullname) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $fullname]);
            $msg = "✅ \"{$fullname}\" muvaffaqiyatli qo'shildi!";
            $msgType = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $msg = 'Bu foydalanuvchi nomi allaqachon mavjud!';
            } else {
                $msg = 'Xatolik: ' . $e->getMessage();
            }
            $msgType = 'danger';
        }
    }
}

// Update role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $targetId = intval($_POST['admin_id']);
    $newRole = $_POST['new_role'];
    
    // Check permissions
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetAdmin = $stmt->fetch();
    
    if (!$targetAdmin) {
        $msg = 'Foydalanuvchi topilmadi!';
        $msgType = 'danger';
    } elseif ($targetAdmin['username'] === 'admin' && $adminRole !== 'system') {
        $msg = 'Asosiy admin rolini o\'zgartirish mumkin emas!';
        $msgType = 'danger';
    } elseif ($newRole === 'system' && $adminRole !== 'system') {
        $msg = 'System rolini faqat tizim administratori bera oladi!';
        $msgType = 'danger';
    } else {
        $stmt = $db->prepare("UPDATE admins SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $targetId]);
        $msg = "✅ \"{$targetAdmin['username']}\" roli \"" . getRoleLabel($newRole) . "\" ga o'zgartirildi!";
        $msgType = 'success';
    }
}

// Toggle active status
if (isset($_GET['toggle_active'])) {
    $targetId = intval($_GET['toggle_active']);
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetAdmin = $stmt->fetch();
    
    if ($targetAdmin && $targetAdmin['username'] !== 'admin') {
        $newStatus = $targetAdmin['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $targetId]);
        $action = $newStatus ? 'faollashtirildi' : 'bloklandi';
        $msg = "✅ \"{$targetAdmin['username']}\" {$action}!";
        $msgType = 'success';
    } else {
        $msg = 'Asosiy admin bloklanishi mumkin emas!';
        $msgType = 'danger';
    }
}

// Delete admin
if (isset($_GET['delete_admin'])) {
    $targetId = intval($_GET['delete_admin']);
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetAdmin = $stmt->fetch();
    
    if ($targetAdmin && $targetAdmin['username'] !== 'admin') {
        // Only system can delete admins
        if (hasRole($adminRole, 'system') || ($adminRole === 'admin' && $targetAdmin['role'] === 'manager')) {
            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$targetId]);
            $msg = "✅ \"{$targetAdmin['username']}\" o'chirildi!";
            $msgType = 'success';
        } else {
            $msg = 'Bu amalni bajarish uchun ruxsatingiz yo\'q!';
            $msgType = 'danger';
        }
    } else {
        $msg = 'Asosiy admin o\'chirilishi mumkin emas!';
        $msgType = 'danger';
    }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $targetId = intval($_POST['admin_id']);
    $newPassword = $_POST['new_password'];
    
    if (strlen($newPassword) < 6) {
        $msg = 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak!';
        $msgType = 'danger';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $targetId]);
        $msg = '✅ Parol muvaffaqiyatli yangilandi!';
        $msgType = 'success';
    }
}

// Fetch stats
$totalAdmins = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$systemCount = $db->query("SELECT COUNT(*) FROM admins WHERE role = 'system'")->fetchColumn();
$adminCount = $db->query("SELECT COUNT(*) FROM admins WHERE role = 'admin'")->fetchColumn();
$managerCount = $db->query("SELECT COUNT(*) FROM admins WHERE role = 'manager'")->fetchColumn();
?>

<script>document.getElementById('page-title').innerText = '🛡️ Rollar va Ruxsatlar';</script>

<style>
    .roles-grid {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 24px;
    }

    .role-hierarchy {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }

    .role-level {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: white;
        transition: all 0.2s;
    }

    .role-level:hover {
        box-shadow: var(--shadow);
        transform: translateY(-1px);
    }

    .role-level-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .rl-system .role-level-icon { background: linear-gradient(135deg, #f5f3ff, #ede9fe); color: #7c3aed; }
    .rl-admin .role-level-icon { background: #ecfdf5; color: #059669; }
    .rl-manager .role-level-icon { background: #fffbeb; color: #d97706; }
    .rl-user .role-level-icon { background: #eff6ff; color: #3b82f6; }

    .role-level-info h4 {
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .role-level-info p {
        font-size: 0.75rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    .role-level-count {
        margin-left: auto;
        background: #f1f5f9;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text);
    }

    .admin-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }

    .admin-row:hover { background: #fafbfc; }
    .admin-row:last-child { border-bottom: none; }

    .admin-row-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        color: white;
        flex-shrink: 0;
    }

    .admin-row-info { flex: 1; min-width: 0; }

    .admin-row-info .admin-name {
        font-weight: 600;
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .admin-row-info .admin-username {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-family: monospace;
    }

    .admin-row-meta {
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .admin-row-actions {
        display: flex;
        gap: 6px;
    }

    .admin-row-actions .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        color: var(--text-light);
        transition: all 0.2s;
    }

    .admin-row-actions .action-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-bg);
    }

    .admin-row-actions .action-btn.danger:hover {
        border-color: var(--danger);
        color: var(--danger);
        background: #fef2f2;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-dot.active { background: #10b981; }
    .status-dot.inactive { background: #ef4444; }

    .avatar-system { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
    .avatar-admin { background: linear-gradient(135deg, #059669, #047857); }
    .avatar-manager { background: linear-gradient(135deg, #d97706, #b45309); }
    .avatar-user { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active { display: flex; }

    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 32px;
        width: 90%;
        max-width: 440px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: modalIn 0.3s ease;
    }

    @keyframes modalIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-content h3 {
        margin-bottom: 20px;
        font-size: 1.1rem;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-actions .btn { flex: 1; justify-content: center; }

    select.form-control {
        appearance: auto;
        cursor: pointer;
    }

    @media (max-width: 1024px) {
        .roles-grid { grid-template-columns: 1fr; }
    }
</style>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
        <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $msg; ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['access_denied'])): ?>
    <div class="alert alert-danger"><i class="fas fa-ban"></i> Bu sahifaga kirish uchun ruxsatingiz yo'q!</div>
<?php endif; ?>

<div class="roles-grid">
    <!-- Left Column: Role Hierarchy & Add Form -->
    <div>
        <!-- Role Hierarchy -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3><i class="fas fa-layer-group" style="margin-right: 8px; color: var(--primary);"></i>Rollar ierarxiyasi</h3>
            </div>
            <div class="card-body padded">
                <div class="role-hierarchy">
                    <div class="role-level rl-system">
                        <div class="role-level-icon"><i class="fas fa-cog"></i></div>
                        <div class="role-level-info">
                            <h4>⚙️ Tizim (System)</h4>
                            <p>To'liq boshqaruv, rollar berish, tizim sozlamalari</p>
                        </div>
                        <div class="role-level-count"><?php echo $systemCount; ?></div>
                    </div>
                    <div class="role-level rl-admin">
                        <div class="role-level-icon"><i class="fas fa-shield-halved"></i></div>
                        <div class="role-level-info">
                            <h4>🛡️ Admin</h4>
                            <p>Foydalanuvchilar, broadcast, kontent boshqaruvi</p>
                        </div>
                        <div class="role-level-count"><?php echo $adminCount; ?></div>
                    </div>
                    <div class="role-level rl-manager">
                        <div class="role-level-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="role-level-info">
                            <h4>👔 Manager</h4>
                            <p>Yangiliklar, xizmatlar boshqaruvi</p>
                        </div>
                        <div class="role-level-count"><?php echo $managerCount; ?></div>
                    </div>
                    <div class="role-level rl-user">
                        <div class="role-level-icon"><i class="fas fa-user"></i></div>
                        <div class="role-level-info">
                            <h4>👤 Foydalanuvchi</h4>
                            <p>Oddiy bot foydalanuvchisi (panelga kirish yo'q)</p>
                        </div>
                        <div class="role-level-count">—</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Admin Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus" style="margin-right: 8px; color: var(--primary);"></i>Yangi qo'shish</h3>
            </div>
            <div class="card-body padded">
                <form method="POST">
                    <input type="hidden" name="add_admin" value="1">
                    <div class="form-group">
                        <label><i class="fas fa-user" style="margin-right: 6px;"></i>To'liq ism</label>
                        <input type="text" name="fullname" required class="form-control" placeholder="Ism Familiya">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-at" style="margin-right: 6px;"></i>Foydalanuvchi nomi (login)</label>
                        <input type="text" name="username" required class="form-control" placeholder="username" pattern="[a-zA-Z0-9_]+" title="Faqat harflar, raqamlar va _ belgisi">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock" style="margin-right: 6px;"></i>Parol</label>
                        <input type="password" name="password" required class="form-control" placeholder="Kamida 6 ta belgi" minlength="6">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag" style="margin-right: 6px;"></i>Rol</label>
                        <select name="role" required class="form-control">
                            <option value="manager">👔 Manager</option>
                            <?php if (hasRole($adminRole, 'system')): ?>
                                <option value="admin">🛡️ Admin</option>
                                <option value="system">⚙️ Tizim</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-plus"></i> Qo'shish
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Admin List -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users-cog" style="margin-right: 8px; color: var(--text-muted);"></i>Boshqaruv foydalanuvchilari (<?php echo $totalAdmins; ?>)</h3>
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="adminSearch" placeholder="Qidirish...">
            </div>
        </div>
        <div class="card-body" id="adminList">
            <?php
            $stmt = $db->query("SELECT * FROM admins ORDER BY FIELD(role, 'system', 'admin', 'manager', 'user'), created_at ASC");
            while ($admin_item = $stmt->fetch()):
                $initial = strtoupper(substr($admin_item['fullname'] ?: $admin_item['username'], 0, 1));
                $role = $admin_item['role'] ?? 'manager';
                $isActive = $admin_item['is_active'] ?? 1;
                $lastLogin = $admin_item['last_login'] ? date('d.m.Y H:i', strtotime($admin_item['last_login'])) : 'Hali kirmagan';
                $isSelf = ($admin_item['username'] === $_SESSION['admin_user']);
            ?>
            <div class="admin-row">
                <div class="admin-row-avatar avatar-<?php echo $role; ?>"><?php echo $initial; ?></div>
                <div class="admin-row-info">
                    <div class="admin-name">
                        <span class="status-dot <?php echo $isActive ? 'active' : 'inactive'; ?>"></span>
                        <?php echo htmlspecialchars($admin_item['fullname'] ?: $admin_item['username']); ?>
                        <?php if ($isSelf): ?><span style="font-size: 0.7rem; color: var(--primary);">(siz)</span><?php endif; ?>
                    </div>
                    <div class="admin-username">@<?php echo $admin_item['username']; ?></div>
                    <div class="admin-row-meta">
                        <span class="badge badge-role <?php echo getRoleBadgeClass($role); ?>"><?php echo getRoleLabel($role); ?></span>
                        &nbsp;|&nbsp; Oxirgi kirish: <?php echo $lastLogin; ?>
                    </div>
                </div>
                <?php if (!$isSelf && $admin_item['username'] !== 'admin' || ($adminRole === 'system')): ?>
                <div class="admin-row-actions">
                    <!-- Change Role -->
                    <button class="action-btn" onclick="openRoleModal(<?php echo $admin_item['id']; ?>, '<?php echo $admin_item['username']; ?>', '<?php echo $role; ?>')" title="Rolni o'zgartirish">
                        <i class="fas fa-user-tag"></i>
                    </button>
                    <!-- Reset Password -->
                    <button class="action-btn" onclick="openPasswordModal(<?php echo $admin_item['id']; ?>, '<?php echo $admin_item['username']; ?>')" title="Parolni tiklash">
                        <i class="fas fa-key"></i>
                    </button>
                    <?php if ($admin_item['username'] !== 'admin'): ?>
                    <!-- Toggle Active -->
                    <a href="?toggle_active=<?php echo $admin_item['id']; ?>" class="action-btn <?php echo $isActive ? 'danger' : ''; ?>" 
                       title="<?php echo $isActive ? 'Bloklash' : 'Faollashtirish'; ?>"
                       onclick="return confirm('<?php echo $isActive ? 'Bloklashni' : 'Faollashtirishni'; ?> tasdiqlaysizmi?')">
                        <i class="fas fa-<?php echo $isActive ? 'ban' : 'check'; ?>"></i>
                    </a>
                    <!-- Delete -->
                    <a href="?delete_admin=<?php echo $admin_item['id']; ?>" class="action-btn danger" title="O'chirish"
                       onclick="return confirm('O\'chirishni tasdiqlaysizmi? Bu amalni qaytarib bo\'lmaydi!')">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div class="modal-overlay" id="roleModal">
    <div class="modal-content">
        <h3><i class="fas fa-user-tag" style="color: var(--primary); margin-right: 8px;"></i>Rolni o'zgartirish</h3>
        <form method="POST">
            <input type="hidden" name="update_role" value="1">
            <input type="hidden" name="admin_id" id="roleAdminId">
            <div class="form-group">
                <label>Foydalanuvchi</label>
                <input type="text" id="roleUsername" class="form-control" disabled>
            </div>
            <div class="form-group">
                <label>Yangi rol</label>
                <select name="new_role" id="roleSelect" class="form-control">
                    <option value="manager">👔 Manager</option>
                    <option value="admin">🛡️ Admin</option>
                    <?php if ($adminRole === 'system'): ?>
                    <option value="system">⚙️ Tizim</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('roleModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<!-- Password Reset Modal -->
<div class="modal-overlay" id="passwordModal">
    <div class="modal-content">
        <h3><i class="fas fa-key" style="color: var(--warning); margin-right: 8px;"></i>Parolni tiklash</h3>
        <form method="POST">
            <input type="hidden" name="reset_password" value="1">
            <input type="hidden" name="admin_id" id="passAdminId">
            <div class="form-group">
                <label>Foydalanuvchi</label>
                <input type="text" id="passUsername" class="form-control" disabled>
            </div>
            <div class="form-group">
                <label>Yangi parol</label>
                <input type="password" name="new_password" required class="form-control" placeholder="Kamida 6 ta belgi" minlength="6">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('passwordModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">O'zgartirish</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search
document.getElementById('adminSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('.admin-row');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Role Modal
function openRoleModal(id, username, currentRole) {
    document.getElementById('roleAdminId').value = id;
    document.getElementById('roleUsername').value = '@' + username;
    document.getElementById('roleSelect').value = currentRole;
    document.getElementById('roleModal').classList.add('active');
}

// Password Modal
function openPasswordModal(id, username) {
    document.getElementById('passAdminId').value = id;
    document.getElementById('passUsername').value = '@' + username;
    document.getElementById('passwordModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on outside click
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<?php require_once 'footer.php'; ?>
