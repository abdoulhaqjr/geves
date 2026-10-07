<x-layouts.app :title="'Visio - ' . $label">
{{-- ⚠️ L'onglet se nomme pour pouvoir être RETROUVÉ. Les pages de la séance
     (présence…) ramènent vers la salle avec ce nom en `target` : le navigateur
     réutilise alors cet onglet-ci au lieu d'ouvrir une seconde salle - deux
     connexions, deux vignettes et de l'écho. Posé pour les DEUX lecteurs
     (LiveKit et Jitsi), sinon le retour marcherait selon le fournisseur. --}}
@if ($onglet)
    <script>try { window.name = @json($onglet); } catch (e) {}</script>
@endif
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
        /* ⚠️ Combien de personnes sont là ne se lisait NULLE PART en mosaïque : le
           compte du bandeau n'existe qu'en vue présentateur, et seulement une fois
           celui-ci déplié. Il est donc posé dans la barre du haut, qui ne disparaît
           dans aucune disposition ni à aucune largeur - c'est la seule chose de la
           salle dont on puisse dire cela. */
        #visio-room .v-count{display:inline-flex;align-items:center;gap:5px;flex-shrink:0;
            font-size:11.5px;font-weight:800;padding:5px 10px;border-radius:50px;
            background:rgba(255,255,255,.07);color:var(--vtext);}
        #visio-room .v-count svg{width:14px;height:14px;display:block;color:var(--vmuted);}
        #visio-room .v-count b{font-variant-numeric:tabular-nums;}
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
        /* ⚠️ Une TEINTE par personne, tirée de son nom (cf. teinteDuNom) : les
           pastilles portaient toutes le même dégradé, et huit caméras fermées
           faisaient huit disques identiques qu'on ne séparait qu'à deux lettres.
           Seule la teinte bouge : la saturation et une clarté BASSE sont fixées ici,
           pour que le blanc des initiales garde son contraste quelle que soit la
           couleur tirée - un jaune clair les effacerait. */
        #visio-room .tile .avatar,#visio-room .person .av,
        #visio-room .pip-bubble .pip-avatar{background:linear-gradient(135deg,
            hsl(var(--teinte,225) 58% 29%),hsl(calc(var(--teinte,225) + 32) 58% 21%));}
        #visio-room .tile .avatar{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.3rem;color:#fff;z-index:1;box-shadow:0 8px 24px rgba(0,0,0,.25);}
        #visio-room .tile.has-video .avatar{display:none!important;}
        #visio-room .tile video,#visio-room .tile .media{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#0a0e17;z-index:1;}
        /* ⚠️ Sa propre image se regarde en MIROIR. Sans cela, lever la main droite
           l'affiche à gauche, et montrer un document le présente retourné : on se
           corrige alors à l'envers, en cherchant de quel côté se déplacer. C'est ce
           que fait toute glace, et tout outil de visio.
           La règle ne porte QUE sur son reflet : jamais sur un partage d'écran, dont
           le texte partirait à l'envers, ni sur l'image des autres - eux, on les voit
           comme on les verrait dans la salle. */
        #visio-room #tileLocal > video,#visio-room .tile.main.soi > video{transform:scaleX(-1);}
        #visio-room .tile .name{position:absolute;left:10px;bottom:10px;z-index:3;background:rgba(0,0,0,.6);padding:5px 10px;border-radius:8px;font-size:12.5px;font-weight:700;max-width:calc(100% - 20px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        /* ⚠️ Sur les vignettes, l'étiquette doit se faire oublier : à 12,5px elle
           occupait la moitié de la largeur et tronquait les noms au tiers. La
           scène garde sa taille - c'est elle qu'on regarde. */
        /* ⚠️ Collée au bord bas, et non à 10px : une vignette cadre un buste, donc
           l'étiquette tombait en plein sur le menton. Les quelques pixels gagnés
           la font passer sous le visage. */
        #visio-room .tile:not(.main) .name{display:inline-flex;align-items:center;gap:6px;
            font-size:11px;padding:4px 8px;border-radius:7px;left:6px;bottom:4px;}
        #visio-room .tile .name .nm{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        /* ⚠️ Qui préside, lu sur la vignette. Les tuiles distantes ne portaient
           AUCUN rôle : en mosaïque, rien ne disait qui animait la séance. La
           pastille ne nomme que le modérateur et le communicateur - écrire
           « Auditeur » quarante fois n'apprend rien et mange la place du nom.
           Masquée dans le bandeau : à 150 px de large, le nom passe d'abord. */
        #visio-room .tile .name .role-chip{flex-shrink:0;font-size:9.5px;font-weight:800;
            padding:1px 6px;border-radius:50px;letter-spacing:.01em;}
        #visio-room .tile .name .role-chip.mod{background:rgba(255,255,255,.22);color:#fff;}
        #visio-room .tile .name .role-chip.pres{background:var(--vgold);color:var(--vblue-d);}
        #visio-room .filmstrip .tile .role-chip{display:none;}
        /* --- Le nom se replie dans le bandeau ---
           ⚠️ La carte n'y fait que 150px (118 puis 96 sur téléphone) : l'étiquette
           occupait presque toute la largeur pour un nom qu'elle tronquait de toute
           façon au tiers - « Modeste Mod… » n'apprend rien que la vignette ne dise
           déjà. Ne reste que l'état du micro, qui est ce qu'on surveille là ; le nom
           revient au survol de la carte, entier.
           ⚠️ `max-width` et non `display:none` : le nom RESTE dans la page, donc
           énonçable par un lecteur d'écran et trouvable par la recherche du
           navigateur. Il est replié, pas retiré.
           ⚠️ La marge négative annule l'écart du conteneur : sans elle, la pastille
           garderait 6px de vide à gauche du micro, pour un nom large de zéro.
           ⚠️ Le repli et le dépli ne vont pas à la même vitesse, et c'est voulu :
           le nom s'ouvre en .32s avec une courbe qui démarre doucement (il
           arrive, on le lit), et se replie en .16s (on a quitté la carte, on
           ne l'attend plus). L'opacité part AVANT la largeur au dépli, sans
           quoi le texte apparaît d'un bloc dans une pastille qui s'ouvre
           encore - c'est ce qui se lisait comme un à-coup.
           ⚠️ Un délai de .12s à l'ouverture : en longeant le bandeau, on
           survole cinq cartes en une seconde, et sans lui elles s'allumeraient
           toutes au passage. */
        #visio-room .filmstrip .tile .name .nm{max-width:0;opacity:0;margin-right:-6px;
            transition:max-width .16s ease-in,opacity .1s ease-in,margin-right .16s ease-in;}
        /* `focus-within` : au clavier, atteindre le bouton de micro doit montrer de
           QUI il s'agit - sans quoi on coupe le micro de quelqu'un sans le nommer. */
        #visio-room .filmstrip .tile:hover .name .nm,
        #visio-room .filmstrip .tile:focus-within .name .nm{max-width:150px;opacity:1;margin-right:0;
            transition:max-width .32s cubic-bezier(.22,1,.36,1) .12s,
                opacity .26s ease-out .18s,margin-right .32s cubic-bezier(.22,1,.36,1) .12s;}
        /* Le clavier, lui, n'a pas longé le bandeau : il a visé. Le nom doit
           être là tout de suite, sinon la tabulation donne l'impression d'un
           temps mort avant de savoir de qui il s'agit. */
        #visio-room .filmstrip .tile:focus-within .name .nm{transition-delay:0s;}
        /* ⚠️ Au doigt, le survol N'EXISTE PAS : un nom qui ne se montre qu'au survol
           serait simplement introuvable. Il reste donc affiché, comme avant. */
        @@media (pointer:coarse){
            #visio-room .filmstrip .tile .name .nm{max-width:150px;opacity:1;margin-right:0;}
        }
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
        #visio-room .pip-bubble .pip-avatar{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.4rem;color:#fff;}
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
        /* ⚠️ Les onglets DÉFILENT, à toutes les largeurs, et ne se partagent plus la
           place à parts égales : ils sont jusqu'à cinq (chat, participants,
           échanges, communicateurs, présence) et « Communicateurs » à 80px se
           serait coupé en plein mot. C'était déjà la règle sous 900px. */
        #visio-room .side-tabs{display:flex;flex:1;min-width:0;padding:0 8px;overflow-x:auto;}
        #visio-room .side-tabs button{flex:0 0 auto;white-space:nowrap;padding:14px 11px;border:none;background:transparent;color:var(--vmuted);font-weight:700;font-size:13px;border-bottom:2px solid transparent;margin-bottom:-1px;}
        #visio-room .side-tabs button:hover{color:var(--vtext);}
        #visio-room .side-tabs button.active{color:var(--vtext);border-bottom-color:var(--vgold);}
        #visio-room .side-panel{display:none;flex:1;min-height:0;flex-direction:column;}
        #visio-room .side-panel.active{display:flex;}
        #visio-room .chat-log{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;}
        /* ⚠️ Le fil se recadrait de FORCE à chaque message : on remontait lire ce qui
           s'était dit, un message arrivait, et la lecture était arrachée vers le bas.
           Il ne suit plus que si on y était déjà ; sinon ce bandeau annonce ce qui
           attend en bas. En PLEINE LARGEUR et dans le flux, et non en pastille
           flottante : au-dessus du champ viennent déjà se poser la citation d'une
           réponse et les outils du modérateur, qu'une pastille recouvrirait. */
        #visio-room .chat-bas{display:none;width:100%;border:none;border-top:1px solid var(--vborder);
            background:rgba(255,215,0,.1);color:var(--vgold);font-size:11.5px;font-weight:800;padding:7px 12px;}
        #visio-room .chat-bas.on{display:block;}
        #visio-room .chat-bas:hover{background:rgba(255,215,0,.18);}
        #visio-room .chat-msg{position:relative;background:rgba(255,255,255,.04);border-radius:12px;padding:10px 12px;font-size:13.5px;line-height:1.45;}
        #visio-room .chat-msg .txt{white-space:pre-wrap;overflow-wrap:anywhere;}
        /* Heure du message : discrète, mais c'est elle qui situe un fil repris en
           cours de séance. */
        #visio-room .chat-msg .who{display:flex;align-items:baseline;gap:7px;}
        #visio-room .chat-msg .who .at{font-size:10px;font-weight:600;color:var(--vmuted);}
        /* Repère entre le fil rejoué et la séance en cours. */
        #visio-room .chat-sep{display:flex;align-items:center;gap:8px;margin:2px 0;
            font-size:10.5px;font-weight:700;color:var(--vmuted);text-transform:uppercase;letter-spacing:.04em;}
        #visio-room .chat-sep::before,#visio-room .chat-sep::after{content:'';flex:1;height:1px;background:var(--vborder);}
        /* Réactions : des pastilles sous le message, la sienne mise en avant. */
        #visio-room .chat-msg .reacts{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px;}
        #visio-room .chat-msg .chip{border:1px solid var(--vborder);background:rgba(255,255,255,.06);
            color:var(--vtext);font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:50px;line-height:1.5;}
        #visio-room .chat-msg .chip:hover{background:rgba(255,255,255,.14);}
        #visio-room .chat-msg .chip.mine{background:rgba(34,62,164,.55);border-color:rgba(255,215,0,.45);}
        /* Les deux actions d'un message, groupées en haut à droite. */
        #visio-room .chat-msg .acts-msg{position:absolute;top:6px;right:6px;display:flex;gap:4px;
            opacity:0;transition:opacity .15s;}
        #visio-room .chat-msg:hover .acts-msg,#visio-room .chat-msg .acts-msg:focus-within{opacity:1;}
        @@media (pointer:coarse){#visio-room .chat-msg .acts-msg{opacity:1;}}
        #visio-room .chat-msg .acts-msg button{border:none;border-radius:50px;background:rgba(255,255,255,.1);
            color:var(--vmuted);font-size:10.5px;font-weight:700;padding:3px 8px;}
        #visio-room .chat-msg .acts-msg button:hover{color:var(--vtext);background:rgba(255,255,255,.18);}
        /* Choix de la réaction : au-dessus du message, jamais sous le doigt.
           ⚠️ Sauf quand il n'y a rien au-dessus. Sur le PREMIER message, les 14px de
           marge du fil l'absorbent - mesuré : il ne dépasse pas. Mais sur un fil
           remonté, un message dont le bord haut affleure celui du panneau reste
           parfaitement lisible - on clique donc « Réagir » dessus - tandis que le
           choix, lui, part au-dessus du cadre et se fait rogner.
           D'où une MESURE dans ouvreChoixReaction, et non un comptage de messages. */
        #visio-room .chat-msg .picker{position:absolute;top:-6px;right:6px;z-index:40;display:flex;gap:2px;
            background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:50px;padding:3px 5px;
            box-shadow:0 10px 28px rgba(0,0,0,.45);}
        #visio-room .chat-msg .picker.bas{top:auto;bottom:-6px;}
        #visio-room .chat-msg .picker button{border:none;background:transparent;font-size:16px;line-height:1;
            padding:3px 4px;border-radius:50%;}
        #visio-room .chat-msg .picker button:hover{background:rgba(255,255,255,.14);}
        /* « Répondre » se montre au survol : une salle de 40 personnes afficherait
           sinon 40 boutons dans une colonne de 400px. Au doigt, où le survol
           n'existe pas, il reste visible. */
        /* Le message cité : court, cliquable pour remonter à l'original. */
        #visio-room .chat-msg .quote{display:block;width:100%;text-align:left;border:none;
            border-left:3px solid var(--vgold);background:rgba(255,255,255,.05);border-radius:0 8px 8px 0;
            padding:6px 9px;margin-bottom:7px;font-size:11.5px;line-height:1.35;color:var(--vmuted);}
        #visio-room .chat-msg .quote .q-who{display:block;font-weight:800;color:var(--vgold);}
        #visio-room .chat-msg .quote .q-text{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        #visio-room .chat-msg.cible{box-shadow:0 0 0 2px rgba(255,215,0,.55);}
        /* Bandeau « on répond à… » : juste au-dessus du champ, là où on écrit. */
        /* Outils du fil, réservés au modérateur. */
        #visio-room .chat-tools{display:none;padding:8px 14px;border-top:1px solid var(--vborder);}
        #visio-room .chat-tools.on{display:block;}
        #visio-room .chat-tools a{font-size:11.5px;font-weight:700;color:var(--vmuted);text-decoration:none;}
        #visio-room .chat-tools a:hover{color:var(--vtext);}
        /* Une pastille sur « Panneau » : le tiroir est fermé par défaut sur
           téléphone, et un message y arrivait sans que rien ne le signale. */
        #visio-room #btnToggleSide.a-lire::after{content:'';width:7px;height:7px;border-radius:50%;
            background:var(--vgold);flex-shrink:0;}
        #visio-room .chat-reply{display:none;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,215,0,.07);}
        #visio-room .chat-reply.on{display:flex;}
        #visio-room .chat-reply .r-text{flex:1;min-width:0;font-size:11.5px;line-height:1.35;color:var(--vmuted);}
        #visio-room .chat-reply .r-text strong{display:block;color:var(--vgold);font-size:11px;}
        #visio-room .chat-reply .r-text span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        #visio-room .chat-reply button{border:none;background:transparent;color:var(--vmuted);font-size:17px;line-height:1;padding:2px 6px;}
        #visio-room .chat-reply button:hover{color:var(--vtext);}
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
        #visio-room .person .av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
        #visio-room .person .meta{flex:1;min-width:0;}
        #visio-room .person .meta strong{display:block;font-size:13.5px;font-weight:700;}
        /* ⚠️ Les états se suivent par un ÉCART et non par des « · » : rang, micro,
           réseau et deux boutons dans 400 px, et la ligne passait sur deux étages.
           Quatre séparateurs, c'est une quarantaine de pixels pris au nom. */
        /* ⚠️ `>` et non un descendant quelconque : la ligne d'état CONTIENT le rang,
           le micro et le réseau, qui ont chacun leur couleur. Écrite sans le
           chevron, cette règle est plus spécifique que les leurs et les repeignait
           tous en gris - un rang gris sur fond or, un réseau qui ne dit plus rien. */
        #visio-room .person .meta > span{font-size:11.5px;color:var(--vmuted);
            display:flex;align-items:center;flex-wrap:wrap;gap:5px;}
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
        /* ⚠️ Les commandes ne portaient AUCUN mot : leur intitulé ne vivait que dans
           `title`, qui n'existe pas au doigt. Sur téléphone, c'était six ronds muets
           pour un public qui n'est pas celui d'un outil de développeur.
           Le libellé vient de `data-nom` (un mot) et non de `title` (la phrase
           complète, qui reste pour le survol et le lecteur d'écran) : « Plus
           d'options » sous une icône de 44 px disloquerait la barre.
           ⚠️ Portée à `.toolbar` seulement : dans la feuille « Plus d'options », les
           mêmes boutons - ce sont les mêmes, déplacés - reprennent leur mise en page
           en ligne et leur `title` entier. */
        #visio-room .toolbar .ctrl{flex-direction:column;gap:3px;width:auto;height:auto;
            min-width:50px;padding:7px 9px 6px;border-radius:14px;}
        #visio-room .toolbar .ctrl::after{content:attr(data-nom);font-size:9.5px;font-weight:700;line-height:1;}
        #visio-room .toolbar .ctrl.leave{width:auto;padding:7px 11px 6px;border-radius:14px;}
        /* Le mot vient de `data-nom` comme pour les autres : plus d'étiquette qui
           apparaît sur grand écran et disparaît sur petit. */
        #visio-room .ctrl.leave span{display:none;}
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
        /* ⚠️ Chaque compteur EST son filtre : on lit « Absents 3 » et on veut voir
           lesquels. D'où des BOUTONS, et non des étiquettes mortes.
           ⚠️ La bordure transparente est posée sur TOUTES : réservée à l'allumée,
           elle décalerait la rangée au moment du clic. */
        #visio-room .presence-counts button{font-family:inherit;font-size:11.5px;font-weight:800;padding:3px 9px;border-radius:999px;background:rgba(255,255,255,.07);color:var(--vmuted);border:1px solid transparent;cursor:pointer;}
        #visio-room .presence-counts button b{color:var(--vtext);}
        #visio-room .presence-counts button:hover{background:rgba(255,255,255,.13);color:var(--vtext);}
        /* Ce que le modérateur n'a pas encore regardé lui-même. L'étiquette
           disparaît quand il n'y a plus rien à confirmer : un compteur à zéro
           permanent se lit comme un reproche. */
        #visio-room .presence-counts button.pr-avoir{background:rgba(255,215,0,.14);color:var(--vgold);}
        #visio-room .presence-counts button.pr-avoir b{color:var(--vgold);}
        /* L'allumée dit ce que la liste montre - sans elle, une liste filtrée se
           lit comme une liste incomplète. */
        #visio-room .presence-counts button.on{background:rgba(34,62,164,.55);border-color:rgba(255,215,0,.5);color:var(--vtext);}
        #visio-room .presence-counts button.pr-avoir.on{background:rgba(255,215,0,.26);border-color:var(--vgold);color:var(--vgold);}
        #visio-room .presence-list{flex:1;overflow-y:auto;padding:8px 14px 0;display:flex;flex-direction:column;gap:8px;}
        #visio-room .pr-item{background:var(--vbg-tile);border:1px solid var(--vborder);border-radius:12px;padding:10px 12px;}
        #visio-room .pr-item .nm{font-weight:700;font-size:13.5px;}
        #visio-room .pr-item .meta{font-size:11.5px;color:var(--vmuted);margin-top:2px;}
        /* Qui est DANS la salle : l'information qui évite d'appeler quelqu'un déjà là. */
        #visio-room .pr-item .in-room{color:#4ade80;font-weight:700;}
        #visio-room .pr-item .meta .pr-avoir{color:var(--vgold);font-weight:700;}
        #visio-room .pr-acts{display:flex;gap:6px;margin-top:8px;}
        /* ⚠️ Bordure de 2px pour TOUS, y compris les boutons éteints : le
           pointillé du demi-état a besoin d'épaisseur pour se voir (en dessous,
           le navigateur arrondit au pixel écran et le trait redevient plein),
           et une épaisseur réservée au demi-état décalerait la ligne de 1,6px au
           moment précis où l'on confirme. Même bordure partout, rien ne bouge. */
        #visio-room .pr-acts button{flex:1;padding:6px 4px;border-radius:8px;border:2px solid var(--vborder);background:transparent;color:var(--vmuted);font-size:11.5px;font-weight:700;cursor:pointer;}
        #visio-room .pr-acts button:hover{color:var(--vtext);background:rgba(255,255,255,.06);}
        #visio-room .pr-acts button.on[data-st=present]{background:#16a34a;border-color:#16a34a;color:#fff;}
        #visio-room .pr-acts button.on[data-st=absent]{background:#dc2626;border-color:#dc2626;color:#fff;}
        /* ⚠️ MI-CHEMIN, et c'est voulu : un pointage posé par la visio compte dans
           « Présents », mais personne ne l'a encore regardé. Plein = constaté par
           le modérateur, pointillé = déduit de la connexion, en attente de son
           aval. Un seul clic suffit à le remplir.
           ⚠️ `[data-st]` est dans le sélecteur pour PASSER DEVANT les trois règles
           pleines ci-dessus : à spécificité égale, l'ordre déciderait, et une
           règle déplacée changerait silencieusement le rendu. */
        #visio-room .pr-acts button.on.auto[data-st]{background:transparent;border-style:dashed;}
        #visio-room .pr-acts button.on.auto[data-st=present]{border-color:#4ade80;color:#4ade80;}
        #visio-room .pr-acts button.on.auto[data-st=absent]{border-color:#f87171;color:#f87171;}
        #visio-room .pr-acts button.on.auto[data-st]:hover{background:rgba(255,255,255,.08);}
        /* Un panneau vide se lit comme une panne : il dit donc ce qu'il attend. */
        #visio-room .pr-empty,#visio-room .v-vide{padding:16px 14px;font-size:12.5px;color:var(--vmuted);line-height:1.5;}
        #visio-room .vol-panel{display:none;position:absolute;bottom:calc(var(--vtoolbar) + 12px);left:50%;transform:translateX(-50%);background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:14px;padding:12px 16px;z-index:46;box-shadow:0 12px 40px rgba(0,0,0,.4);align-items:center;gap:10px;min-width:220px;}
        #visio-room .vol-panel.open{display:flex;}
        #visio-room .vol-panel label{font-size:12px;font-weight:700;color:var(--vmuted);}
        #visio-room .vol-panel input[type=range]{flex:1;accent-color:var(--vgold);}
        #visio-room .vol-panel .vol-val{font-size:12px;font-weight:800;min-width:36px;text-align:right;}
        /* --- Réactions de salle : une bulle qui monte sur la scène ---
           ⚠️ À ne pas confondre avec les réactions du fil (`.chip`), qui se posent
           SUR un message, se comptent et survivent au rechargement. Celle-ci est un
           applaudissement : elle passe, elle ne se compte pas, rien ne la conserve.
           ⚠️ La couche vit dans `.stage-main`, pas dans la page : c'est `.stage-main`
           qui passe en plein écran, et une bulle posée ailleurs disparaîtrait
           justement au moment où l'écran de présentation prend toute la place.
           ⚠️ `pointer-events:none` sur la couche : elle recouvre toute la scène, et
           sans cela elle avalerait les clics destinés à la vignette en dessous. */
        #visio-room .reac-layer{position:absolute;inset:0;z-index:40;pointer-events:none;overflow:hidden;}
        #visio-room .reac-bulle{position:absolute;font-size:34px;line-height:1;
            filter:drop-shadow(0 6px 16px rgba(0,0,0,.55));
            animation:vreac 4s cubic-bezier(.25,.7,.4,1) forwards;}
        @@keyframes vreac{
            0%{opacity:0;transform:translateY(18px) scale(.7);}
            12%{opacity:1;transform:translateY(0) scale(1);}
            72%{opacity:1;}
            100%{opacity:0;transform:translateY(-40vh) scale(.94);}
        }
        /* --- L'emoji se pose aussi sur la carte de qui a réagi ---
           ⚠️ C'est la seule chose qui relie la bulle qui monte à une personne. En
           retrait (opacité réduite) : elle accompagne la vignette, elle ne la
           recouvre pas.
           ⚠️ Coin bas-droit : la main levée tient le haut-gauche, les rôles le
           haut-droit, le nom le bas-gauche. C'est le dernier coin libre, et il
           l'est sur TOUTES les cartes - le bandeau, la grille et la sienne. */
        #visio-room .reac-pop{position:absolute;right:8px;bottom:8px;z-index:4;pointer-events:none;
            font-size:21px;line-height:1;opacity:.72;filter:drop-shadow(0 2px 6px rgba(0,0,0,.6));
            animation:vreacPop 4.2s ease forwards;}
        /* ⚠️ Le nom recule le temps de la réaction : sans cela, l'emoji se pose sur
           la fin d'un nom long, qui occupe le même bas de carte. La transition
           évite que la pastille saute. */
        #visio-room .tile .name{transition:max-width .25s ease;}
        #visio-room .tile.a-reagi .name{max-width:calc(100% - 58px);}
        /* ⚠️ Dans le bandeau, le nom est replié au micro : il n'atteint jamais ce
           coin, et le reculer rognerait le micro sur une carte de 96px. Il ne
           recule que lorsqu'il se déplie - au survol, au clavier, et au doigt où
           il reste ouvert faute de survol. */
        #visio-room .filmstrip .tile.a-reagi .name{max-width:calc(100% - 20px);}
        #visio-room .filmstrip .tile.a-reagi:hover .name,
        #visio-room .filmstrip .tile.a-reagi:focus-within .name{max-width:calc(100% - 58px);}
        /* ⚠️ Au doigt le nom reste ouvert, faute de survol - le recul doit donc
           valoir tout le temps. Et cette règle doit venir APRÈS celle du bandeau :
           à spécificité égale, posée plus haut dans la feuille, elle perdait et
           l'emoji se posait sur le micro (mesuré à 375px). */
        @@media (pointer:coarse){
            #visio-room .filmstrip .tile.a-reagi .name{max-width:calc(100% - 58px);}
        }
        @@keyframes vreacPop{
            0%{opacity:0;transform:scale(.5);}
            10%{opacity:.72;transform:scale(1.18);}
            20%{transform:scale(1);}
            84%{opacity:.72;}
            100%{opacity:0;transform:scale(.92);}
        }
        /* Choix de l'emoji : une rangée au-dessus du bouton, pas la feuille large
           des autres menus - cinq pastilles n'ont pas besoin de 340px. */
        #visio-room .reac-menu{width:auto;padding:8px;}
        #visio-room .reac-menu.open{display:flex;gap:6px;}
        #visio-room .reac-menu button{width:48px;height:48px;border-radius:14px;border:1px solid transparent;
            background:transparent;font-size:25px;line-height:1;cursor:pointer;}
        #visio-room .reac-menu button:hover,#visio-room .reac-menu button:focus-visible{background:rgba(255,255,255,.1);border-color:var(--vborder);}
        /* --- Tableau blanc ---
           ⚠️ Il recouvre la scène mais passe SOUS la couche des réactions (40) :
           applaudir pendant qu'on explique un schéma doit rester visible. Et il
           vit dans `.stage-main`, donc il suit la scène en plein écran.
           ⚠️ Le cadre garde un rapport fixe (16/9) et se centre, marges comprises :
           un tableau est un document, il doit être identique chez tout le monde.
           Sa position est posée par `cadreTableau`, pas par la feuille de style -
           le dessin s'y rapporte, les deux ne peuvent pas diverger. */
        #visio-room .v-board{display:none;position:absolute;inset:0;z-index:38;background:var(--vbg);}
        #visio-room .v-board.on{display:block;}
        #visio-room .bd-cadre{position:absolute;background:#fff;border-radius:10px;overflow:hidden;
            box-shadow:0 12px 44px rgba(0,0,0,.55);}
        /* --- Mode annotation : on écrit SUR ce qui est présenté ---
           ⚠️ La couche devient transparente, et le cadre se cale sur l'image
           elle-même (`cadreMedia`) - pas sur un rectangle 16/9. Un diaporama 4/3
           annoté dans un cadre 16/9 verrait toutes ses flèches décalées.
           ⚠️ Et la salle force `object-fit:contain` sur la scène PENDANT qu'on
           annote. En `cover`, l'image est rognée - et pas pareil selon le format
           de chaque fenêtre : deux personnes ne verraient pas la même portion du
           document, et la flèche de l'une tomberait à côté chez l'autre. */
        #visio-room .v-board.annote{background:transparent;}
        #visio-room .v-board.annote .bd-cadre{background:transparent;box-shadow:none;border-radius:0;
            outline:2px dashed rgba(255,215,0,.45);outline-offset:-1px;}
        #visio-room .v-board.annote.lecture .bd-cadre{outline:none;}
        #visio-room.annote .tile.main > video{object-fit:contain!important;background:#000;}
        /* Le choix du mode, là où l'on travaille déjà. */
        /* Pas de place sous l'image : la barre s'efface jusqu'à ce qu'on la vise. */
        #visio-room .bd-outils.flottante{opacity:.4;transition:opacity .18s ease;}
        #visio-room .bd-outils.flottante:hover,#visio-room .bd-outils.flottante:focus-within{opacity:1;}
        #visio-room .bd-mode{display:inline-flex;border:1px solid var(--vborder);border-radius:10px;overflow:hidden;}
        #visio-room .bd-mode button{border-radius:0;border:none;padding:6px 10px;font-size:11.5px;}
        /* ⚠️ Le chevron n'est pas décoratif : `#visio-room .bd-outils button.on`
           (fond or à 16%) a la MÊME spécificité et vient plus bas dans la
           feuille - elle gagnait donc le fond, en laissant à celle-ci la seule
           couleur du texte. Résultat mesuré : bleu foncé sur fond sombre, la
           pastille du mode actif paraissait VIDE. */
        #visio-room .bd-outils .bd-mode button.on{background:var(--vgold);color:var(--vblue-d);border-color:transparent;}
        /* ⚠️ `touch-action:none` : sans lui, le doigt fait défiler la page au lieu
           de tracer, et le tableau est inutilisable sur tablette. */
        #visio-room .bd-cadre canvas{display:block;width:100%;height:100%;touch-action:none;cursor:crosshair;}
        #visio-room .v-board.lecture .bd-cadre canvas{cursor:default;}
        /* ⚠️ Centrage par `left:0;right:0;margin:auto`, et NON par `left:50%` +
           `translateX(-50%)` : positionné en absolu, `left:50%` ne laisse à
           l'élément que la MOITIÉ de la largeur disponible. Mesuré : la barre
           d'outils se repliait sur deux lignes à 900px de large alors que son
           contenu tient en 630. Le `transform` centrait ensuite un élément déjà
           trop étroit, et rien ne le laissait voir. */
        #visio-room .bd-qui{position:absolute;top:10px;left:0;right:0;margin:0 auto;width:max-content;z-index:3;
            background:rgba(15,18,28,.9);border:1px solid var(--vborder);border-radius:999px;
            padding:5px 14px;font-size:12px;font-weight:700;color:var(--vtext);
            max-width:calc(100% - 24px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #visio-room .bd-outils{display:none;position:absolute;left:0;right:0;margin:0 auto;width:max-content;bottom:12px;z-index:4;
            align-items:center;gap:6px;flex-wrap:wrap;justify-content:center;
            background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:14px;padding:7px 10px;
            box-shadow:0 10px 30px rgba(0,0,0,.45);max-width:calc(100% - 20px);}
        #visio-room .v-board:not(.lecture) .bd-outils{display:flex;}
        #visio-room .bd-outils button{border:1px solid transparent;border-radius:9px;background:transparent;
            color:var(--vtext);font-size:12px;font-weight:700;padding:6px 9px;cursor:pointer;}
        /* Le dessin par-dessus, le mot en dessous - même grammaire que la barre de
           la salle, pour qu'un outil se reconnaisse d'un écran à l'autre. */
        #visio-room .bd-outils button[data-nom]{display:inline-flex;flex-direction:column;align-items:center;
            gap:3px;min-width:46px;padding:5px 7px 4px;}
        #visio-room .bd-outils button[data-nom]::after{content:attr(data-nom);font-size:9.5px;font-weight:700;line-height:1;}
        #visio-room .bd-outils button svg{width:19px;height:19px;display:block;}
        #visio-room .bd-outils button:hover:not(:disabled),
        #visio-room .bd-outils button:focus-visible{background:rgba(255,255,255,.09);}
        #visio-room .bd-outils button.on{background:rgba(255,215,0,.16);border-color:var(--vgold);}
        /* ⚠️ Un bouton éteint doit se VOIR éteint : « Refaire » n'a rien à rendre
           tant qu'on n'a rien annulé, et le proposer quand même fait douter du
           tableau quand il ne se passe rien. */
        #visio-room .bd-outils button:disabled{opacity:.38;cursor:default;}
        #visio-room .bd-outils .sep{width:1px;height:26px;background:var(--vborder);margin:0 2px;}
        #visio-room .bd-outils label{display:inline-flex;align-items:center;color:var(--vmuted);}
        #visio-room .bd-outils label svg{width:17px;height:17px;display:block;}
        #visio-room .bd-outils input[type=range]{width:84px;accent-color:var(--vgold);}
        #visio-room .bd-palette{display:inline-flex;gap:5px;}
        /* Le crayon courant porte un anneau : sur cinq pastilles de couleur, rien
           d'autre ne dit laquelle est tenue. */
        #visio-room .bd-c{width:24px;height:24px;padding:0;border-radius:50%;border:2px solid transparent;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.25);cursor:pointer;}
        #visio-room .bd-c.on{border-color:var(--vgold);}
        #visio-room .bd-danger{color:#fda4af;}
        @@media (max-width:640px){
            /* Sur téléphone, le mot disparaît : il ne reste que les pastilles et les
               verbes les plus courts, sinon la barre mange la moitié du tableau. */
            #visio-room .bd-outils{gap:4px;padding:6px 8px;bottom:8px;}
            #visio-room .bd-outils .sep{display:none;}
            #visio-room .bd-outils input[type=range]{width:64px;}
            /* ⚠️ Le mot disparaît, le dessin reste : à 375px la barre mangeait la
               moitié du tableau. Le `title` et l'`aria-label` portent toujours
               l'intitulé entier. */
            #visio-room .bd-outils button[data-nom]{min-width:0;padding:7px;}
            #visio-room .bd-outils button[data-nom]::after{display:none;}
            #visio-room .bd-mode button{padding:7px 9px;font-size:11px;}
        }
        /* --- Échanges : leur propre surface ---
           ⚠️ Deux colonnes dès qu'il y a la place : sondages et questions se
           consultent ENSEMBLE pendant une séance, et l'un chassait l'autre dans
           une colonne de 400px. En dessous, elles s'empilent et chacune garde son
           propre défilement - sinon répondre à un sondage obligeait à remonter
           par-dessus trente questions. */
        #visio-room .v-ech{display:none;position:absolute;inset:0;z-index:58;
            background:rgba(5,8,16,.72);padding:16px;align-items:center;justify-content:center;}
        #visio-room .v-ech.on{display:flex;}
        #visio-room .ech-box{display:flex;flex-direction:column;width:min(1040px,100%);max-height:100%;
            background:var(--vbg-elev);border:1px solid var(--vborder);border-radius:18px;overflow:hidden;
            box-shadow:0 24px 70px rgba(0,0,0,.6);}
        #visio-room .ech-tete{flex-shrink:0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;
            padding:12px 14px;border-bottom:1px solid var(--vborder);}
        #visio-room .ech-tete h3{font-size:15px;font-weight:800;margin:0;}
        #visio-room .ech-filtres{display:flex;gap:4px;margin-left:auto;}
        #visio-room .ech-filtres button{border:1px solid var(--vborder);background:transparent;color:var(--vmuted);
            border-radius:50px;padding:5px 11px;font-size:11.5px;font-weight:700;cursor:pointer;}
        #visio-room .ech-filtres button:hover{color:var(--vtext);}
        #visio-room .ech-filtres button.on{background:rgba(34,62,164,.55);border-color:rgba(255,215,0,.5);color:var(--vtext);}
        #visio-room .ech-x{flex-shrink:0;width:32px;height:32px;border:none;border-radius:9px;background:transparent;
            color:var(--vmuted);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;}
        #visio-room .ech-x svg{width:17px;height:17px;}
        #visio-room .ech-x:hover{background:rgba(255,255,255,.08);color:var(--vtext);}
        #visio-room .ech-corps{flex:1;min-height:0;display:grid;grid-template-columns:minmax(0,7fr) minmax(0,9fr);}
        #visio-room .ech-col{min-width:0;min-height:0;display:flex;flex-direction:column;}
        #visio-room .ech-col + .ech-col{border-left:1px solid var(--vborder);}
        #visio-room .ech-col h5{flex-shrink:0;font-size:11px;font-weight:800;color:var(--vmuted);
            text-transform:uppercase;letter-spacing:.05em;padding:12px 14px 0;margin:0;}
        #visio-room .ech-pied{flex-shrink:0;border-top:1px solid var(--vborder);padding:10px 14px;}
        #visio-room .ech-pied .eng-form{padding:0;border:none;}
        /* La pastille du bouton : ce qui attend une réponse. */
        #visio-room .ctrl-pastille{position:absolute;top:-2px;right:-2px;min-width:17px;height:17px;padding:0 4px;
            border-radius:50px;background:var(--vgold);color:var(--vblue-d);font-size:10px;font-weight:800;
            line-height:17px;text-align:center;display:none;}
        #visio-room .ctrl-pastille.on{display:block;}
        #visio-room .toolbar .ctrl{position:relative;}
        /* Une question arrivée pendant qu'on avait fermé : la pastille respire une
           fois, puis se tait. Un signal permanent cesse d'être un signal. */
        #visio-room .ctrl.neuf .ctrl-pastille{animation:vneuf 1s ease 3;}
        @@keyframes vneuf{0%,100%{transform:scale(1);}50%{transform:scale(1.35);}}
        @@media (max-width:860px){
            #visio-room .v-ech{padding:0;}
            #visio-room .ech-box{width:100%;height:100%;max-height:100%;border-radius:0;border:none;}
            #visio-room .ech-corps{grid-template-columns:1fr;grid-template-rows:auto minmax(0,1fr);}
            #visio-room .ech-col + .ech-col{border-left:none;border-top:1px solid var(--vborder);}
            /* ⚠️ Les sondages ne prennent que ce qu'il leur faut : en hauteur fixe,
               deux sondages mangeaient l'écran et la file de questions disparaissait. */
            #visio-room .ech-col:first-child .eng{max-height:38vh;}
            #visio-room .ech-filtres{margin-left:0;width:100%;order:3;}
        }
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
        /* Reprise après coupure : la salle ne doit pas rester muette avec, pour
           seule indication, une pastille rouge dans un coin. */
        #visio-room .v-regain{display:none;position:absolute;top:calc(var(--vtop) + 16px);left:50%;
            transform:translateX(-50%);z-index:60;align-items:center;gap:12px;max-width:min(520px,92vw);
            background:rgba(10,14,23,.96);border:1px solid rgba(225,29,72,.5);border-radius:50px;
            padding:8px 10px 8px 18px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.45);}
        #visio-room .v-regain.on{display:inline-flex;}
        #visio-room .v-regain span{min-width:0;}
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
        /* --- Qui parle, maintenant ---
           ⚠️ Rien ne le montrait : en mosaïque à six tuiles, avec le son d'un
           haut-parleur de téléphone, on ne sait pas qui a la parole - on coupe
           quelqu'un, ou on attend son tour sans savoir qu'il est venu.
           Le liseré est VERT et non or : l'or dit déjà la main levée et le rôle de
           présentateur. Et il vient APRÈS `hand-up` : à spécificité égale, la
           dernière règle gagne - une main levée est une demande, la parole est un
           fait, c'est le fait qui s'affiche. */
        #visio-room .tile.parle{border-color:rgba(16,185,129,.8);box-shadow:0 0 0 2px rgba(16,185,129,.28);}
        #visio-room .person.parle{background:rgba(16,185,129,.08);border-radius:10px;padding-left:8px;padding-right:8px;}
        /* --- Réseau de la personne ---
           ⚠️ Ne s'affiche QUE sur un ennui (cf. niveauReseau) : une pastille posée en
           permanence sur tout le monde ne veut plus rien dire. Quand une image se
           figeait, personne ne savait si c'était l'autre qui décrochait ou soi : on
           parlait dans le vide, ou on quittait la salle pour rien. */
        #visio-room .net{display:inline-flex;align-items:center;flex-shrink:0;}
        #visio-room .net svg{width:13px;height:13px;display:block;}
        #visio-room .net.faible{color:#fcd34d;}
        #visio-room .net.perdu{color:#fda4af;}
        #visio-room .person .net{vertical-align:-2px;}
        /* Compteur sur l'onglet : le panneau est souvent fermé, et toujours sur
           un autre onglet que celui des participants. */
        #visio-room .tab-count{display:none;margin-left:6px;background:var(--vgold);color:var(--vblue-d);
            font-size:10.5px;font-weight:900;padding:1px 7px;border-radius:50px;}
        #visio-room .tab-count.on{display:inline-block;}
        /* État du micro : lu de la salle, jamais deviné (cf. syncMicStates).
           En ICÔNE et non en toutes lettres - « micro coupé » répété sur chaque
           ligne pesait plus que le nom qu'on y cherche. Le texte reste dans
           `title`/`aria-label`, pour le survol comme pour un lecteur d'écran. */
        #visio-room .person .mic{display:inline-flex;align-items:center;vertical-align:-3px;}
        #visio-room .person .mic svg{width:14px;height:14px;display:block;}
        #visio-room .person .mic.on{color:#6ee7b7;}
        #visio-room .person .mic.off{color:#fda4af;}
        /* ===================== Échanges (questions & sondages) =====================
           ⚠️ Ils se tenaient sur une AUTRE PAGE : poser une question demandait de
           quitter la visio, donc de cesser de voir et d'entendre ce dont on voulait
           parler. Le panneau accueille les deux, dans l'ordre de la séance : le
           sondage en cours d'abord - c'est lui qui attend une réponse tout de
           suite - puis la file des questions. */
        #visio-room .eng{flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:14px;}
        #visio-room .eng h5{font-size:11px;font-weight:800;color:var(--vmuted);text-transform:uppercase;
            letter-spacing:.04em;display:flex;align-items:center;gap:7px;}
        #visio-room .eng-vide{font-size:12.5px;color:var(--vmuted);line-height:1.5;}
        /* --- Sondage --- */
        #visio-room .poll{background:var(--vbg-tile);border:1px solid var(--vborder);border-radius:12px;padding:12px;}
        #visio-room .poll .q{font-size:13.5px;font-weight:700;line-height:1.35;}
        #visio-room .poll .clos{font-size:10.5px;font-weight:800;color:var(--vmuted);}
        #visio-room .poll .opts{display:flex;flex-direction:column;gap:6px;margin-top:10px;}
        /* La barre de résultat est le FOND du bouton : le choix et le score se lisent
           d'un seul regard, sans légende à rapprocher. */
        #visio-room .poll .opt{position:relative;overflow:hidden;display:flex;align-items:center;gap:8px;
            width:100%;text-align:left;border:1px solid var(--vborder);border-radius:9px;
            background:rgba(255,255,255,.04);color:var(--vtext);font-size:12.5px;font-weight:600;padding:8px 10px;}
        #visio-room .poll .opt .bar{position:absolute;inset:0 auto 0 0;background:rgba(34,62,164,.55);
            transition:width .25s ease;z-index:0;}
        #visio-room .poll .opt .lb,#visio-room .poll .opt .pc{position:relative;z-index:1;}
        #visio-room .poll .opt .lb{flex:1;min-width:0;}
        #visio-room .poll .opt .pc{font-variant-numeric:tabular-nums;font-weight:800;color:var(--vmuted);}
        #visio-room .poll .opt.mine{border-color:rgba(255,215,0,.6);}
        #visio-room .poll .opt.mine .pc{color:var(--vgold);}
        #visio-room .poll .pied{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:9px;}
        #visio-room .poll .tot{font-size:11px;color:var(--vmuted);font-weight:700;}
        /* --- Question --- */
        #visio-room .qst{display:flex;gap:9px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--vborder);}
        #visio-room .qst:last-child{border-bottom:none;}
        /* Le vote est une colonne, pas une ligne : c'est le classement de la file,
           et il doit se lire en balayant verticalement. */
        #visio-room .qst .vote{flex:0 0 auto;display:flex;flex-direction:column;align-items:center;gap:1px;
            min-width:38px;padding:5px 4px;border:1px solid var(--vborder);border-radius:9px;
            background:rgba(255,255,255,.04);color:var(--vtext);font-size:12px;font-weight:800;}
        #visio-room .qst .vote svg{width:13px;height:13px;display:block;}
        #visio-room .qst .vote.on{background:rgba(34,62,164,.6);border-color:rgba(255,215,0,.5);}
        #visio-room .qst .vote:disabled{opacity:.55;cursor:default;}
        #visio-room .qst .corps{flex:1;min-width:0;}
        #visio-room .qst .txt{font-size:13px;line-height:1.45;overflow-wrap:anywhere;}
        #visio-room .qst .sig{font-size:11px;color:var(--vmuted);margin-top:3px;
            display:flex;align-items:center;flex-wrap:wrap;gap:6px;}
        #visio-room .qst .st{font-size:10px;font-weight:800;padding:2px 7px;border-radius:50px;}
        #visio-room .qst .st.answered{background:rgba(16,185,129,.15);color:#6ee7b7;}
        #visio-room .qst .st.hidden{background:rgba(148,163,184,.15);color:var(--vmuted);}
        #visio-room .qst.answered .txt{color:var(--vmuted);}
        #visio-room .qst .acts{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px;}
        #visio-room .qst .acts button{font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:6px;
            border:1px solid var(--vborder);background:rgba(255,255,255,.06);color:var(--vtext);}
        #visio-room .qst .acts button:hover{background:rgba(34,62,164,.45);}
        /* --- Ce qu'on écrit (question, sondage) --- */
        #visio-room .eng-form{border-top:1px solid var(--vborder);padding:12px;display:flex;flex-direction:column;gap:8px;}
        #visio-room .eng-form input{border-radius:10px;border:1px solid var(--vborder);background:rgba(255,255,255,.05);
            color:var(--vtext);padding:10px 13px;font-size:13px;font-family:inherit;width:100%;}
        #visio-room .eng-form input:focus{outline:none;border-color:var(--vblue);}
        #visio-room .eng-form .ligne{display:flex;gap:8px;align-items:center;}
        #visio-room .eng-form .ligne input{flex:1;}
        #visio-room .eng-note{font-size:11.5px;color:var(--vmuted);line-height:1.45;padding:10px 14px;}
        /* Brouillon de sondage : replié tant que le modérateur n'en lance pas un. */
        #visio-room .poll-new{display:none;flex-direction:column;gap:7px;background:var(--vbg-tile);
            border:1px dashed var(--vborder);border-radius:12px;padding:12px;}
        #visio-room .poll-new.on{display:flex;}
        #visio-room .poll-new .opt-ligne{display:flex;gap:6px;align-items:center;}
        #visio-room .poll-new .opt-ligne input{flex:1;}
        #visio-room .poll-new .opt-ligne button{flex:0 0 auto;border:none;background:transparent;
            color:var(--vmuted);font-size:16px;line-height:1;padding:2px 6px;}
        #visio-room .poll-new .acts{display:flex;justify-content:space-between;gap:8px;margin-top:3px;}

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
            /* Le défilement des onglets est désormais la règle générale (cf. plus
               haut) : il ne reste ici qu'un peu d'air en moins. */
            #visio-room .side-tabs button{padding:14px 10px;}
        }

        /* --- Barre d'outils compacte (sous la tablette en portrait) ---
           ⚠️ Seuil à 767px et non 640 : les dix commandes réclament près de 700px,
           ce que l'iPad en portrait (768) offre encore, mais pas une tablette plus
           étroite - les boutons des deux bords y étaient rognés. */
        @@media (max-width:767px) {
            #visio-room{--vtoolbar:68px;}
            #visio-room .toolbar{gap:6px;padding:0 10px;}
            #visio-room .toolbar .sep{display:none;}
            #visio-room .toolbar .ctrl{min-width:44px;padding:5px 7px 4px;}
            #visio-room .ctrl svg{width:21px;height:21px;}
            #visio-room .ctrl.more{display:inline-flex;}
            #visio-room .toolbar .ctrl.leave{padding:5px 8px 4px;}
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
            #visio-room .v-count{padding:5px 8px;gap:4px;}
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
            /* ⚠️ 375 px de haut : ici, chaque pixel rendu à la scène compte plus que
               le mot sous l'icône. Les boutons redeviennent ronds et muets. */
            #visio-room .toolbar .ctrl{width:40px;height:40px;min-width:0;padding:0;border-radius:50%;}
            #visio-room .toolbar .ctrl::after{display:none;}
            #visio-room .ctrl svg{width:19px;height:19px;}
            #visio-room .toolbar .ctrl.leave{width:40px;padding:0;border-radius:50%;}
            #visio-room .stage{padding:6px 8px 4px;}
            #visio-room .filmstrip .tile{flex:0 0 96px;width:96px;}
            #visio-room .filmstrip-toggle{padding:5px 12px;}
            #visio-room .filmstrip-wrap .filmstrip{padding:0 12px 8px;}
        }

        /* --- Temps de parole ---
           ⚠️ La séance a un programme avec des créneaux, et rien ne disait depuis
           combien de temps la personne parlait : le modérateur tenait son horaire de
           tête. Le chronomètre s'affiche pour LUI et pour celui qui a la parole - les
           deux qui peuvent agir dessus ; l'afficher à toute la salle ferait d'un
           dépassement l'affaire de quarante personnes.
           `tabular-nums` : sans lui, les chiffres n'ont pas la même largeur et la
           pastille tremble à chaque seconde. */
        #visio-room .v-chrono{display:none;position:absolute;top:calc(var(--vtop) + 16px);right:20px;z-index:30;
            align-items:center;gap:9px;background:rgba(10,14,23,.86);border:1px solid var(--vborder);
            backdrop-filter:blur(6px);padding:7px 14px;border-radius:50px;font-size:12.5px;font-weight:700;
            box-shadow:0 8px 24px rgba(0,0,0,.35);}
        #visio-room .v-chrono.on{display:inline-flex;}
        #visio-room .v-chrono .qui{color:var(--vmuted);font-weight:600;max-width:170px;
            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        #visio-room .v-chrono .t{font-variant-numeric:tabular-nums;}
        #visio-room .v-chrono.proche{border-color:rgba(245,158,11,.55);}
        #visio-room .v-chrono.proche .t{color:#fcd34d;}
        #visio-room .v-chrono.depasse{border-color:rgba(225,29,72,.6);}
        #visio-room .v-chrono.depasse .t{color:#fda4af;}
        /* --- Écran d'entrée ---
           ⚠️ On tombait dans la salle, et la demande d'autorisation du navigateur
           arrivait PAR-DESSUS une séance déjà commencée. C'est ici que se règlent
           presque tous les ennuis de première fois : un micro muet, une caméra prise
           par une autre application, le mauvais périphérique choisi.
           ⚠️ Il ne s'affiche QUE pour qui diffuse (communicateur, modérateur) : pour
           un auditeur, qui ne publie ni son ni image, ce serait un mur sans objet. */
        #visio-room .v-prejoin{display:none;position:absolute;inset:0;z-index:100;padding:20px;
            background:var(--vbg);align-items:center;justify-content:center;overflow-y:auto;}
        #visio-room .v-prejoin.on{display:flex;}
        #visio-room .pj-box{width:min(460px,100%);background:var(--vbg-elev);border:1px solid var(--vborder);
            border-radius:18px;padding:20px;box-shadow:0 24px 64px rgba(0,0,0,.5);}
        #visio-room .pj-box h3{font-size:16px;font-weight:800;}
        #visio-room .pj-sub{font-size:12.5px;color:var(--vmuted);line-height:1.45;margin-top:4px;}
        #visio-room .pj-view{position:relative;margin-top:14px;border-radius:14px;overflow:hidden;
            background:var(--vbg-tile);aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;}
        /* Son propre reflet ici aussi : c'est la même glace (cf. #tileLocal). */
        #visio-room .pj-view video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);}
        #visio-room .pj-off{position:relative;z-index:2;font-size:12.5px;color:var(--vmuted);}
        #visio-room .pj-view.on video{display:block;}
        #visio-room .pj-view.on .pj-off{display:none;}
        #visio-room .pj-view:not(.on) video{display:none;}
        /* Niveau du micro : la seule preuve qu'il entend quelque chose. Un micro
           muet ne se voit pas, il se CONSTATE - on parle, et la barre bouge. */
        #visio-room .pj-level{position:absolute;left:10px;right:10px;bottom:10px;height:5px;z-index:3;
            border-radius:50px;background:rgba(255,255,255,.14);overflow:hidden;}
        #visio-room .pj-level span{display:block;height:100%;width:0;border-radius:50px;
            background:var(--vok);transition:width .08s linear;}
        #visio-room .pj-togs{display:flex;gap:8px;margin-top:12px;}
        #visio-room .pj-tog{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;
            padding:10px;border-radius:10px;border:1px solid var(--vborder);background:rgba(255,255,255,.05);
            color:var(--vmuted);font-weight:700;font-size:12.5px;}
        #visio-room .pj-tog svg{width:17px;height:17px;}
        #visio-room .pj-tog[aria-pressed=true]{background:var(--vblue);border-color:transparent;color:#fff;}
        #visio-room .pj-devices{display:flex;flex-direction:column;gap:8px;margin-top:12px;}
        #visio-room .pj-devices label{font-size:11px;font-weight:700;color:var(--vmuted);
            display:flex;flex-direction:column;gap:4px;}
        #visio-room .pj-devices select{border-radius:9px;border:1px solid var(--vborder);
            background:var(--vbg-tile);color:var(--vtext);padding:9px 10px;font-size:12.5px;font-family:inherit;}
        #visio-room .pj-msg{display:none;margin-top:12px;font-size:12px;line-height:1.45;color:#fcd34d;}
        #visio-room .pj-msg.on{display:block;}
        #visio-room .pj-acts{display:flex;gap:8px;margin-top:16px;}
        #visio-room .pj-acts .v-btn{flex:1;justify-content:center;}
        @@media (max-width:640px){
            #visio-room .v-chrono{right:10px;padding:6px 11px;font-size:12px;}
            #visio-room .v-chrono .qui{display:none;}
            #visio-room .pj-box{padding:16px;}
        }

        /* ===================== Mouvement réduit =====================
           ⚠️ Deux animations tournaient EN BOUCLE pendant toute une séance : le halo
           du bouton d'enregistrement et la pastille de l'état de connexion. Le reste
           du projet respecte ce réglage (ukc.css, admin.css, public.css) ; la salle,
           non - et c'est elle qu'on regarde deux heures d'affilée.
           L'information ne tient à aucune des deux : le rouge et le mot « REC »
           disent l'enregistrement, et le texte à côté de la pastille dit ce qu'on
           attend. Rien n'est donc caché, seul le mouvement s'arrête. */
        @@media (prefers-reduced-motion: reduce) {
            #visio-room .ctrl.rec.active,#visio-room .rec-badge .dot,
            #visio-room .v-status::before{animation:none;}
            /* Un anneau amputé de son quart ne veut plus rien dire une fois immobile :
               il reprend son contour entier. */
            #visio-room .v-status::before{border-color:rgba(199,209,230,.6);}
            /* Le tiroir s'ouvre, il ne voyage plus. */
            #visio-room .side,#visio-room .side-scrim,#visio-room .v-toast,#visio-room .tile,
            #visio-room .ctrl,#visio-room .pip-resize,#visio-room .side-grip::before,
            #visio-room .chat-msg .acts-msg,#visio-room .v-btn,
            #visio-room .pj-level span,
            #visio-room .filmstrip .tile .name .nm{transition-duration:.01ms;}
            #visio-room .ctrl:hover:not(:disabled){transform:none;}
            /* ⚠️ La bulle ne VOYAGE plus, mais elle ne disparaît pas : c'est la
               seule trace d'une réaction, et `animation:none` la laisserait figée
               sur la scène pour toujours. Elle se montre, puis s'efface sur place. */
            #visio-room .reac-bulle{animation:vreacFixe 3.6s linear forwards;}
            /* Même raison : elle doit encore s'effacer, mais sans la pulsation. */
            #visio-room .reac-pop{animation:vreacFixe 4s linear forwards;}
            #visio-room .tile .name{transition-duration:.01ms;}
        }
        @@keyframes vreacFixe{0%{opacity:0;}8%{opacity:1;}72%{opacity:1;}100%{opacity:0;}}
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
                {{-- Nombre de personnes dans la salle : le chiffre se lit d'un coup
                     d'œil, la phrase entière est dans son `title` / `aria-label`. --}}
                <span class="v-count" id="compteConnectes" title="1 personne connectée"
                      aria-label="1 personne connectée">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    <b id="compteConnectesN">1</b>
                </span>
                <span class="pill warn" id="connPill">Connexion…</span>
                <button type="button" class="v-btn ghost sm" id="btnToggleSide">Panneau</button>
                {{-- ⚠️ Plus de lien « Questions & sondages » ici : il faisait SORTIR de
                     la visio pour participer à ce dont la séance parlait. Les
                     échanges sont un onglet du panneau (cf. #tabEng) ; la page
                     autonome demeure pour une séance sans visio. --}}
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
                    {{-- ⚠️ `aria-hidden` : une réaction est un applaudissement, pas une
                         prise de parole. Énoncées une à une, vingt bulles noieraient le
                         lecteur d'écran et couvriraient le bandeau d'état. Qui veut dire
                         quelque chose lève la main - celle-là est annoncée, et listée. --}}
                    {{-- Tableau blanc. Il recouvre la scène, et seul qui anime peut y
                         tracer (le récepteur le vérifie : cf. ORDRES_RESERVES). --}}
                    <div class="v-board" id="board">
                        <div class="bd-qui" id="boardWho">Tableau blanc</div>
                        <div class="bd-cadre" id="boardFrame"><canvas id="boardCanvas"></canvas></div>
                        <div class="bd-outils" id="boardTools" role="toolbar" aria-label="Outils du tableau">
                            {{-- Les crayons sont posés par le script : une seule liste,
                                 celle que le récepteur accepte. --}}
                            {{-- Tableau ou annotation : le même crayon, deux fonds. --}}
                            <span class="bd-mode" id="boardMode" role="group" aria-label="Mode du tableau">
                                <button type="button" data-mode="blanc" class="on">Tableau</button>
                                <button type="button" data-mode="annote">Annoter</button>
                            </span>
                            <span class="sep"></span>
                            <span class="bd-palette" id="boardPalette"></span>
                            {{-- ⚠️ Icône ET mot, comme la barre de la salle : le `title` seul
                                 n'existe pas au doigt, et une rangée de six dessins sans
                                 légende se devine mal. Le mot s'efface sous 640px, où la
                                 place rendue au tableau vaut mieux qu'une légende. --}}
                            <button type="button" id="bdGomme" title="Gomme" data-nom="Gomme" aria-label="Gomme">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 20H20"/><path d="M15.4 4.6a2 2 0 0 1 2.8 0l2.2 2.2a2 2 0 0 1 0 2.8L11.8 18H7l-2.4-2.4a2 2 0 0 1 0-2.8z"/><path d="M11 9l4 4"/></svg>
                            </button>
                            <span class="sep"></span>
                            <label for="bdTaille" title="Épaisseur du trait" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M3 7h18" stroke-width="1.2"/><path d="M3 12h18" stroke-width="2.6"/><path d="M3 17.5h18" stroke-width="4.4"/></svg>
                            </label>
                            <input type="range" id="bdTaille" min="2" max="16" value="4" aria-label="Épaisseur du trait">
                            <span class="sep"></span>
                            <button type="button" id="bdUndo" title="Annuler mon dernier trait" data-nom="Annuler" aria-label="Annuler mon dernier trait">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14L4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg>
                            </button>
                            {{-- ⚠️ « Refaire » manquait : on annulait un trait d'un geste, et
                                 rien ne le rendait. Il ne rend QUE ses propres traits. --}}
                            <button type="button" id="bdRedo" title="Refaire mon trait annulé" data-nom="Refaire" aria-label="Refaire mon trait annulé" disabled>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 14l5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/></svg>
                            </button>
                            <button type="button" id="bdSave" title="Enregistrer le tableau en image" data-nom="Image" aria-label="Enregistrer en image">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v11"/><path d="M8 10l4 4 4-4"/><path d="M4 16v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2"/></svg>
                            </button>
                            <span class="sep"></span>
                            <button type="button" id="bdClear" class="bd-danger" title="Tout effacer, pour tout le monde" data-nom="Effacer" aria-label="Tout effacer, pour tout le monde">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M10 11v6M14 11v6"/></svg>
                            </button>
                            <button type="button" id="bdClose" title="Fermer le tableau" data-nom="Fermer" aria-label="Fermer le tableau">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="reac-layer" id="reacLayer" aria-hidden="true"></div>
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
                    <button type="button" class="active" data-side="chat">Chat<span class="tab-count" id="chatUnread"></span></button>
                    <button type="button" data-side="people">Participants<span class="tab-count" id="handCount"></span></button>
                    {{-- Questions votées et sondages : ils se tenaient sur une autre
                         page, qu'il fallait quitter la visio pour atteindre. --}}
                    <button type="button" data-side="comms" id="tabComms" style="display:none;">Communicateurs</button>
                    {{-- Onglet réservé au modérateur / rapporteur de la séance (cf. cfg.presence). --}}
                    <button type="button" data-side="presence" id="tabPresence" style="display:none;">Présence</button>
                </div>
                {{-- Hors des onglets : ils défilent sur téléphone, la croix doit
                     rester en place. --}}
                <button type="button" class="side-close" id="btnCloseSide" title="Fermer le panneau" aria-label="Fermer le panneau">&times;</button>
                </div>
                <div class="side-panel active" id="panelChat">
                    {{-- `role="log"` : un lecteur d’écran doit annoncer les messages qui
                         arrivent, sans que l’on ait à revenir lire le panneau. --}}
                    <div class="chat-log" id="chatLog" role="log" aria-live="polite" aria-relevant="additions"></div>
                    {{-- Ce qui est arrivé pendant qu'on lisait plus haut. --}}
                    <button type="button" class="chat-bas" id="chatVersBas"></button>
                    {{-- Compte rendu : le fil s'emporte en texte (modérateur). --}}
                    <div class="chat-tools" id="chatTools">
                        <a id="chatExport" href="#" target="_blank" rel="noopener">⬇ Télécharger le fil (texte)</a>
                    </div>
                    <div class="chat-reply" id="chatReply">
                        <div class="r-text"><strong id="chatReplyWho"></strong><span id="chatReplyText"></span></div>
                        <button type="button" id="chatReplyCancel" title="Annuler la réponse" aria-label="Annuler la réponse">&times;</button>
                    </div>
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
                        Les étiquettes filtrent la liste.
                    </div>
                    <div class="presence-counts" id="presenceCounts" role="group" aria-label="Filtrer la liste de présence"></div>
                    <div class="presence-list" id="presenceList"></div>
                    <div style="padding:10px 14px 14px;">
                        {{-- ⚠️ PAS de `rel="noopener"` ici, et c'est délibéré : la page
                             ouverte est la nôtre, sur la même origine, et elle a besoin
                             de la poignée (`window.opener`) pour remettre CET onglet
                             devant au retour - sans quoi revenir rechargerait la salle,
                             donc en sortirait et y rentrerait sous les yeux de tous. --}}
                        <a class="v-btn sm" id="presencePage" href="#" target="_blank">Ouvrir la page de présence</a>
                    </div>
                </div>
            </aside>
        </div>

        <div class="toolbar" id="visioToolbar">
            <button type="button" class="ctrl muted" id="btnMic" title="Micro" data-nom="Micro" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnSpeaker" title="Son" data-nom="Son">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
            </button>
            <button type="button" class="ctrl muted" id="btnCam" title="Caméra" data-nom="Caméra" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnScreen" title="Partager l’écran" data-nom="Écran" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>
            </button>
            <div class="sep"></div>
            <button type="button" class="ctrl hand" id="btnHand" title="Lever la main" data-nom="Main" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 13V5.5a1.5 1.5 0 0 1 3 0V12"/><path d="M11 12V4.5a1.5 1.5 0 0 1 3 0V12"/><path d="M14 12V6.5a1.5 1.5 0 0 1 3 0V14a6 6 0 0 1-6 6a6 6 0 0 1-5.2-3"/><path d="M8 13v2a2 2 0 0 1-4 0v-2.5A1.5 1.5 0 0 1 7 12.5V13"/></svg>
            </button>
            {{-- Réagir : la seule façon pour l'assistance de dire quelque chose sans
                 couper la parole. Voisin de « Main » parce que c'est la même
                 intention, à deux intensités. --}}
            <button type="button" class="ctrl" id="btnReac" title="Réagir" data-nom="Réagir"
                    aria-haspopup="true" aria-expanded="false" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 14.5a4.5 4.5 0 0 0 7 0"/><path d="M9 9.5h.01"/><path d="M15 9.5h.01"/></svg>
            </button>
            {{-- Le tableau se range avec le partage d'écran : c'est la même chose
                 du point de vue de la salle - ce qu'on montre à la place des visages. --}}
            {{-- Les échanges ont leur bouton : c'est une activité de la séance,
                 pas un onglet d'un panneau qu'il faut d'abord ouvrir. --}}
            <button type="button" class="ctrl" id="btnEch" title="Questions et sondages" data-nom="Échanges"
                    aria-expanded="false" style="display:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a7.5 7.5 0 0 1-9.9 7.1L5 20l1.4-4.3A7.5 7.5 0 1 1 20 11z"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.7 1.1c0 1.7-2.4 2-2.4 3.2"/><path d="M12 16.2h.01"/></svg>
                <span class="ctrl-pastille" id="echCount"></span>
            </button>
            <button type="button" class="ctrl" id="btnBoard" title="Tableau blanc" data-nom="Tableau" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M12 17v3"/><path d="M8 20h8"/><path d="M7 12.5c1.6-3 3-3 4 0s2.4 3 4-1"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnLayout" title="Disposition" data-nom="Vue">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 11h5V5H4v6zm0 7h5v-6H4v6zm6 0h5v-6h-5v6zm6 0h5v-6h-5v6zm-6-7h5V5h-5v6zm6-6v6h5V5h-5z"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnFs" title="Plein écran" data-nom="Plein">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M16 3h3a2 2 0 0 1 2 2v3"/><path d="M8 21H5a2 2 0 0 1-2-2v-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
            </button>
            <button type="button" class="ctrl" id="btnBg" title="Arrière-plan" data-nom="Fond" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M3 16l5-4 3 2 4-5 6 7"/></svg>
            </button>
            <button type="button" class="ctrl rec" id="btnRec" title="Enregistrer" data-nom="REC" style="display:none;">
                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="7"/></svg>
            </button>
            {{-- Visible seulement quand la barre se compacte : elle accueille
                 alors les commandes secondaires, déplacées telles quelles. --}}
            <button type="button" class="ctrl more" id="btnMore" title="Plus d’options" data-nom="Plus" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
            <div class="sep"></div>
            <button type="button" class="ctrl leave" id="btnLeave" title="Quitter la salle" data-nom="Quitter">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> <span>Quitter</span>
            </button>
        </div>

        {{-- ===== Échanges : questions votées et sondages =====
             ⚠️ Ils tenaient dans une colonne de 400px partagée avec le fil, les
             personnes et la présence. Une file de questions et des sondages en
             cours n'y tenaient pas ensemble : on passait son temps à faire
             défiler, et sur téléphone le tiroir recouvrait la séance. Ils ont
             maintenant leur surface et leur bouton. --}}
        <div class="v-ech" id="ech" role="dialog" aria-modal="false" aria-labelledby="echTitre">
            <div class="ech-box">
                <header class="ech-tete">
                    <h3 id="echTitre">Échanges</h3>
                    <div class="ech-filtres" id="echFiltres" role="group" aria-label="Filtrer les questions">
                        <button type="button" data-f="toutes" class="on">Toutes</button>
                        <button type="button" data-f="attente">Sans réponse</button>
                        <button type="button" data-f="miennes">Les miennes</button>
                    </div>
                    <button type="button" class="ech-x" id="echClose" aria-label="Fermer les échanges">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </header>
                <div class="ech-corps">
                    <section class="ech-col" aria-labelledby="echTP">
                        <h5 id="echTP">Sondages</h5>
                        <div class="eng" id="engPolls"></div>
                    </section>
                    <section class="ech-col" aria-labelledby="echTQ">
                        <h5 id="echTQ">Questions</h5>
                        <div class="eng" id="engList"></div>
                    </section>
                </div>
                {{-- Poser une question : réservé aux comptes connectés (le serveur
                     le revérifie). Un visiteur anonyme lit la file sans y écrire. --}}
                <footer class="ech-pied">
                    <div class="eng-form" id="engForm" style="display:none;">
                        <div class="ligne">
                            <input type="text" id="engInput" maxlength="600" autocomplete="off"
                                   placeholder="Poser une question…" aria-label="Poser une question">
                            <button type="button" class="v-btn blue sm" id="engSend">Envoyer</button>
                        </div>
                    </div>
                    <div class="eng-note" id="engNote" style="display:none;">
                        Connectez-vous pour poser une question et voter.
                    </div>
                </footer>
            </div>
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
        {{-- Les emojis sont ceux que le serveur connaît déjà (MeetingMessage::EMOJIS) :
             une seule liste pour le fil et pour la scène, et le récepteur s'y
             tient (le canal de données est public). --}}
        <div class="menu reac-menu" id="reacMenu" role="group" aria-label="Réagir"></div>
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
        <div class="v-regain" id="reconnectBar" role="alert">
            <span id="reconnectText">La connexion à la salle a été perdue.</span>
            <button type="button" class="v-btn blue sm" id="btnReconnect">Reconnecter</button>
        </div>
        {{-- ⚠️ `role="status"` : « Votre micro a été coupé par le modérateur » ne
             parvenait à personne qui ne regarde pas l'écran. Le bandeau de reprise,
             lui, est une `alert` : perdre la salle interrompt ce qu'on faisait. --}}
        {{-- Temps de parole : pour le modérateur et pour qui a la parole. --}}
        <div class="v-chrono" id="chrono" role="status">
            <span class="qui" id="chronoQui"></span><span class="t" id="chronoT">00:00</span>
        </div>
        <div class="v-status" id="lkStatus" role="status">Connexion à la salle…</div>
        <div class="v-toast" id="toast" role="status" aria-live="polite"></div>
        {{-- Écran d'entrée : vérifier ses périphériques AVANT d'ouvrir la porte.
             Posé en dernier pour couvrir la salle, et retiré par le script dès que
             l'on entre (cf. ecranDEntree). --}}
        <div class="v-prejoin" id="prejoin" role="dialog" aria-modal="true" aria-labelledby="pjTitre">
            <div class="pj-box">
                <h3 id="pjTitre">Avant d'entrer</h3>
                <p class="pj-sub">Vérifiez votre caméra et votre micro : parlez, la barre doit bouger.</p>
                <div class="pj-view" id="pjVue">
                    <video id="pjVideo" autoplay playsinline muted></video>
                    <div class="pj-off" id="pjOff">Caméra fermée</div>
                    <div class="pj-level"><span id="pjNiveau"></span></div>
                </div>
                <div class="pj-togs">
                    <button type="button" class="pj-tog" id="pjCam" aria-pressed="false">Caméra</button>
                    <button type="button" class="pj-tog" id="pjMic" aria-pressed="false">Micro</button>
                </div>
                <div class="pj-devices">
                    <label>Caméra<select id="pjCamSel"></select></label>
                    <label>Micro<select id="pjMicSel"></select></label>
                </div>
                <div class="pj-msg" id="pjMsg"></div>
                <div class="pj-acts">
                    <button type="button" class="v-btn blue" id="pjEntrer">Rejoindre la salle</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="lk-config">@json($liveKit)</script>
@else
    {{-- ============ Hors salle LiveKit : Jitsi intégré, lien externe, ou état
         « hors direct » ============
         ⚠️ Cette page s'affiche SELON LE STATUT de la visio (programmée → salle
         d'attente avec le début prévu ; terminée → replay ; réservée → connexion).
         Plus de bouton « Questions & sondages » : les échanges se tiennent
         EXCLUSIVEMENT dans la salle de visio (onglet « Échanges » du panneau). --}}
    <style>
        .mlobby{display:flex;flex-direction:column;min-height:100vh;min-height:100dvh;background:#eef1f6;}
        .mlobby-top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
            padding:12px clamp(12px,3vw,24px);background:#fff;border-bottom:1px solid var(--ukc-border);}
        .mlobby-head{min-width:0;display:flex;flex-direction:column;gap:2px;}
        .mlobby-head strong{font-size:1rem;color:#14205a;}
        .mlobby-head span{font-size:.8rem;color:var(--ukc-muted);}
        .mlobby-top-right{display:flex;align-items:center;gap:10px;flex-shrink:0;}
        .mlobby-main{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;
            padding:clamp(16px,5vw,40px);}
        .mlobby-main.is-embed{padding:0;align-items:stretch;}
        .mlobby-card{width:100%;max-width:580px;background:#fff;border:1px solid var(--ukc-border);
            border-radius:18px;box-shadow:0 18px 50px -24px rgba(20,32,90,.35);
            padding:clamp(24px,5vw,40px);text-align:center;}
        .mlobby-ico{width:72px;height:72px;border-radius:50%;display:inline-flex;align-items:center;
            justify-content:center;margin-bottom:14px;color:var(--ukc-blue);background:#e8ecfb;}
        .mlobby-card--muted .mlobby-ico{color:var(--ukc-muted);background:#eef1f6;}
        .mlobby-card--warn .mlobby-ico{color:#b45309;background:#fef3e2;}
        .mlobby-card--ok .mlobby-ico{color:var(--ukc-green);background:#e6f6ec;}
        .mlobby-ttl{font-size:1.3rem;margin:2px 0 8px;color:#14205a;}
        .mlobby-lead{color:var(--ukc-muted);line-height:1.55;margin:0 auto;max-width:62ch;}
        .mlobby-when{display:inline-flex;align-items:center;gap:12px;margin-top:20px;padding:12px 18px;
            border-radius:12px;background:#eef1f6;color:var(--ukc-blue);text-align:left;}
        .mlobby-when small{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;
            color:var(--ukc-muted);margin-bottom:1px;}
        .mlobby-when strong{display:block;font-size:.98rem;color:#14205a;}
        .mlobby-acts{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:24px;}
        @media (prefers-color-scheme: dark){ .mlobby{background:#0f1726;} }
    </style>
    <div class="mlobby">
        <header class="mlobby-top">
            <div class="mlobby-head">
                <strong>{{ $label }}</strong>
                <span>
                    {{ $editionTitle }}@if ($role->canJoin()) · Vous rejoignez en tant que
                        <strong>{{ ['moderator' => 'modérateur', 'presenter' => 'présentateur', 'attendee' => 'participant'][$role->value] ?? '' }}</strong>@endif
                </span>
            </div>
            <div class="mlobby-top-right">
                <span class="ukc-pill ukc-pill--{{ $meetingStatus->pill() }}">{{ $meetingStatus->label() }}</span>
                <a href="{{ $backUrl }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="close" :size="16" /> Quitter
                </a>
            </div>
        </header>

        <main class="mlobby-main {{ $embed ? 'is-embed' : '' }}">
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
                <div class="mlobby-card mlobby-card--ok">
                    <span class="mlobby-ico"><x-ukc.icon name="video" :size="30" /></span>
                    <h1 class="mlobby-ttl">Rejoindre la visio</h1>
                    <p class="mlobby-lead">La salle s’ouvre dans un nouvel onglet.</p>
                    <div class="mlobby-acts">
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="external" :size="16" /> Ouvrir la salle
                        </a>
                        <a href="{{ $backUrl }}" class="ukc-btn ukc-btn--outline">Retour</a>
                    </div>
                </div>
            @else
                @php
                    // L'état d'affichage découle du statut de la visio ET de l'accès.
                    // (⚠️ pas de `use` dans un bloc @php de vue : enum en nom complet.)
                    $when = $startsAt?->translatedFormat('l j F Y, H\hi');
                    if ($isLive && $role->canJoin()) {
                        $accent = 'warn';  $ico = 'video';
                        $ttl = 'Salle en préparation';
                        $lead = 'La visio est annoncée mais le lien de la réunion n’a pas encore été renseigné par l’organisation.';
                    } elseif ($isLive && $isGuest) {
                        $accent = 'blue';  $ico = 'login';
                        $ttl = 'Connexion requise';
                        $lead = 'La visio est en cours. Connectez-vous avec votre compte participant pour la rejoindre.';
                    } elseif ($isLive) {
                        $accent = 'blue';  $ico = 'lock';
                        $ttl = 'Accès réservé';
                        $lead = 'Cette visio est réservée aux participants inscrits de cet événement.';
                    } elseif ($meetingStatus === \App\Enums\MeetingStatus::Ended) {
                        $accent = 'muted'; $ico = 'check-circle';
                        $ttl = 'La visio est terminée';
                        $lead = $when
                            ? 'Cette séance s’est tenue le '.$when.'.'
                            : 'Cette séance est terminée.';
                    } elseif ($meetingStatus === \App\Enums\MeetingStatus::Scheduled) {
                        $accent = 'blue';  $ico = 'calendar';
                        $ttl = 'La visio n’a pas encore commencé';
                        $lead = $when
                            ? 'La salle ouvrira automatiquement peu avant le début. Gardez cette page ouverte : elle vous y conduira.'
                            : 'La salle ouvrira dès le début de la séance.';
                    } else {
                        $accent = 'muted'; $ico = 'video';
                        $ttl = 'Aucune visio prévue';
                        $lead = 'Aucune visioconférence n’est programmée pour cette séance.';
                    }
                @endphp
                <div class="mlobby-card mlobby-card--{{ $accent }}">
                    <span class="mlobby-ico"><x-ukc.icon :name="$ico" :size="30" /></span>
                    <h1 class="mlobby-ttl">{{ $ttl }}</h1>
                    <p class="mlobby-lead">{{ $lead }}</p>

                    @if (! $isLive && $meetingStatus === \App\Enums\MeetingStatus::Scheduled && $when)
                        <div class="mlobby-when">
                            <x-ukc.icon name="calendar" :size="20" />
                            <div><small>Début prévu</small><strong>{{ $when }}</strong></div>
                        </div>
                    @endif

                    <div class="mlobby-acts">
                        @if (! $isLive && $meetingStatus === \App\Enums\MeetingStatus::Ended && $recordingUrl)
                            <a href="{{ $recordingUrl }}" target="_blank" rel="noopener" class="ukc-btn ukc-btn--blue">
                                <x-ukc.icon name="video" :size="16" /> Voir le replay
                            </a>
                        @endif
                        @if ($isLive && $isGuest)
                            <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--blue">
                                <x-ukc.icon name="login" :size="16" /> Se connecter
                            </a>
                        @endif
                        <a href="{{ $backUrl }}" class="ukc-btn ukc-btn--outline">Retour</a>
                    </div>
                </div>

                {{-- Salle d'attente : une séance programmée s'ouvre automatiquement
                     à l'approche de l'horaire (cf. EventSession::syncMeetingStatus).
                     On recharge périodiquement pour y entrer sans geste manuel. --}}
                @if (! $isLive && $meetingStatus === \App\Enums\MeetingStatus::Scheduled)
                    <script>setTimeout(function () { location.reload(); }, 60000);</script>
                @endif
            @endif
        </main>
    </div>
@endif
</x-layouts.app>
