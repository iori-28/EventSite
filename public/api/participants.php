<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/ParticipantController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

/* =========================
   REGISTER USER TO EVENT
   ========================= */
if ($action === 'register') {

    $user_id  = $_SESSION['user']['id'];
    $event_id = $_POST['event_id'];

    $result = ParticipantController::register($user_id, $event_id);

    if ($result === "NOT_APPROVED") {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=not_approved');
    } elseif ($result === "FULL") {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=event_full');
    } elseif ($result === "ALREADY_REGISTERED") {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=already_registered');
    } elseif ($result === true) {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=success');
    } else {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=failed');
    }
    exit;
}

/* =========================
   CANCEL REGISTRATION
   ========================= */
if ($action === 'cancel') {

    $user_id  = $_SESSION['user']['id'];
    $event_id = $_POST['event_id'];

    echo ParticipantController::cancel($user_id, $event_id)
        ? "CANCEL_SUCCESS"
        : "CANCEL_FAILED";
}

/* =========================
   GET USER EVENTS
   ========================= */
if ($action === 'my-events') {

    echo json_encode(
        ParticipantController::getByUser($_SESSION['user']['id'])
    );
}
