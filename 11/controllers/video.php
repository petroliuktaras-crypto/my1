<?php


// controllers/video.php

// 1️⃣ отримуємо slug з URL
if (empty($slug)) {
    abort404();
}

$video = getVideoBySlug($slug);
if (!$video) {
    abort404();
}

$counts = getVideoLikeCounts((int)$video['id']);

$likeCount = $counts['likes'];
$dislikeCount = $counts['dislikes'];
$totalVotes = $likeCount + $dislikeCount;
$ratingTen = $totalVotes > 0 ? round(($likeCount / $totalVotes) * 10, 1) : 0.0;

$userReaction = '';
$isFavorite = false;
$currentUserLogin = '';
$currentUserId = getCurrentUserId();
if ($currentUserId > 0) {
    $userReaction = getUserVideoReaction((int)$video['id'], $currentUserId);
    $isFavorite = isVideoFavorite((int)$video['id'], $currentUserId);
    $currentUserLogin = trim((string)($_SESSION['login'] ?? ''));
}

$favoritesCount = getVideoFavoritesCount((int)$video['id']);

addVideoView((int)$video['id']);
$viewsCount = getVideoViewsCount((int)$video['id']);

$durationFormatted = formatVideoDuration($video['duration'] ?? null);



$videosWithActors = getVideosWithSameActors($video['id'], 4);

$screenshots = getVideoScreenshots($video['id']);



// 3️⃣ АКТОРИ
$actors = getVideoActors($video['id']);

// 4️⃣ СТУДІЇ
$studios = getVideoStudios($video['id']);

// 5️⃣ КОЛЕКЦІЇ
$collections = getVideoCollections($video['id']);

// 6️⃣ КОМЕНТАРІ
$comments = getVideoComments($video['id']);

$category = getVideoCategory($db, (int)$video['id']);

// 7️⃣ РЕКОМЕНДОВАНІ ВІДЕО
$recommended = getRecommendedVideos($video['id'], 6);

$token = generatePlayerToken($video['id'], 60);

$uploadedAgo = 'щойно';
if (!empty($video['created_at'])) {
    try {
        $createdAt = new DateTime($video['created_at']);
        $now = new DateTime();
        $diffSec = max(0, $now->getTimestamp() - $createdAt->getTimestamp());

        if ($diffSec < 60) {
            $uploadedAgo = 'щойно';
        } elseif ($diffSec < 3600) {
            $mins = (int)floor($diffSec / 60);
            $uploadedAgo = $mins . ' хвилин назад';
        } elseif ($diffSec < 86400) {
            $hours = (int)floor($diffSec / 3600);
            $uploadedAgo = $hours . ' часов назад';
        } else {
            $days = (int)floor($diffSec / 86400);
            $uploadedAgo = $days . ' днів назад';
        }
    } catch (Throwable $e) {
        $uploadedAgo = 'щойно';
    }
}

// 8️⃣ РЕНДЕР ШАБЛОНУ
render('video', [
    'video'        => $video,
    'rating'       => $rating,
    'votes'        => $votes,
    'likeCount' => $likeCount,
    'dislikeCount' => $dislikeCount,
	'counts' => $counts,
	'totalVotes' => $totalVotes,
    'ratingTen' => $ratingTen,
    'userReaction' => $userReaction,
    'isFavorite' => $isFavorite,
    'favoritesCount' => $favoritesCount,
    'currentUserId' => $currentUserId,
    'currentUserLogin' => $currentUserLogin,
    'viewsCount' => $viewsCount,
    'durationFormatted' => $durationFormatted,
    'uploadedAgo' => $uploadedAgo,
    'actors'       => $actors,
	'category'       => $category,
    'studios'      => $studios,
    'collections'  => $collections,
    'screenshots'  => $screenshots,
    'comments'     => $comments,
    'recommended'  => $recommended,
    'videosWithActors'  => $videosWithActors,
    'token'        => $token
]);