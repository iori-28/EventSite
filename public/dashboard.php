<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EventSite Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 20px;
        }

        h1 {
            margin-bottom: 10px;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, .1);
        }

        input,
        select,
        textarea,
        button {
            padding: 8px;
            margin: 6px 0;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #aaa;
        }

        button {
            background: #007bff;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #005fcc;
        }

        .role-box {
            background: #eee;
            padding: 8px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <h1>EventSite Developer Dashboard</h1>

    <!-- SESSION INFO -->
    <div class="card">
        <h2>Session Info</h2>
        <?php if (isset($_SESSION['user'])): ?>
            <p><b>Logged in as:</b></p>
            <div class="role-box">
                ID: <?= $_SESSION['user']['id'] ?> <br>
                Email: <?= $_SESSION['user']['email'] ?> <br>
                Role: <?= $_SESSION['user']['role'] ?>
            </div>
        <?php else: ?>
            <p><b>Not logged in</b></p>
        <?php endif; ?>
    </div>

    <!-- QUICK LOGIN -->
    <div class="card">
        <h2>Quick Login</h2>
        <form method="POST" action="api/auth.php">
            <input type="hidden" name="action" value="login">

            <label>Email:</label>
            <input type="email" name="email" placeholder="admin@example.com">

            <label>Password:</label>
            <input type="password" name="password" placeholder="password">

            <button>Login</button>
        </form>
    </div>

    <!-- LOGOUT -->
    <div class="card">
        <h2>Logout</h2>
        <form method="POST" action="api/auth.php">
            <input type="hidden" name="action" value="logout">
            <button>Logout</button>
        </form>
    </div>

    <!-- CREATE EVENT -->
    <div class="card">
        <h2>Create Event (Admin / Panitia)</h2>
        <form method="POST" action="api/events.php">
            <input type="hidden" name="action" value="create">

            <input name="title" placeholder="Judul Event">
            <textarea name="description" placeholder="Deskripsi event"></textarea>
            <input name="location" placeholder="Lokasi event">
            <input type="datetime-local" name="start_at">
            <input type="datetime-local" name="end_at">
            <input name="capacity" type="number" placeholder="Kapasitas">

            <button>Create Event</button>
        </form>
    </div>

    <!-- APPROVE EVENT -->
    <div class="card">
        <h2>Approve Event (Admin Only)</h2>

        <?php
        if ($_SESSION['user']['role'] !== 'admin') {
            echo "<p>Hanya admin yang bisa approve event.</p>";
        } else {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
            $db = Database::connect();
            $pendingEvents = $db->query("SELECT * FROM events WHERE status = 'pending'")->fetchAll();

            if (count($pendingEvents) === 0) {
                echo "<p><i>Tidak ada event yang menunggu approval.</i></p>";
            } else {
                echo "<table border='1' cellpadding='8' style='width:100%; margin-top:10px;'>
                    <tr>
                        <th>Judul</th>
                        <th>Lokasi</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>";

                foreach ($pendingEvents as $e) {
                    echo "
                <tr>
                    <td>{$e['title']}</td>
                    <td>{$e['location']}</td>
                    <td>{$e['start_at']}</td>
                    <td>
                        <form method='POST' action='api/events.php' style='display:inline;'>
                            <input type='hidden' name='action' value='approve'>
                            <input type='hidden' name='id' value='{$e['id']}'>
                            <button>Approve</button>
                        </form>
                    </td>
                </tr>";
                }

                echo "</table>";
            }
        }
        ?>
    </div>


    <!-- REGISTER EVENT -->
    <div class="card">
        <h2>Register Event (User)</h2>
        <form method="POST" action="api/events.php">
            <input type="hidden" name="action" value="register">

            <input type="number" name="event_id" placeholder="Event ID untuk daftar">

            <button>Register Event</button>
        </form>
    </div>

    <!-- TEST EMAIL / NOTIFICATION -->
    <div class="card">
        <h2>Test Notifikasi (Email)</h2>
        <form method="POST" action="api/notifications.php">
            <input type="hidden" name="action" value="test-create">
            <button>Kirim Test Notifikasi</button>
        </form>
    </div>

    <!-- SHOW EVENT LIST -->
    <div class="card">
        <h2>Tampilkan Event Approved (JSON)</h2>
        <form method="POST" action="api/events.php" target="_blank">
            <input type="hidden" name="action" value="list">
            <button>Lihat Event Approved</button>
        </form>
    </div>

</body>

</html>