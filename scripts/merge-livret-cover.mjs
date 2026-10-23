import { readFile, writeFile } from 'node:fs/promises';
import { PDFDocument } from 'pdf-lib';

const [coverPath, bookPath, outputPath, title, author] = process.argv.slice(2);

if (!coverPath || !bookPath || !outputPath) {
  throw new Error('Chemins de fusion PDF manquants.');
}

const [cover, book] = await Promise.all([
  PDFDocument.load(await readFile(coverPath)),
  PDFDocument.load(await readFile(bookPath)),
]);

if (cover.getPageCount() < 1 || book.getPageCount() < 1) {
  throw new Error('La couverture ou le livre PDF ne contient aucune page.');
}

const merged = await PDFDocument.create();
const [firstPage] = await merged.copyPages(cover, [0]);
merged.addPage(firstPage);

if (book.getPageCount() > 1) {
  const rest = await merged.copyPages(
    book,
    Array.from({ length: book.getPageCount() - 1 }, (_, index) => index + 1),
  );
  for (const page of rest) merged.addPage(page);
}

merged.setTitle(title ?? 'Livre des résumés');
merged.setAuthor(author ?? 'GEVES');
await writeFile(outputPath, await merged.save({ useObjectStreams: false }));
