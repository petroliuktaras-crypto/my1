<?php

function db(): PDO {
    global $db;
    return $db;
}

function getDB(): PDO {
    return db();
}

function render($tpl, $data = []) {
    
    // Глобальні змінні для всіх шаблонів
    $current = getCurrentUrlPath();

    // Дані з контролера
    extract($data);
    require ROOT.'/templates/'.TEMPLATE.'/header.php';
    require ROOT.'/templates/'.TEMPLATE.'/'.$tpl.'.php';
    require ROOT.'/templates/'.TEMPLATE.'/footer.php';
}

function abort404() {
    require ROOT.'/controllers/404.php';
    exit;
}

function getCurrentUrlPath() {
    return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

function getLatestVideos($limit = 12) {
    global $db;
    $stmt = $db->prepare("
        SELECT *
        FROM videos
        WHERE status = 1
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getPopularVideos($limit = 12) {
    global $db;
    $stmt = $db->prepare("
        SELECT *
        FROM videos
        WHERE status = 1
        ORDER BY views DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getVideoById($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getVideoBySlug($slug) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM videos WHERE slug = ? AND status = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}


function tableHasColumn(string $table, string $column): bool {
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if ($tableSafe === '' || $columnSafe === '') {
            return $cache[$key] = false;
        }

        $st = db()->query("SHOW COLUMNS FROM `{$tableSafe}` LIKE " . db()->quote($columnSafe));
        $cache[$key] = (bool)$st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function getCommentsSchema(): array {
    static $schema = null;
    if ($schema !== null) {
        return $schema;
    }

    $pick = static function (array $variants): string {
        foreach ($variants as $column) {
            if (tableHasColumn('comments', $column)) {
                return $column;
            }
        }
        return '';
    };

    $schema = [
        'videoColumn' => $pick(['video_id', 'videoid', 'video']),
        'messageColumn' => $pick(['message', 'text', 'comment', 'content']),
        'nameColumn' => $pick(['guest_name', 'nickname', 'author_name', 'name']),
        'emailColumn' => $pick(['guest_email', 'email', 'author_email']),
        'hasUserId' => tableHasColumn('comments', 'user_id'),
        'hasStatus' => tableHasColumn('comments', 'status'),
        'hasParentId' => tableHasColumn('comments', 'parent_id'),
        'hasCreatedAt' => tableHasColumn('comments', 'created_at'),
        'hasLikes' => tableHasColumn('comments', 'likes'),
    ];

    return $schema;
}


function encodeCommentEmojiEntities(string $text): string {
    return preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u', static function (array $m): string {
        $cp = mb_ord($m[0], 'UTF-8');
        return sprintf('&#x%X;', $cp);
    }, $text) ?? $text;
}

function renderCommentMessage(string $text): string {
    $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $safe = preg_replace_callback('/&amp;#x([0-9A-Fa-f]{4,6});/', static function (array $m): string {
        $cp = hexdec($m[1]);
        if ($cp >= 0x10000 && $cp <= 0x10FFFF) {
            return '&#x' . strtoupper($m[1]) . ';';
        }
        return $m[0];
    }, $safe) ?? $safe;

    $safe = preg_replace_callback('/&amp;#([0-9]{5,7});/', static function (array $m): string {
        $cp = (int)$m[1];
        if ($cp >= 0x10000 && $cp <= 0x10FFFF) {
            return '&#' . $m[1] . ';';
        }
        return $m[0];
    }, $safe) ?? $safe;

    return nl2br($safe);
}

function formatVideoDuration($value): string {
    if ($value === null) {
        return '00:00:00';
    }

    $raw = trim((string)$value);
    if ($raw === '') {
        return '00:00:00';
    }

    // Якщо в БД duration збережений у секундах
    if (ctype_digit($raw)) {
        $seconds = (int)$raw;
        $hours = (int)floor($seconds / 3600);
        $minutes = (int)floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    // Якщо формат вже часу (mm:ss або hh:mm:ss)
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $raw)) {
        $parts = array_map('intval', explode(':', $raw));
        if (count($parts) === 2) {
            [$m, $s] = $parts;
            return sprintf('%02d:%02d:%02d', 0, $m, $s);
        }

        [$h, $m, $s] = $parts;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    return '00:00:00';
}

function getActorBySlug($slug) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM actors WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getActorVideos($actor_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT v.* FROM videos v
        JOIN video_actors va ON va.video_id = v.id
        WHERE va.actor_id = ?
    ");
    $stmt->execute([$actor_id]);
    return $stmt->fetchAll();
}

function getVideoActors(int $video_id): array {
    $stmt = db()->prepare("
        SELECT a.id, a.name, a.slug, a.photo
        FROM actors a
        JOIN video_actors va ON va.actor_id = a.id
        WHERE va.video_id = ?
        ORDER BY a.name
    ");
    $stmt->execute([$video_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVideoStudios(int $video_id): array {
    $stmt = db()->prepare("
        SELECT s.id, s.title, s.slug, s.poster
        FROM studios s
        JOIN video_studios vs ON vs.studio_id = s.id
        WHERE vs.video_id = ?
        ORDER BY s.title
    ");
    $stmt->execute([$video_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVideoCollections(int $video_id): array {
    $stmt = db()->prepare("
        SELECT 
            c.id,
            c.title,
            c.slug,
            c.poster,
            COUNT(cv2.video_id) AS total
        FROM collections c
        JOIN collection_videos cv ON cv.collection_id = c.id
        JOIN collection_videos cv2 ON cv2.collection_id = c.id
        WHERE cv.video_id = ?
        GROUP BY c.id
        ORDER BY c.title
    ");
    $stmt->execute([$video_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVideoComments(int $video_id): array {
    $schema = getCommentsSchema();
    if ($schema['videoColumn'] === '' || $schema['messageColumn'] === '') {
        return [];
    }

    $likesExpr = $schema['hasLikes'] ? 'c.likes' : '0';
    $nameExpr = $schema['nameColumn'] !== '' ? "c.`{$schema['nameColumn']}`" : "''";
    $messageExpr = "c.`{$schema['messageColumn']}`";
    $createdAtExpr = $schema['hasCreatedAt'] ? 'c.created_at' : 'NOW()';

    $joinUsers = $schema['hasUserId'] ? 'LEFT JOIN users u ON u.id = c.user_id' : 'LEFT JOIN users u ON 1=0';

    $where = ["c.`{$schema['videoColumn']}` = ?"];
    if ($schema['hasStatus']) {
        $where[] = 'c.status = 1';
    }
    if ($schema['hasParentId']) {
        $where[] = 'c.parent_id IS NULL';
    }

    $sql = "
        SELECT
            c.id,
            {$messageExpr} AS message,
            {$createdAtExpr} AS created_at,
            {$likesExpr} AS likes,
            COALESCE(u.login, {$nameExpr}, 'Гість') AS login,
            u.avatar
        FROM comments c
        {$joinUsers}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . ($schema['hasCreatedAt'] ? 'c.created_at' : 'c.id') . " DESC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$video_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCommentById(int $commentId): ?array {
    if ($commentId < 1) {
        return null;
    }

    $schema = getCommentsSchema();
    if ($schema['messageColumn'] === '') {
        return null;
    }

    $likesExpr = $schema['hasLikes'] ? 'c.likes' : '0';
    $nameExpr = $schema['nameColumn'] !== '' ? "c.`{$schema['nameColumn']}`" : "''";
    $messageExpr = "c.`{$schema['messageColumn']}`";
    $createdAtExpr = $schema['hasCreatedAt'] ? 'c.created_at' : 'NOW()';
    $joinUsers = $schema['hasUserId'] ? 'LEFT JOIN users u ON u.id = c.user_id' : 'LEFT JOIN users u ON 1=0';

    $stmt = db()->prepare("
        SELECT
            c.id,
            {$messageExpr} AS message,
            {$createdAtExpr} AS created_at,
            {$likesExpr} AS likes,
            COALESCE(u.login, {$nameExpr}, 'Гість') AS login,
            u.avatar
        FROM comments c
        {$joinUsers}
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->execute([$commentId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function submitVideoComment(int $videoId, string $message, string $guestName = '', string $guestEmail = '', int $parentId = 0): array {
    $message = trim($message);
    $message = encodeCommentEmojiEntities($message);
    if ($videoId < 1 || $message === '') {
        return ['success' => false, 'message' => 'Невірні дані'];
    }

    if (mb_strlen($message) > 2000) {
        return ['success' => false, 'message' => 'Коментар занадто довгий'];
    }

    $userId = getCurrentUserId();
    if ($userId < 1) {
        return ['success' => false, 'message' => 'Тільки для зареєстрованих користувачів'];
    }

    $schema = getCommentsSchema();
    if ($schema['videoColumn'] === '' || $schema['messageColumn'] === '') {
        return ['success' => false, 'message' => 'Помилка структури таблиці comments'];
    }

    $db = db();

    $columns = [$schema['videoColumn'], $schema['messageColumn']];
    $placeholders = ['?', '?'];
    $params = [$videoId, $message];

    if ($schema['hasStatus']) {
        $columns[] = 'status';
        $placeholders[] = '0';
    }

    if ($schema['hasCreatedAt']) {
        $columns[] = 'created_at';
        $placeholders[] = 'NOW()';
    }

    if ($parentId > 0) {
        $parent = getCommentById($parentId);
        if (!$parent) {
            return ['success' => false, 'message' => 'Коментар для відповіді не знайдено'];
        }
    }

    if ($schema['hasParentId']) {
        $columns[] = 'parent_id';
        if ($parentId > 0) {
            $placeholders[] = '?';
            $params[] = $parentId;
        } else {
            $placeholders[] = 'NULL';
        }
    } elseif ($parentId > 0) {
        return ['success' => false, 'message' => 'Відповіді не підтримуються'];
    }

    if ($schema['hasUserId']) {
        $columns[] = 'user_id';
        $placeholders[] = '?';
        $params[] = $userId;
    }

    if ($schema['nameColumn'] !== '') {
        $guestName = trim((string)($_SESSION['login'] ?? $guestName));
        $columns[] = $schema['nameColumn'];
        $placeholders[] = '?';
        $params[] = $guestName !== '' ? $guestName : ('user_' . $userId);
    }

    $sql = 'INSERT INTO comments (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return [
        'success' => true,
        'message' => 'Коментар відправлено на модерацію',
        'pending' => true,
        'comment_id' => (int)$db->lastInsertId(),
    ];
}


function getCommentReplies(int $parentId): array {
    if ($parentId < 1) {
        return [];
    }

    $schema = getCommentsSchema();
    if (!$schema['hasParentId'] || $schema['messageColumn'] === '') {
        return [];
    }

    $likesExpr = $schema['hasLikes'] ? 'c.likes' : '0';
    $nameExpr = $schema['nameColumn'] !== '' ? "c.`{$schema['nameColumn']}`" : "''";
    $messageExpr = "c.`{$schema['messageColumn']}`";
    $createdAtExpr = $schema['hasCreatedAt'] ? 'c.created_at' : 'NOW()';
    $joinUsers = $schema['hasUserId'] ? 'LEFT JOIN users u ON u.id = c.user_id' : 'LEFT JOIN users u ON 1=0';

    $where = ['c.parent_id = ?'];
    if ($schema['hasStatus']) {
        $where[] = 'c.status = 1';
    }

    $sql = "
        SELECT
            c.id,
            {$messageExpr} AS message,
            {$createdAtExpr} AS created_at,
            {$likesExpr} AS likes,
            COALESCE(u.login, {$nameExpr}, 'Гість') AS login,
            u.avatar
        FROM comments c
        {$joinUsers}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . ($schema['hasCreatedAt'] ? 'c.created_at' : 'c.id') . " ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function toggleCommentLike(int $commentId): array {
    if ($commentId < 1) {
        return ['success' => false, 'message' => 'Invalid comment'];
    }

    if (getCurrentUserId() < 1) {
        return ['success' => false, 'auth_required' => true, 'message' => 'Потрібна авторизація'];
    }

    if (!tableHasColumn('comments', 'likes')) {
        return ['success' => false, 'message' => 'Лайки коментарів не підтримуються'];
    }

    if (!isset($_SESSION['comment_likes']) || !is_array($_SESSION['comment_likes'])) {
        $_SESSION['comment_likes'] = [];
    }

    $isLiked = !empty($_SESSION['comment_likes'][$commentId]);

    try {
        $exists = db()->prepare('SELECT id FROM comments WHERE id = ? LIMIT 1');
        $exists->execute([$commentId]);
        if (!$exists->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'message' => 'Коментар не знайдено'];
        }

        if ($isLiked) {
            $stmt = db()->prepare('UPDATE comments SET likes = CASE WHEN likes > 0 THEN likes - 1 ELSE 0 END WHERE id = ?');
            $stmt->execute([$commentId]);
            unset($_SESSION['comment_likes'][$commentId]);
        } else {
            $stmt = db()->prepare('UPDATE comments SET likes = likes + 1 WHERE id = ?');
            $stmt->execute([$commentId]);
            $_SESSION['comment_likes'][$commentId] = 1;
        }

        $likesStmt = db()->prepare('SELECT likes FROM comments WHERE id = ? LIMIT 1');
        $likesStmt->execute([$commentId]);
        $row = $likesStmt->fetch(PDO::FETCH_ASSOC) ?: ['likes' => 0];

        return [
            'success' => true,
            'liked' => !$isLiked,
            'likes' => (int)($row['likes'] ?? 0),
        ];
    } catch (Throwable $e) {
        error_log('toggleCommentLike error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Не вдалося оновити лайк'];
    }
}

function getRecommendedVideos(int $video_id, int $limit = 6): array {
    $stmt = db()->prepare("
        SELECT *
        FROM videos
        WHERE status = 1
          AND id != ?
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->bindValue(1, $video_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getStudioBySlug(string $slug) {
    $stmt = db()->prepare("
        SELECT * FROM studios WHERE slug = ?
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStudioVideos(int $studioId, int $limit, int $offset): array {
    $stmt = db()->prepare("
        SELECT v.*
        FROM videos v
        JOIN video_studios vs ON vs.video_id = v.id
        WHERE vs.studio_id = ?
          AND v.status = 1
        ORDER BY v.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");

    $stmt->execute([$studioId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

define('PLAYER_SECRET', 'CHANGE_THIS_SECRET_123');

function generatePlayerToken(int $video_id, int $ttl = 1800): string {
    $expires = time() + $ttl;
    $payload = $video_id . '|' . $expires;
    $hash = hash_hmac('sha256', $payload, PLAYER_SECRET);
    return base64_encode($payload . '|' . $hash);
}

function validatePlayerToken(string $token): ?int {
    $decoded = base64_decode($token);
    if (!$decoded) return null;

    [$video_id, $expires, $hash] = explode('|', $decoded);

    if ($expires < time()) return null;

    $check = hash_hmac(
        'sha256',
        $video_id . '|' . $expires,
        PLAYER_SECRET
    );

    if (!hash_equals($check, $hash)) return null;

    return (int)$video_id;
}

function getActorsList( ?string $letter = null, ?string $gender = null, int $limit = 18, int $offset = 0 ): array {

    // захист
    $limit  = (int)$limit;
    $offset = (int)$offset;

    $sql = "
        SELECT 
            a.id,
            a.name,
            a.slug,
            a.photo,
            a.gender,
            COUNT(DISTINCT v.id) AS total_videos
        FROM actors a
        LEFT JOIN video_actors va ON va.actor_id = a.id
        LEFT JOIN videos v ON v.id = va.video_id AND v.status = 1
        WHERE 1
    ";

    $params = [];

    if ($letter) {
        $sql .= " AND a.name LIKE ? ";
        $params[] = $letter . '%';
    }

    if ($gender) {
        $sql .= " AND a.gender = ? ";
        $params[] = $gender;
    }

    $sql .= "
        GROUP BY a.id
        ORDER BY a.name
        LIMIT $limit OFFSET $offset
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActorsTotal(?string $letter = null, ?string $gender = null): int {
    $sql = "
        SELECT COUNT(DISTINCT a.id)
        FROM actors a
        LEFT JOIN video_actors va ON va.actor_id = a.id
        LEFT JOIN videos v ON v.id = va.video_id AND v.status = 1
        WHERE 1
    ";

    $params = [];

    if ($letter) {
        $sql .= " AND a.name LIKE ? ";
        $params[] = $letter . '%';
    }

    if ($gender) {
        $sql .= " AND a.gender = ? ";
        $params[] = $gender;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function getCategoriesWithCount(): array {
    $stmt = db()->query("
        SELECT 
            c.id,
            c.title,
            c.slug,
            COUNT(cv.video_id) AS total
        FROM categories c
        LEFT JOIN category_video cv ON cv.category_id = c.id
        GROUP BY c.id
        ORDER BY c.title
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryBySlug(string $slug): ?array {
    $stmt = db()->prepare("
        SELECT *
        FROM categories
        WHERE slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getCategoryVideos(int $categoryId, int $limit, int $offset): array {
    $limit  = (int)$limit;
    $offset = (int)$offset;

    $stmt = db()->prepare("
        SELECT v.*
        FROM videos v
        JOIN category_video cv ON cv.video_id = v.id
        WHERE cv.category_id = ?
          AND v.status = 1
        ORDER BY v.created_at DESC
        LIMIT $limit OFFSET $offset
    ");

    $stmt->execute([$categoryId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryVideosCount(int $categoryId): int {
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM category_video cv
        JOIN videos v ON v.id = cv.video_id AND v.status = 1
        WHERE cv.category_id = ?
    ");
    $stmt->execute([$categoryId]);
    return (int)$stmt->fetchColumn();
}

function getCollectionsWithCount(string $sort = 'new'): array {
    $orderBy = match ($sort) {
        'popular' => 'total_videos DESC',
        default   => 'c.created_at ASC'
    };

    $sql = "
        SELECT 
            c.id,
            c.title,
            c.slug,
            c.poster,
            COUNT(cv.video_id) AS total_videos
        FROM collections c
        LEFT JOIN collection_videos cv ON cv.collection_id = c.id
        GROUP BY c.id
        ORDER BY $orderBy
    ";

    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getCollectionBySlug(string $slug): ?array {
    $stmt = db()->prepare("
        SELECT *
        FROM collections
        WHERE slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getCollectionVideos(int $collectionId, int $limit, int $offset): array {
    $stmt = db()->prepare("
        SELECT v.*
        FROM videos v
        INNER JOIN collection_videos cv ON cv.video_id = v.id
        WHERE cv.collection_id = ?
        ORDER BY v.created_at DESC
        LIMIT $limit OFFSET $offset
    ");

    $stmt->execute([$collectionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCollectionVideosCount(int $collectionId): int {
    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM collection_videos
        WHERE collection_id = ?
    ");
    $stmt->execute([$collectionId]);

    return (int)$stmt->fetchColumn();
}

function getStudiosWithCount(string $sort = 'new'): array {
    $orderBy = match ($sort) {
        'popular' => 'total_videos DESC',
        default   => 's.created_at DESC'
    };

    $sql = "
        SELECT 
            s.id,
            s.title,
            s.slug,
            s.poster,
            s.created_at,
            COUNT(vs.video_id) AS total_videos
        FROM studios s
        LEFT JOIN video_studios vs ON vs.studio_id = s.id
        GROUP BY s.id
        ORDER BY {$orderBy}
    ";

    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


function getStudioVideosCount(int $studioId): int {
    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM video_studios
        WHERE studio_id = ?
    ");
    $stmt->execute([$studioId]);
    return (int)$stmt->fetchColumn();
}

function getVideoCategory(PDO $db, int $videoId): ?array
{
    $stmt = $db->prepare("
        SELECT c.id, c.title, c.slug
        FROM categories c
        INNER JOIN category_video cv ON cv.category_id = c.id
        WHERE cv.video_id = ?
        ORDER BY c.id ASC
        LIMIT 1
    ");
    $stmt->execute([$videoId]);

    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    return $category ?: null;
}

function getVideosWithSameActors($videoId, $limit = 4)
{
    global $db;

    $videoId = (int)$videoId;
    $limit   = (int)$limit;

    $sql = "
        SELECT v.* FROM videos v
        INNER JOIN video_actors va ON va.video_id = v.id
        WHERE va.actor_id IN (
            SELECT actor_id
            FROM video_actors
            WHERE video_id = $videoId
        )
        AND v.id != $videoId
        ORDER BY v.id DESC
        LIMIT $limit
    ";

    $result = $db->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetchAll(PDO::FETCH_ASSOC);
}

function getVideoScreenshots(int $videoId): array
{
    global $db;

    $videoId = (int)$videoId;

    $sql = "
        SELECT image
        FROM video_screenshots
        WHERE video_id = $videoId
        ORDER BY sort ASC, id ASC
    ";

    $res = $db->query($sql);
    return $res ? $res->fetchAll(PDO::FETCH_COLUMN) : [];
}

// Функція для авторизації користувача
function login($email, $password) {
    global $db;

    // Перевірка користувача в базі
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Генерація нового session_id
        session_regenerate_id(true);  // Генеруємо новий session_id

        // Зберігаємо користувача в сесії
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login']; // Замість email, зберігаємо login

        // Створюємо запис сесії в базі даних
        $session_id = session_id();
        $stmt = $db->prepare("INSERT INTO sessions (user_id, session_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $session_id]);

        // Збереження session_id в cookies
        setcookie('user_id', $_SESSION['user_id'], time() + 3600, '/');
        setcookie('session_id', $session_id, time() + 3600, '/');

        return true;
    }

    return false; // Якщо логін або пароль невірні
}



function checkSession() {
    global $db;  // Переконайтесь, що $db ініціалізовано для з'єднання з базою даних

    // Якщо сесія вже активна, не робимо нічого
    if (isset($_SESSION['user_id'])) {
        return;
    }

    // Якщо є cookies
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['session_id'])) {
        $user_id = $_COOKIE['user_id'];
        $session_id = $_COOKIE['session_id'];

        // Перевірка наявності цієї сесії в базі даних
        $stmt = $db->prepare("SELECT * FROM sessions WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$user_id, $session_id]);
        $session = $stmt->fetch();

        if ($session) {
            // Відновлюємо сесію
            $_SESSION['user_id'] = $user_id;
            $_SESSION['login'] = $session['login']; // Замість email, зберігаємо login

            // Оновлюємо час сесії в базі даних
            $stmt = $db->prepare("UPDATE sessions SET created_at = NOW() WHERE session_id = ?");
            $stmt->execute([$session_id]);
        } else {
            // Якщо сесії немає в базі даних, видаляємо cookies
            setcookie('user_id', '', time() - 3600, '/');
            setcookie('session_id', '', time() - 3600, '/');
        }
    }
}






function logout() {
    global $db;

    // Видаляємо сесію з бази даних
    if (isset($_SESSION['user_id'])) {
        $session_id = session_id();
        $stmt = $db->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }

    // Знищуємо сесію
    session_unset(); // Видаляємо всі змінні сесії
    session_destroy(); // Знищуємо сесію

    // Видаляємо cookies
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('session_id', '', time() - 3600, '/');

    // Перенаправляємо на сторінку логіну
    header('Location: /login');
    exit();
}

// Функція для перевірки, чи існує користувач в базі
function userExists($email, $nickname) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR login = ?");
    $stmt->execute([$email, $nickname]);
    return $stmt->fetch() ? true : false;
}


function registerUser($email, $nickname, $password) {
    global $db;

    // Хешуємо пароль
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Встановлюємо роль за замовчуванням "user" та статус "1"
    $role = 'user';
    $status = 1;

    // Перевірка, чи вже існує користувач з таким імейлом чи нікнеймом
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR login = ?");
    $stmt->execute([$email, $nickname]);
    if ($stmt->fetch()) {
        return false; // Якщо такий користувач вже існує, реєстрація не успішна
    }

    // Додаємо нового користувача в базу даних
    $stmt = $db->prepare("INSERT INTO users (email, login, password, role, status) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$email, $nickname, $hashed_password, $role, $status]); // Повертаємо true/false в залежності від успіху
}




// Підключення класів PHPMailer
require_once '/home/fi606084/koduma.xyz/www/ajax/phpmailer/src/phpmailer.php';
require_once '/home/fi606084/koduma.xyz/www/ajax/phpmailer/src/exception.php';
require_once '/home/fi606084/koduma.xyz/www/ajax/phpmailer/src/smtp.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function sendResetPasswordEmail($email, $token) {
    $subject = "Відновлення паролю на сайті Koduma.xyz";
    
    // Використовуємо базову URL-адресу для створення посилання
    $resetLink = BASE_URL . "/reset-password?token=$token";
    
    // Тіло листа
    $message = "
    <html>
    <head>
    <title>$subject</title>
    </head>
    <body>
    <p>Ви запитали відновлення паролю на нашому сайті. Перейдіть за наступним посиланням, щоб змінити ваш пароль:</p>
    <p><a href='$resetLink'>$resetLink</a></p>
    </body>
    </html>
    ";

    // Створення об'єкта PHPMailer
    $mail = new PHPMailer(true);  // Включаємо обробку виключень
    try {
        // Налаштування для Gmail
        $mail->isSMTP();  // Використовуємо SMTP
        $mail->Host = 'smtp.gmail.com';  // SMTP сервер для Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'petroliuk.taras@gmail.com';  // Ваш Gmail
        $mail->Password = 'lhvo tphs joov mqzn';  // Ваш пароль або застосунковий пароль
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Використовуємо TLS
        $mail->Port = 587;  // Порт для TLS

       // Встановлюємо кодування
        $mail->CharSet = 'UTF-8';  // Встановлюємо кодування UTF-8



        // Встановлюємо відправника та одержувача
        $mail->setFrom('your_email@gmail.com', 'Відновлення паролю');
        $mail->addAddress($email);  // Адреса отримувача

        // Встановлюємо вміст листа
        $mail->isHTML(true);  // Лист буде відправлений у форматі HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Відправка листа
        if ($mail->send()) {
            return true;  // Лист успішно відправлений
        } else {
            return false;  // Якщо не вдалося відправити лист
        }
    } catch (Exception $e) {
        return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    }
}


// functions.php

// Функція для пошуку в базі даних
function searchDatabase($query) {
    global $db; // Підключення до бази даних через PDO

    // Пошук відео за назвою (вибираємо всі поля)
    $stmt = $db->prepare("SELECT * FROM videos WHERE title LIKE :query LIMIT 10");
    $stmt->execute(['query' => "%$query%"]);
    $videos = $stmt->fetchAll();

    // Пошук акторів за іменем (вибираємо всі поля)
    $stmt = $db->prepare("SELECT * FROM actors WHERE name LIKE :query LIMIT 10");
    $stmt->execute(['query' => "%$query%"]);
    $actors = $stmt->fetchAll();

    // Пошук студій за назвою (вибираємо всі поля)
    $stmt = $db->prepare("SELECT * FROM studios WHERE title LIKE :query LIMIT 10");
    $stmt->execute(['query' => "%$query%"]);
    $studios = $stmt->fetchAll();

    // Об'єднуємо всі результати
    $results = [];

    // Додаємо відео
    foreach ($videos as $video) {
        $results[] = [
            'type' => 'video',
            'name' => $video['title'],
            'url' => "/video/{$video['slug']}",
            'icon' => 'film',
            'preview' => $video['preview'], // Додайте всі інші поля, які вам потрібні
            'created_at' => $video['created_at'],
            'poster' => $video['poster'],
            'duration' => $video['duration'],
            'view' => $video['view'],
        ];
    }

    // Додаємо акторів
    foreach ($actors as $actor) {
        // Підрахунок кількості відео для кожного актора
        $stmt = $db->prepare("SELECT COUNT(*) AS video_count FROM video_actors WHERE actor_id = :actor_id");
        $stmt->execute(['actor_id' => $actor['id']]);
        $actorVideoCount = $stmt->fetchColumn();

        $results[] = [
            'type' => 'actor',
            'name' => $actor['name'],
            'url' => "/actor/{$actor['slug']}",
            'icon' => 'venus-mars',
            'photo' => $actor['photo'], // Додайте всі інші поля, які вам потрібні
            'video_count' => $actorVideoCount, // Кількість відео для цього актора
        ];
    }

    // Додаємо студії
    foreach ($studios as $studio) {
        // Підрахунок кількості відео для кожної студії
        $stmt = $db->prepare("SELECT COUNT(*) AS video_count FROM video_studios WHERE studio_id = :studio_id");
        $stmt->execute(['studio_id' => $studio['id']]);
        $studioVideoCount = $stmt->fetchColumn();

        $results[] = [
            'type' => 'studio',
            'name' => $studio['title'],
            'url' => "/studio/{$studio['slug']}",
            'icon' => 'images',
            'poster' => $studio['poster'], 
            'video_count' => $studioVideoCount, 
        ];
    }

    return $results;
}

function getCurrentSessionId(): string {
    // 1) найчастіше сайт ставить свою cookie 'session_id'
    if (!empty($_COOKIE['session_id'])) {
        return trim((string)$_COOKIE['session_id']);
    }

    // 2) або може бути стандартна cookie PHP (PHPSESSID)
    $php = session_name(); // зазвичай PHPSESSID
    if (!empty($_COOKIE[$php])) {
        return trim((string)$_COOKIE[$php]);
    }

    return '';
}

function getCurrentUserId(): int {
    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }

    if (empty($_COOKIE['session_id'])) return 0;

    $sid = trim((string)$_COOKIE['session_id']);
    if ($sid === '') return 0;

    $db = db();

    $st = $db->prepare("SELECT user_id FROM sessions WHERE session_id = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$sid]);

    $uid = $st->fetchColumn();
    return $uid ? (int)$uid : 0;
}

function getUserVideoReaction(int $videoId, int $userId): string {
    if ($videoId < 1 || $userId < 1) {
        return '';
    }

    $st = db()->prepare("SELECT reaction FROM video_likes WHERE video_id = ? AND user_id = ? LIMIT 1");
    $st->execute([$videoId, $userId]);

    $reaction = (string)$st->fetchColumn();
    return in_array($reaction, ['like', 'dislike'], true) ? $reaction : '';
}



// Лічильники лайків/дізлайків по відео
function getVideoLikeCounts(int $videoId): array {
    $db = getDB();

    $st = $db->prepare("
        SELECT
            SUM(reaction = 'like') AS likes,
            SUM(reaction = 'dislike') AS dislikes
        FROM video_likes
        WHERE video_id = ?
    ");
    $st->execute([$videoId]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'likes' => (int)($row['likes'] ?? 0),
        'dislikes' => (int)($row['dislikes'] ?? 0),
    ];
}

// Поставити/зняти/замінити реакцію
function toggleVideoReaction(int $videoId, int $userId, string $reaction): array {
    if (!in_array($reaction, ['like', 'dislike'], true)) {
        return ['success' => false, 'message' => 'Bad reaction'];
    }

    if ($videoId < 1 || $userId < 1) {
        return ['success' => false, 'message' => 'Bad params'];
    }

    $db = getDB();

    $st = $db->prepare("SELECT id, reaction FROM video_likes WHERE video_id=? AND user_id=? LIMIT 1");
    $st->execute([$videoId, $userId]);
    $exist = $st->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        if ($exist['reaction'] === $reaction) {
            // натиснув те саме -> зняти
            $del = $db->prepare("DELETE FROM video_likes WHERE id=? LIMIT 1");
            $del->execute([(int)$exist['id']]);
            $status = 'removed';
        } else {
            // перемкнув like <-> dislike
            $up = $db->prepare("UPDATE video_likes SET reaction=?, created_at=NOW() WHERE id=? LIMIT 1");
            $up->execute([$reaction, (int)$exist['id']]);
            $status = 'updated';
        }
    } else {
        // поставити нову
        $ins = $db->prepare("INSERT INTO video_likes (video_id, user_id, reaction, created_at) VALUES (?, ?, ?, NOW())");
        $ins->execute([$videoId, $userId, $reaction]);
        $status = 'inserted';
    }

    $counts = getVideoLikeCounts($videoId);

    return [
        'success' => true,
        'status' => $status,
        'likes' => $counts['likes'],
        'dislikes' => $counts['dislikes'],
    ];
}




function isVideoFavorite(int $videoId, int $userId): bool {
    if ($videoId < 1 || $userId < 1) {
        return false;
    }

    $st = db()->prepare("SELECT 1 FROM video_favorites WHERE video_id = ? AND user_id = ? LIMIT 1");
    $st->execute([$videoId, $userId]);
    return (bool)$st->fetchColumn();
}

function getVideoFavoritesCount(int $videoId): int {
    if ($videoId < 1) {
        return 0;
    }

    $st = db()->prepare("SELECT COUNT(*) FROM video_favorites WHERE video_id = ?");
    $st->execute([$videoId]);
    return (int)$st->fetchColumn();
}

function toggleVideoFavorite(int $videoId, int $userId): array {
    if ($videoId < 1 || $userId < 1) {
        return ['success' => false, 'message' => 'Bad params'];
    }

    $db = db();

    $st = $db->prepare("SELECT 1 FROM video_favorites WHERE video_id = ? AND user_id = ? LIMIT 1");
    $st->execute([$videoId, $userId]);
    $exists = (bool)$st->fetchColumn();

    if ($exists) {
        $del = $db->prepare("DELETE FROM video_favorites WHERE video_id = ? AND user_id = ? LIMIT 1");
        $del->execute([$videoId, $userId]);
        $status = 'removed';
        $isFavorite = false;
    } else {
        $ins = $db->prepare("INSERT INTO video_favorites (video_id, user_id) VALUES (?, ?)");
        $ins->execute([$videoId, $userId]);
        $status = 'added';
        $isFavorite = true;
    }

    return [
        'success' => true,
        'status' => $status,
        'is_favorite' => $isFavorite,
        'favorites_count' => getVideoFavoritesCount($videoId),
    ];
}

function getUserFavoriteVideos(int $userId, int $limit = 36, int $offset = 0): array {
    if ($userId < 1) {
        return [];
    }

    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $st = db()->prepare("
        SELECT v.*
        FROM video_favorites vf
        JOIN videos v ON v.id = vf.video_id
        WHERE vf.user_id = ?
          AND v.status = 1
        ORDER BY vf.video_id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// Порахувати перегляди
function getVideoViewsCount(int $videoId): int {
    $db = db();

    // Основне джерело — агреговане поле videos.views (якщо є в схемі)
    if (tableHasColumn('videos', 'views')) {
        $st = $db->prepare("SELECT views FROM videos WHERE id = ? LIMIT 1");
        $st->execute([$videoId]);
        $views = $st->fetchColumn();

        if ($views !== false && $views !== null) {
            return max(0, (int)$views);
        }
    }

    // Fallback для старих/інших схем
    $st = $db->prepare("SELECT COUNT(*) FROM video_views WHERE video_id = ?");
    $st->execute([$videoId]);
    return (int)$st->fetchColumn();
}

// Додати перегляд (унікальний по user_id або ip)
function addVideoView(int $videoId): void {
    $db = db();

    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $userId = getCurrentUserId();
    $isNewView = false;

    if ($userId > 0) {
        // Перевірка для залогіненого
        $st = $db->prepare("SELECT id FROM video_views WHERE video_id = ? AND user_id = ? LIMIT 1");
        $st->execute([$videoId, $userId]);

        if (!$st->fetchColumn()) {
            $ins = $db->prepare("
                INSERT INTO video_views (video_id, user_id, ip, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $ins->execute([$videoId, $userId, $ip !== '' ? $ip : null]);
            $isNewView = true;
        }

    } else {
        // Гість — перевірка по IP
        if ($ip === '') {
            return;
        }

        $st = $db->prepare("SELECT id FROM video_views WHERE video_id = ? AND ip = ? LIMIT 1");
        $st->execute([$videoId, $ip]);

        if (!$st->fetchColumn()) {
            $ins = $db->prepare("
                INSERT INTO video_views (video_id, ip, created_at)
                VALUES (?, ?, NOW())
            ");
            $ins->execute([$videoId, $ip]);
            $isNewView = true;
        }
    }

    // Оновлюємо агреговане поле тільки для нового перегляду (якщо колонка існує)
    if ($isNewView && tableHasColumn('videos', 'views')) {
        $up = $db->prepare("UPDATE videos SET views = views + 1 WHERE id = ? LIMIT 1");
        $up->execute([$videoId]);
    }
}







