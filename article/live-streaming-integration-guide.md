---
title: "Enhancing Your Internet Radio: Integrating Live Streaming with PHP, JS, and Google API"
published: true
tags: ["php", "javascript", "radio", "live-streaming", "youtube", "webdev", "tutorial"]
date: 2026-02-08
description: "A technical deep-dive into how we added live streaming capabilities to a synchronized internet radio system, leveraging PHP, JavaScript, and the YouTube Data API."
---

# Enhancing Your Internet Radio: Integrating Live Streaming with PHP, JS, and Google API

In a previous article, we explored building a synchronized internet radio system that delivers a "traditional broadcast" experience using pre-recorded content, all without complex streaming infrastructure. This system ensures all listeners hear the exact same thing at the same time, thanks to clever server-side scheduling and client-side synchronization.

While highly effective for curated, pre-recorded programming, the natural evolution for such a system is to support *actual live broadcasts*. This article will walk you through the technical implementation of adding live streaming integration, focusing on YouTube as the initial platform, using PHP, JavaScript, and the Google YouTube Data API.

## The Challenge: Merging Live with Curated

The core challenge was to gracefully integrate transient live streams into an existing system designed for deterministic, pre-scheduled content. Key considerations included:

1.  **Prioritization:** Live content should always take precedence over scheduled shows or the continuous loop.
2.  **Detection:** How do we reliably know if a social media account is currently live?
3.  **Playback:** How can the existing player seamlessly switch to and display a live stream?
4.  **Management:** Administrators need a way to configure and manage these live sources.

## Architectural Changes & Implementation Details

To achieve this, we introduced several modifications across the system:

### 1. Database Schema Extensions

We needed new structures to store information about social media live sources and related API keys.

*   **`radio_live_streams` Table:**
    ```sql
    CREATE TABLE IF NOT EXISTS radio_live_streams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform ENUM('youtube', 'facebook', 'tiktok', 'instagram') NOT NULL,
        account_name VARCHAR(255) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_checked DATETIME DEFAULT NULL,
        is_live TINYINT(1) NOT NULL DEFAULT 0,
        stream_url VARCHAR(500) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active_streams (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ```
    This table stores the platform, account identifier (e.g., YouTube Channel ID), its active status, and the dynamically updated live status, stream URL, and title.

*   **`radio_settings` Table:**
    We added a new setting entry for storing API keys, starting with YouTube.
    ```sql
    -- Example entry in database.sql
    INSERT INTO radio_settings (setting_key, setting_value) VALUES
    ('youtube_api_key', '')
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
    ```
    This allows administrators to configure necessary API credentials via the web UI.

### 2. Admin Panel for Management

The `admin/index.php` interface was extended with two new sections:

*   **"Live Streams" Tab:** A dedicated tab for administrators to add, activate/deactivate, and delete live stream sources. It displays a list of configured accounts and their last known live status.
    ```html
    <!-- Example UI elements for Live Streams tab -->
    <div class="panel" id="panel-live">
        <h3>Add Live Stream Source</h3>
        <select id="livePlatform">...</select>
        <input type="text" id="liveAccountName">
        <button onclick="addLiveStream()">Add Live Source</button>
        <div id="liveStreamList">...</div>
    </div>
    ```
    Associated JavaScript functions (`addLiveStream`, `deleteLiveStream`, `toggleLiveStreamActive`, `loadLiveStreams`) handle API interactions and UI rendering.

*   **"Settings" Tab:** A new tab to configure global settings, including the YouTube Data API key.
    ```html
    <!-- Example UI elements for Settings tab -->
    <div class="panel" id="panel-settings">
        <h3>API Keys</h3>
        <input type="text" id="settingYoutubeApiKey">
        <button onclick="saveSettings()">Save Settings</button>
    </div>
    ```
    JavaScript functions (`loadSettings`, `saveSettings`) manage fetching and persisting these settings.

### 3. Backend API Endpoints

New PHP API files were created to support the admin panel's functionality:

*   **`api/live-streams.php`:** Handles GET requests to list live stream sources, POST requests to add/update, and DELETE requests to remove them. It performs basic CRUD operations on the `radio_live_streams` table.
*   **`api/settings.php`:** Manages GET requests to retrieve all settings and POST requests to update key-value pairs in the `radio_settings` table.

Crucially, the `requireAdmin()` helper function in `config.php` was updated to properly return JSON errors for API requests, enhancing security and user experience.

### 4. Reliable Live Status Detection (YouTube Example)

This was arguably the most complex part. We opted for a server-side approach to periodically check stream statuses.

*   **Composer & Google API Client:**
    The project now utilizes [Composer](https://getcomposer.org/) for dependency management. The `google/apiclient` library was installed to interact with Google services, specifically the YouTube Data API v3.
    ```bash
    /opt/homebrew/bin/php composer.phar require google/apiclient
    ```

*   **YouTube Live Checker (`includes/live-checkers/youtube.php`):**
    A dedicated PHP function `checkYouTubeChannelLive($apiKey, $channelId)` was created. This function makes an API call to YouTube's `search.list` endpoint, filtering by `channelId`, `type=video`, and `eventType=live`. If `items` are returned, the channel is live, and we extract the video ID, stream URL, and title.

*   **Background Status Updater (`scripts/update_live_statuses.php`):**
    This CLI-only PHP script is designed to be run periodically (e.g., via a cron job).
    1.  It fetches all `is_active` live stream entries from `radio_live_streams`.
    2.  For each entry, it calls the appropriate checker function based on the `platform` (e.g., `checkYouTubeChannelLive` for YouTube).
    3.  It updates the `is_live`, `stream_url`, and `title` fields in the `radio_live_streams` table.
    ```bash
    # Example cron job entry (runs every minute)
    * * * * * /path/to/php /path/to/project/scripts/update_live_statuses.php >> /var/log/radio_live_update.log 2>&1
    ```
    This asynchronous approach keeps the player responsive and avoids API key exposure on the frontend.

### 5. Prioritization in Now-Playing Logic

The `api/now-playing.php` endpoint, the "brain" of the radio system, was modified to prioritize live streams.

*   A new check was added at the very beginning of the script:
    ```php
    // Check for an active live stream
    $stmt = $db->query("SELECT id, platform, account_name, stream_url, title FROM radio_live_streams WHERE is_active = 1 AND is_live = 1 LIMIT 1");
    $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($liveStream) {
        jsonResponse([
            'status'        => 'live',
            'media'         => [ /* live stream details */ ],
            'offset'        => 0, // Live streams don't have a fixed offset
            'remaining'     => null,
            'next'          => null,
            'next_check_in' => 5, // Re-check frequently for live stream status changes
        ]);
    }
    // ... if no live stream, proceed to check scheduled items, then loop media
    ```
    This ensures that if a live stream is detected, its details are immediately returned to the player, bypassing scheduled and loop content.

### 6. Player (Frontend) Adaptations

The `player.php` JavaScript was updated to handle the new `'live'` status.

*   **Dynamic Media Embedding:** The `loadMedia()` function now checks for `data.status === 'live'`. If true, it dynamically clears the media container and injects an `<iframe>` for YouTube live streams, using the provided `stream_url`.
    ```javascript
    if (data.status === 'live') {
        // Hide standard player controls, show mediaContainer
        // Embed YouTube iframe: <iframe src="https://www.youtube.com/embed/VIDEO_ID?autoplay=1..."></iframe>
        // ...
        return; // Stop further processing for live streams
    }
    // ... existing logic for audio/video elements
    ```
*   **UI Adjustments:** Elements like the cover art, progress bar, and play/pause controls (for media elements) are hidden or modified when an iframe-based live stream is active, as their playback is controlled directly by the embedded player.
*   **Status Indicator:** The visual status indicator now displays "LIVE" prominently when a live broadcast is active.

## Current Status and Future Enhancements

With these changes, your synchronized internet radio system can now seamlessly integrate live broadcasts from YouTube.

**Current Limitations:**
*   Automatic live status detection is currently implemented only for **YouTube**. While other platforms can be configured in the admin panel, their `is_live`, `stream_url`, and `title` fields will not be automatically updated without further backend implementation.
*   The system uses YouTube's standard embed. More advanced interactions (like chat or specific live events) are not yet integrated.

**Next Steps & Future Ideas:**
*   **Expand Live Platform Support:** Implement live status checking and embedding for Facebook Live, TikTok Live, Instagram Live, and custom RTMP streams.
*   **Enhanced Live Player UI:** Integrate platform-specific live chat or viewer count displays directly into the player.
*   **Live Stream Scheduling:** Allow administrators to "schedule" specific live sources to take over at certain times.

This feature significantly enhances the dynamism and real-time capabilities of your internet radio, offering a hybrid experience that blends curated content with exciting live broadcasts.

---
Enjoy building and broadcasting!
