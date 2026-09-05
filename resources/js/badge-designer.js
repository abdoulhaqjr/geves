(function () {
  var page = document.getElementById('page');
  var layersList = document.getElementById('layersList');
  var styleBar = document.getElementById('styleBar');
  var guidesEl = document.getElementById('guides');
  var selected = null;
  var elId = 0;
  var drag = null;
  var resize = null;
  var history = [];
  var historyIndex = -1;
  var historyLock = false;
  var SNAP = 6;
  var zoomLevel = 1;
  var DEFAULT_QR_URL = 'https://univkara.tg/colloque/badge';
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
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&margin=4&data=' + encodeURIComponent(data || DEFAULT_QR_URL);
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
          blend: el.dataset.blend || 'normal', qrUrl: el.dataset.qrUrl || '',
          style: {
            left: el.style.left, top: el.style.top, width: el.style.width, height: el.style.height,
            opacity: el.style.opacity, fontSize: el.style.fontSize, fontFamily: el.style.fontFamily,
            fontWeight: el.style.fontWeight, color: el.style.color, textAlign: el.style.textAlign,
            mixBlendMode: el.style.mixBlendMode, display: el.style.display
          }
        };
      }),
      bg: page.className
    };
  }
  function pushHistory() {
    if (historyLock) return;
    history = history.slice(0, historyIndex + 1);
    history.push(snapshot());
    if (history.length > 50) history.shift(); else historyIndex++;
    updateUndoButtons();
  }
  function restoreSnapshot(snap) {
    historyLock = true;
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    page.className = snap.bg || 'ed-page';
    (snap.els || []).forEach(function (s) {
      var el = document.createElement('div');
      el.id = s.id; el.className = s.className;
      el.dataset.label = s.label || s.id;
      el.dataset.locked = s.locked || '0';
      el.dataset.hidden = s.hidden || '0';
      el.dataset.blend = s.blend || 'normal';
      if (s.qrUrl) el.dataset.qrUrl = s.qrUrl;
      el.innerHTML = s.html;
      Object.keys(s.style || {}).forEach(function (k) { if (s.style[k]) el.style[k] = s.style[k]; });
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div'); rz.className = 'resize'; el.appendChild(rz);
      }
      page.appendChild(el);
      bindEl(el);
    });
    selected = null;
    styleBar.classList.add('disabled');
    hideFloatBar();
    refreshLayers();
    historyLock = false;
    updateUndoButtons();
  }
  function undo() { if (historyIndex <= 0) return; historyIndex--; restoreSnapshot(history[historyIndex]); toast('Annule'); }
  function redo() { if (historyIndex >= history.length - 1) return; historyIndex++; restoreSnapshot(history[historyIndex]); toast('Retabli'); }
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

  function createEl(cfg) {
    var el = document.createElement('div');
    el.className = 'ed-el';
    el.id = cfg.id || ('el-' + (++elId));
    el.dataset.label = cfg.label || el.id;
    el.dataset.locked = '0';
    el.dataset.hidden = '0';
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
      inner.textContent = 'Photo';
      el.appendChild(inner);
    } else if (cfg.type === 'shape') {
      el.classList.add('shape-box');
      el.style.width = (cfg.w || 40) + 'px';
      el.style.height = (cfg.h || 40) + 'px';
      if (cfg.shape === 'circle') el.style.borderRadius = '50%';
      if (cfg.shape === 'line-h') { el.style.height = '3px'; el.style.width = (cfg.w || 100) + 'px'; }
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
      if (isLocked(el)) { toast('Calque verrouille'); return; }
      if (el.classList.contains('text')) {
        e.preventDefault(); startTextEdit(el);
      } else if (el.classList.contains('qr')) {
        e.preventDefault();
        openInputModal({
          title: 'URL du QR code',
          hint: 'Lien encode dans le QR (verification badge).',
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
      refreshLayers();
      updateFloatBar();
    } else {
      styleBar.classList.add('disabled');
      document.getElementById('lyControls').classList.remove('show');
      hideFloatBar();
    }
    updateLayerToolbar();
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
    ['lyFront', 'lyUp', 'lyDown', 'lyBack', 'lyDup', 'lyDel'].forEach(function (id) {
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
    els.forEach(function (el) {
      var hidden = el.dataset.hidden === '1';
      var locked = el.dataset.locked === '1';
      var row = document.createElement('div');
      row.className = 'ed-layer' + (selected === el ? ' active' : '') + (hidden ? ' hidden-layer' : '') + (locked ? ' locked-layer' : '');
      row.dataset.id = el.id;
      row.draggable = true;
      row.innerHTML =
        '<span class="handle">⠿</span>' +
        '<button type="button" class="ico-btn' + (hidden ? ' off' : '') + '" data-act="vis">' + (hidden ? EYE_OFF : EYE) + '</button>' +
        '<span class="name" title="Double-clic pour renommer">' + (el.dataset.label || el.id) + '</span>' +
        '<div class="actions"><button type="button" class="ico-btn' + (locked ? '' : ' off') + '" data-act="lock">' + (locked ? '🔒' : '🔓') + '</button></div>';
      row.addEventListener('click', function (e) {
        if (e.target.closest('[data-act]')) return;
        selectEl(el);
      });
      row.querySelector('[data-act="vis"]').addEventListener('click', function (e) {
        e.stopPropagation();
        var hide = el.dataset.hidden !== '1';
        el.dataset.hidden = hide ? '1' : '0';
        el.style.display = hide ? 'none' : '';
        refreshLayers();
        if (selected === el && hide) selectEl(null);
      });
      row.querySelector('[data-act="lock"]').addEventListener('click', function (e) {
        e.stopPropagation();
        toggleLock(el);
      });
      row.querySelector('.name').addEventListener('dblclick', function (e) {
        e.stopPropagation();
        var prev = el.dataset.label || el.id;
        var input = document.createElement('input');
        input.value = prev;
        this.textContent = '';
        this.appendChild(input);
        input.focus(); input.select();
        function commit() {
          el.dataset.label = input.value.trim() || prev;
          refreshLayers();
          selectEl(el);
        }
        input.addEventListener('blur', commit);
        input.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter') input.blur();
          if (ev.key === 'Escape') { input.value = prev; input.blur(); }
        });
      });
      row.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', el.id);
        window.__lyDragId = el.id;
      });
      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        var fromEl = document.getElementById(window.__lyDragId);
        if (!fromEl || fromEl === el) return;
        var els2 = getEls();
        var fi = els2.indexOf(fromEl), ti = els2.indexOf(el);
        if (fi < ti) page.insertBefore(fromEl, el.nextSibling);
        else page.insertBefore(fromEl, el);
      });
      row.addEventListener('drop', function (e) {
        e.preventDefault();
        refreshLayers();
        pushHistory();
      });
      layersList.appendChild(row);
    });
    updateLayerToolbar();
  }

  function toggleLock(el) {
    var lock = el.dataset.locked !== '1';
    el.dataset.locked = lock ? '1' : '0';
    el.classList.toggle('locked', lock);
    if (el.classList.contains('text')) el.contentEditable = 'false';
    if (lock) stopTextEdit();
    refreshLayers();
    selectEl(el);
    toast(lock ? 'Calque verrouille' : 'Calque deverrouille');
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

  function deleteSelected() {
    if (!selected) return;
    if (isLocked(selected)) { toast('Calque verrouille'); return; }
    selected.remove();
    selected = null;
    styleBar.classList.add('disabled');
    hideFloatBar();
    refreshLayers();
    pushHistory();
  }

  function loadTemplate(key) {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    selected = null;
    hideFloatBar();
    page.classList.remove('size-a6-landscape', 'size-a7-portrait', 'size-card-landscape', 'size-cr80', 'bg-green', 'bg-split', 'bg-soft', 'bg-none');
    page.style.width = ''; page.style.height = '';
    document.querySelectorAll('[data-size]').forEach(function (b) { b.classList.remove('active'); });

    var T = {
      /* A6 portrait 397×560 — disposition centree, photo 64px */
      participant: function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        // En-tete bandeau bleu (~0-180)
        createEl({ type: 'logo', label: 'Logo UK', x: 24, y: 22, w: 130, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' });
        createEl({ type: 'text', label: 'Edition', x: 24, y: 68, w: 350, cls: 'sub on-blue', text: '[edition.name]' });
        createEl({ type: 'text', label: 'Dates', x: 24, y: 92, w: 350, cls: 'sub on-blue', text: '[edition.dates]' });
        // Zone blanche — photo centree (397/2 - 32 = 166.5)
        createEl({ type: 'photo', label: 'Photo', x: 166, y: 200, w: 64 });
        createEl({ type: 'text', label: 'Nom', x: 28, y: 285, w: 340, cls: 'name attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 28, y: 318, w: 340, cls: 'role attr', text: '[recipient.role]' });
        createEl({ type: 'text', label: 'Institution', x: 28, y: 348, w: 340, cls: 'sub attr', text: '[recipient.institution]' });
        // Bas — QR + ref
        createEl({ type: 'qr', label: 'QR Badge', x: 166, y: 400, w: 64, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 28, y: 480, w: 340, cls: 'sub attr', text: '[badge.ref]' });
      },
      communicant: function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        page.classList.add('bg-green');
        createEl({ type: 'logo', label: 'Logo UK', x: 24, y: 22, w: 130, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' });
        createEl({ type: 'text', label: 'Edition', x: 24, y: 68, w: 350, cls: 'sub on-blue', text: '[edition.name]' });
        createEl({ type: 'text', label: 'Dates', x: 24, y: 92, w: 350, cls: 'sub on-blue', text: '[edition.dates]' });
        createEl({ type: 'photo', label: 'Photo', x: 166, y: 200, w: 64 });
        createEl({ type: 'text', label: 'Nom', x: 28, y: 285, w: 340, cls: 'name attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 28, y: 318, w: 340, cls: 'role', text: 'COMMUNICATION' });
        createEl({ type: 'text', label: 'Type', x: 28, y: 348, w: 340, cls: 'sub attr', text: '[communication.type]' });
        createEl({ type: 'qr', label: 'QR Badge', x: 166, y: 400, w: 64, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 28, y: 480, w: 340, cls: 'sub attr', text: '[badge.ref]' });
      },
      staff: function () {
        var chip = document.querySelector('[data-size="a6-portrait"]');
        if (chip) chip.classList.add('active');
        page.classList.add('bg-none');
        document.getElementById('pageBg').style.background = 'linear-gradient(180deg,#0f172a 0%,#0f172a 32%,#fff 32%)';
        createEl({ type: 'logo', label: 'Logo', x: 24, y: 22, w: 150, html: '<div class="mark">UK</div> ORGANISATION' });
        createEl({ type: 'text', label: 'Edition', x: 24, y: 72, w: 350, cls: 'sub on-blue', text: '[edition.name]' });
        createEl({ type: 'photo', label: 'Photo', x: 166, y: 200, w: 64 });
        createEl({ type: 'text', label: 'Nom', x: 28, y: 285, w: 340, cls: 'name attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 28, y: 318, w: 340, cls: 'role', text: 'STAFF' });
        createEl({ type: 'qr', label: 'QR Badge', x: 166, y: 400, w: 64, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 28, y: 480, w: 340, cls: 'sub attr', text: '[badge.ref]' });
      },
      card: function () {
        page.classList.add('size-card-landscape');
        page.classList.add('bg-split');
        var chip = document.querySelector('[data-size="card-landscape"]');
        if (chip) chip.classList.add('active');
        // Carte 340×208
        createEl({ type: 'logo', label: 'Logo', x: 14, y: 12, w: 110, html: '<div class="mark">UK</div> CSIUK' });
        createEl({ type: 'photo', label: 'Photo', x: 16, y: 70, w: 48 });
        createEl({ type: 'text', label: 'Nom', x: 76, y: 72, w: 160, cls: 'name on-blue attr', text: '[recipient.name]' });
        createEl({ type: 'text', label: 'Role', x: 76, y: 104, w: 160, cls: 'role on-blue attr', text: '[recipient.role]' });
        createEl({ type: 'qr', label: 'QR', x: 260, y: 50, w: 56, qrUrl: DEFAULT_QR_URL });
        createEl({ type: 'text', label: 'Ref', x: 76, y: 160, w: 160, cls: 'sub on-blue attr', text: '[badge.ref]' });
      }
    };

    (T[key] || T.participant)();
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
    });
  });

  document.querySelectorAll('.ed-tpl-card').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('.ed-tpl-card').forEach(function (c) { c.classList.remove('active'); });
      card.classList.add('active');
      loadTemplate(card.dataset.tpl);
      toast('Design « ' + card.dataset.tpl + ' » charge');
    });
  });

  var SIZE_CLASSES = ['size-a6-landscape', 'size-a7-portrait', 'size-card-landscape', 'size-cr80'];
  document.querySelectorAll('[data-size]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-size]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      SIZE_CLASSES.forEach(function (c) { page.classList.remove(c); });
      page.style.width = '';
      page.style.height = '';
      var s = btn.dataset.size;
      if (s === 'a6-landscape') page.classList.add('size-a6-landscape');
      else if (s === 'a7-portrait') page.classList.add('size-a7-portrait');
      else if (s === 'card-landscape') page.classList.add('size-card-landscape');
      else if (s === 'cr80') page.classList.add('size-cr80');
      // a6-portrait = default (no extra class)
      if (selected) updateFloatBar();
      toast('Format : ' + btn.textContent.trim());
    });
  });

  document.querySelectorAll('[data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kind = btn.dataset.add;
      var cfg = { x: 40 + Math.random() * 30, y: 40 + Math.random() * 30 };
      if (kind === 'heading') Object.assign(cfg, { type: 'text', label: 'Heading', cls: 'heading', text: 'Titre', w: 200 });
      else if (kind === 'name') Object.assign(cfg, { type: 'text', label: 'Nom', cls: 'name attr', text: '[recipient.name]', w: 220 });
      else if (kind === 'role') Object.assign(cfg, { type: 'text', label: 'Role', cls: 'role attr', text: '[recipient.role]', w: 180 });
      else if (kind === 'textbox') Object.assign(cfg, { type: 'text', label: 'Texte', cls: 'sub', text: 'Nouveau texte', w: 160 });
      else if (kind === 'photo') Object.assign(cfg, { type: 'photo', label: 'Photo', w: 64 });
      else if (kind === 'qr' || kind === 'qr-verify') Object.assign(cfg, { type: 'qr', label: 'QR Badge', w: 72, qrUrl: DEFAULT_QR_URL });
      else if (kind === 'qr-custom') {
        openInputModal({
          title: 'QR personnalise',
          hint: 'URL a encoder',
          value: 'https://',
          onOk: function (url) {
            if (!url) return;
            selectEl(createEl({ type: 'qr', label: 'QR Custom', x: 60, y: 60, w: 72, qrUrl: url }));
            pushHistory();
          }
        });
        return;
      } else if (kind === 'rect') Object.assign(cfg, { type: 'shape', shape: 'rect', label: 'Rectangle', w: 50, h: 30 });
      else if (kind === 'circle') Object.assign(cfg, { type: 'shape', shape: 'circle', label: 'Cercle', w: 40, h: 40 });
      else if (kind === 'line-h') Object.assign(cfg, { type: 'shape', shape: 'line-h', label: 'Ligne', w: 120 });
      selectEl(createEl(cfg));
      pushHistory();
    });
  });

  document.querySelectorAll('.ed-attr').forEach(function (btn) {
    btn.addEventListener('click', function () {
      selectEl(createEl({ type: 'text', label: btn.dataset.attr, cls: 'attr', text: btn.dataset.attr, x: 60, y: 200, w: 200 }));
      pushHistory();
    });
  });

  document.getElementById('lyFront').addEventListener('click', function () { moveLayer('front'); });
  document.getElementById('lyUp').addEventListener('click', function () { moveLayer('up'); });
  document.getElementById('lyDown').addEventListener('click', function () { moveLayer('down'); });
  document.getElementById('lyBack').addEventListener('click', function () { moveLayer('back'); });
  document.getElementById('lyDup').addEventListener('click', duplicateLayer);
  document.getElementById('lyDel').addEventListener('click', deleteSelected);
  document.getElementById('lyOpacity').addEventListener('input', function () {
    if (!selected) return;
    selected.style.opacity = String(this.value / 100);
    document.getElementById('lyOpacityVal').textContent = this.value + '%';
  });
  document.getElementById('lyBlend').addEventListener('change', function () {
    if (!selected) return;
    selected.dataset.blend = this.value;
    selected.style.mixBlendMode = this.value;
  });

  document.getElementById('btnUndo').addEventListener('click', undo);
  document.getElementById('btnRedo').addEventListener('click', redo);
  document.getElementById('zoomIn').addEventListener('click', function () { zoomLevel = Math.min(2, Math.round((zoomLevel + 0.1) * 10) / 10); applyZoom(); });
  document.getElementById('zoomOut').addEventListener('click', function () { zoomLevel = Math.max(0.5, Math.round((zoomLevel - 0.1) * 10) / 10); applyZoom(); });
  document.getElementById('zoomReset').addEventListener('click', function () { zoomLevel = 1; applyZoom(); });

  document.getElementById('fontSize').addEventListener('change', function () {
    if (selected && !isLocked(selected)) selected.style.fontSize = this.value + 'px';
  });
  document.getElementById('fontSizeMinus').addEventListener('click', function () {
    var i = document.getElementById('fontSize'); i.value = Math.max(8, parseInt(i.value, 10) - 1); i.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontSizePlus').addEventListener('click', function () {
    var i = document.getElementById('fontSize'); i.value = Math.min(48, parseInt(i.value, 10) + 1); i.dispatchEvent(new Event('change'));
  });
  document.getElementById('fontFamily').addEventListener('change', function () {
    if (selected && !isLocked(selected)) selected.style.fontFamily = this.value;
  });
  var fw = document.getElementById('fontWeight');
  if (fw) fw.addEventListener('change', function () {
    if (selected && !isLocked(selected)) selected.style.fontWeight = this.value;
  });
  document.getElementById('fontColor').addEventListener('input', function () {
    if (selected && !isLocked(selected)) selected.style.color = this.value;
  });
  document.querySelectorAll('[data-style]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!selected || isLocked(selected)) return;
      if (btn.dataset.style === 'bold') selected.style.fontWeight = selected.style.fontWeight === '800' || selected.style.fontWeight === '700' ? '400' : '700';
      if (btn.dataset.style === 'italic') selected.style.fontStyle = selected.style.fontStyle === 'italic' ? 'normal' : 'italic';
      if (btn.dataset.style === 'underline') selected.style.textDecoration = selected.style.textDecoration === 'underline' ? 'none' : 'underline';
    });
  });
  document.querySelectorAll('[data-align]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (selected && !isLocked(selected)) selected.style.textAlign = btn.dataset.align;
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
      if (el.dataset.qrUrl || /\bqr\b/i.test(cls) || (img && /qrserver|qrcode/i.test(img.src))) {
        return { type: 'qr', x: x, y: y, size: Math.max(w, h) || 96, value: el.dataset.qrUrl || '{ref}' };
      }
      if (img && /^https?:/i.test(img.src)) {
        return { type: 'image', x: x, y: y, w: w, h: h, src: img.src };
      }
      if (!text && (/\b(shape|rect|line|ln-|sq|band)\b/i.test(cls) || el.style.background || cs.backgroundColor !== 'rgba(0, 0, 0, 0)')) {
        return { type: 'rect', x: x, y: y, w: w, h: h, color: rgbToHex(cs.backgroundColor) || '#223ea4' };
      }
      var align = cs.textAlign;
      return {
        type: 'text', x: x, y: y, w: w, h: h,
        text: text.slice(0, 500),
        size: Math.round(parseFloat(cs.fontSize) || 14),
        weight: parseInt(cs.fontWeight, 10) || 500,
        color: rgbToHex(cs.color) || '#1a2332',
        align: (align === 'center' || align === 'right') ? align : 'left'
      };
    }).filter(function (e) { return e && !(e.type === 'text' && !e.text); });
    return { width: W, height: H, bg: '#ffffff', accent: '#223ea4', elements: elements };
  }
  document.getElementById('btnSave').addEventListener('click', function () {
    var cfg = window.UKC_EDITOR || {};
    var btn = this;
    if (!cfg.saveUrl) { toast('Enregistrement indisponible'); return; }
    btn.disabled = true;
    var original = btn.textContent;
    btn.textContent = '…';
    fetch(cfg.saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json' },
      body: JSON.stringify({ edition_id: cfg.editionId || '', kind: cfg.kind || 'badge', type: cfg.type || 'default', layout: serializeLayout(), editor_state: snapshot() })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j && res.j.ok) toast('Badge « ' + document.getElementById('tplName').value + ' » enregistré');
        else toast((res.j && res.j.error) ? res.j.error : 'Échec de l’enregistrement');
      })
      .catch(function () { toast('Erreur réseau lors de l’enregistrement'); })
      .finally(function () { btn.disabled = false; btn.textContent = original; });
  });
  document.getElementById('btnPreview').addEventListener('click', function () {
    var old = document.getElementById('ukcPreviewOverlay');
    if (old) { old.remove(); return; }
    var overlay = document.createElement('div');
    overlay.id = 'ukcPreviewOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.88);display:flex;align-items:center;justify-content:center;padding:24px;overflow:auto;';
    var pw = page.offsetWidth || 397, ph = page.offsetHeight || 560;
    var scale = Math.min(1, (window.innerWidth - 80) / pw, (window.innerHeight - 120) / ph);
    var clone = page.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.transform = 'none';
    clone.style.margin = '0';
    clone.style.boxShadow = '0 20px 60px rgba(0,0,0,.5)';
    clone.querySelectorAll('.resize, .ed-guide, .ed-guide-label').forEach(function (n) { n.remove(); });
    clone.querySelectorAll('.ed-el').forEach(function (n) { n.style.outline = 'none'; n.style.border = 'none'; n.style.boxShadow = 'none'; n.classList.remove('selected'); });
    var frame = document.createElement('div');
    frame.style.cssText = 'transform:scale(' + scale + ');transform-origin:center center;';
    frame.appendChild(clone);
    var close = document.createElement('button');
    close.type = 'button';
    close.textContent = '✕ Fermer l’aperçu';
    close.style.cssText = 'position:fixed;top:16px;right:20px;z-index:10000;background:#fff;color:#1a2332;border-radius:8px;padding:9px 16px;font-weight:700;font-family:inherit;font-size:13.5px;box-shadow:0 4px 14px rgba(0,0,0,.3);';
    close.addEventListener('click', function () { overlay.remove(); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.remove(); });
    function onEsc(e) { if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', onEsc); } }
    document.addEventListener('keydown', onEsc);
    overlay.appendChild(frame);
    overlay.appendChild(close);
    document.body.appendChild(overlay);
  });
  document.getElementById('btnPreview').addEventListener('click', function () {
    // Aperçu désactivé côté back-office (pas de page de scan autonome ici).
  });
  document.getElementById('btnBlank').addEventListener('click', function () {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    selected = null; hideFloatBar(); refreshLayers(); pushHistory();
    toast('Canvas vide');
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
      page.classList.remove('bg-green', 'bg-split', 'bg-soft', 'bg-none');
      document.getElementById('pageBg').style.background = '';
      document.getElementById('pageBg').style.backgroundImage = '';
      var k = opt.dataset.bg;
      if (k === 'green') page.classList.add('bg-green');
      else if (k === 'split') page.classList.add('bg-split');
      else if (k === 'soft') page.classList.add('bg-soft');
      else if (k === 'none') page.classList.add('bg-none');
      pushHistory();
      bgModal.classList.remove('open');
      toast('Fond applique');
    });
  });
  document.getElementById('bgImportZone').addEventListener('click', function () { document.getElementById('bgFileInput').click(); });
  document.getElementById('bgFileInput').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () {
      page.classList.add('bg-none');
      var bg = document.getElementById('pageBg');
      bg.style.backgroundImage = 'url(' + reader.result + ')';
      bg.style.backgroundSize = 'cover';
      pushHistory();
      bgModal.classList.remove('open');
      toast('Image de fond importee');
    };
    reader.readAsDataURL(file);
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
    var reader = new FileReader();
    reader.onload = function () {
      var thumb = document.createElement('div');
      thumb.className = 'ed-thumb';
      thumb.dataset.src = reader.result;
      thumb.style.backgroundImage = 'url(' + reader.result + ')';
      thumb.title = file.name;
      thumb.addEventListener('click', function () {
        selectEl(createEl({
          type: 'logo', label: file.name.slice(0, 16), x: 40, y: 40, w: 80,
          html: '<img src="' + reader.result + '" alt="" style="max-width:100%;max-height:60px;display:block;">'
        }));
        pushHistory();
      });
      document.getElementById('uploadList').appendChild(thumb);
      toast('Image ajoutee');
    };
    reader.readAsDataURL(file);
    e.target.value = '';
  });

  /* AI */
  document.getElementById('aiDrop').addEventListener('click', function () { document.getElementById('aiFile').click(); });
  document.getElementById('aiRun').addEventListener('click', function () {
    var prompt = (document.getElementById('aiPrompt').value || '').trim();
    if (!prompt) { toast('Saisissez un prompt'); return; }
    var btn = document.getElementById('aiRun');
    btn.disabled = true; btn.textContent = 'Analyse…';
    setTimeout(function () {
      var key = /staff|orga/i.test(prompt) ? 'staff' : /communic/i.test(prompt) ? 'communicant' : /carte|card/i.test(prompt) ? 'card' : 'participant';
      loadTemplate(key);
      btn.disabled = false; btn.textContent = 'Generer';
      toast('Badge genere a partir du prompt');
    }, 900);
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
      if (e.key === 'l' || e.key === 'L') { e.preventDefault(); if (selected) toggleLock(selected); }
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

  /* Init */
  loadTemplate('participant');

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
})();
