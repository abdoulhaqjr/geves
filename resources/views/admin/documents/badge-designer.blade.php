<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Éditeur de badges — UK Colloques</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php($ukcEditorCfg = ['editionId' => (string) $edition_id, 'kind' => $kind, 'type' => $type, 'typeLabel' => $typeLabel ?? 'Badge', 'backUrl' => route('admin.documents.attestations'), 'saveUrl' => route('admin.documents.templates.save'), 'csrf' => csrf_token(), 'editorState' => $editorState ?? null])
  <script>window.UKC_EDITOR = @json($ukcEditorCfg);</script>
@verbatim
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --ukc-blue: #223ea4;
      --ukc-blue-dark: #1a3080;
      --ukc-green: #009933;
      --ukc-text: #1a2332;
      --ukc-muted: #5a6577;
      --ukc-light: #8b95a8;
      --ukc-border: #e5e7eb;
      --ukc-bg: #f3f4f6;
      --ukc-font: 'Jost', system-ui, sans-serif;
      --sidebar: 56px;
      --panel: 300px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--ukc-font);
      background: var(--ukc-bg);
      color: var(--ukc-text);
      height: 100vh;
      overflow: hidden;
    }
    button, input, select, textarea { font-family: inherit; }
    button { cursor: pointer; border: none; background: none; }

    .ed-top {
      height: 52px; background: #fff; border-bottom: 1px solid var(--ukc-border);
      display: flex; align-items: center; padding: 0 12px 0 8px; gap: 10px; flex-shrink: 0;
    }
    .ed-top .back { padding: 6px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--ukc-muted); }
    .ed-top .back:hover { background: var(--ukc-bg); color: var(--ukc-text); }
    .ed-top .crumb { font-size: 14px; font-weight: 700; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ed-top .name-input {
      border: 1px solid var(--ukc-border); border-radius: 8px;
      padding: 7px 12px; font-size: 13px; font-weight: 600; min-width: 200px;
    }
    .ed-top .btn-save {
      background: var(--ukc-blue); color: #fff; font-weight: 700;
      padding: 8px 18px; border-radius: 8px; font-size: 13.5px;
    }
    .ed-top .btn-save:hover { background: var(--ukc-blue-dark); }

    .ed-shell {
      display: grid;
      grid-template-columns: var(--sidebar) var(--panel) 1fr;
      height: calc(100vh - 52px);
      min-height: 0;
    }

    .ed-rail {
      background: #fff; border-right: 1px solid var(--ukc-border);
      display: flex; flex-direction: column; align-items: center;
      padding: 10px 0; gap: 4px;
    }
    .ed-rail button {
      width: 44px; height: 48px; border-radius: 10px;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 2px; color: var(--ukc-muted); font-size: 9px; font-weight: 700;
    }
    .ed-rail button svg { width: 20px; height: 20px; }
    .ed-rail button:hover { background: var(--ukc-bg); color: var(--ukc-text); }
    .ed-rail button.active { background: #eef2ff; color: var(--ukc-blue); }

    .ed-panel {
      background: #fff; border-right: 1px solid var(--ukc-border);
      display: flex; flex-direction: column; overflow: hidden; min-height: 0;
    }
    .ed-panel-head {
      padding: 14px 14px 10px; font-size: 13px; font-weight: 800;
      border-bottom: 1px solid var(--ukc-border);
      display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    }
    .ed-panel-body { flex: 1; overflow-y: auto; padding: 12px; min-height: 0; }
    .ed-panel-section {
      font-size: 11px; font-weight: 800; text-transform: uppercase;
      letter-spacing: .05em; color: var(--ukc-light); margin: 12px 0 8px;
    }
    .ed-panel-section:first-child { margin-top: 0; }

    .ed-tool-btn {
      width: 100%; text-align: left; padding: 10px 12px;
      border: 1px solid var(--ukc-border); border-radius: 10px;
      font-size: 13px; font-weight: 600; margin-bottom: 8px;
      background: #fff; color: var(--ukc-text);
    }
    .ed-tool-btn:hover { border-color: var(--ukc-blue); background: #f8fafc; }

    .ed-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .ed-shape {
      aspect-ratio: 1; border: 1px solid var(--ukc-border); border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      background: #fff; cursor: pointer;
    }
    .ed-shape:hover { border-color: var(--ukc-blue); background: #eef2ff; }
    .ed-shape .sq { width: 22px; height: 22px; background: #111; border-radius: 3px; }
    .ed-shape .ci { width: 22px; height: 22px; background: #111; border-radius: 50%; }
    .ed-shape .ln-h { width: 28px; height: 2px; background: #111; }

    .ly-toolbar { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px; }
    .ly-toolbar button {
      flex: 1; min-width: 32px; height: 32px; border-radius: 6px;
      border: 1px solid var(--ukc-border); font-size: 12px; font-weight: 700;
      color: var(--ukc-text); background: #fff;
    }
    .ly-toolbar button:hover:not(:disabled) { border-color: var(--ukc-blue); background: #eef2ff; color: var(--ukc-blue); }
    .ly-toolbar button:disabled { opacity: .35; cursor: not-allowed; }

    .ed-layer {
      display: grid; grid-template-columns: 18px 28px 1fr auto;
      align-items: center; gap: 6px;
      padding: 8px; border: 1px solid var(--ukc-border);
      border-radius: 8px; margin-bottom: 4px; font-size: 12.5px; font-weight: 600;
      background: #fff; cursor: pointer; user-select: none;
      transition: transform .2s ease, border-color .15s, opacity .2s;
    }
    .ed-layer:hover { border-color: #c7d2fe; }
    .ed-layer.active { border-color: var(--ukc-blue); background: #eef2ff; }
    .ed-layer.hidden-layer { opacity: .5; }
    .ed-layer.locked-layer { background: #f8fafc; }
    .ed-layer .handle { color: var(--ukc-light); cursor: grab; font-size: 14px; text-align: center; }
    .ed-layer .ico-btn {
      width: 26px; height: 26px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      color: var(--ukc-muted); font-size: 13px;
    }
    .ed-layer .ico-btn:hover { background: #e2e8f0; color: var(--ukc-text); }
    .ed-layer .ico-btn.off { color: #cbd5e1; }
    .ed-layer .ico-btn svg { width: 16px; height: 16px; display: block; }
    .ed-layer .name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
    .ed-layer .name input {
      width: 100%; border: 1px solid var(--ukc-blue); border-radius: 4px;
      padding: 2px 6px; font-size: 12px; font-weight: 600;
    }

    .ly-meta { font-size: 11px; color: var(--ukc-light); margin-bottom: 8px; line-height: 1.4; }
    .ly-controls {
      margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--ukc-border);
      display: none; flex-direction: column; gap: 10px;
    }
    .ly-controls.show { display: flex; }
    .ly-controls label { font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .ly-controls input[type="range"] { flex: 1; }
    .ly-controls select {
      flex: 1; border: 1px solid var(--ukc-border); border-radius: 6px;
      padding: 5px 8px; font-size: 12px; font-weight: 600;
    }

    .ed-attr {
      font-size: 12px; font-weight: 600; padding: 8px 10px;
      border: 1px dashed var(--ukc-border); border-radius: 8px;
      margin-bottom: 6px; cursor: pointer; background: #fafafa;
      font-family: ui-monospace, monospace; color: #3730a3;
    }
    .ed-attr:hover { border-color: var(--ukc-blue); background: #eef2ff; }

    .ed-upload {
      border: 1px dashed var(--ukc-border); border-radius: 10px;
      padding: 16px; text-align: center; font-size: 13px; color: var(--ukc-muted);
      margin-bottom: 12px; cursor: pointer;
    }
    .ed-upload:hover { border-color: var(--ukc-blue); color: var(--ukc-blue); }
    .ed-thumb {
      border: 1px solid var(--ukc-border); border-radius: 8px;
      padding: 10px; text-align: center; font-size: 11px; font-weight: 700;
      color: var(--ukc-blue); cursor: pointer; background: #fff;
      min-height: 56px; background-size: contain; background-repeat: no-repeat; background-position: center;
    }
    .ed-thumb[data-src] { color: transparent; font-size: 0; }
    .ed-thumb:hover { border-color: var(--ukc-blue); }

    .ed-tpl-card {
      border: 1px solid var(--ukc-border); border-radius: 10px;
      padding: 8px; margin-bottom: 8px; cursor: pointer; background: #fff;
      font-size: 11px; color: var(--ukc-muted);
    }
    .ed-tpl-card:hover, .ed-tpl-card.active {
      border-color: var(--ukc-blue); box-shadow: 0 0 0 2px rgba(34,62,164,.15);
    }
    .ed-tpl-card .mini {
      height: 90px; border-radius: 6px; margin-bottom: 6px;
      background: linear-gradient(160deg, #223ea4 0%, #223ea4 38%, #009933 38%, #009933 100%);
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 10px; color: #fff; text-align: center; padding: 8px;
    }

    .ed-work { display: flex; flex-direction: column; min-width: 0; min-height: 0; overflow: hidden; }
    .ed-toolbar {
      background: #fff; border-bottom: 1px solid var(--ukc-border);
      padding: 8px 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex-shrink: 0;
    }
    .ed-toolbar .label { font-size: 12px; font-weight: 700; color: var(--ukc-muted); }
    .ed-toolbar .chip {
      padding: 5px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700;
      border: 1px solid var(--ukc-border); background: #fff; color: var(--ukc-text);
    }
    .ed-toolbar .chip.active { background: #eef2ff; border-color: var(--ukc-blue); color: var(--ukc-blue); }
    .ed-toolbar .spacer { flex: 1; }
    .ed-toolbar .btn-preview {
      padding: 6px 12px; border: 1px solid var(--ukc-border); border-radius: 8px;
      font-size: 12.5px; font-weight: 700; color: var(--ukc-text);
    }
    .ed-toolbar .sbtn-tool {
      width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--ukc-border);
      display: inline-flex; align-items: center; justify-content: center; color: var(--ukc-text); background: #fff;
    }
    .ed-toolbar .sbtn-tool:hover:not(:disabled) { background: #f1f5f9; }
    .ed-toolbar .sbtn-tool:disabled { opacity: .35; cursor: not-allowed; }
    .ed-toolbar .sbtn-tool svg { width: 16px; height: 16px; }

    .ed-zoom-bar {
      display: flex; align-items: center; gap: 6px;
      background: #fff; border: 1px solid var(--ukc-border);
      border-radius: 8px; padding: 2px 6px;
    }
    .ed-zoom-bar button {
      width: 28px; height: 28px; border-radius: 6px;
      font-weight: 800; font-size: 14px; color: var(--ukc-text);
      display: flex; align-items: center; justify-content: center;
    }
    .ed-zoom-bar button:hover { background: #f1f5f9; }
    .ed-zoom-bar span { font-size: 12px; font-weight: 700; min-width: 42px; text-align: center; }

    .ed-stylebar {
      background: #fff; border-bottom: 1px solid var(--ukc-border);
      padding: 8px 14px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
      flex-shrink: 0; min-height: 48px;
    }
    .ed-stylebar.disabled { opacity: .55; pointer-events: none; }
    .ed-stylebar select, .ed-stylebar input[type="number"] {
      border: 1px solid var(--ukc-border); border-radius: 6px;
      padding: 4px 8px; font-size: 12px; font-weight: 600;
    }
    .ed-stylebar .sbtn {
      width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--ukc-border);
      display: inline-flex; align-items: center; justify-content: center;
      color: var(--ukc-text); background: #fff;
    }
    .ed-stylebar .sbtn:hover { background: #f1f5f9; }
    .ed-stylebar .sbtn.active { background: #eef2ff; border-color: var(--ukc-blue); color: var(--ukc-blue); }
    .ed-stylebar .sbtn.danger { color: #991b1b; }
    .ed-stylebar .sbtn svg { width: 16px; height: 16px; }
    .ed-stylebar .sep { width: 1px; height: 22px; background: var(--ukc-border); margin: 0 4px; }
    .ed-stylebar input[type="color"] {
      width: 32px; height: 32px; border: 1px solid var(--ukc-border);
      border-radius: 6px; padding: 2px; background: #fff;
    }

    .ed-canvas-area {
      flex: 1; overflow: auto; background: #d1d5db;
      display: flex; align-items: flex-start; justify-content: center;
      padding: 36px; min-height: 0;
    }
    .ed-canvas-inner { transform-origin: center top; transition: transform .15s ease; }

    /* Formats badge (96 dpi : 1 mm ≈ 3.78 px) */
    .ed-page {
      width: 397px; height: 560px; /* A6 portrait 105×148 mm */
      background: #fff; position: relative;
      box-shadow: 0 16px 48px rgba(0,0,0,.18);
      overflow: hidden; flex-shrink: 0;
      transition: width .25s ease, height .25s ease;
      border-radius: 8px;
    }
    .ed-page.size-a6-landscape { width: 560px; height: 397px; }
    .ed-page.size-a7-portrait { width: 280px; height: 397px; }
    .ed-page.size-card-landscape { width: 340px; height: 208px; border-radius: 6px; }
    .ed-page.size-cr80 { width: 324px; height: 204px; border-radius: 6px; }
    .ed-page-bg {
      position: absolute; inset: 0; pointer-events: none;
      background: linear-gradient(180deg, #223ea4 0%, #223ea4 32%, #fff 32%, #fff 100%);
      border-radius: inherit;
    }
    .ed-page.bg-green .ed-page-bg {
      background: linear-gradient(180deg, #009933 0%, #009933 32%, #fff 32%, #fff 100%);
    }
    .ed-page.bg-split .ed-page-bg {
      background: linear-gradient(160deg, #223ea4 0%, #223ea4 40%, #009933 40%, #009933 100%);
    }
    .ed-page.bg-none .ed-page-bg { background: #fff; }
    .ed-page.bg-soft .ed-page-bg {
      background: linear-gradient(180deg, #eef2ff 0%, #fff 40%);
    }

    .ed-el {
      position: absolute; cursor: move; user-select: none;
      padding: 3px 5px; border: 1px solid transparent; min-width: 24px;
    }
    .ed-el:hover { border-color: rgba(34,62,164,.35); }
    .ed-el.selected {
      border-color: var(--ukc-blue);
      box-shadow: 0 0 0 2px rgba(34,62,164,.2);
      z-index: 50;
    }
    .ed-el.locked { cursor: not-allowed; }
    .ed-el.locked:hover { border-color: transparent; }
    .ed-el.editing {
      cursor: text; user-select: text;
      border-color: var(--ukc-blue);
      box-shadow: 0 0 0 2px rgba(34,62,164,.25);
      background: transparent;
    }
    .ed-el .resize {
      display: none; position: absolute; right: -5px; bottom: -5px;
      width: 10px; height: 10px; background: var(--ukc-blue);
      border-radius: 2px; cursor: nwse-resize;
    }
    .ed-el.selected:not(.locked):not(.editing) .resize { display: block; }

    .ed-el.text { font-size: 13px; line-height: 1.35; color: var(--ukc-text); outline: none; }
    .ed-el.text.heading {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 16px; font-weight: 900; text-align: center; color: #fff;
    }
    .ed-el.text.sub { font-size: 11px; color: #4b5563; }
    .ed-el.text.name { font-size: 17px; font-weight: 800; text-align: center; line-height: 1.25; }
    .ed-el.text.role {
      font-size: 11px; font-weight: 700; text-align: center;
      color: var(--ukc-blue); text-transform: uppercase; letter-spacing: .04em;
    }
    .ed-el.text.sub { text-align: center; }
    .ed-el.text.attr { color: #3730a3; font-weight: 700; background: transparent; }
    .ed-el.text.on-blue { color: #fff; }
    .ed-el.logo {
      font-size: 11px; font-weight: 900; color: #fff;
      display: flex; align-items: center; gap: 5px;
    }
    .ed-el.logo .mark {
      width: 28px; height: 28px; border-radius: 6px;
      background: linear-gradient(135deg, #fff, #e0e7ff);
      color: var(--ukc-blue); display: flex; align-items: center; justify-content: center; font-size: 10px;
    }
    .ed-el.photo {
      min-width: 28px; min-height: 28px;
      border-radius: 50%;
      background: transparent; border: none !important;
      box-shadow: none;
      overflow: visible; display: block;
      font-size: 10px; font-weight: 700; color: var(--ukc-muted);
      padding: 0 !important;
    }
    .ed-el.photo .photo-inner {
      width: 100%; height: 100%; border-radius: 50%; overflow: hidden;
      background: #e2e8f0; border: 3px solid #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,.12);
      display: flex; align-items: center; justify-content: center;
    }
    .ed-el.photo img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; display: block; }
    .ed-el .resize {
      display: none; position: absolute;
      width: 12px; height: 12px; background: var(--ukc-blue);
      border: 2px solid #fff; border-radius: 50%;
      box-shadow: 0 1px 4px rgba(0,0,0,.35); z-index: 6;
    }
    .ed-el .resize.se { right: -6px; bottom: -6px; cursor: nwse-resize; }
    .ed-el .resize.sw { left: -6px; bottom: -6px; cursor: nesw-resize; }
    .ed-el .resize.ne { right: -6px; top: -6px; cursor: nesw-resize; }
    .ed-el .resize.nw { left: -6px; top: -6px; cursor: nwse-resize; }
    .ed-el.selected:not(.locked):not(.editing) .resize { display: block; }
    .ed-el.qr {
      width: 72px; height: 72px; background: #fff !important;
      border: 1px solid #d1d5db !important; padding: 4px !important;
      display: flex; align-items: center; justify-content: center;
    }
    .ed-el.qr img { width: 100%; height: 100%; object-fit: contain; pointer-events: none; }
    .ed-el.shape-box { background: #111; border-radius: 4px; }

    .ed-guide-x, .ed-guide-y {
      position: absolute; pointer-events: none; z-index: 100; background: #ec4899;
    }
    .ed-guide-x { height: 1px; left: 0; right: 0; }
    .ed-guide-y { width: 1px; top: 0; bottom: 0; }
    .ed-margin-label {
      position: absolute; pointer-events: none; z-index: 102;
      background: #0ea5e9; color: #fff; font-size: 10px; font-weight: 800;
      padding: 2px 6px; border-radius: 4px; white-space: nowrap;
    }
    .ed-margin-line {
      position: absolute; pointer-events: none; z-index: 100; background: #0ea5e9;
    }

    .ed-float-bar {
      position: absolute; z-index: 120; display: none; align-items: center; gap: 2px;
      background: #1e293b; color: #fff; border-radius: 8px; padding: 4px;
      box-shadow: 0 8px 24px rgba(0,0,0,.25); white-space: nowrap;
    }
    .ed-float-bar.show { display: flex; }
    .ed-float-bar button {
      width: 32px; height: 32px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center; color: #e2e8f0;
    }
    .ed-float-bar button:hover { background: rgba(255,255,255,.12); color: #fff; }
    .ed-float-bar button svg { width: 16px; height: 16px; }
    .ed-float-bar .sep { width: 1px; height: 18px; background: rgba(255,255,255,.2); margin: 0 2px; }

    .ed-modal-bg {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(15,26,51,.5); align-items: center; justify-content: center; padding: 20px;
    }
    .ed-modal-bg.open { display: flex; }
    .ed-modal-bg .box {
      background: #fff; border-radius: 16px; width: 100%; max-width: 480px;
      max-height: 90vh; overflow: auto; box-shadow: 0 24px 60px rgba(0,0,0,.25);
    }
    .ed-modal-bg .box-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 18px; border-bottom: 1px solid var(--ukc-border);
      font-weight: 800; font-size: 15px;
    }
    .ed-modal-bg .box-body { padding: 16px 18px 20px; }
    .ed-modal-bg .bg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .ed-modal-bg .bg-opt {
      border: 2px solid var(--ukc-border); border-radius: 10px; height: 72px; cursor: pointer; overflow: hidden; position: relative;
    }
    .ed-modal-bg .bg-opt:hover, .ed-modal-bg .bg-opt.active { border-color: var(--ukc-blue); }
    .ed-modal-bg .bg-opt span {
      position: absolute; bottom: 6px; left: 8px;
      font-size: 11px; font-weight: 700; background: rgba(255,255,255,.9); padding: 2px 6px; border-radius: 4px;
    }
    .ed-modal-bg .import-zone {
      border: 2px dashed var(--ukc-border); border-radius: 10px;
      padding: 20px; text-align: center; font-size: 13px; color: var(--ukc-muted); cursor: pointer;
    }

    .ai-box {
      border: 1px solid var(--ukc-border); border-radius: 12px;
      padding: 12px; background: #f8fafc; margin-bottom: 12px;
    }
    .ai-box textarea {
      width: 100%; min-height: 11em; height: 11em; resize: vertical;
      border: 1px solid var(--ukc-border); border-radius: 8px;
      padding: 10px; font-size: 13px; font-weight: 500; font-family: inherit;
    }
    .ai-drop {
      border: 2px dashed var(--ukc-border); border-radius: 10px;
      padding: 14px; text-align: center; font-size: 12.5px; color: var(--ukc-muted);
      cursor: pointer; background: #fff; margin-top: 10px;
    }
    .ai-drop:hover { border-color: var(--ukc-blue); color: var(--ukc-blue); }
    .ai-run {
      width: 100%; margin-top: 12px;
      background: linear-gradient(135deg, #223ea4, #009933);
      color: #fff; font-weight: 800; font-size: 13.5px;
      padding: 12px 14px; border-radius: 10px;
    }

    .ed-toast {
      position: fixed; bottom: 20px; right: 20px;
      background: #0f172a; color: #fff; padding: 12px 16px;
      border-radius: 10px; font-size: 13px; font-weight: 600;
      display: none; z-index: 100; max-width: 320px;
    }
    .ed-toast.show { display: block; }
    .panel-view { display: none; height: 100%; flex-direction: column; min-height: 0; }
    .panel-view.active { display: flex; }
    .kbd {
      display: inline-block; font-size: 10px; font-weight: 700;
      background: #e2e8f0; color: #475569; padding: 1px 5px; border-radius: 4px;
    }
  </style>
</head>
<body>

  <header class="ed-top">
    <button type="button" class="back" onclick="location.href=(window.UKC_EDITOR&&window.UKC_EDITOR.backUrl)||'/admin/attestations'">← Documents</button>
    <div class="crumb">Badge participant CSIUK25</div>
    <input class="name-input" id="tplName" value="Badge participant CSIUK25">
    <button type="button" class="btn-save" id="btnSave">Save</button>
  </header>

  <div class="ed-shell">
    <nav class="ed-rail">
      <button type="button" data-panel="ai" title="Assistant IA">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/></svg>
        IA
      </button>
      <button type="button" class="active" data-panel="templates" title="Templates">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"/></svg>
        Templates
      </button>
      <button type="button" data-panel="uploads" title="Uploads">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
        Uploads
      </button>
      <button type="button" data-panel="elements" title="Elements">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4.5 2 7L12 17l-6.5 3.5 2-7L2 9h7z"/></svg>
        Elements
      </button>
      <button type="button" data-panel="text" title="Text">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>
        Text
      </button>
      <button type="button" data-panel="attributes" title="Attributes">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
        Attributes
      </button>
      <button type="button" data-panel="qrcodes" title="QR Codes">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5z"/></svg>
        QR Codes
      </button>
      <button type="button" data-panel="layers" title="Layers">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 9L2 7v2l10 5 10-5V7l-10 4zm0 4L2 11v2l10 5 10-5v-2l-10 4z"/></svg>
        Layers
      </button>
    </nav>

    <aside class="ed-panel">
      <div class="panel-view" id="panel-ai">
        <div class="ed-panel-head">Assistant IA</div>
        <div class="ed-panel-body">
          <p class="ly-meta">Decrivez le badge souhaite (role, couleurs, photo, QR…). L IA propose une mise en page editable.</p>
          <div class="ai-box">
            <textarea id="aiPrompt" rows="8" placeholder="Ex. : Badge communicant, bandeau bleu UK, photo ronde, nom en grand, role COMMUNICATION, QR en bas, reference UKC-2026-…"></textarea>
            <div class="ai-drop" id="aiDrop">Image / PDF de reference<br><span style="font-size:11px;">PNG, JPG, PDF · max 8 Mo</span></div>
            <input type="file" id="aiFile" accept="image/*,.pdf" style="display:none;">
            <div id="aiFileList"></div>
            <button type="button" class="ai-run" id="aiRun">Generer</button>
          </div>
        </div>
      </div>

      <div class="panel-view active" id="panel-templates">
        <div class="ed-panel-head">Modeles de design</div>
        <div class="ed-panel-body">
          <p class="ly-meta">Bases de badge participant CSIUK. Personnalisez ensuite les elements.</p>
          <div class="ed-panel-section">Formats portrait</div>
          <div class="ed-tpl-card active" data-tpl="participant">
            <div class="mini">PARTICIPANT</div>
            Bandeau bleu · photo · nom · QR
          </div>
          <div class="ed-tpl-card" data-tpl="communicant">
            <div class="mini" style="background:linear-gradient(160deg,#009933 0%,#009933 38%,#fff 38%);color:#fff;">COMMUNICANT</div>
            Bandeau vert · role COMMUNICATION
          </div>
          <div class="ed-tpl-card" data-tpl="staff">
            <div class="mini" style="background:linear-gradient(160deg,#0f172a 0%,#0f172a 38%,#fff 38%);">STAFF / ORGA</div>
            Badge organisation
          </div>
          <div class="ed-panel-section">Format carte</div>
          <div class="ed-tpl-card" data-tpl="card">
            <div class="mini" style="height:56px;background:linear-gradient(90deg,#223ea4 40%,#009933 40%);">CARTE</div>
            Badge paysage type carte
          </div>
          <button type="button" class="ed-tool-btn" id="btnBlank">+ Design vierge</button>
        </div>
      </div>

      <div class="panel-view" id="panel-uploads">
        <div class="ed-panel-head">Uploads</div>
        <div class="ed-panel-body">
          <div class="ed-upload" id="btnUploadImg">Upload Image<br><span style="font-size:11px;">SVG, PNG, JPG · max 2 Mo</span></div>
          <input type="file" id="fileUpload" accept="image/*" style="display:none;">
          <div class="ed-panel-section">Images ajoutees</div>
          <div class="ed-grid-2" id="uploadList">
            <div class="ed-thumb" data-logo="uk">UK</div>
            <div class="ed-thumb" data-logo="up">Parakou</div>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-elements">
        <div class="ed-panel-head">Elements</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="photo">+ Photo participant</button>
          <div class="ed-panel-section">Formes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-shape" data-add="rect"><div class="sq"></div></button>
            <button type="button" class="ed-shape" data-add="circle"><div class="ci"></div></button>
            <button type="button" class="ed-shape" data-add="line-h"><div class="ln-h"></div></button>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-text">
        <div class="ed-panel-head">Text</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="textbox">+ Add text box</button>
          <button type="button" class="ed-tool-btn" data-add="heading">Add heading</button>
          <button type="button" class="ed-tool-btn" data-add="name">Add nom participant</button>
          <button type="button" class="ed-tool-btn" data-add="role">Add role</button>
        </div>
      </div>

      <div class="panel-view" id="panel-attributes">
        <div class="ed-panel-head">Attributes</div>
        <div class="ed-panel-body">
          <div class="ed-attr" data-attr="[recipient.name]">[recipient.name]</div>
          <div class="ed-attr" data-attr="[recipient.institution]">[recipient.institution]</div>
          <div class="ed-attr" data-attr="[recipient.role]">[recipient.role]</div>
          <div class="ed-attr" data-attr="[recipient.photo]">[recipient.photo]</div>
          <div class="ed-attr" data-attr="[badge.ref]">[badge.ref]</div>
          <div class="ed-attr" data-attr="[edition.name]">[edition.name]</div>
          <div class="ed-attr" data-attr="[edition.dates]">[edition.dates]</div>
          <div class="ed-attr" data-attr="[edition.theme]">[edition.theme]</div>
        </div>
      </div>

      <div class="panel-view" id="panel-qrcodes">
        <div class="ed-panel-head">QR Codes</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="qr-verify">+ QR verification badge</button>
          <button type="button" class="ed-tool-btn" data-add="qr-custom">+ Custom URL</button>
          <p class="ly-meta">Par defaut : page badge-scan (DIDs / inscription).</p>
        </div>
      </div>

      <div class="panel-view" id="panel-layers">
        <div class="ed-panel-head"><span>Layers</span><span style="font-size:11px;font-weight:600;color:var(--ukc-light);" id="lyCount">0</span></div>
        <div class="ed-panel-body">
          <div class="ly-toolbar">
            <button type="button" id="lyFront" title="Premier plan">⤒</button>
            <button type="button" id="lyUp" title="Monter">↑</button>
            <button type="button" id="lyDown" title="Descendre">↓</button>
            <button type="button" id="lyBack" title="Arriere-plan">⤓</button>
            <button type="button" id="lyDup" title="Dupliquer">⧉</button>
            <button type="button" id="lyDel" title="Supprimer">⌫</button>
          </div>
          <p class="ly-meta">Glisser ⠿ · double-clic pour renommer · <span class="kbd">Ctrl+L</span> verrouiller</p>
          <div id="layersList"></div>
          <div class="ly-controls" id="lyControls">
            <label>Opacite <input type="range" id="lyOpacity" min="10" max="100" value="100"><span id="lyOpacityVal">100%</span></label>
            <label>Fusion
              <select id="lyBlend">
                <option value="normal">Normal</option>
                <option value="multiply">Multiply</option>
                <option value="screen">Screen</option>
                <option value="overlay">Overlay</option>
              </select>
            </label>
          </div>
        </div>
      </div>
    </aside>

    <div class="ed-work">
      <div class="ed-toolbar">
        <button type="button" class="sbtn-tool" id="btnUndo" title="Annuler (Ctrl+Z)" disabled>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
        </button>
        <button type="button" class="sbtn-tool" id="btnRedo" title="Retablir" disabled>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
        </button>
        <span class="sep" style="width:1px;height:22px;background:var(--ukc-border);"></span>
        <button type="button" class="chip" id="btnBg">Background</button>
        <span class="label">Format:</span>
        <button type="button" class="chip active" data-size="a6-portrait" title="105 × 148 mm">A6 Portrait</button>
        <button type="button" class="chip" data-size="a6-landscape" title="148 × 105 mm">A6 Paysage</button>
        <button type="button" class="chip" data-size="a7-portrait" title="74 × 105 mm">A7 Portrait</button>
        <button type="button" class="chip" data-size="card-landscape" title="90 × 55 mm">Carte 90×55</button>
        <button type="button" class="chip" data-size="cr80" title="85,6 × 54 mm">CR80</button>
        <span class="spacer"></span>
        <div class="ed-zoom-bar">
          <button type="button" id="zoomOut">−</button>
          <span id="zoomLabel">100%</span>
          <button type="button" id="zoomIn">+</button>
          <button type="button" id="zoomReset" title="Ajuster">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3H4v4M16 3h4v4M8 21H4v-4M16 21h4v-4"/></svg>
          </button>
        </div>
        <button type="button" class="btn-preview" id="btnPreview">Preview</button>
      </div>
      <div class="ed-stylebar disabled" id="styleBar">
        <select id="fontFamily">
          <option value="Jost">Jost</option>
          <option value="Georgia">Georgia</option>
          <option value="'Playfair Display'">Playfair</option>
          <option value="Arial">Arial</option>
          <option value="Lato, sans-serif">Lato</option>
        </select>
        <select id="fontWeight">
          <option value="400">Regular</option>
          <option value="500">Medium</option>
          <option value="600">SemiBold</option>
          <option value="700">Bold</option>
          <option value="800">ExtraBold</option>
        </select>
        <button type="button" class="sbtn" id="fontSizeMinus" title="Taille -">−</button>
        <input type="number" id="fontSize" value="14" min="8" max="72" style="width:48px;">
        <button type="button" class="sbtn" id="fontSizePlus" title="Taille +">+</button>
        <span class="sep"></span>
        <input type="color" id="fontColor" value="#1a2332" title="Couleur">
        <button type="button" class="sbtn" data-style="bold" title="Gras (Ctrl+B)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.1 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
        </button>
        <button type="button" class="sbtn" data-style="italic" title="Italique (Ctrl+I)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4h-8z"/></svg>
        </button>
        <button type="button" class="sbtn" data-style="underline" title="Souligne (Ctrl+U)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
        </button>
        <span class="sep"></span>
        <button type="button" class="sbtn" data-align="left" title="Aligner a gauche">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" class="sbtn" data-align="center" title="Centrer">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" class="sbtn" data-align="right" title="Aligner a droite">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
        </button>
        <button type="button" class="sbtn" data-align="justify" title="Justifier">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18V7H3v2zm0-6v2h18V3H3z"/></svg>
        </button>
        <span class="sep"></span>
        <button type="button" class="sbtn danger" id="btnDelete" title="Supprimer (Del)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        </button>
      </div>
      <div class="ed-canvas-area">
        <div class="ed-canvas-inner" id="canvasInner">
          <div class="ed-page" id="page">
            <div class="ed-page-bg" id="pageBg"></div>
            <div id="guides" style="position:absolute;inset:0;pointer-events:none;z-index:99;"></div>
            <div class="ed-float-bar" id="floatBar">
              <button type="button" id="fbLock" title="Verrouiller (Ctrl+L)">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
              </button>
              <button type="button" id="fbUnlock" title="Deverrouiller" style="display:none;">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h1.9c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"/></svg>
              </button>
              <span class="sep"></span>
              <button type="button" id="fbDup" title="Dupliquer">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
              </button>
              <button type="button" id="fbDel" title="Supprimer">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="ed-modal-bg" id="bgModal">
    <div class="box">
      <div class="box-head">
        <span>Fond du badge</span>
        <button type="button" id="bgModalClose" style="font-size:22px;color:var(--ukc-muted);">×</button>
      </div>
      <div class="box-body">
        <div class="bg-grid">
          <div class="bg-opt active" data-bg="blue" style="background:linear-gradient(180deg,#223ea4 40%,#fff 40%);"><span>Bleu UK</span></div>
          <div class="bg-opt" data-bg="green" style="background:linear-gradient(180deg,#009933 40%,#fff 40%);"><span>Vert</span></div>
          <div class="bg-opt" data-bg="split" style="background:linear-gradient(160deg,#223ea4 40%,#009933 40%);"><span>UK split</span></div>
          <div class="bg-opt" data-bg="soft" style="background:linear-gradient(180deg,#eef2ff,#fff);"><span>Doux</span></div>
          <div class="bg-opt" data-bg="none" style="background:#fff;"><span>Blanc</span></div>
        </div>
        <div class="import-zone" id="bgImportZone">Importer une image de fond</div>
        <input type="file" id="bgFileInput" accept="image/*" style="display:none;">
      </div>
    </div>
  </div>

  <div class="ed-modal-bg" id="inputModal">
    <div class="box" style="max-width:420px;">
      <div class="box-head">
        <span id="inputModalTitle">URL</span>
        <button type="button" id="inputModalClose" style="font-size:22px;color:var(--ukc-muted);">×</button>
      </div>
      <div class="box-body">
        <p id="inputModalHint" style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;"></p>
        <input type="text" id="inputModalField" style="width:100%;border:1px solid var(--ukc-border);border-radius:8px;padding:10px 12px;font-size:14px;font-weight:600;">
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
          <button type="button" id="inputModalCancel" class="chip" style="padding:8px 14px;">Annuler</button>
          <button type="button" id="inputModalOk" class="btn-save" style="padding:8px 18px;">Valider</button>
        </div>
      </div>
    </div>
  </div>

  <div class="ed-toast" id="toast">Enregistre</div>

<script>
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
    if (false) window.open('badge-scan.html', '_blank');
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
</script>
</body>
@endverbatim
</html>
