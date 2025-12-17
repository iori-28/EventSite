<?php

/**
 * Add to Calendar Button Component
 * Reusable component for adding events to calendar
 * 
 * Usage:
 * <?php 
 * require_once 'components/calendar_button.php';
 * renderCalendarButton($event);
 * ?>
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CalendarService.php';

function renderCalendarButton($event)
{
    $googleUrl = CalendarService::generateGoogleCalendarUrl($event);
    $icsUrl = 'api/export_calendar.php?event_id=' . $event['id'];
?>

    <div class="calendar-button-wrapper" style="position: relative; display: inline-block;">
        <button class="btn btn-calendar" onclick="toggleCalendarDropdown(<?= $event['id'] ?>)" style="background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(201, 56, 74, 0.3); transition: transform 0.2s;">
            <span style="font-size: 18px;">📅</span>
            <span>Add to Calendar</span>
            <span style="font-size: 12px;">▼</span>
        </button>

        <div id="calendar-dropdown-<?= $event['id'] ?>" class="calendar-dropdown" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 8px; background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 250px; z-index: 1000; overflow: hidden;">
            <a href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" class="calendar-option" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                <span style="font-size: 20px;">📅</span>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Google Calendar</div>
                    <div style="font-size: 12px; color: #666;">Open in Google Calendar</div>
                </div>
            </a>

            <a href="<?= htmlspecialchars($icsUrl) ?>" class="calendar-option" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                <span style="font-size: 20px;">📥</span>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Download .ics</div>
                    <div style="font-size: 12px; color: #666;">For Outlook, Apple Calendar, etc.</div>
                </div>
            </a>

            <a href="<?= htmlspecialchars($icsUrl) ?>" class="calendar-option" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                <span style="font-size: 20px;">🍎</span>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Apple Calendar</div>
                    <div style="font-size: 12px; color: #666;">Download .ics file</div>
                </div>
            </a>

            <a href="<?= htmlspecialchars($icsUrl) ?>" class="calendar-option" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; text-decoration: none; color: #333; transition: background 0.2s;">
                <span style="font-size: 20px;">📧</span>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Outlook</div>
                    <div style="font-size: 12px; color: #666;">Download .ics file</div>
                </div>
            </a>
        </div>
    </div>

    <style>
        .btn-calendar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4) !important;
        }

        .calendar-option:hover {
            background: #f8f9fa !important;
        }

        .calendar-option:active {
            background: #e9ecef !important;
        }
    </style>

    <script>
        function toggleCalendarDropdown(eventId) {
            const dropdown = document.getElementById('calendar-dropdown-' + eventId);
            const isVisible = dropdown.style.display === 'block';

            // Close all other dropdowns
            document.querySelectorAll('.calendar-dropdown').forEach(d => {
                d.style.display = 'none';
            });

            // Toggle current dropdown
            dropdown.style.display = isVisible ? 'none' : 'block';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.calendar-button-wrapper')) {
                document.querySelectorAll('.calendar-dropdown').forEach(d => {
                    d.style.display = 'none';
                });
            }
        });
    </script>

<?php
}
?>