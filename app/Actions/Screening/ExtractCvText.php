<?php

namespace App\Actions\Screening;

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Throwable;
use UnexpectedValueException;
use ZipArchive;

class ExtractCvText
{
    public function handle(ApplicationDocument $document): string
    {
        $application = $document->application;
        $disk = Storage::disk('local');
        $directory = "applications/{$application->company_id}/{$application->id}/";
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($document->file_path));

        if ($document->type !== 'resume' || ! str_starts_with($document->file_path, $directory)
            || ! $root || ! $path || ! is_file($path)
            || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR.$directory)) {
            throw new UnexpectedValueException('Berkas CV tidak ditemukan di penyimpanan privat lamaran.');
        }

        if (filesize($path) > 5 * 1024 * 1024) {
            throw new UnexpectedValueException('Ukuran CV melebihi batas 5 MB.');
        }

        try {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'pdf' && (new \finfo(FILEINFO_MIME_TYPE))->file($path) === 'application/pdf') {
                $config = new Config;
                $config->setRetainImageContent(false);
                $config->setDecodeMemoryLimit(20 * 1024 * 1024);
                $config->setMaxNestingDepth(100);
                $text = (new Parser([], $config))->parseFile($path)->getText();
            } elseif ($extension === 'docx') {
                $this->checkArchive($path);
                $reader = IOFactory::createReader('Word2007');
                $reader->setImageLoading(false); // Never resolve embedded/external images or URLs.
                $word = $reader->load($path);
                $nodes = 0;
                $text = '';
                foreach ($word->getSections() as $section) {
                    $text .= $this->text($section, $nodes)."\n";
                }
            } else {
                throw new UnexpectedValueException('Format CV harus PDF atau DOCX yang valid.');
            }
        } catch (UnexpectedValueException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new UnexpectedValueException('CV rusak, terenkripsi, atau tidak dapat dibaca. Unggah ulang CV berbasis teks.');
        }

        $text = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', mb_convert_encoding($text, 'UTF-8', 'UTF-8')) ?? '');

        if ($text === '') {
            throw new UnexpectedValueException('Teks CV kosong. PDF hasil scan memerlukan OCR yang belum tersedia.');
        }
        if (mb_strlen($text) > config('ai.max_cv_characters')) {
            throw new UnexpectedValueException('Teks CV terlalu panjang untuk dianalisis. Unggah versi CV yang lebih ringkas.');
        }

        return $text;
    }

    private function checkArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new UnexpectedValueException('Berkas DOCX rusak atau bukan dokumen Word yang valid.');
        }

        try {
            if ($zip->numFiles > 500 || $zip->locateName('word/document.xml') === false) {
                throw new UnexpectedValueException('Struktur DOCX tidak valid atau terlalu kompleks.');
            }
            $total = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $total += $entry['size'];
                if ($total > config('ai.max_docx_uncompressed_bytes')) {
                    throw new UnexpectedValueException('Isi DOCX setelah dekompresi terlalu besar.');
                }
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                if (preg_match('/\.(xml|rels)$/i', $entry['name'])
                    && preg_match('/<!\s*(DOCTYPE|ENTITY)/i', str_replace("\0", '', $zip->getFromIndex($index)))) {
                    throw new UnexpectedValueException('DOCX mengandung deklarasi XML yang tidak diizinkan.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function text(object $element, int &$nodes, int $depth = 0): string
    {
        if (++$nodes > 20000 || $depth > 50) {
            throw new UnexpectedValueException('Struktur DOCX terlalu kompleks.');
        }
        foreach (['getElements', 'getRows', 'getCells'] as $method) {
            if (method_exists($element, $method)) {
                $text = '';
                foreach ($element->{$method}() as $child) {
                    $text .= $this->text($child, $nodes, $depth + 1)."\n";
                    if (mb_strlen($text) > config('ai.max_cv_characters')) {
                        throw new UnexpectedValueException('Teks CV terlalu panjang untuk dianalisis.');
                    }
                }

                return $text;
            }
        }
        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            return is_object($text) ? $this->text($text, $nodes, $depth + 1)
                : (is_array($text) ? implode(' ', array_filter($text, 'is_string')) : (string) $text);
        }

        return '';
    }
}
