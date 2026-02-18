<div class="container profile-v2-page">
    <div class="profile-v2-layout">
        <aside class="profile-v2-sidebar">
            <div class="profile-v2-avatar-wrap">
                <div class="profile-v2-avatar"><?= strtoupper(mb_substr($profileLogin, 0, 1)) ?></div>
                <h1><?= htmlspecialchars($profileLogin) ?></h1>
                <p><?= htmlspecialchars($profileEmail !== '' ? $profileEmail : '@user') ?></p>
            </div>

            <nav class="profile-v2-menu">
                <a href="/profile" class="active">Профіль</a>
                <a href="/favorites">Моє улюблене</a>
                <a href="/comments">Коментарі</a>
                <a href="/settings">Налаштування</a>
            </nav>

            <div class="profile-v2-mini-stats">
                <div><span>Відео</span><strong><?= (int)$profileOverview['videos_count'] ?></strong></div>
                <div><span>Коментарі</span><strong><?= (int)$profileOverview['comments_count'] ?></strong></div>
                <div><span>Лайки</span><strong><?= (int)$profileOverview['reactions_count'] ?></strong></div>
            </div>
        </aside>

        <section class="profile-v2-main">
            <header class="profile-v2-header">
                <h2>Профіль користувача</h2>
                <p>Останні коментарі користувача.</p>
            </header>

            <div class="profile-v2-stats-row">
                <article><span>Улюблене</span><strong><?= (int)$profileOverview['favorites_count'] ?></strong></article>
                <article><span>Коментарі</span><strong><?= (int)$profileOverview['comments_count'] ?></strong></article>
                <article><span>Реакції</span><strong><?= (int)$profileOverview['reactions_count'] ?></strong></article>
            </div>

            <div class="profile-v2-comments-list">
                <?php if (!empty($recentComments)): ?>
                    <?php foreach ($recentComments as $comment): ?>
                        <article class="profile-v2-comment-item">
                            <div class="profile-v2-comment-head">
                                <strong>Коментар до відео:</strong>
                                <?php if (!empty($comment['video_slug'])): ?>
                                    <a href="/video/<?= htmlspecialchars((string)$comment['video_slug']) ?>">
                                        <?= htmlspecialchars((string)($comment['video_title'] ?? ('Відео #' . (int)$comment['video_id']))) ?>
                                    </a>
                                <?php else: ?>
                                    <span><?= htmlspecialchars((string)($comment['video_title'] ?? ('Відео #' . (int)$comment['video_id']))) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="profile-v2-comment-text"><?= renderCommentMessage((string)$comment['message']) ?></p>
                            <div class="profile-v2-comment-time"><?= htmlspecialchars((string)$comment['created_at']) ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="profile-v2-empty">У вас ще немає коментарів.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
