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
        echo "EVENT_NOT_APPROVED";
    } elseif ($result === "FULL") {
        echo "EVENT_FULL";
    } elseif ($result === "ALREADY_REGISTERED") {
        echo "ALREADY_REGISTERED";
    } elseif ($result === true) {
        echo "REGISTER_SUCCESS";
    } else {
        echo "REGISTER_FAILED";
    }
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
