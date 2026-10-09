import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const [inputPath, outputDir, resultPath, appPath, hyperframesVersion] = process.argv.slice(2);
const input = JSON.parse(readFileSync(inputPath, 'utf8'));
const composition = join(outputDir, 'composition');
const assets = join(composition, 'assets');
const guide = input.guide ?? {};
const scenario = input.creative?.scenario ?? {};

for (const path of [composition, assets, join(assets, 'fonts')]) mkdirSync(path, { recursive: true });

const copy = (from, to) => { if (existsSync(from)) copyFileSync(from, to); };
copy(join(appPath, 'public', 'img', 'logo-uk.png'), join(assets, 'logo-uk.png'));
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
const title = concise(guide.title, 'Prise en main GEVES', 110);
const intro = concise(guide.intro, 'Guides pratiques pour utiliser la plateforme.', 300);
const hook = concise(scenario.hook, 'Guide pratique GEVES', 95);
const instruction = concise(guide.instruction, 'Un parcours adapté à votre rôle.', 190);
const introDuration = 7;
const contextDuration = 6;
const stepDuration = 8;
const outroDuration = 7;
const duration = Math.max(36, introDuration + contextDuration + (steps.length * stepDuration) + outroDuration);

// Lit musical local : arpège discret, normalisé à un niveau clairement audible dans la composition.
const music = join(assets, 'geves-bed.m4a');
const musicExpression = '0.18*sin(2*PI*110*t)*(0.62+0.38*sin(2*PI*0.18*t)*sin(2*PI*0.18*t))+0.11*sin(2*PI*220*t)*(0.35+0.65*sin(2*PI*1.35*t)*sin(2*PI*1.35*t))+0.075*sin(2*PI*277.18*t)*(0.25+0.75*sin(2*PI*1.35*t+0.7)*sin(2*PI*1.35*t+0.7))+0.045*sin(2*PI*329.63*t)*(0.25+0.75*sin(2*PI*1.35*t+1.4)*sin(2*PI*1.35*t+1.4))';
run('ffmpeg', ['-y', '-f', 'lavfi', '-i', `aevalsrc=${musicExpression}:d=${duration}:s=44100`, '-af', `afade=t=in:st=0:d=1.2,afade=t=out:st=${Math.max(0, duration - 3)}:d=3,loudnorm=I=-19:TP=-1.5:LRA=5`, '-c:a', 'aac', '-b:a', '192k', music]);

const stepSections = steps.map((step, index) => {
  const start = introDuration + contextDuration + (index * stepDuration);
  const previous = index > 0 ? 'Étape précédente terminée' : 'Point de départ du parcours';
  const following = index + 1 < steps.length ? 'Suite du guide à venir' : 'Parcours terminé';
  const progress = Math.round(((index + 1) / steps.length) * 100);

  return `<section id="scene-step-${index + 1}" class="clip step-scene" data-start="${start}" data-duration="${stepDuration}" data-track-index="${index + 2}"><div class="inner"><div class="step-top reveal delay1"><div class="eyebrow">Parcours guidé</div><div class="progress">${index + 1} <span>/ ${steps.length}</span></div></div><div class="step-layout"><div class="step-number pop p1">${String(index + 1).padStart(2, '0')}</div><div><div class="step-label reveal delay1">ÉTAPE ${index + 1}</div><h2 class="reveal delay2">${esc(step)}</h2><p class="reveal delay3">${esc(instruction)}</p></div></div><div class="step-rail reveal delay3"><span>${esc(previous)}</span><div class="rail"><i style="width:${progress}%"></i></div><span>${esc(following)}</span></div></div></section>`;
}).join('');

const html = `<!doctype html>
<html lang="fr" data-resolution="landscape"><head><meta charset="UTF-8"><meta name="viewport" content="width=1920,height=1080"><style>
@font-face{font-family:Jost;src:url('assets/fonts/jost-400.woff2')}@font-face{font-family:Jost;src:url('assets/fonts/jost-700.woff2');font-weight:700}@font-face{font-family:Jost;src:url('assets/fonts/jost-800.woff2');font-weight:800}*{box-sizing:border-box}html,body{margin:0;background:#0f172a}#root{position:relative;width:100%;height:100%;overflow:hidden;font-family:Jost,Arial,sans-serif;color:#0f172a}.clip{position:absolute;inset:0;overflow:hidden}.inner{width:100%;height:100%;position:relative}.brand{display:flex;align-items:center;gap:22px;font-weight:800;color:#223ea4;font-size:29px}.brand img{width:86px;height:auto}.brand small{display:block;color:#64748b;font-size:15px;font-weight:400;margin-top:3px}.eyebrow{font-size:20px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}.s1,.s4{background:linear-gradient(128deg,#10265f,#223ea4 58%,#087764);color:#fff}.s1 .inner,.s4 .inner{padding:100px 145px;display:flex;flex-direction:column;justify-content:space-between}.s1 .brand,.s4 .brand{background:#fff;border-radius:18px;padding:18px 24px;width:max-content}.s1 h1{font-size:84px;line-height:.98;max-width:1150px;margin:0}.s1 p{font-size:30px;line-height:1.24;max-width:1100px;color:#dbeafe;margin:26px 0 0}.s1 .orb,.s4 .orb{position:absolute;width:790px;height:790px;border:1px solid rgba(255,255,255,.18);border-radius:50%;right:-215px;top:-280px;box-shadow:0 0 0 70px rgba(255,255,255,.035),0 0 0 140px rgba(255,255,255,.025)}.footer{font-size:22px;color:#dbeafe}.s2{background:linear-gradient(140deg,#eef4ff,#f8fafc 58%,#edfdf4)}.s2 .inner{padding:94px 125px}.s2 h2{font-size:68px;line-height:1.02;max-width:1100px;margin:18px 0}.s2 p{font-size:28px;line-height:1.34;max-width:960px;color:#475569}.context-card{position:absolute;right:125px;bottom:100px;width:550px;padding:31px;border:1px solid #dce5f5;border-radius:22px;background:#fff;box-shadow:0 20px 40px rgba(30,64,175,.1)}.context-card b{font-size:20px;color:#223ea4}.context-card span{display:block;font-size:24px;line-height:1.35;margin-top:12px}.step-scene{background:linear-gradient(132deg,#f8fafc,#eaf1ff 55%,#edfbf1)}.step-scene .inner{padding:86px 125px;display:flex;flex-direction:column;justify-content:space-between}.step-top{display:flex;justify-content:space-between;align-items:center}.step-top .eyebrow{color:#223ea4}.progress{display:grid;place-items:center;width:92px;height:58px;border-radius:16px;background:#223ea4;color:#fff;font-size:27px;font-weight:800}.progress span{font-size:17px;font-weight:400;color:#dbeafe}.step-layout{display:grid;grid-template-columns:230px 1fr;gap:58px;align-items:center}.step-number{display:grid;place-items:center;width:204px;height:204px;border-radius:42px;background:linear-gradient(145deg,#223ea4,#009933);color:#fff;font-size:74px;font-weight:800;box-shadow:0 25px 55px rgba(34,62,164,.24)}.step-label{color:#087764;font-size:22px;font-weight:800;letter-spacing:.16em}.step-layout h2{font-size:57px;line-height:1.1;max-width:1250px;margin:18px 0}.step-layout p{font-size:25px;color:#475569;margin:0}.step-rail{display:grid;grid-template-columns:315px 1fr 315px;align-items:center;gap:24px;color:#475569;font-size:18px}.step-rail span:last-child{text-align:right}.rail{height:10px;border-radius:99px;background:#d8e2f2;overflow:hidden}.rail i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#223ea4,#009933)}.s4 .inner{align-items:center;justify-content:center;text-align:center}.s4 h2{font-size:70px;line-height:1.06;max-width:1260px;margin:50px 0 0}.s4 p{font-size:27px;color:#dbeafe;margin-top:24px}.reveal{opacity:0;animation:rise .7s cubic-bezier(.2,0,0,1) forwards}.delay1{animation-delay:.25s}.delay2{animation-delay:.55s}.delay3{animation-delay:.85s}.pop{opacity:0;animation:pop .55s cubic-bezier(.2,0,0,1) forwards}.p1{animation-delay:.15s}@keyframes rise{from{opacity:0;transform:translateY(35px)}to{opacity:1;transform:translateY(0)}}@keyframes pop{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
</style></head><body><main id="root" data-composition-id="main" data-no-timeline data-start="0" data-width="1920" data-height="1080" data-duration="${duration}"><audio id="bgm" data-start="0" data-duration="${duration}" data-track-index="20" data-volume="0.72" src="assets/geves-bed.m4a"></audio><section id="scene-intro" class="clip s1" data-start="0" data-duration="${introDuration}" data-track-index="0"><div class="inner"><div class="orb"></div><div class="brand reveal delay1"><img src="assets/logo-uk.png"><span>GEVES<small>Université de Kara</small></span></div><div><div class="eyebrow reveal delay2">${esc(hook)}</div><h1 class="reveal delay2">${esc(title)}</h1><p class="reveal delay3">${esc(intro)}</p></div><div class="footer reveal delay3">Une expérience claire, adaptée à chaque rôle.</div></div></section><section id="scene-context" class="clip s2" data-start="${introDuration}" data-duration="${contextDuration}" data-track-index="1"><div class="inner"><div class="eyebrow reveal delay1" style="color:#223ea4">Introduction du guide</div><h2 class="reveal delay2">${esc(intro)}</h2><p class="reveal delay3">Le parcours suivant reprend chaque étape dans son ordre afin de vous accompagner jusqu’à la fin.</p><div class="context-card pop p1"><b>Objectif</b><span>${esc(instruction)}</span></div></div></section>${stepSections}<section id="scene-outro" class="clip s4" data-start="${duration - outroDuration}" data-duration="${outroDuration}" data-track-index="${steps.length + 2}"><div class="inner"><div class="orb"></div><div class="brand reveal delay1"><img src="assets/logo-uk.png"><span>GEVES<small>Prise en main</small></span></div><h2 class="reveal delay2">Votre parcours est prêt.</h2><p class="reveal delay3">${esc(title)}</p></div></section></main></body></html>`;

const correctedHtml = html
  .replace('</style>', '.progress{width:100px;height:68px}</style>')
  .replaceAll('<div class="orb"></div>', '<div class="orb" data-layout-allow-overflow></div>');
writeFileSync(join(composition, 'index.html'), correctedHtml);
writeFileSync(join(composition, 'hyperframes.json'), JSON.stringify({ $schema: 'https://hyperframes.heygen.com/schema/hyperframes.json', authoringSkill: 'brag' }, null, 2));
writeFileSync(join(outputDir, 'brag-plan.md'), `# Plan de réalisation\n\n- Guide : ${title}\n- Durée : ${duration} secondes\n- Format : 1920×1080\n- Scènes : introduction, objectif, ${steps.length} étape(s) séquentielle(s), conclusion\n- Direction : palette GEVES, Jost, lit musical original audible et mouvements sobres.\n`);
writeFileSync(join(outputDir, 'composition-brief.md'), `# Brief de composition\n\n## Introduction\n${intro}\n\n## Étapes\n${steps.map((step, index) => `${index + 1}. ${step}`).join('\n')}\n`);
writeFileSync(join(outputDir, 'share-copy.txt'), `Découvrez le guide « ${title} » dans GEVES.`);

const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';
run(npx, ['--yes', `hyperframes@${hyperframesVersion}`, 'check', '.']);
const video = join(outputDir, 'brag.mp4');
run(npx, ['--yes', `hyperframes@${hyperframesVersion}`, 'render', '.', '--output', video, '--workers', '1', '--low-memory-mode', '--no-browser-gpu', '--quality', 'standard']);
const poster = join(outputDir, 'brag.jpg');
run('ffmpeg', ['-y', '-ss', '2', '-i', video, '-frames:v', '1', poster], outputDir);
writeFileSync(resultPath, JSON.stringify({ video: resolve(video), poster: resolve(poster), summary: `Vidéo de prise en main : ${title} (${duration} secondes)` }));
