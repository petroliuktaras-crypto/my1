<?php

$isAjax = (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest')
    || (stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

$respond = static function (array $payload, int $status = 200) use ($isAjax): void {
    if ($isAjax) {
        if (!headers_sent()) {
            http_response_code($status);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $back = $_SERVER['HTTP_REFERER'] ?? '/';

    $parts = parse_url($back);
    $path = $parts['path'] ?? '/';
    if ($path === '') {
        $path = '/';
    }

    // Повертаємо на сторінку відео без query/hash параметрів
    header('Location: ' . $path);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(['success' => false, 'message' => 'Bad request'], 405);
}

$videoId = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
$message = isset($_POST['message']) ? (string)$_POST['message'] : '';
$nickname = isset($_POST['nickname']) ? (string)$_POST['nickname'] : '';
$email = isset($_POST['email']) ? (string)$_POST['email'] : '';
$parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

if (getCurrentUserId() < 1) {
    $respond(['success' => false, 'auth_required' => true, 'message' => 'Потрібна авторизація'], 401);
}

if ($videoId < 1) {
    $respond(['success' => false, 'message' => 'Invalid params'], 422);
}

$video = getVideoById($videoId);
if (!$video || (int)$video['status'] !== 1) {
    $respond(['success' => false, 'message' => 'Відео не знайдено'], 404);
}

try {
    $res = submitVideoComment($videoId, $message, $nickname, $email, $parentId);

    if (!empty($res['success']) && empty($res['pending']) && !empty($res['comment_id'])) {
        $comment = getCommentById((int)$res['comment_id']);
        if ($comment) {
            $res['comment'] = $comment;
        }
    }

    $respond($res, !empty($res['success']) ? 200 : 422);
} catch (Throwable $e) {
    $respond(['success' => false, 'message' => 'Server error'], 500);
}
