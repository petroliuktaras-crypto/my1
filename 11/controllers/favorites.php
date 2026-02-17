<?php

$userId = getCurrentUserId();
if ($userId < 1) {
    header('Location: /login');
    exit;
}

$favorites = getUserFavoriteVideos($userId, 60, 0);

render('favorites', [
    'favorites' => $favorites,
]);
