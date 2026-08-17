/**
 * Interactions du site public (accueil, archives, déroulement) :
 * - menu mobile (burger) ;
 * - galerie + lightbox (images) et modale vidéo (YouTube/Vimeo/embed ou fichier).
 *
 * Les vignettes sont rendues côté serveur (Blade) ; la liste complète des médias
 * est fournie via un <script type="application/json" id="galleryData">.
 */
function initBurger() {
    const burger = document.getElementById('burger');
    const nav = document.getElementById('mainNav');
    if (!burger || !nav) return;
    burger.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

function initGallery() {
    const dataEl = document.getElementById('galleryData');
    const gallery = document.getElementById('gallery');
    if (!dataEl || !gallery) return;

    let media = [];
    try { media = JSON.parse(dataEl.textContent || '[]'); } catch (e) { return; }
    if (!media.length) return;

    const lb = document.getElementById('lightbox');
    const lbMedia = document.getElementById('lbMedia');
    const lbCap = document.getElementById('lbCap');
    const videoLb = document.getElementById('videoLightbox');
    const vidFrame = document.getElementById('vidFrame');
    const vidNative = document.getElementById('vidNative');
    const vidCap = document.getElementById('vidCap');
    let idx = 0;

    function closeVideo() {
        if (!videoLb) return;
        videoLb.classList.remove('open');
        if (vidFrame) { vidFrame.src = ''; vidFrame.style.display = ''; }
        if (vidNative) {
            try { vidNative.pause(); } catch (e) { /* noop */ }
            vidNative.removeAttribute('src'); vidNative.load(); vidNative.style.display = 'none';
        }
        document.body.style.overflow = '';
    }

    function openVideo(url, caption) {
        if (!videoLb) return;
        if (lb) { lb.classList.remove('open'); if (lbMedia) lbMedia.innerHTML = ''; }
        if (vidCap) vidCap.textContent = caption || '';
        url = url || '';
        const isEmbed = /youtube\.com|youtu\.be|vimeo\.com|\/embed\//i.test(url);
        if (isEmbed) {
            if (vidNative) { try { vidNative.pause(); } catch (e) { /* noop */ } vidNative.style.display = 'none'; }
            if (vidFrame) {
                vidFrame.style.display = 'block';
                let src = url;
                if (src.indexOf('watch?v=') >= 0) src = 'https://www.youtube.com/embed/' + src.split('watch?v=')[1].split('&')[0] + '?autoplay=1';
                else if (src.indexOf('youtu.be/') >= 0) src = 'https://www.youtube.com/embed/' + src.split('youtu.be/')[1].split('?')[0] + '?autoplay=1';
                else if (src.indexOf('autoplay') < 0) src += (src.indexOf('?') >= 0 ? '&' : '?') + 'autoplay=1';
                vidFrame.src = src;
            }
        } else {
            if (vidFrame) { vidFrame.src = ''; vidFrame.style.display = 'none'; }
            if (vidNative) {
                vidNative.style.display = 'block'; vidNative.src = url;
                const pr = vidNative.play && vidNative.play(); if (pr && pr.catch) pr.catch(() => {});
            }
        }
        videoLb.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function openAt(i) {
        idx = (i + media.length) % media.length;
        const m = media[idx];
        if (!m) return;
        if (m.type === 'video') { openVideo(m.src, m.caption); return; }
        closeVideo();
        if (lbMedia) lbMedia.innerHTML = '<img src="' + m.src + '" alt="">';
        if (lbCap) lbCap.textContent = m.caption || '';
        if (lb) lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeImg() {
        if (lb) lb.classList.remove('open');
        if (lbMedia) lbMedia.innerHTML = '';
        if (!videoLb || !videoLb.classList.contains('open')) document.body.style.overflow = '';
    }

    gallery.querySelectorAll('.gallery-item:not(.is-more)').forEach((el) => {
        el.addEventListener('click', () => {
            const i = parseInt(el.getAttribute('data-idx'), 10);
            openAt(Number.isNaN(i) ? 0 : i);
        });
    });
    const moreBtn = document.getElementById('galleryMore');
    if (moreBtn) moreBtn.addEventListener('click', () => openAt(parseInt(moreBtn.getAttribute('data-start'), 10) || 0));

    const bind = (id, fn) => { const el = document.getElementById(id); if (el) el.addEventListener('click', fn); };
    bind('lbClose', closeImg);
    bind('lbPrev', () => openAt(idx - 1));
    bind('lbNext', () => openAt(idx + 1));
    bind('vidClose', closeVideo);
    if (lb) lb.addEventListener('click', (e) => { if (e.target === lb) closeImg(); });
    if (videoLb) videoLb.addEventListener('click', (e) => { if (e.target === videoLb) closeVideo(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (videoLb && videoLb.classList.contains('open')) closeVideo();
            else if (lb && lb.classList.contains('open')) closeImg();
            return;
        }
        if (!lb || !lb.classList.contains('open')) return;
        if (e.key === 'ArrowLeft') openAt(idx - 1);
        if (e.key === 'ArrowRight') openAt(idx + 1);
    });
}

function initFilters() {
    const wrap = document.getElementById('filters');
    const grid = document.getElementById('editionsGrid');
    if (!wrap || !grid) return;
    wrap.querySelectorAll('button[data-filter]').forEach((btn) => {
        btn.addEventListener('click', () => {
            wrap.querySelectorAll('button').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const f = btn.getAttribute('data-filter');
            grid.querySelectorAll('.edition-card').forEach((card) => {
                const t = card.getAttribute('data-type');
                card.style.display = (f === 'all' || t === f) ? '' : 'none';
            });
        });
    });
}

function init() { initBurger(); initGallery(); initFilters(); }

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
