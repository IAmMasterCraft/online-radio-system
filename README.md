# 📻 Simple Radio System

A synchronized virtual radio station for your website. All listeners hear the same content at the same time — no live streaming infrastructure required.

## How It Works

This system simulates a traditional radio station using pre-uploaded media and server-side schedules:

1. **Upload** — Admins upload audio/video files. The system detects each file's duration automatically.
2. **Schedule** — Admins set what plays at specific dates/times (e.g., "Morning Devotional at 9:00 AM").
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

## Requirements

- **PHP 7.4+** (8.0+ recommended)
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

## License

Free to use for charitable and non-profit purposes. Built with ❤️.
