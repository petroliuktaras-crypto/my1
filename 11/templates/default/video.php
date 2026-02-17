<?php
// $video
// $actors
// $studios
// $collections
// $comments
// $recommended

?>

<div class="video-page">

    <!-- ================= LEFT COLUMN ================= -->
    <div class="video-main">



        <!-- PLAYER -->
        <div class="video-player">
            <iframe
                src="/player.php?token=<?= urlencode($token) ?>"
                frameborder="0"
                allowfullscreen
                width="100%"
                height="100%">
            </iframe>
        </div>

        <!-- ===== UNDER PLAYER ===== -->
<div class="video-meta-block">

    <!-- ===== ВЕРХ ===== -->
    <div class="video-meta-top">

<div class="meta-like-dislike" data-video-id="<?= (int)$video['id'] ?>">
    <button type="button" class="like-btn<?= ($userReaction === 'like') ? ' active' : '' ?>" data-action="like" aria-label="Поставити лайк">
        <i class="fas fa-thumbs-up like-icon"></i>
        <span class="like-count"><?= (int)$likeCount ?></span>
    </button>

    <button type="button" class="dislike-btn<?= ($userReaction === 'dislike') ? ' active' : '' ?>" data-action="dislike" aria-label="Поставити дізлайк">
        <i class="fas fa-thumbs-down dislike-icon"></i>
        <span class="dislike-count"><?= (int)$dislikeCount ?></span>
    </button>

    <div class="status-message">
        Загальний рейтинг
        <b>(<span class="rating-ten"><?= number_format((float)$ratingTen, 1, '.', '') ?></span>/10)</b>
    </div>
</div>


        <div class="meta-info">
            <div class="meta-item meta-uploaded-time">
                <svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1zm12 8H5v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9zM6 7a1 1 0 0 0-1 1h14a1 1 0 0 0-1-1H6z"/></svg>
                <span><?= htmlspecialchars($uploadedAgo) ?></span>
            </div>
            <div class="meta-item meta-stat-box meta-views-box">
                <svg viewBox="0 0 24 24"><path d="M12 5c-5.5 0-9.5 5-9.5 7s4 7 9.5 7 9.5-5 9.5-7-4-7-9.5-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>
                <span><?= (int)$viewsCount ?></span>
            </div>
            <div class="meta-item meta-stat-box meta-duration-box">
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 11h-5V11h3V6h2z"/></svg>
                <span><?= htmlspecialchars($durationFormatted) ?></span>
            </div>
            <div class="meta-item">
                <button type="button"
                        class="favorite-toggle<?= !empty($isFavorite) ? ' active' : '' ?>"
                        data-video-id="<?= (int)$video['id'] ?>"
                        aria-label="<?= !empty($isFavorite) ? 'Прибрати з улюбленого' : 'Додати в улюблене' ?>">
                    <span class="favorite-icon favorite-icon-plus" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09A5.98 5.98 0 0 1 16.5 3C19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#f87378" stroke="#201d24" stroke-width="1.2"/><circle cx="18.2" cy="17.8" r="4.8" fill="#000" stroke="#201d24" stroke-width="1.2"/><path d="M18.2 15.7v4.2M16.1 17.8h4.2" stroke="#201d24" stroke-width="1.3" stroke-linecap="round"/></svg>
                    </span>
                    <span class="favorite-icon favorite-icon-minus" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09A5.98 5.98 0 0 1 16.5 3C19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#f87378" stroke="#201d24" stroke-width="1.2"/><circle cx="18.2" cy="17.8" r="4.8" fill="#000" stroke="#201d24" stroke-width="1.2"/><path d="M16.2 17.8h4" stroke="#201d24" stroke-width="1.3" stroke-linecap="round"/></svg>
                    </span>
                    <span class="favorite-count"><?= (int)$favoritesCount ?></span>
                </button>
            </div>
        </div>

    </div>

    <!-- ===== НИЗ ===== -->
<div class="video-meta-bottom">

    <!-- РЯДОК -->
    <div class="meta-row">

<!-- Перевіряємо, чи є категорія -->
<?php if (!empty($category)): ?>
    <div class="meta-categories">
        <strong>Категорія:</strong>
        <!-- Виводимо категорію як посилання -->
        <a href="/category/<?= htmlspecialchars($category['slug']) ?>" class="meta-cat">
            <?= htmlspecialchars($category['title']) ?>
        </a>
    </div>
<?php else: ?>
    <!-- Якщо категорії немає, виводимо текст -->
    <div class="meta-categories">
        <strong>Категорія:</strong>
        <span>Категорія не вказана</span>
    </div>
<?php endif; ?>

        <div class="meta-actions">
            <div class="meta-btn" data-action="screens">Скріншоти</div>
            <div class="meta-btn" data-action="share">Поділитися</div>
            <div class="meta-btn" data-action="report">Поскаржитись</div>
        </div>

    </div>

    <!-- ↓↓↓ ОСЬ ТУТ ↓↓↓ -->
    <!-- БЛОК, ЯКИЙ РОСТЕ ВНИЗ -->
    <div class="meta-expand">

<div class="meta-expand-item" data-content="screens">
    <?php if (!empty($screenshots)): ?>
        <div class="screens-grid">
            <?php foreach ($screenshots as $img): ?>
                <img src="/uploads/screenshots/<?= htmlspecialchars($img) ?>" alt="">
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty">Скріншоти відсутні</div>
    <?php endif; ?>
</div>


<div class="meta-expand-item" data-content="share">

    <div class="share-field">
        <label>Посилання на відео</label>
        <input type="text" class="share-url" readonly
               value="<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    </div>

    <div class="share-field">
        <label>BBCode</label>
        <input type="text" class="share-bbcode" readonly
               value="[url=<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>]<?= htmlspecialchars($video['title']) ?>[/url]">
    </div>

</div>


<div class="meta-expand-item" data-content="report">
<form class="report-form">
    <div class="form-group">
        <label for="report-reason">Оберіть причину</label>
        <select id="report-reason" class="report-reason">
            <option value="">Оберіть причину</option>
            <option value="Неправильний контент">Неправильний контент</option>
            <option value="Порушення правил">Порушення правил</option>
            <option value="Інші причини">Інші причини</option>
        </select>
    </div>

    <div class="form-group">
        <label for="report-message">Ваше повідомлення</label>
        <textarea id="report-message" class="report-message" placeholder="Опишіть проблему..."></textarea>
    </div>

    <button type="button" class="send-report">Надіслати</button>
</form>

</div>


    </div>

</div>


</div>



        <!-- ===== TITLE + DESC ===== -->
        <div class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
            <div class="video-desc">
                <?= nl2br(htmlspecialchars($video['description'])) ?>
            </div>
        </div>

        <!-- ===== COMMENTS ===== -->
        <div class="comments">

            <h3>Залиште коментарій:</h3>

            <?php if (!empty($currentUserId)): ?>
                <form class="comment-form js-comment-form" data-video-id="<?= (int)$video['id'] ?>" method="post" action="/ajax/video-comment">
                    <input type="text"
                           name="nickname"
                           placeholder="Введіть нікнейм.."
                           value="<?= htmlspecialchars($currentUserLogin) ?>"
                           readonly
                           required>

                    <textarea name="message" placeholder="Ваш коментар" required></textarea>
                    <div class="comment-tools">
                        <button type="button" class="emoji-toggle" data-emoji-toggle>😊 Смайли</button>
                        <div class="emoji-panel" data-emoji-panel>
                            <button type="button" class="emoji-item" data-emoji>😀</button>
                            <button type="button" class="emoji-item" data-emoji>😍</button>
                            <button type="button" class="emoji-item" data-emoji>🔥</button>
                            <button type="button" class="emoji-item" data-emoji>😂</button>
                            <button type="button" class="emoji-item" data-emoji>😎</button>
                            <button type="button" class="emoji-item" data-emoji>👍</button>
                            <button type="button" class="emoji-item" data-emoji>❤️</button>
                            <button type="button" class="emoji-item" data-emoji>👏</button>
                        </div>
                    </div>
                    <button type="submit">Відправити</button>
                    <div class="comment-form-status is-info" aria-live="polite">Коментар публікується після перевірки модератором або адміністратором.</div>
                </form>
            <?php else: ?>
                <div class="comment-auth-required">
                    <p class="comment-auth-title">Коментарі доступні лише після входу в акаунт.</p>
                    <p class="comment-auth-subtitle">Увійдіть або зареєструйтесь — і зможете залишити коментар до цього відео.</p>
                    <button type="button" class="comment-auth-btn js-open-auth-modal">Увійти, щоб коментувати</button>
                </div>
            <?php endif; ?>

            <div class="comments-list" data-comments-list>
                <?php foreach ($comments as $idx => $c): ?>
                    <div class="comment<?= $idx >= 10 ? ' is-comment-hidden' : '' ?>" data-comment-item>
                        <img class="avatar" src="<?= $c['avatar'] ?: '/uploads/avatars/default.png' ?>">

                        <div class="comment-body">
                            <div class="comment-head">
                                <span class="username"><?= htmlspecialchars($c['login']) ?></span>
                                <span class="arrow">▼</span>
                            </div>

                            <div class="comment-text">
                                <?= renderCommentMessage((string)$c['message']) ?>
                            </div>

                            <div class="comment-footer">
                                <span class="time"><?= $c['created_at'] ?></span>
                                <button type="button" class="comment-reply-btn" data-comment-reply data-comment-id="<?= (int)$c['id'] ?>">Відповісти</button>
                            </div>

                            <div class="comment-reply-box" data-comment-reply-box>
                                <form class="comment-reply-form js-comment-reply-form" data-video-id="<?= (int)$video['id'] ?>" data-parent-id="<?= (int)$c['id'] ?>" method="post" action="/ajax/video-comment">
                                    <div class="comment-tools comment-tools-reply">
                                        <textarea class="comment-reply-input" name="message" placeholder="Ваша відповідь" required></textarea>
                                        <button type="button" class="emoji-corner-btn" data-emoji-toggle aria-label="Смайлики">☺</button>
                                        <button type="submit" class="reply-send-btn" aria-label="Відправити відповідь">➤</button>
                                        <div class="emoji-panel" data-emoji-panel>
                                            <button type="button" class="emoji-item" data-emoji>😀</button>
                                            <button type="button" class="emoji-item" data-emoji>😍</button>
                                            <button type="button" class="emoji-item" data-emoji>🔥</button>
                                            <button type="button" class="emoji-item" data-emoji>😂</button>
                                            <button type="button" class="emoji-item" data-emoji>😎</button>
                                            <button type="button" class="emoji-item" data-emoji>👍</button>
                                            <button type="button" class="emoji-item" data-emoji>❤️</button>
                                            <button type="button" class="emoji-item" data-emoji>👏</button>
                                        </div>
                                    </div>
                                    <div class="comment-form-status" aria-live="polite"></div>
                                </form>
                            </div>

                            <?php $replies = getCommentReplies((int)$c['id']); ?>
                            <?php if (!empty($replies)): ?>
                                <div class="comment-replies">
                                    <?php foreach ($replies as $r): ?>
                                        <div class="comment-reply-item">
                                            <div class="comment-reply-head">
                                                <span class="username"><?= htmlspecialchars($r['login']) ?></span>
                                                <span class="time"><?= htmlspecialchars($r['created_at']) ?></span>
                                            </div>
                                            <div class="comment-text"><?= renderCommentMessage((string)$r['message']) ?></div>
                                            <button type="button" class="comment-like comment-like-btn comment-like-reply" data-comment-like data-comment-id="<?= (int)$r['id'] ?>">
                                                <span data-comment-like-count><?= (int)$r['likes'] ?></span> <span>❤️</span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="comment-like comment-like-btn" data-comment-like data-comment-id="<?= (int)$c['id'] ?>">
                            <span data-comment-like-count><?= (int)$c['likes'] ?></span> <span>❤️</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($comments) > 10): ?>
                <div class="comments-more-wrap">
                    <button type="button" class="comments-more-btn" data-comments-more data-step="10">Показати ще 10</button>
                </div>
            <?php endif; ?>

        </div>

        

        <!-- ===== RECOMMENDED ===== -->
        <?php if (!empty($recommended)): ?>
        <div class="video-related">
            <h2>Рекомендовані відео</h2>

            <div class="cards">
                <?php foreach ($recommended as $v): ?>
                    <div class="card">

        <!-- POSTER -->
        <div class="card-thumb" data-preview="<?= $v['preview'] ?>">
            <div class="preview-progress"></div>

            <img src="<?= $v['poster'] ?>" alt="">
            <div class="badge">Новые</div>

            <div class="card-overlay">
                <span>⏱ <?= gmdate('i:s', $v['duration']) ?></span>
                <span>👁 <?= (int)$v['views'] ?></span>
                <span>👍 85%</span>
            </div>
        </div>

                        <div class="card-title">
                            <a href="/video/<?= htmlspecialchars($v['slug']) ?>">
                                <?= htmlspecialchars($v['title']) ?>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ================= RIGHT SIDEBAR ================= -->
    <aside class="video-sidebar">

        <!-- ACTORS -->
        <?php if (!empty($actors)): ?>
        <div class="sidebar-box actors-box">
		<h3>Актори</h3>
            <?php foreach ($actors as $actor): ?>
                <a href="/actor/<?= htmlspecialchars($actor['slug']) ?>" class="actor-item">
                    <img src="<?= htmlspecialchars($actor['photo']) ?>">
                    <div class="actor-name"><?= htmlspecialchars($actor['name']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- STUDIOS -->
        <?php if (!empty($studios)): ?>
        <div class="sidebar-box box-null">
            <h3>Студії</h3>

            <?php foreach ($studios as $studio): ?>
                <a href="/studio/<?= htmlspecialchars($studio['slug']) ?>" class="studio-item">
                    <div class="studio-thumb">
                        <img src="<?= htmlspecialchars($studio['poster']) ?>">
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- COLLECTIONS -->
        <?php if (!empty($collections)): ?>
        <div class="sidebar-box">
            <h3>Колекції</h3>

            <div class="collections-tags">
    <?php foreach ($collections as $col): ?>
        <a href="/collection/<?= htmlspecialchars($col['slug']) ?>" class="collection-tag">
            #<?= htmlspecialchars($col['title']) ?>
        </a>
    <?php endforeach; ?>
</div>
        </div>
        <?php endif; ?>
		
		
		
<?php if (!empty($videosWithActors)): ?>
    <div class="sidebar-box box-null">

         <h3>Відео з акторами</h3>

        <div class="cards cards-one-column">
            <?php foreach ($videosWithActors as $v): ?>
                <div class="card">

                    <div class="card-thumb" data-preview="<?=($v['preview']) ?>">
                        <div class="preview-progress"></div>

                        <img src="<?= htmlspecialchars($v['poster']) ?>" alt="<?= htmlspecialchars($v['title']) ?>">
                        <div class="badge">Новые</div>

                        <div class="card-overlay">
                            <span>⏱ <?= gmdate('i:s', (int)$v['duration']) ?></span>
                            <span>👁 <?= (int)$v['views'] ?></span>
                            <span>👍 85%</span>
                        </div>
                    </div>

                    <div class="card-title">
                        <a href="/video/<?= htmlspecialchars($v['slug']) ?>">
                            <?= htmlspecialchars($v['title']) ?>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
<?php endif; ?>

		
		

    </aside>

</div>
<div id="lightbox" class="lightbox">
    <span class="lightbox-close">×</span>

    <span class="lightbox-nav prev">‹</span>
    <span class="lightbox-nav next">›</span>

    <img class="lightbox-img" src="" alt="">
</div>

