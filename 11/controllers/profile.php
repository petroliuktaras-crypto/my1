<?php

$userId = getCurrentUserId();
if ($userId < 1) {
    header('Location: /login');
    exit;
}

$login = trim((string)($_SESSION['login'] ?? 'Користувач'));
$email = trim((string)($_SESSION['email'] ?? ''));

$profile = getUserProfileOverview($userId);

$profileVideos = getUserFavoriteVideos($userId, 6, 0);
if (empty($profileVideos)) {
    $profileVideos = getLatestVideos(6);
}

render('profile', [
    'profileLogin' => $login,
    'profileEmail' => $email,
    'profileOverview' => $profile,
    'profileVideos' => $profileVideos,
]);
