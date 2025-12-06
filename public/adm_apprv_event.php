<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("AKSES DITOLAK");
}

$db = Database::connect();
$events = $db->query("SELECT * FROM events WHERE status = 'pending'")->fetchAll();
?>

<h2>EVENT MENUNGGU PERSETUJUAN</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Judul</th>
        <th>Lokasi</th>
        <th>Waktu</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($events as $e): ?>
        <tr>
            <td><?= $e['title'] ?></td>
            <td><?= $e['location'] ?></td>
            <td><?= $e['start_at'] ?></td>
            <td>
                <form method="POST" action="api/events.php" style="display:inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <button>✅ APPROVE</button>
                </form>
            </td>
        </tr>
    <?php endforeach ?>
</table>