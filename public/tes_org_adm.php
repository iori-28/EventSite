<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/OrganizationController.php';

$data = OrganizationController::pending();
?>

<?php foreach ($data as $org): ?>
    <form method="POST" action="api/organizations.php">
        <b><?= $org['name'] ?></b>

        <input type="hidden" name="id" value="<?= $org['id'] ?>">
        <button name="action" value="approve">APPROVE</button>
        <button name="action" value="reject">REJECT</button>
    </form>
<?php endforeach ?>