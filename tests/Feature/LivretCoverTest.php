<?php

use App\Documents\LivretCover;
use App\Models\Event;
use App\Support\TaskDocSpace;

it('livre le PDF même si DocSpace refuse de supprimer sa conversion temporaire', function () {
    $makePdf = static function (): string {
        $pdf = new FPDF;
        $pdf->AddPage();

        return $pdf->Output('S');
    };

    $cover = $makePdf();
    $book = $makePdf();
    $event = new Event([
        'title' => 'Colloque de contrôle',
        'settings' => ['doc' => ['livret.cover_docspace_file_id' => '123', 'livret.cover_docspace_active' => true]],
    ]);
    $docspace = new class($cover) extends TaskDocSpace
    {
        public function __construct(private string $cover) {}

        public function roomId(?Event $event = null): string
        {
            return '1';
        }

        public function saveAsPdf(string $fileId, string $folderId): string
        {
            return '456';
        }

        public function downloadFile(string $fileId): string
        {
            return $this->cover;
        }

        public function deleteFile(string $fileId): void
        {
            throw new RuntimeException('DocSpace HTTP 403');
        }
    };

    expect(LivretCover::pdf($event, $book, $docspace))->toStartWith('%PDF-');
});
