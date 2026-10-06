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

document.addEventListener('click', function (e) {
    const tocLink = e.target.closest('.aab-toc-link[data-aab-toc-target]');
    if (!tocLink) return;

    if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const targetId = tocLink.getAttribute('data-aab-toc-target');
    const target = targetId ? document.getElementById(targetId) : null;

    if (!target) return;

    e.preventDefault();
    target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
    });
    window.history.pushState(null, '', '#' + encodeURIComponent(targetId));
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

function aabInitTocTargets() {
    const normalizeText = function (value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    const headings = Array.from(document.querySelectorAll('h2, h3, h4')).filter(function (heading) {
        return !heading.closest('.aab-toc');
    });

    document.querySelectorAll('.aab-toc').forEach(function (toc) {
        const claimedHeadings = new Set();

        toc.querySelectorAll('.aab-toc-link[data-aab-toc-target]').forEach(function (link) {
            const targetId = link.getAttribute('data-aab-toc-target');
            if (!targetId || document.getElementById(targetId)) return;

            const item = link.closest('.aab-toc-item');
            const levelMatch = item ? item.className.match(/aab-toc-level-([2-4])/) : null;
            const expectedTag = levelMatch ? 'H' + levelMatch[1] : '';
            const expectedText = normalizeText(link.textContent);

            const heading = headings.find(function (candidate) {
                return !claimedHeadings.has(candidate) &&
                    (!expectedTag || candidate.tagName === expectedTag) &&
                    normalizeText(candidate.textContent) === expectedText;
            });

            if (!heading) return;

            claimedHeadings.add(heading);
            if (heading.id) {
                link.setAttribute('href', '#' + heading.id);
                link.setAttribute('data-aab-toc-target', heading.id);
            } else {
                heading.id = targetId;
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        aabInitResponsiveTables();
        aabInitTocTargets();
    });
} else {
    aabInitResponsiveTables();
    aabInitTocTargets();
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

function aabInitTocScrollspy() {
    document.documentElement.classList.add('js');
    const toc = document.querySelector('[data-aab-toc]');
    if (!toc) return;
    const list = toc.querySelector('.aab-toc-list');
    const items = Array.from(toc.querySelectorAll('[data-aab-toc-item]'));
    const links = Array.from(toc.querySelectorAll('.aab-toc-link[data-aab-toc-target]'));
    const progressBar = toc.querySelector('.aab-toc-progress-bar');
    const article = document.querySelector('.aab-content') || document.querySelector('.aab-article');
    const details = toc.querySelector('.aab-toc-disclosure');
    const origin = toc.parentNode, marker = document.createComment('aab-toc-origin');
    origin.insertBefore(marker, toc);
    const desktop = window.matchMedia('(min-width: 1025px)');
    let lastDesktop = desktop.matches;
    let desktopOpen = desktop.matches ? true : null;
    let mobileOpen = details ? details.open : false;
    if (details && desktop.matches) details.open = true;
    function arrange() {
        const dest = document.querySelector('[data-aab-toc-destination]');
        const side = dest ? dest.closest('.aab-layout-side') : null;
        if (side) side.style.display = desktop.matches ? '' : 'none';
        if (desktop.matches && dest) {
            if (toc.parentNode !== dest) dest.appendChild(toc);
            toc.classList.add('aab-toc-moved');
        } else {
            if (marker.parentNode && toc.parentNode !== marker.parentNode) marker.parentNode.insertBefore(toc, marker.nextSibling);
            toc.classList.remove('aab-toc-moved');
        }
    }
    arrange();
    if (details) details.addEventListener('toggle', () => { if (desktop.matches) desktopOpen = details.open; else mobileOpen = details.open; });
    const changeMode = () => {
        if (details) {
            if (lastDesktop) desktopOpen = details.open; else mobileOpen = details.open;
            lastDesktop = desktop.matches;
            details.open = desktop.matches ? (desktopOpen === null ? true : desktopOpen) : mobileOpen;
            if (desktop.matches && desktopOpen === null) desktopOpen = true;
        }
        arrange();
    };
    desktop.addEventListener ? desktop.addEventListener('change', changeMode) : desktop.addListener(changeMode);
    let stack=[];
    items.forEach((item,i)=>{
        const level=Number(item.dataset.level)||2;
        while(stack.length && stack[stack.length-1].level>=level) stack.pop();
        const parent=stack[stack.length-1];
        if(parent){item.dataset.parentIndex=parent.index;parent.item.classList.add('has-children');}
        stack.push({level,index:i,item});
    });
    items.forEach(item=>{
        if(!item.classList.contains('has-children'))return;
        const button=item.querySelector(':scope > .aab-toc-branch-toggle');if(!button)return;
        button.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();setBranch(item,!item.classList.contains('is-expanded'));});
    });
    function setBranch(item,open){item.classList.toggle('is-expanded',open);const b=item.querySelector(':scope > .aab-toc-branch-toggle');if(b){b.setAttribute('aria-expanded',String(open));b.setAttribute('aria-label',open?'بستن زیرعنوان‌ها':'باز کردن زیرعنوان‌ها');}items.forEach(child=>{if(child.dataset.parentIndex===undefined)return;let parent=items[Number(child.dataset.parentIndex)],visible=true;while(parent){if(!parent.classList.contains('is-expanded'))visible=false;parent=parent.dataset.parentIndex!==undefined?items[Number(parent.dataset.parentIndex)]:null;}child.hidden=!visible;});}
    items.forEach(item=>{if(item.dataset.parentIndex!==undefined)item.hidden=true;});
    document.addEventListener('click',e=>{const link=e.target.closest('.aab-toc-link[data-aab-toc-target]');if(!link)return;let item=link.closest('[data-aab-toc-item]');while(item&&item.dataset.parentIndex!==undefined){const parent=items[Number(item.dataset.parentIndex)];setBranch(parent,true);item=parent;}});
    const update=()=>{
        if(progressBar&&article){const r=article.getBoundingClientRect(),total=r.height-innerHeight;if(total>0)progressBar.style.width=Math.min(100,Math.max(0,(-r.top/total)*100))+'%';}
        let current=null;links.forEach(link=>{const h=document.getElementById(link.dataset.aabTocTarget);if(h&&h.getBoundingClientRect().top<=140)current=link;});
        items.forEach(item=>item.classList.remove('is-ancestor'));
        links.forEach(link=>{const active=link===current;link.classList.toggle('is-active',active);if(active)link.setAttribute('aria-current','location');else link.removeAttribute('aria-current');});
        if(current){let item=current.closest('[data-aab-toc-item]');while(item&&item.dataset.parentIndex!==undefined){const parent=items[Number(item.dataset.parentIndex)];parent.classList.add('is-ancestor');setBranch(parent,true);item=parent;}}
    };
    let tick=false;window.addEventListener('scroll',()=>{if(!tick){requestAnimationFrame(()=>{update();tick=false;});tick=true;}},{passive:true});window.addEventListener('resize',arrange,{passive:true});update();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        aabInitGalleryLightbox();
        aabInitGallerySlider();
        aabInitTocScrollspy();
    });
} else {
    aabInitGalleryLightbox();
    aabInitGallerySlider();
    aabInitTocScrollspy();
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
