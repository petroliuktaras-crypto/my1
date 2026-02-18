<div class="container" style="padding: 24px 0;">
    <h1>Моє улюблене</h1>

    <?php if (empty($favorites)): ?>
        <p>У вас ще немає улюблених відео.</p>
    <?php else: ?>
        <div class="cards">
            <?php foreach ($favorites as $item): ?>
                <article class="card">
                    <a href="/video/<?= htmlspecialchars($item['slug']) ?>" class="card-thumb">
                        <img src="/uploads/posters/<?= htmlspecialchars($item['poster']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    </a>
                    <h3 class="card-title">
                        <a href="/video/<?= htmlspecialchars($item['slug']) ?>"><?= htmlspecialchars($item['title']) ?></a>
                    </h3>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
