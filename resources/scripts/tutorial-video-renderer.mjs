import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { extname, join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const [inputPath, outputDir, resultPath, appPath, hyperframesVersion] = process.argv.slice(2);
const input = JSON.parse(readFileSync(inputPath, 'utf8'));
const composition = join(outputDir, 'composition');
const assets = join(composition, 'assets');
const guide = input.guide ?? {};
const scenario = input.creative?.scenario ?? {};

for (const path of [composition, assets, join(assets, 'fonts')]) mkdirSync(path, { recursive: true });

const copy = (from, to) => { if (existsSync(from)) copyFileSync(from, to); };
copy(input.brand?.logo || join(appPath, 'public', 'img', 'apple-touch-icon.png'), join(assets, 'logo-mark.png'));
copy(join(appPath, 'public', 'fonts', 'jost-400.woff2'), join(assets, 'fonts', 'jost-400.woff2'));
copy(join(appPath, 'public', 'fonts', 'jost-700.woff2'), join(assets, 'fonts', 'jost-700.woff2'));
copy(join(appPath, 'public', 'fonts', 'jost-800.woff2'), join(assets, 'fonts', 'jost-800.woff2'));

const run = (command, args, cwd = composition) => {
  const result = spawnSync(command, args, { cwd, encoding: 'utf8', windowsHide: true, shell: process.platform === 'win32' });
  if (result.status !== 0) throw new Error(`${command} ${args.join(' ')}: ${(result.error?.message || result.stderr || result.stdout || 'échec du rendu').trim()}`);
  return result;
};

const esc = (value = '') => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
const concise = (value, fallback, length = 180) => String(value || fallback).replace(/\s+/g, ' ').trim().slice(0, length);
const steps = Array.isArray(guide.steps) && guide.steps.length > 0
  ? guide.steps.map(step => concise(step, 'Suivez les indications de ce guide.', 280))
  : ['Suivez les indications de ce guide.'];
const captures = Array.isArray(input.captures) ? input.captures : [];
if (captures.length < steps.length) {
  throw new Error('Les captures réelles de chaque étape sont nécessaires avant le rendu de la vidéo.');
}
const title = concise(guide.title, 'Prise en main GEVES', 110);
const intro = concise(guide.intro, 'Guides pratiques pour utiliser la plateforme.', 300);
const hook = concise(scenario.hook, 'Guide pratique GEVES', 95);
// La précision libre de l'administrateur pilote les IA de préproduction ; elle
// ne doit jamais être affichée telle quelle dans la vidéo finale.
const instruction = 'Repérez l’action mise en avant sur l’écran réel de GEVES.';
const introDuration = 7;
const contextDuration = 6;
const stepDuration = 10;
const outroDuration = 7;
const duration = Math.max(36, introDuration + contextDuration + (steps.length * stepDuration) + outroDuration);

// Les lits musicaux sont produits localement. Ils peuvent donc accompagner chaque
// tutoriel sans introduire de dépendance ou de licence externe.
const music = join(assets, 'geves-bed.m4a');
const defaultMusic = join(appPath, 'resources', 'media', 'tutorials', 'uplifting-corporate-loop.mp3');
const musicChoice = ['signature', 'calm', 'dynamic', 'none'].includes(input.music?.choice)
  ? input.music.choice
  : 'signature';
const customMusic = input.music?.path;
const musicExpressions = {
  // Signature GEVES : basse chaude, arpège lumineux et pulsation douce.
  signature: '0.14*sin(2*PI*82.41*t)*(0.58+0.42*sin(2*PI*0.16*t)*sin(2*PI*0.16*t))+0.09*sin(2*PI*164.81*t)*(0.3+0.7*sin(2*PI*1.25*t)*sin(2*PI*1.25*t))+0.075*sin(2*PI*246.94*t)*(0.2+0.8*sin(2*PI*1.25*t+0.5)*sin(2*PI*1.25*t+0.5))+0.06*sin(2*PI*329.63*t)*(0.15+0.85*sin(2*PI*1.25*t+1.05)*sin(2*PI*1.25*t+1.05))+0.045*sin(2*PI*493.88*t)*(0.1+0.9*sin(2*PI*1.25*t+1.58)*sin(2*PI*1.25*t+1.58))',
  calm: '0.09*sin(2*PI*146.83*t)*(0.7+0.3*sin(2*PI*0.09*t)*sin(2*PI*0.09*t))+0.055*sin(2*PI*220*t)*(0.35+0.65*sin(2*PI*0.42*t)*sin(2*PI*0.42*t))+0.04*sin(2*PI*293.66*t)*(0.25+0.75*sin(2*PI*0.42*t+0.8)*sin(2*PI*0.42*t+0.8))',
  dynamic: '0.12*sin(2*PI*98*t)*(0.55+0.45*sin(2*PI*0.28*t)*sin(2*PI*0.28*t))+0.07*sin(2*PI*196*t)*(0.25+0.75*sin(2*PI*1.75*t)*sin(2*PI*1.75*t))+0.055*sin(2*PI*293.66*t)*(0.2+0.8*sin(2*PI*1.75*t+0.65)*sin(2*PI*1.75*t+0.65))+0.035*sin(2*PI*440*t)*(0.2+0.8*sin(2*PI*3.5*t)*sin(2*PI*3.5*t))',
};
const musicFilter = `afade=t=in:st=0:d=1.2,afade=t=out:st=${Math.max(0, duration - 3)}:d=3,loudnorm=I=-17:TP=-1.5:LRA=5`;
let musicTag = '';

// Un fichier choisi par l'administrateur a toujours priorité, y compris si le
// sélecteur était resté sur « Sans musique » avant le téléversement.
const selectedMusic = customMusic && existsSync(customMusic)
  ? customMusic
  : musicChoice === 'signature' && existsSync(defaultMusic) ? defaultMusic : null;

if (musicChoice !== 'none' || selectedMusic) {
  if (selectedMusic) {
    const copiedMusic = join(assets, `selected-music${extname(selectedMusic) || '.m4a'}`);
    copy(selectedMusic, copiedMusic);
    run('ffmpeg', ['-y', '-stream_loop', '-1', '-i', copiedMusic, '-t', String(duration), '-vn', '-af', musicFilter, '-c:a', 'aac', '-b:a', '192k', music]);
  } else {
    run('ffmpeg', ['-y', '-f', 'lavfi', '-i', `aevalsrc=${musicExpressions[musicChoice]}:d=${duration}:s=44100`, '-af', musicFilter, '-c:a', 'aac', '-b:a', '192k', music]);
  }

  musicTag = `<audio id="bgm" data-start="0" data-duration="${duration}" data-track-index="20" data-volume="0.78" src="assets/geves-bed.m4a"></audio>`;
}

const captureFor = (index) => {
  const capture = captures[index];
  const filename = `capture-${index + 1}.png`;
  copy(capture.path, join(assets, filename));
  if (!existsSync(join(assets, filename))) throw new Error(`Capture introuvable pour l’étape ${index + 1}.`);

  return `<figure class="screen-capture pop p1" aria-label="Capture réelle GEVES : ${esc(capture.title || `étape ${index + 1}`)}"><img src="assets/${filename}" alt="Capture réelle de ${esc(capture.title || 'GEVES')}"><figcaption>Écran réel GEVES · ${esc(capture.title || `Étape ${index + 1}`)}</figcaption></figure>`;
};

const stepSections = steps.map((step, index) => {
  const start = introDuration + contextDuration + (index * stepDuration);
  const previous = index > 0 ? 'Étape précédente terminée' : 'Point de départ du parcours';
  const following = index + 1 < steps.length ? 'Suite du guide à venir' : 'Parcours terminé';
  const progress = Math.round(((index + 1) / steps.length) * 100);

  return `<section id="scene-step-${index + 1}" class="clip step-scene" data-start="${start}" data-duration="${stepDuration}" data-track-index="${index + 2}"><div class="inner"><div class="step-top reveal delay1"><div class="eyebrow">Parcours guidé</div><div class="progress">${index + 1} <span>/ ${steps.length}</span></div></div><div class="step-layout"><div class="step-copy"><div class="step-number pop p1">${String(index + 1).padStart(2, '0')}</div><div class="step-label reveal delay1">ÉTAPE ${index + 1}</div><h2 class="reveal delay2">${esc(step)}</h2><p class="reveal delay3">${esc(instruction)}</p></div>${captureFor(index)}</div><div class="step-rail reveal delay3"><span>${esc(previous)}</span><div class="rail"><i style="width:${progress}%"></i></div><span>${esc(following)}</span></div></div></section>`;
}).join('');

const html = `<!doctype html>
<html lang="fr" data-resolution="landscape"><head><meta charset="UTF-8"><meta name="viewport" content="width=1920,height=1080"><style>
@font-face{font-family:Jost;src:url('assets/fonts/jost-400.woff2')}@font-face{font-family:Jost;src:url('assets/fonts/jost-700.woff2');font-weight:700}@font-face{font-family:Jost;src:url('assets/fonts/jost-800.woff2');font-weight:800}*{box-sizing:border-box}html,body{margin:0;background:#0f172a}#root{position:relative;width:100%;height:100%;overflow:hidden;font-family:Jost,Arial,sans-serif;color:#0f172a}.clip{position:absolute;inset:0;overflow:hidden}.inner{width:100%;height:100%;position:relative}.brand{display:flex;align-items:center;gap:18px;font-weight:800;color:#223ea4;font-size:29px}.brand img{width:62px;height:62px;object-fit:contain}.brand small{display:block;color:#526172;font-size:15px;font-weight:400;margin-top:3px}.eyebrow{font-size:20px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}.s1,.s4{background:linear-gradient(128deg,#10265f,#223ea4 58%,#087764);color:#fff}.s1 .inner,.s4 .inner{padding:100px 145px;display:flex;flex-direction:column;justify-content:space-between}.s1 .brand,.s4 .brand{background:#fff;border-radius:18px;padding:14px 20px;width:max-content}.s1 h1{font-size:84px;line-height:.98;max-width:1150px;margin:0}.s1 p{font-size:30px;line-height:1.24;max-width:1100px;color:#dbeafe;margin:26px 0 0}.s1 .orb,.s4 .orb{position:absolute;width:790px;height:790px;border:1px solid rgba(255,255,255,.18);border-radius:50%;right:-215px;top:-280px;box-shadow:0 0 0 70px rgba(255,255,255,.035),0 0 0 140px rgba(255,255,255,.025)}.footer{font-size:22px;color:#dbeafe}.s2{background:linear-gradient(140deg,#eef4ff,#f8fafc 58%,#edfdf4)}.s2 .inner{padding:94px 125px}.s2 h2{font-size:68px;line-height:1.02;max-width:1100px;margin:18px 0}.s2 p{font-size:28px;line-height:1.34;max-width:960px;color:#475569}.context-card{position:absolute;right:125px;bottom:100px;width:550px;padding:31px;border:1px solid #dce5f5;border-radius:22px;background:#fff;box-shadow:0 20px 40px rgba(30,64,175,.1)}.context-card b{font-size:20px;color:#223ea4}.context-card span{display:block;font-size:24px;line-height:1.35;margin-top:12px}.step-scene{background:linear-gradient(132deg,#f8fafc,#eaf1ff 55%,#edfbf1)}.step-scene .inner{padding:76px 118px 62px;display:flex;flex-direction:column;justify-content:space-between}.step-top{display:flex;justify-content:space-between;align-items:center}.step-top .eyebrow{color:#223ea4}.progress{display:grid;place-items:center;width:92px;height:58px;border-radius:16px;background:#223ea4;color:#fff;font-size:27px;font-weight:800}.progress span{font-size:17px;font-weight:400;color:#dbeafe}.step-layout{display:grid;grid-template-columns:minmax(0,.88fr) minmax(0,1.12fr);gap:62px;align-items:center}.step-copy{position:relative}.step-number{display:grid;place-items:center;width:106px;height:106px;border-radius:28px;background:linear-gradient(145deg,#223ea4,#009933);color:#fff;font-size:42px;font-weight:800;box-shadow:0 18px 38px rgba(34,62,164,.22);margin-bottom:34px}.step-label{color:#087764;font-size:20px;font-weight:800;letter-spacing:.16em}.step-layout h2{font-size:45px;line-height:1.08;margin:16px 0}.step-layout p{font-size:23px;color:#475569;margin:0}.screen-sim{height:560px;border:11px solid #fff;border-radius:28px;overflow:hidden;background:#f8fafc;box-shadow:0 30px 70px rgba(15,23,42,.22);position:relative}.sim-head{height:72px;background:#fff;display:flex;align-items:center;gap:12px;padding:0 25px;border-bottom:1px solid #e2e8f0;color:#223ea4}.sim-head img{width:35px;height:35px;object-fit:contain}.sim-head strong{font-size:22px}.sim-head span{font-size:14px;color:#526172}.sim-head i{margin-left:auto;width:155px;height:27px;border-radius:8px;background:#f1f5f9}.sim-head em{font-style:normal;font-size:26px;color:#526172}.sim-head b{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#223ea4,#009933);color:#fff}.sim-body{height:calc(100% - 72px);display:grid;grid-template-columns:86px 1fr}.sim-body aside{background:#10265f;padding:36px 21px;display:flex;align-items:center;flex-direction:column;gap:26px}.sim-home{font-size:30px;color:#fff;margin-bottom:16px}.sim-nav{width:42px;height:10px;border-radius:99px;background:rgba(255,255,255,.35)}.sim-nav.active{height:42px;border-radius:12px;background:#009933;box-shadow:0 8px 22px rgba(0,153,51,.42)}.sim-page{position:relative;padding:34px 40px;background:linear-gradient(135deg,#eff5ff,#f8fafc 55%,#effaf3)}.sim-crumb{font-size:15px;color:#526172}.sim-crumb small{padding:0 8px}.sim-page-head{display:flex;align-items:center;justify-content:space-between;margin:16px 0 27px}.sim-page-head b{display:block;font-size:29px}.sim-page-head span{display:block;color:#526172;font-size:15px;margin-top:4px}.sim-page-head button{border:0;border-radius:9px;background:#223ea4;color:#fff;padding:13px 17px;font:700 15px Jost}.sim-card{background:#fff;border:1px solid #dce5f5;border-radius:17px;padding:24px;box-shadow:0 12px 28px rgba(30,64,175,.08)}.sim-card-top{display:flex;align-items:center;gap:10px}.sim-pill{font-weight:800;font-size:12px;color:#087764;background:#e9f8ee;padding:7px 10px;border-radius:99px}.sim-card-top i{display:block;width:68px;height:9px;border-radius:99px;background:#e5e7eb}.sim-focus{display:grid;grid-template-columns:52px 1fr 38px;gap:14px;align-items:center;margin:22px 0;padding:17px;border:2px solid #223ea4;border-radius:14px;background:#f8fbff}.sim-icon{display:grid;place-items:center;width:52px;height:52px;border-radius:13px;background:#e8eeff;color:#223ea4;font-weight:800}.sim-focus strong{font-size:17px;line-height:1.25;display:block}.sim-focus small{display:block;color:#526172;font-size:13px;margin-top:4px}.sim-focus button{border:0;border-radius:50%;background:#009933;color:#0f172a;width:32px;height:32px;font-size:17px}.sim-row{display:flex;gap:11px;align-items:center;margin-top:13px}.sim-row span{width:22px;height:22px;border-radius:7px;background:#e8eeff}.sim-row i{height:9px;border-radius:99px;background:#e5e7eb;width:42%}.sim-row i:last-child{width:25%}.sim-row.short i{width:31%}.sim-cursor{position:absolute;right:52px;bottom:42px;color:#223ea4;font-size:47px;filter:drop-shadow(0 5px 5px rgba(15,23,42,.26));animation:cursor 1.7s ease-in-out infinite}.step-rail{display:grid;grid-template-columns:315px 1fr 315px;align-items:center;gap:24px;color:#475569;font-size:18px}.step-rail span:last-child{text-align:right}.rail{height:10px;border-radius:99px;background:#d8e2f2;overflow:hidden}.rail i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#223ea4,#009933)}.s4 .inner{align-items:center;justify-content:center;text-align:center}.s4 h2{font-size:70px;line-height:1.06;max-width:1260px;margin:50px 0 0}.s4 p{font-size:27px;color:#dbeafe;margin-top:24px}.reveal{opacity:0;animation:rise .7s cubic-bezier(.2,0,0,1) forwards}.delay1{animation-delay:.25s}.delay2{animation-delay:.55s}.delay3{animation-delay:.85s}.pop{opacity:0;animation:pop .55s cubic-bezier(.2,0,0,1) forwards}.p1{animation-delay:.15s}@keyframes rise{from{opacity:0;transform:translateY(35px)}to{opacity:1;transform:translateY(0)}}@keyframes pop{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes cursor{0%,100%{transform:translate(0,0) scale(1)}55%{transform:translate(-28px,-38px) scale(.9)}}
</style></head><body><main id="root" data-composition-id="main" data-no-timeline data-start="0" data-width="1920" data-height="1080" data-duration="${duration}">${musicTag}<section id="scene-intro" class="clip s1" data-start="0" data-duration="${introDuration}" data-track-index="0"><div class="inner"><div class="orb"></div><div class="brand reveal delay1"><img src="assets/logo-mark.png"><span>GEVES<small>Université de Kara</small></span></div><div><div class="eyebrow reveal delay2">${esc(hook)}</div><h1 class="reveal delay2">${esc(title)}</h1><p class="reveal delay3">${esc(intro)}</p></div><div class="footer reveal delay3">Une expérience claire, adaptée à chaque rôle.</div></div></section><section id="scene-context" class="clip s2" data-start="${introDuration}" data-duration="${contextDuration}" data-track-index="1"><div class="inner"><div class="eyebrow reveal delay1" style="color:#223ea4">Introduction du guide</div><h2 class="reveal delay2">${esc(intro)}</h2><p class="reveal delay3">Le parcours suivant reprend chaque étape dans son ordre afin de vous accompagner jusqu’à la fin.</p><div class="context-card pop p1"><b>Objectif</b><span>${esc(instruction)}</span></div></div></section>${stepSections}<section id="scene-outro" class="clip s4" data-start="${duration - outroDuration}" data-duration="${outroDuration}" data-track-index="${steps.length + 2}"><div class="inner"><div class="orb"></div><div class="brand reveal delay1"><img src="assets/logo-mark.png"><span>GEVES<small>Prise en main</small></span></div><h2 class="reveal delay2">Votre parcours est prêt.</h2><p class="reveal delay3">${esc(title)}</p></div></section></main></body></html>`;

const correctedHtml = html
  .replace('</style>', '.progress{width:100px;height:68px}.brand{gap:22px}.brand img{width:auto;height:84px;max-width:300px;object-fit:contain}.screen-capture{height:560px;margin:0;border:11px solid #fff;border-radius:28px;overflow:hidden;background:#fff;box-shadow:0 30px 70px rgba(15,23,42,.22);position:relative}.screen-capture img{display:block;width:100%;height:100%;object-fit:cover;object-position:top;animation:capture-pan 10s ease-in-out both}.screen-capture figcaption{position:absolute;right:0;bottom:0;left:0;display:flex;align-items:center;height:42px;padding:0 19px;color:#fff;background:linear-gradient(90deg,rgba(15,23,42,.86),rgba(15,23,42,.48));font-size:15px;font-weight:700}@keyframes capture-pan{from{transform:scale(1.018);transform-origin:50% 0}to{transform:scale(1.075);transform-origin:50% 18%}}</style>')
  .replaceAll('<div class="orb"></div>', '<div class="orb" data-layout-allow-overflow></div>');
writeFileSync(join(composition, 'index.html'), correctedHtml);
writeFileSync(join(composition, 'hyperframes.json'), JSON.stringify({ $schema: 'https://hyperframes.heygen.com/schema/hyperframes.json', authoringSkill: 'brag' }, null, 2));
writeFileSync(join(outputDir, 'brag-plan.md'), `# Plan de réalisation\n\n- Guide : ${title}\n- Durée : ${duration} secondes\n- Format : 1920×1080\n- Scènes : introduction, objectif, ${steps.length} étape(s) séquentielle(s), conclusion\n- Musique : ${musicChoice === 'none' && !selectedMusic ? 'sans musique' : customMusic && existsSync(customMusic) ? 'fichier fourni par l’administrateur' : musicChoice === 'signature' ? 'Uplifting Corporate Loop (piste GEVES par défaut)' : musicChoice}\n- Direction : monogramme GEVES compact agrandi, captures authentifiées de GEVES, musique et mouvements sobres.\n`);
writeFileSync(join(outputDir, 'composition-brief.md'), `# Brief de composition\n\n## Introduction\n${intro}\n\n## Étapes\n${steps.map((step, index) => `${index + 1}. ${step}`).join('\n')}\n`);
writeFileSync(join(outputDir, 'share-copy.txt'), `Découvrez le guide « ${title} » dans GEVES.`);

const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';
run(npx, ['--yes', `hyperframes@${hyperframesVersion}`, 'check', '.']);
const video = join(outputDir, 'brag.mp4');
run(npx, ['--yes', `hyperframes@${hyperframesVersion}`, 'render', '.', '--output', video, '--workers', '1', '--low-memory-mode', '--no-browser-gpu', '--quality', 'standard']);
const poster = join(outputDir, 'brag.jpg');
run('ffmpeg', ['-y', '-ss', '2', '-i', video, '-frames:v', '1', poster], outputDir);
writeFileSync(resultPath, JSON.stringify({ video: resolve(video), poster: resolve(poster), summary: `Vidéo de prise en main : ${title} (${duration} secondes)` }));
