<?php
// Initialize session and dependencies
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Enforce login
requireLogin();

// Retrieve file name
$file = $_GET['file'] ?? '';

if (empty($file)) {
    die("No file specified.");
}

// Clean up file name to prevent basic directory traversal
$file = basename($file);
$filePath = dirname(__DIR__) . '/uploads/idlar_attachments/' . $file;

if (!file_exists($filePath)) {
    die("File not found.");
}

// Verify it's a doc/docx file based on extension
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, ['doc', 'docx'])) {
    die("Invalid file type for this viewer.");
}

try {
    // Determine the renderer name and path
    $rendererName = \PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF;
    $rendererLibraryPath = dirname(__DIR__) . '/vendor/dompdf/dompdf';

    // Set the PDF renderer
    if (!\PhpOffice\PhpWord\Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
        die("Could not setup PDF renderer.");
    }

    // Load the document using the correct reader for DOC or DOCX
    $readerType = ($ext === 'doc') ? 'MsDoc' : 'Word2007';
    // Fall back to automatic detection if necessary, but specifying is safer
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath, $readerType);

    // Some doc files might not load properly via MsDoc reader in older versions, 
    // depending on the internal doc format. We'll handle generic errors if they fail.

    // Save as PDF to output stream
    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');

    // Free up memories if dealing with large file sizes
    
    // Set headers to display PDF in browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . str_replace(['.docx', '.doc'], '.pdf', $file) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output directly
    $pdfWriter->save('php://output');
    exit;

} catch (\Exception $e) {
    // For production, you might want to log this error instead of displaying it.
    die("Error processing document: " . $e->getMessage());
}
