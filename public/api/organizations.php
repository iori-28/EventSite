<?php
session_start();
if (!isset($_SESSION['user'])) {
    die("NO_SESSION_LOGIN");
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/OrganizationController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'create') {

        $name = $_POST['name'];
        $desc = $_POST['description'];
        $user_id = $_SESSION['user']['id'];
        $role = $_SESSION['user']['role'];

        // ROLE ADMIN LANGSUNG APPROVED
        $is_approved = ($role === 'admin') ? 1 : 0;

        echo OrganizationController::create($name, $desc, $user_id, $is_approved)
            ? ($is_approved ? "ORG_CREATED_DIRECT" : "ORG_REQUESTED")
            : "ORG_FAILED";
    }


    // APPROVE ADMIN
    if ($_POST['action'] === 'approve') {
        echo OrganizationController::approve($_POST['id'])
            ? "ORG_APPROVED"
            : "FAILED";
    }

    // REJECT ADMIN
    if ($_POST['action'] === 'reject') {
        echo OrganizationController::reject($_POST['id'])
            ? "ORG_REJECTED"
            : "FAILED";
    }
}
