<form method="POST" action="api/events.php">
    <input type="hidden" name="action" value="create">

    <input name="title" placeholder="Judul">
    <textarea name="description"></textarea>
    <input name="location" placeholder="Lokasi">
    <input name="start_at" type="datetime-local">
    <input name="end_at" type="datetime-local">
    <input name="capacity" type="number">

    <button>CREATE EVENT</button>
</form>