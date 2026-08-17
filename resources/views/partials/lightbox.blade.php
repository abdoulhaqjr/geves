{{-- Lightbox images + modale vidéo (pilotés par public.js). --}}
<div class="lb-overlay" id="lightbox" role="dialog" aria-modal="true" aria-label="Galerie">
    <div class="lb-inner">
        <button type="button" class="lb-close" id="lbClose" aria-label="Fermer">×</button>
        <button type="button" class="lb-prev" id="lbPrev" aria-label="Précédent">‹</button>
        <button type="button" class="lb-next" id="lbNext" aria-label="Suivant">›</button>
        <div id="lbMedia"></div>
        <div class="lb-cap" id="lbCap"></div>
    </div>
</div>

<div class="lb-overlay" id="videoLightbox" role="dialog" aria-modal="true" aria-label="Vidéo">
    <div class="lb-inner">
        <button type="button" class="lb-close" id="vidClose" aria-label="Fermer">×</button>
        <div class="vid-frame-wrap">
            <iframe id="vidFrame" src="" title="Vidéo" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            <video id="vidNative" controls playsinline style="display:none;"></video>
        </div>
        <div class="lb-cap" id="vidCap"></div>
    </div>
</div>
