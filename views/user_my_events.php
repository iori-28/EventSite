<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('user');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get my events
$query = "
    SELECT e.*, p.status as registration_status, p.registered_at, p.qr_token, p.id as participant_id
    FROM participants p
    JOIN events e ON p.event_id = e.id 
    WHERE p.user_id = :user_id
    ORDER BY e.start_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$all_events = $stmt->fetchAll();

$upcoming = [];
$past = [];

foreach ($all_events as $event) {
    if (strtotime($event['end_at']) > time()) {
        $upcoming[] = $event;
    } else {
        $past[] = $event;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Saya - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
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
                    <h1>Event Saya</h1>
                    <div class="header-breadcrumb">Daftar event yang Anda ikuti</div>
                </div>
            </header>

            <!-- View Toggle & Filters -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 20px;">
                    <!-- View Toggle -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                        <div style="display: flex; gap: 10px;">
                            <button id="listViewBtn" onclick="switchView('list')" class="btn btn-sm" style="background: var(--primary-color); color: white;">
                                📋 List View
                            </button>
                            <button id="calendarViewBtn" onclick="switchView('calendar')" class="btn btn-outline btn-sm">
                                📅 Calendar View
                            </button>
                        </div>
                        <div style="flex: 1; max-width: 400px;">
                            <input type="text" id="searchInput" placeholder="🔍 Cari event..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                        </div>
                    </div>

                    <!-- Filter Pills -->
                    <div id="filterPills" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <button onclick="filterEvents('all')" class="filter-pill active" data-filter="all">
                            Semua Event
                        </button>
                        <button onclick="filterEvents('upcoming')" class="filter-pill" data-filter="upcoming">
                            Akan Datang
                        </button>
                        <button onclick="filterEvents('past')" class="filter-pill" data-filter="past">
                            Sudah Lewat
                        </button>
                        <button onclick="filterEvents('registered')" class="filter-pill" data-filter="registered">
                            Registered
                        </button>
                        <button onclick="filterEvents('checked_in')" class="filter-pill" data-filter="checked_in">
                            Checked In
                        </button>
                        <button onclick="clearFilters()" class="btn btn-outline btn-sm" style="flex-shrink: 0;">
                            ✖ Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- List View Container -->
            <div id="listView">
                <!-- Upcoming Events -->
                <section class="mb-5">
                    <h2 style="font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">Akan Datang</h2>

                    <?php if (count($upcoming) > 0): ?>
                        <div class="grid grid-2">
                            <?php foreach ($upcoming as $event): ?>
                                <div class="card event-card"
                                    data-status="<?= $event['registration_status'] ?>"
                                    data-time="upcoming"
                                    data-title="<?= htmlspecialchars($event['title']) ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-between mb-3">
                                            <span class="badge badge-info"><?= ucfirst($event['registration_status']) ?></span>
                                            <span class="text-muted" style="font-size: 12px;">Ref: #<?= $event['id'] ?></span>
                                        </div>

                                        <h3 class="card-title"><?= htmlspecialchars($event['title']) ?></h3>

                                        <div class="event-meta">
                                            <div class="event-meta-item">
                                                📅 <?= date('d M Y', strtotime($event['start_at'])) ?>
                                            </div>
                                            <div class="event-meta-item">
                                                🕒 <?= date('H:i', strtotime($event['start_at'])) ?>
                                            </div>
                                        </div>

                                        <p class="text-muted mb-3" style="font-size: 14px;">📍 <?= htmlspecialchars($event['location']) ?></p>

                                        <div class="d-flex gap-2">
                                            <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=user_my_events" class="btn btn-primary btn-sm" style="flex: 1;">Detail</a>
                                            <button onclick="showQRCode('<?= $event['qr_token'] ?>', '<?= htmlspecialchars($event['title']) ?>')" class="btn btn-secondary btn-sm" style="flex: 1;">
                                                📱 QR Code
                                            </button>
                                            <?php if ($event['registration_status'] !== 'checked_in' && !in_array($event['status'], ['completed', 'waiting_completion'])): ?>
                                                <button onclick="cancelRegistration(<?= $event['id'] ?>)" class="btn btn-outline btn-sm" style="flex: 1; border-color: var(--danger-color); color: var(--danger-color);">Batalkan</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card p-4 text-center">
                            <p class="text-muted">Tidak ada event event yang akan datang.</p>
                            <a href="index.php?page=user_browse_events" class="btn btn-primary btn-sm mt-2">Cari Event</a>
                        </div>
                    <?php endif; ?>
                </section>

                </section>

                <!-- Past Events -->
                <section>
                    <h2 style="font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">Riwayat Event</h2>

                    <?php if (count($past) > 0): ?>
                        <div class="card">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8f9fa; text-align: left;">
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Event</th>
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Tanggal</th>
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Lokasi</th>
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past as $event): ?>
                                        <tr class="event-row"
                                            data-status="<?= $event['registration_status'] ?>"
                                            data-time="past"
                                            data-title="<?= htmlspecialchars($event['title']) ?>">
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <strong><?= htmlspecialchars($event['title']) ?></strong>
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <?= date('d M Y', strtotime($event['start_at'])) ?>
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <?= htmlspecialchars($event['location']) ?>
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <span class="badge badge-secondary" style="background: #eee; color: #666;">Selesai</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Belum ada riwayat event.</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Calendar View Container -->
            <div id="calendarView" style="display: none;">
                <div class="card">
                    <div class="card-body" style="padding: 20px;">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:8px; max-width:400px; width:90%; text-align:center;">
            <h3 style="margin-bottom:20px; color:#1a1a1a;">QR Code Kehadiran</h3>
            <p style="margin-bottom:15px; font-size:14px; color:#666;" id="qr-event-title"></p>
            <div id="qr-code" style="margin:20px auto; display:flex; justify-content:center;"></div>
            <p style="margin-top:15px; font-size:13px; color:#999;">Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran</p>
            <button onclick="closeQRModal()" class="btn btn-primary" style="margin-top:20px;">Tutup</button>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/id.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
        .filter-pill {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }

        .filter-pill:hover {
            background: #f8f9fa;
        }

        .filter-pill.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Calendar styling */
        .fc {
            font-family: var(--font-family);
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-event[data-status="registered"] {
            background-color: #3788d8 !important;
            border-color: #3788d8 !important;
        }

        .fc-event[data-status="checked_in"] {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }

        .fc-event[data-status="cancelled"] {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        /* Fallback class-based styling */
        .status-registered {
            background-color: #3788d8 !important;
            border-color: #3788d8 !important;
        }

        .status-checked_in {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }

        .status-cancelled {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }
    </style>

    <script>
        let calendar;
        let currentFilter = 'all';
        let currentSearchTerm = '';

        // Event data untuk calendar
        const allEventsData = [
            <?php foreach (array_merge($upcoming, $past) as $event): ?> {
                    id: '<?= $event['id'] ?>',
                    title: '<?= addslashes($event['title']) ?>',
                    start: '<?= date('Y-m-d\TH:i:s', strtotime($event['start_at'])) ?>',
                    end: '<?= date('Y-m-d\TH:i:s', strtotime($event['end_at'])) ?>',
                    url: 'index.php?page=event-detail&id=<?= $event['id'] ?>&from=user_my_events',
                    className: 'status-<?= $event['registration_status'] ?>',
                    backgroundColor: '<?= $event['registration_status'] === "checked_in" ? "#28a745" : ($event['registration_status'] === "registered" ? "#3788d8" : "#dc3545") ?>',
                    borderColor: '<?= $event['registration_status'] === "checked_in" ? "#28a745" : ($event['registration_status'] === "registered" ? "#3788d8" : "#dc3545") ?>',
                    extendedProps: {
                        status: '<?= $event['registration_status'] ?>',
                        location: '<?= addslashes($event['location']) ?>',
                        isPast: <?= strtotime($event['end_at']) < time() ? 'true' : 'false' ?>
                    }
                },
            <?php endforeach; ?>
        ];

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                events: allEventsData,
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        // Add view parameter to preserve calendar state
                        const separator = info.event.url.includes('?') ? '&' : '?';
                        window.location.href = info.event.url + separator + 'view=calendar';
                    }
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }
            });

            // Auto-restore view preference (localStorage priority, URL fallback)
            const savedViewMode = localStorage.getItem('eventViewMode');
            const urlParams = new URLSearchParams(window.location.search);
            const urlView = urlParams.get('view');

            // Priority: localStorage > URL parameter
            const preferredView = savedViewMode || urlView;
            if (preferredView === 'calendar') {
                switchView('calendar');
            }
        });

        // View switcher
        function switchView(view) {
            const listView = document.getElementById('listView');
            const calendarView = document.getElementById('calendarView');
            const listBtn = document.getElementById('listViewBtn');
            const calendarBtn = document.getElementById('calendarViewBtn');

            // Save preference to localStorage
            localStorage.setItem('eventViewMode', view);

            // Update URL parameter
            const url = new URL(window.location);
            if (view === 'calendar') {
                url.searchParams.set('view', 'calendar');
            } else {
                url.searchParams.delete('view');
            }
            window.history.replaceState({}, '', url);

            if (view === 'list') {
                listView.style.display = 'block';
                calendarView.style.display = 'none';
                listBtn.style.background = 'var(--primary-color)';
                listBtn.style.color = 'white';
                listBtn.classList.remove('btn-outline');
                calendarBtn.style.background = 'transparent';
                calendarBtn.style.color = 'var(--primary-color)';
                calendarBtn.classList.add('btn-outline');
            } else {
                listView.style.display = 'none';
                calendarView.style.display = 'block';
                calendarBtn.style.background = 'var(--primary-color)';
                calendarBtn.style.color = 'white';
                calendarBtn.classList.remove('btn-outline');
                listBtn.style.background = 'transparent';
                listBtn.style.color = 'var(--primary-color)';
                listBtn.classList.add('btn-outline');

                // Render calendar when switching to it
                setTimeout(() => calendar.render(), 100);
            }
        }

        // Filter events
        function filterEvents(filter) {
            currentFilter = filter;

            // Update active pill
            document.querySelectorAll('.filter-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            event.target.classList.add('active');

            applyFilters();
        }

        // Search events
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function(e) {
            currentSearchTerm = e.target.value.toLowerCase();
            applyFilters();
        });

        // Apply filters and search
        function applyFilters() {
            const cards = document.querySelectorAll('.event-card');
            const rows = document.querySelectorAll('.event-row');

            [...cards, ...rows].forEach(elem => {
                const status = elem.dataset.status;
                const time = elem.dataset.time;
                const title = elem.dataset.title.toLowerCase();

                let matchFilter = currentFilter === 'all' ||
                    (currentFilter === 'upcoming' && time === 'upcoming') ||
                    (currentFilter === 'past' && time === 'past') ||
                    (currentFilter === status);

                let matchSearch = currentSearchTerm === '' || title.includes(currentSearchTerm);

                elem.style.display = (matchFilter && matchSearch) ? '' : 'none';
            });
        }

        // Clear filters
        function clearFilters() {
            currentFilter = 'all';
            currentSearchTerm = '';
            searchInput.value = '';

            document.querySelectorAll('.filter-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            document.querySelector('[data-filter="all"]').classList.add('active');

            applyFilters();
        }

        // QR Code functions
        function showQRCode(token, eventTitle) {
            const modal = document.getElementById('qr-modal');
            const qrContainer = document.getElementById('qr-code');
            const titleEl = document.getElementById('qr-event-title');

            // Clear previous QR
            qrContainer.innerHTML = '';

            // Set title
            titleEl.textContent = eventTitle;

            // Generate QR Code
            new QRCode(qrContainer, {
                text: token,
                width: 256,
                height: 256,
                colorDark: "#1a1a1a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Show modal
            modal.style.display = 'flex';
        }

        function closeQRModal() {
            document.getElementById('qr-modal').style.display = 'none';
        }

        function cancelRegistration(eventId) {
            if (!confirm('Apakah Anda yakin ingin membatalkan pendaftaran event ini?')) return;

            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('event_id', eventId);

            fetch('api/participants.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    const status = data.trim();
                    if (status === 'CANCEL_SUCCESS') {
                        alert('Pendaftaran berhasil dibatalkan.');
                        location.reload();
                    } else {
                        alert('Gagal membatalkan pendaftaran: ' + status);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan koneksi.');
                });
        }
    </script>
</body>

</html>