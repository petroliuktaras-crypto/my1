document.addEventListener('DOMContentLoaded', function () {

  // ================== GENDER FILTER ==================
  const genderSelect = document.getElementById('genderSelect');
  if (genderSelect) {
    genderSelect.addEventListener('change', function () {
      const gender = this.value;
      const basePath = window.location.pathname;

      if (gender) window.location.href = basePath + '?gender=' + gender;
      else window.location.href = basePath;
    });
  }

  // ================== VIDEO PREVIEW (hover, delay, JS video create/remove) ==================
  let previewVideo = null;
  let timer = null;
  const DELAY = 1200;

  document.addEventListener('mouseover', (e) => {
    const card = e.target.closest('.card-thumb[data-preview]');
    if (!card) return;
    if (timer || previewVideo) return;

    const progress = card.querySelector('.preview-progress');
    if (!progress) return;

    progress.style.display = 'block';
    progress.style.transition = 'none';
    progress.style.width = '0%';
    progress.offsetHeight;

    progress.style.transition = `width ${DELAY}ms ease-in-out`;
    progress.style.width = '100%';

    timer = setTimeout(() => {
      previewVideo = document.createElement('video');
      previewVideo.src = card.dataset.preview;
      previewVideo.muted = true;
      previewVideo.loop = true;
      previewVideo.autoplay = true;
      previewVideo.playsInline = true;

      previewVideo.style.position = 'absolute';
      previewVideo.style.inset = '0';
      previewVideo.style.width = '100%';
      previewVideo.style.height = '100%';
      previewVideo.style.objectFit = 'cover';

      previewVideo.addEventListener('playing', () => {
        progress.style.display = 'none';
      }, { once: true });

      card.appendChild(previewVideo);
      previewVideo.play().catch(() => {});
    }, DELAY);
  });

  document.addEventListener('mouseout', (e) => {
    const card = e.target.closest('.card-thumb');
    if (!card) return;

    clearTimeout(timer);
    timer = null;

    const progress = card.querySelector('.preview-progress');
    if (progress) {
      progress.style.display = 'none';
      progress.style.width = '0%';
    }

    if (previewVideo) {
      previewVideo.pause();
      previewVideo.remove();
      previewVideo = null;
    }
  });

  // ================== PASSWORD TOGGLE ==================
  function togglePasswordByInput(inputEl) {
    if (!inputEl) return;

    inputEl.type = (inputEl.type === 'password') ? 'text' : 'password';

    const eyeIcon = inputEl.nextElementSibling;
    if (eyeIcon) eyeIcon.classList.toggle('active');
  }

  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
      const inputEl = this.previousElementSibling;
      togglePasswordByInput(inputEl);
    });
  });

  // ================== META BUTTON TOGGLE ==================
  document.querySelectorAll('.meta-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.action;
      const target = document.querySelector('.meta-expand-item[data-content="' + action + '"]');
      if (!target) return;

      const isActive = target.classList.contains('active');

      document.querySelectorAll('.meta-expand-item').forEach(item => item.classList.remove('active'));
      document.querySelectorAll('.meta-btn').forEach(button => button.classList.remove('active'));

      if (!isActive) {
        target.classList.add('active');
        btn.classList.add('active');
      }
    });
  });

  // ================== SCREENSHOTS LIGHTBOX + NAVIGATION ==================
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.querySelector('.lightbox-img');
  const lightboxClose = document.querySelector('.lightbox-close');
  const prevBtn = document.querySelector('.lightbox-nav.prev');
  const nextBtn = document.querySelector('.lightbox-nav.next');

  let screens = [];
  let currentIndex = 0;

  function showScreen(index) {
    if (!screens.length || !lightboxImg) return;

    if (index < 0) index = screens.length - 1;
    if (index >= screens.length) index = 0;

    currentIndex = index;
    lightboxImg.src = screens[currentIndex].src;
  }

  if (lightbox && lightboxImg) {
    // Відкриття (ОДИН обробник, без дубля)
    document.addEventListener('click', function (e) {
      const img = e.target.closest('.screens-grid img');
      if (!img) return;

      screens = Array.from(document.querySelectorAll('.screens-grid img'));
      currentIndex = screens.indexOf(img);
      if (currentIndex === -1) return;

      lightboxImg.src = screens[currentIndex].src;
      lightbox.classList.add('active');
    });

    // Закриття по ✕
    if (lightboxClose) {
      lightboxClose.addEventListener('click', function () {
        lightbox.classList.remove('active');
        lightboxImg.src = '';
      });
    }

    // Закриття по кліку на фон
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) {
        lightbox.classList.remove('active');
        lightboxImg.src = '';
      }
    });

    // Prev / Next кнопки (з перевірками)
    if (prevBtn) prevBtn.addEventListener('click', () => showScreen(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => showScreen(currentIndex + 1));

    // Клавіатура (ESC + стрілки)
    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('active')) return;

      if (e.key === 'Escape') {
        lightbox.classList.remove('active');
        lightboxImg.src = '';
      }
      if (e.key === 'ArrowLeft') showScreen(currentIndex - 1);
      if (e.key === 'ArrowRight') showScreen(currentIndex + 1);
    });
  }

  // ================== SHARE (select input text) ==================
  document.addEventListener('click', function (e) {
    const input = e.target.closest('.share-url, .share-bbcode');
    if (!input) return;

    input.select();
    input.setSelectionRange(0, 99999);
  });








document.addEventListener('click', function (e) {
  const btn = e.target.closest('.like-btn, .dislike-btn');
  if (!btn) return;

  const wrap = btn.closest('.meta-like-dislike');
  if (!wrap || wrap.dataset.loading === '1') return;

  const videoId = wrap.getAttribute('data-video-id');
  const reaction = btn.getAttribute('data-action'); // like | dislike

  const formData = new FormData();
  formData.append('video_id', videoId);
  formData.append('reaction', reaction);

  wrap.dataset.loading = '1';

  fetch('/ajax/video-reaction', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        if (data.message === 'Потрібно увійти') {
          openAuthModal();
        }
        return;
      }

      const likeEl = wrap.querySelector('.like-count');
      const dislikeEl = wrap.querySelector('.dislike-count');
      const ratingTenEl = wrap.querySelector('.rating-ten');

      const likes = Number(data.likes) || 0;
      const dislikes = Number(data.dislikes) || 0;
      const total = likes + dislikes;
      const ratingTen = total > 0 ? ((likes / total) * 10) : 0;

      if (likeEl) likeEl.textContent = likes;
      if (dislikeEl) dislikeEl.textContent = dislikes;
      if (ratingTenEl) ratingTenEl.textContent = ratingTen.toFixed(1);

      const activeReaction = data.user_reaction || '';
      wrap.querySelectorAll('.like-btn, .dislike-btn').forEach((x) => {
        x.classList.toggle('active', x.getAttribute('data-action') === activeReaction);
      });
    })
    .catch(() => {})
    .finally(() => {
      delete wrap.dataset.loading;
    });
});









document.addEventListener('click', function (e) {
  const favoriteBtn = e.target.closest('.favorite-toggle');
  if (!favoriteBtn) return;

  if (favoriteBtn.dataset.loading === '1') return;

  const videoId = favoriteBtn.getAttribute('data-video-id');
  if (!videoId) return;

  const formData = new FormData();
  formData.append('video_id', videoId);

  favoriteBtn.dataset.loading = '1';

  fetch('/ajax/video-favorite', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        if (data.message === 'Потрібно увійти') {
          openAuthModal();
        }
        return;
      }

      favoriteBtn.classList.toggle('active', !!data.is_favorite);

      const countEl = favoriteBtn.querySelector('.favorite-count');
      favoriteBtn.setAttribute('aria-label', data.is_favorite ? 'Прибрати з улюбленого' : 'Додати в улюблене');
      if (countEl) {
        countEl.textContent = Number(data.favorites_count) || 0;
      }
    })
    .catch(() => {})
    .finally(() => {
      delete favoriteBtn.dataset.loading;
    });
});


async function postComment(form, formData) {
  const url = form.getAttribute('action') || '/ajax/video-comment';

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: formData
    });

    const type = response.headers.get('content-type') || '';
    if (!type.includes('application/json')) {
      return { ok: false, data: { success: false, message: 'Невірна відповідь сервера' } };
    }

    const data = await response.json();
    return { ok: response.ok, data };
  } catch (_) {
    return { ok: false, data: { success: false, message: 'Помилка мережі' } };
  }
}

async function submitCommentForm(form, parentId = 0) {
  if (!form || form.dataset.loading === '1') return;

  const videoId = form.getAttribute('data-video-id');
  const nicknameEl = form.querySelector('[name="nickname"]');
  const messageEl = form.querySelector('[name="message"]');
  const statusEl = form.querySelector('.comment-form-status');
  const submitBtn = form.querySelector('button[type="submit"]');

  const formData = new FormData();
  formData.append('video_id', videoId || '0');
  formData.append('nickname', nicknameEl ? nicknameEl.value : '');
  formData.append('message', messageEl ? messageEl.value : '');
  if (parentId > 0) {
    formData.append('parent_id', String(parentId));
  }

  form.dataset.loading = '1';
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');
  }
  if (statusEl) {
    statusEl.textContent = 'Відправка...';
    statusEl.className = 'comment-form-status is-pending';
  }

  const result = await postComment(form, formData);
  const data = result.data || {};

  if (data.auth_required) {
    openAuthModal();
  }

  if (!data.success) {
    if (statusEl) {
      statusEl.textContent = data.message || 'Не вдалося відправити коментар';
      statusEl.className = 'comment-form-status is-error';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.classList.remove('is-loading');
    }
    delete form.dataset.loading;
    return;
  }

  if (messageEl) messageEl.value = '';
  if (statusEl) {
    statusEl.textContent = data.message || 'Коментар відправлено на модерацію';
    statusEl.className = 'comment-form-status is-success';
  }

  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.classList.remove('is-loading');
  }
  delete form.dataset.loading;
}

document.addEventListener('submit', async function (e) {
  const mainForm = e.target.closest('.js-comment-form');
  if (mainForm) {
    e.preventDefault();
    await submitCommentForm(mainForm, 0);
    return;
  }

  const replyForm = e.target.closest('.js-comment-reply-form');
  if (replyForm) {
    e.preventDefault();
    const parentId = Number(replyForm.getAttribute('data-parent-id') || '0');
    await submitCommentForm(replyForm, parentId);
  }
});

function openAuthModal() {
  const m = document.getElementById('authModal');
  if (!m) return;
  m.classList.add('active');
  document.body.classList.add('modal-open');
}

function closeAuthModal() {
  const m = document.getElementById('authModal');
  if (!m) return;
  m.classList.remove('active');
  document.body.classList.remove('modal-open');
}

document.addEventListener('click', (e) => {
  if (e.target.closest('[data-auth-close]')) closeAuthModal();
});

document.addEventListener('click', (e) => {
  if (e.target.closest('.js-open-auth-modal')) openAuthModal();
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeAuthModal();
});

document.addEventListener('click', (e) => {
  const replyBtn = e.target.closest('[data-comment-reply]');
  if (replyBtn) {
    const comment = replyBtn.closest('[data-comment-item]');
    if (!comment) return;
    const box = comment.querySelector('[data-comment-reply-box]');
    if (!box) return;
    box.classList.toggle('active');
    if (box.classList.contains('active')) {
      const textarea = box.querySelector('textarea[name="message"]');
      if (textarea) textarea.focus();
    }
    return;
  }

  const emojiToggle = e.target.closest('[data-emoji-toggle]');
  if (emojiToggle) {
    const tools = emojiToggle.closest('.comment-tools');
    if (!tools) return;
    const panel = tools.querySelector('[data-emoji-panel]');
    if (!panel) return;
    panel.classList.toggle('active');
    return;
  }

  const emojiBtn = e.target.closest('[data-emoji]');
  if (emojiBtn) {
    const tools = emojiBtn.closest('.comment-tools');
    if (!tools) return;
    const form = tools.closest('form');
    if (!form) return;
    const textarea = form.querySelector('textarea[name="message"]');
    if (!textarea) return;
    const emoji = emojiBtn.textContent || '';
    textarea.value = (textarea.value || '') + emoji;
    textarea.focus();
    return;
  }

  const likeBtn = e.target.closest('[data-comment-like]');
  if (likeBtn) {
    if (likeBtn.dataset.loading === '1') return;
    likeBtn.dataset.loading = '1';
    const commentId = Number(likeBtn.getAttribute('data-comment-id') || '0');

    const fd = new FormData();
    fd.append('comment_id', String(commentId));

    fetch('/ajax/comment-like', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: fd
    })
      .then(async (res) => {
        const data = await res.json().catch(() => ({}));
        if (data.auth_required) {
          openAuthModal();
          return;
        }
        if (!data.success) return;

        const countEl = likeBtn.querySelector('[data-comment-like-count]');
        if (countEl) countEl.textContent = String(Number(data.likes) || 0);
        likeBtn.classList.toggle('active', !!data.liked);
      })
      .catch(() => {})
      .finally(() => {
        delete likeBtn.dataset.loading;
      });
    return;
  }

  if (!e.target.closest('.comment-tools')) {
    document.querySelectorAll('[data-emoji-panel].active').forEach((panel) => {
      panel.classList.remove('active');
    });
  }
});

const commentsMoreBtn = document.querySelector('[data-comments-more]');
if (commentsMoreBtn) {
  commentsMoreBtn.addEventListener('click', () => {
    const step = Number(commentsMoreBtn.getAttribute('data-step') || '10') || 10;
    const hiddenItems = Array.from(document.querySelectorAll('[data-comment-item].is-comment-hidden'));

    hiddenItems.slice(0, step).forEach((item) => {
      item.classList.remove('is-comment-hidden');
    });

    const left = document.querySelectorAll('[data-comment-item].is-comment-hidden').length;
    if (left <= 0) {
      commentsMoreBtn.remove();
    }
  });
}

// ================== AUTOCOMPLETE SEARCH ==================
  const searchInput = document.getElementById('autocomplete');
  const suggestionsBox = document.getElementById('autocomplete-suggestions');
  let acController = null;

  if (searchInput && suggestionsBox) {
    searchInput.addEventListener('input', function () {
      const q = this.value.trim();

      if (q.length < 2) {
        suggestionsBox.style.display = 'none';
        suggestionsBox.innerHTML = '';
        return;
      }

      if (acController) acController.abort();
      acController = new AbortController();

      fetch('/search?ajax=true&query=' + encodeURIComponent(q), { signal: acController.signal })
        .then(r => r.json())
        .then(results => {
          suggestionsBox.innerHTML = '';

          if (Array.isArray(results) && results.length > 0) {
            suggestionsBox.style.display = 'block';

            const grouped = { "Відео": [], "Моделі": [], "Студії": [] };

            results.forEach(item => {
              if (item.type === 'video') grouped['Відео'].push(item);
              else if (item.type === 'actor') grouped['Моделі'].push(item);
              else if (item.type === 'studio') grouped['Студії'].push(item);
            });

            for (const group in grouped) {
              if (!grouped[group].length) continue;

              const title = document.createElement('div');
              title.classList.add('autocomplete-suggestion-title');
              title.textContent = group;
              suggestionsBox.appendChild(title);

              grouped[group].forEach(item => {
                const row = document.createElement('div');
                row.classList.add('autocomplete-suggestion');

                const link = document.createElement('a');
                link.href = item.url || '#';
                link.classList.add('link');

                const icon = item.icon ? item.icon : 'search';
                const name = item.name ? item.name : '';

                link.innerHTML = `<i class="fas fa-${icon}"></i>${name}`;

                row.appendChild(link);
                suggestionsBox.appendChild(row);
              });
            }
          } else {
            suggestionsBox.style.display = 'block';
            const no = document.createElement('div');
            no.classList.add('autocomplete-suggestion');
            no.textContent = 'Нічого не знайдено';
            suggestionsBox.appendChild(no);
          }
        })
        .catch(err => {
          if (err.name === 'AbortError') return;
          console.error('Error:', err);
          suggestionsBox.style.display = 'none';
          suggestionsBox.innerHTML = '';
        });
    });
  }

  // ================== REPORT FORM (AJAX) ==================
  const reportForm = document.querySelector('.report-form');
  const reportButton = document.querySelector('.send-report');

  if (reportForm && reportButton) {
    const successMessage = document.createElement('div');
    const errorMessage = document.createElement('div');

    successMessage.classList.add('report-message-success');
    errorMessage.classList.add('report-message-error');

    reportForm.appendChild(successMessage);
    reportForm.appendChild(errorMessage);

    reportButton.addEventListener('click', function () {
      const reasonEl = document.querySelector('.report-reason');
      const msgEl = document.querySelector('.report-message');

      const reason = reasonEl ? reasonEl.value : '';
      const message = msgEl ? msgEl.value : '';

      if (!reason || !message) {
        errorMessage.textContent = 'Будь ласка, виберіть причину та введіть текст.';
        errorMessage.style.display = 'block';
        successMessage.style.display = 'none';
        return;
      }

      const formData = new FormData();
      formData.append('reason', reason);
      formData.append('message', message);

      fetch('/ajax/submit_report.php', {
        method: 'POST',
        body: formData
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            successMessage.textContent = data.message;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';

            reportForm.reset();

            setTimeout(() => location.reload(), 2000);
          } else {
            errorMessage.textContent = data.message;
            errorMessage.style.display = 'block';
            successMessage.style.display = 'none';
          }
        })
        .catch(error => {
          console.error('Помилка при відправці запиту:', error);
          errorMessage.textContent = 'Сталася помилка при відправці скарги. Спробуйте ще раз.';
          errorMessage.style.display = 'block';
          successMessage.style.display = 'none';
        });
    });
  }

});
