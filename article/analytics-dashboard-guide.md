---
title: "Measuring Your Audience: Adding an Analytics Dashboard to Your Internet Radio"
published: true
tags: ["php", "javascript", "radio", "analytics", "webdev", "tutorial"]
date: 2026-02-08
description: "A step-by-step guide to implementing a simple yet effective analytics dashboard for your PHP-based internet radio system, helping you understand your audience better."
---

# Measuring Your Audience: Adding an Analytics Dashboard to Your Internet Radio

After integrating live streaming capabilities into our synchronized internet radio system, the next logical step is to understand who is listening and what they enjoy. An analytics dashboard provides invaluable insights into listener engagement, helping you tailor your programming for maximum impact.

This article details the implementation of a simple analytics dashboard that tracks total listens, unique listeners, popular tracks, and peak listening times.

## 1. The Foundation: Logging Listen History

Before we can display any analytics, we need to collect the data. This starts with a new database table to log every listen event.

*   **`radio_listen_history` Table:**
    ```sql
    CREATE TABLE IF NOT EXISTS radio_listen_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        media_id INT,
        live_stream_id INT,
        listen_time DATETIME NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        FOREIGN KEY (media_id) REFERENCES radio_media(id) ON DELETE SET NULL,
        FOREIGN KEY (live_stream_id) REFERENCES radio_live_streams(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ```
    This table is designed to be simple and efficient. It logs the `media_id` (for pre-recorded content) or `live_stream_id`, the timestamp of the listen event, and anonymized user data like `ip_address` and `user_agent` for calculating unique listeners and understanding the audience's technology.

*   **Integrating Logging into `api/now-playing.php`:**
    The "brain" of our radio, `api/now-playing.php`, is the perfect place to log listen events. Since every player client calls this endpoint to get the current track, we can add a logging function that executes on each request.

    ```php
    // In api/now-playing.php
    function logListenHistory(PDO $db, ?array $media, ?array $liveStream): void {
        $stmt = $db->prepare(
            "INSERT INTO radio_listen_history (media_id, live_stream_id, listen_time, ip_address, user_agent) 
             VALUES (:media_id, :live_stream_id, NOW(), :ip, :ua)"
        );
        $stmt->execute([
            ':media_id' => $media ? $media['id'] : null,
            ':live_stream_id' => $liveStream ? $liveStream['id'] : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    // This function is then called right before a jsonResponse is sent to the client:
    if ($liveStream) {
        logListenHistory($db, null, $liveStream);
        jsonResponse(...);
    }
    // ...
    if ($scheduled) {
        logListenHistory($db, $scheduled, null);
        jsonResponse(...);
    }
    // ...
    if ($currentTrack) { // for loop media
        logListenHistory($db, $currentTrack, null);
        jsonResponse(...);
    }
    ```

## 2. Building the Analytics API

With data being collected, we need an API endpoint to process and serve it to the admin dashboard.

*   **`api/analytics.php`:**
    This new read-only endpoint performs several SQL queries to aggregate the raw data from `radio_listen_history` into meaningful statistics.

    ```php
    // In api/analytics.php
    // 1. Get Total Listens
    $totalListens = $db->query("SELECT COUNT(*) FROM radio_listen_history")->fetchColumn();

    // 2. Get Unique Listeners
    $uniqueListeners = $db->query("SELECT COUNT(DISTINCT ip_address) FROM radio_listen_history")->fetchColumn();

    // 3. Get Most Popular Tracks
    $popularTracks = $db->query("
        SELECT m.title, m.artist, COUNT(h.id) as listen_count
        FROM radio_listen_history h
        JOIN radio_media m ON h.media_id = m.id
        WHERE h.media_id IS NOT NULL
        GROUP BY h.media_id
        ORDER BY listen_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Peak Listening Times
    $peakTimes = $db->query("
        SELECT HOUR(listen_time) as hour, COUNT(*) as listen_count
        FROM radio_listen_history
        GROUP BY hour
        ORDER BY listen_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Finally, return all data as a single JSON object
    echo json_encode([
        'total_listens' => (int) $totalListens,
        'unique_listeners' => (int) $uniqueListeners,
        'popular_tracks' => $popularTracks,
        'peak_times' => $peakTimes
    ]);
    ```
    This API provides all the necessary data in a single, efficient call, minimizing load on the server and simplifying the frontend logic.

## 3. Creating the Admin Dashboard UI

The final step is to present this data in a clear and readable format in the admin panel.

*   **New "Analytics" Tab:**
    A new "Analytics" tab was added to `admin/index.php`, providing a dedicated space for the dashboard.

*   **Dashboard Layout:**
    The dashboard is structured with CSS Grid for a responsive layout of "stat cards" and sections for more detailed lists.

    ```html
    <!-- In admin/index.php -->
    <div class="panel" id="panel-analytics">
        <div class="section-title">Analytics Dashboard</div>

        <div class="analytics-grid">
            <div class="analytics-card">
                <h3>Total Listens</h3>
                <div class="stat" id="totalListens">...</div>
            </div>
            <div class="analytics-card">
                <h3>Unique Listeners (by IP)</h3>
                <div class="stat" id="uniqueListeners">...</div>
            </div>
        </div>

        <div class="analytics-section">
            <h3>Most Popular Tracks</h3>
            <div id="popularTracksList"></div>
        </div>

        <div class="analytics-section">
            <h3>Peak Listening Times (by hour)</h3>
            <div id="peakTimesList"></div>
        </div>
    </div>
    ```

*   **Frontend JavaScript (`loadAnalytics()`):**
    A new JavaScript function, `loadAnalytics()`, is called when the "Analytics" tab is clicked. This function:
    1.  Fetches data from `/api/analytics.php`.
    2.  Updates the `textContent` of the stat cards (`totalListens`, `uniqueListeners`).
    3.  Dynamically generates and injects HTML for the "Most Popular Tracks" and "Peak Listening Times" lists.

    ```javascript
    async function loadAnalytics() {
        const resp = await fetch(BASE + '/api/analytics.php');
        const data = await resp.json();

        document.getElementById('totalListens').textContent = data.total_listens;
        document.getElementById('uniqueListeners').textContent = data.unique_listeners;

        // Populate popular tracks list
        const popularTracksList = document.getElementById('popularTracksList');
        if (data.popular_tracks && data.popular_tracks.length) {
            popularTracksList.innerHTML = data.popular_tracks.map(track => `...`).join('');
        }
        // ... same for peak times
    }
    ```

## Conclusion

By adding a simple data logging mechanism, a dedicated API endpoint, and a clean frontend interface, we've created a powerful analytics dashboard. This provides valuable, at-a-glance insights into listener behavior, helping station managers make informed decisions about their content strategy.

While this implementation is basic, it establishes a solid foundation for more advanced analytics in the future, such as geographic listener mapping (via IP geolocation), detailed listening history per track, or more complex engagement metrics.
