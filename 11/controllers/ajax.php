<?php
// controllers/ajax.php

$action = $_GET['slug'] ?? null; // router підставляє slug з URL

switch ($action) {
    case 'video-reaction':
        require ROOT . '/controllers/ajax/video-reaction.php';
        break;

    case 'video-favorite':
        require ROOT . '/controllers/ajax/video-favorite.php';
        break;

    case 'video-comment':
        require ROOT . '/controllers/ajax/video-comment.php';
        break;

    case 'comment-like':
        require ROOT . '/controllers/ajax/comment-like.php';
        break;

    default:
        abort404();
}
