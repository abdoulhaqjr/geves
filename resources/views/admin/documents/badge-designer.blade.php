<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Éditeur de badges - {{ config('app.name') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php($ukcEditorCfg = ['editionId' => (string) $event_id, 'kind' => $kind, 'type' => $type, 'typeLabel' => $typeLabel ?? 'Badge', 'backUrl' => route('admin.documents.attestations', ['event_id' => $event_id, 'tab' => 'badges']), 'saveUrl' => route('admin.documents.templates.save'), 'csrf' => csrf_token(), 'editorState' => $editorState ?? null, 'vars' => $vars ?? [], 'preview' => $preview ?? false])
  <script>window.UKC_EDITOR = @json($ukcEditorCfg);</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  @vite(['resources/css/badge-designer.css', 'resources/css/designer-tailwise.css', 'resources/js/badge-designer.js'])
</head>
<body>

  <header class="ed-top">
    <button type="button" class="back" onclick="location.href=(window.UKC_EDITOR&&window.UKC_EDITOR.backUrl)||'/admin/attestations'">← Documents</button>
    <div class="crumb">{{ $typeLabel }} - {{ $scope ?? 'Participant' }}</div>
    <input class="name-input" id="tplName" value="{{ $typeLabel }}" title="Nom du modèle, repris dans la liste des documents">
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
            <div class="ai-drop" id="aiDrop">Image / PDF de reference<br><span style="font-size:11px;">PNG, JPG, PDF - max 8 Mo</span></div>
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
          <div class="ed-tpl-card active" data-tpl="co">
            <div class="mini" style="background:linear-gradient(150deg,#00be62 0%,#00be62 46%,#253c98 60%,#253c98 100%);color:#fff;">COMITÉ D’ORGANISATION</div>
            Cadre vert/bleu UK x Parakou (par défaut)
          </div>
          <div class="ed-tpl-card" data-tpl="participant">
            <div class="mini">PARTICIPANT</div>
            Bandeau bleu - photo - nom - QR
          </div>
          <div class="ed-tpl-card" data-tpl="communicant">
            <div class="mini" style="background:linear-gradient(160deg,#009933 0%,#009933 38%,#fff 38%);color:#fff;">COMMUNICANT</div>
            Bandeau vert - role COMMUNICATION
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
          <div class="ed-upload" id="btnUploadImg">Upload Image<br><span style="font-size:11px;">SVG, PNG, JPG - max 2 Mo</span></div>
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
          <p class="ly-meta">Par défaut : identifiant du participant (DIDs / inscription).</p>
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
          <p class="ly-meta">Glisser ⠿ - double-clic pour renommer - <span class="kbd">Ctrl+L</span> verrouiller</p>
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

</body>
</html>
