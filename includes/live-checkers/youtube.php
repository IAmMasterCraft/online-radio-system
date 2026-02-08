<?php

require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust path as necessary

use Google\Client;
use Google\Service\YouTube;

function checkYouTubeChannelLive($apiKey, $channelId): array {
    $client = new Client();
    $client->setDeveloperKey($apiKey);

    $youtube = new YouTube($client);

    try {
        $searchResponse = $youtube->search->listSearch('snippet', [
            'channelId' => $channelId,
            'type' => 'video',
            'eventType' => 'live', // Filters for live broadcasts
            'maxResults' => 1,     // We only need to know if at least one is live
        ]);

        if (!empty($searchResponse['items'])) {
            $item = $searchResponse['items'][0];
            $videoId = $item['id']['videoId'];
            $streamTitle = $item['snippet']['title'];
            $streamUrl = "https://www.youtube.com/watch?v={$videoId}";

            return [
                'is_live' => true,
                'stream_url' => $streamUrl,
                'title' => $streamTitle
            ];
        } else {
            return [
                'is_live' => false,
                'stream_url' => null,
                'title' => null
            ];
        }
    } catch (Google\Service\Exception $e) {
        // Log or handle the API error more gracefully in a real application
        error_log("YouTube API service error: " . $e->getMessage());
        return [
            'is_live' => false,
            'stream_url' => null,
            'title' => 'API Error: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("YouTube client error: " . $e->getMessage());
        return [
            'is_live' => false,
            'stream_url' => null,
            'title' => 'Client Error: ' . $e->getMessage()
        ];
    }
}