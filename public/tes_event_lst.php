<?php
session_start();
?>

<h2>LIST EVENT APPROVED</h2>

<form method="POST" action="api/events.php">
    <input type="hidden" name="action" value="list">
    <button>LOAD EVENTS</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ch = curl_init("http://localhost/EventSite/public/api/events.php");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "action=list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $events = json_decode($response, true);

    echo "<pre>";
    print_r($events);
    echo "</pre>";
}
