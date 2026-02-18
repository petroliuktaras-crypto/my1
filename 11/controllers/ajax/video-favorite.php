<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Bad request'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = getCurrentUserId();
if ($userId < 1) {
    echo json_encode(['success' => false, 'message' => 'Потрібно увійти'], JSON_UNESCAPED_UNICODE);
    exit;
}

$videoId = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
if ($videoId < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid params'], JSON_UNESCAPED_UNICODE);
    exit;
}

$video = getVideoById($videoId);
if (!$video || (int)$video['status'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Відео не знайдено'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $res = toggleVideoFavorite($videoId, $userId);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
