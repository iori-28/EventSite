<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Handling Action (Delete / Change Role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'delete_user') {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['status' => 'success', 'message' => 'User deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
        }
        exit;
    }

    if ($_POST['action'] === 'change_role') {
        $id = $_POST['id'] ?? null;
        $role = $_POST['new_role'] ?? null;

        if (!$id || !$role) {
            echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
            exit;
        }

        if (!in_array($role, ['user', 'panitia', 'admin'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
            exit;
        }

        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt->execute([$role, $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Role updated to ' . $role]);
        } else {
            $errorInfo = $stmt->errorInfo();
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $errorInfo[2]]);
        }
        exit;
    }
}

// Filtering
$role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($role !== 'all') {
    $sql .= " AND role = :role";
    $params[':role'] = $role;
}

if ($search) {
    $sql .= " AND (name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - EventSite Admin</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-title">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:24px; cursor:pointer; margin-right:10px;">☰</button>
                    <h1>Kelola Users</h1>
                    <div class="header-breadcrumb">Daftar semua pengguna sistem</div>
                </div>
            </header>

            <div class="card mb-4" style="padding: 20px;">
                <form method="GET" class="d-flex align-center gap-2" style="flex-wrap: wrap;">
                    <input type="hidden" name="page" value="admin_manage_users">
                    <select name="role" class="form-control" style="width: auto; min-width: 150px;">
                        <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>Semua Role</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="panitia" <?= $role === 'panitia' ? 'selected' : '' ?>>Panitia</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>

                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau email..." class="form-control" style="width: auto; flex: 1;">

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php?page=admin_manage_users" class="btn btn-outline">Reset</a>
                </form>
            </div>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <?php if (count($users) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; text-align: left;">
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">User</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Email</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Role</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Bergabung</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <div class="d-flex align-center gap-2">
                                                <div style="width: 32px; height: 32px; background: #eee; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold;">
                                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                </div>
                                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                            </div>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                                                <select onchange="changeRole(<?= $u['id'] ?>, this.value)" data-old-role="<?= $u['role'] ?>" style="padding: 4px; border-radius: 4px; font-size: 11px;">
                                                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="panitia" <?= $u['role'] === 'panitia' ? 'selected' : '' ?>>Panitia</option>
                                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge" style="background: #333; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                                                <form method="POST" action="api/users.php" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="border-color: #dc3545; color: #dc3545;">Hapus</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 12px;">(Anda)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 60px;">
                            <p class="text-muted">Tidak ada user ditemukan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function deleteUser(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus user ini? Aksi ini tidak dapat dibatalkan.')) return;

            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('id', id);

            fetch('', { // POST to self
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('User berhasil dihapus!');
                        location.reload();
                    } else {
                        alert('Gagal menghapus user.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan sistem.');
                });
        }

        function changeRole(id, newRole) {
            const select = event.target; // Get the select element
            const oldRole = select.getAttribute('data-old-role') || select.value;

            console.log('Changing role:', {
                id,
                oldRole,
                newRole
            }); // Debug log

            // Disable select while processing
            select.disabled = true;
            select.style.opacity = '0.5';

            const formData = new FormData();
            formData.append('action', 'change_role');
            formData.append('id', id);
            formData.append('new_role', newRole);

            console.log('Sending request...'); // Debug log

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Debug log

                    if (data.status === 'success') {
                        alert('✅ Role berhasil diubah menjadi ' + newRole + '!');
                        select.setAttribute('data-old-role', newRole); // Update old role
                        location.reload();
                    } else {
                        alert('❌ Gagal mengubah role: ' + (data.message || 'Unknown error'));
                        select.value = oldRole; // Reset on failure
                        select.disabled = false;
                        select.style.opacity = '1';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('❌ Terjadi kesalahan sistem: ' + err.message);
                    select.value = oldRole; // Reset on error
                    select.disabled = false;
                    select.style.opacity = '1';
                });
        }
    </script>
</body>

</html>