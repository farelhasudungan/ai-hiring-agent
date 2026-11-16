<?php

namespace App\Services\Intake;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfTextExtractor
{
    /**
     * @throws RuntimeException When the PDF is unreadable or has no extractable text.
     */
    public function extract(string $pdfContent): string
    {
        try {
            $text = (new Parser)->parseContent($pdfContent)->getText();
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to read PDF: '.$exception->getMessage(), previous: $exception);
        }

        $text = trim(preg_replace('/[ \t]+/', ' ', $text) ?? '');

        if ($text === '') {
            throw new RuntimeException('PDF has no extractable text (possibly a scanned image).');
        }

        return $text;
    }
}
