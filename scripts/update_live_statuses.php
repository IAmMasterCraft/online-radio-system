<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/live-checkers/youtube.php';

// This script is meant to be run via CLI or cron job, not directly via web.
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_USER_AGENT'])) {
    die("Access denied: This script can only be run from the command line.");
}

echo "Starting live stream status update...
";

$db = getDB();

try {
    $youtubeApiKey = getSetting('youtube_api_key');
    if (empty($youtubeApiKey)) {
        echo "Warning: YouTube API Key is not set in settings. YouTube live checks will be skipped.
";
    }

    $stmt = $db->query("SELECT id, platform, account_name FROM radio_live_streams WHERE is_active = 1");
    $activeStreams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activeStreams as $stream) {
        echo "Checking stream ID: {$stream['id']} (Platform: {$stream['platform']}, Account: {$stream['account_name']})...
";
        $isLive = false;
        $streamUrl = null;
        $streamTitle = null;

        switch ($stream['platform']) {
            case 'youtube':
                if (empty($youtubeApiKey)) {
                    echo "  Skipping YouTube check: API Key missing.
";
                    continue 2; // Continue to next stream
                }
                $result = checkYouTubeChannelLive($youtubeApiKey, $stream['account_name']);
                $isLive = $result['is_live'];
                $streamUrl = $result['stream_url'];
                $streamTitle = $result['title'];
                break;
            // TODO: Add cases for 'facebook', 'tiktok', 'instagram'
            default:
                echo "  Unknown platform '{$stream['platform']}'. Skipping.
";
                continue 2; // Continue to next stream
        }

        $updateStmt = $db->prepare(
            "UPDATE radio_live_streams 
             SET is_live = ?, stream_url = ?, title = ?, last_checked = NOW() 
             WHERE id = ?"
        );
        $updateStmt->execute([$isLive, $streamUrl, $streamTitle, $stream['id']]);

        if ($isLive) {
            echo "  Status: LIVE! Title: "{$streamTitle}", URL: {$streamUrl}
";
        } else {
            echo "  Status: Offline.
";
        }
    }

    echo "Live stream status update completed.
";

} catch (Exception $e) {
    echo "Error during live stream update: " . $e->getMessage() . "
";
    error_log("Live stream update script error: " . $e->getMessage());
}
