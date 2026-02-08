# 📻 Simple Radio System

A synchronized virtual radio station for your website. All listeners hear the same content at the same time — no live streaming infrastructure required.

## How It Works

This system simulates a traditional radio station using pre-uploaded media and server-side schedules:

1. **Upload** — Admins upload audio/video files. The system detects each file's duration automatically.
2. **Schedule** — Admins set what plays at specific dates/times (e.g., "News Briefing at 9:00 AM").
3. **Loop / Filler** — Admins designate certain tracks as "loop" media that plays continuously when nothing is scheduled.
4. **Synchronized Playback** — When a listener opens the player:
   - The server checks the current time
   - Finds the scheduled media (or the current position in the loop playlist)
   - Calculates the **offset** (how far into the media we should be)
   - Returns this to the browser, which seeks to the exact position
   - All listeners are in sync — just like real radio

### The Sync Magic

**Scheduled content:** If a show started at 2:00 PM and it's now 2:05 PM, the offset is 5 minutes. Every listener gets told "play this file starting at 5:00."

**Loop/filler content:** Uses a fixed epoch timestamp. The system calculates `(current_time - epoch) % total_loop_duration` to find the exact position in the loop cycle. Since this is pure math, every listener gets the identical result.

The player also periodically re-syncs with the server to correct any drift from buffering or network delays.

---

## Features

*   **Synchronized Media Playback:** All listeners hear the same audio/video content at the same time, whether it's scheduled programming or continuous loop music.
*   **Live Streaming Integration:** Seamlessly integrate live broadcasts from social media platforms (currently YouTube with automatic detection) alongside pre-recorded content. Live streams take precedence over all other programming.
*   **Admin Panel:** A comprehensive web-based interface for managing all aspects of the radio station, including:
    *   Uploading and managing audio/video media.
    *   Creating and managing broadcast schedules.
    *   Organizing and reordering a continuous "loop / filler" playlist.
    *   Configuring API keys (e.g., YouTube Data API key) for external services.
    *   Adding and managing social media live stream sources.
*   **Media Management:** Upload, organize, and manage your audio and video files with automatic duration detection.
*   **Flexible Scheduling:** Define specific times for media playback, with automatic end-time calculation.
*   **Continuous Loop Playback:** Maintain an always-on broadcast with a customizable loop playlist for when no specific programs are scheduled.
*   **Web-based Player:** A responsive player that automatically fetches current programming and synchronizes playback for all listeners.
*   **API Access:** A clean API for developers to build custom players or integrate with other systems.

---

## Requirements

- **PHP 7.4+** (8.0+ recommended) and PHP CLI available in system PATH.
- **Composer** (for PHP dependency management).
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Web server** (Apache or Nginx)
- **ffmpeg/ffprobe** (recommended, for server-side duration detection)
  - Without it, duration is detected via the browser when files are played

## Installation

### 1. Copy files to your web server

Place the `radio/` folder in your website's document root (or wherever you prefer):

```
your-website/
├── index.php          (your existing site)
├── radio/
│   ├── config.php
│   ├── install.php
│   ├── player.php
│   ├── database.sql
│   ├── admin/
│   │   └── index.php
│   ├── api/
│   │   ├── now-playing.php
│   │   ├── upload.php
│   │   ├── media.php
│   │   ├── schedule.php
│   │   └── loop-reorder.php
│   └── uploads/       (created automatically)
```

### 2. Configure

Edit `radio/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_radio');   // Create this database
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// URL path to the radio directory
define('BASE_URL', '/radio');

// Admin login (change these!)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'your-secure-password');
```

### 3. Create the database

```sql
CREATE DATABASE online_radio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3a. Install Composer Dependencies

Navigate to the project root and install PHP dependencies:

```bash
/opt/homebrew/bin/php composer.phar install
```
*(Ensure `php` is in your system PATH or adjust the command above to the full path of your PHP executable)*

### 4. Run the installer

Visit `https://yoursite.com/radio/install.php` in your browser. This creates all the necessary tables and directories.

**⚠️ Delete `install.php` after installation.**

### 5. Install ffmpeg (recommended)

**Ubuntu/Debian:**
```bash
sudo apt update && sudo apt install ffmpeg
```

**CentOS/RHEL:**
```bash
sudo yum install ffmpeg
```

If ffprobe is not available, the system falls back to browser-based duration detection.

### 6. Setup Live Stream Status Updates (Optional, for Live Feature)

If you plan to use the live streaming feature, you'll need to set up a cron job to periodically update the live status of your configured social media accounts.

Example cron job (runs every minute):
```bash
* * * * * /opt/homebrew/bin/php /path/to/your/online-radio-system/scripts/update_live_statuses.php >> /var/log/radio_live_update.log 2>&1
```
*(Adjust `/opt/homebrew/bin/php` and `/path/to/your/online-radio-system/` to your actual paths)*

---

## Usage

### Admin Panel

Visit `https://yoursite.com/radio/admin/` and log in.

**Upload Tab**
- Drag & drop or click to upload audio/video files
- Set title, artist, description
- Optionally upload a cover image
- Check "Add to loop/filler playlist" for background content

**Media Library Tab**
- View all uploaded media
- Preview files
- Toggle loop status
- Delete media

**Schedule Tab**
- Select media and a start time
- The end time is calculated automatically from the media's duration
- View upcoming schedule with "ON AIR" indicators
- Remove schedule entries

**Loop / Filler Tab**
- View all filler tracks
- See total loop duration
- Drag to reorder tracks
- Remove tracks from loop

**Live Streams Tab**
- Add and manage social media accounts for live broadcasting (currently YouTube with automatic status detection).
- The system will automatically check if configured sources are live (requires cron job setup).

**Settings Tab**
- Configure various system settings, including API keys for external services (e.g., YouTube Data API key for live stream detection).

### Player

Visit `https://yoursite.com/radio/player.php`

The player automatically:
- Fetches what's currently playing
- Seeks to the correct position
- Shows track info, progress, and "up next"
- Re-syncs periodically and transitions between tracks
- Supports both audio and video content

---

## Embedding in Your Website

### Simple iframe embed:
```html
<iframe src="/radio/player.php" width="500" height="700" frameborder="0"></iframe>
```

### Use the API directly:

The `now-playing` API returns everything you need to build a custom player:

```
GET /radio/api/now-playing.php
```

Response:
```json
{
  "status": "scheduled",
  "server_time": 1700000000,
  "media": {
    "id": 5,
    "title": "Morning Show",
    "artist": "Jane",
    "url": "/radio/uploads/media_abc123.mp3",
    "media_type": "audio",
    "duration": 3600,
    "cover_image": "/radio/uploads/covers/cover_xyz.jpg"
  },
  "offset": 300.5,
  "remaining": 3299.5,
  "next": {
    "type": "scheduled",
    "title": "News Hour",
    "start_time": "2025-01-15 10:00:00"
  },
  "next_check_in": 3299.5
}
```

### Mini player widget:

You can add a compact player to any page on your site:

```html
<div id="mini-radio"></div>
<script>
  async function initRadio() {
    const resp = await fetch('/radio/api/now-playing.php');
    const data = await resp.json();
    if (data.status === 'offline') return;

    const el = document.getElementById('mini-radio');
    el.innerHTML = `
      <p>🔴 Now Playing: ${data.media.title}</p>
      <audio controls autoplay src="${data.media.url}"></audio>
    `;
    el.querySelector('audio').currentTime = data.offset;
  }
  initRadio();
</script>
```

---

## API Reference

| Endpoint | Method | Description |
|---|---|---|
| `/api/now-playing.php` | GET | Get current media, offset, and next info |
| `/api/media.php` | GET | List all media (filters: `is_loop`, `media_type`, `active`) |
| `/api/media.php?id=X` | GET | Get single media item |
| `/api/media.php` | POST | Update media (JSON body with `id` + fields) |
| `/api/media.php` | PUT | Update duration (JSON body: `{id, duration}`) |
| `/api/media.php?id=X` | DELETE | Delete media and its files |
| `/api/upload.php` | POST | Upload media (multipart form) |
| `/api/schedule.php` | GET | List schedules (query: `range=upcoming|today|week|past|all`) |
| `/api/schedule.php` | POST | Create schedule (JSON: `{media_id, start_time, title?, description?}`) |
| `/api/schedule.php?id=X` | DELETE | Delete schedule entry |
| `/api/loop-reorder.php` | POST | Reorder loop playlist (JSON: `{order: [id, id, ...]}`) |
| `/api/live-streams.php` | GET | List all configured live stream sources |
| `/api/live-streams.php` | POST | Add new live stream source or update (JSON: `{platform, account_name, is_active?}`) |
| `/api/live-streams.php?id=X` | DELETE | Delete a live stream source |
| `/api/settings.php` | GET | Get all application settings |
| `/api/settings.php` | POST | Update application settings (JSON: `{key: value, ...}`) |


---

## PHP Upload Limits

For large media files, you may need to increase PHP's upload limits in `php.ini`:

```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
```

Then restart your web server.

---

## Customization

### Station Name & Tagline

Update via the `radio_settings` database table:

```sql
UPDATE radio_settings SET setting_value = 'Your Station Name' WHERE setting_key = 'station_name';
UPDATE radio_settings SET setting_value = 'Your tagline here' WHERE setting_key = 'station_tagline';
```

### Authentication

The included auth is basic (username/password in config). For production, replace with your existing website's authentication system by modifying the `requireAdmin()` function in `config.php`.

### Styling

The player (`player.php`) and admin panel (`admin/index.php`) use self-contained CSS. Edit the `:root` variables to match your business's brand colors.

---

## Security Considerations

1. **Delete `install.php`** after setup
2. **Change the admin credentials** in `config.php`
3. **Protect the uploads directory** — the included `.htaccess` disables directory listing
4. **Use HTTPS** for your website
5. **Consider rate limiting** the API endpoints
6. For production, implement proper user authentication tied to your existing system

---

## Troubleshooting

**Media won't play?**
- Check browser console for CORS errors
- Ensure `BASE_URL` in config.php matches your actual URL path
- Verify the uploads directory is readable by the web server

**Duration shows as 0?**
- Install ffprobe for reliable server-side detection
- Alternatively, play the file once in the admin preview — the browser detects and reports the duration

**Players out of sync?**
- The default sync interval is 30 seconds with 2-second drift tolerance
- Adjust `SYNC_INTERVAL` and `MAX_DRIFT` in config.php
- Network latency can cause brief desync during buffering

**Large files fail to upload?**
- Increase PHP upload limits (see above)
- Check web server config (Nginx: `client_max_body_size`)

---

## TODO / Future Features

### 🎯 High Priority
- [ ] **Analytics Dashboard** — Track listener count, popular tracks, peak listening times, and geographic distribution
- [ ] **Request System** — Allow listeners to request songs with moderation queue

### 📊 Analytics & Monitoring
- [ ] **Real-time Listener Count** — Show current active listeners on admin panel and player
- [ ] **Listening History** — Track what users listened to and when
- [ ] **Advanced Reports** — Weekly/monthly reports with charts and graphs
- [ ] **Performance Metrics** — Server load, buffering events, error tracking
- [ ] **Listener Demographics** — Device types, browsers, locations (privacy-respecting)

### 🎨 User Experience
- [ ] **Progressive Web App (PWA)** — Installable player with offline support
- [ ] **Dark/Light Theme Toggle** — User preference for player appearance
- [] **Mini Player Mode** — Compact floating player that stays on screen while browsing
- [ ] **Keyboard Shortcuts** — Space to play/pause, arrow keys for volume, etc.
- [ ] **Chromecast & AirPlay Support** — Stream to smart TVs and speakers
- [ ] **Sleep Timer** — Auto-stop playback after specified time

### 🎙️ Content Management
- [ ] **Podcast Integration** — Auto-import episodes from RSS feeds and schedule them
- [ ] **Bulk Upload** — Upload multiple files at once with batch metadata editing
- [ ] **Media Categories/Tags** — Organize content by genre, mood, topic, etc.
- [ ] **Smart Playlists** — Auto-generate playlists based on rules (genre, duration, tags)
- [ ] **Content Calendar** — Visual monthly/weekly view of scheduled programming
- [ ] **Recurring Schedules** — Schedule shows to repeat daily/weekly/monthly
- [ ] **Drag-and-Drop Scheduling** — Visual timeline interface for scheduling

### 💬 Community Features
- [ ] **Live Chat** — Real-time chat for listeners during broadcasts
- [ ] **Comments System** — Allow listeners to comment on shows/tracks
- [ ] **Listener Polls** — Run interactive polls during broadcasts
- [ ] **Social Media Integration** — Auto-post "Now Playing" to Twitter, Facebook, Discord
- [ ] **Share Functionality** — Let listeners share what they're listening to
- [ ] **Email Notifications** — Subscribe to notifications for favorite shows

### 🔧 Technical Improvements
- [ ] **CDN Support** — Serve media files from CDN for better performance
- [ ] **Cloud Storage Integration** — S3, Google Cloud Storage, Azure Blob support
- [ ] **Auto-Transcoding** — Convert uploaded files to optimal formats automatically
- [ ] **Adaptive Bitrate Streaming** — Multiple quality levels for different connection speeds
- [ ] **WebSocket Support** — Real-time updates without polling
- [ ] **API Webhooks** — Notify external services when events occur (show starts, ends, etc.)
- [ ] **Multi-station Support** — Run multiple radio stations from single installation
- [ ] **Database Migration System** — Version-controlled database schema updates
- [ ] **Docker Support** — Containerized deployment with docker-compose

### 🎛️ DJ & Producer Tools
- [ ] **DJ Panel** — Separate interface for DJs to manage their shows
- [ ] **Live Mixer** — Simple web-based audio mixer for live shows
- [ ] **Show Notes & Timestamps** — Add markers and notes during shows
- [ ] **Pre-show Preparation** — Upload and prepare content before going live
- [ ] **Automated Intros/Outros** — Auto-insert station IDs and jingles

### 🛡️ Security & Compliance
- [ ] **OAuth2 Integration** — Support for Google, Facebook, GitHub login
- [ ] **Two-Factor Authentication** — 2FA for admin accounts
- [ ] **Content Licensing Tracking** — Track music licenses and royalty information
- [ ] **DMCA Compliance Tools** — Automated copyright claim handling
- [ ] **Privacy Controls** — GDPR-compliant listener data management
- [ ] **Rate Limiting** — Protect API endpoints from abuse

### 💰 Monetization (Optional)
- [ ] **Ad Insertion System** — Dynamic ad breaks with targeting
- [ ] **Sponsorship Tools** — Manage sponsor mentions and advertisements
- [ ] **Donation Integration** — Accept listener donations (Stripe, PayPal)
- [ ] **Premium Subscriptions** — Ad-free listening, exclusive content
- [ ] **Merchandise Store Integration** — Link to station merchandise

### 🌐 Internationalization
- [ ] **Multi-language Support** — Player and admin interface in multiple languages
- [ ] **Timezone Management** — Display schedules in listener's local timezone
- [ ] **Localized Content** — Different content for different regions

---

## Contributing

Contributions make the world a better place! If you'd like to work on any of the TODO items above or have other suggestions, please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request with clear description of changes

For major features, please open an issue first to discuss your ideas.

---

## License

Free to use for charitable and non-profit purposes. Built with ❤️.
