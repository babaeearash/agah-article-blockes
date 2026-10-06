document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.aab-copy-btn');
    if (!btn) return;

    const url = btn.getAttribute('data-url');
    if (!url) return;

    const originalText = btn.textContent;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(url);
        } else {
            const input = document.createElement('textarea');
            input.value = url;
            input.setAttribute('readonly', '');
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        btn.textContent = 'لینک کپی شد';
        btn.classList.add('aab-copied');

        setTimeout(function () {
            btn.textContent = originalText;
            btn.classList.remove('aab-copied');
        }, 1800);
    } catch (err) {
        btn.textContent = 'خطا در کپی';
        setTimeout(function () {
            btn.textContent = originalText;
        }, 1800);
    }
});

function aabInitResponsiveTables() {
    document.querySelectorAll('.aab-content table').forEach(function (table) {
        let scrollArea = table.closest('.aab-table-scroll, .wp-block-table, .table-responsive');

        if (!scrollArea) {
            scrollArea = document.createElement('div');
            scrollArea.className = 'aab-table-scroll';
            table.parentNode.insertBefore(scrollArea, table);
            scrollArea.appendChild(table);
        }

        scrollArea.setAttribute('role', 'region');
        scrollArea.setAttribute('aria-label', 'جدول قابل پیمایش');
        scrollArea.setAttribute('tabindex', '0');
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        aabInitResponsiveTables();
    });
} else {
    aabInitResponsiveTables();
}

function aabActivatePlaylistVideo(button) {
    const playlist = button.closest('[data-aab-video-playlist]');
    if (!playlist || button.getAttribute('aria-selected') === 'true') return;

    const index = button.getAttribute('data-aab-video-index');
    const template = playlist.querySelector('[data-aab-video-template="' + index + '"]');
    const frame = playlist.querySelector('[data-aab-video-frame]');
    const currentTitle = playlist.querySelector('[data-aab-video-current-title]');

    if (!template || !frame) return;

    frame.replaceChildren(template.content.cloneNode(true));

    frame.querySelectorAll('script[src]').forEach(function (oldScript) {
        const script = document.createElement('script');
        script.src = oldScript.src;
        script.type = oldScript.type || 'text/javascript';
        script.async = true;
        oldScript.replaceWith(script);
    });

    playlist.querySelectorAll('[data-aab-video-index]').forEach(function (item) {
        const isCurrent = item === button;
        item.setAttribute('aria-selected', String(isCurrent));
        item.setAttribute('tabindex', isCurrent ? '0' : '-1');
    });

    frame.setAttribute('aria-labelledby', button.id);
    if (currentTitle) {
        currentTitle.textContent = button.getAttribute('data-aab-video-title') || '';
    }
}

document.addEventListener('click', function (e) {
    const videoButton = e.target.closest('[data-aab-video-index]');
    if (!videoButton) return;
    aabActivatePlaylistVideo(videoButton);
});

document.addEventListener('keydown', function (e) {
    const videoButton = e.target.closest('[data-aab-video-index]');
    if (!videoButton) return;

    const playlist = videoButton.closest('[data-aab-video-playlist]');
    if (!playlist) return;

    const items = Array.from(playlist.querySelectorAll('[data-aab-video-index]'));
    const currentIndex = items.indexOf(videoButton);
    const handledKeys = ['ArrowDown', 'ArrowLeft', 'ArrowUp', 'ArrowRight', 'Home', 'End'];
    let nextIndex = currentIndex;

    if (!handledKeys.includes(e.key)) return;

    if (e.key === 'ArrowDown' || e.key === 'ArrowLeft') nextIndex = Math.min(items.length - 1, currentIndex + 1);
    if (e.key === 'ArrowUp' || e.key === 'ArrowRight') nextIndex = Math.max(0, currentIndex - 1);
    if (e.key === 'Home') nextIndex = 0;
    if (e.key === 'End') nextIndex = items.length - 1;

    e.preventDefault();
    if (nextIndex === currentIndex) return;

    items[nextIndex].focus();
    aabActivatePlaylistVideo(items[nextIndex]);
});

document.addEventListener('click', function (e) {
    const replyButton = e.target.closest('[data-aab-comment-reply]');
    if (!replyButton) return;

    const comments = replyButton.closest('.aab-comments-wrap');
    const parentField = comments ? comments.querySelector('#comment_parent') : null;
    const textarea = comments ? comments.querySelector('#comment') : null;
    const status = comments ? comments.querySelector('.aab-comment-form-status') : null;

    if (!comments || !parentField || !textarea) return;

    const commentId = replyButton.getAttribute('data-aab-comment-reply') || '0';
    const author = replyButton.getAttribute('data-aab-comment-author') || '';
    const isActive = parentField.value === commentId;
    const nextParent = isActive ? '0' : commentId;

    parentField.value = nextParent;

    comments.querySelectorAll('[data-aab-comment-reply]').forEach(function (button) {
        const isCurrent = nextParent !== '0' && button.getAttribute('data-aab-comment-reply') === nextParent;
        button.setAttribute('aria-expanded', String(isCurrent));
    });

    if (status) {
        status.textContent = nextParent === '0'
            ? ''
            : 'در حال پاسخ به ' + author + '. پاسخ خود را بنویسید و ارسال کنید.';
    }

    if (!isActive) {
        textarea.focus({ preventScroll: true });
        textarea.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'center'
        });
    }
});

function aabInitGalleryLightbox() {
    let lightboxEl = document.querySelector('.aab-lightbox');
    if (!lightboxEl) {
        lightboxEl = document.createElement('div');
        lightboxEl.className = 'aab-lightbox';
        lightboxEl.setAttribute('role', 'dialog');
        lightboxEl.setAttribute('aria-modal', 'true');
        lightboxEl.setAttribute('aria-label', 'بزرگ‌نمایی تصویر');
        lightboxEl.innerHTML = '<div class="aab-lightbox__dialog">' +
            '<button type="button" class="aab-lightbox__close" aria-label="بستن">&times;</button>' +
            '<span class="aab-lightbox__counter"></span>' +
            '<button type="button" class="aab-lightbox__nav aab-lightbox__nav--prev" aria-label="تصویر قبلی">&#10095;</button>' +
            '<img class="aab-lightbox__img" src="" alt="">' +
            '<button type="button" class="aab-lightbox__nav aab-lightbox__nav--next" aria-label="تصویر بعدی">&#10094;</button>' +
            '<div class="aab-lightbox__caption"></div>' +
            '</div>';
        document.body.appendChild(lightboxEl);
    }

    const imgEl = lightboxEl.querySelector('.aab-lightbox__img');
    const captionEl = lightboxEl.querySelector('.aab-lightbox__caption');
    const counterEl = lightboxEl.querySelector('.aab-lightbox__counter');
    const prevBtn = lightboxEl.querySelector('.aab-lightbox__nav--prev');
    const nextBtn = lightboxEl.querySelector('.aab-lightbox__nav--next');
    const closeBtn = lightboxEl.querySelector('.aab-lightbox__close');

    let currentItems = [];
    let currentIndex = 0;

    const toFa = (num) => String(num).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });

    const showItem = (idx) => {
        if (!currentItems.length) return;
        currentIndex = (idx + currentItems.length) % currentItems.length;
        const item = currentItems[currentIndex];
        const src = item.getAttribute('href');
        const caption = item.getAttribute('data-caption') || '';
        const img = item.querySelector('img');
        const alt = img ? img.getAttribute('alt') || '' : '';

        imgEl.src = src;
        imgEl.alt = alt;
        captionEl.textContent = caption;
        captionEl.hidden = !caption;

        if (currentItems.length > 1) {
            counterEl.textContent = toFa(currentIndex + 1) + ' از ' + toFa(currentItems.length);
            prevBtn.hidden = false;
            nextBtn.hidden = false;
        } else {
            counterEl.textContent = '';
            prevBtn.hidden = true;
            nextBtn.hidden = true;
        }
    };

    const open = (items, startIdx) => {
        currentItems = items;
        showItem(startIdx);
        lightboxEl.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        lightboxEl.classList.remove('is-open');
        document.body.style.overflow = '';
        imgEl.src = '';
    };

    document.addEventListener('click', function (e) {
        const link = e.target.closest('[data-aab-lightbox-item]');
        if (!link) return;
        e.preventDefault();
        const gallery = link.closest('[data-aab-gallery]');
        const items = gallery ? Array.from(gallery.querySelectorAll('[data-aab-lightbox-item]')) : [link];
        const idx = items.indexOf(link);
        open(items, idx >= 0 ? idx : 0);
    });

    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', function () { showItem(currentIndex - 1); });
    nextBtn.addEventListener('click', function () { showItem(currentIndex + 1); });

    lightboxEl.addEventListener('click', function (e) {
        if (e.target === lightboxEl) close();
    });

    document.addEventListener('keydown', function (e) {
        if (!lightboxEl.classList.contains('is-open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') showItem(currentIndex - 1);
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') showItem(currentIndex + 1);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        aabInitGalleryLightbox();
        aabInitGallerySlider();
    });
} else {
    aabInitGalleryLightbox();
    aabInitGallerySlider();
}


function aabInitGallerySlider() {
    document.querySelectorAll('.aab-gallery-thumbs-wrap').forEach(w=>{const row=w.querySelector('.aab-gallery-thumbs'),buttons=()=>Array.from(row.querySelectorAll('[data-aab-gallery-thumb]'));const update=()=>{const all=buttons(),r=row.getBoundingClientRect(),visible=all.map((b,i)=>({i,rect:b.getBoundingClientRect()})).filter(x=>x.rect.right>r.left&&x.rect.left<r.right);w.classList.toggle('is-overflowing',row.scrollWidth>row.clientWidth+2);const first=visible.length?visible[0].i:0,last=visible.length?visible[visible.length-1].i:all.length-1;const prev=w.querySelector('.aab-gallery-nav--prev'),next=w.querySelector('.aab-gallery-nav--next');if(prev)prev.disabled=first===0;if(next)next.disabled=last>=all.length-1;};update();row.addEventListener('scroll',update,{passive:true});if(window.ResizeObserver)new ResizeObserver(update).observe(row);else window.addEventListener('resize',update,{passive:true});});
    document.addEventListener('click',function(e){const nav=e.target.closest('[data-aab-gallery-nav]');if(nav){const row=nav.closest('.aab-gallery-thumbs-wrap').querySelector('.aab-gallery-thumbs');row.scrollBy({left:Number(nav.dataset.aabGalleryNav)*row.clientWidth*.8,behavior:'smooth'});return;}const thumb=e.target.closest('[data-aab-gallery-thumb]');if(!thumb)return;const gallery=thumb.closest('.aab-gallery-slider');if(!gallery)return;const index=thumb.getAttribute('data-aab-gallery-thumb');gallery.querySelectorAll('[data-aab-gallery-thumb]').forEach(t=>t.classList.remove('is-active'));thumb.classList.add('is-active');gallery.querySelectorAll('[data-aab-gallery-main-item]').forEach(m=>m.classList.remove('is-active'));const main=gallery.querySelector('[data-aab-gallery-main-item="'+index+'"]');if(main)main.classList.add('is-active');});
}

(function(){
 const players=Array.from(document.querySelectorAll('[data-aab-player]'));let active=null;
 const timeText=n=>{if(!Number.isFinite(n)||n<0)n=0;const m=Math.floor(n/60),s=Math.floor(n%60);return '−'+m+':'+String(s).padStart(2,'0');};
 players.forEach(p=>{
  const audio=p.querySelector('audio'),btn=p.querySelector('[data-aab-play]'),seek=p.querySelector('[data-aab-seek]'),remain=p.querySelector('[data-aab-remaining]'),status=p.querySelector('[data-aab-audio-status]');
  if(!audio||!btn||!seek||!remain)return;
  p.classList.add('aab-player-enhanced');
  const play=()=>{const result=audio.play();if(result&&typeof result.catch==='function')result.catch(()=>{if(status)status.textContent='پخش این فایل ممکن نشد. اتصال یا دسترسی فایل را بررسی کنید.';});};
  const update=()=>{const d=audio.duration||0;seek.value=d?String(Math.round(audio.currentTime/d*1000)):0;remain.textContent=timeText(d-audio.currentTime);btn.textContent=audio.paused?'▶':'Ⅱ';btn.setAttribute('aria-label',audio.paused?'پخش':'مکث');};
  btn.addEventListener('click',()=>audio.paused?play():audio.pause());
  audio.addEventListener('play',()=>{players.forEach(other=>{if(other!==p)other.querySelector('audio')?.pause();});active=p;if(status)status.textContent='';update();});
  ['pause','timeupdate','durationchange','ended'].forEach(ev=>audio.addEventListener(ev,update));
  seek.addEventListener('input',()=>{if(audio.duration)audio.currentTime=Number(seek.value)/1000*audio.duration;});
  seek.addEventListener('keydown',e=>{if(['ArrowRight','ArrowUp'].includes(e.key)){e.preventDefault();audio.currentTime=Math.min(audio.duration||0,audio.currentTime+5);}if(['ArrowLeft','ArrowDown'].includes(e.key)){e.preventDefault();audio.currentTime=Math.max(0,audio.currentTime-5);}});
  p.addEventListener('keydown',e=>{if(e.target.matches('input,textarea,select,[contenteditable=true]'))return;if(e.code==='Space'){e.preventDefault();audio.paused?play():audio.pause();}});
  p._aabPlay=play;update();
 });
 if('mediaSession' in navigator){
  function metadata(p){if(typeof window.MediaMetadata!=='function')return;navigator.mediaSession.metadata=new MediaMetadata({title:p.dataset.title||'فایل صوتی مقاله',artist:p.dataset.artist||'',artwork:p.dataset.artwork?[{src:p.dataset.artwork,sizes:'150x150'}]:[]});}
  ['play','pause','seekbackward','seekforward','seekto'].forEach(action=>{try{navigator.mediaSession.setActionHandler(action,d=>{const p=active;if(!p)return;const a=p.querySelector('audio');if(action==='play')p._aabPlay();if(action==='pause')a.pause();if(action==='seekbackward')a.currentTime=Math.max(0,a.currentTime-(d.seekOffset||10));if(action==='seekforward')a.currentTime=Math.min(a.duration||Infinity,a.currentTime+(d.seekOffset||10));if(action==='seekto')a.currentTime=d.seekTime;});}catch(e){}});
  players.forEach(p=>p.querySelector('audio')?.addEventListener('play',()=>metadata(p)));
 }
})();

/* Table of contents: smooth jump, branches, scrollspy, mobile preview + pill. */
(function () {
    const DESKTOP = window.matchMedia('(min-width: 1025px)');
    const reduceMotion = function () {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    };
    const cssPx = function (name, fallback) {
        const value = parseFloat(getComputedStyle(document.documentElement).getPropertyValue(name));
        return Number.isFinite(value) ? value : fallback;
    };
    const normalize = function (value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    function scrollToHeading(heading) {
        const top = heading.getBoundingClientRect().top + window.scrollY - cssPx('--aab-scroll-offset', 110);
        window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion() ? 'auto' : 'smooth' });
        if (!heading.hasAttribute('tabindex')) heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
    }

    // Standalone TOCs may point at headings the theme rendered without our ids.
    function resolveTargets(toc) {
        let headings = null;
        toc.querySelectorAll('[data-aab-toc-target]').forEach(function (link) {
            const id = link.getAttribute('data-aab-toc-target');
            if (document.getElementById(id)) return;
            headings = headings || Array.from(document.querySelectorAll('h2,h3,h4,h5,h6')).filter(function (h) {
                return !h.closest('.aab-toc');
            });
            const text = normalize(link.querySelector('.aab-toc__text').textContent);
            const match = headings.find(function (h) { return normalize(h.textContent) === text; });
            if (!match) return;
            headings.splice(headings.indexOf(match), 1);
            if (!match.id) match.id = id;
            link.setAttribute('href', '#' + match.id);
            link.setAttribute('data-aab-toc-target', match.id);
        });
    }

    function initToc(toc) {
        resolveTargets(toc);

        const links = Array.from(toc.querySelectorAll('.aab-toc__link'));
        const scroller = toc.querySelector('.aab-toc__scroller');
        const more = toc.querySelector('[data-aab-toc-more]');
        const progress = toc.querySelector('[data-aab-toc-progress]');
        const article = toc.closest('.aab-article');
        const content = (article && article.querySelector('.aab-content')) || article || document.querySelector('.aab-content');
        const entries = links.map(function (link) {
            return { link: link, item: link.closest('.aab-toc__item'), heading: document.getElementById(link.getAttribute('data-aab-toc-target')), top: 0 };
        }).filter(function (entry) { return entry.heading; });
        let current = null;

        function setBranch(item, open) {
            item.classList.toggle('is-open', open);
            const button = item.querySelector(':scope > .aab-toc__row > .aab-toc__branch');
            if (button) button.setAttribute('aria-expanded', String(open));
        }
        function ancestors(item) {
            const list = [];
            let parent = item.parentElement.closest('.aab-toc__item');
            while (parent && toc.contains(parent)) {
                list.push(parent);
                parent = parent.parentElement.closest('.aab-toc__item');
            }
            return list;
        }

        // Mobile preview: clamp only when the list is actually taller than the preview.
        function setClamped(clamped) {
            toc.classList.toggle('is-clamped', clamped);
            if (more) more.setAttribute('aria-expanded', String(!clamped));
        }
        function refreshMore() {
            if (!more) return;
            if (DESKTOP.matches || toc.classList.contains('is-docked')) {
                more.hidden = true;
                return;
            }
            const wasClamped = toc.classList.contains('is-clamped');
            setClamped(true);
            const overflowing = scroller.scrollHeight > scroller.clientHeight + 8;
            setClamped(overflowing && wasClamped);
            more.hidden = !overflowing;
        }
        if (more) {
            more.addEventListener('click', function () {
                const expand = toc.classList.contains('is-clamped');
                setClamped(!expand);
                if (!expand && toc.getBoundingClientRect().top < 0) toc.scrollIntoView({ block: 'start', behavior: reduceMotion() ? 'auto' : 'smooth' });
            });
        }
        // Keyboard users tabbing into the hidden part of the preview get the full list.
        toc.addEventListener('focusin', function (e) {
            if (toc.classList.contains('is-clamped') && e.target.closest('.aab-toc__scroller') && !DESKTOP.matches) setClamped(false);
        });

        toc.addEventListener('click', function (e) {
            const branch = e.target.closest('.aab-toc__branch');
            if (branch) {
                const item = branch.closest('.aab-toc__item');
                item.dataset.userToggled = '1';
                setBranch(item, !item.classList.contains('is-open'));
                return;
            }
            const link = e.target.closest('.aab-toc__link');
            if (!link || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const heading = document.getElementById(link.getAttribute('data-aab-toc-target'));
            if (!heading) return;
            e.preventDefault();
            scrollToHeading(heading);
            history.pushState(null, '', '#' + encodeURIComponent(heading.id));
        });

        // Scrollspy from cached offsets: no layout reads per scroll frame except the content box.
        function measure() {
            entries.forEach(function (entry) {
                entry.top = entry.heading.getBoundingClientRect().top + window.scrollY;
            });
        }
        function keepVisible(link) {
            if (!scroller || scroller.scrollHeight <= scroller.clientHeight) return;
            const linkBox = link.getBoundingClientRect();
            const box = scroller.getBoundingClientRect();
            if (linkBox.top < box.top + 8 || linkBox.bottom > box.bottom - 8) {
                scroller.scrollTo({ top: scroller.scrollTop + linkBox.top - box.top - box.height / 3, behavior: reduceMotion() ? 'auto' : 'smooth' });
            }
        }
        function setCurrent(entry) {
            if (entry === current) return;
            if (current) {
                current.link.classList.remove('is-active');
                current.link.removeAttribute('aria-current');
                ancestors(current.item).forEach(function (item) {
                    item.classList.remove('is-ancestor');
                    if (!item.dataset.userToggled) setBranch(item, false);
                });
            }
            current = entry;
            if (entry) {
                entry.link.classList.add('is-active');
                entry.link.setAttribute('aria-current', 'location');
                ancestors(entry.item).forEach(function (item) {
                    item.classList.add('is-ancestor');
                    setBranch(item, true);
                });
                if (toc.classList.contains('is-docked') || DESKTOP.matches) keepVisible(entry.link);
            }
            toc.dispatchEvent(new CustomEvent('aab:toc-current', { detail: entry ? normalize(entry.link.querySelector('.aab-toc__text').textContent) : '' }));
        }
        function update() {
            const line = window.scrollY + cssPx('--aab-scroll-offset', 110) + 12;
            let next = null;
            for (let i = 0; i < entries.length && entries[i].top <= line; i++) next = entries[i];
            setCurrent(next);

            if (content) {
                const box = content.getBoundingClientRect();
                const total = box.height - window.innerHeight * 0.6;
                const ratio = total > 0 ? Math.min(1, Math.max(0, -box.top / total)) : 0;
                if (progress) progress.style.transform = 'scaleX(' + ratio.toFixed(3) + ')';
                toc.dispatchEvent(new CustomEvent('aab:toc-progress', { detail: ratio }));
            }
        }

        let ticking = false;
        const onScroll = function () {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () { ticking = false; update(); });
        };
        const remeasure = function () { measure(); refreshMore(); update(); };

        measure();
        refreshMore();
        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('load', remeasure);
        DESKTOP.addEventListener ? DESKTOP.addEventListener('change', remeasure) : DESKTOP.addListener(remeasure);
        if (window.ResizeObserver && content) {
            let pending = 0;
            new ResizeObserver(function () {
                cancelAnimationFrame(pending);
                pending = requestAnimationFrame(remeasure);
            }).observe(content);
        } else {
            window.addEventListener('resize', remeasure, { passive: true });
        }

        return { toc: toc, entries: entries };
    }

    // Elementor/theme sidebars that use [aagah_article_sidebar]: dock there on desktop.
    function initDock(toc) {
        const dest = document.querySelector('[data-aab-toc-destination]');
        if (!dest || toc.closest('.aab-has-toc')) return;
        const marker = document.createComment('aab-toc');
        toc.parentNode.insertBefore(marker, toc);
        const place = function () {
            if (DESKTOP.matches) {
                if (toc.parentNode !== dest) dest.appendChild(toc);
            } else if (toc.parentNode !== marker.parentNode) {
                marker.parentNode.insertBefore(toc, marker.nextSibling);
            }
            toc.classList.toggle('is-docked', DESKTOP.matches);
        };
        place();
        DESKTOP.addEventListener ? DESKTOP.addEventListener('change', place) : DESKTOP.addListener(place);
    }

    // Mobile pill showing the current section once the TOC is out of view.
    function initFab(toc) {
        if (!window.IntersectionObserver) return;
        const content = toc.closest('.aab-article');
        if (!content) return;
        const fab = document.createElement('button');
        fab.type = 'button';
        fab.className = 'aab-toc-fab';
        fab.setAttribute('aria-label', 'بازگشت به فهرست مطالب');
        fab.innerHTML = '<span class="aab-toc-fab__icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/></svg></span>' +
            '<span class="aab-toc-fab__text"><span class="aab-toc-fab__label">فهرست مطالب</span><span class="aab-toc-fab__current"></span></span><span class="aab-toc-fab__bar"></span>';
        document.body.appendChild(fab);
        const currentEl = fab.querySelector('.aab-toc-fab__current');
        const bar = fab.querySelector('.aab-toc-fab__bar');
        let tocVisible = true;
        let contentVisible = false;
        const sync = function () {
            fab.classList.toggle('is-visible', !tocVisible && contentVisible && currentEl.textContent !== '');
        };
        toc.addEventListener('aab:toc-current', function (e) { currentEl.textContent = e.detail; sync(); });
        toc.addEventListener('aab:toc-progress', function (e) { bar.style.transform = 'scaleX(' + e.detail.toFixed(3) + ')'; });
        new IntersectionObserver(function (list) { tocVisible = list[0].isIntersecting; sync(); }).observe(toc);
        new IntersectionObserver(function (list) { contentVisible = list[0].isIntersecting; sync(); }).observe(content);
        fab.addEventListener('click', function () {
            toc.classList.remove('is-clamped');
            const active = toc.querySelector('.aab-toc__link.is-active') || toc.querySelector('.aab-toc__link');
            const top = toc.getBoundingClientRect().top + window.scrollY - cssPx('--aab-scroll-offset', 110);
            window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion() ? 'auto' : 'smooth' });
            if (active) active.focus({ preventScroll: true });
        });
    }

    function boot() {
        document.querySelectorAll('[data-aab-toc]').forEach(function (toc, index) {
            initToc(toc);
            if (index === 0 && !toc.classList.contains('aab-toc--standalone')) initFab(toc);
            initDock(toc);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

/* Comment form: prevent double submit and show progress. */
document.addEventListener('submit', function (e) {
    const form = e.target.closest('.aab-comment-form');
    if (!form) return;
    const button = form.querySelector('.aab-comment-submit');
    if (!button) return;
    if (button.getAttribute('aria-busy') === 'true') {
        e.preventDefault();
        return;
    }
    button.setAttribute('aria-busy', 'true');
    button.dataset.label = button.textContent;
    button.textContent = 'در حال ارسال…';
    window.addEventListener('pageshow', function () {
        button.removeAttribute('aria-busy');
        button.textContent = button.dataset.label;
    }, { once: true });
});
