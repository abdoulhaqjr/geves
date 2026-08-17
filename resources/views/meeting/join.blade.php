<x-layouts.app :title="'Visio — ' . $session->label">
@if ($liveKit)
    {{-- ============ Salle LiveKit intégrée (lecteur maison, SDK bundlé) ============ --}}
    <style>
        #visio-room{position:fixed;inset:0;z-index:60;display:flex;flex-direction:column;overflow:hidden;
            --vbg:#0b0f1a;--vbg-elev:#121826;--vbg-panel:#161d2e;--vbg-tile:#1c2438;--vborder:rgba(255,255,255,.08);
            --vtext:#f1f5f9;--vmuted:#94a3b8;--vdanger:#e11d48;--vdanger-h:#be123c;--vok:#10b981;
            --vblue:#223ea4;--vblue-d:#1a3080;--vgold:#ffd700;--vgreen:#009933;
            background:var(--vbg);color:var(--vtext);font-family:var(--ukc-font,'Jost',sans-serif);}
        #visio-room *{box-sizing:border-box;margin:0;padding:0;}
        #visio-room button{cursor:pointer;font-family:inherit;}
        #visio-room .v-topbar{height:56px;flex-shrink:0;background:var(--vbg-elev);border-bottom:1px solid var(--vborder);
            display:flex;align-items:center;justify-content:space-between;padding:0 16px;gap:12px;}
        #visio-room .v-left{display:flex;align-items:center;gap:12px;min-width:0;}
        #visio-room .v-mark{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--vblue),var(--vgreen));
            display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;color:#fff;flex-shrink:0;}
        #visio-room .v-title{min-width:0;}
        #visio-room .v-title strong{display:block;font-size:13.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #visio-room .v-title span{font-size:11.5px;color:var(--vmuted);}
        #visio-room .v-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
        #visio-room .pill{font-size:11px;font-weight:700;padding:5px 10px;border-radius:50px;}
        #visio-room .pill.lk{background:rgba(34,62,164,.35);color:#c7d2fe;}
        #visio-room .pill.ok{background:rgba(16,185,129,.15);color:#6ee7b7;}
        #visio-room .pill.warn{background:rgba(245,158,11,.15);color:#fcd34d;}
        #visio-room .pill.err{background:rgba(225,29,72,.2);color:#fda4af;}
        #visio-room .v-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;border-radius:10px;
            font-weight:700;font-size:13.5px;padding:11px 16px;color:var(--vtext);}
        #visio-room .v-btn.gold{background:var(--vgold);color:var(--vblue-d);}
        #visio-room .v-btn.ghost{background:rgba(255,255,255,.06);border:1px solid var(--vborder);}
        #visio-room .v-btn.danger{background:var(--vdanger);color:#fff;}
        #visio-room .v-btn.sm{padding:8px 12px;font-size:12.5px;border-radius:8px;}
        #visio-room .v-main{flex:1;min-height:0;display:grid;grid-template-columns:1fr 340px;position:relative;}
        #visio-room .v-main.side-closed{grid-template-columns:1fr;}
        #visio-room .v-main.side-closed .side{display:none;}
        @@media(max-width:900px){#visio-room .v-main{grid-template-columns:1fr;}
            #visio-room .side{display:none;position:absolute;inset:0;z-index:15;width:100%;max-width:360px;right:0;left:auto;}
            #visio-room .side.open{display:flex;}}
        #visio-room .stage{min-width:0;min-height:0;display:flex;flex-direction:column;padding:12px 12px 8px;background:var(--vbg);}
        #visio-room .stage-main{flex:1;min-height:0;display:flex;flex-direction:column;position:relative;}
        #visio-room #stageGrid{flex:1;min-height:0;}
        #visio-room .stage-grid{display:grid;gap:10px;align-content:start;}
        #visio-room .stage-grid.speaker{grid-template-columns:1fr;grid-template-rows:1fr;}
        #visio-room .stage-grid.speaker .tile.main{aspect-ratio:auto;height:100%;border-radius:16px;}
        #visio-room .stage-grid:not(.speaker){grid-template-columns:repeat(auto-fit,minmax(220px,1fr));grid-auto-rows:minmax(140px,1fr);height:100%;align-content:stretch;}
        #visio-room .stage-grid:not(.speaker) .tile{aspect-ratio:auto;min-height:140px;height:100%;}
        #visio-room .stage-grid:not(.speaker).has-screen-share{grid-template-columns:1fr;grid-template-rows:1fr;}
        #visio-room .stage-grid:not(.speaker).has-screen-share .tile.main video{object-fit:contain!important;}
        #visio-room .filmstrip-wrap{flex-shrink:0;border-top:1px solid var(--vborder);background:rgba(0,0,0,.25);}
        #visio-room .filmstrip-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 12px;border:none;background:transparent;color:var(--vmuted);font-size:12px;font-weight:700;}
        #visio-room .filmstrip-toggle:hover{color:var(--vtext);background:rgba(255,255,255,.03);}
        #visio-room .filmstrip-wrap .filmstrip{display:none;gap:8px;overflow-x:auto;padding:0 12px 12px;}
        #visio-room .filmstrip-wrap.open .filmstrip{display:flex;}
        #visio-room .filmstrip-wrap.open .filmstrip-toggle .chev{transform:rotate(180deg);}
        #visio-room .filmstrip .tile{flex:0 0 150px;width:150px;aspect-ratio:16/10;height:auto;}
        #visio-room .stage-grid:not(.speaker) .filmstrip-wrap{display:none;}
        #visio-room .tile{position:relative;background:var(--vbg-tile);border:1px solid var(--vborder);border-radius:12px;overflow:hidden;aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;transition:border-color .2s,box-shadow .2s;}
        #visio-room .tile.is-presenter{border-color:rgba(255,215,0,.35);box-shadow:0 0 0 1px rgba(255,215,0,.12);}
        #visio-room .tile .avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--vblue),var(--vgreen));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.3rem;color:#fff;z-index:1;box-shadow:0 8px 24px rgba(0,0,0,.25);}
        #visio-room .tile.has-video .avatar{display:none!important;}
        #visio-room .tile video,#visio-room .tile .media{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#0a0e17;z-index:1;}
        #visio-room .tile .name{position:absolute;left:10px;bottom:10px;z-index:3;background:rgba(0,0,0,.6);padding:5px 10px;border-radius:8px;font-size:12.5px;font-weight:700;max-width:calc(100% - 20px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #visio-room .tile .badges{position:absolute;top:10px;right:10px;z-index:3;display:flex;gap:4px;}
        #visio-room .tile .badges span{font-size:10px;font-weight:800;padding:4px 8px;border-radius:50px;background:rgba(0,0,0,.55);}
        #visio-room .info-block{position:absolute;z-index:2;text-align:center;color:var(--vtext);}
        #visio-room .info-block .info-name{font-size:1.1rem;font-weight:800;}
        #visio-room .info-block .info-meta{font-size:.9rem;color:var(--vmuted);margin-top:4px;max-width:80vw;}
        #visio-room .pip-dropzone{display:none;position:absolute;top:10px;left:50%;transform:translateX(-50%);z-index:22;background:rgba(0,0,0,.6);border:1px dashed rgba(255,255,255,.4);border-radius:10px;padding:6px 12px;font-size:12px;color:#fff;}
        #visio-room .pip-dropzone.visible{display:block;}
        #visio-room .pip-dropzone.active{border-color:var(--vgold);color:var(--vgold);}
        #visio-room .pip-bubble{position:absolute;width:120px;height:120px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,215,0,.75);box-shadow:0 8px 28px rgba(0,0,0,.45);z-index:20;background:#1c2438;cursor:grab;user-select:none;right:16px;bottom:16px;}
        #visio-room .pip-bubble.dragging{cursor:grabbing;}
        #visio-room .pip-bubble .pip-avatar{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.4rem;color:#fff;background:linear-gradient(135deg,var(--vblue),var(--vgreen));}
        #visio-room .pip-bubble.has-video .pip-avatar{display:none;}
        #visio-room .pip-resize{position:absolute;right:4px;bottom:4px;z-index:5;width:18px;height:18px;border-radius:4px;background:rgba(0,0,0,.55);cursor:nwse-resize;border:1px solid rgba(255,255,255,.35);}
        #visio-room .pip-size-hint{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,.65);color:#fff;font-size:11px;font-weight:700;padding:4px 8px;border-radius:6px;z-index:6;display:none;}
        #visio-room .pip-bubble.resizing .pip-size-hint{display:block;}
        #visio-room .side{border-left:1px solid var(--vborder);background:var(--vbg-panel);display:flex;flex-direction:column;min-height:0;}
        #visio-room .side-tabs{display:flex;flex-shrink:0;border-bottom:1px solid var(--vborder);padding:0 8px;}
        #visio-room .side-tabs button{flex:1;padding:14px 8px;border:none;background:transparent;color:var(--vmuted);font-weight:700;font-size:13px;border-bottom:2px solid transparent;margin-bottom:-1px;}
        #visio-room .side-tabs button:hover{color:var(--vtext);}
        #visio-room .side-tabs button.active{color:var(--vtext);border-bottom-color:var(--vgold);}
        #visio-room .side-panel{display:none;flex:1;min-height:0;flex-direction:column;}
        #visio-room .side-panel.active{display:flex;}
        #visio-room .chat-log{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;}
        #visio-room .chat-msg{background:rgba(255,255,255,.04);border-radius:12px;padding:10px 12px;font-size:13.5px;line-height:1.45;}
        #visio-room .chat-msg .who{font-size:11px;font-weight:800;color:var(--vgold);margin-bottom:3px;}
        #visio-room .chat-msg.me .who{color:#93c5fd;}
        #visio-room .chat-input{border-top:1px solid var(--vborder);padding:12px;display:flex;gap:8px;}
        #visio-room .chat-input input{flex:1;border-radius:10px;border:1px solid var(--vborder);background:rgba(255,255,255,.05);color:var(--vtext);padding:11px 14px;font-size:13.5px;}
        #visio-room .chat-input input:focus{outline:none;border-color:var(--vblue);}
        #visio-room .people-list,#visio-room .comm-list{flex:1;overflow-y:auto;padding:12px 14px;}
        #visio-room .person{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--vborder);}
        #visio-room .person:last-child{border-bottom:none;}
        #visio-room .person .av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--vblue),var(--vgreen));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
        #visio-room .person .meta{flex:1;min-width:0;}
        #visio-room .person .meta strong{display:block;font-size:13.5px;font-weight:700;}
        #visio-room .person .meta span{font-size:11.5px;color:var(--vmuted);}
        #visio-room .mod-actions{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;}
        #visio-room .mod-actions button{font-size:10.5px;font-weight:700;padding:4px 8px;border-radius:6px;border:1px solid var(--vborder);background:rgba(255,255,255,.06);color:var(--vtext);}
        #visio-room .mod-actions button:hover{background:rgba(34,62,164,.45);}
        #visio-room .comm-item{padding:10px 0;border-bottom:1px solid var(--vborder);}
        #visio-room .comm-item:last-child{border-bottom:none;}
        #visio-room .comm-item .row{display:flex;justify-content:space-between;gap:8px;align-items:flex-start;}
        #visio-room .comm-item.speaking{background:rgba(255,215,0,.05);border-radius:8px;padding:8px;}
        #visio-room .comm-item .st{font-size:11px;font-weight:800;padding:3px 8px;border-radius:50px;white-space:nowrap;flex-shrink:0;}
        #visio-room .comm-item .st.on{background:rgba(16,185,129,.15);color:#6ee7b7;}
        #visio-room .comm-item .st.off{background:rgba(148,163,184,.15);color:var(--vmuted);}
        #visio-room .comm-item .st.live{background:rgba(225,29,72,.2);color:#fda4af;}
        #visio-room .comm-item .acts{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;}
        #visio-room .comm-item .acts button{font-size:11px;font-weight:700;padding:5px 9px;border-radius:6px;border:1px solid var(--vborder);background:rgba(255,255,255,.06);color:var(--vtext);}
        #visio-room .comm-item .acts button.primary{background:var(--vblue);border-color:transparent;}
        #visio-room .comm-item .acts button:hover{filter:brightness(1.15);}
        #visio-room .toolbar{flex-shrink:0;height:80px;background:var(--vbg-elev);border-top:1px solid var(--vborder);display:flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;}
        #visio-room .toolbar .sep{width:1px;height:28px;background:var(--vborder);margin:0 6px;}
        #visio-room .ctrl{width:48px;height:48px;border-radius:50%;border:none;background:rgba(255,255,255,.08);color:var(--vtext);display:inline-flex;align-items:center;justify-content:center;transition:background .15s,transform .1s;}
        #visio-room .ctrl:hover:not(:disabled){background:rgba(255,255,255,.14);transform:translateY(-1px);}
        #visio-room .ctrl:disabled{opacity:.35;cursor:not-allowed;}
        #visio-room .ctrl svg{width:22px;height:22px;}
        #visio-room .ctrl.active{background:var(--vblue);box-shadow:0 0 0 3px rgba(34,62,164,.3);}
        #visio-room .ctrl.muted{background:rgba(225,29,72,.85);color:#fff;}
        #visio-room .ctrl.hand.on{background:var(--vgold);color:var(--vblue-d);}
        #visio-room .ctrl.rec{background:rgba(225,29,72,.15);color:#fda4af;}
        #visio-room .ctrl.rec.active{background:var(--vdanger);color:#fff;animation:vrec 1.4s ease infinite;}
        #visio-room .ctrl.leave{background:var(--vdanger);color:#fff;width:auto;padding:0 18px;border-radius:24px;font-weight:700;font-size:13.5px;gap:8px;}
        #visio-room .ctrl.leave svg{width:18px;height:18px;}
        @@keyframes vrec{0%,100%{box-shadow:0 0 0 0 rgba(225,29,72,.5);}50%{box-shadow:0 0 0 6px rgba(225,29,72,0);}}
        #visio-room .rec-badge{display:none;position:absolute;top:12px;left:12px;z-index:28;background:rgba(225,29,72,.9);color:#fff;font-size:11px;font-weight:800;padding:5px 10px;border-radius:6px;}
        #visio-room .rec-badge.on{display:inline-flex;align-items:center;gap:6px;}
        #visio-room .rec-badge .dot{width:8px;height:8px;border-radius:50%;background:#fff;animation:vrec 1.2s ease infinite;}
        #visio-room .menu{display:none;position:absolute;bottom:92px;left:50%;transform:translateX(-50%);background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:16px;padding:14px;z-index:45;box-shadow:0 16px 48px rgba(0,0,0,.45);width:min(340px,92vw);}
        #visio-room .menu.open{display:block;}
        #visio-room .menu h4{font-size:12px;font-weight:800;color:var(--vmuted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;}
        #visio-room .bg-options{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
        #visio-room .bg-opt{border:2px solid transparent;border-radius:12px;overflow:hidden;cursor:pointer;background:var(--vbg-tile);padding:0;aspect-ratio:1;position:relative;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--vmuted);}
        #visio-room .bg-opt:hover{border-color:rgba(255,255,255,.25);}
        #visio-room .bg-opt.active{border-color:var(--vgold);color:var(--vtext);}
        #visio-room .share-menu button{display:flex;width:100%;align-items:center;gap:10px;padding:12px 14px;border:none;border-radius:10px;background:transparent;color:var(--vtext);font-weight:700;font-size:13.5px;text-align:left;}
        #visio-room .share-menu button:hover{background:rgba(255,255,255,.08);}
        #visio-room .share-menu .hint{font-size:11.5px;color:var(--vmuted);padding:8px 10px 4px;line-height:1.4;}
        #visio-room .vol-panel{display:none;position:absolute;bottom:92px;left:50%;transform:translateX(-50%);background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:14px;padding:12px 16px;z-index:46;box-shadow:0 12px 40px rgba(0,0,0,.4);align-items:center;gap:10px;min-width:220px;}
        #visio-room .vol-panel.open{display:flex;}
        #visio-room .vol-panel label{font-size:12px;font-weight:700;color:var(--vmuted);}
        #visio-room .vol-panel input[type=range]{flex:1;accent-color:var(--vgold);}
        #visio-room .vol-panel .vol-val{font-size:12px;font-weight:800;min-width:36px;text-align:right;}
        #visio-room .perm-banner{display:none;position:absolute;top:64px;left:50%;transform:translateX(-50%);z-index:70;width:min(420px,92vw);background:var(--vbg-elev);border:1px solid rgba(255,215,0,.35);border-radius:14px;padding:14px 16px;box-shadow:0 12px 40px rgba(0,0,0,.4);font-size:13px;line-height:1.45;}
        #visio-room .perm-banner.on{display:block;}
        #visio-room .perm-banner strong{color:var(--vgold);}
        #visio-room .perm-banner .actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
        #visio-room .perm-banner .actions button{border:none;border-radius:8px;padding:8px 12px;font-weight:700;font-size:12.5px;}
        #visio-room .perm-banner .actions .p1{background:var(--vgold);color:var(--vblue-d);}
        #visio-room .perm-banner .actions .p2{background:rgba(255,255,255,.08);color:var(--vtext);border:1px solid var(--vborder);}
        #visio-room .v-toast{position:absolute;bottom:96px;left:50%;transform:translateX(-50%) translateY(20px);z-index:90;background:rgba(15,18,28,.96);color:var(--vtext);border:1px solid var(--vborder);border-radius:10px;padding:10px 16px;font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:.25s;}
        #visio-room .v-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
        #visio-room .v-status{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#c7d1e6;background:rgba(0,0,0,.4);padding:10px 16px;border-radius:10px;font-size:.9rem;}
    </style>
    @endverbatim

    <div id="visio-room" data-role="{{ $liveKit['role'] }}">
        <header class="v-topbar">
            <div class="v-left">
                <div class="v-mark">UK</div>
                <div class="v-title">
                    <strong id="roomTitle">{{ $liveKit['sessionLabel'] }}</strong>
                    <span id="roomMeta">{{ $liveKit['editionTitle'] }} · LiveKit</span>
                </div>
            </div>
            <div class="v-right">
                <span class="pill lk">LiveKit</span>
                <span class="pill warn" id="connPill">Connexion…</span>
                <button type="button" class="v-btn ghost sm" id="btnToggleSide">Panneau</button>
                <a href="{{ $liveKit['backUrl'] }}" class="v-btn danger sm" id="btnLeaveTop">Quitter</a>
            </div>
        </header>

        <div class="v-main" id="mainLayout">
            <div class="stage">
                <div class="stage-main" id="stageMain">
                    <div class="stage-grid speaker" id="stageGrid">
                        <div class="tile main is-presenter" id="tileMain">
                            <div class="avatar" id="mainAvatar">?</div>
                            <div class="info-block" id="mainInfo" style="display:none;">
                                <div class="info-name" id="mainInfoName">—</div>
                                <div class="info-meta" id="mainInfoMeta">—</div>
                            </div>
                            <div class="name" id="mainName">Présentateur</div>
                            <div class="badges"><span id="mainRole">—</span></div>
                            <div class="rec-badge" id="recBadge"><span class="dot"></span> REC</div>
                            <div class="pip-dropzone" id="pipDropzone">↑ Glisser vers le haut pour fermer</div>
                        </div>
                    </div>
                </div>
                <div class="filmstrip-wrap" id="filmstripWrap">
                    <button type="button" class="filmstrip-toggle" id="filmstripToggle">
                        <span>Participants (<span id="filmstripCount">1</span>)</span><span class="chev">▲</span>
                    </button>
                    <div class="filmstrip" id="filmstrip">
                        <div class="tile" id="tileLocal">
                            <div class="avatar" id="localAvatar">?</div>
                            <div class="name" id="localName">—</div>
                            <div class="badges"><span id="localRole">Vous</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="side" id="sidePanel">
                <div class="side-tabs">
                    <button type="button" class="active" data-side="chat">Chat</button>
                    <button type="button" data-side="people">Participants</button>
                    <button type="button" data-side="comms" id="tabComms" style="display:none;">Communicateurs</button>
                </div>
                <div class="side-panel active" id="panelChat">
                    <div class="chat-log" id="chatLog"></div>
                    <div class="chat-input">
                        <input type="text" id="chatInput" placeholder="Écrire un message…" autocomplete="off">
                        <button type="button" class="v-btn gold sm" id="btnSendChat">Envoyer</button>
                    </div>
                </div>
                <div class="side-panel" id="panelPeople"><div class="people-list" id="peopleList"></div></div>
                <div class="side-panel" id="panelComms">
                    <div style="padding:12px 14px 0;font-size:12px;color:var(--vmuted);line-height:1.4;">Communications programmées de la session. Un seul présentateur à la fois.</div>
                    <div class="comm-list" id="commList"></div>
                </div>
            </aside>
        </div>

        <div class="toolbar">
            <button type="button" class="ctrl muted" id="btnMic" title="Micro" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnSpeaker" title="Son">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
            </button>
            <button type="button" class="ctrl muted" id="btnCam" title="Caméra" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnScreen" title="Partager l’écran" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>
            </button>
            <div class="sep"></div>
            <button type="button" class="ctrl hand" id="btnHand" title="Lever la main" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 13V5.5a1.5 1.5 0 0 1 3 0V12"/><path d="M11 12V4.5a1.5 1.5 0 0 1 3 0V12"/><path d="M14 12V6.5a1.5 1.5 0 0 1 3 0V14a6 6 0 0 1-6 6a6 6 0 0 1-5.2-3"/><path d="M8 13v2a2 2 0 0 1-4 0v-2.5A1.5 1.5 0 0 1 7 12.5V13"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnLayout" title="Disposition">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 11h5V5H4v6zm0 7h5v-6H4v6zm6 0h5v-6h-5v6zm6 0h5v-6h-5v6zm-6-7h5V5h-5v6zm6-6v6h5V5h-5z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnFs" title="Plein écran">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M16 3h3a2 2 0 0 1 2 2v3"/><path d="M8 21H5a2 2 0 0 1-2-2v-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnBg" title="Arrière-plan" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M3 16l5-4 3 2 4-5 6 7"/></svg>
            </button>
            <button type="button" class="ctrl rec" id="btnRec" title="Enregistrer" style="display:none;">
                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="7"/></svg>
            </button>
            <div class="sep"></div>
            <button type="button" class="ctrl leave" id="btnLeave">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Quitter
            </button>
        </div>

        <div class="menu" id="bgMenu">
            <h4>Arrière-plan caméra</h4>
            <div class="bg-options">
                <button type="button" class="bg-opt active" data-bg="none">Aucun</button>
                <button type="button" class="bg-opt" data-bg="blur">Flou</button>
            </div>
        </div>
        <div class="menu share-menu" id="shareMenu">
            <div class="hint">Pour un diaporama (PowerPoint, PDF…), choisissez la <strong>fenêtre de l’application</strong>.</div>
            <button type="button" data-share="window">Fenêtre d’application</button>
            <button type="button" data-share="monitor">Écran entier</button>
            <button type="button" data-share="browser">Onglet du navigateur</button>
            <button type="button" data-share="stop" id="shareStopBtn" style="display:none;color:#fda4af;">Arrêter le partage</button>
        </div>
        <div class="vol-panel" id="volPanel">
            <label>Volume</label><input type="range" id="volRange" min="0" max="100" value="100"><span class="vol-val" id="volVal">100%</span>
        </div>
        <div class="perm-banner" id="permBanner">
            <strong>Permissions navigateur</strong>
            <div id="permBannerText" style="margin-top:6px;color:var(--vmuted);"></div>
            <div class="actions"><button type="button" class="p1" id="permRetry">Réessayer</button><button type="button" class="p2" id="permDismiss">Fermer</button></div>
        </div>
        <div class="v-status" id="lkStatus">Connexion à la salle…</div>
        <div class="v-toast" id="toast"></div>
    </div>

    <script type="application/json" id="lk-config">@json($liveKit)</script>
@else
    {{-- ============ Jitsi / lien externe / messages ============ --}}
    @php($st = $session->meetingStatusEnum())
    <div style="display:flex;flex-direction:column;height:100vh;">
        <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
                       padding:10px clamp(12px,3vw,24px);background:#fff;border-bottom:1px solid var(--ukc-border);">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    @if ($session->isLive())
                        <span class="ukc-pill ukc-pill--err" style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:currentColor;"></span> EN DIRECT
                        </span>
                    @endif
                    <strong style="font-size:1rem;">{{ $session->label }}</strong>
                </div>
                <div style="font-size:.8rem;color:var(--ukc-muted);">
                    {{ $session->edition?->title }}
                    @if ($role->canJoin()) · Vous rejoignez en tant que
                        <strong>{{ ['moderator' => 'modérateur', 'presenter' => 'présentateur', 'attendee' => 'participant'][$role->value] ?? '' }}</strong>
                    @endif
                </div>
            </div>
            <a href="{{ url()->previous() }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                <x-ukc.icon name="close" :size="16" /> Quitter
            </a>
        </header>

        <main style="flex:1;min-height:0;display:flex;">
            @if ($embed)
                <div id="jitsi-container" style="flex:1;width:100%;height:100%;"></div>
                <script src="https://{{ $embed['domain'] }}/external_api.js"></script>
                <script>
                    (function () {
                        if (typeof JitsiMeetExternalAPI === 'undefined') { return; }
                        const api = new JitsiMeetExternalAPI(@js($embed['domain']), {
                            roomName: @js($embed['roomName']),
                            parentNode: document.getElementById('jitsi-container'),
                            width: '100%', height: '100%',
                            jwt: @js($embed['jwt']),
                            userInfo: { displayName: @js($embed['userName']) },
                            configOverwrite: { prejoinPageEnabled: true },
                        });
                        api.addListener('readyToClose', function () { window.location = @js(url()->previous() ?: route('home')); });
                    })();
                </script>
            @elseif ($externalUrl)
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <span class="ico" style="display:inline-flex;color:var(--ukc-blue);margin-bottom:8px;"><x-ukc.icon name="video" :size="34" /></span>
                        <h3 style="margin-bottom:6px;">Rejoindre la visio</h3>
                        <p style="color:var(--ukc-muted);">La salle s’ouvre dans un nouvel onglet.</p>
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--blue" style="margin-top:14px;">
                            <x-ukc.icon name="external" :size="16" /> Ouvrir la salle
                        </a>
                    </div>
                </div>
            @elseif ($session->isLive() && $role->canJoin())
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <h3 style="margin-bottom:6px;">Salle non configurée</h3>
                        <p style="color:var(--ukc-muted);">Le lien de la réunion n’a pas encore été renseigné par l’organisation.</p>
                    </div>
                </div>
            @else
                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:24px;">
                    <div class="ukc-card" style="max-width:460px;text-align:center;">
                        <span class="ico" style="display:inline-flex;color:var(--ukc-blue);margin-bottom:8px;"><x-ukc.icon name="video" :size="34" /></span>
                        @if (! $session->isLive())
                            <h3 style="margin-bottom:6px;">La visio n’est pas en cours</h3>
                            <p style="color:var(--ukc-muted);">{{ $st->label() }}. Revenez à l’heure de la session{{ '' }}@if ($session->starts_at) ({{ $session->starts_at->translatedFormat('d F, H:i') }})@endif.</p>
                            @if ($session->hasRecording())
                                <a href="{{ $session->recording_url }}" target="_blank" class="ukc-btn ukc-btn--blue" style="margin-top:14px;"><x-ukc.icon name="video" :size="16" /> Voir le replay</a>
                            @endif
                        @elseif ($isGuest)
                            <h3 style="margin-bottom:6px;">Connexion requise</h3>
                            <p style="color:var(--ukc-muted);">Cette visio est réservée aux participants inscrits.</p>
                            <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--blue" style="margin-top:14px;"><x-ukc.icon name="login" :size="16" /> Se connecter</a>
                        @else
                            <h3 style="margin-bottom:6px;">Accès réservé</h3>
                            <p style="color:var(--ukc-muted);">Cette visio est réservée aux participants inscrits de cette édition.</p>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>
@endif
</x-layouts.app>
