<?php

use App\AI\Exceptions\AiUnavailableException;
use App\AI\OfficeTextExtractor;

function officeArchive(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'office-test-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }
    $zip->close();
    try {
        return (string) file_get_contents($path);
    } finally {
        unlink($path);
    }
}

it('lit le texte d’un document Word ordinaire', function () {
    $bytes = officeArchive([
        'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Bonjour</w:t></w:r></w:p></w:body></w:document>',
    ]);

    expect(OfficeTextExtractor::extract('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $bytes))
        ->toBe('Bonjour');
});

it('refuse une archive Office trop volumineuse après décompression', function () {
    $bytes = officeArchive(['word/document.xml' => str_repeat('A', 33 * 1024 * 1024)]);

    expect(fn () => OfficeTextExtractor::extract('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $bytes))
        ->toThrow(AiUnavailableException::class, 'décompression');
});
