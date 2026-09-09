<?php
declare(strict_types=1);

/** @return array{success: bool, message: string} */
function queuePdf(string $pdfFile): array
{
    if (!is_file($pdfFile)) {
        return ['success' => false, 'message' => 'De PDF bestaat niet.'];
    }

    if (!is_dir(PRINT_QUEUE_DIR)
        && !mkdir(PRINT_QUEUE_DIR, 0775, true)
        && !is_dir(PRINT_QUEUE_DIR)) {
        return ['success' => false, 'message' => 'De printwachtrij kon niet worden aangemaakt.'];
    }

    $queueFile = PRINT_QUEUE_DIR . '/' . basename($pdfFile);
    $temporaryFile = $queueFile . '.tmp-' . getmypid();

    if (!copy($pdfFile, $temporaryFile) || !rename($temporaryFile, $queueFile)) {
        @unlink($temporaryFile);
        return ['success' => false, 'message' => 'De PDF kon niet in de printwachtrij worden geplaatst.'];
    }

    return ['success' => true, 'message' => 'De bon staat klaar in de printwachtrij.'];
}
