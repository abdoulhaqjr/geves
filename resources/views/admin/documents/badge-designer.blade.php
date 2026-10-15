<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Éditeur de badges - {{ config('app.name') }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16.png') }}">
  <link rel="apple-touch-icon" href="{{ \App\Support\Branding::mark() }}">
  @php($ukcEditorCfg = ['editionId' => (string) $event_id, 'kind' => $kind, 'type' => $type, 'typeLabel' => $typeLabel ?? 'Badge', 'backUrl' => route('admin.documents.attestations', ['event_id' => $event_id, 'tab' => 'badges']), 'saveUrl' => route('admin.documents.templates.save'), 'generateUrl' => route('admin.documents.templates.generate'), 'uploadUrl' => route('admin.documents.templates.upload'), 'csrf' => csrf_token(), 'editorState' => $editorState ?? null, 'vars' => $vars ?? [], 'preview' => $preview ?? false, 'logos' => ['uk' => (parse_url(\App\Support\Branding::wide(), PHP_URL_PATH) ?: \App\Support\Branding::wide()), 'parakou' => '/img/attestations/logo-parakou.png']])
  <script>window.UKC_EDITOR = @json($ukcEditorCfg);</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800;900&family=Lato:ital,wght@0,400;0,700;1,400;1,700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  @vite(['resources/css/badge-designer.css', 'resources/css/designer-tailwise.css', 'resources/js/badge-designer.js'])
</head>
<body>

  <header class="ed-top">
    <button type="button" class="back" onclick="location.href=(window.UKC_EDITOR&&window.UKC_EDITOR.backUrl)||'/admin/attestations'">← Documents</button>
    <div class="crumb">{{ $typeLabel }} - {{ $scope ?? 'Participant' }}</div>
    <input class="name-input" id="tplName" value="{{ $typeLabel }}" title="Nom du modèle, repris dans la liste des documents">
    <button type="button" class="btn-save" id="btnSave">Enregistrer</button>
  </header>

  <div class="ed-shell">
    <nav class="ed-rail">
      <button type="button" data-panel="ai" title="Assistant IA">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/></svg>
        IA
      </button>
      <button type="button" data-panel="templates" title="Modèles">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"/></svg>
        Modèles
      </button>
      <button type="button" data-panel="uploads" title="Images">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
        Images
      </button>
      <button type="button" data-panel="elements" title="Éléments">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4.5 2 7L12 17l-6.5 3.5 2-7L2 9h7z"/></svg>
        Éléments
      </button>
      <button type="button" data-panel="text" title="Texte">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>
        Texte
      </button>
      <button type="button" data-panel="attributes" title="Champs">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
        Champs
      </button>
      <button type="button" data-panel="qrcodes" title="QR codes">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5z"/></svg>
        QR codes
      </button>
      <button type="button" class="active" data-panel="layers" title="Calques">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 9L2 7v2l10 5 10-5V7l-10 4zm0 4L2 11v2l10 5 10-5v-2l-10 4z"/></svg>
        Calques
      </button>
    </nav>

    <aside class="ed-panel">
      <div class="panel-view" id="panel-ai">
        <div class="ed-panel-head">Assistant IA</div>
        <div class="ed-panel-body">
          <p class="ly-meta">Décrivez le badge « {{ $scope }} » souhaité (couleurs, photo, QR…). Ce modèle sera utilisé pour les destinataires de cette catégorie.</p>
          <div class="ai-box">
            <textarea id="aiPrompt" rows="8" placeholder="Ex. : Badge {{ $scope }}, bandeau bleu UK, photo ronde, nom en grand, QR en bas, référence UKC-2026-…"></textarea>
            <div class="ai-drop" id="aiDrop">Image de référence<br><span style="font-size:11px;">PNG, JPG, WebP - max 8 Mo</span></div>
            <input type="file" id="aiFile" accept="image/png,image/jpeg,image/webp" style="display:none;">
            <div id="aiFileList"></div>
            <button type="button" class="ai-run" id="aiRun">Générer</button>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-templates">
        <div class="ed-panel-head">Modèles de design</div>
        <div class="ed-panel-body">
          <p class="ly-meta">Variantes de présentation du badge « {{ $scope }} ». Le modèle choisi s’applique aux destinataires de cette catégorie.</p>
          <div class="ed-panel-section" id="badgeDesignFormat">A6 Portrait</div>
          {{-- L'aperçu (.mini) est rendu par le JS depuis le modèle réel (T[tpl]),
               rempli des valeurs d'exemple - même rendu que « Mes badges ». --}}
          <div class="ed-tpl-card active" data-tpl="co" data-formats="a6-portrait a7-portrait">
            <div class="mini"></div>
            Cadre vert/bleu UK x Parakou
          </div>
          <div class="ed-tpl-card" data-tpl="co-photo" data-formats="a6-portrait a7-portrait">
            <div class="mini"></div>
            Cadre vert/bleu avec photo UK x Parakou
          </div>
          <div class="ed-tpl-card" data-tpl="participant" data-formats="a6-portrait a6-landscape a7-portrait card-landscape cr80">
            <div class="mini"></div>
            Bandeau bleu - photo - nom - QR
          </div>
          <div class="ed-tpl-card" data-tpl="card" data-formats="a6-landscape card-landscape cr80">
            <div class="mini"></div>
            Badge paysage type carte
          </div>
          <button type="button" class="ed-tool-btn" id="btnBlank">+ Design vierge</button>
        </div>
      </div>

      <div class="panel-view" id="panel-uploads">
        <div class="ed-panel-head">Images</div>
        <div class="ed-panel-body">
          <div class="ed-upload" id="btnUploadImg">Téléverser une image<br><span style="font-size:11px;">PNG, JPG, WebP ou GIF</span></div>
          <input type="file" id="fileUpload" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;">
          <div class="ed-panel-section">Images ajoutées</div>
          <div class="ed-grid-2" id="uploadList">
            <div class="ed-thumb ed-thumb--logo" data-logo="uk" title="Université de Kara"><img src="{{ $ukcEditorCfg['logos']['uk'] }}" alt="Logo Université de Kara"></div>
            <div class="ed-thumb ed-thumb--logo" data-logo="up" title="Université de Parakou"><img src="{{ $ukcEditorCfg['logos']['parakou'] }}" alt="Logo Université de Parakou"></div>
          </div>
          <p class="ly-meta" style="margin-top:10px;">Cliquez une image pour l’ajouter au badge. Le fond se change avec <strong>Image de fond</strong>.</p>
        </div>
      </div>

      <div class="panel-view" id="panel-elements">
        <div class="ed-panel-head">Éléments</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="photo">+ Photo participant</button>
          <div class="ed-panel-section">Pictogrammes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-tool-btn" data-add="icon-location">+ Localisation</button>
            <button type="button" class="ed-tool-btn" data-add="icon-calendar">+ Calendrier</button>
          </div>
          <div class="ed-panel-section">Lignes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-shape" data-add="line-h" title="Ligne horizontale"><div class="ln-h"></div></button>
            <button type="button" class="ed-shape" data-add="line-v" title="Ligne verticale"><div class="ln-v"></div></button>
          </div>
          <div class="ed-panel-section">Formes</div>
          <div class="ed-grid-2">
            <button type="button" class="ed-shape" data-add="rect" title="Rectangle plein"><div class="sq"></div></button>
            <button type="button" class="ed-shape" data-add="rect-o" title="Rectangle contour"><div class="sq-o"></div></button>
            <button type="button" class="ed-shape" data-add="circle" title="Cercle plein"><div class="ci"></div></button>
            <button type="button" class="ed-shape" data-add="circle-o" title="Cercle contour"><div class="ci-o"></div></button>
          </div>
        </div>
      </div>

      <div class="panel-view" id="panel-text">
        <div class="ed-panel-head">Texte</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="textbox">+ Zone de texte</button>
          <div class="ed-panel-section">Styles de texte</div>
          <button type="button" class="ed-tool-btn" data-add="heading">Titre</button>
          <button type="button" class="ed-tool-btn" data-add="subheading">Sous-titre</button>
          <button type="button" class="ed-tool-btn" data-add="normal">Paragraphe</button>
          <div class="ed-panel-section">Champs courants</div>
          <button type="button" class="ed-tool-btn" data-add="name">Nom du titulaire</button>
          <button type="button" class="ed-tool-btn" data-add="role">Rôle du titulaire</button>
          <p class="ly-meta" style="margin-top:12px;">Double-cliquez un texte du badge pour le modifier.</p>
        </div>
      </div>

      <div class="panel-view" id="panel-attributes">
        <div class="ed-panel-head">Champs du badge</div>
        <div class="ed-panel-body">
          <p class="ly-meta">Cliquez un champ pour l’ajouter au badge. Sa valeur sera remplacée pour chaque destinataire.</p>
          <div class="ed-attr" data-attr="[recipient.name]">[recipient.name]</div>
          <div class="ed-attr" data-attr="[recipient.institution]">[recipient.institution]</div>
          <div class="ed-attr" data-attr="[recipient.role]">[recipient.role]</div>
          <div class="ed-attr" data-attr="[recipient.photo]">[recipient.photo]</div>
          <div class="ed-attr" data-attr="[badge.ref]">[badge.ref]</div>
          <div class="ed-attr" data-attr="[edition.name]">[edition.name]</div>
          <div class="ed-attr" data-attr="[edition.dates]">[edition.dates]</div>
          <div class="ed-attr" data-attr="[edition.location]">[edition.location]</div>
          <div class="ed-attr" data-attr="[edition.theme]">[edition.theme]</div>
        </div>
      </div>

      <div class="panel-view" id="panel-qrcodes">
        <div class="ed-panel-head">QR codes</div>
        <div class="ed-panel-body">
          <button type="button" class="ed-tool-btn" data-add="qr-verify">+ Page de vérification</button>
          <p class="ly-meta">Le QR code mène à la vérification du badge de son titulaire.</p>
          <button type="button" class="ed-tool-btn" data-add="qr-custom">+ Adresse personnalisée</button>
          <p class="ly-meta">Pointe vers l’adresse de votre choix.</p>
        </div>
      </div>

      <div class="panel-view active" id="panel-layers">
        <div class="ed-panel-head"><span>Calques</span><span style="font-size:11px;font-weight:600;color:var(--ukc-light);" id="lyCount">0</span></div>
        <div class="ed-panel-body">
          <div class="ly-toolbar">
            <button type="button" id="lyFront" title="Premier plan (Ctrl+])">⤒</button>
            <button type="button" id="lyUp" title="Monter (Ctrl+Maj+])">↑</button>
            <button type="button" id="lyDown" title="Descendre (Ctrl+Maj+[)">↓</button>
            <button type="button" id="lyBack" title="Arrière-plan (Ctrl+[)">⤓</button>
            <button type="button" id="lyDup" title="Dupliquer (Ctrl+D)">⧉</button>
            <button type="button" id="lyGroup" title="Grouper le calque (Ctrl+G)">⊞</button>
            <button type="button" id="lyDel" title="Supprimer (Suppr)">⌫</button>
          </div>
          <p class="ly-meta">Glisser ⠿ pour réordonner - double-clic sur un nom pour le modifier - <span class="kbd">Ctrl+G</span> grouper</p>
          <div id="layersList"></div>
          <div class="ly-controls" id="lyControls">
            <label>Opacité <input type="range" id="lyOpacity" min="10" max="100" value="100"><span id="lyOpacityVal">100%</span></label>
            <label>Fusion
              <select id="lyBlend">
                <option value="normal">Normal</option>
                <option value="multiply">Produit</option>
                <option value="screen">Superposition</option>
                <option value="overlay">Incrustation</option>
                <option value="darken">Obscurcir</option>
                <option value="lighten">Éclaircir</option>
                <option value="color-dodge">Densité couleur -</option>
                <option value="color-burn">Densité couleur +</option>
                <option value="hard-light">Lumière crue</option>
                <option value="soft-light">Lumière tamisée</option>
                <option value="difference">Différence</option>
                <option value="exclusion">Exclusion</option>
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
        <button type="button" class="sbtn-tool" id="btnRedo" title="Rétablir (Ctrl+Maj+Z)" disabled>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
        </button>
        <span class="sep" style="width:1px;height:22px;background:var(--ukc-border);"></span>
        <button type="button" class="chip" id="btnBg">Image de fond</button>
        <span class="label">Format :</span>
        <div class="ed-format-chips" role="group" aria-label="Format du badge">
          <button type="button" class="chip active" data-size="a6-portrait" title="105 × 148 mm">A6 Portrait</button>
          <button type="button" class="chip" data-size="a6-landscape" title="148 × 105 mm">A6 Paysage</button>
          <button type="button" class="chip" data-size="a7-portrait" title="74 × 105 mm">A7 Portrait</button>
          <button type="button" class="chip" data-size="card-landscape" title="90 × 55 mm">Carte 90×55</button>
          <button type="button" class="chip" data-size="cr80" title="85,6 × 54 mm">CR80</button>
        </div>
        <select class="ed-format-select" id="badgeFormatSelect" aria-label="Format du badge">
          <option value="a6-portrait">A6 Portrait</option>
          <option value="a6-landscape">A6 Paysage</option>
          <option value="a7-portrait">A7 Portrait</option>
          <option value="card-landscape">Carte 90×55</option>
          <option value="cr80">CR80</option>
        </select>
        <span class="spacer"></span>
        <div class="ed-zoom-bar">
          <button type="button" id="zoomOut" title="Zoom -">−</button>
          <span id="zoomLabel">100%</span>
          <button type="button" id="zoomIn" title="Zoom +">+</button>
          <button type="button" id="zoomReset" title="Ajuster à la vue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3H4v4M16 3h4v4M8 21H4v-4M16 21h4v-4"/></svg>
          </button>
        </div>
        <button type="button" class="btn-preview" id="btnPreview">Aperçu</button>
      </div>
      <div class="ed-stylebar disabled" id="styleBar">
        <select id="fontFamily">
          <option value="Jost">Jost</option>
          <option value="Georgia">Georgia</option>
          <option value="'Times New Roman'">Times</option>
          <option value="'Playfair Display'">Playfair</option>
          <option value="Arial">Arial</option>
          <option value="Lato, sans-serif">Lato</option>
        </select>
        <select id="fontWeight">
          <option value="400">Normal</option>
          <option value="500">Moyen</option>
          <option value="600">Demi-gras</option>
          <option value="700">Gras</option>
          <option value="800">Extra-gras</option>
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
