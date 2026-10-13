/* UKC editor v2 - syntax verified */
(function () {
  var page = document.getElementById('page');
  var layersList = document.getElementById('layersList');
  var styleBar = document.getElementById('styleBar');
  var guidesEl = document.getElementById('guides');
  var selected = null;
  var elId = 0;
  var groupId = 0;
  var drag = null;
  var resize = null;
  var groups = {};
  var history = [];
  var historyIndex = -1;
  var historyLock = false;
  // Modifications non enregistrées : sert à prévenir avant de quitter la page.
  // Posé par pushHistory (tout changement passe par là) + les réglages de la barre
  // d'outils qui ne génèrent pas d'entrée d'historique. Remis à zéro après
  // l'hydratation initiale et à chaque enregistrement réussi.
  var dirty = false;
  function markDirty() { dirty = true; }
  var SNAP = 6;
  // ⚠️ Le QR de vérification encodait une adresse FIXE et sans identifiant
  // (« /attestation-verify ») : scanner deux attestations différentes menait au
  // même endroit, qui ne vérifiait rien. Il encode désormais le JETON, que le
  // rendu remplace par l'adresse de l'attestation délivrée.
  var DEFAULT_QR_URL = '{verify_url}';
  var zoomLevel = 1;

  function qrImageUrl(data, size) {
    size = size || 120;
    var value = data || DEFAULT_QR_URL;
    // Un jeton ne se scanne pas : l'aperçu montre l'adresse d'exemple, pour que
    // l'image ressemble à celle du document délivré.
    if (value === DEFAULT_QR_URL) {
      value = ((window.UKC_EDITOR || {}).vars || {})['verify_url'] || value;
    }
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&margin=4&data=' + encodeURIComponent(value);
  }

  function setQrContent(el, url) {
    el.dataset.qrUrl = url || DEFAULT_QR_URL;
    el.innerHTML = '';
    var img = document.createElement('img');
    img.alt = 'QR';
    img.src = qrImageUrl(el.dataset.qrUrl, 160);
    img.onerror = function () {
      el.innerHTML = '<div class="qr-fallback">QR<br>CODE</div>';
      var rz = document.createElement('div');
      rz.className = 'resize';
      el.appendChild(rz);
    };
    el.appendChild(img);
    var rz = document.createElement('div');
    rz.className = 'resize';
    el.appendChild(rz);
  }

  function applyZoom() {
    var inner = document.getElementById('canvasInner');
    if (inner) inner.style.transform = 'scale(' + zoomLevel + ')';
    var lab = document.getElementById('zoomLabel');
    if (lab) lab.textContent = Math.round(zoomLevel * 100) + '%';
  }

  var inputModalCb = null;
  function openInputModal(opts) {
    opts = opts || {};
    var modal = document.getElementById('inputModal');
    document.getElementById('inputModalTitle').textContent = opts.title || 'Saisir une valeur';
    document.getElementById('inputModalHint').textContent = opts.hint || '';
    var field = document.getElementById('inputModalField');
    field.value = opts.value || '';
    field.placeholder = opts.placeholder || '';
    inputModalCb = opts.onOk || null;
    modal.classList.add('open');
    setTimeout(function () { field.focus(); field.select(); }, 50);
  }
  function closeInputModal() {
    document.getElementById('inputModal').classList.remove('open');
    inputModalCb = null;
  }

  function pruneUploads() {
    // Remove upload thumbs whose images are no longer on canvas
    var list = document.getElementById('uploadList');
    if (!list) return;
    var usedSrcs = {};
    getEls().forEach(function (el) {
      var img = el.querySelector('img');
      if (img && img.src) usedSrcs[img.src] = true;
      if (el.dataset.uploadSrc) usedSrcs[el.dataset.uploadSrc] = true;
    });
    Array.prototype.slice.call(list.querySelectorAll('.ed-thumb[data-src]')).forEach(function (th) {
      var src = th.dataset.src;
      if (src && !usedSrcs[src]) {
        // keep if never placed; only remove if was placed then deleted
        if (th.dataset.wasUsed === '1') th.remove();
      }
    });
  }

  var DEFAULT_ELEMENTS = [
    { type: 'logo', id: 'logo-uk', label: 'Logo UK', x: 40, y: 28, w: 160, html: '<div class="mark">UK</div> UNIVERSITÉ DE KARA' },
    { type: 'logo', id: 'logo-up', label: 'Logo UP', x: 680, y: 28, w: 180, html: 'UNIVERSITÉ DE PARAKOU' },
    { type: 'text', id: 'title', label: 'Titre', x: 80, y: 100, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
    { type: 'text', id: 'intro', label: 'Intro', x: 100, y: 160, w: 700, cls: 'sub', text: 'Je soussigné, Prof. Padabo KADOUZA, Président du Comité d’Organisation, atteste que' },
    { type: 'text', id: 'name', label: 'Destinataire', x: 150, y: 210, w: 600, cls: 'name attr', text: '[recipient.name]' },
    { type: 'text', id: 'body', label: 'Corps', x: 80, y: 270, w: 740, cls: 'sub', text: 'a suivi avec diligence la formation sur la valorisation des résultats de recherche, tenue en marge du 6e colloque scientifique international de l’Université de Kara, en collaboration avec l’Université de Parakou (06 - 10 octobre 2025, Kara).' },
    { type: 'text', id: 'closing', label: 'Formule', x: 80, y: 380, w: 740, cls: 'sub', text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.' },
    { type: 'text', id: 'place', label: 'Lieu / date', x: 520, y: 450, w: 320, cls: 'sub', text: 'Fait à Kara, le 10 octobre 2025' },
    { type: 'text', id: 'role', label: 'Fonction', x: 520, y: 490, w: 320, cls: 'sub', text: 'Le Président du Comité d’Organisation,' },
    { type: 'text', id: 'sign', label: 'Signataire', x: 520, y: 530, w: 320, cls: '', text: 'Prof. Padabo KADOUZA' },
    { type: 'text', id: 'ref', label: 'Référence', x: 40, y: 580, w: 420, cls: 'sub', text: 'Référence : [certificate.uuid]' },
    { type: 'qr', id: 'qr1', label: 'QR Verification', x: 40, y: 500, w: 72, qrUrl: DEFAULT_QR_URL }
  ];

  function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 2200);
  }

  /* ---------- History (undo / redo) ---------- */
  function snapshot() {
    var els = getEls().map(function (el) {
      return {
        id: el.id,
        html: el.innerHTML,
        className: el.className,
        label: el.dataset.label,
        locked: el.dataset.locked,
        hidden: el.dataset.hidden,
        group: el.dataset.group || '',
        blend: el.dataset.blend || 'normal',
        // ⚠️ Sans elle, l'adresse encodée était perdue à la réouverture : le QR
        // gardait l'image d'alors et le jeton ne revenait pas.
        qrUrl: el.dataset.qrUrl || '',
        style: {
          left: el.style.left,
          top: el.style.top,
          width: el.style.width,
          height: el.style.height,
          opacity: el.style.opacity,
          fontSize: el.style.fontSize,
          fontFamily: el.style.fontFamily,
          fontWeight: el.style.fontWeight,
          fontStyle: el.style.fontStyle,
          textDecoration: el.style.textDecoration,
          textAlign: el.style.textAlign,
          color: el.style.color,
          mixBlendMode: el.style.mixBlendMode,
          display: el.style.display,
          background: el.style.background,
          borderRadius: el.style.borderRadius,
          border: el.style.border
        }
      };
    });
    return {
      els: els,
      groups: JSON.parse(JSON.stringify(groups)),
      bg: page.className,
      bgImage: document.getElementById('pageBg').style.backgroundImage || ''
    };
  }

  function pushHistory() {
    if (historyLock) return;
    dirty = true;
    history = history.slice(0, historyIndex + 1);
    history.push(snapshot());
    if (history.length > 60) history.shift();
    else historyIndex++;
    updateUndoButtons();
  }

  function restoreSnapshot(snap) {
    historyLock = true;
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = JSON.parse(JSON.stringify(snap.groups || {}));
    page.className = snap.bg || 'ed-page';
    document.getElementById('pageBg').style.backgroundImage = snap.bgImage || '';
    (snap.els || []).forEach(function (s) {
      var el = document.createElement('div');
      el.id = s.id;
      el.className = s.className;
      el.dataset.label = s.label || s.id;
      el.dataset.locked = s.locked || '0';
      el.dataset.hidden = s.hidden || '0';
      el.dataset.group = s.group || '';
      el.dataset.blend = s.blend || 'normal';
      el.innerHTML = s.html;
      if (s.qrUrl) el.dataset.qrUrl = s.qrUrl;
      Object.keys(s.style || {}).forEach(function (k) {
        if (s.style[k]) el.style[k] = s.style[k];
      });
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div');
        rz.className = 'resize';
        el.appendChild(rz);
      }
      page.appendChild(el);
      // Le QR est redessiné à partir de son adresse : l'image enregistrée peut
      // dater d'une autre adresse, et c'est elle qu'on verrait sinon.
      if (el.classList.contains('qr')) setQrContent(el, el.dataset.qrUrl || DEFAULT_QR_URL);
      bindEl(el);
    });
    selected = null;
    styleBar.classList.add('disabled');
    document.getElementById('lyControls').classList.remove('show');
    refreshLayers();
    historyLock = false;
    updateUndoButtons();
  }

  function undo() {
    if (historyIndex <= 0) return;
    historyIndex--;
    restoreSnapshot(history[historyIndex]);
    toast('Annulé');
  }
  function redo() {
    if (historyIndex >= history.length - 1) return;
    historyIndex++;
    restoreSnapshot(history[historyIndex]);
    toast('Rétabli');
  }
  function updateUndoButtons() {
    document.getElementById('btnUndo').disabled = historyIndex <= 0;
    document.getElementById('btnRedo').disabled = historyIndex >= history.length - 1;
  }

  /* ---------- Alignment guides ---------- */
  function clearGuides() {
    if (guidesEl) guidesEl.innerHTML = '';
  }
  function addGuide(type, pos) {
    var g = document.createElement('div');
    g.className = type === 'x' ? 'ed-guide-x' : 'ed-guide-y';
    if (type === 'x') g.style.top = pos + 'px';
    else g.style.left = pos + 'px';
    guidesEl.appendChild(g);
  }
  function snapDrag(el, x, y) {
    clearGuides();
    var w = el.offsetWidth, h = el.offsetHeight;
    var pw = page.clientWidth, ph = page.clientHeight;
    var targetsX = [0, pw / 2, pw];
    var targetsY = [0, ph / 2, ph];
    var others = [];
    getEls().forEach(function (other) {
      if (other === el || other.style.display === 'none') return;
      var ox = other.offsetLeft, oy = other.offsetTop;
      var ow = other.offsetWidth, oh = other.offsetHeight;
      targetsX.push(ox, ox + ow / 2, ox + ow);
      targetsY.push(oy, oy + oh / 2, oy + oh);
      others.push({ el: other, x: ox, y: oy, w: ow, h: oh });
    });

    function trySnap(val, targets, isX) {
      var best = val, dmin = SNAP + 1, hit = null;
      targets.forEach(function (t) {
        var candidates = isX
          ? [val, val + w / 2, val + w]
          : [val, val + h / 2, val + h];
        candidates.forEach(function (c, i) {
          var d = Math.abs(c - t);
          if (d < dmin) {
            dmin = d;
            hit = t;
            best = isX ? t - (i === 0 ? 0 : i === 1 ? w / 2 : w) : t - (i === 0 ? 0 : i === 1 ? h / 2 : h);
          }
        });
      });
      return dmin <= SNAP ? { val: best, guide: hit } : null;
    }

    var bestX = x, bestY = y;
    var sx = trySnap(x, targetsX, true);
    var sy = trySnap(y, targetsY, false);
    if (sx) { bestX = sx.val; addGuide('y', sx.guide); }
    if (sy) { bestY = sy.val; addGuide('x', sy.guide); }

    bestX = Math.max(0, Math.min(bestX, pw - 20));
    bestY = Math.max(0, Math.min(bestY, ph - 20));

    // Marges par rapport aux autres calques (gap horizontal / vertical)
    others.forEach(function (o) {
      var gapRight = o.x - (bestX + w);
      var gapLeft = bestX - (o.x + o.w);
      var gapBottom = o.y - (bestY + h);
      var gapTop = bestY - (o.y + o.h);
      var overlapY = bestY < o.y + o.h && bestY + h > o.y;
      var overlapX = bestX < o.x + o.w && bestX + w > o.x;

      if (overlapY && gapRight >= 0 && gapRight <= 80) {
        addMarginH(bestX + w, o.x, bestY + h / 2, gapRight);
      }
      if (overlapY && gapLeft >= 0 && gapLeft <= 80) {
        addMarginH(o.x + o.w, bestX, bestY + h / 2, gapLeft);
      }
      if (overlapX && gapBottom >= 0 && gapBottom <= 80) {
        addMarginV(bestY + h, o.y, bestX + w / 2, gapBottom);
      }
      if (overlapX && gapTop >= 0 && gapTop <= 80) {
        addMarginV(o.y + o.h, bestY, bestX + w / 2, gapTop);
      }
    });

    return { x: bestX, y: bestY };
  }

  function addMarginH(x1, x2, y, gap) {
    var left = Math.min(x1, x2);
    var width = Math.abs(x2 - x1);
    if (width < 1) return;
    var line = document.createElement('div');
    line.className = 'ed-margin-line';
    line.style.left = left + 'px';
    line.style.top = y + 'px';
    line.style.width = width + 'px';
    line.style.height = '1px';
    guidesEl.appendChild(line);
    var lab = document.createElement('div');
    lab.className = 'ed-margin-label';
    lab.textContent = Math.round(gap) + ' px';
    lab.style.left = (left + width / 2) + 'px';
    lab.style.top = (y - 14) + 'px';
    lab.style.transform = 'translateX(-50%)';
    guidesEl.appendChild(lab);
  }

  function addMarginV(y1, y2, x, gap) {
    var top = Math.min(y1, y2);
    var height = Math.abs(y2 - y1);
    if (height < 1) return;
    var line = document.createElement('div');
    line.className = 'ed-margin-line';
    line.style.left = x + 'px';
    line.style.top = top + 'px';
    line.style.width = '1px';
    line.style.height = height + 'px';
    guidesEl.appendChild(line);
    var lab = document.createElement('div');
    lab.className = 'ed-margin-label';
    lab.textContent = Math.round(gap) + ' px';
    lab.style.left = (x + 6) + 'px';
    lab.style.top = (top + height / 2) + 'px';
    lab.style.transform = 'translateY(-50%)';
    guidesEl.appendChild(lab);
  }


  function isLocked(el) { return el && el.dataset.locked === '1'; }
  function isEditing(el) { return el && el.classList.contains('editing'); }

  function createEl(cfg) {
    var el = document.createElement('div');
    el.className = 'ed-el';
    el.id = cfg.id || ('el-' + (++elId));
    el.dataset.label = cfg.label || el.id;
    el.dataset.locked = '0';
    el.dataset.hidden = '0';
    el.dataset.group = cfg.group || '';
    el.dataset.blend = 'normal';
    el.style.left = (cfg.x || 40) + 'px';
    el.style.top = (cfg.y || 40) + 'px';
    if (cfg.w) el.style.width = cfg.w + 'px';
    el.style.opacity = '1';
    el.style.mixBlendMode = 'normal';

    if (cfg.type === 'logo') {
      el.classList.add('logo');
      // Hauteur explicite : une image en height:100% a besoin d'une boîte dimensionnée
      // (sinon elle s'effondre), et la sérialisation lit cette hauteur.
      if (cfg.h) el.style.height = cfg.h + 'px';
      el.innerHTML = cfg.html || 'LOGO';
    } else if (cfg.type === 'qr') {
      el.classList.add('qr');
      el.style.width = (cfg.w || 72) + 'px';
      el.style.height = (cfg.w || 72) + 'px';
      // content set after append via setQrContent
    } else if (cfg.type === 'shape') {
      el.classList.add('shape-box');
      el.style.width = (cfg.w || 40) + 'px';
      el.style.height = (cfg.h || 40) + 'px';
      if (cfg.shape === 'circle') el.style.borderRadius = '50%';
      if (cfg.shape === 'line-h') {
        el.style.height = '3px'; el.style.width = (cfg.w || 120) + 'px'; el.style.background = '#111';
      }
      if (cfg.shape === 'line-v') {
        el.style.width = '3px'; el.style.height = (cfg.h || 80) + 'px'; el.style.background = '#111';
      }
      if (cfg.outline) {
        el.style.background = 'transparent'; el.style.border = '2px solid #111';
      }
    } else if (cfg.type === 'signatures') {
      // Bloc des signatures des formateurs : au rendu réel, les vraies signatures
      // (figées à l'émission) sont posées par LayoutRenderer::renderSignatures.
      // Dans le concepteur, on montre un repère : deux emplacements de signature.
      el.classList.add('sig-box');
      el.dataset.sig = '1';
      el.dataset.sigSize = String(cfg.size || 11);
      el.dataset.sigNames = cfg.names === false ? '0' : '1';
      el.style.width = (cfg.w || 350) + 'px';
      el.style.height = (cfg.h || 92) + 'px';
      el.style.display = 'flex';
      el.style.gap = '8px';
      el.innerHTML = '<div style="flex:1;border:1px dashed #94a3b8;border-radius:6px;display:flex;align-items:flex-end;justify-content:center;padding-bottom:4px;font-size:10px;color:#64748b;">Signature formateur 1</div>'
        + '<div style="flex:1;border:1px dashed #94a3b8;border-radius:6px;display:flex;align-items:flex-end;justify-content:center;padding-bottom:4px;font-size:10px;color:#64748b;">Signature formateur 2</div>';
    } else {
      el.classList.add('text');
      if (cfg.cls) cfg.cls.split(/\s+/).forEach(function (c) { if (c) el.classList.add(c); });
      el.textContent = cfg.text || 'Texte';
      // Styles explicites, prioritaires sur les classes : un modèle peut fixer sa
      // police, sa taille, sa graisse, etc. sans dépendre des classes du concepteur
      // (dont l'allure - Playfair, indigo - ne convient pas à toute maquette).
      if (cfg.font) el.style.fontFamily = cfg.font;
      if (cfg.size) el.style.fontSize = cfg.size + 'px';
      if (cfg.weight) el.style.fontWeight = String(cfg.weight);
      if (cfg.color) el.style.color = cfg.color;
      if (cfg.italic) el.style.fontStyle = 'italic';
      if (cfg.underline) el.style.textDecoration = 'underline';
      if (cfg.upper) el.style.textTransform = 'uppercase';
      if (cfg.align) el.style.textAlign = cfg.align;
      if (cfg.leading) el.style.lineHeight = String(cfg.leading);
      if (cfg.spacing) el.style.letterSpacing = cfg.spacing + 'px';
    }

    var rz = document.createElement('div');
    rz.className = 'resize';
    el.appendChild(rz);
    page.appendChild(el);
    bindEl(el);
    if (cfg.type === 'qr') {
      setQrContent(el, cfg.qrUrl || DEFAULT_QR_URL);
    }
    if (cfg.uploadSrc) {
      el.dataset.uploadSrc = cfg.uploadSrc;
    }
    refreshLayers();
    return el;
  }

  function textContentOf(el) {
    var clone = el.cloneNode(true);
    var r = clone.querySelector('.resize');
    if (r) r.remove();
    return clone.textContent;
  }

  function startTextEdit(el) {
    if (!el.classList.contains('text') || isLocked(el)) return;
    stopTextEdit();
    el.classList.add('editing');
    el.contentEditable = 'true';
    el.focus();
    // select all
    try {
      var range = document.createRange();
      range.selectNodeContents(el);
      // exclude resize handle if present
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    } catch (e) {}
  }

  function stopTextEdit() {
    var edited = false;
    page.querySelectorAll('.ed-el.editing').forEach(function (el) {
      el.classList.remove('editing');
      el.contentEditable = 'false';
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div');
        rz.className = 'resize';
        el.appendChild(rz);
      }
      edited = true;
    });
    if (edited) pushHistory();
  }

  function bindEl(el) {
    el.addEventListener('mousedown', function (e) {
      if (isEditing(el)) return; // let caret work
      if (isLocked(el)) {
        selectEl(el);
        e.preventDefault();
        return;
      }
      if (e.target.classList.contains('resize')) {
        resize = { el: el, sx: e.clientX, sy: e.clientY, sw: el.offsetWidth, sh: el.offsetHeight };
        e.preventDefault();
        e.stopPropagation();
        return;
      }
      selectEl(el);
      drag = { el: el, ox: e.clientX - el.offsetLeft, oy: e.clientY - el.offsetTop };
      hideFloatBar();
      e.preventDefault();
    });
    el.addEventListener('dblclick', function (e) {
      if (isLocked(el)) {
        e.preventDefault();
        e.stopPropagation();
        toast('Calque verrouillé');
        return;
      }
      if (el.classList.contains('text')) {
        e.preventDefault();
        e.stopPropagation();
        startTextEdit(el);
      } else if (el.classList.contains('qr')) {
        e.preventDefault();
        e.stopPropagation();
        var cur = el.dataset.qrUrl || DEFAULT_QR_URL;
        openInputModal({
          title: 'Adresse du QR code',
          hint: 'Lien ouvert au scan. Laissez {verify_url} pour que chaque attestation pointe vers la sienne.',
          value: cur,
          placeholder: 'https://...',
          onOk: function (url) {
            if (!url) return;
            setQrContent(el, url);
            pushHistory();
            toast('QR mis à jour');
          }
        });
      }
    });
    el.addEventListener('blur', function () {
      if (isEditing(el)) stopTextEdit();
    });
    el.addEventListener('keydown', function (e) {
      if (!isEditing(el)) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        stopTextEdit();
        el.blur();
      }
      // Ctrl+Enter finish
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        stopTextEdit();
        el.blur();
      }
    });
  }

  function selectEl(el) {
    stopTextEdit();
    if (selected) selected.classList.remove('selected');
    selected = el;
    if (el) {
      el.classList.add('selected');
      if (isLocked(el)) styleBar.classList.add('disabled');
      else styleBar.classList.remove('disabled');
      var cs = window.getComputedStyle(el);
      document.getElementById('fontSize').value = parseInt(cs.fontSize, 10) || 14;
      syncTextControls(el, cs);
      document.getElementById('lyControls').classList.add('show');
      var op = Math.round((parseFloat(el.style.opacity) || 1) * 100);
      document.getElementById('lyOpacity').value = op;
      document.getElementById('lyOpacityVal').textContent = op + '%';
      document.getElementById('lyBlend').value = el.dataset.blend || 'normal';
      document.querySelectorAll('.ed-layer').forEach(function (l) {
        l.classList.toggle('active', l.dataset.id === el.id);
      });
      updateLayerToolbar();
      updateFloatBar();
    } else {
      styleBar.classList.add('disabled');
      document.getElementById('lyControls').classList.remove('show');
      updateLayerToolbar();
      hideFloatBar();
    }
  }

  /* Synchronise la barre d'outils sur l'élément sélectionné : police, graisse,
     couleur, gras/italique/souligné et alignement reflètent ce qu'on vient de
     choisir (sinon le champ police gardait la valeur de l'élément précédent). */
  function syncTextControls(el, cs) {
    // Police : on apparie par CLÉ de police (fontKey), pas par chaîne CSS brute -
    // le navigateur normalise guillemets et espaces, une égalité stricte échouerait.
    var famSel = document.getElementById('fontFamily');
    if (famSel) {
      var wantKey = fontKey(cs.fontFamily);
      Array.prototype.forEach.call(famSel.options, function (opt) {
        opt.selected = fontKey(opt.value) === wantKey;
      });
    }
    // Graisse : valeur numérique calculée (normal→400, bold→700), ramenée à l'option
    // existante la plus proche parmi 400/500/600/700/800.
    var wSel = document.getElementById('fontWeight');
    if (wSel) {
      var w = cs.fontWeight;
      if (w === 'normal') w = '400';
      if (w === 'bold') w = '700';
      var wn = parseInt(w, 10) || 400;
      var opts = [400, 500, 600, 700, 800];
      var nearest = opts.reduce(function (a, b) { return Math.abs(b - wn) < Math.abs(a - wn) ? b : a; });
      wSel.value = String(nearest);
    }
    var colorInp = document.getElementById('fontColor');
    if (colorInp) { var hex = rgbToHex(cs.color); if (hex) colorInp.value = hex; }
    // États gras/italique/souligné (même logique que les bascules).
    var weightOn = cs.fontWeight === '700' || cs.fontWeight === '800' || cs.fontWeight === 'bold';
    var italicOn = cs.fontStyle === 'italic';
    var underlineOn = (cs.textDecorationLine || cs.textDecoration || '').indexOf('underline') > -1;
    document.querySelectorAll('[data-style]').forEach(function (b) {
      var s = b.dataset.style;
      b.classList.toggle('active', (s === 'bold' && weightOn) || (s === 'italic' && italicOn) || (s === 'underline' && underlineOn));
    });
    var align = cs.textAlign;
    if (align === 'start') align = 'left';
    if (align === 'end') align = 'right';
    document.querySelectorAll('[data-align]').forEach(function (b) {
      b.classList.toggle('active', b.dataset.align === align);
    });
  }

  function updateLayerToolbar() {
    var has = !!selected;
    ['lyFront', 'lyUp', 'lyDown', 'lyBack', 'lyDup', 'lyGroup', 'lyDel'].forEach(function (id) {
      var node = document.getElementById(id);
      if (node) node.disabled = !has;
    });
  }

  function hideFloatBar() {
    var bar = document.getElementById('floatBar');
    if (bar) bar.classList.remove('show');
  }

  function updateFloatBar() {
    var bar = document.getElementById('floatBar');
    if (!bar || !selected) { hideFloatBar(); return; }
    var locked = isLocked(selected);
    var btnLock = document.getElementById('fbLock');
    var btnUnlock = document.getElementById('fbUnlock');
    if (btnLock && btnUnlock) {
      btnLock.style.display = locked ? 'none' : 'flex';
      btnUnlock.style.display = locked ? 'flex' : 'none';
    }
    // Prefer BELOW the element so top margin guides stay visible.
    // If not enough space below, place to the right; last resort above with offset.
    var elTop = selected.offsetTop;
    var elLeft = selected.offsetLeft;
    var elW = selected.offsetWidth;
    var elH = selected.offsetHeight;
    var barH = 40;
    var barW = 120;
    var top, left;
    var spaceBelow = page.clientHeight - (elTop + elH);
    var spaceRight = page.clientWidth - (elLeft + elW);

    if (spaceBelow >= barH + 12) {
      top = elTop + elH + 10;
      left = elLeft + elW / 2;
      bar.style.transform = 'translateX(-50%)';
    } else if (spaceRight >= barW + 12) {
      top = elTop;
      left = elLeft + elW + 10;
      bar.style.transform = 'none';
    } else if (elTop >= barH + 16) {
      top = elTop - barH - 12;
      left = elLeft + elW / 2;
      bar.style.transform = 'translateX(-50%)';
    } else {
      top = elTop + 8;
      left = Math.max(barW / 2 + 4, elLeft + elW / 2);
      bar.style.transform = 'translateX(-50%)';
    }
    bar.style.top = top + 'px';
    bar.style.left = left + 'px';
    bar.classList.add('show');
  }

  document.addEventListener('mousemove', function (e) {
    if (drag && !isLocked(drag.el) && !isEditing(drag.el)) {
      var rawX = e.clientX - drag.ox;
      var rawY = e.clientY - drag.oy;
      var sn = snapDrag(drag.el, rawX, rawY);
      drag.el.style.left = sn.x + 'px';
      drag.el.style.top = sn.y + 'px';
    }
    if (resize && !isLocked(resize.el)) {
      var dw = e.clientX - resize.sx;
      var dh = e.clientY - resize.sy;
      resize.el.style.width = Math.max(30, resize.sw + dw) + 'px';
      if (resize.el.classList.contains('shape-box') || resize.el.classList.contains('qr')) {
        resize.el.style.height = Math.max(20, resize.sh + dh) + 'px';
      }
    }
  });
  document.addEventListener('mouseup', function () {
    if (drag) {
      clearGuides();
      pushHistory();
      if (selected) updateFloatBar();
    }
    if (resize) pushHistory();
    drag = null; resize = null;
  });

  page.addEventListener('mousedown', function (e) {
    if (e.target === page || e.target.classList.contains('ed-page-bg')) {
      stopTextEdit();
      selectEl(null);
    }
  });

  function getEls() {
    return Array.prototype.slice.call(page.querySelectorAll('.ed-el'));
  }

  function refreshLayers() {
    var els = getEls().reverse();
    layersList.innerHTML = '';
    document.getElementById('lyCount').textContent = els.length + ' calque' + (els.length > 1 ? 's' : '');

    // Group by dataset.group
    var byGroup = {};
    var ungrouped = [];
    els.forEach(function (el) {
      var g = el.dataset.group || '';
      if (g && groups[g]) {
        if (!byGroup[g]) byGroup[g] = [];
        byGroup[g].push(el);
      } else {
        ungrouped.push(el);
      }
    });

    // ordered groups
    var order = (window.__ukcGroupOrder || []).slice();
    Object.keys(groups).forEach(function (id) {
      if (order.indexOf(id) < 0) order.push(id);
    });
    window.__ukcGroupOrder = order.filter(function (id) { return groups[id]; });
    window.__ukcGroupOrder.forEach(function (gid) {
      if (!byGroup[gid] || !byGroup[gid].length) return;
      layersList.appendChild(renderGroup(gid, byGroup[gid]));
    });
    ungrouped.forEach(function (el) {
      layersList.appendChild(renderLayerRow(el));
    });
    updateLayerToolbar();
  }

  function renderGroup(gid, members) {
    var g = groups[gid];
    var box = document.createElement('div');
    box.className = 'ed-group' + (g.collapsed ? ' collapsed' : '');
    box.dataset.groupId = gid;
    box.draggable = true;

    var head = document.createElement('div');
    head.className = 'ed-group-head';
    head.innerHTML =
      '<span class="handle" title="Glisser pour reordonner le groupe">⠿</span>' +
      '<span class="chev">' + (g.collapsed ? '▶' : '▼') + '</span>' +
      '<span class="grp-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="Double-clic pour renommer">📁 ' + g.name + '</span>' +
      '<span style="font-size:11px;color:var(--ukc-light);">' + members.length + '</span>';
    head.addEventListener('click', function (e) {
      if (e.target.closest('.grp-name-edit')) return;
      g.collapsed = !g.collapsed;
      refreshLayers();
    });
    var nameSpan = head.querySelector('.grp-name');
    if (nameSpan) {
      nameSpan.addEventListener('dblclick', function (e) {
        e.stopPropagation();
        startGroupRename(gid, nameSpan);
      });
    }
    box.appendChild(head);

    var body = document.createElement('div');
    body.className = 'ed-group-body';
    members.forEach(function (el) {
      body.appendChild(renderLayerRow(el));
    });
    box.appendChild(body);

    // drop on group
    box.addEventListener('dragover', function (e) {
      e.preventDefault();
      box.classList.add('drag-over');
    });
    box.addEventListener('dragleave', function () {
      box.classList.remove('drag-over');
    });
    box.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      box.classList.remove('drag-over');
      box.classList.remove('drag-over-group');
      var dragType = e.dataTransfer.getData('application/x-ukc-type') || window.__lyDragType;
      var dragId = e.dataTransfer.getData('text/plain') || window.__lyDragId;
      if (dragType === 'group' && dragId && dragId !== gid) {
        // reorder groups: move dragId group before this group in order list
        reorderGroupBefore(dragId, gid);
        pushHistory();
        toast('Groupe réordonné');
        return;
      }
      var fromEl = document.getElementById(dragId);
      if (!fromEl) return;
      fromEl.dataset.group = gid;
      refreshLayers();
      pushHistory();
      toast('Ajoute au groupe « ' + g.name + ' »');
    });

    box.addEventListener('dragstart', function (e) {
      // only start group drag from handle or head, not from child layers
      if (e.target.closest('.ed-layer')) return;
      e.dataTransfer.setData('text/plain', gid);
      e.dataTransfer.setData('application/x-ukc-type', 'group');
      e.dataTransfer.effectAllowed = 'move';
      window.__lyDragId = gid;
      window.__lyDragType = 'group';
      requestAnimationFrame(function () { box.classList.add('dragging'); });
    });
    box.addEventListener('dragend', function () {
      box.classList.remove('dragging');
      window.__lyDragId = null;
      window.__lyDragType = null;
      document.querySelectorAll('.drag-over-group, .drag-over').forEach(function (n) {
        n.classList.remove('drag-over-group', 'drag-over');
      });
    });
    box.addEventListener('dragover', function (e) {
      e.preventDefault();
      var dragType = window.__lyDragType;
      if (dragType === 'group') {
        box.classList.add('drag-over-group');
        box.classList.remove('drag-over');
      } else {
        box.classList.add('drag-over');
      }
    });
    box.addEventListener('dragleave', function () {
      box.classList.remove('drag-over', 'drag-over-group');
    });

    return box;
  }

  // groupOrder: array of group ids for display order (ungrouped always at end)
  if (typeof window.__ukcGroupOrder === 'undefined') window.__ukcGroupOrder = [];

  function reorderGroupBefore(fromGid, toGid) {
    var order = window.__ukcGroupOrder.slice();
    // ensure all groups present
    Object.keys(groups).forEach(function (id) {
      if (order.indexOf(id) < 0) order.push(id);
    });
    var fi = order.indexOf(fromGid);
    var ti = order.indexOf(toGid);
    if (fi < 0 || ti < 0) return;
    order.splice(fi, 1);
    ti = order.indexOf(toGid);
    order.splice(ti, 0, fromGid);
    window.__ukcGroupOrder = order;
    // Also reorder DOM z of members: first group's members at bottom of stack etc.
    // Simpler: just refresh list order
    refreshLayers();
  }

  function renderLayerRow(el) {
    var hidden = el.dataset.hidden === '1';
    var locked = el.dataset.locked === '1';
    var row = document.createElement('div');
    row.className = 'ed-layer' +
      (selected === el ? ' active' : '') +
      (hidden ? ' hidden-layer' : '') +
      (locked ? ' locked-layer' : '');
    row.dataset.id = el.id;
    row.draggable = true;

    row.innerHTML =
      '<span class="handle" title="Glisser pour reordonner">⠿</span>' +
      '<button type="button" class="ico-btn' + (hidden ? ' off' : '') + '" data-act="vis" title="Afficher / masquer">' + (hidden ? '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>' : '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>') + '</button>' +
      '<span class="name" title="Double-clic pour renommer">' + (el.dataset.label || el.id) + '</span>' +
      '<div class="actions">' +
        '<button type="button" class="ico-btn' + (locked ? '' : ' off') + '" data-act="lock" title="Verrouiller">' + (locked ? '🔒' : '🔓') + '</button>' +
      '</div>';

    row.addEventListener('click', function (e) {
      if (e.target.closest('[data-act]')) return;
      selectEl(el);
    });
    row.querySelector('[data-act="vis"]').addEventListener('click', function (e) {
      e.stopPropagation();
      toggleVisibility(el);
    });
    row.querySelector('[data-act="lock"]').addEventListener('click', function (e) {
      e.stopPropagation();
      toggleLock(el);
    });

    var nameEl = row.querySelector('.name');
    nameEl.addEventListener('dblclick', function (e) {
      e.stopPropagation();
      startRename(el, nameEl);
    });

    row.addEventListener('dragstart', function (e) {
      e.stopPropagation();
      e.dataTransfer.setData('text/plain', el.id);
      e.dataTransfer.setData('application/x-ukc-type', 'layer');
      e.dataTransfer.effectAllowed = 'move';
      window.__lyDragId = el.id;
      window.__lyDragType = 'layer';
      window.__lyDidReorder = false;
      requestAnimationFrame(function () { row.classList.add('dragging'); });
    });
    row.addEventListener('dragend', function () {
      row.classList.remove('dragging');
      window.__lyDragId = null;
      document.querySelectorAll('.ed-layer.drag-over, .ed-group.drag-over').forEach(function (n) {
        n.classList.remove('drag-over');
      });
      if (window.__lyDidReorder) {
        refreshLayers();
        pushHistory();
      }
    });
    row.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      row.classList.add('drag-over');
      // Live visual swap: move DOM node as we hover past
      var fromId = e.dataTransfer.getData('text/plain') || row.dataset._from;
      // dataTransfer.getData may be empty in dragover in some browsers - use global
      fromId = window.__lyDragId || fromId;
      var fromEl = fromId && document.getElementById(fromId);
      if (!fromEl || fromEl === el) return;
      var els = getEls();
      var fromIdx = els.indexOf(fromEl);
      var toIdx = els.indexOf(el);
      if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) return;
      // Swap neighbor when crossing
      if (fromIdx < toIdx) {
        // moving up in stack (later in DOM): insert after el
        if (fromEl.nextSibling !== el.nextSibling) {
          page.insertBefore(fromEl, el.nextSibling);
          window.__lyDidReorder = true;
        }
      } else {
        if (el.previousSibling !== fromEl) {
          page.insertBefore(fromEl, el);
          window.__lyDidReorder = true;
        }
      }
    });
    row.addEventListener('dragleave', function () {
      row.classList.remove('drag-over');
    });
    row.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      row.classList.remove('drag-over');
      var fromEl = document.getElementById(window.__lyDragId || e.dataTransfer.getData('text/plain'));
      if (!fromEl) return;
      fromEl.dataset.group = el.dataset.group || '';
      refreshLayers();
      // animate the rows that moved
      var rowFrom = layersList.querySelector('[data-id="' + fromEl.id + '"]');
      if (rowFrom) {
        rowFrom.classList.add('swap-up');
        setTimeout(function () { rowFrom.classList.remove('swap-up'); }, 220);
      }
      selectEl(fromEl);
      pushHistory();
      toast('Ordre des calques mis à jour');
    });

    return row;
  }

  function startRename(el, nameEl) {
    var prev = el.dataset.label || el.id;
    var input = document.createElement('input');
    input.type = 'text';
    input.value = prev;
    nameEl.textContent = '';
    nameEl.appendChild(input);
    input.focus();
    input.select();
    function commit() {
      var v = input.value.trim() || prev;
      el.dataset.label = v;
      refreshLayers();
      selectEl(el);
    }
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
      if (e.key === 'Escape') { input.value = prev; input.blur(); }
    });
  }

  function startGroupRename(gid, nameEl) {
    var g = groups[gid];
    if (!g) return;
    var prev = g.name;
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'grp-name-edit';
    input.value = prev;
    input.style.cssText = 'width:100%;border:1px solid var(--ukc-blue);border-radius:4px;padding:2px 6px;font-size:12px;font-weight:800;';
    nameEl.textContent = '';
    nameEl.appendChild(input);
    input.focus();
    input.select();
    function commit() {
      var v = input.value.trim() || prev;
      g.name = v;
      refreshLayers();
    }
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
      if (e.key === 'Escape') { input.value = prev; input.blur(); }
    });
    input.addEventListener('click', function (e) { e.stopPropagation(); });
  }

  function toggleVisibility(el) {
    var hide = el.dataset.hidden !== '1';
    el.dataset.hidden = hide ? '1' : '0';
    el.style.display = hide ? 'none' : '';
    refreshLayers();
    if (selected === el && hide) selectEl(null);
    else if (selected === el) selectEl(el);
  }

  function toggleLock(el) {
    var lock = el.dataset.locked !== '1';
    el.dataset.locked = lock ? '1' : '0';
    el.classList.toggle('locked', lock);
    if (el.classList.contains('text')) {
      el.contentEditable = 'false';
    }
    if (lock) stopTextEdit();
    refreshLayers();
    selectEl(el);
    toast(lock ? 'Calque verrouille - non deplacable ni editable' : 'Calque deverrouille');
  }

  function moveLayer(dir) {
    if (!selected) return;
    if (isLocked(selected)) { toast('Calque verrouillé'); return; }
    var els = getEls();
    var idx = els.indexOf(selected);
    if (idx < 0) return;
    selected.style.transition = 'opacity .15s';
    selected.style.opacity = '0.5';
    if (dir === 'front') page.appendChild(selected);
    else if (dir === 'back') {
      var first = page.querySelector('.ed-el');
      if (first) page.insertBefore(selected, first);
    } else if (dir === 'up' && idx < els.length - 1) {
      page.insertBefore(els[idx + 1], selected);
    } else if (dir === 'down' && idx > 0) {
      page.insertBefore(selected, els[idx - 1]);
    }
    setTimeout(function () {
      selected.style.opacity = selected.dataset._op || '1';
      selected.style.transition = '';
      refreshLayers();
      selectEl(selected);
      pushHistory();
    }, 150);
  }

  function duplicateLayer() {
    if (!selected) return;
    var src = selected;
    var cfg = {
      type: src.classList.contains('qr') ? 'qr' :
            src.classList.contains('logo') ? 'logo' :
            src.classList.contains('shape-box') ? 'shape' : 'text',
      label: (src.dataset.label || src.id) + ' copie',
      x: src.offsetLeft + 16,
      y: src.offsetTop + 16,
      w: src.offsetWidth,
      group: src.dataset.group || '',
      text: src.classList.contains('text') ? textContentOf(src) : undefined,
      html: src.classList.contains('logo') ? src.innerHTML.replace(/<div class="resize"><\/div>/, '') : undefined
    };
    if (src.classList.contains('heading')) cfg.cls = 'heading';
    else if (src.classList.contains('sub')) cfg.cls = 'sub';
    else if (src.classList.contains('attr')) cfg.cls = 'attr';
    var el = createEl(cfg);
    el.style.fontSize = src.style.fontSize || '';
    el.style.color = src.style.color || '';
    el.style.fontWeight = src.style.fontWeight || '';
    el.style.opacity = src.style.opacity || '1';
    el.style.mixBlendMode = src.style.mixBlendMode || 'normal';
    el.dataset.blend = src.dataset.blend || 'normal';
    selectEl(el);
    pushHistory();
    toast('Calque dupliqué');
  }

  function groupSelected() {
    if (!selected) return;
    var gid = 'g' + (++groupId);
    groups[gid] = { name: 'Groupe ' + groupId, collapsed: false, memberIds: [selected.id] };
    selected.dataset.group = gid;
    if (!window.__ukcGroupOrder) window.__ukcGroupOrder = [];
    window.__ukcGroupOrder.unshift(gid);
    refreshLayers();
    pushHistory();
    toast('Groupe créé - glissez d’autres calques dessus');
  }

  function deleteSelected() {
    if (!selected) return;
    if (isLocked(selected)) {
      toast('Calque verrouillé');
      return;
    }
    selected.remove();
    selected = null;
    styleBar.classList.add('disabled');
    document.getElementById('lyControls').classList.remove('show');
    refreshLayers();
    pruneUploads();
    pushHistory();
  }

  document.getElementById('lyFront').addEventListener('click', function () { moveLayer('front'); });
  document.getElementById('lyUp').addEventListener('click', function () { moveLayer('up'); });
  document.getElementById('lyDown').addEventListener('click', function () { moveLayer('down'); });
  document.getElementById('lyBack').addEventListener('click', function () { moveLayer('back'); });
  document.getElementById('lyDup').addEventListener('click', duplicateLayer);
  document.getElementById('lyGroup').addEventListener('click', groupSelected);
  document.getElementById('lyDel').addEventListener('click', deleteSelected);

  document.getElementById('lyOpacity').addEventListener('input', function () {
    if (!selected) return;
    var v = this.value;
    selected.style.opacity = String(v / 100);
    selected.dataset._op = String(v / 100);
    document.getElementById('lyOpacityVal').textContent = v + '%';
    markDirty();
  });
  document.getElementById('lyOpacity').addEventListener('change', function () {
    if (selected) pushHistory();
  });
  document.getElementById('lyBlend').addEventListener('change', function () {
    if (!selected) return;
    selected.dataset.blend = this.value;
    selected.style.mixBlendMode = this.value;
    toast('Mode de fusion : ' + this.value);
    pushHistory();
  });

  // Charge le modele Participation par defaut (apres definition de loadTemplate - appele plus bas si besoin)
  DEFAULT_ELEMENTS.forEach(createEl);
  var bgEl0 = document.getElementById('pageBg');
  if (bgEl0) {
    bgEl0.style.backgroundImage = "url('/img/bg-attestation.png')";
    bgEl0.style.backgroundSize = '100% 100%';
    bgEl0.style.backgroundPosition = 'center';
  }
  pushHistory(); // baseline

  document.getElementById('btnUndo').addEventListener('click', undo);
  document.getElementById('btnRedo').addEventListener('click', redo);

  document.getElementById('zoomIn').addEventListener('click', function () {
    zoomLevel = Math.min(2, Math.round((zoomLevel + 0.1) * 10) / 10);
    applyZoom();
  });
  document.getElementById('zoomOut').addEventListener('click', function () {
    zoomLevel = Math.max(0.4, Math.round((zoomLevel - 0.1) * 10) / 10);
    applyZoom();
  });
  document.getElementById('zoomReset').addEventListener('click', function () {
    zoomLevel = 1;
    applyZoom();
  });
  // Ctrl + wheel zoom
  document.querySelector('.ed-canvas-area').addEventListener('wheel', function (e) {
    if (!(e.ctrlKey || e.metaKey)) return;
    e.preventDefault();
    if (e.deltaY < 0) zoomLevel = Math.min(2, Math.round((zoomLevel + 0.05) * 100) / 100);
    else zoomLevel = Math.max(0.4, Math.round((zoomLevel - 0.05) * 100) / 100);
    applyZoom();
  }, { passive: false });

  /* Background modal */
  var bgModal = document.getElementById('bgModal');
  document.getElementById('btnBg').addEventListener('click', function () {
    bgModal.classList.add('open');
  });
  document.getElementById('bgModalClose').addEventListener('click', function () {
    bgModal.classList.remove('open');
  });
  bgModal.addEventListener('click', function (e) {
    if (e.target === bgModal) bgModal.classList.remove('open');
  });
  document.querySelectorAll('#bgModal .bg-opt').forEach(function (opt) {
    opt.addEventListener('click', function () {
      document.querySelectorAll('#bgModal .bg-opt').forEach(function (o) { o.classList.remove('active'); });
      opt.classList.add('active');
      page.classList.remove('bg-none', 'bg-simple', 'bg-gold', 'bg-bands', 'bg-custom', 'bg-plain');
      var bgEl = document.getElementById('pageBg');
      bgEl.style.backgroundImage = '';
      bgEl.style.backgroundSize = '';
      bgEl.style.backgroundPosition = '';
      // ⚠️ « Bandes UK », « Simple » et « Or discret » étaient des DÉGRADÉS CSS
      // posés par une classe : le concepteur les affichait, mais la mise en page
      // enregistrée ne retient qu'une image de fond - le document délivré
      // ressortait donc blanc, et dompdf ne sait de toute façon pas tracer un
      // dégradé. Ils sont désormais des images, comme les deux autres fonds.
      var FONDS = {
        'default': '/img/bg-attestation-uk.png',
        'plain': '/img/bg-attestation.png',
        'bands': '/img/bg-attestation-bands.png',
        'simple': '/img/bg-attestation-simple.png',
        'gold': '/img/bg-attestation-gold.png'
      };
      var kind = opt.dataset.bg;
      if (FONDS[kind]) {
        bgEl.style.backgroundImage = "url('" + FONDS[kind] + "')";
        bgEl.style.backgroundSize = '100% 100%';
        bgEl.style.backgroundPosition = 'center';
      } else {
        page.classList.add('bg-none');
      }
      pushHistory();
      toast('Arrière-plan appliqué');
      bgModal.classList.remove('open');
    });
  });
  /**
   * Dépose l'image sur le serveur et rend son adresse.
   *
   * ⚠️ Les images étaient embarquées en `data:` base64 dans le DOM : elles
   * restaient visibles dans le concepteur (l'instantané les conserve) mais
   * `LayoutSanitizer` refuse `data:`, donc elles étaient **jetées de la mise en
   * page enregistrée** - les logos disparaissaient du document délivré.
   */
  function uploadImage(file) {
    var cfg = window.UKC_EDITOR || {};
    if (!cfg.uploadUrl) return Promise.reject(new Error('indisponible'));
    var body = new FormData();
    body.append('file', file);
    return fetch(cfg.uploadUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json' },
      body: body
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || !j.ok || !j.url) throw new Error((j && j.message) || 'échec');
      return j.url;
    });
  }
  document.getElementById('bgImportZone').addEventListener('click', function () {
    document.getElementById('bgFileInput').click();
  });
  document.getElementById('bgFileInput').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    uploadImage(file).then(function (url) {
      page.classList.remove('bg-none', 'bg-simple', 'bg-gold', 'bg-bands');
      page.classList.add('bg-custom');
      var bgEl = document.getElementById('pageBg');
      bgEl.style.backgroundImage = 'url(' + url + ')';
      bgEl.style.backgroundSize = 'cover';
      bgEl.style.backgroundPosition = 'center';
      pushHistory();
      toast('Image de fond importée');
      bgModal.classList.remove('open');
    }).catch(function () { toast('Échec du téléversement de l’image'); });
    e.target.value = '';
  });

  /* Uploads only images */
  document.getElementById('btnUploadImg').addEventListener('click', function () {
    document.getElementById('fileUpload').click();
  });
  document.getElementById('fileUpload').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    uploadImage(file).then(function (url) {
      var thumb = document.createElement('div');
      thumb.className = 'ed-thumb';
      thumb.title = file.name;
      thumb.style.backgroundImage = 'url(' + url + ')';
      thumb.dataset.src = url;
      thumb.dataset.wasUsed = '0';
      thumb.addEventListener('click', function () {
        var el = createEl({
          type: 'logo',
          label: file.name.slice(0, 20),
          x: 100, y: 100, w: 120,
          uploadSrc: url,
          html: '<img src="' + url + '" alt="" style="max-width:100%;max-height:80px;display:block;">'
        });
        thumb.dataset.wasUsed = '1';
        selectEl(el);
        pushHistory();
      });
      document.getElementById('uploadList').appendChild(thumb);
      toast('Image ajoutée');
    }).catch(function () { toast('Échec du téléversement de l’image'); });
    e.target.value = '';
  });
  document.querySelectorAll('#uploadList .ed-thumb').forEach(function (th) {
    th.addEventListener('click', function () {
      // Vraies images : UK = logo large des Réglages, Parakou = asset livré (plus de
      // placeholder texte, qui ne survivait pas au rendu).
      var logos = (window.UKC_EDITOR || {}).logos || {};
      var up = th.dataset.logo === 'up';
      var src = up ? (logos.parakou || '/img/attestations/logo-parakou.png') : (logos.uk || '/img/logo-uk.png');
      var el = createEl({
        type: 'logo', label: up ? 'Logo Parakou' : 'Logo UK',
        x: 80, y: 60, w: up ? 260 : 230, h: 60, uploadSrc: src,
        html: '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">'
      });
      selectEl(el);
      pushHistory();
    });
  });

  document.querySelectorAll('.ed-rail button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.ed-rail button').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      document.querySelectorAll('.panel-view').forEach(function (p) { p.classList.remove('active'); });
      document.getElementById('panel-' + btn.dataset.panel).classList.add('active');
      if (btn.dataset.panel === 'layers') refreshLayers();
    });
  });

  document.querySelectorAll('[data-paper]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-paper]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      page.classList.toggle('portrait', btn.dataset.paper === 'portrait');
      if (btn.dataset.paper === 'letter') {
        page.style.width = '880px'; page.style.height = '640px'; page.classList.remove('portrait');
      } else if (btn.dataset.paper === 'landscape') {
        page.style.width = '900px'; page.style.height = '636px';
      } else {
        page.style.width = ''; page.style.height = '';
      }
    });
  });

  document.querySelectorAll('[data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kind = btn.dataset.add;
      var cfg = { x: 80 + Math.random() * 40, y: 80 + Math.random() * 40 };
      if (kind === 'heading') Object.assign(cfg, { type: 'text', label: 'Heading', cls: 'heading', text: 'Titre', w: 400 });
      else if (kind === 'subheading' || kind === 'normal' || kind === 'textbox')
        Object.assign(cfg, { type: 'text', label: 'Texte', cls: 'sub', text: 'Nouveau texte', w: 300 });
      else if (kind === 'qr' || kind === 'qr-verify') {
        Object.assign(cfg, { type: 'qr', label: 'QR Verification', w: 72, qrUrl: DEFAULT_QR_URL });
      } else if (kind === 'qr-custom') {
        openInputModal({
          title: 'QR code personnalisé',
          hint: 'Adresse encodée dans ce QR code, la même pour toutes les attestations.',
          value: 'https://',
          placeholder: 'https://...',
          onOk: function (url) {
            if (!url) return;
            var c = { type: 'qr', label: 'QR Custom', x: 80 + Math.random() * 40, y: 80 + Math.random() * 40, w: 72, qrUrl: url };
            selectEl(createEl(c));
            document.querySelector('.ed-rail button[data-panel="layers"]').click();
            pushHistory();
          }
        });
        return;
      }
      else if (kind === 'rect') Object.assign(cfg, { type: 'shape', shape: 'rect', label: 'Rectangle', w: 60, h: 40 });
      else if (kind === 'rect-o') Object.assign(cfg, { type: 'shape', shape: 'rect', outline: true, label: 'Rectangle', w: 60, h: 40 });
      else if (kind === 'circle') Object.assign(cfg, { type: 'shape', shape: 'circle', label: 'Cercle', w: 48, h: 48 });
      else if (kind === 'circle-o') Object.assign(cfg, { type: 'shape', shape: 'circle', outline: true, label: 'Cercle', w: 48, h: 48 });
      else if (kind === 'line-h') Object.assign(cfg, { type: 'shape', shape: 'line-h', label: 'Ligne H', w: 160 });
      else if (kind === 'line-v') Object.assign(cfg, { type: 'shape', shape: 'line-v', label: 'Ligne V', h: 100 });
      selectEl(createEl(cfg));
      document.querySelector('.ed-rail button[data-panel="layers"]').click();
    });
  });

  document.querySelectorAll('.ed-attr').forEach(function (btn) {
    btn.addEventListener('click', function () {
      selectEl(createEl({
        type: 'text', label: btn.dataset.attr, cls: 'attr',
        text: btn.dataset.attr, x: 200, y: 200, w: 280
      }));
      document.querySelector('.ed-rail button[data-panel="layers"]').click();
    });
  });

  /* Style bar */
  document.getElementById('fontSize').addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontSize = this.value + 'px'; pushHistory(); }
  });
  document.getElementById('fontSizeMinus').addEventListener('click', function () {
    var inp = document.getElementById('fontSize');
    inp.value = Math.max(8, parseInt(inp.value, 10) - 1);
    inp.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontSizePlus').addEventListener('click', function () {
    var inp = document.getElementById('fontSize');
    inp.value = Math.min(72, parseInt(inp.value, 10) + 1);
    inp.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontFamily').addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontFamily = this.value; pushHistory(); }
  });
  document.getElementById('fontWeight').addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontWeight = this.value; pushHistory(); }
  });
  document.getElementById('fontColor').addEventListener('input', function () {
    if (selected && !isLocked(selected)) { selected.style.color = this.value; markDirty(); }
  });
  document.getElementById('fontColor').addEventListener('change', function () {
    if (selected && !isLocked(selected)) pushHistory();
  });
  document.querySelectorAll('[data-style]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!selected || isLocked(selected)) return;
      var s = btn.dataset.style;
      if (s === 'bold') {
        var on = selected.style.fontWeight === '800' || selected.style.fontWeight === '700';
        selected.style.fontWeight = on ? '400' : '700';
        btn.classList.toggle('active', !on);
      }
      if (s === 'italic') {
        var it = selected.style.fontStyle === 'italic';
        selected.style.fontStyle = it ? 'normal' : 'italic';
        btn.classList.toggle('active', !it);
      }
      if (s === 'underline') {
        var u = selected.style.textDecoration === 'underline';
        selected.style.textDecoration = u ? 'none' : 'underline';
        btn.classList.toggle('active', !u);
      }
      pushHistory();
    });
  });
  document.querySelectorAll('[data-align]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!selected || isLocked(selected)) return;
      selected.style.textAlign = btn.dataset.align;
      document.querySelectorAll('[data-align]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      pushHistory();
    });
  });
  document.getElementById('btnDelete').addEventListener('click', deleteSelected);

  /* ---------- Enregistrement réel (POST vers templates.save) ---------- */
  function rgbToHex(c) {
    if (!c) return '';
    if (c.charAt(0) === '#') return c;
    var m = c.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
    if (!m) return '';
    return '#' + [1, 2, 3].map(function (i) { return ('0' + parseInt(m[i], 10).toString(16)).slice(-2); }).join('');
  }
  /* Police du navigateur → clé de la liste blanche serveur (LayoutSanitizer::FONTS).
     ⚠️ On n'enregistre JAMAIS la déclaration CSS brute : elle finirait telle quelle
     dans un attribut `style` du document rendu. */
  function fontKey(family) {
    var f = (family || '').toLowerCase();
    if (f.indexOf('playfair') > -1) return 'playfair';
    if (f.indexOf('times') > -1) return 'times';
    if (f.indexOf('georgia') > -1) return 'georgia';
    if (f.indexOf('lato') > -1) return 'lato';
    if (f.indexOf('arial') > -1 || f.indexOf('helvetica') > -1) return 'arial';
    return 'jost';
  }

  /* Sérialisation de la maquette vers le modèle enregistré.
     ⚠️ Tout ce qui n'est pas repris ici est PERDU : le document délivré ne
     ressemblera plus à ce qui a été dessiné. C'est ce qui est arrivé à la police,
     à l'italique, au souligné, à l'interlignage, aux coins arrondis, aux filets
     et au fond de page - tous rétablis ci-dessous. */
  function serializeLayout() {
    var rect = page.getBoundingClientRect();
    var W = Math.max(120, Math.round(rect.width)), H = Math.max(120, Math.round(rect.height));
    var pageBg = rgbToHex(getComputedStyle(page).backgroundColor) || '#ffffff';
    var elements = getEls().filter(function (el) { return el.style.display !== 'none'; }).map(function (el) {
      var cs = getComputedStyle(el);
      var x = Math.round(parseFloat(el.style.left) || 0);
      var y = Math.round(parseFloat(el.style.top) || 0);
      var w = Math.round(parseFloat(el.style.width) || el.offsetWidth || 100);
      var h = Math.round(parseFloat(el.style.height) || el.offsetHeight || 30);
      var cls = el.className || '';
      var img = el.querySelector('img');
      var text = (el.innerText || '').trim();
      // Bloc de signatures des formateurs (élément structuré, rendu côté serveur) :
      // préservé tel quel, sinon il repartirait en « texte » et disparaîtrait.
      if (el.dataset.sig === '1') {
        return { type: 'signatures', x: x, y: y, w: w, h: h, size: parseInt(el.dataset.sigSize, 10) || 11, names: el.dataset.sigNames !== '0' };
      }
      if (el.dataset.qrUrl || /\bqr\b/i.test(cls) || (img && /qrserver|qrcode/i.test(img.src))) {
        return { type: 'qr', x: x, y: y, size: Math.max(w, h) || 96, value: el.dataset.qrUrl || '{verify_url}' };
      }
      // ⚠️ `img.src` est toujours absolu côté DOM : un logo téléversé passe donc
      // bien ici. Le filtre d'avant (^https?:) laissait tomber les blob:/data:
      // mais surtout renvoyait l'élément vers la branche « texte », où il
      // disparaissait faute de texte.
      if (img && /^(https?:|\/)/i.test(img.getAttribute('src') || img.src)) {
        return { type: 'image', x: x, y: y, w: w, h: h, src: img.getAttribute('src') || img.src };
      }
      if (!text && (/\b(shape|rect|line|ln-|sq)\b/i.test(cls) || el.style.background || cs.backgroundColor !== 'rgba(0, 0, 0, 0)')) {
        var bw = Math.round(parseFloat(cs.borderTopWidth) || 0);
        return {
          type: 'rect', x: x, y: y, w: w, h: h,
          color: rgbToHex(cs.backgroundColor) || '#223ea4',
          radius: Math.round(parseFloat(cs.borderTopLeftRadius) || 0),
          border: bw,
          border_color: bw > 0 ? (rgbToHex(cs.borderTopColor) || '#223ea4') : ''
        };
      }
      var align = cs.textAlign;
      return {
        type: 'text', x: x, y: y, w: w, h: h,
        text: text.slice(0, 500),
        size: Math.round(parseFloat(cs.fontSize) || 16),
        weight: parseInt(cs.fontWeight, 10) || 500,
        color: rgbToHex(cs.color) || '#1a2332',
        align: (align === 'center' || align === 'right') ? align : 'left',
        font: fontKey(cs.fontFamily),
        italic: cs.fontStyle === 'italic',
        underline: (cs.textDecorationLine || cs.textDecoration || '').indexOf('underline') > -1,
        upper: cs.textTransform === 'uppercase',
        leading: Math.round((parseFloat(cs.lineHeight) / (parseFloat(cs.fontSize) || 16)) * 100) / 100 || 1.2,
        spacing: Math.round((parseFloat(cs.letterSpacing) || 0) * 100) / 100
      };
    }).filter(function (e) { return e && !(e.type === 'text' && !e.text); });
    // ⚠️ L'image de fond n'était pas sérialisée : une attestation dessinée sur
    // un fond illustré ressortait sur du blanc.
    var bgEl = document.getElementById('pageBg');
    var bgUrl = bgEl ? (getComputedStyle(bgEl).backgroundImage || '').match(/url\((['"]?)(.*?)\1\)/) : null;
    return { width: W, height: H, bg: pageBg, bg_image: bgUrl ? bgUrl[2] : '', accent: '#223ea4', elements: elements };
  }
  function performSave(onSuccess) {
    var cfg = window.UKC_EDITOR || {};
    var btn = document.getElementById('btnSave');
    if (!cfg.saveUrl) { toast('Enregistrement indisponible'); return; }
    btn.disabled = true;
    var original = btn.textContent;
    btn.textContent = '…';
    fetch(cfg.saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json' },
      body: JSON.stringify({ event_id: cfg.editionId || '', kind: cfg.kind || 'attestation', type: cfg.type || 'participation', title: (document.getElementById('tplName') || {}).value || '', layout: serializeLayout(), editor_state: snapshot() })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j && res.j.ok) {
          dirty = false;
          toast('Template « ' + document.getElementById('tplName').value + ' » enregistré');
          if (typeof onSuccess === 'function') onSuccess();
        } else toast((res.j && res.j.error) ? res.j.error : 'Échec de l’enregistrement');
      })
      .catch(function () { toast('Erreur réseau lors de l’enregistrement'); })
      .finally(function () { btn.disabled = false; btn.textContent = original; });
  }
  document.getElementById('btnSave').addEventListener('click', function () { performSave(); });

  /* ---------- Garde « modifications non enregistrées » ----------
     En édition (hors aperçu verrouillé), prévenir avant de quitter : notre bouton
     « ← Documents » ouvre un modal maison ; un rechargement / fermeture d'onglet /
     retour navigateur déclenche l'avertissement natif (seul moyen pour ces cas). */
  (function () {
    var cfg = window.UKC_EDITOR || {};
    if (cfg.preview) return; // l'aperçu verrouillé ne modifie rien

    var nameInp = document.getElementById('tplName');
    if (nameInp) nameInp.addEventListener('input', markDirty);

    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
      return '';
    });

    function go() { location.href = cfg.backUrl || '/admin/documents'; }

    function confirmLeave() {
      if (document.getElementById('ukcLeaveModal')) return;
      var ov = document.createElement('div');
      ov.id = 'ukcLeaveModal';
      ov.style.cssText = 'position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;padding:24px;';
      var box = document.createElement('div');
      box.style.cssText = 'background:#fff;border-radius:14px;max-width:440px;width:100%;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.3);font-family:Jost,system-ui,sans-serif;';
      var h = document.createElement('h3');
      h.textContent = 'Modifications non enregistrées';
      h.style.cssText = 'margin:0 0 8px;font-size:18px;font-weight:800;color:#1a2332;';
      var p = document.createElement('p');
      p.textContent = 'Vous avez des modifications non enregistrées. Voulez-vous quitter sans enregistrer ?';
      p.style.cssText = 'margin:0 0 20px;font-size:14px;color:#475569;line-height:1.5;';
      var row = document.createElement('div');
      row.style.cssText = 'display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;';
      function mkBtn(label, kind) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        var base = 'border:none;border-radius:9px;padding:10px 16px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;';
        if (kind === 'primary') b.style.cssText = base + 'background:#223ea4;color:#fff;';
        else if (kind === 'danger') b.style.cssText = base + 'background:#fee2e2;color:#b91c1c;';
        else b.style.cssText = base + 'background:#e2e8f0;color:#334155;';
        return b;
      }
      function close() { ov.remove(); }
      var bCancel = mkBtn('Annuler', 'ghost');
      var bLeave = mkBtn('Quitter sans enregistrer', 'danger');
      var bSave = mkBtn('Enregistrer et quitter', 'primary');
      bCancel.addEventListener('click', close);
      bLeave.addEventListener('click', function () { dirty = false; go(); });
      bSave.addEventListener('click', function () { performSave(function () { go(); }); });
      ov.addEventListener('click', function (e) { if (e.target === ov) close(); });
      document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape' && document.getElementById('ukcLeaveModal')) { close(); document.removeEventListener('keydown', esc); }
      });
      row.appendChild(bCancel); row.appendChild(bLeave); row.appendChild(bSave);
      box.appendChild(h); box.appendChild(p); box.appendChild(row);
      ov.appendChild(box);
      document.body.appendChild(ov);
    }

    var backBtn = document.querySelector('.ed-top .back');
    if (backBtn) {
      backBtn.onclick = null; // remplace le onclick inline du gabarit
      backBtn.addEventListener('click', function () {
        if (dirty) confirmLeave();
        else go();
      });
    }
  })();
  /**
   * Remplace [espace.cle] et {cle} par les valeurs d'exemple fournies par le
   * serveur. Un jeton sans valeur est laissé tel quel : mieux vaut le voir que
   * de croire le document complet.
   */
  function fillTokens(root) {
    var vars = (window.UKC_EDITOR || {}).vars || {};
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(function (n) {
      n.nodeValue = n.nodeValue.replace(/\[([A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)?)\]|\{([A-Za-z0-9_]+)\}/g,
        function (whole, bracket, brace) {
          var key = bracket || brace;
          var v = vars[key];
          return (typeof v === 'string' || typeof v === 'number') && String(v) !== '' ? String(v) : whole;
        });
    });
  }
  document.getElementById('btnPreview').addEventListener('click', function () {
    var cfgP = window.UKC_EDITOR || {};
    var isPrev = !!cfgP.preview;
    var old = document.getElementById('ukcPreviewOverlay');
    if (old) { if (!isPrev) old.remove(); return; }

    // En mode aperçu verrouillé, fermer ramène aux Documents (le concepteur n'est
    // pas censé rester visible derrière) ; sinon on retire simplement l'overlay.
    function dismiss() {
      if (isPrev) { location.href = cfgP.backUrl || '/admin/documents'; return; }
      var o = document.getElementById('ukcPreviewOverlay'); if (o) o.remove();
    }

    var overlay = document.createElement('div');
    overlay.id = 'ukcPreviewOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:' + (isPrev ? '#1e293b' : 'rgba(15,23,42,.88)') + ';display:flex;align-items:center;justify-content:center;padding:24px;overflow:auto;';

    var pw = page.offsetWidth || 900, ph = page.offsetHeight || 636;
    var scale = Math.min(1, (window.innerWidth - 80) / pw, (window.innerHeight - 120) / ph);

    var clone = page.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.transform = 'none';
    clone.style.margin = '0';
    clone.style.boxShadow = '0 20px 60px rgba(0,0,0,.5)';
    clone.querySelectorAll('.resize, .ed-guide, .ed-guide-label').forEach(function (n) { n.remove(); });
    // L'aperçu doit montrer le document, pas ses jetons : mêmes valeurs
    // d'exemple que l'aperçu serveur, pour que les deux coïncident.
    fillTokens(clone);
    clone.querySelectorAll('.ed-el').forEach(function (n) {
      n.style.outline = 'none'; n.style.border = 'none'; n.style.boxShadow = 'none';
      n.classList.remove('selected');
    });

    var frame = document.createElement('div');
    frame.style.cssText = 'transform:scale(' + scale + ');transform-origin:center center;';
    frame.appendChild(clone);

    var close = document.createElement('button');
    close.type = 'button';
    close.textContent = isPrev ? '✕ Fermer' : '✕ Fermer l’aperçu';
    close.style.cssText = 'position:fixed;top:16px;right:20px;z-index:10000;background:#fff;color:#1a2332;border-radius:8px;padding:9px 16px;font-weight:700;font-family:inherit;font-size:13.5px;box-shadow:0 4px 14px rgba(0,0,0,.3);cursor:pointer;';
    close.addEventListener('click', dismiss);

    overlay.addEventListener('click', function (e) { if (e.target === overlay) dismiss(); });
    function onEsc(e) { if (e.key === 'Escape') { document.removeEventListener('keydown', onEsc); dismiss(); } }
    document.addEventListener('keydown', onEsc);

    overlay.appendChild(frame);
    overlay.appendChild(close);
    if (isPrev) {
      var tag = document.createElement('div');
      tag.textContent = 'Aperçu du modèle - valeurs d’exemple';
      tag.style.cssText = 'position:fixed;top:18px;left:20px;z-index:10000;color:#cbd5e1;font-family:inherit;font-size:13px;';
      overlay.appendChild(tag);
    }
    document.body.appendChild(overlay);
  });
  document.getElementById('btnBlank').addEventListener('click', function () {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = {};
    selected = null;
    refreshLayers();
    toast('Page vierge - A4 paysage');
  });
  function clearCanvas() {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = {};
    window.__ukcGroupOrder = [];
    selected = null;
    styleBar.classList.add('disabled');
  }

  // Maquette officielle UK x Parakou commune (participation, modération…) : même
  // cadre, logos, typographie (Times titre + Lato corps), QR et bloc signataire ;
  // seuls le TITRE et le CORPS varient. ⚠️ Doit rester identique à
  // LayoutSanitizer::officialAttestation() (PHP).
  function officialTpl(title, body, titleSize) {
    var SERIF = "'Times New Roman', Times, serif";
    var SANS = "'Lato', system-ui, sans-serif";
    var DARK = '#1a2332';
    var LOGOS = (window.UKC_EDITOR || {}).logos || {};
    var imgHtml = function (src) {
      return '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">';
    };
    return [
      { type: 'logo', label: 'Logo UK', x: 135, y: 26, w: 230, h: 60, uploadSrc: LOGOS.uk || '/img/logo-uk.png', html: imgHtml(LOGOS.uk || '/img/logo-uk.png') },
      { type: 'logo', label: 'Logo Parakou', x: 620, y: 26, w: 260, h: 60, uploadSrc: LOGOS.parakou || '/img/attestations/logo-parakou.png', html: imgHtml(LOGOS.parakou || '/img/attestations/logo-parakou.png') },
      { type: 'text', label: 'Titre', x: 120, y: 104, w: 660, text: title, font: SERIF, size: titleSize || 28, weight: 700, color: DARK, align: 'center' },
      { type: 'text', label: 'Sous-titre', x: 120, y: 152, w: 660, text: 'Décernée à', font: SANS, size: 15, weight: 500, color: '#2b3340', align: 'center' },
      { type: 'text', label: 'Destinataire', x: 120, y: 178, w: 660, text: '[recipient.civility] [recipient.name]', font: SANS, size: 23, weight: 800, color: DARK, align: 'center' },
      { type: 'text', label: 'Corps', x: 125, y: 234, w: 648, text: body, font: SANS, size: 14, weight: 500, color: DARK, leading: 1.4 },
      { type: 'text', label: 'Thème (libellé)', x: 125, y: 316, w: 195, text: 'Thème du colloque :', font: SANS, size: 14, weight: 700, color: DARK },
      { type: 'text', label: 'Thème', x: 330, y: 316, w: 436, text: '[edition.theme]', font: SANS, size: 14, weight: 700, color: DARK, leading: 1.3 },
      { type: 'text', label: 'Formule', x: 125, y: 408, w: 648, text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', font: SANS, size: 14, weight: 500, color: DARK },
      { type: 'qr', label: 'QR vérification', x: 120, y: 492, w: 76, qrUrl: DEFAULT_QR_URL },
      { type: 'text', label: 'Lieu / date', x: 490, y: 452, w: 350, text: 'Fait à Kara, le [certificate.issued_on]', font: SANS, size: 14, weight: 500, color: DARK },
      { type: 'text', label: 'Fonction', x: 490, y: 476, w: 350, text: 'Le [signatory.role]', font: SANS, size: 15, weight: 700, color: DARK },
      { type: 'text', label: 'Signataire', x: 490, y: 556, w: 350, text: '[signatory.name]', font: SANS, size: 17, weight: 700, color: DARK },
      { type: 'text', label: 'Identifiant', x: 30, y: 592, w: 430, text: 'Identifiant de l’attestation : [certificate.uuid]', font: SANS, size: 11, weight: 700, color: DARK }
    ];
  }

  function loadTemplate(key) {
    clearCanvas();
    var T = {
      participation: officialTpl('ATTESTATION DE PARTICIPATION', 'pour avoir participé au [edition.name], [edition.dates], à [edition.location].'),
      moderation: officialTpl('ATTESTATION DE MODÉRATION', 'pour avoir modéré une session lors du [edition.name], [edition.dates], à [edition.location].'),
      rapporteur: officialTpl('ATTESTATION DE RAPPORTEUR', 'pour avoir servi en tant que rapporteur lors du [edition.name], [edition.dates], à [edition.location].'),
      organisation: officialTpl('ATTESTATION DE PARTICIPATION À L’ORGANISATION', 'pour avoir travaillé en appui au comité d’organisation, lors du [edition.name], [edition.dates], à [edition.location].', 24),
      comite_organisation: officialTpl('ATTESTATION DU COMITÉ D’ORGANISATION', 'pour avoir été membre du comité d’organisation du [edition.name], [edition.dates], à [edition.location].'),
      // Attestation de FORMATION : design distinct (coins colorés), première personne,
      // signature du responsable + jusqu'à deux formateurs (élément « signatures »).
      // ⚠️ Doit rester identique à LayoutSanitizer::attestationFormation() (PHP).
      formation: (function () {
        var SERIF = "'Times New Roman', Times, serif";
        var SANS = "'Lato', system-ui, sans-serif";
        var DARK = '#1a2332';
        var LOGOS = (window.UKC_EDITOR || {}).logos || {};
        var imgHtml = function (src) {
          return '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">';
        };
        return [
          { type: 'logo', label: 'Logo UK', x: 56, y: 44, w: 178, h: 54, uploadSrc: LOGOS.uk || '/img/logo-uk.png', html: imgHtml(LOGOS.uk || '/img/logo-uk.png') },
          { type: 'logo', label: 'Logo Parakou', x: 726, y: 44, w: 128, h: 78, uploadSrc: LOGOS.parakou || '/img/attestations/logo-parakou.png', html: imgHtml(LOGOS.parakou || '/img/attestations/logo-parakou.png') },
          { type: 'text', label: 'Titre', x: 80, y: 118, w: 740, text: 'ATTESTATION DE FORMATION', font: SERIF, size: 38, weight: 700, color: DARK, align: 'center' },
          { type: 'text', label: 'Je soussigné', x: 70, y: 186, w: 760, text: 'Je soussigné, [signatory.name], [signatory.role], atteste que', font: SANS, size: 14, weight: 500, color: DARK },
          { type: 'text', label: 'Destinataire', x: 70, y: 220, w: 760, text: '[recipient.civility] [recipient.name]', font: SANS, size: 22, weight: 800, color: DARK, align: 'center' },
          { type: 'text', label: 'Corps', x: 70, y: 272, w: 765, text: 'a suivi avec diligence la formation sur [formation.title], tenue en marge des travaux du [edition.name], qui s’est tenu [edition.dates] à [edition.location], sur le thème : « [edition.theme] ».', font: SANS, size: 14, weight: 500, color: DARK, leading: 1.45 },
          { type: 'text', label: 'Formule', x: 70, y: 398, w: 760, text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', font: SANS, size: 14, weight: 500, color: DARK },
          { type: 'signatures', label: 'Signatures formateurs', x: 70, y: 470, w: 350, h: 92, size: 11, names: true },
          { type: 'qr', label: 'QR vérification', x: 436, y: 486, w: 66, qrUrl: DEFAULT_QR_URL },
          { type: 'text', label: 'Lieu / date', x: 500, y: 438, w: 360, text: 'Fait à Kara, le [certificate.issued_on]', font: SANS, size: 13, weight: 500, color: DARK },
          { type: 'text', label: 'Fonction', x: 500, y: 470, w: 360, text: 'Le [signatory.role],', font: SANS, size: 14, weight: 700, color: DARK },
          { type: 'text', label: 'Responsable', x: 500, y: 558, w: 360, text: '[signatory.name]', font: SANS, size: 16, weight: 700, color: DARK },
          { type: 'text', label: 'Référence', x: 40, y: 600, w: 520, text: 'Référence de l’attestation : [certificate.uuid]', font: SANS, size: 11, weight: 700, color: DARK }
        ];
      })(),
      // Attestation de COMMUNICATION (modèle officiel UK x Université de Parakou).
      // ⚠️ Doit rester identique à LayoutSanitizer::attestationCommunication() (PHP) :
      // les deux décrivent la même maquette. Le cadre décoratif est porté par l'image
      // de fond ; logos et texte sont éditables. Jetons de la palette ([espace.cle]).
      // Titre en Times (dompdf : Times natif), corps en Lato (hébergée) : rendu
      // identique écran/PDF, fidèle à la typographie du modèle.
      communication: (function () {
        var SERIF = "'Times New Roman', Times, serif";
        var SANS = "'Lato', system-ui, sans-serif";
        var DARK = '#1a2332';
        var LOGOS = (window.UKC_EDITOR || {}).logos || {};
        var imgHtml = function (src) {
          return '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">';
        };
        return [
          // Logos en éléments image : UK = logo large des Réglages, Parakou = asset livré.
          { type: 'logo', label: 'Logo UK', x: 135, y: 26, w: 230, h: 60, uploadSrc: LOGOS.uk || '/img/logo-uk.png', html: imgHtml(LOGOS.uk || '/img/logo-uk.png') },
          { type: 'logo', label: 'Logo Parakou', x: 620, y: 26, w: 260, h: 60, uploadSrc: LOGOS.parakou || '/img/attestations/logo-parakou.png', html: imgHtml(LOGOS.parakou || '/img/attestations/logo-parakou.png') },
          { type: 'text', label: 'Titre', x: 120, y: 104, w: 660, text: 'ATTESTATION DE COMMUNICATION', font: SERIF, size: 28, weight: 700, color: DARK, align: 'center' },
          { type: 'text', label: 'Sous-titre', x: 120, y: 152, w: 660, text: 'Décernée à', font: SANS, size: 15, weight: 500, color: '#2b3340', align: 'center' },
          { type: 'text', label: 'Destinataire', x: 120, y: 178, w: 660, text: '[recipient.civility] [recipient.name]', font: SANS, size: 23, weight: 800, color: DARK, align: 'center' },
          { type: 'text', label: 'Corps', x: 125, y: 226, w: 640, text: 'pour avoir participé et communiqué au [edition.name], [edition.dates], à [edition.location].', font: SANS, size: 14, weight: 500, color: DARK, leading: 1.4 },
          { type: 'text', label: 'Thème (libellé)', x: 125, y: 286, w: 195, text: 'Thème du colloque :', font: SANS, size: 14, weight: 700, color: DARK },
          { type: 'text', label: 'Thème', x: 330, y: 286, w: 436, text: '[edition.theme]', font: SANS, size: 14, weight: 700, color: DARK, leading: 1.3 },
          { type: 'text', label: 'Titre com. (libellé)', x: 125, y: 350, w: 200, text: 'Titre de la communication :', font: SANS, size: 14, weight: 700, color: DARK },
          { type: 'text', label: 'Titre com.', x: 330, y: 350, w: 436, text: '[communication.title]', font: SANS, size: 14, weight: 700, color: DARK, leading: 1.3 },
          { type: 'text', label: 'Type (libellé)', x: 125, y: 414, w: 200, text: 'Type de communication :', font: SANS, size: 14, weight: 700, color: DARK },
          { type: 'text', label: 'Type', x: 330, y: 414, w: 220, text: '[communication.type]', font: SANS, size: 14, weight: 700, color: DARK },
          { type: 'text', label: 'Formule', x: 125, y: 460, w: 648, text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', font: SANS, size: 14, weight: 500, color: DARK },
          { type: 'qr', label: 'QR vérification', x: 120, y: 500, w: 76, qrUrl: DEFAULT_QR_URL },
          { type: 'text', label: 'Lieu / date', x: 490, y: 490, w: 350, text: 'Fait à Kara, le [certificate.issued_on]', font: SANS, size: 14, weight: 500, color: DARK },
          { type: 'text', label: 'Fonction', x: 490, y: 514, w: 350, text: 'Le [signatory.role]', font: SANS, size: 15, weight: 700, color: DARK },
          { type: 'text', label: 'Signataire', x: 490, y: 560, w: 350, text: '[signatory.name]', font: SANS, size: 17, weight: 700, color: DARK },
          { type: 'text', label: 'Identifiant', x: 30, y: 588, w: 430, text: 'Identifiant de l’attestation : [certificate.uuid]', font: SANS, size: 11, weight: 700, color: DARK }
        ];
      })(),
      'formation-csiuk': [
        { type: 'logo', label: 'Logo UK', x: 48, y: 32, w: 170, html: '<div class="mark">UK</div> UNIVERSITÉ DE KARA' },
        { type: 'logo', label: 'Logo UP', x: 680, y: 32, w: 180, html: 'UNIVERSITÉ DE PARAKOU' },
        { type: 'text', label: 'Titre', x: 80, y: 110, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
        { type: 'text', label: 'Intro', x: 80, y: 170, w: 740, cls: 'sub', text: 'Je soussigné, [signatory.name], Président du Comité d’Organisation, atteste que' },
        { type: 'text', label: 'Destinataire', x: 100, y: 220, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 280, w: 740, cls: 'sub', text: 'a suivi avec diligence la formation sur la valorisation des résultats de recherche, tenue en marge des travaux du colloque scientifique international de l’Université de Kara, en collaboration avec l’Université de Parakou ([edition.dates]), sur le thème : [edition.theme].' },
        { type: 'text', label: 'Formule', x: 80, y: 390, w: 740, cls: 'sub', text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 520, y: 460, w: 320, cls: 'sub', text: 'Fait à Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 500, w: 320, cls: 'sub', text: 'Le Président du Comité d’Organisation,' },
        { type: 'text', label: 'Signataire', x: 520, y: 550, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 64, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Référence', x: 48, y: 580, w: 480, cls: 'sub', text: 'Référence : [certificate.uuid]' }
      ],
      'formation-forvarrito': [
        { type: 'logo', label: 'Logo For-VaRRITO', x: 40, y: 24, w: 200, html: 'For-VaRRITO' },
        { type: 'logo', label: 'Logo UL', x: 420, y: 28, w: 140, html: 'Univ. Lome' },
        { type: 'logo', label: 'Logo UK', x: 700, y: 28, w: 160, html: '<div class="mark">UK</div> UNIVERSITÉ DE KARA' },
        { type: 'text', label: 'Titre', x: 80, y: 100, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
        { type: 'text', label: 'Intro', x: 80, y: 160, w: 740, cls: 'sub', text: 'Je soussigné, [signatory.name], Coordinateur du projet For-VaRRITO, atteste que' },
        { type: 'text', label: 'Destinataire', x: 100, y: 220, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 290, w: 740, cls: 'sub', text: 'a suivi avec diligence et réussite la formation sur l’organisation de la recherche et de l’innovation, la protection de la propriété intellectuelle, le transfert de technologie et la valorisation économique des résultats de recherche ([edition.dates]).' },
        { type: 'text', label: 'Formule', x: 80, y: 400, w: 740, cls: 'sub', text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 80, y: 470, w: 320, cls: 'sub', text: 'Fait à Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 470, w: 320, cls: 'sub', text: 'Le Coordinateur du projet,' },
        { type: 'text', label: 'Signataire', x: 520, y: 530, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'text', label: 'Partenaires', x: 80, y: 540, w: 300, cls: 'sub', text: 'UE - AUF - RRIWA' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 56, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Référence', x: 48, y: 580, w: 500, cls: 'sub', text: 'Référence : [certificate.uuid] - forvarrito.tg' }
      ]
    };
    var list = T[key] || T.participation;
    list.forEach(function (cfg) { createEl(cfg); });
    // fond selon modele
    var bgEl = document.getElementById('pageBg');
    page.classList.remove('bg-none', 'bg-simple', 'bg-gold', 'bg-bands', 'bg-custom', 'bg-plain');
    if (key === 'communication' || key === 'participation' || key === 'moderation' || key === 'rapporteur' || key === 'organisation' || key === 'comite_organisation') {
      // Cadre décoratif officiel (motifs kente, hélice ADN) partagé par ces modèles.
      bgEl.style.backgroundImage = "url('/img/attestations/attestation-communication-bg.png')";
      bgEl.style.backgroundSize = '100% 100%';
    } else if (key === 'formation') {
      // Cadre officiel de l'attestation de formation (coins colorés, carte grise).
      bgEl.style.backgroundImage = "url('/img/attestations/attestation-formation-bg.png')";
      bgEl.style.backgroundSize = '100% 100%';
    } else if (key === 'formation-csiuk') {
      bgEl.style.backgroundImage = "url('/img/bg-attestation-uk.png')";
      bgEl.style.backgroundSize = '100% 100%';
    } else if (key === 'formation-forvarrito') {
      bgEl.style.backgroundImage = '';
      page.classList.add('bg-gold');
    } else {
      bgEl.style.backgroundImage = "url('/img/bg-attestation.png')";
      bgEl.style.backgroundSize = '100% 100%';
    }
    refreshLayers();
    pushHistory();
    selectEl(null);
  }

  document.querySelectorAll('.ed-tpl-card').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('.ed-tpl-card').forEach(function (c) { c.classList.remove('active'); });
      card.classList.add('active');
      loadTemplate(card.dataset.tpl);
      toast('Design « ' + card.dataset.tpl + ' » charge');
    });
  });

  /* Keyboard shortcuts */
  document.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    var typing = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;

    // Text editing mode: don't steal most keys
    if (selected && isEditing(selected)) return;

    if ((e.key === 'Delete' || e.key === 'Backspace') && selected && !typing) {
      e.preventDefault();
      deleteSelected();
      return;
    }
    if ((e.ctrlKey || e.metaKey) && !typing) {
      if ((e.key === 'z' || e.key === 'Z') && e.shiftKey) { e.preventDefault(); redo(); return; }
      if (e.key === 'z' || e.key === 'Z') { e.preventDefault(); undo(); return; }
      if (e.key === 'l' || e.key === 'L') {
        e.preventDefault();
        if (selected) toggleLock(selected);
        return;
      }
      if (e.key === 'y' || e.key === 'Y') { e.preventDefault(); redo(); return; }
      if (e.key === 'd' || e.key === 'D') { e.preventDefault(); duplicateLayer(); }
      if (e.key === 'g' || e.key === 'G') { e.preventDefault(); groupSelected(); }
      if (e.key === 'b' || e.key === 'B') {
        e.preventDefault();
        var b = document.querySelector('[data-style="bold"]');
        if (b) b.click();
      }
      if (e.key === 'i' || e.key === 'I') {
        e.preventDefault();
        var it = document.querySelector('[data-style="italic"]');
        if (it) it.click();
      }
      if (e.key === 'u' || e.key === 'U') {
        e.preventDefault();
        var u = document.querySelector('[data-style="underline"]');
        if (u) u.click();
      }
      if (e.key === ']' && e.shiftKey) { e.preventDefault(); moveLayer('up'); }
      else if (e.key === ']') { e.preventDefault(); moveLayer('front'); }
      if (e.key === '[' && e.shiftKey) { e.preventDefault(); moveLayer('down'); }
      else if (e.key === '[') { e.preventDefault(); moveLayer('back'); }
      if (e.key === 's' || e.key === 'S') {
        e.preventDefault();
        document.getElementById('btnSave').click();
      }
    }
    // Enter starts edit on selected text
    if (e.key === 'Enter' && selected && selected.classList.contains('text') && !typing) {
      e.preventDefault();
      startTextEdit(selected);
    }

    // Arrow keys move selected element
    if (selected && !typing && !isEditing(selected) && !isLocked(selected)) {
      var step = e.shiftKey ? 10 : 1;
      var moved = false;
      var left = selected.offsetLeft;
      var top = selected.offsetTop;
      if (e.key === 'ArrowLeft') { left -= step; moved = true; }
      if (e.key === 'ArrowRight') { left += step; moved = true; }
      if (e.key === 'ArrowUp') { top -= step; moved = true; }
      if (e.key === 'ArrowDown') { top += step; moved = true; }
      if (moved) {
        e.preventDefault();
        left = Math.max(0, Math.min(left, page.clientWidth - 20));
        top = Math.max(0, Math.min(top, page.clientHeight - 20));
        selected.style.left = left + 'px';
        selected.style.top = top + 'px';
        clearTimeout(window.__arrowHistTimer);
        window.__arrowHistTimer = setTimeout(function () { pushHistory(); }, 400);
      }
    }
  });

  /* Input modal wiring */
  document.getElementById('inputModalClose').addEventListener('click', closeInputModal);
  document.getElementById('inputModalCancel').addEventListener('click', closeInputModal);
  document.getElementById('inputModal').addEventListener('click', function (e) {
    if (e.target.id === 'inputModal') closeInputModal();
  });
  document.getElementById('inputModalOk').addEventListener('click', function () {
    var val = document.getElementById('inputModalField').value.trim();
    var cb = inputModalCb;
    closeInputModal();
    if (cb) cb(val);
  });
  document.getElementById('inputModalField').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('inputModalOk').click();
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      closeInputModal();
    }
  });

  /* Stylebar starts disabled */
  styleBar.classList.add('disabled');

  document.getElementById('fbLock').addEventListener('click', function (e) {
    e.stopPropagation();
    if (selected) toggleLock(selected);
  });
  document.getElementById('fbUnlock').addEventListener('click', function (e) {
    e.stopPropagation();
    if (selected) toggleLock(selected);
  });
  document.getElementById('fbDup').addEventListener('click', function (e) {
    e.stopPropagation();
    duplicateLayer();
  });
  document.getElementById('fbDel').addEventListener('click', function (e) {
    e.stopPropagation();
    deleteSelected();
  });
  // Prevent float bar mousedown from clearing selection
  document.getElementById('floatBar').addEventListener('mousedown', function (e) {
    e.stopPropagation();
  });

  /* ---------- Assistant IA ---------- */
  var aiFiles = [];

  function renderAiFiles() {
    var box = document.getElementById('aiFileList');
    box.innerHTML = '';
    aiFiles.forEach(function (f, i) {
      var row = document.createElement('div');
      row.className = 'ai-file';
      row.innerHTML = '<span>📎 ' + f.name + '</span><span style="color:var(--ukc-light);font-size:11px;">' + Math.round(f.size / 1024) + ' Ko</span>';
      var rm = document.createElement('span');
      rm.className = 'rm';
      rm.textContent = '×';
      rm.title = 'Retirer';
      rm.addEventListener('click', function () {
        aiFiles.splice(i, 1);
        renderAiFiles();
      });
      row.appendChild(rm);
      box.appendChild(row);
    });
  }

  function addAiFile(file) {
    if (!file) return;
    var ok = /image\/|pdf|word|document|msword|officedocument/i.test(file.type) ||
             /\.(png|jpe?g|gif|webp|pdf|docx?)$/i.test(file.name);
    if (!ok) {
      toast('Format non pris en charge (image, PDF ou Word)');
      return;
    }
    if (file.size > 8 * 1024 * 1024) {
      toast('Fichier trop volumineux (max 8 Mo)');
      return;
    }
    aiFiles.push(file);
    renderAiFiles();
  }

  var aiDrop = document.getElementById('aiDrop');
  var aiFileInput = document.getElementById('aiFile');
  if (aiDrop) {
    aiDrop.addEventListener('click', function () { aiFileInput.click(); });
    aiDrop.addEventListener('dragover', function (e) {
      e.preventDefault();
      aiDrop.classList.add('dragover');
    });
    aiDrop.addEventListener('dragleave', function () { aiDrop.classList.remove('dragover'); });
    aiDrop.addEventListener('drop', function (e) {
      e.preventDefault();
      aiDrop.classList.remove('dragover');
      Array.prototype.forEach.call(e.dataTransfer.files, addAiFile);
    });
  }
  if (aiFileInput) {
    aiFileInput.addEventListener('change', function (e) {
      Array.prototype.forEach.call(e.target.files || [], addAiFile);
      e.target.value = '';
    });
  }

  function runAiAssistant() {
    var prompt = (document.getElementById('aiPrompt').value || '').trim();
    if (!prompt && !aiFiles.length) {
      toast('Décrivez le modèle voulu ou joignez un fichier');
      return;
    }
    var btn = document.getElementById('aiRun');
    btn.disabled = true;
    btn.textContent = 'Analyse en cours…';

    // Simulation locale (maquette) : construit une mise en page a partir du prompt
    // En production : POST vers endpoint IA (analyse PDF/image + generation calques)
    setTimeout(function () {
      var lower = prompt.toLowerCase();
      var isFormation = /formation/.test(lower);
      var isComm = /communication|orale|poster/.test(lower);
      var title = isFormation ? 'ATTESTATION DE FORMATION' :
                  isComm ? 'ATTESTATION DE COMMUNICATION' :
                  'ATTESTATION DE PARTICIPATION';

      // Clear canvas elements optional: improve = keep some structure
      page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
      groups = {};
      window.__ukcGroupOrder = [];

      createEl({ type: 'logo', id: 'ai-logo-uk', label: 'Logo UK', x: 48, y: 36, w: 150, html: '<div class="mark">UK</div> UNIVERSITÉ DE KARA' });
      createEl({ type: 'logo', id: 'ai-logo-up', label: 'Logo UP', x: 680, y: 36, w: 170, html: 'UNIVERSITÉ DE PARAKOU' });
      createEl({ type: 'text', id: 'ai-title', label: 'Titre', x: 80, y: 110, w: 740, cls: 'heading', text: title });
      createEl({ type: 'text', id: 'ai-intro', label: 'Intro', x: 100, y: 170, w: 700, cls: 'sub', text: 'Je soussigné, Prof. Padabo KADOUZA, Président du Comité d’Organisation, atteste que' });
      createEl({ type: 'text', id: 'ai-name', label: 'Destinataire', x: 150, y: 220, w: 600, cls: 'name attr', text: '[recipient.name]' });
      var body = prompt
        ? ('Modele genere a partir de votre demande : « ' + prompt.slice(0, 160) + (prompt.length > 160 ? '…' : '') + ' ». Personnalisez les textes et champs sur le canvas.')
        : 'a participe avec assiduite aux travaux du colloque scientifique international de l Universite de Kara.';
      createEl({ type: 'text', id: 'ai-body', label: 'Corps', x: 80, y: 280, w: 740, cls: 'sub', text: body });
      createEl({ type: 'text', id: 'ai-close', label: 'Formule', x: 80, y: 380, w: 740, cls: 'sub', text: 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.' });
      createEl({ type: 'text', id: 'ai-place', label: 'Lieu / date', x: 520, y: 450, w: 320, cls: 'sub', text: 'Fait à Kara, le [certificate.issued_on]' });
      createEl({ type: 'text', id: 'ai-sign', label: 'Signataire', x: 520, y: 520, w: 320, cls: '', text: '[signatory.name]' });
      createEl({ type: 'qr', id: 'ai-qr', label: 'QR Verification', x: 48, y: 480, w: 72, qrUrl: DEFAULT_QR_URL });
      createEl({ type: 'text', id: 'ai-ref', label: 'Référence', x: 48, y: 570, w: 400, cls: 'sub', text: 'Référence : [certificate.uuid]' });

      if (aiFiles.length) {
        toast('Modele genere (ref. ' + aiFiles.length + ' fichier(s) + prompt)');
      } else {
        toast('Modèle généré à partir de votre description');
      }
      refreshLayers();
      pushHistory();
      selectEl(null);
      document.querySelector('.ed-rail button[data-panel="layers"]').click();
      btn.disabled = false;
      btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/></svg> Generer';
    }, 1200);
  }

  var aiRunBtn = document.getElementById('aiRun');
  if (aiRunBtn) aiRunBtn.addEventListener('click', runAiAssistant);

  /* ---------- Hydratation depuis la mise en page enregistrée ---------- */
  (function () {
    var cfg = window.UKC_EDITOR || {};
    // Le nom du modèle et sa portée sont rendus par Blade à partir du titre
    // enregistré : ne rien réécrire ici, sinon chaque ouverture repréfixait le nom.

    var snap = cfg.editorState;
    if (snap && snap.els && snap.els.length) {
      try {
        // Normalise les anciens chemins de fond relatifs vers /img/.
        if (snap.bgImage) snap.bgImage = snap.bgImage.replace(/(['"(])(?:\.?\/)?(?:admin\/)?(bg-attestation)/g, '$1/img/$2');
        restoreSnapshot(snap);
        refreshLayers();
        history = [snapshot()];
        historyIndex = 0;
        updateUndoButtons();
        return;
      } catch (e) { /* retombe sur le template intégré du type si l'instantané est invalide */ }
    }
    // Aucun état enregistré : charge le template intégré correspondant au type.
    var TPL_KEY = { participation: 'participation', communication: 'communication', moderation: 'moderation', rapporteur: 'rapporteur', organisation: 'organisation', comite_organisation: 'comite_organisation', formation: 'formation' };
    var key = TPL_KEY[cfg.type];
    if (cfg.kind !== 'badge' && key && typeof loadTemplate === 'function') {
      try {
        loadTemplate(key);
        history = [snapshot()];
        historyIndex = 0;
        updateUndoButtons();
      } catch (e) { /* conserve le rendu par défaut si le template intégré échoue */ }
    }
  })();

  // La construction initiale (baseline, hydratation, template intégré) a marqué le
  // document « modifié » : on repart d'un état propre, rien n'a été touché par l'utilisateur.
  dirty = false;

  /* ---------- Mode APERÇU verrouillé : ouvre l'aperçu exact du modèle ---------- */
  (function () {
    var cfg = window.UKC_EDITOR || {};
    if (!cfg.preview) return;
    // Le canevas vient d'être bâti (hydratation ci-dessus) : on ouvre l'aperçu
    // plein écran, qui clone ce canevas exact et remplace les jetons.
    requestAnimationFrame(function () {
      var b = document.getElementById('btnPreview');
      if (b) b.click();
    });
  })();
})();
