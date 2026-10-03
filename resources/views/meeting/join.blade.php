<x-layouts.app :title="'Visio - ' . $label">
@if ($liveKit)
    {{-- ============ Salle LiveKit intégrée (lecteur maison, SDK bundlé) ============ --}}
    <style>
        /* ⚠️ Les hauteurs des deux barres sont des VARIABLES : une dizaine de
           règles s'y accrochent (bandeau d'état, menus, infobulle, panneau de
           volume). Écrites en dur, elles laissaient tous ces éléments à
           l'ancienne hauteur dès que les barres se compactaient sur téléphone :
           le bandeau d'état flottait dans le vide, les menus passaient sous la
           barre d'outils. */
        #visio-room{position:fixed;inset:0;z-index:60;display:flex;flex-direction:column;overflow:hidden;
            --vtop:56px;--vtoolbar:80px;--vside:400px;
            --vbg:#0b0f1a;--vbg-elev:#121826;--vbg-panel:#161d2e;--vbg-tile:#1c2438;--vborder:rgba(255,255,255,.08);
            --vtext:#f1f5f9;--vmuted:#94a3b8;--vdanger:#e11d48;--vdanger-h:#be123c;--vok:#10b981;
            --vblue:#223ea4;--vblue-d:#1a3080;--vgold:#ffd700;--vgreen:#009933;
            background:var(--vbg);color:var(--vtext);font-family:var(--ukc-font,'Jost',sans-serif);}
        /* ⚠️ Sur téléphone, `inset:0` fait confiance au GRAND viewport : la barre
           d'adresse du navigateur recouvrait la barre d'outils, et « Quitter »
           devenait inatteignable. `100dvh` suit la hauteur réellement visible, et
           les marges de sûreté tiennent compte de l'encoche et de la barre
           gestuelle (elles valent zéro partout ailleurs). */
        @@supports (height:100dvh){#visio-room{height:100dvh;}}
        /* Un défilement qui atteint le bout du chat ne doit pas se propager à
           la page : sur Android, cela déclenchait « tirer pour recharger » au
           milieu d'une séance. */
        #visio-room{overscroll-behavior:contain;}
        #visio-room{padding:env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);}
        #visio-room *{box-sizing:border-box;margin:0;padding:0;}
        #visio-room button{cursor:pointer;font-family:inherit;}
        #visio-room .v-topbar{height:var(--vtop);flex-shrink:0;background:var(--vbg-elev);border-bottom:1px solid var(--vborder);
            display:flex;align-items:center;justify-content:space-between;padding:0 16px;gap:12px;}
        /* `flex:1` : sans lui, la droite de la barre (qui ne se comprime pas) écrasait
           le titre de la séance jusqu'à ZÉRO pixel de large sur téléphone. */
        #visio-room .v-left{display:flex;align-items:center;gap:12px;min-width:0;flex:1;}
        /* Le logo apporte sa propre forme : pas d'aplat derrière, et `contain`
           pour ne jamais le déformer quel que soit le fichier téléversé.
           ⚠️ Le cadre ROGNE légèrement (overflow + léger agrandissement) : les
           fichiers de marque portent presque toujours une marge blanche cuite
           dans l'image, qui faisait flotter un petit logo dans un grand carré.
           Le grossissement reste modeste pour ne pas entamer un logo déjà cadré. */
        #visio-room .v-mark{width:34px;height:34px;border-radius:9px;flex-shrink:0;overflow:hidden;display:block;}
        #visio-room .v-mark img{width:100%;height:100%;object-fit:contain;display:block;transform:scale(1.16);}
        #visio-room .v-title{min-width:0;display:flex;flex-direction:column;gap:0;}
        /* Deux lignes qui se lisent comme un bloc : interlignes serrés, sinon le
           sous-titre paraît appartenir à autre chose que le titre. */
        #visio-room .v-title strong{display:block;font-size:13.5px;font-weight:800;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #visio-room .v-title span{display:block;font-size:11.5px;line-height:1.3;color:var(--vmuted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #visio-room .v-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
        /* Pastille d'état : une puce colorée porte l'information, le texte la nomme.
           Lue d'un coup d'œil sans avoir à lire. */
        #visio-room .pill{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:5px 11px;border-radius:50px;}
        #visio-room .pill::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0;}
        #visio-room .pill.ok{background:rgba(16,185,129,.15);color:#6ee7b7;}
        #visio-room .pill.warn{background:rgba(245,158,11,.15);color:#fcd34d;}
        #visio-room .pill.err{background:rgba(225,29,72,.2);color:#fda4af;}
        #visio-room .v-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;border-radius:10px;
            font-weight:700;font-size:13.5px;padding:11px 16px;color:var(--vtext);transition:background .15s,filter .15s;}
        #visio-room .v-btn:hover{filter:brightness(1.12);}
        #visio-room .v-btn.gold{background:var(--vgold);color:var(--vblue-d);}
        /* ⚠️ L'action courante est BLEUE, pas or : l'or est un accent de la charte,
           et un gros aplat jaune dans une salle sombre tire l'œil plus que ce qui
           se passe à l'écran. L'or reste pour signaler, pas pour agir. */
        #visio-room .v-btn.blue{background:var(--vblue);color:#fff;}
        #visio-room .v-btn.ghost{background:rgba(255,255,255,.06);border:1px solid var(--vborder);}
        #visio-room .v-btn.ghost:hover{background:rgba(255,255,255,.12);}
        #visio-room .v-btn.danger{background:var(--vdanger);color:#fff;}
        #visio-room .v-btn.sm{padding:8px 12px;font-size:12.5px;border-radius:8px;}
        /* ⚠️ Anneau de focus VISIBLE partout : une salle se pilote aussi au clavier,
           et sans lui on ne sait pas quel bouton on s'apprête à déclencher. */
        #visio-room button:focus-visible,#visio-room a:focus-visible,
        #visio-room input:focus-visible,#visio-room label:focus-within{
            outline:2px solid var(--vgold);outline-offset:2px;border-radius:8px;}
        /* Barres de défilement sombres : les barres claires par défaut tranchaient
           sur chaque panneau et trahissaient la page web sous l'application. */
        #visio-room *{scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.18) transparent;}
        #visio-room *::-webkit-scrollbar{width:8px;height:8px;}
        #visio-room *::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18);border-radius:50px;}
        #visio-room *::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.3);}
        #visio-room *::-webkit-scrollbar-track{background:transparent;}
        /* ⚠️ La largeur du panneau est une VARIABLE, réglable à la poignée : 340px
           fixes ne suffisaient pas. C'est là que vivent le chat, la liste des
           participants et le pointage de présence - un nom suivi de son
           institution y passait à la ligne trois fois. */
        #visio-room .v-main{flex:1;min-height:0;display:grid;grid-template-columns:1fr var(--vside);position:relative;}
        #visio-room .v-main.side-closed{grid-template-columns:1fr;}
        #visio-room .v-main.side-closed .side{display:none;}
        #visio-room .stage{min-width:0;min-height:0;display:flex;flex-direction:column;padding:12px 12px 8px;background:var(--vbg);}
        #visio-room .stage-main{flex:1;min-height:0;display:flex;flex-direction:column;position:relative;}
        #visio-room #stageGrid{flex:1;min-height:0;}
        #visio-room .stage-grid{display:grid;gap:10px;align-content:start;}
        #visio-room .stage-grid.speaker{grid-template-columns:1fr;grid-template-rows:1fr;}
        #visio-room .stage-grid.speaker .tile.main{aspect-ratio:auto;height:100%;border-radius:16px;}
        /* ⚠️ Mosaïque : le nombre de COLONNES est calculé (cf. layoutGrid()), il
           n'est pas laissé à `auto-fit`. Avec quatre personnes, `auto-fit` les
           alignait sur une seule rangée : des tuiles hautes et étroites, où un
           visage filmé en 16/9 était rogné jusqu'au nez. On cherche la grille qui
           donne les plus grandes tuiles proches du 16/9, et on la centre. */
        /* ⚠️ Flex et non grid : avec trois personnes sur une grille 2×2, la
           quatrième cellule restait vide et la dernière tuile se retrouvait collée
           à gauche d'un trou. En flex, la rangée incomplète se CENTRE. */
        #visio-room .stage-grid:not(.speaker){display:flex;flex-wrap:wrap;gap:10px;height:100%;
            align-content:center;justify-content:center;}
        #visio-room .stage-grid:not(.speaker) .tile{aspect-ratio:auto;min-height:0;
            flex:0 0 calc((100% - (var(--cols,2) - 1) * 10px) / var(--cols,2));
            height:calc((100% - (var(--rows,1) - 1) * 10px) / var(--rows,1));}
        /* La tuile « scène » ne sert qu'à la vue présentateur et au partage
           d'écran : en mosaïque et sans image, elle ajoutait un cadre
           « Présentateur - en attente » qui ne correspondait à personne. */
        #visio-room .stage-grid:not(.speaker) .tile.main:not(.has-video):not(.has-screen){display:none;}
        /* Partage d'écran en mosaïque : l'écran prend toute la scène - c'est ce
           qu'on est venu lire, et le réduire à une vignette parmi d'autres le
           rendrait illisible. ⚠️ Règles en flex, la mosaïque n'étant plus une
           grille : `grid-template-*` n'y aurait aucun effet. */
        #visio-room .stage-grid:not(.speaker).has-screen-share .tile{display:none;}
        #visio-room .stage-grid:not(.speaker).has-screen-share .tile.main{display:flex;flex:0 0 100%;height:100%;}
        /* ⚠️ Un écran partagé se montre ENTIER, jamais rogné. Les tuiles sont en
           `cover` - c'est juste pour un visage, qu'on cadre - mais sur un écran
           cela coupe les bords : barres d'outils, bas de diapositive, dernière
           ligne d'un tableau. Avec un écran 16/9 dans une scène large, c'est près
           d'un quart de la hauteur qui disparaissait. La règle porte sur la TUILE
           et non sur la disposition : le partage est tout aussi illisible rogné en
           vue présentateur qu'en mosaïque. `>` pour ne pas toucher la vignette
           caméra (PiP), qui elle se cadre. */
        #visio-room .tile.main.has-screen > video{object-fit:contain!important;background:#000;}
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
        /* ⚠️ Sur les vignettes, l'étiquette doit se faire oublier : à 12,5px elle
           occupait la moitié de la largeur et tronquait les noms au tiers. La
           scène garde sa taille - c'est elle qu'on regarde. */
        #visio-room .tile:not(.main) .name{display:inline-flex;align-items:center;gap:6px;
            font-size:11px;padding:4px 8px;border-radius:7px;}
        #visio-room .tile .name .nm{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        /* État du micro, posé là où l'œil cherche déjà le nom. Cliquable par le
           modérateur (et par chacun sur sa propre vignette) : couper un micro
           demandait d'ouvrir le panneau, de trouver la personne, puis le bouton. */
        #visio-room .mic-dot{flex-shrink:0;width:19px;height:19px;padding:0;border:none;border-radius:50%;
            background:transparent;color:rgba(255,255,255,.92);display:inline-flex;align-items:center;justify-content:center;}
        #visio-room .mic-dot svg{width:14px;height:14px;display:block;}
        #visio-room .mic-dot.off{color:#fda4af;}
        #visio-room .mic-dot:not(:disabled):hover{background:rgba(255,255,255,.2);}
        #visio-room .mic-dot:disabled{cursor:default;}
        #visio-room .tile .badges{position:absolute;top:10px;right:10px;z-index:3;display:flex;gap:4px;}
        #visio-room .tile .badges span{font-size:10px;font-weight:800;padding:4px 8px;border-radius:50px;background:rgba(0,0,0,.55);}
        /* Bloc nom + indication : placé SOUS l'avatar centré (72px) pour ne pas se superposer. */
        #visio-room .info-block{position:absolute;top:50%;left:50%;transform:translate(-50%,52px);z-index:2;text-align:center;color:var(--vtext);width:max-content;max-width:min(90%,420px);padding:0 12px;}
        #visio-room .info-block .info-name{font-size:1.1rem;font-weight:800;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        #visio-room .info-block .info-meta{font-size:.9rem;color:var(--vmuted);margin-top:4px;line-height:1.4;}
        #visio-room .pip-dropzone{display:none;position:absolute;top:10px;left:50%;transform:translateX(-50%);z-index:22;background:rgba(0,0,0,.6);border:1px dashed rgba(255,255,255,.4);border-radius:10px;padding:6px 12px;font-size:12px;color:#fff;}
        #visio-room .pip-dropzone.visible{display:block;}
        #visio-room .pip-dropzone.active{border-color:var(--vgold);color:var(--vgold);}
        #visio-room .pip-bubble{position:absolute;width:120px;height:120px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,215,0,.75);box-shadow:0 8px 28px rgba(0,0,0,.45);z-index:20;background:#1c2438;cursor:grab;user-select:none;right:16px;bottom:16px;}
        #visio-room .pip-bubble.dragging{cursor:grabbing;}
        #visio-room .pip-bubble .pip-avatar{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.4rem;color:#fff;background:linear-gradient(135deg,var(--vblue),var(--vgreen));}
        #visio-room .pip-bubble.has-video .pip-avatar{display:none;}
        /* ⚠️ La poignée était posée dans le COIN du cadre (right/bottom:4px). Or la
           vignette est un DISQUE en `overflow:hidden` : ce coin tombe hors du
           disque, la poignée était donc rognée - invisible et impossible à
           attraper. Elle se place maintenant sur le disque lui-même, et se montre
           au survol pour ne pas encombrer l'image en permanence. */
        /* ⚠️ 14 % et pas moins : la poignée est posée sur la DIAGONALE, où elle
           s'éloigne du centre en 0,55·taille quand le disque, lui, ne grandit
           qu'en 0,5·taille. En deçà de cet encart, elle ressort du disque aux
           grandes tailles et le masque circulaire la rogne. */
        #visio-room .pip-resize{position:absolute;right:14%;bottom:14%;z-index:5;width:24px;height:24px;border-radius:50%;
            background:rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.45);cursor:nwse-resize;
            display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s,transform .15s;}
        #visio-room .pip-bubble:hover .pip-resize,#visio-room .pip-bubble.resizing .pip-resize{opacity:1;}
        #visio-room .pip-resize:hover{transform:scale(1.12);}
        /* ⚠️ Double flèche diagonale, et non un coin de fenêtre : un disque n'a pas
           de coin, le geste va dans les DEUX sens (tirer pour agrandir, pousser
           pour réduire), et c'est déjà ce que montre le curseur `nwse-resize` -
           icône et curseur doivent raconter la même chose. */
        #visio-room .pip-resize svg{width:13px;height:13px;display:block;color:rgba(255,255,255,.95);}
        #visio-room .pip-size-hint{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,.72);color:#fff;font-size:11px;font-weight:700;padding:4px 9px;border-radius:50px;z-index:6;display:none;white-space:nowrap;}
        #visio-room .pip-bubble.resizing .pip-size-hint{display:block;}
        /* ⚠️ AU DOIGT, deux choses tombent en même temps : la cible de 24 px est
           sous le minimum visable (~44 px), et surtout **le survol n'existe pas**,
           donc une poignée révélée au survol reste tout simplement INVISIBLE. Elle
           s'affiche donc en permanence et s'agrandit. `touch-action:none` empêche
           le navigateur de prendre le geste pour un défilement de page : sans lui,
           tirer la poignée fait glisser la page au lieu de redimensionner. */
        @@media (pointer: coarse) {
            #visio-room .pip-resize{width:40px;height:40px;opacity:1;}
            #visio-room .pip-resize svg{width:18px;height:18px;}
            #visio-room .pip-bubble,#visio-room .pip-resize{touch-action:none;}
        }
        #visio-room .side{position:relative;border-left:1px solid var(--vborder);background:var(--vbg-panel);display:flex;flex-direction:column;min-height:0;}
        /* Poignée de largeur : une bande d'attrape large (9px) pour un trait fin.
           ⚠️ La bande visible ne fait que 3px : une poignée épaisse couperait la
           scène en deux en permanence, alors qu'on ne s'en sert qu'une fois. */
        #visio-room .side-grip{position:absolute;left:-4px;top:0;bottom:0;width:9px;z-index:26;cursor:col-resize;}
        #visio-room .side-grip::before{content:'';position:absolute;left:3px;top:0;bottom:0;width:3px;
            background:transparent;transition:background .15s;}
        #visio-room .side-grip:hover::before,#visio-room .side-grip.on::before{background:rgba(255,215,0,.55);}
        #visio-room .side-grip:focus-visible{outline:2px solid var(--vgold);outline-offset:-2px;}
        /* Au doigt : bande d'attrape élargie, et le geste ne doit pas être pris
           pour un défilement de page. */
        @@media (pointer:coarse){#visio-room .side-grip{width:20px;left:-10px;touch-action:none;}
            #visio-room .side-grip::before{left:8px;}}
        /* La bordure passe sur l'en-tête : la croix de fermeture est à côté des
           onglets, pas dedans, pour ne pas défiler avec eux. */
        #visio-room .side-head{display:flex;flex-shrink:0;min-width:0;border-bottom:1px solid var(--vborder);}
        #visio-room .side-tabs{display:flex;flex:1;min-width:0;padding:0 8px;}
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
        /* ⚠️ Une ligne par personne, lue d'un seul balayage : avatar, identité, et
           l'action à droite. Le bouton de micro était SOUS le nom, poussant chaque
           personne sur trois lignes - quatre participants et la liste défilait
           déjà. */
        #visio-room .person{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vborder);}
        #visio-room .person:last-child{border-bottom:none;}
        #visio-room .person .av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--vblue),var(--vgreen));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
        #visio-room .person .meta{flex:1;min-width:0;}
        #visio-room .person .meta strong{display:block;font-size:13.5px;font-weight:700;}
        #visio-room .person .meta span{font-size:11.5px;color:var(--vmuted);}
                /* `margin-left:auto` : les actions se collent à droite quelle que soit la
           longueur du nom. Elles passent dessous seulement si la largeur du
           panneau ne les tient plus - il se règle (cf. --vside). */
        #visio-room .mod-actions{display:flex;flex-wrap:wrap;gap:5px;margin-left:auto;
            justify-content:flex-end;flex-shrink:0;max-width:55%;}
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
        #visio-room .toolbar{flex-shrink:0;height:var(--vtoolbar);background:var(--vbg-elev);border-top:1px solid var(--vborder);display:flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;}
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
        #visio-room .menu{display:none;position:absolute;bottom:calc(var(--vtoolbar) + 12px);left:50%;transform:translateX(-50%);background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:16px;padding:14px;z-index:45;box-shadow:0 16px 48px rgba(0,0,0,.45);width:min(340px,92vw);}
        #visio-room .menu.open{display:block;}
        #visio-room .menu h4{font-size:12px;font-weight:800;color:var(--vmuted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;}
        #visio-room .bg-options{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
        #visio-room .bg-opt{border:2px solid transparent;border-radius:12px;overflow:hidden;cursor:pointer;background:var(--vbg-tile);padding:0;aspect-ratio:1;position:relative;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--vmuted);}
        #visio-room .bg-opt:hover{border-color:rgba(255,255,255,.25);}
        #visio-room .bg-opt.active{border-color:var(--vgold);color:var(--vtext);}
        #visio-room .bg-opt.bg-upload{flex-direction:column;gap:2px;}
        #visio-room .bg-opt.bg-upload span{font-size:10px;line-height:1.2;text-align:center;padding:0 4px;}
        #visio-room .share-menu button{display:flex;width:100%;align-items:center;gap:10px;padding:12px 14px;border:none;border-radius:10px;background:transparent;color:var(--vtext);font-weight:700;font-size:13.5px;text-align:left;}
        #visio-room .share-menu button:hover{background:rgba(255,255,255,.08);}
        #visio-room .share-menu .hint{font-size:11.5px;color:var(--vmuted);padding:8px 10px 4px;line-height:1.4;}
        /* --- Présence tenue dans la salle (modérateur / rapporteur) --- */
        #visio-room .presence-counts{display:flex;flex-wrap:wrap;gap:6px;padding:12px 14px 4px;}
        #visio-room .presence-counts span{font-size:11.5px;font-weight:800;padding:3px 9px;border-radius:999px;background:rgba(255,255,255,.07);color:var(--vmuted);}
        #visio-room .presence-counts span b{color:var(--vtext);}
        #visio-room .presence-list{flex:1;overflow-y:auto;padding:8px 14px 0;display:flex;flex-direction:column;gap:8px;}
        #visio-room .pr-item{background:var(--vbg-tile);border:1px solid var(--vborder);border-radius:12px;padding:10px 12px;}
        #visio-room .pr-item .nm{font-weight:700;font-size:13.5px;}
        #visio-room .pr-item .meta{font-size:11.5px;color:var(--vmuted);margin-top:2px;}
        /* Qui est DANS la salle : l'information qui évite d'appeler quelqu'un déjà là. */
        #visio-room .pr-item .in-room{color:#4ade80;font-weight:700;}
        #visio-room .pr-acts{display:flex;gap:6px;margin-top:8px;}
        #visio-room .pr-acts button{flex:1;padding:6px 4px;border-radius:8px;border:1px solid var(--vborder);background:transparent;color:var(--vmuted);font-size:11.5px;font-weight:700;cursor:pointer;}
        #visio-room .pr-acts button:hover{color:var(--vtext);background:rgba(255,255,255,.06);}
        #visio-room .pr-acts button.on[data-st=present]{background:#16a34a;border-color:#16a34a;color:#fff;}
        #visio-room .pr-acts button.on[data-st=absent]{background:#dc2626;border-color:#dc2626;color:#fff;}
        #visio-room .pr-acts button.on[data-st=excused]{background:var(--vgold);border-color:var(--vgold);color:#2b1f00;}
        #visio-room .pr-empty{padding:16px 14px;font-size:12.5px;color:var(--vmuted);line-height:1.5;}
        #visio-room .vol-panel{display:none;position:absolute;bottom:calc(var(--vtoolbar) + 12px);left:50%;transform:translateX(-50%);background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:14px;padding:12px 16px;z-index:46;box-shadow:0 12px 40px rgba(0,0,0,.4);align-items:center;gap:10px;min-width:220px;}
        #visio-room .vol-panel.open{display:flex;}
        #visio-room .vol-panel label{font-size:12px;font-weight:700;color:var(--vmuted);}
        #visio-room .vol-panel input[type=range]{flex:1;accent-color:var(--vgold);}
        #visio-room .vol-panel .vol-val{font-size:12px;font-weight:800;min-width:36px;text-align:right;}
        #visio-room .perm-banner{display:none;position:absolute;top:calc(var(--vtop) + 8px);left:50%;transform:translateX(-50%);z-index:70;width:min(420px,92vw);background:var(--vbg-elev);border:1px solid rgba(255,215,0,.35);border-radius:14px;padding:14px 16px;box-shadow:0 12px 40px rgba(0,0,0,.4);font-size:13px;line-height:1.45;}
        #visio-room .perm-banner.on{display:block;}
        #visio-room .perm-banner strong{color:var(--vgold);}
        #visio-room .perm-banner .actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
        #visio-room .perm-banner .actions button{border:none;border-radius:8px;padding:8px 12px;font-weight:700;font-size:12.5px;}
        #visio-room .perm-banner .actions .p1{background:var(--vgold);color:var(--vblue-d);}
        #visio-room .perm-banner .actions .p2{background:rgba(255,255,255,.08);color:var(--vtext);border:1px solid var(--vborder);}
        #visio-room .v-toast{position:absolute;bottom:calc(var(--vtoolbar) + 16px);left:50%;transform:translateX(-50%) translateY(20px);z-index:90;background:rgba(15,18,28,.96);color:var(--vtext);border:1px solid var(--vborder);border-radius:10px;padding:10px 16px;font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:.25s;}
        #visio-room .v-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
        /* ⚠️ L'état de connexion se posait au MILIEU de la scène, à côté du nom du
           présentateur : deux informations sans rapport collées l'une à l'autre.
           Il devient un bandeau discret en haut, qui n'occupe pas la place de ce
           qu'on est venu regarder. */
        /* ⚠️ Décalé de la hauteur de la barre : posé à 14px du haut, il se
           superposait au titre de la séance. Il appartient à la scène. */
        #visio-room .v-status{position:absolute;top:calc(var(--vtop) + 16px);left:50%;transform:translateX(-50%);z-index:30;
            display:none;align-items:center;gap:9px;color:#c7d1e6;background:rgba(10,14,23,.82);
            border:1px solid var(--vborder);backdrop-filter:blur(6px);padding:8px 14px;border-radius:50px;
            font-size:12.5px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.35);}
        #visio-room .v-status.on{display:inline-flex;}
        #visio-room .v-status::before{content:'';width:12px;height:12px;border-radius:50%;flex-shrink:0;
            border:2px solid rgba(199,209,230,.25);border-top-color:#c7d1e6;animation:vspin .7s linear infinite;}
        @@keyframes vspin{to{transform:rotate(360deg);}}
        /* --- Boîte de confirmation ---
           ⚠️ Pas le `confirm()` du navigateur : il sort visuellement de la salle,
           gèle la page (donc la visio continue sans qu'on puisse rien faire) et
           n'est pas garanti dans un contexte embarqué. */
        #visio-room .v-modal{display:none;position:absolute;inset:0;z-index:95;padding:20px;
            background:rgba(4,7,13,.62);align-items:center;justify-content:center;}
        #visio-room .v-modal.on{display:flex;}
        #visio-room .v-modal-box{background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:16px;
            padding:20px;width:min(430px,100%);box-shadow:0 24px 64px rgba(0,0,0,.5);}
        #visio-room .v-modal-box h4{font-size:15px;font-weight:800;margin-bottom:8px;}
        #visio-room .v-modal-box p{font-size:13.5px;line-height:1.5;color:var(--vmuted);}
        #visio-room .v-modal-box p strong{color:var(--vtext);}
        #visio-room .v-modal-acts{display:flex;justify-content:flex-end;gap:8px;margin-top:18px;flex-wrap:wrap;}
        /* --- Mains levées ---
           ⚠️ Une main levée dans un panneau fermé n'existe pas : elle doit se voir
           sur la TUILE. Et son NUMÉRO dit qui a demandé la parole en premier -
           sans lui, le modérateur donne la parole dans l'ordre où son œil tombe. */
        #visio-room .tile.hand-up{border-color:rgba(255,215,0,.65);box-shadow:0 0 0 2px rgba(255,215,0,.22);}
        #visio-room .hand-badge{position:absolute;top:10px;left:10px;z-index:4;background:var(--vgold);
            color:var(--vblue-d);font-size:11px;font-weight:900;padding:4px 9px;border-radius:50px;}
        #visio-room .person.hand-up{background:rgba(255,215,0,.07);border-radius:10px;padding-left:8px;padding-right:8px;}
        #visio-room .person .rank{background:var(--vgold);color:var(--vblue-d);font-size:10.5px;font-weight:900;
            padding:2px 7px;border-radius:50px;}
        /* Compteur sur l'onglet : le panneau est souvent fermé, et toujours sur
           un autre onglet que celui des participants. */
        #visio-room .tab-count{display:none;margin-left:6px;background:var(--vgold);color:var(--vblue-d);
            font-size:10.5px;font-weight:900;padding:1px 7px;border-radius:50px;}
        #visio-room .tab-count.on{display:inline-block;}
        /* État du micro : lu de la salle, jamais deviné (cf. syncMicStates). */
        #visio-room .person .mic{font-weight:700;}
        #visio-room .person .mic.on{color:#6ee7b7;}
        #visio-room .person .mic.off{color:#fda4af;}
        /* --- Voile du tiroir (téléphone / tablette) ---
           Le panneau recouvre la scène : il faut voir qu'il est AU-DESSUS, et
           qu'un appui à côté le referme. */
        #visio-room .side-scrim{display:none;position:absolute;inset:0;z-index:24;
            background:rgba(4,7,13,.55);opacity:0;pointer-events:none;transition:opacity .22s;}
        #visio-room .side-scrim.on{opacity:1;pointer-events:auto;}
        /* Croix de fermeture : quand le panneau occupe tout l'écran, repartir
           chercher « Panneau » dans la barre du haut n'est plus le geste évident. */
        #visio-room .side-close{display:none;flex:0 0 auto;align-items:center;justify-content:center;
            padding:0 14px;border:none;background:transparent;color:var(--vmuted);font-size:16px;font-weight:700;}
        #visio-room .side-close:hover{color:var(--vtext);}
        /* --- Feuille « Plus d'options » (barres compactes) ---
           ⚠️ Les commandes n'y sont pas RECOPIÉES : ce sont les mêmes boutons,
           déplacés par le script (cf. rangeSecondaires). Un second jeu voudrait
           dire deux écouteurs et deux états à tenir d'accord (micro coupé,
           enregistrement en cours…). Leur `title` sert d'étiquette : rien à écrire
           deux fois, rien à tenir à jour en double. */
        #visio-room .ctrl.more{display:none;}
        #visio-room .more-sheet .more-slot{display:flex;flex-direction:column;gap:6px;}
        #visio-room .more-sheet .ctrl{width:100%;height:auto;border-radius:12px;justify-content:flex-start;
            gap:12px;padding:12px 14px;background:rgba(255,255,255,.06);}
        #visio-room .more-sheet .ctrl::after{content:attr(title);font-size:13.5px;font-weight:700;}
        #visio-room .more-sheet .ctrl:disabled::after{opacity:.6;}
        #visio-room .more-sheet .v-btn{width:100%;justify-content:flex-start;padding:12px 14px;font-size:13.5px;}

        /* ===================== Tablette et téléphone =====================
           ⚠️ Ce bloc vient EN DERNIER, après toutes les règles de base, et c'est
           la correction de fond : l'adaptation ≤900px était écrite AVANT
           `.side{display:flex}`. À spécificité égale, c'est la dernière règle qui
           gagne - donc la base rouvrait le tiroir. Sur téléphone, le panneau
           latéral s'affichait tout seul par-dessus les visages dès l'entrée dans
           la salle, et la bascule marchait à l'envers. */
        @@media (max-width:900px) {
            #visio-room .v-main{grid-template-columns:1fr;}
            /* Tiroir qui glisse par-dessus la scène. `visibility` en plus de
               `transform` : fermé, il ne doit pas garder ses champs dans l'ordre
               de tabulation - on tabulait jusque dans un chat invisible. */
            #visio-room .v-main .side,
            #visio-room .v-main.side-closed .side{
                display:flex;position:absolute;top:0;bottom:0;right:0;left:auto;z-index:25;
                width:min(400px,100%);box-shadow:-14px 0 44px rgba(0,0,0,.55);
                transform:translateX(100%);visibility:hidden;
                transition:transform .22s ease,visibility .22s ease;}
            #visio-room .v-main .side.open,
            #visio-room .v-main.side-closed .side.open{transform:none;visibility:visible;}
            #visio-room .side-scrim{display:block;}
            /* Un tiroir qui recouvre déjà toute la scène ne s'élargit pas. */
            #visio-room .side-grip{display:none;}
            #visio-room .side-close{display:inline-flex;}
            /* Quatre onglets ne tiennent plus côte à côte : ils défilent plutôt
               que de s'écraser sur deux lettres chacun. */
            #visio-room .side-tabs{overflow-x:auto;}
            #visio-room .side-tabs button{flex:0 0 auto;white-space:nowrap;padding:14px 12px;}
        }

        /* --- Barre d'outils compacte (sous la tablette en portrait) ---
           ⚠️ Seuil à 767px et non 640 : les dix commandes réclament près de 700px,
           ce que l'iPad en portrait (768) offre encore, mais pas une tablette plus
           étroite - les boutons des deux bords y étaient rognés. */
        @@media (max-width:767px) {
            #visio-room{--vtoolbar:68px;}
            #visio-room .toolbar{gap:6px;padding:0 10px;}
            #visio-room .toolbar .sep{display:none;}
            #visio-room .ctrl{width:44px;height:44px;}
            #visio-room .ctrl svg{width:21px;height:21px;}
            #visio-room .ctrl.more{display:inline-flex;}
            /* En rond comme les autres : l'étiquette coûtait 60px de barre, et le
               rouge dit déjà ce que fait ce bouton. */
            #visio-room .ctrl.leave{width:44px;padding:0;border-radius:50%;}
            #visio-room .ctrl.leave span{display:none;}
        }

        /* --- Téléphone : la barre du haut ne garde que ce qui situe la séance --- */
        @@media (max-width:640px) {
            #visio-room{--vtop:52px;}
            #visio-room .v-topbar{padding:0 10px;gap:10px;}
            #visio-room .v-mark{width:30px;height:30px;border-radius:8px;}
            #visio-room .v-title strong{font-size:12.5px;}
            #visio-room .v-title span{font-size:10.5px;}
            #visio-room .v-right{gap:6px;}
            /* ⚠️ La droite réclamait 405px sur 375 : le titre tombait à zéro et
               « Quitter » sortait de l'écran. La pastille garde sa puce - l'état se
               lit à la couleur - et perd son texte (repris dans son `title`). */
            #visio-room .v-right .pill{font-size:0;gap:0;padding:7px;}
            #visio-room .v-right .pill::before{width:9px;height:9px;}
            /* Quitter est déjà en rouge, en grand, dans la barre du bas. */
            #visio-room #btnLeaveTop{display:none;}
            #visio-room .stage{padding:8px 8px 6px;}
            /* Menus et bandeaux en feuille pleine largeur : centrés sur 340px, ils
               laissaient deux liserés inutiles et débordaient en hauteur. */
            #visio-room .menu,#visio-room .vol-panel{left:8px;right:8px;width:auto;
                transform:none;max-height:62vh;overflow-y:auto;}
            #visio-room .perm-banner{left:8px;right:8px;width:auto;transform:none;}
            #visio-room .bg-options{grid-template-columns:repeat(4,1fr);}
            #visio-room .tile .avatar{width:56px;height:56px;font-size:1.1rem;}
            #visio-room .info-block{transform:translate(-50%,44px);}
            #visio-room .info-block .info-name{font-size:1rem;}
            #visio-room .info-block .info-meta{font-size:.82rem;}
            #visio-room .filmstrip .tile{flex:0 0 118px;width:118px;}
        }

        /* --- Téléphone couché : 375px de haut, dont 136 pour les barres ---
           C'est l'orientation dans laquelle on regarde une visio : les barres se
           font fines pour rendre la hauteur à la scène. */
        @@media (max-height:480px) and (orientation:landscape) {
            #visio-room{--vtop:44px;--vtoolbar:56px;}
            #visio-room .v-title span{display:none;}
            #visio-room .ctrl{width:40px;height:40px;}
            #visio-room .ctrl svg{width:19px;height:19px;}
            #visio-room .ctrl.leave{width:40px;}
            #visio-room .stage{padding:6px 8px 4px;}
            #visio-room .filmstrip .tile{flex:0 0 96px;width:96px;}
            #visio-room .filmstrip-toggle{padding:5px 12px;}
            #visio-room .filmstrip-wrap .filmstrip{padding:0 12px 8px;}
        }
    </style>

    <div id="visio-room" data-role="{{ $liveKit['role'] }}">
        <header class="v-topbar">
            <div class="v-left">
                {{-- Logo réglé dans Réglages › Général, forme compacte et variante
                     fond sombre : la barre de la salle est sombre. Un monogramme
                     « UK » écrit en dur ici faisait mentir le logo de la plateforme
                     partout ailleurs. --}}
                <span class="v-mark">
                    <img src="{{ \App\Support\Branding::markWhite() }}" alt="{{ config('app.name') }}"
                         @class(['logo-auto-white' => \App\Support\Branding::whitensOnDark(\App\Support\Branding::MARK)])>
                </span>
                <div class="v-title">
                    <strong id="roomTitle">{{ $liveKit['sessionLabel'] }}</strong>
                    {{-- ⚠️ Pas de nom de fournisseur ici : un participant n'a que
                         faire de savoir quel SFU porte la salle, et il était écrit
                         deux fois (ligne de titre + pastille). --}}
                    <span id="roomMeta">{{ $liveKit['editionTitle'] }}</span>
                </div>
            </div>
            <div class="v-right">
                <span class="pill warn" id="connPill">Connexion…</span>
                <button type="button" class="v-btn ghost sm" id="btnToggleSide">Panneau</button>
                @if ($engagementUrl)
                    <a href="{{ $engagementUrl }}" class="v-btn ghost sm" id="btnEngagement">Questions &amp; sondages</a>
                @endif
                <a href="{{ $liveKit['backUrl'] }}" class="v-btn danger sm" id="btnLeaveTop">Quitter</a>
            </div>
        </header>

        <div class="v-main" id="mainLayout">
            {{-- Voile du tiroir : n'existe qu'en dessous de 900px (cf. style). --}}
            <div class="side-scrim" id="sideScrim"></div>
            <div class="stage">
                <div class="stage-main" id="stageMain">
                    <div class="stage-grid speaker" id="stageGrid">
                        <div class="tile main is-presenter" id="tileMain">
                            <div class="avatar" id="mainAvatar">?</div>
                            <div class="info-block" id="mainInfo" style="display:none;">
                                <div class="info-name" id="mainInfoName">-</div>
                                <div class="info-meta" id="mainInfoMeta">-</div>
                            </div>
                            <div class="name" id="mainName">Présentateur</div>
                            <div class="badges"><span id="mainRole">-</span></div>
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
                            <div class="name"><span class="nm" id="localName">-</span>
                                <button type="button" class="mic-dot" id="localMic" title="Micro"></button></div>
                            <div class="badges"><span id="localRole">Vous</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="side" id="sidePanel">
                <div class="side-grip" id="sideGrip" role="separator" aria-orientation="vertical" tabindex="0"
                     aria-label="Largeur du panneau"
                     title="Glisser pour élargir · flèches gauche/droite · double-clic pour revenir à la largeur normale"></div>
                <div class="side-head">
                <div class="side-tabs">
                    <button type="button" class="active" data-side="chat">Chat</button>
                    <button type="button" data-side="people">Participants<span class="tab-count" id="handCount"></span></button>
                    <button type="button" data-side="comms" id="tabComms" style="display:none;">Communicateurs</button>
                    {{-- Onglet réservé au modérateur / rapporteur de la séance (cf. cfg.presence). --}}
                    <button type="button" data-side="presence" id="tabPresence" style="display:none;">Présence</button>
                </div>
                {{-- Hors des onglets : ils défilent sur téléphone, la croix doit
                     rester en place. --}}
                <button type="button" class="side-close" id="btnCloseSide" title="Fermer le panneau" aria-label="Fermer le panneau">&times;</button>
                </div>
                <div class="side-panel active" id="panelChat">
                    <div class="chat-log" id="chatLog"></div>
                    <div class="chat-input">
                        <input type="text" id="chatInput" placeholder="Écrire un message…" autocomplete="off">
                        <button type="button" class="v-btn blue sm" id="btnSendChat">Envoyer</button>
                    </div>
                </div>
                <div class="side-panel" id="panelPeople"><div class="people-list" id="peopleList"></div></div>
                <div class="side-panel" id="panelComms">
                    <div style="padding:12px 14px 0;font-size:12px;color:var(--vmuted);line-height:1.4;">Communications programmées de la session. Un seul présentateur à la fois.</div>
                    <div class="comm-list" id="commList"></div>
                </div>
                <div class="side-panel" id="panelPresence">
                    <div style="padding:12px 14px 0;font-size:12px;color:var(--vmuted);line-height:1.4;">
                        Présence des auteurs des communications de la séance. Les connectés sont repérés automatiquement.
                    </div>
                    <div class="presence-counts" id="presenceCounts"></div>
                    <div class="presence-list" id="presenceList"></div>
                    <div style="padding:10px 14px 14px;">
                        <a class="v-btn sm" id="presencePage" href="#" target="_blank" rel="noopener">Ouvrir la page de présence</a>
                    </div>
                </div>
            </aside>
        </div>

        <div class="toolbar" id="visioToolbar">
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
            {{-- Visible seulement quand la barre se compacte : elle accueille
                 alors les commandes secondaires, déplacées telles quelles. --}}
            <button type="button" class="ctrl more" id="btnMore" title="Plus d’options" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
            <div class="sep"></div>
            <button type="button" class="ctrl leave" id="btnLeave">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> <span>Quitter</span>
            </button>
        </div>

        <div class="menu" id="bgMenu">
            <h4>Arrière-plan caméra</h4>
            <div class="bg-options">
                <button type="button" class="bg-opt active" data-bg="none">Aucun</button>
                <button type="button" class="bg-opt" data-bg="blur">Flou</button>
                @foreach ($backgrounds as $bg)
                    <button type="button" class="bg-opt bg-img" data-bg="{{ $bg }}" title="Arrière-plan"
                            style="background-image:url('{{ e($bg) }}');background-size:cover;background-position:center;"></button>
                @endforeach
                <label class="bg-opt bg-upload" title="Utiliser mon image">
                    <span>+ Mon image</span>
                    <input type="file" id="bgUpload" accept="image/*" hidden>
                </label>
            </div>
        </div>
        <div class="menu share-menu" id="shareMenu">
            <div class="hint">Pour un diaporama (PowerPoint, PDF…), choisissez la <strong>fenêtre de l’application</strong>.</div>
            <button type="button" data-share="window">Fenêtre d’application</button>
            <button type="button" data-share="monitor">Écran entier</button>
            <button type="button" data-share="browser">Onglet du navigateur</button>
            <button type="button" data-share="stop" id="shareStopBtn" style="display:none;color:#fda4af;">Arrêter le partage</button>
        </div>
        <div class="menu more-sheet" id="moreSheet">
            <h4>Plus d’options</h4>
            <div class="more-slot" id="moreSlot"></div>
        </div>
        <div class="vol-panel" id="volPanel">
            <label>Volume</label><input type="range" id="volRange" min="0" max="100" value="100"><span class="vol-val" id="volVal">100%</span>
        </div>
        <div class="perm-banner" id="permBanner">
            <strong>Permissions navigateur</strong>
            <div id="permBannerText" style="margin-top:6px;color:var(--vmuted);"></div>
            <div class="actions"><button type="button" class="p1" id="permRetry">Réessayer</button><button type="button" class="p2" id="permDismiss">Fermer</button></div>
        </div>
        {{-- Confirmation des gestes qu'on ne peut pas défaire. --}}
        <div class="v-modal" id="vModal" role="dialog" aria-modal="true" aria-labelledby="vModalTitle">
            <div class="v-modal-box">
                <h4 id="vModalTitle">Confirmer</h4>
                <p id="vModalText"></p>
                <div class="v-modal-acts">
                    <button type="button" class="v-btn ghost sm" id="vModalNo">Annuler</button>
                    <button type="button" class="v-btn blue sm" id="vModalYes">Confirmer</button>
                </div>
            </div>
        </div>
        <div class="v-status" id="lkStatus">Connexion à la salle…</div>
        <div class="v-toast" id="toast"></div>
    </div>

    <script type="application/json" id="lk-config">@json($liveKit)</script>
@else
    {{-- ============ Jitsi / lien externe / messages ============ --}}
    <div style="display:flex;flex-direction:column;height:100vh;">
        <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
                       padding:10px clamp(12px,3vw,24px);background:#fff;border-bottom:1px solid var(--ukc-border);">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    @if ($isLive)
                        <span class="ukc-pill ukc-pill--err" style="display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:currentColor;"></span> EN DIRECT
                        </span>
                    @endif
                    <strong style="font-size:1rem;">{{ $label }}</strong>
                </div>
                <div style="font-size:.8rem;color:var(--ukc-muted);">
                    {{ $editionTitle }}
                    @if ($role->canJoin()) · Vous rejoignez en tant que
                        <strong>{{ ['moderator' => 'modérateur', 'presenter' => 'présentateur', 'attendee' => 'participant'][$role->value] ?? '' }}</strong>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                @if ($engagementUrl)
                    <a href="{{ $engagementUrl }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="inbox" :size="16" /> Questions &amp; sondages
                    </a>
                @endif
                <a href="{{ $backUrl }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="close" :size="16" /> Quitter
                </a>
            </div>
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
                        api.addListener('readyToClose', function () { window.location = @js($backUrl); });
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
            @elseif ($isLive && $role->canJoin())
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
                        @if (! $isLive)
                            <h3 style="margin-bottom:6px;">La visio n’est pas en cours</h3>
                            <p style="color:var(--ukc-muted);">
                                {{ $statusLabel }}.
                                {{ $startsAt
                                    ? __('Revenez à l’heure prévue (:date).', ['date' => $startsAt->translatedFormat('d F, H:i')])
                                    : __('Revenez à l’heure prévue.') }}
                            </p>
                            @if ($recordingUrl)
                                <a href="{{ $recordingUrl }}" target="_blank" class="ukc-btn ukc-btn--blue" style="margin-top:14px;"><x-ukc.icon name="video" :size="16" /> Voir le replay</a>
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
