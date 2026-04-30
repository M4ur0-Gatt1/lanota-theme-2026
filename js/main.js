document.addEventListener('DOMContentLoaded', function() {
    // Elements used across features
    const debateFeed = document.getElementById('debate-feed');
    // Removed aggressive cache-busting that caused hard reloads

// Featured News: stories-style progress bars and// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');
    
    if (themeToggle) {
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        // Update button text and icon based on current theme
        updateThemeButton(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButton(newTheme);
        });
    }
    
    function updateThemeButton(theme) {
        if (!themeIcon) return;
        const symbolId = (theme === 'dark') ? '#icon-sun' : '#icon-moon';
        // If themeIcon is an SVG, switch its <use>
        if (themeIcon.tagName && themeIcon.tagName.toLowerCase() === 'svg') {
            const use = themeIcon.querySelector('use');
            if (use) use.setAttribute('href', symbolId);
        } else {
            // Fallback for legacy <i> icons during transition
            themeIcon.className = (theme === 'dark') ? 'fas fa-sun' : 'fas fa-moon';
        }
        if (themeText) {
            themeText.textContent = (theme === 'dark') ? 'Modo Claro' : 'Modo Oscuro';
        }
    }

  const section = document.querySelector('.featured-news');
  const carousel = section ? section.querySelector('.featured-carousel') : null;
  if (!section || !carousel) return;

  const items = Array.from(carousel.querySelectorAll('.featured-story'));
  if (!items.length) return;

  // On desktop, don't render progress bars nor auto-advance logic
  const isDesktop = window.matchMedia && window.matchMedia('(min-width: 992px)').matches;
  if (isDesktop) {
    return;
  }

  // Create progress bars container
  let bars = section.querySelector('.featured-bars');
  if (!bars) {
    bars = document.createElement('div');
    bars.className = 'featured-bars';
    items.forEach(() => {
      const bar = document.createElement('div');
      bar.className = 'bar';
      const span = document.createElement('span');
      bar.appendChild(span);
      bars.appendChild(bar);
    });
    section.appendChild(bars);
  }

  const barSpans = Array.from(bars.querySelectorAll('.bar > span'));

  function getCurrentIndex() {
    const scrollLeft = carousel.scrollLeft;
    // Find item whose left is closest to scrollLeft
    let closestIdx = 0;
    let minDelta = Infinity;
    items.forEach((el, idx) => {
      const delta = Math.abs(el.offsetLeft - scrollLeft);
      if (delta < minDelta) { minDelta = delta; closestIdx = idx; }
    });
    return closestIdx;
  }

  function updateBars() {
    const scrollLeft = carousel.scrollLeft;
    const idx = getCurrentIndex();
    const currentEl = items[idx];
    const start = currentEl.offsetLeft;
    const width = currentEl.clientWidth;
    const progress = Math.max(0, Math.min(1, (scrollLeft - start) / (width || 1) * -1 + 1));

    barSpans.forEach((span, i) => {
      if (i < idx) span.style.width = '100%';
      else if (i > idx) span.style.width = '0%';
      else span.style.width = (progress * 100).toFixed(1) + '%';
    });
  }

  // Initial state
  updateBars();

  // Update on scroll with rAF throttle
  let ticking = false;
  carousel.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        updateBars();
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });

  // Click to advance to next item (gentle UX)
  let touchMoved = false; let touchStartY = 0; let touchStartX = 0; let touchStartScrollY = 0; let vScrolling = false;
  carousel.addEventListener('touchstart', (e) => {
    const t = e.changedTouches && e.changedTouches[0];
    if (t) {
      touchStartY = t.clientY;
      touchStartX = t.clientX;
      touchStartScrollY = (window.pageYOffset || document.documentElement.scrollTop || 0);
      touchMoved = false;
    }
  }, { passive: true });
  carousel.addEventListener('touchmove', (e) => {
    const t = e.changedTouches && e.changedTouches[0];
    if (t) {
      const dy = Math.abs(t.clientY - touchStartY);
      const dx = Math.abs(t.clientX - touchStartX);
      if (dy > 5) touchMoved = true; // treat as vertical scroll intent (more sensitive)
      // If clearly a vertical gesture (dy > dx) allow page to take over by disabling carousel hit-testing
      if (!vScrolling && dy > dx && dy > 5) {
        vScrolling = true;
        carousel.style.pointerEvents = 'none';
      }
    }
  }, { passive: true });
  // Re-enable hit-testing after the gesture ends and DO NOT reset touchMoved here
  // after touchend on mobile browsers. If we reset immediately, the click handler
  // would think it was a tap and wrongly advance the story after a vertical scroll.
  // touchMoved will be reset at the next touchstart.
  ['touchend','touchcancel'].forEach(ev => {
    carousel.addEventListener(ev, () => {
      if (vScrolling) {
        vScrolling = false;
        carousel.style.pointerEvents = '';
      }
      // leave touchMoved as-is; it resets at next touchstart
    }, { passive: true });
  });

  carousel.addEventListener('click', (e) => {
    // Ignore clicks on links inside items
    if ((e.target.closest && e.target.closest('a')) || e.metaKey || e.ctrlKey) return;
    const idx = getCurrentIndex();
    // Desktop: go directly to article
    if (window.matchMedia && window.matchMedia('(min-width: 992px)').matches) {
      const el = items[idx];
      const url = el && (el.getAttribute('data-permalink') || el.dataset.permalink);
      if (url) { window.location.href = url; return; }
    }
    // Mobile: disable tap-to-advance to prioritize vertical scroll reliability
    return;
  });

  // Auto-advance with pause behavior
  let autoRunning = true;
  let autoLastTs = null;
  let autoProgress = 0; // 0..1
  let autoIdx = getCurrentIndex();
  const AUTO_DURATION = 5000; // 5s per story
  const PAUSE_AFTER_INTERACTION = 3000; // 3s
  let pauseUntil = 0;

  function setBarsForAuto() {
    barSpans.forEach((span, i) => {
      if (i < autoIdx) span.style.width = '100%';
      else if (i > autoIdx) span.style.width = '0%';
      else span.style.width = (autoProgress * 100).toFixed(1) + '%';
    });
  }

  function advanceTo(idx) {
    autoIdx = Math.max(0, Math.min(items.length - 1, idx));
    const target = items[autoIdx];
    carousel.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
    autoProgress = 0;
    autoLastTs = performance.now();
    setBarsForAuto();
  }

  function autoTick(ts) {
    if (!autoRunning) return;
    if (document.hidden) { requestAnimationFrame(autoTick); return; }
    if (!autoLastTs) autoLastTs = ts;
    const now = ts;
    if (now < pauseUntil) { requestAnimationFrame(autoTick); return; }
    const delta = now - autoLastTs;
    autoLastTs = now;
    autoProgress += delta / AUTO_DURATION;
    if (autoProgress >= 1) {
      if (autoIdx < items.length - 1) {
        advanceTo(autoIdx + 1);
      } else {
        advanceTo(0);
      }
    } else {
      setBarsForAuto();
    }
    requestAnimationFrame(autoTick);
  }

  function pauseAuto(ms = PAUSE_AFTER_INTERACTION) {
    pauseUntil = performance.now() + ms;
  }

  // Reset auto on manual scroll alignment
  let alignTimer = null;
  carousel.addEventListener('scroll', () => {
    // When user scrolls, pause auto and sync index once settled
    pauseAuto(3000);
    if (alignTimer) clearTimeout(alignTimer);
    alignTimer = setTimeout(() => {
      autoIdx = getCurrentIndex();
      autoProgress = 0;
      setBarsForAuto();
    }, 150);
  }, { passive: true });

  // Pause on pointer/touch over carousel
  ['pointerdown','touchstart','mouseenter'].forEach(ev => {
    carousel.addEventListener(ev, () => pauseAuto(3000), { passive: true });
  });
  // Resume logic uses pauseUntil timestamp; no extra handler needed

  // Pause when tab hidden
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) pauseAuto(999999); else pauseAuto(800);
  });

  // React to lightbox state to pause/resume auto-advance
  document.addEventListener('featured-lightbox-open', () => pauseAuto(999999));
  document.addEventListener('featured-lightbox-close', () => pauseAuto(800));

  // Pause auto when lightbox is open; resume slightly after close
  document.addEventListener('featured-lightbox-open', () => pauseAuto(999999));
  document.addEventListener('featured-lightbox-close', () => pauseAuto(800));

  autoRunning = true;
  requestAnimationFrame(autoTick);
});

// Featured News: desktop navigation arrows (run now; we're already in DOMContentLoaded)
{
  const section = document.querySelector('.featured-news');
  const carousel = section ? section.querySelector('.featured-carousel') : null;
  const prevBtn = section ? section.querySelector('.featured-nav.prev') : null;
  const nextBtn = section ? section.querySelector('.featured-nav.next') : null;
  if (section && carousel && prevBtn && nextBtn) {
    let items = Array.from(carousel.querySelectorAll('.featured-story'));
    if (items.length) {
      const isDesktop = () => window.matchMedia && window.matchMedia('(min-width: 992px)').matches;

      function getCurrentIndex() {
        const scrollLeft = carousel.scrollLeft;
        let closestIdx = 0;
        let minDelta = Infinity;
        items.forEach((el, idx) => {
          const delta = Math.abs(el.offsetLeft - scrollLeft);
          if (delta < minDelta) { minDelta = delta; closestIdx = idx; }
        });
        return closestIdx;
      }

      function goTo(idx) {
        const clamped = Math.max(0, Math.min(items.length - 1, idx));
        const target = items[clamped];
        if (target) carousel.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
      }

      function onPrev(e) {
        e.preventDefault(); e.stopPropagation();
        if (!isDesktop()) return;
        const idx = getCurrentIndex();
        goTo(idx - 1);
      }

      function onNext(e) {
        e.preventDefault(); e.stopPropagation();
        if (!isDesktop()) return;
        const idx = getCurrentIndex();
        goTo(idx + 1);
      }

      prevBtn.addEventListener('click', onPrev);
      nextBtn.addEventListener('click', onNext);

      // Keep items list fresh if DOM changes (optional safeguard)
      const mo = new MutationObserver(() => { items = Array.from(carousel.querySelectorAll('.featured-story')); });
      mo.observe(carousel, { childList: true });
    }
  }
}
    
    // Do not unregister service workers automatically (keeps offline/cache benefits)
    
    
    
    // Foro de Debate: infinite scroll
    if (debateFeed && typeof ajax_object !== 'undefined') {
        const loading = document.getElementById('debate-loading');
        let isLoading = false;
        const getState = () => ({
            page: Number(debateFeed.getAttribute('data-page') || '1'),
            max: Number(debateFeed.getAttribute('data-max') || '1'),
            hashtag: debateFeed.getAttribute('data-hashtag') || ''
        });

        const loadMore = async () => {
            const { page, max, hashtag } = getState();
            if (isLoading || page >= max) return;
            isLoading = true;
            if (loading) loading.style.display = 'block';

            const formData = new URLSearchParams();
            formData.set('action', 'load_more_debates');
            formData.set('page', String(page + 1));
            if (hashtag) formData.set('hashtag', hashtag);

            try {
                const res = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: formData.toString(),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success && data.data && data.data.html) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.data.html;
                    while (tmp.firstChild) {
                        debateFeed.appendChild(tmp.firstChild);
                    }
                    debateFeed.setAttribute('data-page', String(page + 1));
                }
            } catch (err) {
                // fail silently
            } finally {
                isLoading = false;
                if (loading) loading.style.display = 'none';
            }
        };

        const onScroll = () => {
            const { page, max } = getState();
            if (page >= max) return;
            const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 800);
            if (nearBottom) loadMore();
        };
        window.addEventListener('scroll', debounce(onScroll, 150));
    }
    
    // Do not clear Cache Storage automatically
    
    // Remove only known mobile menu overlays (avoid nuking legit UI overlays like story titles)
    const menuOverlays = document.querySelectorAll('.mobile-menu-overlay');
    menuOverlays.forEach(overlay => {
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    });
    
    // Ensure body is clickable
    document.body.style.pointerEvents = 'auto';
    document.documentElement.style.pointerEvents = 'auto';
    // Theme toggle functionality
    const body = document.body;
    
    // Determine initial theme: saved or OS preference (supports light | dark | fullcolor)
    let currentTheme = localStorage.getItem('theme');
    if (!currentTheme) {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        currentTheme = prefersDark ? 'dark' : 'light';
    }
    
    // Apply theme
    if (currentTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        body.classList.add('dark-theme');
        updateThemeIcons('dark');
    } else if (currentTheme === 'fullcolor') {
        body.setAttribute('data-theme', 'fullcolor');
        body.classList.add('fullcolor-theme');
        updateThemeIcons('fullcolor');
    } else {
        body.setAttribute('data-theme', 'light');
        body.classList.remove('dark-theme');
        body.classList.remove('fullcolor-theme');
        updateThemeIcons('light');
    }
    
    function updateThemeIcons(theme) {
        const symbolId = (theme === 'dark') ? '#icon-sun' : '#icon-moon';
        // Update SVG <use> where available
        const svgUses = document.querySelectorAll('#theme-icon use, #desktop-theme-icon use, #mobile-theme-icon use, .theme-toggle svg use');
        svgUses.forEach(u => { try { u.setAttribute('href', symbolId); } catch(e){} });
        // Backward compatibility for remaining <i> FA icons during transition
        const legacyIcons = document.querySelectorAll('.theme-toggle i, #theme-icon i, #desktop-theme-icon i, #mobile-theme-icon i');
        legacyIcons.forEach(icon => {
            if (!icon) return;
            if (theme === 'dark') icon.className = 'fas fa-sun';
            else icon.className = 'fas fa-moon';
        });
    }
    
    function toggleTheme() {
        const t = body.getAttribute('data-theme');
        if (t === 'light') {
            body.setAttribute('data-theme', 'dark');
            body.classList.add('dark-theme');
            body.classList.remove('fullcolor-theme');
            updateThemeIcons('dark');
            localStorage.setItem('theme', 'dark');
        } else if (t === 'dark') {
            body.setAttribute('data-theme', 'fullcolor');
            body.classList.add('fullcolor-theme');
            body.classList.remove('dark-theme');
            updateThemeIcons('fullcolor');
            localStorage.setItem('theme', 'fullcolor');
        } else {
            body.setAttribute('data-theme', 'light');
            body.classList.remove('dark-theme');
            body.classList.remove('fullcolor-theme');
            updateThemeIcons('light');
            localStorage.setItem('theme', 'light');
        }
    }
    
    // Add click listeners to all theme toggles
    document.addEventListener('click', function(e) {
        if (e.target.closest('#theme-toggle') || e.target.closest('#desktop-theme-toggle') || e.target.closest('#mobile-theme-toggle') || e.target.closest('.theme-toggle')) {
            e.preventDefault();
            toggleTheme();
        }
    });

    // If user hasn't explicitly chosen a theme, follow OS preference changes
    if (!localStorage.getItem('theme') && window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const applyMq = (ev) => {
            if (localStorage.getItem('theme')) return; // user took control
            const mode = ev.matches ? 'dark' : 'light';
            body.setAttribute('data-theme', mode);
            body.classList.toggle('dark-theme', mode === 'dark');
            body.classList.toggle('fullcolor-theme', mode === 'fullcolor');
            updateThemeIcons(mode);
        };
        if (typeof mq.addEventListener === 'function') mq.addEventListener('change', applyMq);
        else if (typeof mq.addListener === 'function') mq.addListener(applyMq);
    }

    // Menu toggle functionality
    const hamburgerToggle = document.getElementById('hamburger-toggle');
    const desktopHamburgerToggle = document.getElementById('desktop-hamburger-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');

    // Toggle menu function
    function toggleMenu(open) {
        const isOpen = mobileMenu.classList.contains('active');
        if (open === undefined) {
            mobileMenu.classList.toggle('active');
        } else if (open) {
            mobileMenu.classList.add('active');
        } else {
            mobileMenu.classList.remove('active');
        }
        
        // Update body overflow and ARIA attributes
        if (mobileMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            document.body.classList.add('mobile-menu-open');
            if (desktopHamburgerToggle) {
                desktopHamburgerToggle.setAttribute('aria-expanded', 'true');
            }
            if (hamburgerToggle) {
                hamburgerToggle.setAttribute('aria-expanded', 'true');
            }
        } else {
            document.body.style.overflow = '';
            document.body.classList.remove('mobile-menu-open');
            if (desktopHamburgerToggle) {
                desktopHamburgerToggle.setAttribute('aria-expanded', 'false');
            }
            if (hamburgerToggle) {
                hamburgerToggle.setAttribute('aria-expanded', 'false');
            }
        }
    }

    // Mobile menu toggle
    if (hamburgerToggle && mobileMenu) {
        hamburgerToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }

    // Desktop hamburger menu toggle
    if (desktopHamburgerToggle && mobileMenu) {
        desktopHamburgerToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }
    
    // Close mobile menu
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu(false);
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close menu when clicking outside
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
                document.body.classList.remove('mobile-menu-open');
                if (desktopHamburgerToggle) desktopHamburgerToggle.setAttribute('aria-expanded', 'false');
                if (hamburgerToggle) hamburgerToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Close menu with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('active')) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
            document.body.classList.remove('mobile-menu-open');
            if (desktopHamburgerToggle) desktopHamburgerToggle.setAttribute('aria-expanded', 'false');
            if (hamburgerToggle) hamburgerToggle.setAttribute('aria-expanded', 'false');
        }
    });


    // Desktop Sticky Header: reuse mobile header when scrolling on desktop
    (function initDesktopStickyHeader(){
        const header = document.querySelector('.mobile-header');
        if (!header) return;
        const mq = window.matchMedia('(min-width: 992px)');
        let ticking = false;
        function update(){
            const y = window.pageYOffset || document.documentElement.scrollTop || 0;
            const active = mq.matches && y > 120 && !document.body.classList.contains('mobile-menu-open');
            document.body.classList.toggle('show-desktop-sticky', !!active);
            ticking = false;
        }
        window.addEventListener('scroll', () => {
            if (!ticking) { ticking = true; requestAnimationFrame(update); }
        }, { passive: true });
        if (typeof mq.addEventListener === 'function') mq.addEventListener('change', update);
        else if (typeof mq.addListener === 'function') mq.addListener(update);
        // Observe menu state to hide sticky when menu opens
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu && 'MutationObserver' in window) {
            const mo = new MutationObserver(update);
            mo.observe(mobileMenu, { attributes: true, attributeFilter: ['class'] });
        }
        update();
    })();

    // Infinite scroll functionality (home/archive/trending only)
    let currentPage = 1;
    let isLoading = false;
    let hasMorePosts = true;

    const postsContainer = document.getElementById('posts-feed');
    const loadingSpinner = document.getElementById('loading-spinner');

    function loadMorePosts() {
        if (isLoading || !hasMorePosts) return;

        isLoading = true;
        if (loadingSpinner) loadingSpinner.style.display = 'block';

        const formData = new FormData();
        formData.append('action', 'load_more_posts');
        formData.append('page', currentPage + 1);
        formData.append('nonce', ajax_object.nonce);
        if (ajax_object.current_post_id && Number(ajax_object.current_post_id) > 0) {
            formData.append('exclude[]', ajax_object.current_post_id);
        }
        // Persist trending filters if enabled
        if (ajax_object.trending && Number(ajax_object.trending.enabled) === 1) {
            formData.append('trending', '1');
            if (ajax_object.trending.sort) formData.append('sort', ajax_object.trending.sort);
            if (ajax_object.trending.cat_ids) formData.append('cat_ids', ajax_object.trending.cat_ids);
            if (ajax_object.trending.tag_slugs) formData.append('tag_slugs', ajax_object.trending.tag_slugs);
        }

        fetch(ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (!data || data.trim() === '') {
                hasMorePosts = false;
            } else if (postsContainer) {
                postsContainer.insertAdjacentHTML('beforeend', data);
                currentPage++;

                // Initialize like UI for newly added posts
                initLikeButtons(postsContainer);
                initSaveButtons(postsContainer);
                initPostVideos(postsContainer);
                
                // Re-observe new lazy-loaded images
                if ('IntersectionObserver' in window) {
                    const newImages = postsContainer.querySelectorAll('img[loading="lazy"]:not(.observed)');
                    newImages.forEach(img => {
                        img.classList.add('observed');
                        if (window.imageObserver) {
                            window.imageObserver.observe(img);
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading posts:', error);
        })
        .finally(() => {
            isLoading = false;
            if (loadingSpinner) loadingSpinner.style.display = 'none';
        });
    }

    // ---------- Likes ----------
    function storageKey(postId) { return `liked_${postId}`; }
    function isLiked(postId) { return localStorage.getItem(storageKey(postId)) === '1'; }
    function setLiked(postId, liked) { localStorage.setItem(storageKey(postId), liked ? '1' : '0'); }

    function updateLikeUI(btn, liked, count) {
        const icon = btn.querySelector('i');
        const countEl = btn.querySelector('.like-count');
        if (icon) {
            icon.classList.toggle('far', !liked);
            icon.classList.toggle('fas', liked);
            icon.classList.add('fa-heart');
        }
        btn.classList.toggle('liked', liked);
        if (countEl && typeof count === 'number') {
            countEl.textContent = count;
        }
    }

    function initLikeButtons(scope = document) {
        const buttons = scope.querySelectorAll('.like-btn');
        buttons.forEach(btn => {
            const postId = btn.getAttribute('data-post-id');
            if (!postId) return;
            const liked = isLiked(postId);
            updateLikeUI(btn, liked);
        });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.like-btn');
        if (!btn) return;

        const postId = btn.getAttribute('data-post-id');
        if (!postId) return;

        const liked = isLiked(postId);
        const actionDo = liked ? 'unlike' : 'like';

        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('nonce', ajax_object.nonce);
        formData.append('post_id', postId);
        formData.append('do', actionDo);

        fetch(ajax_object.ajax_url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(json => {
                if (!json || !json.success) return;
                const newCount = json.data && typeof json.data.count === 'number' ? json.data.count : undefined;
                setLiked(postId, !liked);
                updateLikeUI(btn, !liked, newCount);
            })
            .catch(() => {})
    });

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { initLikeButtons(); initSaveButtons(); initPostVideos(); });
    } else {
        initLikeButtons();
        initSaveButtons();
        initPostVideos();
    }

    // ---------- Save / Bookmark ----------
    function saveKey(postId) { return `saved_${postId}`; }
    function isSaved(postId) { return localStorage.getItem(saveKey(postId)) === '1'; }
    function setSaved(postId, saved) { localStorage.setItem(saveKey(postId), saved ? '1' : '0'); }

    function updateSaveUI(btn, saved) {
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('far', !saved);
            icon.classList.toggle('fas', saved);
            icon.classList.add('fa-bookmark');
        }
        btn.classList.toggle('saved', saved);
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        btn.setAttribute('title', saved ? 'Guardado' : 'Guardar');
    }

    function initSaveButtons(scope = document) {
        const buttons = scope.querySelectorAll('.save-btn');
        buttons.forEach(btn => {
            const postId = btn.getAttribute('data-post-id');
            if (!postId) return;
            const saved = isSaved(postId);
            updateSaveUI(btn, saved);
        });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.save-btn');
        if (!btn) return;
        const postId = btn.getAttribute('data-post-id');
        if (!postId) return;
        const saved = isSaved(postId);
        setSaved(postId, !saved);
        updateSaveUI(btn, !saved);
    });

    // Ensure comment icon/link always navigates to comments
    document.addEventListener('click', function(e) {
        const a = e.target.closest('a.comments-link');
        if (!a) return;
        // Do not prevent default; explicitly navigate to be safe
        if (a.href) {
            window.location.href = a.href;
        }
    });

    // Start infinite scroll only when enabled and container exists
    if (postsContainer && typeof ajax_object !== 'undefined' && Number(ajax_object.infinite_enabled) === 1) {
        const onScroll = () => {
            if (!hasMorePosts || isLoading) return;
            const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 800);
            if (nearBottom) loadMorePosts();
        };
        window.addEventListener('scroll', debounce(onScroll, 150));
    }

    // ---------- Featured Videos (autoplay in viewport + mute toggle) ----------
    function initPostVideos(scope = document) {
        const wrappers = scope.querySelectorAll('.post-media');
        if (!wrappers.length) return;

        const pauseOthers = (except) => {
            document.querySelectorAll('.post-video').forEach(v => {
                if (v !== except && !v.paused) {
                    v.pause();
                    const w = v.closest('.post-media');
                    if (w) w.classList.remove('playing');
                }
            });
        };

        // Per-wrapper setup
        wrappers.forEach(wrap => {
            const isEmbed = wrap.classList.contains('embed-video');
            const muteBtn = wrap.querySelector('.mute-toggle');

            // Mount iframe for embeds, preserving overlays like mute button
            const mountEmbed = () => {
                if (!isEmbed) return;
                if (wrap.querySelector('iframe')) return;
                const src = wrap.getAttribute('data-src');
                if (!src) return;
                const iframe = document.createElement('iframe');
                iframe.src = src;
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
                iframe.setAttribute('allowfullscreen', '');
                iframe.setAttribute('loading', 'lazy');
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                // Remove only placeholders
                wrap.querySelectorAll('.embed-thumb, .embed-poster').forEach(el => el.remove());
                wrap.insertBefore(iframe, wrap.firstChild);
                wrap.classList.add('playing');
            };

            if (isEmbed) {
                // Click anywhere (except mute) mounts the iframe
                wrap.addEventListener('click', function(e) {
                    if (e.target.closest('.mute-toggle')) return;
                    mountEmbed();
                });

                // Mute toggle: rebuild src to switch mute state
                if (muteBtn) {
                    muteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const muted = muteBtn.getAttribute('data-muted') === '1';
                        const provider = wrap.getAttribute('data-provider') || '';
                        const id = wrap.getAttribute('data-id') || '';
                        let base = '';
                        if (provider === 'youtube' && id) {
                            base = `https://www.youtube.com/embed/${id}?autoplay=1&rel=0&playsinline=1&modestbranding=1&controls=0&fs=0&iv_load_policy=3`;
                            const newSrc = base + (muted ? '' : '&mute=1');
                            const iframe = wrap.querySelector('iframe');
                            if (iframe) iframe.src = newSrc; else wrap.setAttribute('data-src', newSrc);
                        } else if (provider === 'vimeo' && id) {
                            base = `https://player.vimeo.com/video/${id}?autoplay=1&title=0&byline=0&portrait=0`;
                            const newSrc = base + (muted ? '&muted=1' : '');
                            const iframe = wrap.querySelector('iframe');
                            if (iframe) iframe.src = newSrc; else wrap.setAttribute('data-src', newSrc);
                        }
                        muteBtn.setAttribute('data-muted', muted ? '0' : '1');
                    });
                }
            }
        });
    }

    // ---------- More Actions Dropdown ----------
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.more-actions__btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const container = btn.closest('.more-actions');
            const isOpen = container.classList.contains('is-open');
            
            // Close all other dropdowns
            document.querySelectorAll('.more-actions.is-open').forEach(dropdown => {
                if (dropdown !== container) {
                    dropdown.classList.remove('is-open');
                    const otherBtn = dropdown.querySelector('.more-actions__btn');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });
            
            // Toggle current dropdown
            container.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', container.classList.contains('is-open') ? 'true' : 'false');
            return;
        }
        
        // Handle menu item clicks
        const menuItem = e.target.closest('.more-actions__item');
        if (menuItem) {
            e.preventDefault();
            e.stopPropagation();
            
            if (menuItem.classList.contains('js-report-post')) {
                handleReportPost(menuItem);
            } else if (menuItem.classList.contains('js-share-link')) {
                handleShareLink(menuItem);
            } else if (menuItem.classList.contains('js-start-debate')) {
                handleStartDebate(menuItem);
            }
            
            // Close dropdown after action
            const container = menuItem.closest('.more-actions');
            if (container) {
                container.classList.remove('is-open');
                const btn = container.querySelector('.more-actions__btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
            return;
        }
        
        // Close dropdowns when clicking outside
        if (!e.target.closest('.more-actions')) {
            document.querySelectorAll('.more-actions.is-open').forEach(dropdown => {
                dropdown.classList.remove('is-open');
                const btn = dropdown.querySelector('.more-actions__btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Close dropdown with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.more-actions.is-open').forEach(dropdown => {
                dropdown.classList.remove('is-open');
                const btn = dropdown.querySelector('.more-actions__btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Initialize video functionality
    initPostVideos();

    // ---------- Forum Actions Handlers ----------
    function handleReportPost(menuItem) {
        const postId = menuItem.dataset.postId;
        if (!postId) return;

        if (!confirm('¿Estás seguro de que quieres denunciar este post?')) {
            return;
        }

        fetch(lanota_2026_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nm_report_post',
                post_id: postId,
                nonce: lanota_2026_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.data.message, 'success');
            } else {
                showNotification(data.data.message || 'Error al denunciar el post', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        });
    }

    function handleShareLink(menuItem) {
        const permalink = menuItem.dataset.permalink;
        if (!permalink) return;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(permalink).then(() => {
                showNotification('Enlace copiado al portapapeles', 'success');
            }).catch(err => {
                console.error('Error copying to clipboard:', err);
                fallbackCopyTextToClipboard(permalink);
            });
        } else {
            fallbackCopyTextToClipboard(permalink);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('Enlace copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Fallback: Could not copy text: ', err);
            showNotification('No se pudo copiar el enlace', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    function handleStartDebate(menuItem) {
        const postId = menuItem.dataset.postId;
        const title = menuItem.dataset.title;
        if (!postId) return;

        showDebateModal(postId, title);
    }

    function showDebateModal(parentPostId, originalTitle) {
        // Create modal HTML
        const modalHTML = `
            <div class="debate-modal-overlay" id="debate-modal">
                <div class="debate-modal">
                    <div class="debate-modal-header">
                        <h3>Iniciar nuevo debate</h3>
                        <button class="debate-modal-close" aria-label="Cerrar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="debate-modal-body">
                        <p class="debate-modal-reference">
                            <i class="fas fa-reply"></i>
                            En respuesta a: "${originalTitle}"
                        </p>
                        <form id="new-debate-form">
                            <div class="form-group">
                                <label for="debate-title">Título del debate</label>
                                <input type="text" id="debate-title" name="title" required 
                                       placeholder="Escribe el título de tu debate...">
                            </div>
                            <div class="form-group">
                                <label for="debate-content">Contenido</label>
                                <div class="debate-editor-container">
                                    <textarea id="debate-content" name="content" required 
                                              placeholder="Desarrolla tu punto de vista..." rows="6"></textarea>
                                    
                                    <!-- Editor Toolbar -->
                                    <div class="editor-toolbar">
                                        <button type="button" class="toolbar-btn emoji-btn" id="emoji-picker-btn-modal" aria-label="Agregar emoji" title="Agregar emoji">
                                            <i class="fas fa-smile"></i>
                                        </button>
                                        <button type="button" class="toolbar-btn hashtag-btn" id="hashtag-helper-btn-modal" aria-label="Ver hashtags populares" title="Hashtags populares">
                                            <i class="fas fa-hashtag"></i>
                                        </button>
                                    </div>

                                    <!-- Emoji Picker -->
                                    <div class="emoji-picker" id="emoji-picker-modal" style="display: none;">
                                        <div class="emoji-categories">
                                            <button type="button" class="emoji-cat-btn active" data-category="smileys">😊</button>
                                            <button type="button" class="emoji-cat-btn" data-category="people">👋</button>
                                            <button type="button" class="emoji-cat-btn" data-category="nature">🌱</button>
                                            <button type="button" class="emoji-cat-btn" data-category="food">🍎</button>
                                            <button type="button" class="emoji-cat-btn" data-category="activities">⚽</button>
                                            <button type="button" class="emoji-cat-btn" data-category="travel">✈️</button>
                                            <button type="button" class="emoji-cat-btn" data-category="objects">💡</button>
                                            <button type="button" class="emoji-cat-btn" data-category="symbols">❤️</button>
                                        </div>
                                        <div class="emoji-grid" id="emoji-grid-modal"></div>
                                    </div>


                                    <!-- Hashtag Helper -->
                                    <div class="hashtag-helper" id="hashtag-helper-modal" style="display: none;">
                                        <div class="hashtag-list" id="hashtag-list-modal"></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="parent_post_id" value="${parentPostId}">
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary debate-modal-close">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Crear Debate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('debate-modal');
        
        // Show modal
        setTimeout(() => modal.classList.add('active'), 10);
        
        // Initialize debate editor for modal
        setTimeout(() => {
            if (typeof DebateEditor !== 'undefined') {
                new DebateEditor('debate-content', 'emoji-picker-modal', null, 'hashtag-helper-modal');
            }
        }, 100);

        // Handle form submission
        const form = document.getElementById('new-debate-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creando...';

            fetch(lanota_2026_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'nm_create_debate_from_post',
                    parent_post_id: formData.get('parent_post_id'),
                    title: formData.get('title'),
                    content: formData.get('content'),
                    nonce: lanota_2026_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.data.message, 'success');
                    closeDebateModal();
                    // Optionally redirect to new debate
                    if (data.data.permalink) {
                        setTimeout(() => {
                            window.location.href = data.data.permalink;
                        }, 1500);
                    }
                } else {
                    showNotification(data.data.message || 'Error al crear el debate', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Crear Debate';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Debate';
            });
        });

        // Handle modal close
        modal.addEventListener('click', function(e) {
            if (e.target.classList.contains('debate-modal-overlay') || 
                e.target.closest('.debate-modal-close')) {
                closeDebateModal();
            }
        });

        // Close with Escape key
        function handleEscape(e) {
            if (e.key === 'Escape') {
                closeDebateModal();
            }
        }
        document.addEventListener('keydown', handleEscape);
        modal.dataset.escapeHandler = 'true';
    }

    function closeDebateModal() {
        const modal = document.getElementById('debate-modal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.remove();
                // Remove escape handler
                document.removeEventListener('keydown', handleEscape);
            }, 300);
        }
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto remove after 4 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // Make functions globally available
    window.handleReportPost = handleReportPost;
    window.handleShareLink = handleShareLink;
    window.handleStartDebate = handleStartDebate;

    // Utility function for debouncing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Post More Actions (three dots on single post image)
    document.addEventListener('click', function(e) {
        // Toggle more actions menu
        if (e.target.matches('.post-more-actions .more-actions-btn') || e.target.closest('.post-more-actions .more-actions-btn')) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.more-actions-btn');
            const menu = button.nextElementSibling;
            
            // Close all other menus
            document.querySelectorAll('.post-more-actions .more-actions-menu').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            
            // Toggle current menu
            menu.classList.toggle('show');
        }
        
        // Handle create debate from post
        if (e.target.matches('.create-debate-from-post') || e.target.closest('.create-debate-from-post')) {
            e.preventDefault();
            
            const button = e.target.closest('.create-debate-from-post');
            const postId = button.dataset.postId;
            
            if (!postId) return;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando debate...';
            
            fetch(ajax_object.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'create_debate_from_news',
                    post_id: postId,
                    nonce: ajax_object.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.data.message, data.data.exists ? 'info' : 'success');
                    
                    // Close menu
                    button.closest('.more-actions-menu').classList.remove('show');
                    
                    // Redirect to debate
                    setTimeout(() => {
                        window.location.href = data.data.permalink;
                    }, 1000);
                } else {
                    showNotification(data.data.message || 'Error al crear el debate', 'error');
                }
            })
            .catch(error => {
                console.error('Error creating debate:', error);
                showNotification('Error al crear el debate', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-comments"></i>Generar debate a partir de este post';
            });
        }
        
        // Handle report post
        if (e.target.matches('.report-post') || e.target.closest('.report-post')) {
            e.preventDefault();
            
            const button = e.target.closest('.report-post');
            const postId = button.dataset.postId;
            
            if (!postId) return;
            
            if (!confirm('¿Estás seguro de que quieres denunciar este post?')) {
                return;
            }
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Denunciando...';
            
            fetch(ajax_object.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'nm_report_post',
                    post_id: postId,
                    nonce: ajax_object.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.data.message || 'Denuncia registrada', 'success');
                    
                    // Close menu
                    button.closest('.more-actions-menu').classList.remove('show');
                } else {
                    showNotification(data.data.message || 'Error al denunciar', 'error');
                }
            })
            .catch(error => {
                console.error('Error reporting post:', error);
                showNotification('Error al denunciar el post', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-flag"></i>Denunciar post';
            });
        }
        
        // Close menus when clicking outside
        if (!e.target.closest('.post-more-actions')) {
            document.querySelectorAll('.post-more-actions .more-actions-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Close menus on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.post-more-actions .more-actions-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Notification system for single posts
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} notification-toast`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && searchModal && searchModal.classList.contains('active')) {
            searchModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Add haptic feedback for mobile buttons
    const mobileNavBtns = document.querySelectorAll('.mobile-nav-btn');
    mobileNavBtns.forEach(btn => {
        btn.addEventListener('touchstart', function() {
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        });
    });

    // Scroll To Top (desktop)
    const scrollTopBtn = document.getElementById('scroll-to-top');
    function updateScrollTopVisibility() {
        if (!scrollTopBtn) return;
        const shouldShow = window.scrollY > 400;
        if (shouldShow) scrollTopBtn.classList.add('show');
        else scrollTopBtn.classList.remove('show');
    }
    if (scrollTopBtn) {
        window.addEventListener('scroll', updateScrollTopVisibility, { passive: true });
        updateScrollTopVisibility();
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Image Lightbox functionality (clean, single implementation)
    const imageLightbox = document.getElementById('image-lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxExcerpt = document.getElementById('lightbox-excerpt');
    const lightboxReadMore = document.getElementById('lightbox-read-more');
    const lightboxClose = document.getElementById('lightbox-close');
    const lightboxPrev = document.getElementById('lightbox-prev');
    const lightboxNext = document.getElementById('lightbox-next');
    const lightboxImageContainer = document.querySelector('.lightbox-image-container');
    const lightboxBars = document.getElementById('lightbox-bars');
    let currentArticleUrl = '';
    let featuredStories = [];
    let currentStoryIndex = -1;
    // Lightbox auto-advance state
    let lbAnimId = 0;
    let lbProgress = 0; // 0..1
    let lbLastTs = 0;
    const LB_DURATION = 5000; // 5s per story

    function openLightbox(imageSrc, title, excerpt, articleUrl) {
        if (imageLightbox && lightboxImage) {
            lightboxImage.src = imageSrc;
            if (lightboxTitle) lightboxTitle.textContent = title || '';
            if (lightboxExcerpt) lightboxExcerpt.textContent = excerpt || '';
            currentArticleUrl = articleUrl || '';
            // Ensure container is visible in the same frame to avoid flashes
            imageLightbox.style.display = 'block';
            requestAnimationFrame(() => {
                imageLightbox.classList.add('active');
            });
            if (lightboxReadMore) {
                lightboxReadMore.style.display = currentArticleUrl ? 'flex' : 'none';
            }
            imageLightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            document.dispatchEvent(new CustomEvent('featured-lightbox-open'));
        }
    }

    function closeLightbox() {
        if (imageLightbox) {
            imageLightbox.classList.remove('active');
            document.body.style.overflow = '';
            currentArticleUrl = '';
            currentStoryIndex = -1;
            if (lightboxReadMore) {
                lightboxReadMore.style.display = 'none';
            }
            imageLightbox.setAttribute('aria-hidden', 'true');
            document.dispatchEvent(new CustomEvent('featured-lightbox-close'));
            // Hide container after transition frame to avoid flicker
            requestAnimationFrame(() => { imageLightbox.style.display = 'none'; });
        }
    }

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (imageLightbox) {
        imageLightbox.addEventListener('click', function(e) {
            if (e.target === imageLightbox) closeLightbox();
        });
    }
    if (lightboxReadMore) {
        // Hide by default defensively
        lightboxReadMore.style.display = 'none';
        lightboxReadMore.addEventListener('click', function() {
            if (currentArticleUrl) window.location.href = currentArticleUrl;
        });
    }

    // Defensive: ensure container is hidden on init in case inline style was removed
    if (imageLightbox) {
        imageLightbox.style.display = 'none';
        imageLightbox.setAttribute('aria-hidden', 'true');
    }

    // Build stories array using data attributes
    const featuredStoryNodes = document.querySelectorAll('.featured-news .featured-story');
    featuredStories = Array.from(featuredStoryNodes).map((node) => {
        const img = node.querySelector('img');
        const full = node.dataset.full || (img ? img.src : '');
        const permalink = node.dataset.permalink || node.dataset.url || '#';
        const title = node.dataset.title || (node.querySelector('.featured-story-title')?.textContent || 'Noticia Destacada');
        const excerpt = node.dataset.excerpt || '';
        return { image: full, title, excerpt, url: permalink };
    });

    // Build bars in lightbox
    function renderLightboxBars() {
        if (!lightboxBars) return;
        lightboxBars.innerHTML = '';
        featuredStories.forEach(() => {
            const bar = document.createElement('div');
            bar.className = 'bar';
            const span = document.createElement('span');
            bar.appendChild(span);
            lightboxBars.appendChild(bar);
        });
    }
    renderLightboxBars();

    // Desktop: delegate click on any featured story to navigate directly
    document.addEventListener('click', function(e) {
        if (!(window.matchMedia && window.matchMedia('(min-width: 992px)').matches)) return;
        const node = e.target.closest && e.target.closest('.featured-news .featured-story');
        if (!node) return;
        const url = node.getAttribute('data-permalink') || node.dataset.permalink;
        if (url) {
            window.location.href = url;
        }
    });

    function setLightboxBars() {
        if (!lightboxBars) return;
        const spans = Array.from(lightboxBars.querySelectorAll('.bar > span'));
        spans.forEach((s, i) => {
            if (i < currentStoryIndex) s.style.width = '100%';
            else if (i > currentStoryIndex) s.style.width = '0%';
            else s.style.width = `${Math.max(0, Math.min(1, lbProgress)) * 100}%`;
        });
    }

    function cancelLbAnim() {
        if (lbAnimId) cancelAnimationFrame(lbAnimId);
        lbAnimId = 0;
    }

    function tickLightbox(ts) {
        if (!imageLightbox || !imageLightbox.classList.contains('active')) { cancelLbAnim(); return; }
        if (!lbLastTs) lbLastTs = ts;
        const delta = ts - lbLastTs; lbLastTs = ts;
        lbProgress += delta / LB_DURATION;
        if (lbProgress >= 1) {
            // Next story or close
            if (currentStoryIndex < featuredStories.length - 1) {
                showStoryAt(currentStoryIndex + 1);
            } else {
                closeLightbox();
                return;
            }
        } else {
            setLightboxBars();
        }
        lbAnimId = requestAnimationFrame(tickLightbox);
    }

    function startLightboxAuto() {
        lbProgress = 0; lbLastTs = 0;
        setLightboxBars();
        cancelLbAnim();
        lbAnimId = requestAnimationFrame(tickLightbox);
    }

    function showStoryAt(index) {
        if (!featuredStories.length) return;
        if (index < 0) index = featuredStories.length - 1;
        if (index >= featuredStories.length) index = 0;
        currentStoryIndex = index;
        const s = featuredStories[currentStoryIndex];
        openLightbox(s.image, s.title, s.excerpt, s.url);
        // Update bars and start auto progress for this story
        setLightboxBars();
        startLightboxAuto();
    }

    // Click handlers on previews: open lightbox only on mobile widths
    if (!(window.matchMedia && window.matchMedia('(min-width: 992px)').matches)) {
        featuredStoryNodes.forEach((node, idx) => {
            const img = node.querySelector('img');
            const open = (e) => { e.preventDefault(); e.stopPropagation(); showStoryAt(idx); };
            if (img) img.addEventListener('click', open);
            node.addEventListener('click', open);
        });
    }

    // Nav buttons
    if (lightboxPrev) lightboxPrev.addEventListener('click', (e) => { e.preventDefault(); if (currentStoryIndex !== -1) showStoryAt(currentStoryIndex - 1); });
    if (lightboxNext) lightboxNext.addEventListener('click', (e) => { e.preventDefault(); if (currentStoryIndex !== -1) showStoryAt(currentStoryIndex + 1); });

    // Keyboard nav
    document.addEventListener('keydown', function(e) {
        if (!imageLightbox || !imageLightbox.classList.contains('active')) return;
        if (e.key === 'ArrowLeft' && currentStoryIndex !== -1) showStoryAt(currentStoryIndex - 1);
        else if (e.key === 'ArrowRight' && currentStoryIndex !== -1) showStoryAt(currentStoryIndex + 1);
        else if (e.key === 'Escape') closeLightbox();
    });

    // Swipe
    if (lightboxImageContainer) {
        let touchStartX = 0, touchEndX = 0; const threshold = 50;
        lightboxImageContainer.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        lightboxImageContainer.addEventListener('touchmove', (e) => { touchEndX = e.changedTouches[0].screenX; }, { passive: true });
        lightboxImageContainer.addEventListener('touchend', () => {
            const dx = touchEndX - touchStartX;
            if (Math.abs(dx) > threshold) {
                if (dx < 0 && currentStoryIndex !== -1) showStoryAt(currentStoryIndex + 1);
                if (dx > 0 && currentStoryIndex !== -1) showStoryAt(currentStoryIndex - 1);
            }
            touchStartX = 0; touchEndX = 0;
        });
    }

    // Search suggestions functionality
    const searchInputs = document.querySelectorAll('.search-input');

    searchInputs.forEach(searchInput => {
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.trim();

                if (searchTerm.length < 3) {
                    hideSuggestions();
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetchSearchSuggestions(searchTerm);
                }, 300);
            });

            searchInput.addEventListener('blur', function() {
                setTimeout(hideSuggestions, 200);
            });
        }
    });

    function fetchSearchSuggestions(searchTerm) {
        const formData = new FormData();
        formData.append('action', 'search_suggestions');
        formData.append('search_term', searchTerm);

        fetch(ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(suggestions => {
            showSuggestions(suggestions);
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
        });
    }

    function showSuggestions(suggestions) {
        hideSuggestions();

        if (suggestions.length === 0) return;

        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'search-suggestions';
        suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        `;

        suggestions.forEach(suggestion => {
            const suggestionItem = document.createElement('a');
            suggestionItem.href = suggestion.url;
            suggestionItem.className = 'suggestion-item';
            suggestionItem.style.cssText = `
                display: block;
                padding: 12px 16px;
                text-decoration: none;
                color: var(--text-color);
                border-bottom: 1px solid var(--border-color);
                transition: background-color 0.2s ease;
            `;
            suggestionItem.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 4px;">${suggestion.title}</div>
                <div style="font-size: 12px; color: var(--text-secondary);">${suggestion.excerpt}</div>
            `;

            suggestionItem.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--hover-color)';
            });

            suggestionItem.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
            });

            suggestionsContainer.appendChild(suggestionItem);
        });

        const searchContainer = document.querySelector('.search-container');
        searchContainer.appendChild(suggestionsContainer);
    }

    function hideSuggestions() {
        const existingSuggestions = document.querySelector('.search-suggestions');
        if (existingSuggestions) {
            existingSuggestions.remove();
        }
    }

    // Post interactions
    document.addEventListener('click', function(e) {
        if (e.target.closest('.post-action')) {
            e.preventDefault();
            const action = e.target.closest('.post-action');
            action.style.color = 'var(--primary-color)';
            
            // Add simple animation
            action.style.transform = 'scale(1.1)';
            setTimeout(() => {
                action.style.transform = 'scale(1)';
            }, 150);
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Mobile menu toggle (if needed in future)
    function initMobileMenu() {
        const mobileBreakpoint = 768;
        
        function handleResize() {
            if (window.innerWidth <= mobileBreakpoint) {
                // Mobile specific adjustments
                document.body.classList.add('mobile-view');
            } else {
                document.body.classList.remove('mobile-view');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Initial call
    }

    initMobileMenu();

    // Social sharing functions
    window.shareOnFacebook = function(url) {
        const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    };

    window.shareOnTwitter = function(title, url) {
        const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    };

    window.shareOnWhatsApp = function(title, url) {
        const shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
        window.open(shareUrl, '_blank');
    };

    window.shareOnTelegram = function(title, url) {
        const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
        window.open(shareUrl, '_blank');
    };

    window.copyToClipboard = function(url) {
        function applyFeedback(btn) {
            if (!btn) return;
            const span = btn.querySelector('span');
            if (span) {
                const originalText = span.textContent;
                span.textContent = '¡Copiado!';
                btn.style.backgroundColor = 'var(--primary-color)';
                btn.style.color = 'white';
                setTimeout(() => {
                    span.textContent = originalText;
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                }, 2000);
            } else {
                // Icon-only button: add temporary visual class
                btn.classList.add('copied');
                const originalTitle = btn.getAttribute('title');
                btn.setAttribute('title', '¡Copiado!');
                setTimeout(() => {
                    btn.classList.remove('copied');
                    if (originalTitle) {
                        btn.setAttribute('title', originalTitle);
                    } else {
                        btn.removeAttribute('title');
                    }
                }, 2000);
            }
        }

        navigator.clipboard.writeText(url).then(() => {
            const btn = event.target.closest('.social-share-btn');
            applyFeedback(btn);
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            const btn = event.target.closest('.social-share-btn');
            applyFeedback(btn);
        });
    };

    // Enhanced lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.1
        });

        // Observe all images with data-src attribute
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
        
        // Also observe images with loading="lazy"
        document.querySelectorAll('img[loading="lazy"]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Performance optimization: debounce scroll events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Apply debounce to scroll handler
    const debouncedScroll = debounce(function() {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
            loadMorePosts();
        }
    }, 100);

    window.addEventListener('scroll', debouncedScroll);
    
    // Compose interactions: redirect to forum if logged in, paywall if not
    function goToComposeTarget() {
        if (typeof ajax_object !== 'undefined') {
            if (ajax_object.is_logged_in) {
                const target = ajax_object.forum_url || '/';
                window.location.href = target;
            } else {
                const target = ajax_object.paywall_url || ajax_object.login_url || '/';
                window.location.href = target;
            }
        }
    }

    // Legacy button support (if exists somewhere else)
    const composeBtn = document.getElementById('compose-btn');
    if (composeBtn) {
        composeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            goToComposeTarget();
        });
    }

    // New composer card
    const composeCard = document.getElementById('compose-card');
    if (composeCard) {
        let guestTooltipArmed = false;
        let guestTooltipTimer = null;

        function handleGuestTooltip(e) {
            if (typeof ajax_object === 'undefined' || ajax_object.is_logged_in) return false;
            // If not armed, show tooltip and arm for next tap
            if (!guestTooltipArmed) {
                e.preventDefault();
                composeCard.classList.add('show-tooltip');
                guestTooltipArmed = true;
                if (guestTooltipTimer) clearTimeout(guestTooltipTimer);
                guestTooltipTimer = setTimeout(() => {
                    composeCard.classList.remove('show-tooltip');
                    guestTooltipArmed = false;
                }, 2000);
                return true; // handled
            }
            // Already armed: proceed to navigate
            return false;
        }

        // Click anywhere on card
        composeCard.addEventListener('click', function(e) {
            if (handleGuestTooltip(e)) return;
            e.preventDefault();
            goToComposeTarget();
        });
        // Touchstart: show tooltip without navigating on first tap
        composeCard.addEventListener('touchstart', function(e) {
            if (handleGuestTooltip(e)) return;
        }, { passive: false });
        // Remove tooltip on blur
        composeCard.addEventListener('blur', function() {
            composeCard.classList.remove('show-tooltip');
            guestTooltipArmed = false;
            if (guestTooltipTimer) clearTimeout(guestTooltipTimer);
        });
        // Keyboard accessibility
        composeCard.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                goToComposeTarget();
            }
        });
        // Explicit Post button inside card
        const postBtn = composeCard.querySelector('.composer-post-btn');
        if (postBtn) {
            postBtn.addEventListener('click', function(e) {
                if (handleGuestTooltip(e)) return;
                e.preventDefault();
                e.stopPropagation();
                goToComposeTarget();
            });
            postBtn.addEventListener('touchstart', function(e) {
                if (handleGuestTooltip(e)) return;
            }, { passive: false });
        }
    }

    // Foro de Debate: character counter and create debate
    const debateForm = document.getElementById('debate-form');
    if (debateForm && typeof ajax_object !== 'undefined') {
        const textarea = document.getElementById('debate-content');
        const counter = document.getElementById('debate-counter');
        const linkInput = document.getElementById('debate-link');
        const feedback = document.getElementById('debate-feedback');

        const maxLen = Number(ajax_object.debate_max_len || 400);

        const updateCounter = () => {
            const len = (textarea.value || '').length;
            counter.textContent = `${len} / ${maxLen}`;
            counter.style.color = len > maxLen ? 'var(--danger-color, #e74c3c)' : '';
        };
        textarea.addEventListener('input', updateCounter);
        updateCounter();

        debateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            feedback.textContent = '';
            const content = (textarea.value || '').trim();
            const link_url = (linkInput && linkInput.value) ? linkInput.value.trim() : '';
            if (!content) {
                feedback.textContent = 'Escribí algo para publicar.';
                return;
            }
            if (content.length > maxLen) {
                feedback.textContent = `Máximo ${maxLen} caracteres.`;
                return;
            }
            const formData = new URLSearchParams();
            formData.set('action', 'create_debate');
            formData.set('nonce', ajax_object.nonce);
            formData.set('content', content);
            if (link_url) formData.set('link_url', link_url);

            debateForm.querySelector('button[type="submit"]').disabled = true;
            try {
                const res = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: formData.toString(),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    textarea.value = '';
                    if (linkInput) linkInput.value = '';
                    updateCounter();
                    feedback.textContent = (data.data.status === 'publish') ? 'Publicado' : 'Enviado para moderación';
                    // Simple refresh to show the new debate at top if published
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    feedback.textContent = data.data && data.data.message ? data.data.message : 'Error al publicar.';
                }
            } catch (err) {
                feedback.textContent = 'Error de red.';
            } finally {
                debateForm.querySelector('button[type="submit"]').disabled = false;
            }
        });
    }

    // Foro de Debate: reply forms
    if (debateFeed && typeof ajax_object !== 'undefined') {
        debateFeed.addEventListener('submit', async (e) => {
            const form = e.target.closest('form.reply-form');
            if (!form) return;
            e.preventDefault();
            const card = form.closest('.debate-card');
            if (!card) return;
            const postId = card.getAttribute('data-id');
            const input = form.querySelector('input[name="content"]');
            const content = (input && input.value) ? input.value.trim() : '';
            const maxLen = Number(ajax_object.reply_max_len || 240);
            if (!content) return;
            if (content.length > maxLen) {
                alert(`Máximo ${maxLen} caracteres.`);
                return;
            }

            const formData = new URLSearchParams();
            formData.set('action', 'create_debate_reply');
            formData.set('nonce', ajax_object.nonce);
            formData.set('post_id', postId);
            formData.set('content', content);

            form.querySelector('button[type="submit"]').disabled = true;
            try {
                const res = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: formData.toString(),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    input.value = '';
                    // Simple refresh so comment counts and preview list update
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    alert(data.data && data.data.message ? data.data.message : 'Error al responder.');
                }
            } catch (err) {
                alert('Error de red.');
            } finally {
                form.querySelector('button[type="submit"]').disabled = false;
            }
        });
    }
    
    // Featured carousel scroll functionality
    const featuredCarousel = document.querySelector('.featured-carousel');
    if (featuredCarousel) {
        featuredCarousel.addEventListener('wheel', (e) => {
            // Convert vertical wheel to horizontal scroll only when vertical intent
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                featuredCarousel.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        // Touch support for mobile: only block default on horizontal-intent drags
        let startX = 0, startY = 0, scrollLeft = 0, isHorizontal = false;
        const intentThreshold = 8; // px

        featuredCarousel.addEventListener('touchstart', (e) => {
            const t = e.touches[0];
            startX = t.pageX; startY = t.pageY;
            scrollLeft = featuredCarousel.scrollLeft;
            isHorizontal = false;
        }, { passive: true });

        featuredCarousel.addEventListener('touchmove', (e) => {
            if (startX === 0 && startY === 0) return;
            const t = e.touches[0];
            const dx = t.pageX - startX;
            const dy = t.pageY - startY;
            if (!isHorizontal) {
                if (Math.abs(dx) > Math.abs(dy) + intentThreshold) {
                    isHorizontal = true;
                } else {
                    // Vertical scroll: let the page handle it
                    return;
                }
            }
            // Horizontal drag: prevent default to avoid page scroll and translate to carousel scroll
            e.preventDefault();
            const walk = dx * 1.6;
            featuredCarousel.scrollLeft = scrollLeft - walk;
        }, { passive: false });

        featuredCarousel.addEventListener('touchend', () => {
            startX = 0; startY = 0; isHorizontal = false;
        });
    }

    // ===== Post Actions (three-dots) =====
    (function initPostActions(){
        const MENUS = document.querySelectorAll('.post-actions-more');
        if (!MENUS.length) return;

        function closeAll(except) {
            MENUS.forEach(box => {
                if (except && box === except) return;
                const btn = box.querySelector('.post-actions-more__toggle');
                const menu = box.querySelector('.post-actions-more__menu');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                if (menu) menu.setAttribute('aria-hidden', 'true');
                box.classList.remove('is-open');
            });
        }

        document.addEventListener('click', function(e){
            const toggle = e.target.closest && e.target.closest('.post-actions-more__toggle');
            const container = e.target.closest && e.target.closest('.post-actions-more');
            if (toggle && container) {
                e.preventDefault();
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                closeAll();
                const menu = container.querySelector('.post-actions-more__menu');
                const next = !expanded;
                toggle.setAttribute('aria-expanded', next ? 'true' : 'false');
                if (menu) menu.setAttribute('aria-hidden', next ? 'false' : 'true');
                container.classList.toggle('is-open', next);
                return;
            }
            // Outside click closes
            if (container) return; // clicks inside keep it
            closeAll();
        });

        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeAll();
        });

        function ensureAuthAndPerms(actionLabel) {
            if (typeof ajax_object === 'undefined') return { ok: false };
            if (!ajax_object.is_logged_in) {
                // Send to login
                window.location.href = ajax_object.login_url || '/wp-login.php';
                return { ok: false };
            }
            if (!ajax_object.can_post_forum) {
                // Not subscribed
                const go = confirm('Necesitás una suscripción activa para usar esta función. ¿Ver planes?');
                if (go) window.location.href = ajax_object.paywall_url || '/suscripcion/';
                return { ok: false };
            }
            return { ok: true };
        }

        // Start debate from post (on single posts)
        document.addEventListener('click', function(e){
            const a = e.target.closest && e.target.closest('.js-start-debate');
            if (!a) return;
            e.preventDefault();
            const gate = ensureAuthAndPerms('Iniciar debate');
            if (!gate.ok) return;
            const title = a.getAttribute('data-title') || '';
            const url = a.getAttribute('data-url') || '';
            // Prefill forum form
            const forum = (typeof ajax_object !== 'undefined' && ajax_object.forum_url) ? ajax_object.forum_url : '/en-debate/';
            const content = title && url ? `${title}\n\n${url}` : (url || title);
            const target = new URL(forum, window.location.origin);
            target.searchParams.set('prefill', '1');
            if (title) target.searchParams.set('title', title);
            if (content) target.searchParams.set('content', content);
            target.searchParams.set('open', '1');
            window.location.href = target.toString();
        });

        // Report post (active on debate topics)
        document.addEventListener('click', function(e){
            const a = e.target.closest && e.target.closest('.js-report-post');
            if (!a) return;
            e.preventDefault();
            const gate = ensureAuthAndPerms('Denunciar posteo');
            if (!gate.ok) return;
            const title = a.getAttribute('data-title') || '';
            const link = a.getAttribute('data-url') || window.location.href;
            // Placeholder: send via email client. Replace with backend endpoint when available.
            const subj = encodeURIComponent('Denuncia de posteo');
            const body = encodeURIComponent(`Quiero denunciar el siguiente contenido:\n\nTítulo: ${title}\nURL: ${link}\n\nMotivo: `);
            window.location.href = `mailto:?subject=${subj}&body=${body}`;
        });
    })();

    // ===== Forum prefill handler (arriving from Start Debate) =====
    (function prefillForum(){
        try {
            const params = new URLSearchParams(window.location.search);
            if (params.get('prefill') !== '1') return;
            const t = params.get('title') || '';
            const c = params.get('content') || '';
            const titleInput = document.getElementById('topic_title');
            const contentInput = document.getElementById('topic_content');
            if (titleInput) titleInput.value = t;
            if (contentInput) contentInput.value = c;
            // Open the collapsible form if present
            const wrap = document.querySelector('.forum-new-topic.is-collapsible');
            const btn = wrap ? wrap.querySelector('.collapsible-toggle') : null;
            const panel = document.getElementById('newTopicPanel');
            if (btn && panel) {
                btn.setAttribute('aria-expanded', 'true');
                btn.title = 'Ocultar formulario';
                panel.hidden = false;
                panel.classList.remove('is-collapsed');
                panel.classList.add('is-open');
                const icon = btn.querySelector('i');
                if (icon) icon.style.transform = 'rotate(180deg)';
                // Focus title for quick editing
                if (titleInput) setTimeout(()=> titleInput.focus(), 150);
            }
        } catch(_) {}
    })();

    // ===== Debate Replies (subscribers) =====
    (function debateReplies(){
        const repliesWrap = document.getElementById('replies');
        const replyForm = document.querySelector('.js-reply-form');
        if (!repliesWrap && !replyForm) return;

        function requireSubscriber() {
            if (typeof ajax_object === 'undefined') return false;
            if (!ajax_object.is_logged_in) {
                window.location.href = ajax_object.login_url || '/wp-login.php';
                return false;
            }
            if (!ajax_object.can_post_forum) {
                const go = confirm('Las respuestas son para suscriptores. ¿Ver planes?');
                if (go) window.location.href = ajax_object.paywall_url || '/suscripcion/';
                return false;
            }
            return true;
        }

        function buildReplyNode(commentId, author, dateText, content, liked, likeCount){
            const el = document.createElement('article');
            el.className = 'reply-item';
            el.setAttribute('data-comment-id', String(commentId));
            el.innerHTML = `
              <header class="reply-meta">
                <strong class="reply-author"></strong>
                <time class="reply-date"></time>
              </header>
              <div class="reply-content"></div>
              <div class="reply-actions">
                <button class="reply-like js-like-reply ${liked ? 'is-liked' : ''}" data-comment-id="${commentId}" aria-pressed="${liked ? 'true' : 'false'}">
                  <i class="fas fa-thumbs-up"></i> <span class="reply-like-count">${likeCount}</span>
                </button>
              </div>`;
            el.querySelector('.reply-author').textContent = author || 'Vos';
            el.querySelector('.reply-date').textContent = dateText || new Date().toLocaleDateString();
            el.querySelector('.reply-content').textContent = content;
            return el;
        }

        if (replyForm) {
            replyForm.addEventListener('submit', async (e)=>{
                e.preventDefault();
                if (!requireSubscriber()) return;
                const ta = replyForm.querySelector('textarea[name="content"]');
                const btn = replyForm.querySelector('.js-submit-reply');
                const postId = replyForm.getAttribute('data-post-id');
                const content = (ta && ta.value || '').trim();
                if (!content) return;
                btn && (btn.disabled = true);
                try {
                    const fd = new FormData();
                    fd.append('action', 'create_debate_reply');
                    fd.append('nonce', ajax_object.nonce || '');
                    fd.append('post_id', postId || '');
                    fd.append('content', content);
                    const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data || !data.success) throw new Error(data && data.data && data.data.message || 'Error');
                    const cid = data.data.comment_id;
                    const node = buildReplyNode(cid, ajax_object.current_user_name || '', '', content, false, 0);
                    if (repliesWrap) {
                        // Insert on top
                        repliesWrap.insertBefore(node, repliesWrap.firstChild);
                    }
                    if (ta) ta.value = '';
                } catch(err) {
                    alert(err && err.message ? err.message : 'No se pudo publicar');
                } finally {
                    btn && (btn.disabled = false);
                }
            });
        }

        document.addEventListener('click', async (e)=>{
            const btn = e.target.closest && e.target.closest('.js-like-reply');
            if (!btn) return;
            e.preventDefault();
            if (!ajax_object || !ajax_object.is_logged_in) {
                window.location.href = ajax_object && ajax_object.login_url ? ajax_object.login_url : '/wp-login.php';
                return;
            }
            const cid = btn.getAttribute('data-comment-id');
            const countEl = btn.querySelector('.reply-like-count');
            let count = parseInt(countEl ? countEl.textContent : '0', 10) || 0;
            const wasLiked = btn.classList.contains('is-liked');
            // Optimistic
            btn.classList.toggle('is-liked');
            btn.setAttribute('aria-pressed', wasLiked ? 'false' : 'true');
            count = wasLiked ? Math.max(0, count - 1) : (count + 1);
            if (countEl) countEl.textContent = String(count);
            try {
                const fd = new FormData();
                fd.append('action', 'nm_toggle_reply_like');
                fd.append('nonce', ajax_object.nonce || '');
                fd.append('comment_id', cid || '');
                const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: fd });
                const data = await res.json();
                if (!data || !data.success) throw new Error('Error');
                if (countEl) countEl.textContent = String(data.data.count || 0);
                btn.classList.toggle('is-liked', !!data.data.liked);
                btn.setAttribute('aria-pressed', data.data.liked ? 'true' : 'false');
            } catch(_) {
                // Revert on error
                btn.classList.toggle('is-liked', wasLiked);
                btn.setAttribute('aria-pressed', wasLiked ? 'true' : 'false');
                if (countEl) countEl.textContent = String(wasLiked ? (count + 1) : Math.max(0, count - 1));
                alert('No se pudo actualizar el me gusta');
            }
        });
    })();
});
