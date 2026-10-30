/**
 * Assemble le livre des résumés : couverture DocSpace, pages ajoutées, livre PDF.
 *
 * ⚠️⚠️ La spécification arrive dans un FICHIER JSON, jamais en arguments. Les
 * chemins et les titres contiennent des espaces, des accents et des guillemets ;
 * sous Windows, cmd les recoupe à sa façon et l'assemblage se ferait au mauvais
 * endroit - ou pas du tout.
 *
 * Deux actions :
 *
 *   {"action":"count","files":[...]}            -> {"pages":[n, ...]}
 *   {"action":"assemble", book, output, ...}    -> {"pages":n}
 *
 * Le comptage est appelé AVANT le rendu du livre : c'est lui qui dit combien de
 * pages blanches réserver pour chaque document ajouté.
 */
import { readFile, writeFile } from 'node:fs/promises';
import { PDFDocument, PDFName, PDFString, PDFHexString } from 'pdf-lib';

const specPath = process.argv[2];
if (!specPath) {
  throw new Error('Spécification d’assemblage manquante.');
}
const spec = JSON.parse(await readFile(specPath, 'utf8'));

const load = async (path) => PDFDocument.load(await readFile(path));

if (spec.action === 'count') {
  const pages = [];
  for (const file of spec.files ?? []) {
    pages.push((await load(file)).getPageCount());
  }
  process.stdout.write(JSON.stringify({ pages }));
  process.exit(0);
}

if (spec.action !== 'assemble') {
  throw new Error('Action d’assemblage inconnue.');
}

const book = await load(spec.book);
const bookPages = book.getPageCount();
if (bookPages < 1) {
  throw new Error('Le livre PDF ne contient aucune page.');
}

/**
 * Où se trouve chaque repère ? dompdf écrit un lien cliquable par page réservée ;
 * pdf-lib relit l'annotation et donne le NUMÉRO DE PAGE EXACT, quelle que soit la
 * longueur du livre. C'est la seule mesure fiable : le nombre de pages d'une
 * section dépend du contenu, et personne ne peut le prédire avant le rendu.
 */
const anchors = new Map();
book.getPages().forEach((page, index) => {
  const annots = page.node.Annots();
  if (!annots) return;
  for (let k = 0; k < annots.size(); k++) {
    const action = annots.lookup(k)?.lookup?.(PDFName.of('A'));
    const uri = action?.lookup?.(PDFName.of('URI'));
    if (uri instanceof PDFString || uri instanceof PDFHexString) {
      anchors.set(uri.decodeText(), index);
    }
  }
});

// --- Pages ajoutées ancrées à une section : elles REMPLACENT leurs pages réservées.
const replaced = new Map(); // index de la 1re page réservée -> { count, docIndex }
const extraDocs = [];
for (const entry of spec.replace ?? []) {
  const indexes = [];
  for (let k = 0; k < entry.pages; k++) {
    const index = anchors.get(`${entry.uri}/${k}`);
    if (index === undefined) {
      throw new Error(`Repère introuvable pour la page ajoutée « ${entry.title} ».`);
    }
    indexes.push(index);
  }
  // ⚠️ Les pages réservées DOIVENT se suivre : si elles ne se suivent pas, le
  // rendu a glissé et l'insertion écraserait du contenu du livre. On s'arrête.
  for (let k = 1; k < indexes.length; k++) {
    if (indexes[k] !== indexes[0] + k) {
      throw new Error(`Les pages réservées à « ${entry.title} » ne se suivent pas.`);
    }
  }
  const doc = await load(entry.file);
  if (doc.getPageCount() !== entry.pages) {
    throw new Error(`La page ajoutée « ${entry.title} » a changé de longueur pendant l’assemblage.`);
  }
  replaced.set(indexes[0], { count: entry.pages, docIndex: extraDocs.length });
  extraDocs.push(doc);
}

const merged = await PDFDocument.create();

// Pages du livre à conserver : tout sauf les pages réservées.
const reserved = new Set();
for (const [start, { count }] of replaced) {
  for (let k = 0; k < count; k++) reserved.add(start + k);
}
const keep = [];
for (let i = 0; i < bookPages; i++) if (!reserved.has(i)) keep.push(i);
const coverTarget = keep.length ? keep[0] : -1;
const kept = new Map();
(await merged.copyPages(book, keep)).forEach((page, k) => kept.set(keep[k], page));

const copiedExtras = [];
for (const doc of extraDocs) {
  copiedExtras.push(await merged.copyPages(doc, doc.getPageIndices()));
}

// Couverture DocSpace : elle prend la place de la couverture automatique, qui est
// la première page du livre rendu. Rien ne se glisse avant elle.
let cover = null;
if (spec.cover) {
  const coverDoc = await load(spec.cover);
  if (coverDoc.getPageCount() < 1) {
    throw new Error('La couverture PDF ne contient aucune page.');
  }
  [cover] = await merged.copyPages(coverDoc, [0]);
}

for (let i = 0; i < bookPages; i++) {
  const slot = replaced.get(i);
  if (slot) {
    for (const page of copiedExtras[slot.docIndex]) merged.addPage(page);
    continue;
  }
  if (!kept.has(i)) continue; // page réservée déjà traitée par son premier index
  merged.addPage(i === coverTarget && cover ? cover : kept.get(i));
}

// --- Pages ajoutées placées par NUMÉRO : elles se glissent, elles ne remplacent rien.
// ⚠️ Elles décalent donc tout ce qui suit, numéros imprimés compris : c'est la
// rançon de ce mode, et l'écran le dit au gestionnaire avant qu'il le choisisse.
let shift = 0;
for (const entry of [...(spec.insert ?? [])].sort((a, b) => a.page - b.page)) {
  const doc = await load(entry.file);
  const pages = await merged.copyPages(doc, doc.getPageIndices());
  const at = Math.max(0, Math.min(merged.getPageCount(), entry.page - 1 + shift));
  pages.forEach((page, k) => merged.insertPage(at + k, page));
  shift += pages.length;
}

merged.setTitle(spec.title ?? 'Livre des résumés');
merged.setAuthor(spec.author ?? 'GEVES');
await writeFile(spec.output, await merged.save({ useObjectStreams: false }));
process.stdout.write(JSON.stringify({ pages: merged.getPageCount() }));
