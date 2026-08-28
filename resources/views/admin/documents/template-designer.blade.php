<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Éditeur de templates — UK Colloques</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php($ukcEditorCfg = ['editionId' => (string) $edition_id, 'kind' => $kind, 'type' => $type, 'typeLabel' => $typeLabel ?? 'Participation', 'backUrl' => route('admin.documents.attestations'), 'saveUrl' => route('admin.documents.templates.save'), 'csrf' => csrf_token(), 'editorState' => $editorState ?? null])
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
      display: flex; align-items: center; padding: 0 12px 0 8px; gap: 10px;
      flex-shrink: 0;
    }
    .ed-top .back {
      padding: 6px 10px; border-radius: 8px; font-size: 13px; font-weight: 600;
      color: var(--ukc-muted);
    }
    .ed-top .back:hover { background: var(--ukc-bg); color: var(--ukc-text); }
    .ed-top .crumb {
      font-size: 14px; font-weight: 700; flex: 1; min-width: 0;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
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
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0;
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
    .ed-shape .sq-o { width: 22px; height: 22px; border: 2px solid #111; border-radius: 3px; }
    .ed-shape .ci { width: 22px; height: 22px; background: #111; border-radius: 50%; }
    .ed-shape .ci-o { width: 22px; height: 22px; border: 2px solid #111; border-radius: 50%; }
    .ed-shape .ln-h { width: 28px; height: 2px; background: #111; }
    .ed-shape .ln-v { width: 2px; height: 28px; background: #111; }

    .ly-toolbar {
      display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px;
    }
    .ly-toolbar button {
      flex: 1; min-width: 32px; height: 32px; border-radius: 6px;
      border: 1px solid var(--ukc-border); font-size: 12px; font-weight: 700;
      color: var(--ukc-text); background: #fff;
    }
    .ly-toolbar button:hover:not(:disabled) { border-color: var(--ukc-blue); background: #eef2ff; color: var(--ukc-blue); }
    .ly-toolbar button:disabled { opacity: .35; cursor: not-allowed; }

    .ed-layer {
      display: grid;
      grid-template-columns: 18px 28px 1fr auto;
      align-items: center; gap: 6px;
      padding: 8px; border: 1px solid var(--ukc-border);
      border-radius: 8px; margin-bottom: 4px; font-size: 12.5px; font-weight: 600;
      background: #fff; cursor: pointer; user-select: none;
      transition: transform .2s ease, box-shadow .2s ease, border-color .15s, opacity .2s, background .15s;
    }
    .ed-layer:hover { border-color: #c7d2fe; }
    .ed-layer.active { border-color: var(--ukc-blue); background: #eef2ff; }
    .ed-layer.hidden-layer { opacity: .5; }
    .ed-layer.locked-layer { background: #f8fafc; }
    .ed-layer.dragging {
      opacity: .35; transform: scale(.98);
      box-shadow: 0 4px 12px rgba(34,62,164,.2);
    }
    .ed-layer.drag-over {
      border-color: var(--ukc-blue);
      box-shadow: 0 -3px 0 0 var(--ukc-blue);
    }
    .ed-layer .handle { color: var(--ukc-light); cursor: grab; font-size: 14px; text-align: center; }
    .ed-layer .handle:active { cursor: grabbing; }
    .ed-layer .ico-btn {
      width: 26px; height: 26px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      color: var(--ukc-muted); font-size: 13px;
    }
    .ed-layer .ico-btn:hover { background: #e2e8f0; color: var(--ukc-text); }
    .ed-layer .ico-btn.off { color: #cbd5e1; }
    .ed-layer .name {
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0;
    }
    .ed-layer .name input {
      width: 100%; border: 1px solid var(--ukc-blue); border-radius: 4px;
      padding: 2px 6px; font-size: 12px; font-weight: 600;
    }
    .ed-layer .actions { display: flex; gap: 2px; }

    /* Groups */
    .ed-group {
      border: 1px solid var(--ukc-border);
      border-radius: 10px; margin-bottom: 8px; overflow: hidden;
      background: #f8fafc;
      transition: box-shadow .2s, border-color .2s;
    }
    .ed-group.drag-over { border-color: var(--ukc-blue); box-shadow: 0 0 0 2px rgba(34,62,164,.2); }
    .ed-group-head {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 10px; font-size: 12.5px; font-weight: 800;
      cursor: pointer; background: #f1f5f9;
      border-bottom: 1px solid var(--ukc-border);
    }
    .ed-group-head .chev { font-size: 10px; color: var(--ukc-muted); width: 14px; }
    .ed-group.collapsed .ed-group-body { display: none; }
    .ed-group.collapsed .ed-group-head { border-bottom: none; }
    .ed-group-body { padding: 6px; }
    .ed-group-body .ed-layer { margin-bottom: 4px; }

    .ly-meta {
      font-size: 11px; color: var(--ukc-light); margin-bottom: 8px; line-height: 1.4;
    }
    .ly-controls {
      margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--ukc-border);
      display: none; flex-direction: column; gap: 10px;
    }
    .ly-controls.show { display: flex; }
    .ly-controls label {
      font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px;
    }
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
    }
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
      height: 72px; border-radius: 6px; margin-bottom: 6px;
      background: linear-gradient(135deg, #eef2ff, #f0fdf4);
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 10px; color: var(--ukc-blue);
    }

    .ed-work {
      display: flex; flex-direction: column; min-width: 0; min-height: 0; overflow: hidden;
    }
    .ed-toolbar {
      background: #fff; border-bottom: 1px solid var(--ukc-border);
      padding: 8px 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      flex-shrink: 0;
    }
    .ed-toolbar .label { font-size: 12px; font-weight: 700; color: var(--ukc-muted); }
    .ed-toolbar .chip {
      padding: 5px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700;
      border: 1px solid var(--ukc-border); background: #fff; color: var(--ukc-text);
    }
    .ed-toolbar .chip.active {
      background: #eef2ff; border-color: var(--ukc-blue); color: var(--ukc-blue);
    }
    .ed-toolbar .spacer { flex: 1; }
    .ed-toolbar .btn-preview {
      padding: 6px 12px; border: 1px solid var(--ukc-border); border-radius: 8px;
      font-size: 12.5px; font-weight: 700; color: var(--ukc-text);
    }

    /* Style bar: fixed height, never overlays canvas */
    .ed-stylebar {
      background: #fff; border-bottom: 1px solid var(--ukc-border);
      padding: 8px 14px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
      flex-shrink: 0; min-height: 48px;
    }
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
    .ed-stylebar .sbtn.danger:hover { background: #fef2f2; }
    .ed-stylebar .sbtn svg { width: 16px; height: 16px; }
    .ed-stylebar .sep {
      width: 1px; height: 22px; background: var(--ukc-border); margin: 0 4px;
    }
    .ed-stylebar input[type="color"] {
      width: 32px; height: 32px; border: 1px solid var(--ukc-border);
      border-radius: 6px; padding: 2px; background: #fff;
    }

    .ed-canvas-area {
      flex: 1; overflow: auto; background: #d1d5db;
      display: flex; align-items: flex-start; justify-content: center;
      padding: 28px; min-height: 0;
    }

    .ed-page {
      width: 900px; height: 636px;
      background: #f7f8fa; position: relative;
      /* box-shadow set with UK border colors below */
      overflow: hidden; flex-shrink: 0;
      transition: width .25s ease, height .25s ease;
    }
    .ed-page.portrait { width: 636px; height: 900px; }
    .ed-page {
      box-shadow: 0 16px 48px rgba(0,0,0,.18);
    }
    .ed-page-bg {
      position: absolute; inset: 0; pointer-events: none;
      background-color: #f3f4f6;
      background-image: url('/img/bg-attestation-uk.png');
      background-size: 100% 100%;
      background-repeat: no-repeat;
      background-position: center;
      border-radius: 12px;
    }
    .ed-page.bg-plain .ed-page-bg {
      background-image: url('/img/bg-attestation.png');
      background-color: #fff;
      border-radius: 0;
    }
    .ed-page.bg-bands .ed-page-bg {
      background-image:
        linear-gradient(135deg, transparent 70%, #223ea4 70%, #223ea4 78%, #009933 78%, #009933 88%, transparent 88%),
        linear-gradient(225deg, transparent 72%, #009933 72%, #009933 82%, #223ea4 82%, #223ea4 92%, transparent 92%);
      background-color: #f7f8fa;
      background-size: auto;
      border-radius: 0;
    }
    .ed-page.bg-none .ed-page-bg {
      background-image: none;
      background-color: #fff;
      border-radius: 0;
    }
    .ed-page.bg-simple .ed-page-bg {
      background-image: none;
      background: linear-gradient(180deg, #fff, #f8fafc);
      border-radius: 0;
    }
    .ed-page.bg-gold .ed-page-bg {
      background-image: radial-gradient(ellipse at 50% 0%, rgba(255,215,0,.25), transparent 55%);
      background-color: #fffcf5;
      border-radius: 0;
    }
    .ed-page.bg-custom .ed-page-bg {
      background-size: cover;
      background-position: center;
    }

    .ed-el {
      position: absolute; cursor: move; user-select: none;
      padding: 4px 6px; border: 1px solid transparent; min-width: 40px;
      transition: box-shadow .15s, border-color .15s;
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

    .ed-el.text { font-size: 14px; line-height: 1.45; color: var(--ukc-text); outline: none; }
    .ed-el.text.heading {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 28px; font-weight: 900; letter-spacing: .02em;
      text-transform: uppercase; text-align: center;
    }
    .ed-el.text.sub { font-size: 13px; color: #4b5563; }
    .ed-el.text.name { font-size: 22px; font-weight: 800; text-align: center; }
    .ed-el.text.attr {
      color: #3730a3; font-weight: 700;
      background: transparent; border-radius: 4px;
    }
    .ed-el.logo {
      font-size: 12px; font-weight: 900; color: var(--ukc-blue);
      display: flex; align-items: center; gap: 6px;
    }
    .ed-el.logo .mark {
      width: 36px; height: 36px; border-radius: 8px;
      background: linear-gradient(135deg, var(--ukc-blue), var(--ukc-green));
      color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px;
    }
    .ed-el.qr {
      width: 64px; height: 64px; background: #fff;
      border: 1px solid #d1d5db !important;
      display: flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 800; color: var(--ukc-muted);
    }
    .ed-el.shape-box { background: #111; border-radius: 4px; }

    .ed-toast {
      position: fixed; bottom: 20px; right: 20px;
      background: #0f172a; color: #fff; padding: 12px 16px;
      border-radius: 10px; font-size: 13px; font-weight: 600;
      display: none; z-index: 100; max-width: 320px;
    }
    .ed-toast.show { display: block; }
    .panel-view { display: none; height: 100%; flex-direction: column; min-height: 0; }
    .panel-view.active { display: flex; }


    /* Alignment guides */
    .ed-guide-x, .ed-guide-y {
      position: absolute; pointer-events: none; z-index: 100;
      background: #ec4899;
    }
    .ed-guide-x { height: 1px; left: 0; right: 0; }
    .ed-guide-y { width: 1px; top: 0; bottom: 0; }
    .ed-guide-label {
      position: absolute; pointer-events: none; z-index: 101;
      background: #ec4899; color: #fff; font-size: 10px; font-weight: 700;
      padding: 1px 5px; border-radius: 3px; white-space: nowrap;
    }

    /* Background modal */
    .ed-modal-bg {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(15,26,51,.5); align-items: center; justify-content: center;
      padding: 20px;
    }
    .ed-modal-bg.open { display: flex; }
    .ed-modal-bg .box {
      background: #fff; border-radius: 16px; width: 100%; max-width: 520px;
      max-height: 90vh; overflow: auto; box-shadow: 0 24px 60px rgba(0,0,0,.25);
    }
    .ed-modal-bg .box-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 18px; border-bottom: 1px solid var(--ukc-border);
      font-weight: 800; font-size: 15px;
    }
    .ed-modal-bg .box-body { padding: 16px 18px 20px; }
    .ed-modal-bg .bg-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;
    }
    .ed-modal-bg .bg-opt {
      border: 2px solid var(--ukc-border); border-radius: 10px;
      height: 72px; cursor: pointer; overflow: hidden; position: relative;
    }
    .ed-modal-bg .bg-opt:hover, .ed-modal-bg .bg-opt.active {
      border-color: var(--ukc-blue);
    }
    .ed-modal-bg .bg-opt span {
      position: absolute; bottom: 6px; left: 8px;
      font-size: 11px; font-weight: 700; background: rgba(255,255,255,.9);
      padding: 2px 6px; border-radius: 4px;
    }
    .ed-modal-bg .bg-default {
      background:
        linear-gradient(135deg, transparent 70%, #223ea4 70%, #223ea4 78%, #009933 78%, #009933 88%, transparent 88%),
        linear-gradient(225deg, transparent 72%, #009933 72%, #009933 82%, #223ea4 82%, #223ea4 92%, transparent 92%),
        #f7f8fa;
    }
    .ed-modal-bg .bg-simple { background: linear-gradient(180deg, #fff, #f0f3f9); }
    .ed-modal-bg .bg-gold {
      background: radial-gradient(ellipse at 50% 0%, rgba(255,215,0,.3), transparent 55%), #fffcf5;
    }
    .ed-modal-bg .bg-white { background: #fff; border-color: #d1d5db; }
    .ed-modal-bg .import-zone {
      border: 2px dashed var(--ukc-border); border-radius: 10px;
      padding: 20px; text-align: center; font-size: 13px; color: var(--ukc-muted);
      cursor: pointer; margin-top: 8px;
    }
    .ed-modal-bg .import-zone:hover { border-color: var(--ukc-blue); color: var(--ukc-blue); }

    .ed-toolbar .sbtn-tool {
      width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--ukc-border);
      display: inline-flex; align-items: center; justify-content: center;
      color: var(--ukc-text); background: #fff;
    }
    .ed-toolbar .sbtn-tool:hover:not(:disabled) { background: #f1f5f9; }
    .ed-toolbar .sbtn-tool:disabled { opacity: .35; cursor: not-allowed; }
    .ed-toolbar .sbtn-tool svg { width: 16px; height: 16px; }

    .ed-layer.swap-up {
      animation: lySwapUp .22s ease;
    }
    .ed-layer.swap-down {
      animation: lySwapDown .22s ease;
    }
    @keyframes lySwapUp {
      from { transform: translateY(8px); opacity: .7; }
      to { transform: none; opacity: 1; }
    }
    @keyframes lySwapDown {
      from { transform: translateY(-8px); opacity: .7; }
      to { transform: none; opacity: 1; }
    }


    .ed-zoom-bar {
      display: flex; align-items: center; gap: 6px;
      background: #fff; border: 1px solid var(--ukc-border);
      border-radius: 8px; padding: 2px 6px;
    }
    .ed-zoom-bar button {
      width: 28px; height: 28px; border-radius: 6px;
      font-weight: 800; font-size: 14px; color: var(--ukc-text);
    }
    .ed-zoom-bar button:hover { background: #f1f5f9; }
    .ed-zoom-bar span {
      font-size: 12px; font-weight: 700; min-width: 42px; text-align: center;
    }
    .ed-canvas-inner {
      transform-origin: center top;
      transition: transform .15s ease;
    }
    .ed-el.qr {
      background: #fff !important;
      padding: 4px !important;
      display: flex; align-items: center; justify-content: center;
    }
    .ed-el.qr img {
      width: 100%; height: 100%; object-fit: contain; display: block;
      pointer-events: none;
    }
    .ed-el.qr .qr-fallback {
      font-size: 9px; font-weight: 800; color: var(--ukc-muted); text-align: center;
    }
    .ed-thumb {
      position: relative; min-height: 56px;
      background-size: contain; background-repeat: no-repeat; background-position: center;
    }
    .ed-thumb[data-src] {
      color: transparent; font-size: 0;
    }
    .ed-thumb:hover { border-color: var(--ukc-blue); }


    .ed-el.text.attr {
      color: #3730a3; font-weight: 700;
      background: transparent;
      border-radius: 4px;
    }
    .ed-el.editing {
      cursor: text; user-select: text;
      border-color: var(--ukc-blue);
      box-shadow: 0 0 0 2px rgba(34,62,164,.25);
      background: transparent;
    }
    .ed-layer .ico-btn svg {
      width: 16px; height: 16px; display: block;
    }
    .ed-group {
      cursor: default;
    }
    .ed-group.dragging {
      opacity: .4;
    }
    .ed-group.drag-over-group {
      border-color: var(--ukc-blue);
      box-shadow: 0 -3px 0 0 var(--ukc-blue);
    }
    .ed-group-head .handle {
      color: var(--ukc-light); cursor: grab; font-size: 14px;
      margin-right: 2px;
    }


    .ed-stylebar.disabled {
      opacity: .55;
      pointer-events: none;
    }
    .ed-stylebar.disabled .sbtn,
    .ed-stylebar.disabled select,
    .ed-stylebar.disabled input {
      cursor: not-allowed;
    }
    .ai-box {
      border: 1px solid var(--ukc-border);
      border-radius: 12px;
      padding: 12px;
      background: #f8fafc;
      margin-bottom: 12px;
    }
    .ai-box textarea {
      width: 100%; min-height: 11em; height: 11em; resize: vertical;
      border: 1px solid var(--ukc-border); border-radius: 8px;
      padding: 10px; font-size: 13px; font-weight: 500;
      font-family: inherit; color: var(--ukc-text);
      background: #fff;
    }
    .ai-box textarea:focus {
      outline: none; border-color: var(--ukc-blue);
      box-shadow: 0 0 0 3px rgba(34,62,164,.12);
    }
    .ai-drop {
      border: 2px dashed var(--ukc-border); border-radius: 10px;
      padding: 14px; text-align: center; font-size: 12.5px;
      color: var(--ukc-muted); cursor: pointer; background: #fff;
      margin-top: 10px; transition: .15s;
    }
    .ai-drop:hover, .ai-drop.dragover {
      border-color: var(--ukc-blue); color: var(--ukc-blue);
      background: #eef2ff;
    }
    .ai-file {
      display: flex; align-items: center; gap: 8px;
      margin-top: 8px; padding: 8px 10px;
      background: #fff; border: 1px solid var(--ukc-border);
      border-radius: 8px; font-size: 12px; font-weight: 600;
    }
    .ai-file .rm {
      margin-left: auto; color: #991b1b; font-weight: 800;
      cursor: pointer; padding: 2px 6px;
    }
    .ai-run {
      width: 100%; margin-top: 12px;
      background: linear-gradient(135deg, #223ea4, #009933);
      color: #fff; font-weight: 800; font-size: 13.5px;
      padding: 12px 14px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .ai-run:hover { filter: brightness(1.05); }
    .ai-run:disabled { opacity: .6; cursor: wait; }
    .ai-tips {
      font-size: 11.5px; color: var(--ukc-muted); line-height: 1.45;
      margin-top: 10px;
    }
    .ai-tips strong { color: var(--ukc-text); }


    .ed-float-bar {
      position: absolute;
      z-index: 120;
      display: none;
      align-items: center;
      gap: 2px;
      background: #1e293b;
      color: #fff;
      border-radius: 8px;
      padding: 4px;
      box-shadow: 0 8px 24px rgba(0,0,0,.25);
      transform: translateX(-50%);
      white-space: nowrap;
    }
    .ed-float-bar.show { display: flex; }
    .ed-float-bar button {
      width: 32px; height: 32px; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      color: #e2e8f0;
    }
    .ed-float-bar button:hover { background: rgba(255,255,255,.12); color: #fff; }
    .ed-float-bar button.active { background: rgba(34,62,164,.9); color: #fff; }
    .ed-float-bar button svg { width: 16px; height: 16px; }
    .ed-float-bar .sep {
      width: 1px; height: 18px; background: rgba(255,255,255,.2); margin: 0 2px;
    }
    .ed-guide-label {
      position: absolute; pointer-events: none; z-index: 101;
      background: #ec4899; color: #fff;
      font-size: 10px; font-weight: 700;
      padding: 1px 5px; border-radius: 3px;
      white-space: nowrap; line-height: 1.3;
    }
    .ed-margin-label {
      position: absolute; pointer-events: none; z-index: 102;
      background: #0ea5e9; color: #fff;
      font-size: 10px; font-weight: 800;
      padding: 2px 6px; border-radius: 4px;
      white-space: nowrap;
      box-shadow: 0 2px 6px rgba(0,0,0,.15);
    }
    .ed-margin-line {
      position: absolute; pointer-events: none; z-index: 100;
      background: #0ea5e9;
    }

    .kbd {
      display: inline-block; font-size: 10px; font-weight: 700;
      background: #e2e8f0; color: #475569; padding: 1px 5px;
      border-radius: 4px; margin-left: 4px;
    }
  </style>
</head>
<body>

  <header class="ed-top">
    <button type="button" class="back" onclick="location.href=(window.UKC_EDITOR&&window.UKC_EDITOR.backUrl)||'/admin/attestations'">← Documents</button>
    <div class="crumb">Attestation de Formation CSIUK25</div>
    <input class="name-input" id="tplName" value="Attestation de Formation CSIUK25">
    <button type="button" class="btn-save" id="btnSave">Save</button>
  </header>

  <div class="ed-shell">
    <nav class="ed-rail">
      <button type="button" data-panel="ai" title="Assistant IA">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2zM7.5 13a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm9 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/></svg>
        IA
      </button>

      <button type="button" data-panel="templates" title="Templates">
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
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 3h2v2h-2v-3h-2v2h2v1zm3 3h3v3h-3v-3zm-3 0h2v2h-2v-2z"/></svg>
        QR Codes
      </button>
      <button type="button" class="active" data-panel="layers" title="Layers">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 9L2 7v2l10 5 10-5V7l-10 4zm0 4L2 11v2l10 5 10-5v-2l-10 4z"/></svg>
        Layers
      </button>
    </nav>

    <aside class="ed-panel">
      <div class="panel-view" id="panel-templates">
        <div class="ed-panel-head">Modeles de design</div>
        <div class="ed-panel-body">
          <p class="ly-meta" style="margin-bottom:12px;">Bases inspirees des attestations CSIUK / For-VaRRITO. Choisissez un design, puis personnalisez les elements.</p>
          <div class="ed-panel-section">Colloque CSIUK (UK · Parakou)</div>
          <div class="ed-tpl-card active" data-tpl="participation">
            <div class="mini">PARTICIPATION</div>
            Logos UK/UP · theme · signature president
          </div>
          <div class="ed-tpl-card" data-tpl="communication">
            <div class="mini">COMMUNICATION</div>
            Titre + type de communication · theme
          </div>
          <div class="ed-tpl-card" data-tpl="moderation">
            <div class="mini">MODERATION</div>
            Session moderee · theme colloque
          </div>
          <div class="ed-tpl-card" data-tpl="formation-csiuk">
            <div class="mini">FORMATION CSIUK</div>
            Fond coins UK · formation en marge du colloque
          </div>
          <div class="ed-panel-section">Projet For-VaRRITO</div>
          <div class="ed-tpl-card" data-tpl="formation-forvarrito">
            <div class="mini">FORMATION FOR-VaRRITO</div>
            Bordure motifs · logos multi-partenaires
          </div>
          <div class="ed-panel-section">Nouveau</div>
          <button type="button" class="ed-tool-btn" id="btnBlank">+ Design vierge (A4 paysage)</button>
        </div>
      </div>

      <div class="panel-view" id="panel-uploads">
        <div class="ed-panel-head">Uploads</div>
        <div class="ed-panel-body">
          <div class="ed-upload" id="btnUploadImg">Upload Image<br><span style="font-size:11px;">SVG, PNG, JPG · max 2 Mo</span></div>
          <input type="file" id="fileUpload" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display:none;">
          <div class="ed-panel-section">Images ajoutees</div>
          <div class="ed-grid-2" id="uploadList">
            <div class="ed-thumb" data-logo="uk">UK · Univ. Kara</div>
            <div class="ed-thumb" data-logo="up">Univ. Parakou</div>
          </div>
          <p class="ly-meta" style="margin-top:10px;">Cliquez une image pour l'ajouter sur le canvas. L'arriere-plan se change via <strong>Add Background Image</strong>.</p>
        </div>
      </div>

      <div class="panel-view" id="panel-elements">
        <div class="ed-panel-head">Elements</div>
        <div class="ed-panel-body">
          <div class="ed-panel-section">Lignes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-shape" data-add="line-h"><div class="ln-h"></div></button>
            <button type="button" class="ed-shape" data-add="line-v"><div class="ln-v"></div></button>
          </div>
          <div class="ed-panel-section">Formes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-shape" data-add="rect"><div class="sq"></div></button>
            <button type="button" class="ed-shape" data-add="rect-o"><div class="sq-o"></div></button>
            <button type="button" class="ed-shape" data-add="circle"><div class="ci"></div></button>
            <button type="button" class="ed-shape" data-add="circle-o"><div class="ci-o"></div></button>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-text">
        <div class="ed-panel-head">Text</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="textbox">+ Add text box</button>
          <div class="ed-panel-section">Text Styles</div>
          <button type="button" class="ed-tool-btn" data-add="heading" style="font-size:18px;font-family:Georgia,serif;">Add heading</button>
          <button type="button" class="ed-tool-btn" data-add="subheading">Add subheading</button>
          <button type="button" class="ed-tool-btn" data-add="normal">Add normal text</button>
          <p class="ly-meta" style="margin-top:12px;">Double-clic sur un texte du canvas pour l'editer. <span class="kbd">Enter</span> valide, <span class="kbd">Esc</span> annule.</p>
        </div>
      </div>

      <div class="panel-view" id="panel-attributes">
        <div class="ed-panel-head">Attributes (champs)</div>
        <div class="ed-panel-body">
          <p style="font-size:12px;color:var(--ukc-muted);margin-bottom:10px;">Cliquer pour ajouter sur le canvas</p>
          <div class="ed-attr" data-attr="[recipient.name]">[recipient.name]</div>
          <div class="ed-attr" data-attr="[recipient.institution]">[recipient.institution]</div>
          <div class="ed-attr" data-attr="[certificate.uuid]">[certificate.uuid]</div>
          <div class="ed-attr" data-attr="[certificate.issued_on]">[certificate.issued_on]</div>
          <div class="ed-attr" data-attr="[certificate.reference]">[certificate.reference]</div>
          <div class="ed-attr" data-attr="[communication.title]">[communication.title]</div>
          <div class="ed-attr" data-attr="[communication.type]">[communication.type]</div>
          <div class="ed-attr" data-attr="[edition.name]">[edition.name]</div>
          <div class="ed-attr" data-attr="[edition.dates]">[edition.dates]</div>
          <div class="ed-attr" data-attr="[edition.theme]">[edition.theme]</div>
          <div class="ed-attr" data-attr="[signatory.name]">[signatory.name]</div>
          <div class="ed-attr" data-attr="[signatory.role]">[signatory.role]</div>
        </div>
      </div>

      <div class="panel-view" id="panel-qrcodes">
        <div class="ed-panel-head">QR Codes</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="qr-verify">+ Verification Page</button>
          <p class="ly-meta">Redirige vers la page publique de verification de l'attestation.</p>
          <button type="button" class="ed-tool-btn" data-add="qr-custom">+ Custom URL</button>
          <p class="ly-meta">Redirige vers une URL personnalisee.</p>
        </div>
      </div>

      <div class="panel-view active" id="panel-layers">
        <div class="ed-panel-head">
          <span>Layers</span>
          <span style="font-size:11px;font-weight:600;color:var(--ukc-light);" id="lyCount">0</span>
        </div>
        <div class="ed-panel-body">
          <div class="ly-toolbar">
            <button type="button" id="lyFront" title="Premier plan (Ctrl+])">⤒</button>
            <button type="button" id="lyUp" title="Monter (Ctrl+Shift+])">↑</button>
            <button type="button" id="lyDown" title="Descendre (Ctrl+Shift+[)">↓</button>
            <button type="button" id="lyBack" title="Arriere-plan (Ctrl+[)">⤓</button>
            <button type="button" id="lyDup" title="Dupliquer (Ctrl+D)">⧉</button>
            <button type="button" id="lyGroup" title="Grouper la selection">⊞</button>
            <button type="button" id="lyDel" title="Supprimer (Del)">⌫</button>
          </div>
          <p class="ly-meta">Glisser ⠿ pour reordonner · double-clic nom pour renommer · <span class="kbd">Ctrl+G</span> grouper</p>
          <div id="layersList"></div>
          <div class="ly-controls" id="lyControls">
            <label>
              Opacite
              <input type="range" id="lyOpacity" min="10" max="100" value="100">
              <span id="lyOpacityVal">100%</span>
            </label>
            <label>
              Fusion
              <select id="lyBlend">
                <option value="normal">Normal</option>
                <option value="multiply">Multiply</option>
                <option value="screen">Screen</option>
                <option value="overlay">Overlay</option>
                <option value="darken">Darken</option>
                <option value="lighten">Lighten</option>
                <option value="color-dodge">Color dodge</option>
                <option value="color-burn">Color burn</option>
                <option value="hard-light">Hard light</option>
                <option value="soft-light">Soft light</option>
                <option value="difference">Difference</option>
                <option value="exclusion">Exclusion</option>
              </select>
            </label>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-ai">
        <div class="ed-panel-head">Assistant IA</div>
        <div class="ed-panel-body">
          <p class="ly-meta" style="margin-bottom:10px;">Decrivez le modele souhaite ou importez une attestation existante. L IA propose une mise en page editable sur le canvas.</p>
          <div class="ai-box">
            <label style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--ukc-light);display:block;margin-bottom:6px;">Prompt</label>
            <textarea id="aiPrompt" rows="8" placeholder="Ex. : Attestation de participation au colloque CSIUK, logos UK et UP en haut, titre centré, nom du participant en grand, QR de verification en bas a gauche, signature du president a droite..."></textarea>
            <div class="ai-drop" id="aiDrop">
              Deposer une image, PDF ou Word<br>
              <span style="font-size:11px;">PNG, JPG, PDF, DOC, DOCX · max 8 Mo</span>
            </div>
            <input type="file" id="aiFile" accept="image/*,.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none;">
            <div id="aiFileList"></div>
            <button type="button" class="ai-run" id="aiRun">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/></svg>
              Generer
            </button>
          </div>
          <div class="ai-tips">
            <strong>Astuces</strong><br>
            • Precisez le type (participation, formation, communication…)<br>
            • Indiquez logos, QR, signatures, champs dynamiques<br>
            • Une piece jointe sert de reference visuelle a l IA
          </div>
        </div>
      </div>

    </aside>

    <div class="ed-work">
      <div class="ed-toolbar">
        <button type="button" class="sbtn-tool" id="btnUndo" title="Annuler (Ctrl+Z)" disabled>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
        </button>
        <button type="button" class="sbtn-tool" id="btnRedo" title="Retablir (Ctrl+Shift+Z)" disabled>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
        </button>
        <span class="sep" style="width:1px;height:22px;background:var(--ukc-border);margin:0 4px;"></span>
        <button type="button" class="chip" id="btnBg">Add Background Image</button>
        <span class="label">Paper Size:</span>
        <button type="button" class="chip active" data-paper="landscape">A4 Paysage</button>
        <button type="button" class="chip" data-paper="portrait">A4 Portrait</button>
        <button type="button" class="chip" data-paper="letter">US Letter</button>
        <span class="spacer"></span>
        <div class="ed-zoom-bar">
          <button type="button" id="zoomOut" title="Zoom -">−</button>
          <span id="zoomLabel">100%</span>
          <button type="button" id="zoomIn" title="Zoom +">+</button>
          <button type="button" id="zoomReset" title="Ajuster a la vue" style="width:32px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M8 3H4v4M16 3h4v4M8 21H4v-4M16 21h4v-4"/></svg>
          </button>
        </div>
        <button type="button" class="btn-preview" id="btnPreview">Preview</button>
      </div>
      <div class="ed-stylebar" id="styleBar">
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
              <button type="button" id="fbLock" title="Verrouiller / deverrouiller (Ctrl+L)">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
              </button>
              <button type="button" id="fbUnlock" title="Deverrouiller" style="display:none;">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h1.9c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"/></svg>
              </button>
              <span class="sep"></span>
              <button type="button" id="fbDup" title="Dupliquer (Ctrl+D)">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
              </button>
              <button type="button" id="fbDel" title="Supprimer (Del)">
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
        <span>Arriere-plan de l'attestation</span>
        <button type="button" id="bgModalClose" style="font-size:22px;color:var(--ukc-muted);">×</button>
      </div>
      <div class="box-body">
        <div class="ed-panel-section" style="margin-top:0;">Propositions</div>
        <div class="bg-grid">
          <div class="bg-opt active" data-bg="default" style="background:#f3f4f6 url('/img/bg-attestation-uk.png') center/100% 100% no-repeat;"><span>UK couleurs</span></div>
          <div class="bg-opt" data-bg="plain" style="background:#fff url('/img/bg-attestation.png') center/100% 100% no-repeat;"><span>Cadre gris</span></div>
          <div class="bg-opt bg-default" data-bg="bands"><span>Bandes UK</span></div>
          <div class="bg-opt bg-simple" data-bg="simple"><span>Simple</span></div>
          <div class="bg-opt bg-gold" data-bg="gold"><span>Or discret</span></div>
          <div class="bg-opt bg-white" data-bg="none"><span>Blanc</span></div>
        </div>
        <div class="ed-panel-section">Importer une image</div>
        <div class="import-zone" id="bgImportZone">
          Cliquez ou deposez une image (PNG, JPG, SVG)<br>
          <span style="font-size:11px;">Sera etiree en fond de page</span>
        </div>
        <input type="file" id="bgFileInput" accept="image/*" style="display:none;">
      </div>
    </div>
  </div>

  <div class="ed-modal-bg" id="inputModal">
    <div class="box" style="max-width:420px;">
      <div class="box-head">
        <span id="inputModalTitle">Saisir une valeur</span>
        <button type="button" id="inputModalClose" style="font-size:22px;color:var(--ukc-muted);">×</button>
      </div>
      <div class="box-body">
        <p id="inputModalHint" style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;line-height:1.4;"></p>
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
/* UKC editor v2 — syntax verified */
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
  var SNAP = 6;
  var DEFAULT_QR_URL = 'https://univkara.tg/attestation-verify';
  var zoomLevel = 1;
  var uploadRegistry = {}; // id -> { src, name, elIds: [] }

  function qrImageUrl(data, size) {
    size = size || 120;
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&margin=4&data=' + encodeURIComponent(data || DEFAULT_QR_URL);
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
    { type: 'logo', id: 'logo-uk', label: 'Logo UK', x: 40, y: 28, w: 160, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
    { type: 'logo', id: 'logo-up', label: 'Logo UP', x: 680, y: 28, w: 180, html: 'UNIVERSITE DE PARAKOU' },
    { type: 'text', id: 'title', label: 'Titre', x: 80, y: 100, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
    { type: 'text', id: 'intro', label: 'Intro', x: 100, y: 160, w: 700, cls: 'sub', text: 'Je soussigne, Prof. Padabo KADOUZA, President du Comite d Organisation, atteste que' },
    { type: 'text', id: 'name', label: 'Destinataire', x: 150, y: 210, w: 600, cls: 'name attr', text: '[recipient.name]' },
    { type: 'text', id: 'body', label: 'Corps', x: 80, y: 270, w: 740, cls: 'sub', text: 'a suivi avec diligence la formation sur la valorisation des resultats de recherche, tenue en marge du 6e colloque scientifique international de l Universite de Kara, en collaboration avec l Universite de Parakou (06 – 10 octobre 2025, Kara).' },
    { type: 'text', id: 'closing', label: 'Formule', x: 80, y: 380, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
    { type: 'text', id: 'place', label: 'Lieu / date', x: 520, y: 450, w: 320, cls: 'sub', text: 'Fait a Kara, le 10 octobre 2025' },
    { type: 'text', id: 'role', label: 'Fonction', x: 520, y: 490, w: 320, cls: 'sub', text: 'Le President du Comite d Organisation,' },
    { type: 'text', id: 'sign', label: 'Signataire', x: 520, y: 530, w: 320, cls: '', text: 'Prof. Padabo KADOUZA' },
    { type: 'text', id: 'ref', label: 'Reference', x: 40, y: 580, w: 420, cls: 'sub', text: 'Reference : [certificate.uuid]' },
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
      Object.keys(s.style || {}).forEach(function (k) {
        if (s.style[k]) el.style[k] = s.style[k];
      });
      if (!el.querySelector('.resize')) {
        var rz = document.createElement('div');
        rz.className = 'resize';
        el.appendChild(rz);
      }
      page.appendChild(el);
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
    toast('Annule');
  }
  function redo() {
    if (historyIndex >= history.length - 1) return;
    historyIndex++;
    restoreSnapshot(history[historyIndex]);
    toast('Retabli');
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
    } else {
      el.classList.add('text');
      if (cfg.cls) cfg.cls.split(/\s+/).forEach(function (c) { if (c) el.classList.add(c); });
      el.textContent = cfg.text || 'Texte';
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
        toast('Calque verrouille');
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
          title: 'URL du QR code',
          hint: 'Lien ouvert lors du scan de ce QR code.',
          value: cur,
          placeholder: 'https://...',
          onOk: function (url) {
            if (!url) return;
            setQrContent(el, url);
            pushHistory();
            toast('QR mis a jour');
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
        toast('Groupe reordonne');
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
      // dataTransfer.getData may be empty in dragover in some browsers — use global
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
      toast('Ordre des calques mis a jour');
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
    toast(lock ? 'Calque verrouille — non deplacable ni editable' : 'Calque deverrouille');
  }

  function moveLayer(dir) {
    if (!selected) return;
    if (isLocked(selected)) { toast('Calque verrouille'); return; }
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
    toast('Calque duplique');
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
    toast('Groupe cree — glissez d autres calques dessus');
  }

  function deleteSelected() {
    if (!selected) return;
    if (isLocked(selected)) {
      toast('Calque verrouille');
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
  });
  document.getElementById('lyBlend').addEventListener('change', function () {
    if (!selected) return;
    selected.dataset.blend = this.value;
    selected.style.mixBlendMode = this.value;
    toast('Mode de fusion : ' + this.value);
  });

  // Charge le modele Participation par defaut (apres definition de loadTemplate — appele plus bas si besoin)
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
      var kind = opt.dataset.bg;
      if (kind === 'default') {
        bgEl.style.backgroundImage = "url('/img/bg-attestation-uk.png')";
        bgEl.style.backgroundSize = '100% 100%';
        bgEl.style.backgroundPosition = 'center';
      } else if (kind === 'plain') {
        page.classList.add('bg-plain');
        bgEl.style.backgroundImage = "url('/img/bg-attestation.png')";
        bgEl.style.backgroundSize = '100% 100%';
        bgEl.style.backgroundPosition = 'center';
      } else if (kind === 'bands') {
        page.classList.add('bg-bands');
      } else if (kind === 'none') {
        page.classList.add('bg-none');
      } else if (kind === 'simple') {
        page.classList.add('bg-simple');
      } else if (kind === 'gold') {
        page.classList.add('bg-gold');
      }
      pushHistory();
      toast('Arriere-plan applique');
      bgModal.classList.remove('open');
    });
  });
  document.getElementById('bgImportZone').addEventListener('click', function () {
    document.getElementById('bgFileInput').click();
  });
  document.getElementById('bgFileInput').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () {
      page.classList.remove('bg-none', 'bg-simple', 'bg-gold', 'bg-bands');
      page.classList.add('bg-custom');
      var bgEl = document.getElementById('pageBg');
      bgEl.style.backgroundImage = 'url(' + reader.result + ')';
      bgEl.style.backgroundSize = 'cover';
      bgEl.style.backgroundPosition = 'center';
      pushHistory();
      toast('Image de fond importee');
      bgModal.classList.remove('open');
    };
    reader.readAsDataURL(file);
  });

  /* Uploads only images */
  document.getElementById('btnUploadImg').addEventListener('click', function () {
    document.getElementById('fileUpload').click();
  });
  document.getElementById('fileUpload').addEventListener('change', function (e) {
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () {
      var thumb = document.createElement('div');
      thumb.className = 'ed-thumb';
      thumb.title = file.name;
      thumb.style.backgroundImage = 'url(' + reader.result + ')';
      thumb.dataset.src = reader.result;
      thumb.dataset.wasUsed = '0';
      thumb.addEventListener('click', function () {
        var el = createEl({
          type: 'logo',
          label: file.name.slice(0, 20),
          x: 100, y: 100, w: 120,
          uploadSrc: reader.result,
          html: '<img src="' + reader.result + '" alt="" style="max-width:100%;max-height:80px;display:block;">'
        });
        thumb.dataset.wasUsed = '1';
        selectEl(el);
        pushHistory();
      });
      document.getElementById('uploadList').appendChild(thumb);
      toast('Image ajoutee');
    };
    reader.readAsDataURL(file);
    e.target.value = '';
  });
  document.querySelectorAll('#uploadList .ed-thumb').forEach(function (th) {
    th.addEventListener('click', function () {
      var label = th.dataset.logo === 'up' ? 'Logo UP' : 'Logo UK';
      var html = th.dataset.logo === 'up'
        ? 'UNIVERSITE DE PARAKOU'
        : '<div class="mark">UK</div> UNIVERSITE DE KARA';
      selectEl(createEl({ type: 'logo', label: label, x: 80, y: 80, w: 160, html: html }));
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
          title: 'QR code personnalise',
          hint: 'Indiquez l URL qui sera encodee dans le QR code.',
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
    if (selected && !isLocked(selected)) selected.style.fontSize = this.value + 'px';
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
    if (selected && !isLocked(selected)) selected.style.fontFamily = this.value;
  });
  document.getElementById('fontWeight').addEventListener('change', function () {
    if (selected && !isLocked(selected)) selected.style.fontWeight = this.value;
  });
  document.getElementById('fontColor').addEventListener('input', function () {
    if (selected && !isLocked(selected)) selected.style.color = this.value;
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
    });
  });
  document.querySelectorAll('[data-align]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!selected || isLocked(selected)) return;
      selected.style.textAlign = btn.dataset.align;
      document.querySelectorAll('[data-align]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
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
    var W = Math.max(120, Math.round(rect.width)), H = Math.max(120, Math.round(rect.height));
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
        return { type: 'qr', x: x, y: y, size: Math.max(w, h) || 96, value: el.dataset.qrUrl || '{verify_url}' };
      }
      if (img && /^https?:/i.test(img.src)) {
        return { type: 'image', x: x, y: y, w: w, h: h, src: img.src };
      }
      if (!text && (/\b(shape|rect|line|ln-|sq)\b/i.test(cls) || el.style.background || cs.backgroundColor !== 'rgba(0, 0, 0, 0)')) {
        return { type: 'rect', x: x, y: y, w: w, h: h, color: rgbToHex(cs.backgroundColor) || '#223ea4' };
      }
      var align = cs.textAlign;
      return {
        type: 'text', x: x, y: y, w: w, h: h,
        text: text.slice(0, 500),
        size: Math.round(parseFloat(cs.fontSize) || 16),
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
      body: JSON.stringify({ edition_id: cfg.editionId || '', kind: cfg.kind || 'attestation', type: cfg.type || 'participation', layout: serializeLayout(), editor_state: snapshot() })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j && res.j.ok) toast('Template « ' + document.getElementById('tplName').value + ' » enregistré');
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

    var pw = page.offsetWidth || 900, ph = page.offsetHeight || 636;
    var scale = Math.min(1, (window.innerWidth - 80) / pw, (window.innerHeight - 120) / ph);

    var clone = page.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.transform = 'none';
    clone.style.margin = '0';
    clone.style.boxShadow = '0 20px 60px rgba(0,0,0,.5)';
    clone.querySelectorAll('.resize, .ed-guide, .ed-guide-label').forEach(function (n) { n.remove(); });
    clone.querySelectorAll('.ed-el').forEach(function (n) {
      n.style.outline = 'none'; n.style.border = 'none'; n.style.boxShadow = 'none';
      n.classList.remove('selected');
    });

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
  document.getElementById('btnBlank').addEventListener('click', function () {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = {};
    selected = null;
    refreshLayers();
    toast('Design vierge — A4 paysage');
  });
  function clearCanvas() {
    page.querySelectorAll('.ed-el').forEach(function (e) { e.remove(); });
    groups = {};
    window.__ukcGroupOrder = [];
    selected = null;
    styleBar.classList.add('disabled');
  }

  function loadTemplate(key) {
    clearCanvas();
    var T = {
      participation: [
        { type: 'logo', label: 'Logo UK', x: 48, y: 28, w: 170, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
        { type: 'logo', label: 'Logo UP', x: 680, y: 28, w: 180, html: 'UNIVERSITE DE PARAKOU' },
        { type: 'text', label: 'Titre', x: 100, y: 100, w: 700, cls: 'heading', text: 'ATTESTATION DE PARTICIPATION' },
        { type: 'text', label: 'Sous-titre', x: 100, y: 148, w: 700, cls: 'sub', text: 'Decernee a' },
        { type: 'text', label: 'Destinataire', x: 100, y: 178, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 240, w: 740, cls: 'sub', text: 'pour avoir participe au Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' },
        { type: 'text', label: 'Theme label', x: 80, y: 320, w: 160, cls: 'sub', text: 'Theme du colloque :' },
        { type: 'text', label: 'Theme', x: 240, y: 320, w: 580, cls: 'sub attr', text: '[edition.theme]' },
        { type: 'text', label: 'Formule', x: 80, y: 390, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 520, y: 450, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 490, w: 320, cls: 'sub', text: 'Le President du comite d organisation' },
        { type: 'text', label: 'Signataire', x: 520, y: 540, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 64, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Identifiant', x: 48, y: 580, w: 500, cls: 'sub', text: 'Identifiant : [certificate.uuid]' }
      ],
      communication: [
        { type: 'logo', label: 'Logo UK', x: 48, y: 24, w: 170, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
        { type: 'logo', label: 'Logo UP', x: 680, y: 24, w: 180, html: 'UNIVERSITE DE PARAKOU' },
        { type: 'text', label: 'Titre', x: 80, y: 90, w: 740, cls: 'heading', text: 'ATTESTATION DE COMMUNICATION' },
        { type: 'text', label: 'Sous-titre', x: 100, y: 138, w: 700, cls: 'sub', text: 'Decernee a' },
        { type: 'text', label: 'Destinataire', x: 100, y: 168, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 220, w: 740, cls: 'sub', text: 'pour avoir participe et communique au Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' },
        { type: 'text', label: 'Theme label', x: 80, y: 290, w: 180, cls: 'sub', text: 'Theme du colloque :' },
        { type: 'text', label: 'Theme', x: 260, y: 290, w: 560, cls: 'sub attr', text: '[edition.theme]' },
        { type: 'text', label: 'Titre com. label', x: 80, y: 340, w: 200, cls: 'sub', text: 'Titre de la communication :' },
        { type: 'text', label: 'Titre com.', x: 280, y: 340, w: 540, cls: 'sub attr', text: '[communication.title]' },
        { type: 'text', label: 'Type label', x: 80, y: 400, w: 180, cls: 'sub', text: 'Type de communication :' },
        { type: 'text', label: 'Type', x: 270, y: 400, w: 200, cls: 'sub attr', text: '[communication.type]' },
        { type: 'text', label: 'Formule', x: 80, y: 450, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 520, y: 500, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 530, w: 320, cls: 'sub', text: 'Le President du comite d organisation' },
        { type: 'text', label: 'Signataire', x: 520, y: 570, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 520, w: 56, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Identifiant', x: 48, y: 590, w: 450, cls: 'sub', text: 'Identifiant : [certificate.uuid]' }
      ],
      moderation: [
        { type: 'logo', label: 'Logo UK', x: 48, y: 28, w: 170, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
        { type: 'logo', label: 'Logo UP', x: 680, y: 28, w: 180, html: 'UNIVERSITE DE PARAKOU' },
        { type: 'text', label: 'Titre', x: 100, y: 100, w: 700, cls: 'heading', text: 'ATTESTATION DE MODERATION' },
        { type: 'text', label: 'Sous-titre', x: 100, y: 148, w: 700, cls: 'sub', text: 'Decernee a' },
        { type: 'text', label: 'Destinataire', x: 100, y: 178, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 240, w: 740, cls: 'sub', text: 'pour avoir modere une session lors du Colloque Scientifique International de l Universite de Kara, en collaboration avec l Universite de Parakou, du 06 au 10 octobre 2025 au palais des congres de Kara, au Togo.' },
        { type: 'text', label: 'Theme label', x: 80, y: 330, w: 160, cls: 'sub', text: 'Theme du colloque :' },
        { type: 'text', label: 'Theme', x: 240, y: 330, w: 580, cls: 'sub attr', text: '[edition.theme]' },
        { type: 'text', label: 'Formule', x: 80, y: 400, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 520, y: 460, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 500, w: 320, cls: 'sub', text: 'Le President du comite d organisation' },
        { type: 'text', label: 'Signataire', x: 520, y: 550, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 64, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Identifiant', x: 48, y: 580, w: 500, cls: 'sub', text: 'Identifiant : [certificate.uuid]' }
      ],
      'formation-csiuk': [
        { type: 'logo', label: 'Logo UK', x: 48, y: 32, w: 170, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
        { type: 'logo', label: 'Logo UP', x: 680, y: 32, w: 180, html: 'UNIVERSITE DE PARAKOU' },
        { type: 'text', label: 'Titre', x: 80, y: 110, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
        { type: 'text', label: 'Intro', x: 80, y: 170, w: 740, cls: 'sub', text: 'Je soussigne, [signatory.name], President du Comite d Organisation, atteste que' },
        { type: 'text', label: 'Destinataire', x: 100, y: 220, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 280, w: 740, cls: 'sub', text: 'a suivi avec diligence la formation sur la valorisation des resultats de recherche, tenue en marge des travaux du colloque scientifique international de l Universite de Kara, en collaboration avec l Universite de Parakou ([edition.dates]), sur le theme : [edition.theme].' },
        { type: 'text', label: 'Formule', x: 80, y: 390, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 520, y: 460, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 500, w: 320, cls: 'sub', text: 'Le President du Comite d Organisation,' },
        { type: 'text', label: 'Signataire', x: 520, y: 550, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 64, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Reference', x: 48, y: 580, w: 480, cls: 'sub', text: 'Reference : [certificate.uuid]' }
      ],
      'formation-forvarrito': [
        { type: 'logo', label: 'Logo For-VaRRITO', x: 40, y: 24, w: 200, html: 'For-VaRRITO' },
        { type: 'logo', label: 'Logo UL', x: 420, y: 28, w: 140, html: 'Univ. Lome' },
        { type: 'logo', label: 'Logo UK', x: 700, y: 28, w: 160, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' },
        { type: 'text', label: 'Titre', x: 80, y: 100, w: 740, cls: 'heading', text: 'ATTESTATION DE FORMATION' },
        { type: 'text', label: 'Intro', x: 80, y: 160, w: 740, cls: 'sub', text: 'Je soussigne, [signatory.name], Coordinateur du projet For-VaRRITO, atteste que' },
        { type: 'text', label: 'Destinataire', x: 100, y: 220, w: 700, cls: 'name attr', text: '[recipient.name]' },
        { type: 'text', label: 'Corps', x: 80, y: 290, w: 740, cls: 'sub', text: 'a suivi avec diligence et reussite la formation sur l organisation de la recherche et innovation, la protection de la propriete intellectuelle, le transfert de technologie et la valorisation economique des resultats de recherche ([edition.dates]).' },
        { type: 'text', label: 'Formule', x: 80, y: 400, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' },
        { type: 'text', label: 'Lieu / date', x: 80, y: 470, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' },
        { type: 'text', label: 'Fonction', x: 520, y: 470, w: 320, cls: 'sub', text: 'Le Coordinateur du projet,' },
        { type: 'text', label: 'Signataire', x: 520, y: 530, w: 320, cls: '', text: '[signatory.name]' },
        { type: 'text', label: 'Partenaires', x: 80, y: 540, w: 300, cls: 'sub', text: 'UE · AUF · RRIWA' },
        { type: 'qr', label: 'QR Verification', x: 48, y: 500, w: 56, qrUrl: DEFAULT_QR_URL },
        { type: 'text', label: 'Reference', x: 48, y: 580, w: 500, cls: 'sub', text: 'Reference : [certificate.uuid] · forvarrito.tg' }
      ]
    };
    var list = T[key] || T.participation;
    list.forEach(function (cfg) { createEl(cfg); });
    // fond selon modele
    var bgEl = document.getElementById('pageBg');
    page.classList.remove('bg-none', 'bg-simple', 'bg-gold', 'bg-bands', 'bg-custom', 'bg-plain');
    if (key === 'formation-csiuk') {
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
      toast('Format non supporte (image, PDF ou Word)');
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
      toast('Saisissez un prompt ou ajoutez un fichier');
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

      createEl({ type: 'logo', id: 'ai-logo-uk', label: 'Logo UK', x: 48, y: 36, w: 150, html: '<div class="mark">UK</div> UNIVERSITE DE KARA' });
      createEl({ type: 'logo', id: 'ai-logo-up', label: 'Logo UP', x: 680, y: 36, w: 170, html: 'UNIVERSITE DE PARAKOU' });
      createEl({ type: 'text', id: 'ai-title', label: 'Titre', x: 80, y: 110, w: 740, cls: 'heading', text: title });
      createEl({ type: 'text', id: 'ai-intro', label: 'Intro', x: 100, y: 170, w: 700, cls: 'sub', text: 'Je soussigne, Prof. Padabo KADOUZA, President du Comite d Organisation, atteste que' });
      createEl({ type: 'text', id: 'ai-name', label: 'Destinataire', x: 150, y: 220, w: 600, cls: 'name attr', text: '[recipient.name]' });
      var body = prompt
        ? ('Modele genere a partir de votre demande : « ' + prompt.slice(0, 160) + (prompt.length > 160 ? '…' : '') + ' ». Personnalisez les textes et champs sur le canvas.')
        : 'a participe avec assiduite aux travaux du colloque scientifique international de l Universite de Kara.';
      createEl({ type: 'text', id: 'ai-body', label: 'Corps', x: 80, y: 280, w: 740, cls: 'sub', text: body });
      createEl({ type: 'text', id: 'ai-close', label: 'Formule', x: 80, y: 380, w: 740, cls: 'sub', text: 'En foi de quoi, la presente attestation lui est delivree pour servir et valoir ce que de droit.' });
      createEl({ type: 'text', id: 'ai-place', label: 'Lieu / date', x: 520, y: 450, w: 320, cls: 'sub', text: 'Fait a Kara, le [certificate.issued_on]' });
      createEl({ type: 'text', id: 'ai-sign', label: 'Signataire', x: 520, y: 520, w: 320, cls: '', text: '[signatory.name]' });
      createEl({ type: 'qr', id: 'ai-qr', label: 'QR Verification', x: 48, y: 480, w: 72, qrUrl: DEFAULT_QR_URL });
      createEl({ type: 'text', id: 'ai-ref', label: 'Reference', x: 48, y: 570, w: 400, cls: 'sub', text: 'Reference : [certificate.uuid]' });

      if (aiFiles.length) {
        toast('Modele genere (ref. ' + aiFiles.length + ' fichier(s) + prompt)');
      } else {
        toast('Modele genere a partir du prompt');
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
    // Titre du document = type d'attestation en cours d'édition (et non un libellé figé).
    var docName = (cfg.kind === 'badge' ? 'Badge · ' : 'Attestation de ') + (cfg.typeLabel || '');
    var crumbEl = document.querySelector('.ed-top .crumb');
    var nameEl = document.getElementById('tplName');
    if (crumbEl) crumbEl.textContent = docName;
    if (nameEl) nameEl.value = docName;

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
    var TPL_KEY = { participation: 'participation', communication: 'communication', moderation: 'moderation', formation: 'formation-csiuk' };
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
})();
</script>
</body>
@endverbatim
</html>
