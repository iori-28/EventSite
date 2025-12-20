<!-- 
Post-Registration Modal Component

Modal yang muncul setelah user berhasil register event.
User bisa pilih untuk:
1. Auto-add ke Google Calendar (jika sudah connect) atau connect Google Calendar
2. Add manual dengan button existing
3. Skip

Usage:
require_once 'components/post_registration_modal.php';

Lalu di script registration:
<script>
showPostRegistrationModal({
    event_id: <?= $event_id ?>,
    event_title: '<?= $event_title ?>',
    calendar_connected: <?= $calendar_connected ? 'true' : 'false' ?>,
    auto_add_enabled: <?= $auto_add_enabled ? 'true' : 'false' ?>
});
</script>
-->

<style>
    .post-reg-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9998;
        animation: fadeIn 0.3s ease;
    }

    .post-reg-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 16px;
        padding: 0;
        max-width: 500px;
        width: 90%;
        z-index: 9999;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translate(-50%, -40%);
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }

    .post-reg-modal-header {
        background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%);
        color: white;
        padding: 24px;
        border-radius: 16px 16px 0 0;
        text-align: center;
    }

    .post-reg-modal-header h3 {
        margin: 0 0 8px 0;
        font-size: 24px;
        font-weight: 700;
    }

    .post-reg-modal-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .post-reg-modal-body {
        padding: 32px 24px;
    }

    .post-reg-modal-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
        text-align: center;
    }

    .post-reg-calendar-options {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .post-reg-calendar-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: #333;
    }

    .post-reg-calendar-btn:hover {
        border-color: #c9384a;
        background: #fff5f6;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(201, 56, 74, 0.15);
    }

    .post-reg-calendar-btn.primary {
        background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%);
        color: white;
        border-color: transparent;
    }

    .post-reg-calendar-btn.primary:hover {
        background: linear-gradient(135deg, #d44557 0%, #9c2333 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(201, 56, 74, 0.3);
    }

    .post-reg-btn-icon {
        font-size: 24px;
        flex-shrink: 0;
    }

    .post-reg-btn-content {
        flex: 1;
        text-align: left;
    }

    .post-reg-btn-title {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 4px;
    }

    .post-reg-btn-desc {
        font-size: 12px;
        opacity: 0.8;
    }

    .post-reg-skip-btn {
        display: block;
        text-align: center;
        margin-top: 16px;
        color: #666;
        text-decoration: none;
        font-size: 14px;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .post-reg-skip-btn:hover {
        background: #f5f5f5;
        color: #333;
    }

    .post-reg-loading {
        display: none;
        text-align: center;
        padding: 20px;
    }

    .post-reg-spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #c9384a;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 12px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Modal HTML -->
<div class="post-reg-modal-overlay" id="postRegModalOverlay"></div>
<div class="post-reg-modal" id="postRegModal">
    <div class="post-reg-modal-header">
        <h3>✅ Pendaftaran Berhasil!</h3>
        <p id="postRegEventTitle"></p>
    </div>
    <div class="post-reg-modal-body">
        <div id="postRegContent">
            <p class="post-reg-modal-title">Tambahkan event ke kalender Anda?</p>
            <div class="post-reg-calendar-options" id="postRegOptions">
                <!-- Options will be dynamically generated -->
            </div>
            <a href="#" class="post-reg-skip-btn" onclick="closePostRegModal(); return false;">
                Lewati, saya tambahkan nanti
            </a>
        </div>
        <div class="post-reg-loading" id="postRegLoading">
            <div class="post-reg-spinner"></div>
            <p>Menambahkan ke Google Calendar...</p>
        </div>
    </div>
</div>

<script>
    let currentEventData = null;

    /**
     * Show post-registration modal
     * 
     * @param {Object} eventData - Event data object
     * @param {number} eventData.event_id - Event ID
     * @param {string} eventData.event_title - Event title
     * @param {boolean} eventData.calendar_connected - Is Google Calendar connected?
     * @param {boolean} eventData.auto_add_enabled - Is auto-add enabled?
     */
    function showPostRegistrationModal(eventData) {
        currentEventData = eventData;

        // Set event title
        document.getElementById('postRegEventTitle').textContent = eventData.event_title;

        // Generate options based on calendar connection status
        const optionsContainer = document.getElementById('postRegOptions');
        optionsContainer.innerHTML = '';

        if (eventData.calendar_connected && eventData.auto_add_enabled) {
            // KONDISI 1: User connected & auto-add ON - show auto-add option
            optionsContainer.innerHTML = `
            <button class="post-reg-calendar-btn primary" onclick="autoAddToCalendar()">
                <div class="post-reg-btn-icon">📅</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Tambahkan Otomatis ke Google Calendar</div>
                    <div class="post-reg-btn-desc">Event akan langsung masuk ke kalender Anda</div>
                </div>
            </button>
            <a href="index.php?page=event-detail&id=${eventData.event_id}" class="post-reg-calendar-btn">
                <div class="post-reg-btn-icon">📥</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Tambahkan Manual</div>
                    <div class="post-reg-btn-desc">Download .ics atau gunakan link Google Calendar</div>
                </div>
            </a>
        `;
        } else if (eventData.calendar_connected && !eventData.auto_add_enabled) {
            // KONDISI 2: User connected tapi auto-add OFF - show manual add to Google Calendar
            optionsContainer.innerHTML = `
            <button class="post-reg-calendar-btn primary" onclick="manualAddToCalendar()">
                <div class="post-reg-btn-icon">📅</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Tambahkan ke Google Calendar</div>
                    <div class="post-reg-btn-desc">Event akan ditambahkan ke kalender Anda</div>
                </div>
            </button>
            <a href="index.php?page=event-detail&id=${eventData.event_id}" class="post-reg-calendar-btn">
                <div class="post-reg-btn-icon">📥</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Download .ics</div>
                    <div class="post-reg-btn-desc">Download file kalender untuk import manual</div>
                </div>
            </a>
        `;
        } else {
            // KONDISI 3: User not connected - show connect option
            optionsContainer.innerHTML = `
            <button class="post-reg-calendar-btn primary" onclick="connectGoogleCalendar()">
                <div class="post-reg-btn-icon">🔗</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Hubungkan Google Calendar</div>
                    <div class="post-reg-btn-desc">Sinkronkan event ke kalender Anda</div>
                </div>
            </button>
            <a href="index.php?page=event-detail&id=${eventData.event_id}" class="post-reg-calendar-btn">
                <div class="post-reg-btn-icon">📥</div>
                <div class="post-reg-btn-content">
                    <div class="post-reg-btn-title">Tambahkan Manual</div>
                    <div class="post-reg-btn-desc">Download .ics atau gunakan link Google Calendar</div>
                </div>
            </a>
        `;
        }

        // Show modal
        document.getElementById('postRegModalOverlay').style.display = 'block';
        document.getElementById('postRegModal').style.display = 'block';
    }

    /**
     * Close modal
     */
    function closePostRegModal() {
        document.getElementById('postRegModalOverlay').style.display = 'none';
        document.getElementById('postRegModal').style.display = 'none';
        currentEventData = null;
    }

    /**
     * Auto-add event to Google Calendar (untuk user dengan auto-add enabled)
     */
    function autoAddToCalendar() {
        if (!currentEventData) return;

        // Show loading
        document.getElementById('postRegContent').style.display = 'none';
        document.getElementById('postRegLoading').style.display = 'block';

        // Call API to auto-add
        fetch('api/google-calendar-auto-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=${currentEventData.event_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Event berhasil ditambahkan ke Google Calendar!');
                    closePostRegModal();
                    // Redirect to my events or dashboard
                    window.location.href = 'index.php?page=user_my_events&calendar_added=1';
                } else {
                    alert('❌ Gagal menambahkan ke Google Calendar: ' + (data.error || 'Unknown error'));
                    document.getElementById('postRegContent').style.display = 'block';
                    document.getElementById('postRegLoading').style.display = 'none';
                }
            })
            .catch(error => {
                alert('❌ Terjadi kesalahan: ' + error);
                document.getElementById('postRegContent').style.display = 'block';
                document.getElementById('postRegLoading').style.display = 'none';
            });
    }

    /**
     * Manual add event to Google Calendar (untuk user connected tapi auto-add OFF)
     * Sama seperti auto-add, tapi tanpa cek auto_add_enabled
     */
    function manualAddToCalendar() {
        if (!currentEventData) return;

        // Show loading
        document.getElementById('postRegContent').style.display = 'none';
        document.getElementById('postRegLoading').style.display = 'block';

        // Call API dengan force_add=1 parameter untuk bypass auto-add check
        fetch('api/google-calendar-auto-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=${currentEventData.event_id}&force_add=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Event berhasil ditambahkan ke Google Calendar!');
                    closePostRegModal();
                    window.location.href = 'index.php?page=user_my_events&calendar_added=1';
                } else {
                    alert('❌ Gagal menambahkan ke Google Calendar: ' + (data.error || 'Unknown error'));
                    document.getElementById('postRegContent').style.display = 'block';
                    document.getElementById('postRegLoading').style.display = 'none';
                }
            })
            .catch(error => {
                alert('❌ Terjadi kesalahan: ' + error);
                document.getElementById('postRegContent').style.display = 'block';
                document.getElementById('postRegLoading').style.display = 'none';
            });
    }

    /**
     * Redirect to Google Calendar OAuth
     */
    function connectGoogleCalendar() {
        // Store event_id in session untuk redirect back setelah OAuth
        sessionStorage.setItem('post_oauth_event_id', currentEventData.event_id);
        sessionStorage.setItem('post_oauth_auto_add', 'true');

        // Redirect to OAuth flow
        window.location.href = 'api/google-calendar-connect.php';
    }

    // Close modal when clicking overlay
    document.getElementById('postRegModalOverlay')?.addEventListener('click', closePostRegModal);
</script>