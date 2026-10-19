(function () {
  var page = document.getElementById('page');
  var layersList = document.getElementById('layersList');
  var styleBar = document.getElementById('styleBar');
  var guidesEl = document.getElementById('guides');
  var selected = null;
  var elId = 0;
  var groupId = 0;
  var groups = {};
  var drag = null;
  var resize = null;
  var history = [];
  var historyIndex = -1;
  var historyLock = false;
  // Modifications non enregistrées : prévenir avant de quitter (cf. template-designer.js).
  var dirty = false;
  function markDirty() { dirty = true; }
  var SNAP = 6;
  var zoomLevel = 1;
  var DEFAULT_QR_URL = '{verify_url}';
  var inputModalCb = null;

  var EYE = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
  var EYE_OFF = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';

  function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 2200);
  }
  function isLocked(el) { return el && el.dataset.locked === '1'; }
  function isEditing(el) { return el && el.classList.contains('editing'); }
  function getEls() { return Array.prototype.slice.call(page.querySelectorAll('.ed-el')); }

  function qrImageUrl(data, size) {
    size = size || 120;
    var value = data || DEFAULT_QR_URL;
    var vars = ((window.UKC_EDITOR || {}).vars || {});
    value = value.replace(/\[([A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)?)\]|\{([A-Za-z0-9_]+)\}/g, function (whole, bracket, brace) {
      var resolved = vars[bracket || brace];
      return (typeof resolved === 'string' || typeof resolved === 'number') && String(resolved) !== '' ? String(resolved) : whole;
    });
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&margin=4&data=' + encodeURIComponent(value);
  }
  function setQrContent(el, url) {
    el.dataset.qrUrl = url || DEFAULT_QR_URL;
    el.innerHTML = '';
    var img = document.createElement('img');
    img.alt = 'QR';
    img.src = qrImageUrl(el.dataset.qrUrl, 140);
    el.appendChild(img);
    appendResizeHandles(el);
  }

  function applyZoom() {
    document.getElementById('canvasInner').style.transform = 'scale(' + zoomLevel + ')';
    document.getElementById('zoomLabel').textContent = Math.round(zoomLevel * 100) + '%';
  }

  function clearGuides() { if (guidesEl) guidesEl.innerHTML = ''; }
  function addGuide(type, pos) {
    var g = document.createElement('div');
    g.className = type === 'x' ? 'ed-guide-x' : 'ed-guide-y';
    if (type === 'x') g.style.top = pos + 'px'; else g.style.left = pos + 'px';
    guidesEl.appendChild(g);
  }
  function addMarginH(x1, x2, y, gap) {
    var left = Math.min(x1, x2), width = Math.abs(x2 - x1);
    if (width < 1) return;
    var line = document.createElement('div');
    line.className = 'ed-margin-line';
    line.style.cssText = 'left:' + left + 'px;top:' + y + 'px;width:' + width + 'px;height:1px;';
    guidesEl.appendChild(line);
    var lab = document.createElement('div');
    lab.className = 'ed-margin-label';
    lab.textContent = Math.round(gap) + ' px';
    lab.style.cssText = 'left:' + (left + width / 2) + 'px;top:' + (y - 14) + 'px;transform:translateX(-50%);';
    guidesEl.appendChild(lab);
  }
  function addMarginV(y1, y2, x, gap) {
    var top = Math.min(y1, y2), height = Math.abs(y2 - y1);
    if (height < 1) return;
    var line = document.createElement('div');
    line.className = 'ed-margin-line';
    line.style.cssText = 'left:' + x + 'px;top:' + top + 'px;width:1px;height:' + height + 'px;';
    guidesEl.appendChild(line);
    var lab = document.createElement('div');
    lab.className = 'ed-margin-label';
    lab.textContent = Math.round(gap) + ' px';
    lab.style.cssText = 'left:' + (x + 6) + 'px;top:' + (top + height / 2) + 'px;transform:translateY(-50%);';
    guidesEl.appendChild(lab);
  }

  function snapDrag(el, x, y) {
    clearGuides();
    var w = el.offsetWidth, h = el.offsetHeight;
    var pw = page.clientWidth, ph = page.clientHeight;
    var targetsX = [0, pw / 2, pw], targetsY = [0, ph / 2, ph], others = [];
    getEls().forEach(function (other) {
      if (other === el || other.style.display === 'none') return;
      var ox = other.offsetLeft, oy = other.offsetTop, ow = other.offsetWidth, oh = other.offsetHeight;
      targetsX.push(ox, ox + ow / 2, ox + ow);
      targetsY.push(oy, oy + oh / 2, oy + oh);
      others.push({ x: ox, y: oy, w: ow, h: oh });
    });
    function trySnap(val, targets, isX) {
      var best = val, dmin = SNAP + 1, hit = null;
      targets.forEach(function (t) {
        var candidates = isX ? [val, val + w / 2, val + w] : [val, val + h / 2, val + h];
        candidates.forEach(function (c, i) {
          var d = Math.abs(c - t);
          if (d < dmin) {
            dmin = d; hit = t;
            best = isX ? t - (i === 0 ? 0 : i === 1 ? w / 2 : w) : t - (i === 0 ? 0 : i === 1 ? h / 2 : h);
          }
        });
      });
      return dmin <= SNAP ? { val: best, guide: hit } : null;
    }
    var bestX = x, bestY = y;
    var sx = trySnap(x, targetsX, true), sy = trySnap(y, targetsY, false);
    if (sx) { bestX = sx.val; addGuide('y', sx.guide); }
    if (sy) { bestY = sy.val; addGuide('x', sy.guide); }
    bestX = Math.max(0, Math.min(bestX, pw - 20));
    bestY = Math.max(0, Math.min(bestY, ph - 20));
    others.forEach(function (o) {
      var gapRight = o.x - (bestX + w), gapLeft = bestX - (o.x + o.w);
      var gapBottom = o.y - (bestY + h), gapTop = bestY - (o.y + o.h);
      var overlapY = bestY < o.y + o.h && bestY + h > o.y;
      var overlapX = bestX < o.x + o.w && bestX + w > o.x;
      if (overlapY && gapRight >= 0 && gapRight <= 60) addMarginH(bestX + w, o.x, bestY + h / 2, gapRight);
      if (overlapY && gapLeft >= 0 && gapLeft <= 60) addMarginH(o.x + o.w, bestX, bestY + h / 2, gapLeft);
      if (overlapX && gapBottom >= 0 && gapBottom <= 60) addMarginV(bestY + h, o.y, bestX + w / 2, gapBottom);
      if (overlapX && gapTop >= 0 && gapTop <= 60) addMarginV(o.y + o.h, bestY, bestX + w / 2, gapTop);
    });
    return { x: bestX, y: bestY };
  }

  /* History */
  function snapshot() {
    return {
      els: getEls().map(function (el) {
        return {
          id: el.id, html: el.innerHTML, className: el.className,
          label: el.dataset.label, locked: el.dataset.locked, hidden: el.dataset.hidden,
          group: el.dataset.group || '', outline: el.dataset.outline || '', shape: el.dataset.shape || '',
          blend: el.dataset.blend || 'normal', qrUrl: el.dataset.qrUrl || '',
          style: {
            left: el.style.left, top: el.style.top, width: el.style.width, height: el.style.height,
            opacity: el.style.opacity, fontSize: el.style.fontSize, fontFamily: el.style.fontFamily,
            fontWeight: el.style.fontWeight, fontStyle: el.style.fontStyle,
            textDecoration: el.style.textDecoration, textTransform: el.style.textTransform,
            lineHeight: el.style.lineHeight, letterSpacing: el.style.letterSpacing,
            color: el.style.color, textAlign: el.style.textAlign,
            background: el.style.background, border: el.style.border,
            borderRadius: el.style.borderRadius,
            mixBlendMode: el.style.mixBlendMode, display: el.style.display
          }
        };
      }),
      bg: page.className,
      bgStyle: document.getElementById('pageBg').style.background || '',
      bgImage: document.getElementById('pageBg').style.backgroundImage || '',
      groups: JSON.parse(JSON.stringify(groups))
    };
  }
  function pushHistory() {
    if (historyLock) return;
    dirty = true;
    history = history.slice(0, historyIndex + 1);
    history.push(snapshot());
    if (history.length > 50) history.shift(); else historyIndex++;
    updateUndoButtons();
  }
  function restoreSnapshot(snap) {
    historyLock = true;
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = JSON.parse(JSON.stringify(snap.groups || {}));
    groupId = Math.max(groupId, ...Object.keys(groups).map(function (id) { return Number(id.slice(1)) || 0; }));
    elId = Math.max(elId, ...(snap.els || []).map(function (item) {
      var match = String(item.id || '').match(/^el-(\d+)$/);
      return match ? Number(match[1]) : 0;
    }));
    page.className = snap.bg || 'ed-page';
    document.getElementById('pageBg').style.background = snap.bgStyle || '';
    document.getElementById('pageBg').style.backgroundImage = snap.bgImage || '';
    syncFormatControls();
    document.querySelectorAll('.ed-tpl-card').forEach(function (card) { card.classList.remove('active'); });
    (snap.els || []).forEach(function (s) {
      var el = document.createElement('div');
      el.id = s.id; el.className = s.className;
      el.classList.remove('selected', 'editing');
      el.dataset.label = s.label || s.id;
      el.dataset.locked = s.locked || '0';
      el.dataset.hidden = s.hidden || '0';
      el.dataset.group = s.group || '';
      el.dataset.outline = s.outline || '';
      el.dataset.shape = s.shape || '';
      el.dataset.blend = s.blend || 'normal';
      if (s.qrUrl) el.dataset.qrUrl = s.qrUrl;
      el.innerHTML = s.html;
      Object.keys(s.style || {}).forEach(function (k) { if (s.style[k]) el.style[k] = s.style[k]; });
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div'); rz.className = 'resize'; el.appendChild(rz);
      }
      page.appendChild(el);
      paintBadgeIcon(el);
      bindEl(el);
    });
    selected = null;
    styleBar.classList.add('disabled');
    styleBar.classList.remove('is-line');
    hideFloatBar();
    refreshLayers();
    historyLock = false;
    updateUndoButtons();
  }
  function undo() { if (historyIndex <= 0) return; historyIndex--; restoreSnapshot(history[historyIndex]); toast('Annulé'); }
  function redo() { if (historyIndex >= history.length - 1) return; historyIndex++; restoreSnapshot(history[historyIndex]); toast('Rétabli'); }
  function updateUndoButtons() {
    document.getElementById('btnUndo').disabled = historyIndex <= 0;
    document.getElementById('btnRedo').disabled = historyIndex >= history.length - 1;
  }

  function appendResizeHandles(el) {
    el.querySelectorAll('.resize').forEach(function (n) { n.remove(); });
    ['nw', 'ne', 'sw', 'se'].forEach(function (corner) {
      var rz = document.createElement('div');
      rz.className = 'resize ' + corner;
      rz.dataset.corner = corner;
      el.appendChild(rz);
    });
  }

  function badgeIconSrc(el) {
    var img = el && el.querySelector('img[src]');
    if (!img) return '';
    var src = img.getAttribute('src') || '';
    return /^\/img\/badges\/icon-(location|calendar)\.(?:png|svg)$/.test(src) ? src : '';
  }
  function paintBadgeIcon(el, color) {
    var src = badgeIconSrc(el);
    if (!src) return false;
    el.classList.add('badge-icon');
    var img = el.querySelector('img[src]');
    var paint = el.querySelector('.ed-icon-paint');
    if (!paint) {
      paint = document.createElement('span');
      paint.className = 'ed-icon-paint';
      paint.setAttribute('aria-hidden', 'true');
      img.insertAdjacentElement('afterend', paint);
    }
    img.style.display = 'none';
    paint.style.maskImage = 'url("' + src + '")';
    paint.style.webkitMaskImage = 'url("' + src + '")';
    el.style.color = color || el.style.color || '#2e3440';
    return true;
  }

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

    if (cfg.type === 'logo') {
      el.classList.add('logo');
      el.innerHTML = cfg.html || 'LOGO';
    } else if (cfg.type === 'qr') {
      el.classList.add('qr');
      el.style.width = (cfg.w || 64) + 'px';
      el.style.height = (cfg.w || 64) + 'px';
    } else if (cfg.type === 'photo') {
      el.classList.add('photo');
      var pw = cfg.w || 64;
      el.style.width = pw + 'px';
      el.style.height = (cfg.h || pw) + 'px';
      el.innerHTML = '';
      var inner = document.createElement('div');
      inner.className = 'photo-inner';
      // Icône d'avatar (silhouette) comme repère de la photo, au lieu du mot « Photo ».
      inner.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.42 0-8 2.69-8 6v2h16v-2c0-3.31-3.58-6-8-6Z"/></svg>';
      el.appendChild(inner);
    } else if (cfg.type === 'shape') {
      el.classList.add('shape-box');
      el.dataset.shape = cfg.shape || 'rect';
      el.style.width = (cfg.w || 40) + 'px';
      el.style.height = (cfg.h || 40) + 'px';
      if (cfg.shape === 'circle') el.style.borderRadius = '50%';
      if (cfg.shape === 'line-h') { el.style.height = (cfg.h || 3) + 'px'; el.style.width = (cfg.w || 100) + 'px'; }
      if (cfg.shape === 'line-v') { el.style.width = (cfg.w || 3) + 'px'; el.style.height = (cfg.h || 80) + 'px'; }
      if (cfg.outline) { el.dataset.outline = '1'; el.style.background = 'transparent'; el.style.border = '2px solid #111'; }
    } else {
      el.classList.add('text');
      if (cfg.cls) cfg.cls.split(/\s+/).forEach(function (c) { if (c) el.classList.add(c); });
      el.textContent = cfg.text || 'Texte';
    }
    appendResizeHandles(el);
    page.appendChild(el);
    bindEl(el);
    if (cfg.type === 'qr') setQrContent(el, cfg.qrUrl || DEFAULT_QR_URL);
    refreshLayers();
    return el;
  }

  function bindEl(el) {
    el.addEventListener('mousedown', function (e) {
      if (isEditing(el)) return;
      if (isLocked(el)) { selectEl(el); e.preventDefault(); return; }
      if (e.target.classList.contains('resize')) {
        resize = {
          el: el, sx: e.clientX, sy: e.clientY,
          sw: el.offsetWidth, sh: el.offsetHeight,
          left: el.offsetLeft, top: el.offsetTop,
          corner: e.target.dataset.corner || 'se'
        };
        e.preventDefault(); e.stopPropagation(); return;
      }
      selectEl(el);
      drag = { el: el, ox: e.clientX - el.offsetLeft, oy: e.clientY - el.offsetTop };
      hideFloatBar();
      e.preventDefault();
    });
    el.addEventListener('dblclick', function (e) {
      if (isLocked(el)) { toast('Calque verrouillé'); return; }
      if (el.classList.contains('text')) {
        e.preventDefault(); startTextEdit(el);
      } else if (el.classList.contains('qr')) {
        e.preventDefault();
        openInputModal({
          title: 'URL du QR code',
          hint: 'Adresse ouverte au scan. Laissez {verify_url} pour chaque badge.',
          value: el.dataset.qrUrl || DEFAULT_QR_URL,
          onOk: function (url) { if (url) { setQrContent(el, url); pushHistory(); } }
        });
      }
    });
  }

  function startTextEdit(el) {
    if (!el.classList.contains('text') || isLocked(el)) return;
    stopTextEdit();
    el.classList.add('editing');
    el.contentEditable = 'true';
    el.focus();
  }
  function stopTextEdit() {
    var edited = false;
    page.querySelectorAll('.ed-el.editing').forEach(function (el) {
      el.classList.remove('editing');
      el.contentEditable = 'false';
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div'); rz.className = 'resize'; el.appendChild(rz);
      }
      edited = true;
    });
    if (edited) pushHistory();
  }

  function lineDirection(el) {
    if (!el || !el.classList.contains('shape-box')) return '';
    if (el.dataset.shape === 'line-h' || el.dataset.shape === 'line-v') return el.dataset.shape;
    var w = parseFloat(el.style.width) || el.offsetWidth;
    var h = parseFloat(el.style.height) || el.offsetHeight;
    if (h <= 40 && w >= h * 4) return 'line-h';
    if (w <= 40 && h >= w * 4) return 'line-v';
    return '';
  }

  function selectEl(el) {
    stopTextEdit();
    if (selected) selected.classList.remove('selected');
    selected = el;
    if (el) {
      el.classList.add('selected');
      styleBar.classList.toggle('disabled', isLocked(el));
      document.getElementById('lyControls').classList.add('show');
      var op = Math.round((parseFloat(el.style.opacity) || 1) * 100);
      document.getElementById('lyOpacity').value = op;
      document.getElementById('lyOpacityVal').textContent = op + '%';
      document.getElementById('lyBlend').value = el.dataset.blend || 'normal';
      document.getElementById('fontSize').value = parseInt(window.getComputedStyle(el).fontSize, 10) || 13;
      var direction = lineDirection(el);
      styleBar.classList.toggle('is-line', !!direction);
      if (direction) document.getElementById('lineThickness').value = Math.round(parseFloat(direction === 'line-h' ? el.style.height : el.style.width) || 3);
      syncTextControls(el, window.getComputedStyle(el));
      updateFloatBar();
    } else {
      styleBar.classList.add('disabled');
      styleBar.classList.remove('is-line');
      document.getElementById('lyControls').classList.remove('show');
      hideFloatBar();
    }
    layersList.querySelectorAll('.ed-layer').forEach(function (row) {
      row.classList.toggle('active', !!el && row.dataset.id === el.id);
    });
    updateLayerToolbar();
  }

  // Police du navigateur -> clé de liste blanche (même grammaire que template-designer).
  function fontKeyB(family) {
    var f = (family || '').toLowerCase();
    if (f.indexOf('playfair') > -1) return 'playfair';
    if (f.indexOf('times') > -1) return 'times';
    if (f.indexOf('georgia') > -1) return 'georgia';
    if (f.indexOf('lato') > -1) return 'lato';
    if (f.indexOf('arial') > -1 || f.indexOf('helvetica') > -1) return 'arial';
    return 'jost';
  }
  /* Synchronise la barre d'outils sur l'élément sélectionné (police, graisse,
     couleur, alignement) : le champ police reflète ce qu'on vient de choisir. */
  function syncTextControls(el, cs) {
    var famSel = document.getElementById('fontFamily');
    if (famSel) {
      var wantKey = fontKeyB(cs.fontFamily);
      Array.prototype.forEach.call(famSel.options, function (opt) { opt.selected = fontKeyB(opt.value) === wantKey; });
    }
    var wSel = document.getElementById('fontWeight');
    if (wSel) {
      var w = cs.fontWeight; if (w === 'normal') w = '400'; if (w === 'bold') w = '700';
      var wn = parseInt(w, 10) || 400;
      var opts = [400, 500, 600, 700, 800];
      wSel.value = String(opts.reduce(function (a, b) { return Math.abs(b - wn) < Math.abs(a - wn) ? b : a; }));
    }
    var colorInp = document.getElementById('fontColor');
    if (colorInp) {
      var sample = el.classList.contains('shape-box') && el.dataset.outline !== '1' ? cs.backgroundColor
        : el.classList.contains('shape-box') ? cs.borderTopColor : cs.color;
      var hex = rgbToHex(sample); if (hex) colorInp.value = hex;
    }
    var align = cs.textAlign; if (align === 'start') align = 'left'; if (align === 'end') align = 'right';
    document.querySelectorAll('[data-align]').forEach(function (b) { b.classList.toggle('active', b.dataset.align === align); });
    var weightOn = cs.fontWeight === '700' || cs.fontWeight === '800' || cs.fontWeight === 'bold';
    var italicOn = cs.fontStyle === 'italic';
    var underlineOn = (cs.textDecorationLine || cs.textDecoration || '').indexOf('underline') > -1;
    document.querySelectorAll('[data-style]').forEach(function (b) {
      var s = b.dataset.style;
      b.classList.toggle('active', (s === 'bold' && weightOn) || (s === 'italic' && italicOn) || (s === 'underline' && underlineOn));
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
    document.getElementById('fbLock').style.display = locked ? 'none' : 'flex';
    document.getElementById('fbUnlock').style.display = locked ? 'flex' : 'none';
    var elTop = selected.offsetTop, elLeft = selected.offsetLeft;
    var elW = selected.offsetWidth, elH = selected.offsetHeight;
    var top, left;
    if (page.clientHeight - (elTop + elH) >= 52) {
      top = elTop + elH + 10; left = elLeft + elW / 2;
      bar.style.transform = 'translateX(-50%)';
    } else {
      top = Math.max(4, elTop - 44); left = elLeft + elW / 2;
      bar.style.transform = 'translateX(-50%)';
    }
    bar.style.top = top + 'px';
    bar.style.left = left + 'px';
    bar.classList.add('show');
  }

  function updateLayerToolbar() {
    var has = !!selected;
    ['lyFront', 'lyUp', 'lyDown', 'lyBack', 'lyDup', 'lyGroup', 'lyDel'].forEach(function (id) {
      var n = document.getElementById(id);
      if (n) n.disabled = !has;
    });
  }

  document.addEventListener('mousemove', function (e) {
    if (drag && !isLocked(drag.el) && !isEditing(drag.el)) {
      var sn = snapDrag(drag.el, e.clientX - drag.ox, e.clientY - drag.oy);
      drag.el.style.left = sn.x + 'px';
      drag.el.style.top = sn.y + 'px';
    }
    if (resize && !isLocked(resize.el)) {
      var dx = e.clientX - resize.sx;
      var dy = e.clientY - resize.sy;
      if (resize.el.classList.contains('photo') || resize.el.classList.contains('qr')) {
        // Ratio carre : on prend le plus grand deplacement
        var delta = Math.max(dx, dy);
        if (e.shiftKey) {
          // Shift = libre (non carre)
          var nw = Math.max(32, resize.sw + dx);
          var nh = Math.max(32, resize.sh + dy);
          resize.el.style.width = nw + 'px';
          resize.el.style.height = nh + 'px';
        } else {
          var size = Math.max(32, Math.round(resize.sw + delta));
          resize.el.style.width = size + 'px';
          resize.el.style.height = size + 'px';
        }
      } else if (resize.el.classList.contains('shape-box')) {
        resize.el.style.width = Math.max(16, resize.sw + dx) + 'px';
        resize.el.style.height = Math.max(8, resize.sh + dy) + 'px';
      } else {
        resize.el.style.width = Math.max(24, resize.sw + dx) + 'px';
      }
      if (selected === resize.el) updateFloatBar();
    }
  });
  document.addEventListener('mouseup', function () {
    if (drag) { clearGuides(); pushHistory(); if (selected) updateFloatBar(); }
    if (resize) pushHistory();
    drag = null; resize = null;
  });
  page.addEventListener('mousedown', function (e) {
    if (e.target === page || e.target.classList.contains('ed-page-bg') || e.target.id === 'guides') {
      stopTextEdit(); selectEl(null);
    }
  });

  function refreshLayers() {
    var els = getEls().reverse();
    layersList.innerHTML = '';
    document.getElementById('lyCount').textContent = els.length + ' calque' + (els.length > 1 ? 's' : '');
    Object.keys(groups).forEach(function (id) {
      if (!els.some(function (el) { return el.dataset.group === id; })) delete groups[id];
    });
    var renderedGroups = {};
    els.forEach(function (el) {
      var gid = el.dataset.group;
      if (gid && groups[gid]) {
        if (!renderedGroups[gid]) {
          renderedGroups[gid] = true;
          layersList.appendChild(renderLayerGroup(gid, els.filter(function (item) { return item.dataset.group === gid; })));
        }
      } else layersList.appendChild(renderLayerRow(el));
    });
    updateLayerToolbar();
  }

  function renderLayerGroup(gid, members) {
    var group = groups[gid];
    var box = document.createElement('div');
    box.className = 'ed-group' + (group.collapsed ? ' collapsed' : '');
    box.draggable = !members.some(isLocked);
    var head = document.createElement('div');
    head.className = 'ed-group-head';
    var handle = document.createElement('span'); handle.className = 'handle'; handle.textContent = '⠿';
    var chevron = document.createElement('span'); chevron.className = 'chev'; chevron.textContent = group.collapsed ? '▶' : '▼';
    var name = document.createElement('span'); name.className = 'grp-name'; name.textContent = '📁 ' + group.name;
    name.title = 'Double-clic pour renommer';
    var count = document.createElement('span'); count.textContent = String(members.length);
    var ungroup = document.createElement('button'); ungroup.type = 'button'; ungroup.className = 'ico-btn';
    ungroup.title = 'Dégrouper'; ungroup.textContent = '×';
    head.append(handle, chevron, name, count, ungroup);
    head.addEventListener('click', function (e) {
      if (e.target === name || e.target === ungroup || e.target.closest('input')) return;
      group.collapsed = !group.collapsed;
      refreshLayers();
    });
    name.addEventListener('dblclick', function (e) {
      e.stopPropagation();
      var original = group.name;
      var input = document.createElement('input'); input.value = original;
      name.textContent = ''; name.appendChild(input); input.focus(); input.select();
      input.addEventListener('blur', function () {
        var value = input.value.trim() || original;
        if (value !== original) { group.name = value; pushHistory(); }
        refreshLayers();
      });
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); input.blur(); }
        if (event.key === 'Escape') { input.value = original; input.blur(); }
      });
    });
    ungroup.addEventListener('click', function (e) {
      e.stopPropagation();
      members.forEach(function (el) { el.dataset.group = ''; });
      delete groups[gid]; refreshLayers(); pushHistory();
    });
    box.appendChild(head);
    var body = document.createElement('div'); body.className = 'ed-group-body';
    members.forEach(function (el) { body.appendChild(renderLayerRow(el)); });
    box.appendChild(body);
    box.addEventListener('dragstart', function (e) {
      if (e.target.closest('.ed-layer')) return;
      e.dataTransfer.setData('text/plain', gid);
      e.dataTransfer.setData('application/x-ukc-type', 'group');
      e.dataTransfer.effectAllowed = 'move';
      window.__lyDragId = gid; window.__lyDragType = 'group';
    });
    box.addEventListener('dragend', clearLayerDrag);
    box.addEventListener('dragover', function (e) {
      e.preventDefault(); e.stopPropagation(); box.classList.add('drag-over');
    });
    box.addEventListener('dragleave', function (e) {
      if (!box.contains(e.relatedTarget)) box.classList.remove('drag-over');
    });
    box.addEventListener('drop', function (e) {
      e.preventDefault(); e.stopPropagation(); box.classList.remove('drag-over');
      var fromId = window.__lyDragId || e.dataTransfer.getData('text/plain');
      if (window.__lyDragType === 'group') {
        if (fromId === gid || !groups[fromId]) return;
        var moving = getEls().filter(function (el) { return el.dataset.group === fromId; });
        if (moving.some(isLocked)) return;
        var target = getEls().filter(function (el) { return el.dataset.group === gid; }).pop();
        var fragment = document.createDocumentFragment();
        moving.forEach(function (el) { fragment.appendChild(el); });
        page.insertBefore(fragment, target.nextSibling);
      } else {
        var fromEl = document.getElementById(fromId);
        if (!fromEl || isLocked(fromEl)) return;
        fromEl.dataset.group = gid;
        var top = getEls().filter(function (el) { return el.dataset.group === gid && el !== fromEl; }).pop();
        if (top) page.insertBefore(fromEl, top.nextSibling);
        selectEl(fromEl);
      }
      clearLayerDrag(); refreshLayers(); pushHistory();
    });
    return box;
  }

  function clearLayerDrag() {
    window.__lyDragId = null; window.__lyDragType = null;
    layersList.querySelectorAll('.drag-over, .dragging').forEach(function (node) {
      node.classList.remove('drag-over', 'dragging');
    });
  }

  function renderLayerRow(el) {
    var hidden = el.dataset.hidden === '1';
    var locked = el.dataset.locked === '1';
    var row = document.createElement('div');
    row.className = 'ed-layer' + (selected === el ? ' active' : '') + (hidden ? ' hidden-layer' : '') + (locked ? ' locked-layer' : '');
    row.dataset.id = el.id;
    row.draggable = !locked;
    row.innerHTML =
      '<span class="handle" title="Glisser pour réordonner">⠿</span>' +
      '<button type="button" class="ico-btn' + (hidden ? ' off' : '') + '" data-act="vis" title="Afficher / masquer">' + (hidden ? EYE_OFF : EYE) + '</button>' +
      '<span class="name" title="Double-clic pour renommer"></span>' +
      '<div class="actions"><button type="button" class="ico-btn' + (locked ? '' : ' off') + '" data-act="lock" title="Verrouiller / déverrouiller">' + (locked ? '🔒' : '🔓') + '</button></div>';
    row.querySelector('.name').textContent = el.dataset.label || el.id;
    row.addEventListener('click', function (e) {
      if (!e.target.closest('[data-act]')) selectEl(el);
    });
    row.querySelector('[data-act="vis"]').addEventListener('click', function (e) {
      e.stopPropagation();
      var hide = el.dataset.hidden !== '1';
      el.dataset.hidden = hide ? '1' : '0';
      el.style.display = hide ? 'none' : '';
      if (selected === el && hide) selectEl(null);
      refreshLayers();
      pushHistory();
    });
    row.querySelector('[data-act="lock"]').addEventListener('click', function (e) {
      e.stopPropagation(); toggleLock(el);
    });
    row.querySelector('.name').addEventListener('dblclick', function (e) {
      e.stopPropagation();
      var original = el.dataset.label || el.id;
      var input = document.createElement('input'); input.value = original;
      this.textContent = ''; this.appendChild(input); input.focus(); input.select();
      input.addEventListener('blur', function () {
        var value = input.value.trim() || original;
        if (value !== original) { el.dataset.label = value; pushHistory(); }
        refreshLayers(); selectEl(el);
      });
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); input.blur(); }
        if (event.key === 'Escape') { input.value = original; input.blur(); }
      });
    });
    row.addEventListener('dragstart', function (e) {
      e.stopPropagation();
      e.dataTransfer.setData('text/plain', el.id);
      e.dataTransfer.setData('application/x-ukc-type', 'layer');
      e.dataTransfer.effectAllowed = 'move';
      window.__lyDragId = el.id; window.__lyDragType = 'layer';
      requestAnimationFrame(function () { row.classList.add('dragging'); });
    });
    row.addEventListener('dragend', clearLayerDrag);
    row.addEventListener('dragover', function (e) {
      e.preventDefault(); e.stopPropagation(); row.classList.add('drag-over');
    });
    row.addEventListener('dragleave', function () { row.classList.remove('drag-over'); });
    row.addEventListener('drop', function (e) {
      e.preventDefault(); e.stopPropagation(); row.classList.remove('drag-over');
      if (window.__lyDragType !== 'layer') return;
      var fromEl = document.getElementById(window.__lyDragId || e.dataTransfer.getData('text/plain'));
      if (!fromEl || fromEl === el || isLocked(fromEl)) return;
      var order = getEls();
      if (order.indexOf(fromEl) < order.indexOf(el)) page.insertBefore(fromEl, el.nextSibling);
      else page.insertBefore(fromEl, el);
      fromEl.dataset.group = el.dataset.group || '';
      clearLayerDrag();
      refreshLayers(); selectEl(fromEl); pushHistory();
    });
    return row;
  }

  function toggleLock(el) {
    var lock = el.dataset.locked !== '1';
    el.dataset.locked = lock ? '1' : '0';
    el.classList.toggle('locked', lock);
    if (el.classList.contains('text')) el.contentEditable = 'false';
    if (lock) stopTextEdit();
    refreshLayers();
    selectEl(el);
    pushHistory();
    toast(lock ? 'Calque verrouillé' : 'Calque déverrouillé');
  }

  function moveLayer(dir) {
    if (!selected || isLocked(selected)) return;
    var els = getEls();
    var idx = els.indexOf(selected);
    if (dir === 'front') page.appendChild(selected);
    else if (dir === 'back') { var f = page.querySelector('.ed-el'); if (f) page.insertBefore(selected, f); }
    else if (dir === 'up' && idx < els.length - 1) page.insertBefore(els[idx + 1], selected);
    else if (dir === 'down' && idx > 0) page.insertBefore(selected, els[idx - 1]);
    refreshLayers();
    selectEl(selected);
    pushHistory();
  }

  function duplicateLayer() {
    if (!selected) return;
    var src = selected;
    var cfg = {
      type: src.classList.contains('qr') ? 'qr' : src.classList.contains('photo') ? 'photo' :
            src.classList.contains('logo') ? 'logo' : src.classList.contains('shape-box') ? 'shape' : 'text',
      label: (src.dataset.label || src.id) + ' copie',
      x: src.offsetLeft + 12, y: src.offsetTop + 12, w: src.offsetWidth,
      group: src.dataset.group || '',
      text: src.classList.contains('text') ? src.childNodes[0] && src.childNodes[0].textContent : undefined,
      html: src.classList.contains('logo') ? src.innerHTML.replace(/<div class="resize"><\/div>/, '') : undefined,
      qrUrl: src.dataset.qrUrl
    };
    if (src.classList.contains('heading')) cfg.cls = 'heading';
    else if (src.classList.contains('name')) cfg.cls = 'name';
    else if (src.classList.contains('role')) cfg.cls = 'role';
    else if (src.classList.contains('sub')) cfg.cls = 'sub';
    var el = createEl(cfg);
    selectEl(el);
    pushHistory();
  }

  function groupSelected() {
    if (!selected) return;
    var gid = 'g' + (++groupId);
    groups[gid] = { name: 'Groupe ' + groupId, collapsed: false };
    selected.dataset.group = gid;
    refreshLayers(); pushHistory();
    toast('Groupe créé : glissez d’autres calques dessus');
  }

  function deleteSelected() {
    if (!selected) return;
    if (isLocked(selected)) { toast('Calque verrouillé'); return; }
    selected.remove();
    selected = null;
    styleBar.classList.add('disabled');
    hideFloatBar();
    refreshLayers();
    pushHistory();
  }

  function loadTemplate(key) {
    var targetFormat = getCurrentFormat();
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = {};
    selected = null;
    hideFloatBar();
    page.classList.remove('size-a6-landscape', 'size-a7-portrait', 'size-card-landscape', 'size-cr80', 'bg-green', 'bg-split', 'bg-soft', 'bg-none', 'bg-co', 'bg-co-icons', 'bg-co-line');
    document.getElementById('pageBg').style.backgroundImage = '';
    document.getElementById('pageBg').style.background = '';
    page.style.width = ''; page.style.height = '';
    document.querySelectorAll('[data-size]').forEach(function (b) { b.classList.remove('active'); });

    // Styles en ligne sur un élément texte (capturés par le snapshot) - pour les
    // modèles qui veulent une typographie propre, hors classes .name/.role/.sub.
    function styled(o) {
      var e = createEl({ type: 'text', label: o.label, x: o.x, y: o.y, w: o.w, text: o.text });
      if (o.h) e.style.height = o.h + 'px';
      e.style.fontSize = o.size + 'px';
      e.style.fontWeight = String(o.weight || 600);
      e.style.color = o.color || '#2e3440';
      e.style.textAlign = o.align || 'center';
      if (o.lh) e.style.lineHeight = String(o.lh);
      if (o.upper) e.style.textTransform = 'uppercase';
      return e;
    }
    function imgEl(o) {
      var e = createEl({ type: 'logo', label: o.label, x: o.x, y: o.y, w: o.w, html: '<img src="' + o.src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">' });
      e.style.height = o.h + 'px';
      paintBadgeIcon(e);
      return e;
    }
    // Filet vert = élément de mise en page (rect), pas un décor cuit dans le fond.
    // ⚠️ Couleur #00be62 = la MÊME que le cadre (contour) de l'image de fond.
    function barEl(o) {
      var e = createEl({ type: 'shape', shape: 'line-h', label: o.label, x: o.x, y: o.y, w: o.w });
      e.style.height = (o.h || 4) + 'px';
      e.style.background = o.color || '#00be62';
      e.style.borderRadius = '2px';
      return e;
    }

    var T = {
      /* Badge participant par défaut (A6 portrait 397×560).
         ⚠️ Doit rester identique à LayoutSanitizer::defaultBadge() (PHP). */
      co: function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        page.classList.add('bg-co', 'bg-co-icons', 'bg-co-line');
        // Les filets verts restent dans le cadre, tandis que le séparateur sombre
        // du pied est un calque horizontal indépendant.
        imgEl({ label: 'Logo UK', x: 34, y: 53, w: 146, h: 40, src: ((window.UKC_EDITOR || {}).logos || {}).uk || '/img/logo-uk.png' });
        imgEl({ label: 'Logo Parakou', x: 204, y: 53, w: 158, h: 40, src: '/img/attestations/logo-parakou.png' });
        styled({ label: 'Événement', x: 24, y: 150, w: 349, h: 54, text: '[edition.name]', size: 15, weight: 700, color: '#2e3440', lh: 1.25 });
        styled({ label: 'Nom', x: 20, y: 232, w: 357, h: 40, text: '[recipient.name]', size: 22, weight: 800, color: '#253c98' });
        styled({ label: 'Rôle', x: 20, y: 276, w: 357, h: 24, text: '[recipient.role]', size: 15, weight: 700, color: '#2e3440' });
        styled({ label: 'Thème', x: 40, y: 322, w: 317, h: 80, text: '[edition.theme]', size: 10, weight: 700, color: '#111111', lh: 1.3, upper: true });
        barEl({ label: 'Séparateur', x: 108, y: 417, w: 181, h: 4, color: '#2e3440' });
        imgEl({ label: 'Icône localisation', x: 70, y: 434, w: 24, h: 24, src: '/img/badges/icon-location.svg' });
        imgEl({ label: 'Icône calendrier', x: 70, y: 472, w: 24, h: 24, src: '/img/badges/icon-calendar.svg' });
        styled({ label: 'Lieu', x: 98, y: 438, w: 190, text: '[edition.location]', size: 12, weight: 600, color: '#2e3440', align: 'left' });
        styled({ label: 'Dates', x: 98, y: 476, w: 190, text: '[edition.dates]', size: 12, weight: 600, color: '#2e3440', align: 'left' });
        createEl({ type: 'qr', label: 'QR Badge', x: 295, y: 438, w: 78, qrUrl: DEFAULT_QR_URL });
      },
      /* Variante avec photo du même badge. Elle se choisit dans le designer
         et s'enregistre sur le modèle de la catégorie courante. */
      'co-photo': function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        page.classList.add('bg-co', 'bg-co-icons', 'bg-co-line');
        imgEl({ label: 'Logo UK', x: 34, y: 53, w: 146, h: 40, src: ((window.UKC_EDITOR || {}).logos || {}).uk || '/img/logo-uk.png' });
        imgEl({ label: 'Logo Parakou', x: 204, y: 53, w: 158, h: 40, src: '/img/attestations/logo-parakou.png' });
        styled({ label: 'Événement', x: 24, y: 108, w: 349, h: 54, text: '[edition.name]', size: 15, weight: 700, color: '#2e3440', lh: 1.25 });
        createEl({ type: 'photo', label: 'Photo', x: 149, y: 156, w: 100 });
        styled({ label: 'Nom', x: 20, y: 272, w: 357, h: 40, text: '[recipient.name]', size: 22, weight: 800, color: '#253c98' });
        styled({ label: 'Rôle', x: 20, y: 316, w: 357, h: 24, text: '[recipient.role]', size: 15, weight: 700, color: '#2e3440' });
        styled({ label: 'Thème', x: 40, y: 350, w: 317, h: 70, text: '[edition.theme]', size: 10, weight: 700, color: '#111111', lh: 1.3, upper: true });
        barEl({ label: 'Séparateur', x: 108, y: 417, w: 181, h: 4, color: '#2e3440' });
        imgEl({ label: 'Icône localisation', x: 70, y: 434, w: 24, h: 24, src: '/img/badges/icon-location.svg' });
        imgEl({ label: 'Icône calendrier', x: 70, y: 472, w: 24, h: 24, src: '/img/badges/icon-calendar.svg' });
        styled({ label: 'Lieu', x: 98, y: 438, w: 190, text: '[edition.location]', size: 12, weight: 600, color: '#2e3440', align: 'left' });
        styled({ label: 'Dates', x: 98, y: 476, w: 190, text: '[edition.dates]', size: 12, weight: 600, color: '#2e3440', align: 'left' });
        createEl({ type: 'qr', label: 'QR Badge', x: 295, y: 438, w: 78, qrUrl: DEFAULT_QR_URL });
      },
      /* A6 portrait 397×560 - disposition centree, photo 64px */
      participant: function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        // En-tete bandeau bleu (~0-180)
        imgEl({ label: 'Logo UK', x: 24, y: 22, w: 130, h: 36, src: ((window.UKC_EDITOR || {}).logos || {}).uk || '/img/logo-uk.png' });
        createEl({ type: 'text', label: 'Edition', x: 24, y: 68, w: 350, cls: 'sub on-blue', text: '[edition.name]' });
        createEl({ type: 'text', label: 'Dates', x: 24, y: 92, w: 350, cls: 'sub on-blue', text: '[edition.dates]' });
        // Zone blanche - photo centree (397/2 - 32 = 166.5)
        createEl({ type: 'photo', label: 'Photo', x: 166, y: 200, w: 64 });
        createEl({ type: 'text', label: 'Nom', x: 28, y: 285, w: 340, cls: 'name attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 28, y: 318, w: 340, cls: 'role attr', text: '[recipient.role]' });
        createEl({ type: 'text', label: 'Institution', x: 28, y: 348, w: 340, cls: 'sub attr', text: '[recipient.institution]' });
        // Bas - QR + ref
        createEl({ type: 'qr', label: 'QR Badge', x: 166, y: 400, w: 64, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 28, y: 480, w: 340, cls: 'sub attr', text: '[badge.ref]' });
      },
      card: function () {
        page.classList.add('size-card-landscape');
        page.classList.add('bg-split');
        var chip = document.querySelector('[data-size="card-landscape"]');
        if (chip) chip.classList.add('active');
        // Carte 340×208
        imgEl({ label: 'Logo UK', x: 14, y: 12, w: 110, h: 38, src: ((window.UKC_EDITOR || {}).logos || {}).uk || '/img/logo-uk.png' });
        createEl({ type: 'photo', label: 'Photo', x: 16, y: 70, w: 48 });
        createEl({ type: 'text', label: 'Nom', x: 76, y: 72, w: 160, cls: 'name on-blue attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 76, y: 104, w: 160, cls: 'role on-blue attr', text: '[recipient.role]' });
        createEl({ type: 'qr', label: 'QR', x: 260, y: 50, w: 56, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 76, y: 160, w: 160, cls: 'sub on-blue attr', text: '[badge.ref]' });
      }
    };

    (T[key] || T.co)();
    applyFormat(targetFormat, false);
    refreshLayers();
    pushHistory();
    selectEl(null);
  }

  /* UI wiring */
  document.querySelectorAll('.ed-rail button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.ed-rail button').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      document.querySelectorAll('.panel-view').forEach(function (p) { p.classList.remove('active'); });
      document.getElementById('panel-' + btn.dataset.panel).classList.add('active');
      if (btn.dataset.panel === 'layers') refreshLayers();
      // Les aperçus (.mini) ne sont mesurables qu'une fois le panneau visible.
      if (btn.dataset.panel === 'templates') requestAnimationFrame(fitThumbs);
    });
  });

  document.querySelectorAll('.ed-tpl-card').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('.ed-tpl-card').forEach(function (c) { c.classList.remove('active'); });
      loadTemplate(card.dataset.tpl);
      card.classList.add('active');
      toast('Design « ' + card.dataset.tpl + ' » charge');
    });
  });

  // ⚠️ Les badges s'impriment à leur TAILLE RÉELLE : ces dimensions sont celles du
  // porte-badge, en px à 96 dpi (1 px = 1/96 pouce), et la planche A4 dispose ce
  // que le modèle déclare. Ajouter un format = une ligne ici et une dans
  // FORMAT_LABELS ; tout le reste s'en déduit.
  var FORMAT_DIMENSIONS = {
    'modele-303': [333, 472], 'modele-304': [378, 510], 'modele-106': [246, 359],
    'a6-pochette': [408, 586], 'cr80': [324, 204],
    'a6-portrait': [397, 560], 'a6-landscape': [560, 397],
    'a7-portrait': [280, 397], 'card-landscape': [340, 208]
  };
  var FORMAT_LABELS = {
    'modele-303': 'Modèle 303', 'modele-304': 'Modèle 304', 'modele-106': 'Modèle 106',
    'a6-pochette': 'A6 porte-badge', 'cr80': 'CR80',
    'a6-portrait': 'A6 Portrait', 'a6-landscape': 'A6 Paysage',
    'a7-portrait': 'A7 Portrait', 'card-landscape': 'Carte 90×55'
  };
  // ⚠️ DÉDUITES des formats déclarés, jamais recopiées : quatre listes devaient
  // rester d'accord (pastilles, menu déroulant, dimensions, libellés), et c'est le
  // menu déroulant qui a fini par ignorer les formats ajoutés.
  var SIZE_CLASSES = Object.keys(FORMAT_DIMENSIONS)
    .filter(function (key) { return key !== 'a6-portrait'; })
    .map(function (key) { return 'size-' + key; });

  function getCurrentFormat() {
    // `a6-portrait` est la taille de base de la page : elle n'a pas de classe.
    var size = 'a6-portrait';
    Object.keys(FORMAT_DIMENSIONS).forEach(function (key) {
      if (key !== 'a6-portrait' && page.classList.contains('size-' + key)) size = key;
    });
    return size;
  }
  function syncFormatControls() {
    var size = getCurrentFormat();
    document.querySelectorAll('[data-size]').forEach(function (button) {
      button.classList.toggle('active', button.dataset.size === size);
    });
    document.getElementById('badgeFormatSelect').value = size;
    document.getElementById('badgeDesignFormat').textContent = 'Designs pour ' + FORMAT_LABELS[size];
    document.querySelectorAll('.ed-tpl-card').forEach(function (card) {
      card.hidden = !card.dataset.formats.split(' ').includes(size);
      if (card.hidden) card.classList.remove('active');
    });
  }

  // ⚠️ Changer de format RÉAJUSTE la maquette : chaque élément voit sa position et
  // sa taille mises à l'échelle du changement de dimensions (x/largeur par le rapport
  // des largeurs, y/hauteur par celui des hauteurs). Sans ça, les éléments restaient à
  // leurs coordonnées A6 et débordaient sur un format plus petit. Les carrés (QR, photo)
  // et les corps de texte suivent le plus petit rapport, pour ne pas se déformer.
  // Réduit la police d'un texte tant qu'il déborde de sa boîte (en largeur), avec
  // un plancher : un format plus étroit ne doit jamais faire sortir le texte de son
  // cadre. Ne fait que RÉDUIRE (jamais grossir) : c'est un filet de sécurité par-dessus
  // la mise à l'échelle proportionnelle, pas une seconde règle de taille.
  function fitText(el) {
    if (!el.classList.contains('text')) return;
    var fs = parseFloat(el.style.fontSize) || 0;
    if (!fs) return;
    var guard = 0;
    while (el.scrollWidth > el.clientWidth + 1 && fs > 6 && guard < 60) {
      fs = Math.max(6, fs - 0.5);
      el.style.fontSize = fs + 'px';
      guard++;
    }
  }

  // Réajustement au changement de format : position et taille de chaque élément sont
  // mises à l'échelle du changement de dimensions (x/largeur par le rapport des largeurs,
  // y/hauteur par celui des hauteurs ; QR, photo et police par le plus petit rapport pour
  // ne pas se déformer), puis un passage fitText garantit que chaque texte tient dans sa
  // boîte au nouveau format.
  function scaleEls(rx, ry) {
    if (!(rx > 0) || !(ry > 0) || (rx === 1 && ry === 1)) return;
    var rf = Math.min(rx, ry);
    page.querySelectorAll('.ed-el').forEach(function (el) {
      el.style.left = Math.round((parseFloat(el.style.left) || 0) * rx) + 'px';
      el.style.top = Math.round((parseFloat(el.style.top) || 0) * ry) + 'px';
      var square = el.classList.contains('qr') || el.classList.contains('photo');
      var sw = square ? rf : rx, sh = square ? rf : ry;
      if (el.style.width) el.style.width = Math.max(4, Math.round((parseFloat(el.style.width) || 0) * sw)) + 'px';
      if (el.style.height) el.style.height = Math.max(3, Math.round((parseFloat(el.style.height) || 0) * sh)) + 'px';
      if (el.style.fontSize) el.style.fontSize = Math.max(6, Math.round((parseFloat(el.style.fontSize) || 0) * rf)) + 'px';
    });
    page.querySelectorAll('.ed-el.text').forEach(fitText);
  }

  function applyFormat(size, recordHistory) {
    if (!FORMAT_DIMENSIONS[size]) return;
    var previous = getCurrentFormat();
    SIZE_CLASSES.forEach(function (c) { page.classList.remove(c); });
    page.style.width = '';
    page.style.height = '';
    if (size !== 'a6-portrait') page.classList.add('size-' + size);
    syncFormatControls();
    if (size !== previous) {
      scaleEls(FORMAT_DIMENSIONS[size][0] / FORMAT_DIMENSIONS[previous][0], FORMAT_DIMENSIONS[size][1] / FORMAT_DIMENSIONS[previous][1]);
      if (recordHistory) pushHistory();
    }
    if (selected) updateFloatBar();
  }

  document.querySelectorAll('[data-size]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      applyFormat(btn.dataset.size, true);
      toast('Format : ' + btn.textContent.trim());
    });
  });

  document.getElementById('badgeFormatSelect').addEventListener('change', function () {
    var button = document.querySelector('[data-size="' + this.value + '"]');
    if (button) button.click();
  });

  document.querySelectorAll('[data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kind = btn.dataset.add;
      var cfg = { x: 40 + Math.random() * 30, y: 40 + Math.random() * 30 };
      if (kind === 'heading') Object.assign(cfg, { type: 'text', label: 'Titre', cls: 'heading', text: 'Titre', w: 200 });
      else if (kind === 'subheading') Object.assign(cfg, { type: 'text', label: 'Sous-titre', cls: 'subheading', text: 'Sous-titre', w: 190 });
      else if (kind === 'normal') Object.assign(cfg, { type: 'text', label: 'Paragraphe', cls: 'paragraph', text: 'Votre texte', w: 210 });
      else if (kind === 'name') Object.assign(cfg, { type: 'text', label: 'Nom', cls: 'name attr', text: '[recipient.name]', w: 220 });
      else if (kind === 'role') Object.assign(cfg, { type: 'text', label: 'Role', cls: 'role attr', text: '[recipient.role]', w: 180 });
      else if (kind === 'textbox') Object.assign(cfg, { type: 'text', label: 'Texte', cls: 'sub', text: 'Nouveau texte', w: 160 });
      else if (kind === 'photo') Object.assign(cfg, { type: 'photo', label: 'Photo', w: 64 });
      else if (kind === 'icon-location' || kind === 'icon-calendar') {
        var locationIcon = kind === 'icon-location';
        Object.assign(cfg, {
          type: 'logo', label: locationIcon ? 'Icône localisation' : 'Icône calendrier',
          w: 24,
          html: '<img src="/img/badges/icon-' + (locationIcon ? 'location' : 'calendar') + '.svg" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">'
        });
      }
      else if (kind === 'qr' || kind === 'qr-verify') Object.assign(cfg, { type: 'qr', label: 'QR Badge', w: 72, qrUrl: DEFAULT_QR_URL });
      else if (kind === 'qr-custom') {
        openInputModal({
          title: 'QR personnalisé',
          hint: 'Adresse à encoder dans le QR code.',
          value: 'https://',
          onOk: function (url) {
            if (!url) return;
            selectEl(createEl({ type: 'qr', label: 'QR personnalisé', x: 60, y: 60, w: 72, qrUrl: url }));
            pushHistory();
          }
        });
        return;
      } else if (kind === 'rect') Object.assign(cfg, { type: 'shape', shape: 'rect', label: 'Rectangle', w: 50, h: 30 });
      else if (kind === 'rect-o') Object.assign(cfg, { type: 'shape', shape: 'rect', outline: true, label: 'Rectangle contour', w: 50, h: 30 });
      else if (kind === 'circle') Object.assign(cfg, { type: 'shape', shape: 'circle', label: 'Cercle', w: 40, h: 40 });
      else if (kind === 'circle-o') Object.assign(cfg, { type: 'shape', shape: 'circle', outline: true, label: 'Cercle contour', w: 40, h: 40 });
      else if (kind === 'line-h') Object.assign(cfg, { type: 'shape', shape: 'line-h', label: 'Ligne', w: 120 });
      else if (kind === 'line-v') Object.assign(cfg, { type: 'shape', shape: 'line-v', label: 'Ligne verticale', h: 80 });
      var added = createEl(cfg);
      if (kind === 'icon-location' || kind === 'icon-calendar') { added.style.height = '24px'; paintBadgeIcon(added, '#2e3440'); }
      selectEl(added);
      pushHistory();
    });
  });

  document.querySelectorAll('.ed-attr').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var token = btn.dataset.attr;
      var config = token === '[recipient.photo]'
        ? { type: 'photo', label: 'Photo', x: 60, y: 200, w: 80, h: 80 }
        : { type: 'text', label: token, cls: 'attr', text: token, x: 60, y: 200, w: 200 };
      selectEl(createEl(config));
      pushHistory();
    });
  });

  document.getElementById('lyFront').addEventListener('click', function () { moveLayer('front'); });
  document.getElementById('lyUp').addEventListener('click', function () { moveLayer('up'); });
  document.getElementById('lyDown').addEventListener('click', function () { moveLayer('down'); });
  document.getElementById('lyBack').addEventListener('click', function () { moveLayer('back'); });
  document.getElementById('lyDup').addEventListener('click', duplicateLayer);
  document.getElementById('lyGroup').addEventListener('click', groupSelected);
  document.getElementById('lyDel').addEventListener('click', deleteSelected);
  document.getElementById('lyOpacity').addEventListener('input', function () {
    if (!selected) return;
    selected.style.opacity = String(this.value / 100);
    document.getElementById('lyOpacityVal').textContent = this.value + '%';
    markDirty();
  });
  document.getElementById('lyOpacity').addEventListener('change', function () {
    if (selected) pushHistory();
  });
  document.getElementById('lyBlend').addEventListener('change', function () {
    if (!selected) return;
    selected.dataset.blend = this.value;
    selected.style.mixBlendMode = this.value;
    pushHistory();
  });

  document.getElementById('btnUndo').addEventListener('click', undo);
  document.getElementById('btnRedo').addEventListener('click', redo);
  document.getElementById('zoomIn').addEventListener('click', function () { zoomLevel = Math.min(2, Math.round((zoomLevel + 0.1) * 10) / 10); applyZoom(); });
  document.getElementById('zoomOut').addEventListener('click', function () { zoomLevel = Math.max(0.5, Math.round((zoomLevel - 0.1) * 10) / 10); applyZoom(); });
  document.getElementById('zoomReset').addEventListener('click', function () { zoomLevel = 1; applyZoom(); });
  document.querySelector('.ed-canvas-area').addEventListener('wheel', function (e) {
    if (!e.ctrlKey && !e.metaKey) return;
    e.preventDefault();
    zoomLevel = Math.max(0.5, Math.min(2, Math.round((zoomLevel + (e.deltaY < 0 ? 0.1 : -0.1)) * 10) / 10));
    applyZoom();
  }, { passive: false });

  document.getElementById('fontSize').addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontSize = this.value + 'px'; pushHistory(); }
  });
  document.getElementById('lineThickness').addEventListener('change', function () {
    var direction = lineDirection(selected);
    if (!direction || isLocked(selected)) return;
    var thickness = Math.max(1, Math.min(40, parseInt(this.value, 10) || 1));
    this.value = thickness;
    selected.dataset.shape = direction;
    selected.style[direction === 'line-h' ? 'height' : 'width'] = thickness + 'px';
    pushHistory();
    updateFloatBar();
  });
  document.getElementById('fontSizeMinus').addEventListener('click', function () {
    var i = document.getElementById('fontSize'); i.value = Math.max(8, parseInt(i.value, 10) - 1); i.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontSizePlus').addEventListener('click', function () {
    var i = document.getElementById('fontSize'); i.value = Math.min(48, parseInt(i.value, 10) + 1); i.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontFamily').addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontFamily = this.value; pushHistory(); }
  });
  var fw = document.getElementById('fontWeight');
  if (fw) fw.addEventListener('change', function () {
    if (selected && !isLocked(selected)) { selected.style.fontWeight = this.value; pushHistory(); }
  });
  document.getElementById('fontColor').addEventListener('input', function () {
    if (!selected || isLocked(selected)) return;
    if (selected.classList.contains('shape-box')) {
      if (selected.dataset.outline === '1') selected.style.borderColor = this.value;
      else selected.style.backgroundColor = this.value;
    } else {
      selected.style.color = this.value;
      paintBadgeIcon(selected, this.value);
    }
    markDirty();
  });
  document.getElementById('fontColor').addEventListener('change', function () {
    if (selected && !isLocked(selected)) pushHistory();
  });
  document.querySelectorAll('[data-style]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!selected || isLocked(selected)) return;
      if (btn.dataset.style === 'bold') selected.style.fontWeight = selected.style.fontWeight === '800' || selected.style.fontWeight === '700' ? '400' : '700';
      if (btn.dataset.style === 'italic') selected.style.fontStyle = selected.style.fontStyle === 'italic' ? 'normal' : 'italic';
      if (btn.dataset.style === 'underline') selected.style.textDecoration = selected.style.textDecoration === 'underline' ? 'none' : 'underline';
      syncTextControls(selected, window.getComputedStyle(selected));
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
  function serializeLayout() {
    var rect = page.getBoundingClientRect();
    var W = Math.max(120, Math.round(page.offsetWidth || rect.width));
    var H = Math.max(120, Math.round(page.offsetHeight || rect.height));
    var elements = getEls().filter(function (el) { return el.style.display !== 'none'; }).map(function (el) {
      var cs = getComputedStyle(el);
      var x = Math.round(parseFloat(el.style.left) || 0);
      var y = Math.round(parseFloat(el.style.top) || 0);
      var w = Math.round(parseFloat(el.style.width) || el.offsetWidth || 100);
      var h = Math.round(parseFloat(el.style.height) || el.offsetHeight || 30);
      var cls = el.className || '';
      var img = el.querySelector('img');
      var text = (el.innerText || '').trim();
      if (el.classList.contains('photo')) {
        return { type: 'photo', x: x, y: y, w: w, h: h };
      }
      if (el.dataset.qrUrl || /\bqr\b/i.test(cls) || (img && /qrserver|qrcode/i.test(img.src))) {
        return { type: 'qr', x: x, y: y, size: Math.max(w, h) || 96, value: el.dataset.qrUrl || '{ref}' };
      }
      if (img && /^(https?:|\/)/i.test(img.getAttribute('src') || img.src)) {
        var source = img.getAttribute('src') || img.src;
        var image = { type: 'image', x: x, y: y, w: w, h: h, src: source };
        if (badgeIconSrc(el)) image.color = rgbToHex(cs.color) || '#2e3440';
        return image;
      }
      if (!text && (/\b(shape|rect|line|ln-|sq|band)\b/i.test(cls) || el.style.background || cs.backgroundColor !== 'rgba(0, 0, 0, 0)')) {
        var outline = el.dataset.outline === '1';
        var bw = el.style.border ? Math.round(parseFloat(cs.borderTopWidth) || 0) : 0;
        return {
          type: 'rect', shape: lineDirection(el) || el.dataset.shape || 'rect', x: x, y: y, w: w, h: h,
          color: outline ? '#ffffff' : (rgbToHex(cs.backgroundColor) || '#223ea4'),
          fill: !outline,
          radius: Math.round(parseFloat(cs.borderTopLeftRadius) || 0),
          border: bw,
          border_color: bw > 0 ? (rgbToHex(cs.borderTopColor) || '#223ea4') : ''
        };
      }
      var align = cs.textAlign;
      return {
        type: 'text', x: x, y: y, w: w, h: h,
        text: text.slice(0, 500),
        size: Math.round(parseFloat(cs.fontSize) || 14),
        weight: parseInt(cs.fontWeight, 10) || 500,
        color: rgbToHex(cs.color) || '#1a2332',
        align: (align === 'center' || align === 'right') ? align : 'left',
        font: fontKeyB(cs.fontFamily),
        italic: cs.fontStyle === 'italic',
        underline: (cs.textDecorationLine || cs.textDecoration || '').indexOf('underline') > -1,
        upper: cs.textTransform === 'uppercase',
        leading: Math.round((parseFloat(cs.lineHeight) / (parseFloat(cs.fontSize) || 14)) * 100) / 100 || 1.2,
        spacing: Math.round((parseFloat(cs.letterSpacing) || 0) * 100) / 100
      };
    }).filter(function (e) { return e && !(e.type === 'text' && !e.text); });
    var bgEl = document.getElementById('pageBg');
    var out = { width: W, height: H, bg: rgbToHex(bgEl.style.backgroundColor) || '#ffffff', accent: '#223ea4', elements: elements };
    // Cadre officiel (image de fond) : sans ça, enregistrer un badge depuis le
    // concepteur perdait le cadre vert/bleu (serializeLayout ne l'émettait pas).
    if (page.classList.contains('bg-co')) out.bg_image = '/img/badges/badge-default-bg.png';
    if (page.classList.contains('bg-co-line')) out.erase_default_rule = true;
    var customBg = bgEl.style.backgroundImage.match(/url\((['"]?)(.*?)\1\)/);
    if (customBg) out.bg_image = customBg[2];
    return out;
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
      body: JSON.stringify({ event_id: cfg.editionId || '', kind: cfg.kind || 'badge', type: cfg.type || 'default', title: (document.getElementById('tplName') || {}).value || '', layout: serializeLayout(), editor_state: snapshot() })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j && res.j.ok) {
          dirty = false;
          toast('Badge « ' + document.getElementById('tplName').value + ' » enregistré');
          if (typeof onSuccess === 'function') onSuccess();
        } else toast((res.j && res.j.error) ? res.j.error : 'Échec de l’enregistrement');
      })
      .catch(function () { toast('Erreur réseau lors de l’enregistrement'); })
      .finally(function () { btn.disabled = false; btn.textContent = original; });
  }
  document.getElementById('btnSave').addEventListener('click', function () { performSave(); });

  /* ---------- Garde « modifications non enregistrées » (cf. template-designer.js) ---------- */
  (function () {
    var cfg = window.UKC_EDITOR || {};
    if (cfg.preview) return;
    var nameInp = document.getElementById('tplName');
    if (nameInp) nameInp.addEventListener('input', markDirty);
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault(); e.returnValue = ''; return '';
    });
    function go() { location.href = cfg.backUrl || '/admin/documents'; }
    function confirmLeave() {
      if (document.getElementById('ukcLeaveModal')) return;
      var previousFocus = document.activeElement;
      var ov = document.createElement('div');
      ov.id = 'ukcLeaveModal';
      ov.className = 'ed-leave-overlay';
      var box = document.createElement('div');
      box.className = 'ed-leave-dialog';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-modal', 'true');
      box.setAttribute('aria-labelledby', 'edLeaveTitle');
      box.setAttribute('aria-describedby', 'edLeaveDescription');
      var content = document.createElement('div');
      content.className = 'ed-leave-content';
      var h = document.createElement('h3');
      h.id = 'edLeaveTitle';
      h.textContent = 'Modifications non enregistrées';
      var p = document.createElement('p');
      p.id = 'edLeaveDescription';
      p.textContent = 'Vos modifications seront perdues si vous quittez sans enregistrer.';
      var row = document.createElement('div');
      row.className = 'ed-leave-actions';
      function mkBtn(label, kind) {
        var b = document.createElement('button'); b.type = 'button'; b.textContent = label;
        b.className = 'ed-leave-button ed-leave-button--' + kind;
        return b;
      }
      function close() {
        ov.remove();
        document.removeEventListener('keydown', onKeydown);
        if (previousFocus && previousFocus.focus) previousFocus.focus();
      }
      function onKeydown(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'Tab') {
          var buttons = [bCancel, bLeave, bSave];
          var index = buttons.indexOf(document.activeElement);
          if (e.shiftKey && index <= 0) {
            e.preventDefault();
            bSave.focus();
          } else if (!e.shiftKey && index === buttons.length - 1) {
            e.preventDefault();
            bCancel.focus();
          }
        }
      }
      var bCancel = mkBtn('Annuler', 'ghost');
      var bLeave = mkBtn('Quitter sans enregistrer', 'danger');
      var bSave = mkBtn('Enregistrer et quitter', 'primary');
      bCancel.addEventListener('click', close);
      bLeave.addEventListener('click', function () { dirty = false; go(); });
      bSave.addEventListener('click', function () { performSave(function () { go(); }); });
      ov.addEventListener('click', function (e) { if (e.target === ov) close(); });
      document.addEventListener('keydown', onKeydown);
      row.appendChild(bCancel); row.appendChild(bLeave); row.appendChild(bSave);
      content.appendChild(h); content.appendChild(p);
      box.appendChild(content); box.appendChild(row);
      ov.appendChild(box); document.body.appendChild(ov);
      bCancel.focus();
    }
    var backBtn = document.querySelector('.ed-top .back');
    if (backBtn) {
      backBtn.onclick = null;
      backBtn.addEventListener('click', function () { if (dirty) confirmLeave(); else go(); });
    }
  })();
  function fillBadgeTokens(root) {
    var vars = (window.UKC_EDITOR || {}).vars || {};
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(function (n) {
      n.nodeValue = n.nodeValue.replace(/\[([A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)?)\]|\{([A-Za-z0-9_]+)\}/g,
        function (whole, bracket, brace) {
          var key = bracket || brace;
          var v = vars[key];
          if (typeof v !== 'string' && typeof v !== 'number') return '';
          var value = String(v);
          return bracket && bracket === bracket.toUpperCase() ? value.toUpperCase() : value;
        });
    });
    root.querySelectorAll('.ed-el.photo').forEach(function (el) {
      var photo = vars['recipient.photo'];
      var inner = el.querySelector('.photo-inner');
      if (!inner) return;
      inner.textContent = '';
      if (typeof photo === 'string' && photo) {
        var image = document.createElement('img');
        image.src = photo;
        image.alt = '';
        image.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        inner.appendChild(image);
      } else {
        inner.textContent = vars.initials || 'AK';
      }
    });
  }
  document.getElementById('btnPreview').addEventListener('click', function () {
    var cfgP = window.UKC_EDITOR || {};
    var isPrev = !!cfgP.preview;
    var old = document.getElementById('ukcPreviewOverlay');
    if (old) { if (!isPrev) old.remove(); return; }
    function dismiss() {
      if (isPrev) { location.href = cfgP.backUrl || '/admin/documents'; return; }
      var o = document.getElementById('ukcPreviewOverlay'); if (o) o.remove();
    }
    var overlay = document.createElement('div');
    overlay.id = 'ukcPreviewOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:' + (isPrev ? '#1e293b' : 'rgba(15,23,42,.88)') + ';display:flex;align-items:center;justify-content:center;padding:24px;overflow:auto;';
    var pw = page.offsetWidth || 397, ph = page.offsetHeight || 560;
    var scale = Math.min(1, (window.innerWidth - 80) / pw, (window.innerHeight - 120) / ph);
    var clone = page.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.transform = 'none';
    clone.style.margin = '0';
    clone.style.boxShadow = '0 20px 60px rgba(0,0,0,.5)';
    clone.querySelectorAll('.resize, .ed-guide, .ed-guide-label').forEach(function (n) { n.remove(); });
    fillBadgeTokens(clone);
    clone.querySelectorAll('.ed-el').forEach(function (n) {
      n.style.outline = 'none'; n.style.boxShadow = 'none'; n.classList.remove('selected');
      if (!n.classList.contains('shape-box') && !n.classList.contains('qr')) n.style.border = 'none';
      if (n.classList.contains('qr')) {
        var qrImg = n.querySelector('img');
        if (qrImg) qrImg.src = qrImageUrl(n.dataset.qrUrl || DEFAULT_QR_URL, 140);
      }
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
    selected = null; hideFloatBar(); refreshLayers(); pushHistory();
    toast('Badge vierge');
  });

  /* Background modal */
  var bgModal = document.getElementById('bgModal');
  document.getElementById('btnBg').addEventListener('click', function () { bgModal.classList.add('open'); });
  document.getElementById('bgModalClose').addEventListener('click', function () { bgModal.classList.remove('open'); });
  bgModal.addEventListener('click', function (e) { if (e.target === bgModal) bgModal.classList.remove('open'); });
  document.querySelectorAll('#bgModal .bg-opt').forEach(function (opt) {
    opt.addEventListener('click', function () {
      document.querySelectorAll('#bgModal .bg-opt').forEach(function (o) { o.classList.remove('active'); });
      opt.classList.add('active');
      page.classList.remove('bg-green', 'bg-split', 'bg-soft', 'bg-none', 'bg-co', 'bg-co-icons', 'bg-co-line');
      document.getElementById('pageBg').style.background = '';
      document.getElementById('pageBg').style.backgroundImage = '';
      var k = opt.dataset.bg;
      if (k === 'green') page.classList.add('bg-green');
      else if (k === 'split') page.classList.add('bg-split');
      else if (k === 'soft') page.classList.add('bg-soft');
      else if (k === 'none') page.classList.add('bg-none');
      pushHistory();
      bgModal.classList.remove('open');
      toast('Fond appliqué');
    });
  });
  document.getElementById('bgImportZone').addEventListener('click', function () { document.getElementById('bgFileInput').click(); });
  function uploadImage(file) {
    var cfg = window.UKC_EDITOR || {};
    if (!cfg.uploadUrl) return Promise.reject(new Error('Téléversement indisponible'));
    var body = new FormData();
    body.append('file', file);
    return fetch(cfg.uploadUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json' },
      body: body
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.body || !res.body.ok || !res.body.url) throw new Error((res.body && res.body.message) || 'Échec du téléversement');
        return res.body.url;
      });
  }
  document.getElementById('bgFileInput').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    uploadImage(file).then(function (url) {
      page.classList.remove('bg-co', 'bg-co-icons', 'bg-co-line');
      page.classList.add('bg-none');
      var bg = document.getElementById('pageBg');
      bg.style.backgroundImage = 'url("' + url + '")';
      bg.style.backgroundSize = 'cover';
      pushHistory();
      bgModal.classList.remove('open');
      toast('Image de fond importée');
    }).catch(function (error) { toast(error.message || 'Échec du téléversement'); });
    e.target.value = '';
  });

  /* Input modal */
  function openInputModal(opts) {
    document.getElementById('inputModalTitle').textContent = opts.title || 'Saisir';
    document.getElementById('inputModalHint').textContent = opts.hint || '';
    document.getElementById('inputModalField').value = opts.value || '';
    inputModalCb = opts.onOk || null;
    document.getElementById('inputModal').classList.add('open');
    setTimeout(function () { document.getElementById('inputModalField').focus(); }, 40);
  }
  function closeInputModal() {
    document.getElementById('inputModal').classList.remove('open');
    inputModalCb = null;
  }
  document.getElementById('inputModalClose').addEventListener('click', closeInputModal);
  document.getElementById('inputModalCancel').addEventListener('click', closeInputModal);
  document.getElementById('inputModalOk').addEventListener('click', function () {
    var val = document.getElementById('inputModalField').value.trim();
    var cb = inputModalCb;
    closeInputModal();
    if (cb) cb(val);
  });
  document.getElementById('inputModalField').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') document.getElementById('inputModalOk').click();
    if (e.key === 'Escape') closeInputModal();
  });

  /* Float bar */
  document.getElementById('fbLock').addEventListener('click', function (e) { e.stopPropagation(); if (selected) toggleLock(selected); });
  document.getElementById('fbUnlock').addEventListener('click', function (e) { e.stopPropagation(); if (selected) toggleLock(selected); });
  document.getElementById('fbDup').addEventListener('click', function (e) { e.stopPropagation(); duplicateLayer(); });
  document.getElementById('fbDel').addEventListener('click', function (e) { e.stopPropagation(); deleteSelected(); });
  document.getElementById('floatBar').addEventListener('mousedown', function (e) { e.stopPropagation(); });

  /* Uploads */
  document.getElementById('btnUploadImg').addEventListener('click', function () { document.getElementById('fileUpload').click(); });
  document.getElementById('fileUpload').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    uploadImage(file).then(function (url) {
      var thumb = document.createElement('div');
      thumb.className = 'ed-thumb';
      thumb.dataset.src = url;
      thumb.style.backgroundImage = 'url("' + url + '")';
      thumb.title = file.name;
      thumb.addEventListener('click', function () {
        selectEl(createEl({
          type: 'logo', label: file.name.slice(0, 16), x: 40, y: 40, w: 80,
          html: '<img src="' + url + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">'
        }));
        pushHistory();
      });
      document.getElementById('uploadList').appendChild(thumb);
      toast('Image ajoutée');
    }).catch(function (error) { toast(error.message || 'Échec du téléversement'); });
    e.target.value = '';
  });
  document.querySelectorAll('#uploadList .ed-thumb[data-logo]').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var logos = (window.UKC_EDITOR || {}).logos || {};
      var parakou = thumb.dataset.logo === 'up';
      var src = parakou ? logos.parakou : logos.uk;
      if (!src) return;
      var el = createEl({
        type: 'logo', label: parakou ? 'Logo Parakou' : 'Logo UK',
        x: 40, y: 40, w: parakou ? 125 : 115,
        html: '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:contain;display:block;">'
      });
      el.style.height = '40px';
      selectEl(el);
      pushHistory();
    });
  });

  /* AI */
  var aiFile = null;
  var aiDrop = document.getElementById('aiDrop');
  var aiInput = document.getElementById('aiFile');
  function showAiFile() {
    var list = document.getElementById('aiFileList');
    list.innerHTML = '';
    if (!aiFile) return;
    var row = document.createElement('div'); row.className = 'ai-file';
    var name = document.createElement('span'); name.textContent = aiFile.name;
    var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'rm'; remove.textContent = '×'; remove.title = 'Retirer';
    remove.addEventListener('click', function () { aiFile = null; showAiFile(); });
    row.appendChild(name); row.appendChild(remove); list.appendChild(row);
  }
  function chooseAiFile(file) {
    if (!file) return;
    if (!/image\/(png|jpeg|webp)/.test(file.type) || file.size > 8 * 1024 * 1024) {
      toast('Choisissez une image PNG, JPG ou WebP de 8 Mo maximum'); return;
    }
    aiFile = file; showAiFile();
  }
  aiDrop.addEventListener('click', function () { aiInput.click(); });
  aiDrop.addEventListener('dragover', function (e) { e.preventDefault(); aiDrop.classList.add('dragover'); });
  aiDrop.addEventListener('dragleave', function () { aiDrop.classList.remove('dragover'); });
  aiDrop.addEventListener('drop', function (e) { e.preventDefault(); aiDrop.classList.remove('dragover'); chooseAiFile(e.dataTransfer.files[0]); });
  aiInput.addEventListener('change', function (e) { chooseAiFile(e.target.files && e.target.files[0]); e.target.value = ''; });

  function applyGeneratedLayout(layout) {
    var format = getCurrentFormat();
    var dims = FORMAT_DIMENSIONS[format];
    var sx = dims[0] / (Number(layout.width) || dims[0]);
    var sy = dims[1] / (Number(layout.height) || dims[1]);
    page.querySelectorAll('.ed-el').forEach(function (el) { el.remove(); });
    groups = {}; selected = null; hideFloatBar();
    page.classList.remove('bg-green', 'bg-split', 'bg-soft', 'bg-none', 'bg-co', 'bg-co-icons', 'bg-co-line');
    if (layout.erase_default_rule) page.classList.add('bg-co-line');
    var bg = document.getElementById('pageBg');
    bg.style.background = layout.bg || '#ffffff';
    if (layout.bg_image) bg.style.backgroundImage = 'url("' + layout.bg_image + '")';
    if (layout.bg_image === '/img/badges/badge-default-bg.png'
      && (layout.elements || []).some(function (item) { return /^\/img\/badges\/icon-location\.(?:png|svg)$/.test(item.src || ''); })
      && (layout.elements || []).some(function (item) { return /^\/img\/badges\/icon-calendar\.(?:png|svg)$/.test(item.src || ''); })) {
      page.classList.add('bg-co-icons');
    }
    (layout.elements || []).forEach(function (item, index) {
      var base = { x: Math.round((item.x || 0) * sx), y: Math.round((item.y || 0) * sy), w: Math.max(1, Math.round((item.w || item.size || 50) * sx)), h: Math.max(1, Math.round((item.h || item.size || 30) * sy)) };
      var el;
      if (item.type === 'text') {
        el = createEl(Object.assign(base, { type: 'text', label: 'Texte ' + (index + 1), text: item.text || 'Texte' }));
        el.style.height = base.h + 'px';
        el.style.fontSize = (item.size || 14) + 'px';
        el.style.fontWeight = String(item.weight || 500);
        el.style.color = item.color || '#1a2332';
        el.style.textAlign = item.align || 'left';
        if (item.font === 'times') el.style.fontFamily = "'Times New Roman', Times, serif";
        else if (item.font === 'georgia') el.style.fontFamily = 'Georgia, serif';
        else if (item.font === 'playfair') el.style.fontFamily = "'Playfair Display', Georgia, serif";
        else if (item.font === 'lato') el.style.fontFamily = 'Lato, sans-serif';
        else if (item.font === 'arial') el.style.fontFamily = 'Arial, sans-serif';
        if (item.italic) el.style.fontStyle = 'italic';
        if (item.underline) el.style.textDecoration = 'underline';
        if (item.upper) el.style.textTransform = 'uppercase';
        if (item.leading) el.style.lineHeight = String(item.leading);
        if (item.spacing) el.style.letterSpacing = item.spacing + 'px';
      } else if (item.type === 'rect') {
        el = createEl(Object.assign(base, { type: 'shape', shape: item.shape || 'rect', outline: item.fill === false, label: item.shape === 'line-h' ? 'Ligne horizontale' : item.shape === 'line-v' ? 'Ligne verticale' : 'Forme ' + (index + 1) }));
        el.style.background = item.fill === false ? 'transparent' : (item.color || '#223ea4');
        el.style.borderRadius = (item.radius || 0) + 'px';
        if (item.border) el.style.border = item.border + 'px solid ' + (item.border_color || '#223ea4');
      } else if (item.type === 'image' && item.src) {
        el = createEl(Object.assign(base, { type: 'logo', label: 'Image ' + (index + 1) }));
        el.innerHTML = '';
        var image = document.createElement('img');
        image.src = item.src; image.alt = '';
        image.style.cssText = 'width:100%;height:100%;object-fit:contain;display:block;';
        el.appendChild(image); appendResizeHandles(el);
        el.style.height = base.h + 'px';
        paintBadgeIcon(el, item.color || '#2e3440');
      } else if (item.type === 'photo') {
        createEl(Object.assign(base, { type: 'photo', label: 'Photo' }));
      } else if (item.type === 'qr') {
        createEl(Object.assign(base, { type: 'qr', label: 'QR Badge', qrUrl: item.value || DEFAULT_QR_URL }));
      }
    });
    refreshLayers(); pushHistory(); selectEl(null);
    document.querySelector('.ed-rail button[data-panel="layers"]').click();
  }
  document.getElementById('aiRun').addEventListener('click', function () {
    var prompt = (document.getElementById('aiPrompt').value || '').trim();
    if (!prompt) { toast('Décrivez le badge souhaité'); return; }
    var cfg = window.UKC_EDITOR || {};
    var btn = document.getElementById('aiRun');
    var body = new FormData();
    body.append('prompt', prompt);
    body.append('event_id', cfg.editionId || '');
    body.append('kind', 'badge');
    body.append('type', cfg.type || 'default');
    if (aiFile) body.append('inspiration', aiFile);
    btn.disabled = true; btn.textContent = 'Génération en cours…';
    fetch(cfg.generateUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json' }, body: body })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.body || !res.body.layout) throw new Error((res.body && res.body.error) || 'Échec de la génération');
        applyGeneratedLayout(res.body.layout);
        toast('Mise en page générée ; vérifiez-la avant enregistrement');
      })
      .catch(function (error) { toast(error.message || 'Échec de la génération'); })
      .finally(function () { btn.disabled = false; btn.textContent = 'Générer'; });
  });

  /* Keyboard */
  document.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    var typing = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;
    if (selected && isEditing(selected)) return;
    if ((e.key === 'Delete' || e.key === 'Backspace') && selected && !typing) { e.preventDefault(); deleteSelected(); return; }
    if ((e.ctrlKey || e.metaKey) && !typing) {
      if ((e.key === 'z' || e.key === 'Z') && e.shiftKey) { e.preventDefault(); redo(); return; }
      if (e.key === 'z' || e.key === 'Z') { e.preventDefault(); undo(); return; }
      if (e.key === 'y' || e.key === 'Y') { e.preventDefault(); redo(); return; }
      if (e.key === 'd' || e.key === 'D') { e.preventDefault(); duplicateLayer(); }
      if (e.key === 'g' || e.key === 'G') { e.preventDefault(); groupSelected(); }
      if (e.key === 'l' || e.key === 'L') { e.preventDefault(); if (selected) toggleLock(selected); }
      if (e.key === ']' && e.shiftKey) { e.preventDefault(); moveLayer('up'); }
      else if (e.key === ']') { e.preventDefault(); moveLayer('front'); }
      if (e.key === '[' && e.shiftKey) { e.preventDefault(); moveLayer('down'); }
      else if (e.key === '[') { e.preventDefault(); moveLayer('back'); }
      if (e.key === 's' || e.key === 'S') { e.preventDefault(); document.getElementById('btnSave').click(); }
    }
    if (e.key === 'Enter' && selected && selected.classList.contains('text') && !typing) {
      e.preventDefault(); startTextEdit(selected);
    }
    if (selected && !typing && !isEditing(selected) && !isLocked(selected)) {
      // +/- ou [ ] : redimensionner photo / QR
      if ((e.key === '+' || e.key === '=' || e.key === ']' || e.key === '-') || e.key === '[') {
        if (selected.classList.contains('photo') || selected.classList.contains('qr')) {
          e.preventDefault();
          var cur = selected.offsetWidth;
          var delta = (e.key === '-' || e.key === '[') ? -4 : 4;
          if (e.shiftKey) delta *= 3;
          var size = Math.max(32, Math.min(280, cur + delta));
          selected.style.width = size + 'px';
          selected.style.height = size + 'px';
          updateFloatBar();
          clearTimeout(window.__arrowHistTimer);
          window.__arrowHistTimer = setTimeout(pushHistory, 400);
          return;
        }
      }
      var step = e.shiftKey ? 10 : 1, moved = false;
      var left = selected.offsetLeft, top = selected.offsetTop;
      if (e.key === 'ArrowLeft') { left -= step; moved = true; }
      if (e.key === 'ArrowRight') { left += step; moved = true; }
      if (e.key === 'ArrowUp') { top -= step; moved = true; }
      if (e.key === 'ArrowDown') { top += step; moved = true; }
      if (moved) {
        e.preventDefault();
        selected.style.left = Math.max(0, left) + 'px';
        selected.style.top = Math.max(0, top) + 'px';
        updateFloatBar();
        clearTimeout(window.__arrowHistTimer);
        window.__arrowHistTimer = setTimeout(pushHistory, 400);
      }
    }
  });

  /* ---------- Aperçus réels des variantes de design ----------
     Chaque carte « Modèles de design » montre le modèle RÉEL (rendu depuis T[tpl],
     rempli des valeurs d'exemple), comme l'aperçu de « Mes badges ». Le rendu réutilise
     la même machinerie que l'aperçu (clone de la page + fillBadgeTokens), d'où une seule
     source de vérité. ⚠️ On ne construit qu'à l'INIT (page vierge) : loadTemplate mute la
     page, et le faire plus tard écraserait le travail en cours. */
  var THUMB_W_FALLBACK = 252;
  function thumbNativeFormat(formats) {
    // Format dans lequel le modèle est dessiné (évite une mise à l'échelle qui déforme).
    if (formats.indexOf('a6-portrait') !== -1) return 'a6-portrait';
    if (formats.indexOf('card-landscape') !== -1) return 'card-landscape';
    return formats[0] || 'a6-portrait';
  }
  function renderVariantClone(key, nativeFmt) {
    SIZE_CLASSES.forEach(function (c) { page.classList.remove(c); });
    if (nativeFmt && nativeFmt !== 'a6-portrait') page.classList.add('size-' + nativeFmt);
    loadTemplate(key);
    var pw = page.offsetWidth || 397, ph = page.offsetHeight || 560;
    var clone = page.cloneNode(true);
    clone.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
    clone.querySelectorAll('.resize, .ed-guide, .ed-guide-label').forEach(function (n) { n.remove(); });
    clone.style.margin = '0'; clone.style.boxShadow = 'none'; clone.style.transform = 'none';
    fillBadgeTokens(clone);
    clone.querySelectorAll('.ed-el').forEach(function (n) {
      n.style.outline = 'none'; n.style.boxShadow = 'none'; n.classList.remove('selected');
      if (!n.classList.contains('shape-box') && !n.classList.contains('qr')) n.style.border = 'none';
      if (n.classList.contains('qr')) {
        var qi = n.querySelector('img');
        if (qi) qi.src = qrImageUrl(n.dataset.qrUrl || DEFAULT_QR_URL, 120);
      }
    });
    return { clone: clone, w: pw, h: ph };
  }
  function fitThumb(mini) {
    var nw = parseFloat(mini.dataset.nw || '0'), nh = parseFloat(mini.dataset.nh || '0');
    var wrap = mini.firstElementChild;
    if (!nw || !nh || !wrap) return;
    var sc = (mini.clientWidth || THUMB_W_FALLBACK) / nw;
    wrap.style.transform = 'scale(' + sc + ')';
    mini.style.height = Math.round(nh * sc) + 'px';
  }
  function fitThumbs() { document.querySelectorAll('.ed-tpl-card .mini').forEach(fitThumb); }
  function buildVariantThumbs() {
    document.querySelectorAll('.ed-tpl-card[data-tpl]').forEach(function (card) {
      var mini = card.querySelector('.mini');
      if (!mini) return;
      var formats = (card.dataset.formats || '').split(' ').filter(Boolean);
      var r = renderVariantClone(card.dataset.tpl, thumbNativeFormat(formats));
      mini.dataset.nw = r.w; mini.dataset.nh = r.h;
      var wrap = document.createElement('div');
      wrap.style.cssText = 'position:absolute;top:0;left:0;width:' + r.w + 'px;height:' + r.h + 'px;transform-origin:top left;';
      wrap.appendChild(r.clone);
      mini.innerHTML = '';
      mini.appendChild(wrap);
      fitThumb(mini);
    });
    // Repartir de l'A6 portrait pour la construction réelle qui suit.
    SIZE_CLASSES.forEach(function (c) { page.classList.remove(c); });
  }
  buildVariantThumbs();
  history = []; historyIndex = -1;

  /* Une catégorie a un seul modèle ; la photo est un choix de design. */
  loadTemplate('co');

  /* ---------- Hydratation depuis le badge enregistré ---------- */
  (function () {
    var snap = (window.UKC_EDITOR || {}).editorState;
    if (snap && snap.els && snap.els.length) {
      try {
        restoreSnapshot(snap);
        refreshLayers();
        history = [snapshot()];
        historyIndex = 0;
        updateUndoButtons();
      } catch (e) { /* conserve le badge par défaut si l'instantané est invalide */ }
    }
  })();

  // La construction initiale a marqué le document « modifié » : on repart propre.
  dirty = false;

  // La construction des aperçus a pu retirer la carte active (filtrage par format) :
  // on remet en évidence la variante initiale, sauf si un badge personnalisé a été chargé.
  (function () {
    var ed = window.UKC_EDITOR || {};
    var hydrated = ed.editorState && ed.editorState.els && ed.editorState.els.length;
    var k = 'co';
    document.querySelectorAll('.ed-tpl-card').forEach(function (c) {
      c.classList.toggle('active', !hydrated && c.dataset.tpl === k);
    });
  })();

  /* ---------- Mode APERÇU verrouillé : ouvre l'aperçu exact du badge ---------- */
  (function () {
    if (!(window.UKC_EDITOR || {}).preview) return;
    requestAnimationFrame(function () {
      var b = document.getElementById('btnPreview');
      if (b) b.click();
    });
  })();
})();
