<?php

$isAjax = (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest')
    || (stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

$respond = static function (array $payload, int $status = 200): void {
    if (!headers_sent()) {
        http_response_code($status);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(['success' => false, 'message' => 'Bad request'], 405);
}

$commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

try {
    $res = toggleCommentLike($commentId);
    $status = !empty($res['success']) ? 200 : (!empty($res['auth_required']) ? 401 : 422);
    $respond($res, $status);
} catch (Throwable $e) {
    $respond(['success' => false, 'message' => 'Server error'], 500);
}
