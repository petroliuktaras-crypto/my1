<div class="container profile-v1-page">
    <section class="profile-v1-header-card">
        <div class="profile-v1-avatar"><?= strtoupper(mb_substr($profileLogin, 0, 1)) ?></div>
        <div class="profile-v1-user-meta">
            <h1><?= htmlspecialchars($profileLogin) ?></h1>
            <p><?= htmlspecialchars($profileEmail !== '' ? $profileEmail : '@user') ?></p>
        </div>
    </section>

    <section class="profile-v1-stats-grid">
        <article class="profile-v1-stat">
            <span class="profile-v1-stat-label">Відео</span>
            <strong><?= (int)$profileOverview['videos_count'] ?></strong>
        </article>
        <article class="profile-v1-stat">
            <span class="profile-v1-stat-label">Лайки / реакції</span>
            <strong><?= (int)$profileOverview['reactions_count'] ?></strong>
        </article>
        <article class="profile-v1-stat">
            <span class="profile-v1-stat-label">Коментарі</span>
            <strong><?= (int)$profileOverview['comments_count'] ?></strong>
        </article>
        <article class="profile-v1-stat">
            <span class="profile-v1-stat-label">Улюблене</span>
            <strong><?= (int)$profileOverview['favorites_count'] ?></strong>
        </article>
    </section>

    <section class="profile-v1-content-grid">
        <div class="profile-v1-activity-card">
            <h2>Остання активність</h2>
            <?php if (!empty($profileOverview['recent_activity'])): ?>
                <ul class="profile-v1-activity-list">
                    <?php foreach ($profileOverview['recent_activity'] as $item): ?>
                        <li>
                            <div class="profile-v1-dot" aria-hidden="true"></div>
                            <div>
                                <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a>
                                <p><?= htmlspecialchars($item['meta']) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="profile-v1-empty">Поки що немає активності.</p>
            <?php endif; ?>
        </div>

        <aside class="profile-v1-actions-card">
            <h2>Швидкі дії</h2>
            <div class="profile-v1-actions-list">
                <?php foreach ($profileOverview['quick_actions'] as $action): ?>
                    <a href="<?= htmlspecialchars($action['url']) ?>" class="profile-v1-action-btn">
                        <?= htmlspecialchars($action['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
    </section>
</div>
