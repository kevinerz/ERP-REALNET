<?php
// process_ocr.php

// Set appropriate headers for JSON response
header('Content-Type: application/json');

// Include Composer's autoloader for Google Cloud Vision client library
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type; // For specifying feature type (TEXT_DETECTION)

// Define the path to your Google Cloud Service Account Key JSON file.
// !!! IMPORTANT: This path MUST be outside your web-accessible directory for security reasons.
// Example: if your web root is /var/www/html/, put the key in /var/www/private/
define('GOOGLE_APPLICATION_CREDENTIALS_PATH', '/path/to/your/my-project-vision-key.json'); // <--- UPDATE THIS PATH!!!

// --- Input Validation ---
if (!isset($_FILES['ktp_photo']) || $_FILES['ktp_photo']['error'] !== UPLOAD_ERR_OK) {
    error_log("Upload Error: " . ($_FILES['ktp_photo']['error'] ?? 'No file uploaded or unknown error'));
    echo json_encode(['success' => false, 'message' => 'Tidak ada file KTP yang diunggah atau terjadi kesalahan upload.']);
    exit;
}

$tempFilePath = $_FILES['ktp_photo']['tmp_name'];
$fileName = $_FILES['ktp_photo']['name'];
$fileSize = $_FILES['ktp_photo']['size'];
$fileType = $_FILES['ktp_photo']['type'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($fileType, $allowedTypes)) {
    // Log invalid file type attempt
    error_log("Invalid file type uploaded: " . $fileType);
    echo json_encode(['success' => false, 'message' => 'Tipe file tidak didukung. Harap unggah gambar (JPG, PNG, GIF, WebP).']);
    exit;
}

// Validate file size (e.g., max 5MB for KTP photos)
$maxFileSize = 5 * 1024 * 1024; // 5 MB
if ($fileSize > $maxFileSize) {
    error_log("File size too large: " . $fileSize . " bytes.");
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maks. 5MB).']);
    exit;
}

// --- OCR Processing with Google Cloud Vision API ---
$nama = '';
$nik = '';
$alamat = '';

try {
    // Instantiate a client
    $imageAnnotatorClient = new ImageAnnotatorClient([
        'credentials' => GOOGLE_APPLICATION_CREDENTIALS_PATH
    ]);

    // Read the image content
    $imageContent = file_get_contents($tempFilePath);

    // Perform text detection (DOCUMENT_TEXT_DETECTION is usually better for structured documents like KTPs)
    $response = $imageAnnotatorClient->annotateImage(
        (new Google\Cloud\Vision\V1\Image())->setContent($imageContent),
        [
            (new Google\Cloud\Vision\V1\Feature())->setType(Type::DOCUMENT_TEXT_DETECTION)
        ]
    );

    $fullText = '';
    // The full text is usually in the first text annotation
    if ($text = $response->getFullTextAnnotation()) {
        $fullText = $text->getText();
    } else {
        // Fallback to basic text detection if document text detection fails
        if (isset($response->getTextAnnotations()[0])) {
            $fullText = $response->getTextAnnotations()[0]->getDescription();
        }
    }

    $imageAnnotatorClient->close(); // Close the client to release resources

    // --- KTP Data Parsing Logic (CRITICAL & COMPLEX) ---
    // This part requires robust string parsing or regex to extract specific fields from the KTP.
    // The patterns below are examples and may need extensive refinement based on actual KTP images
    // and the exact output format from Google Vision API.

    // 1. Extract NIK (Nomor Induk Kependudukan - exactly 16 digits)
    if (preg_match('/\b(\d{16})\b/', $fullText, $matches)) {
        $nik = $matches[1];
    } else {
        // Fallback for NIK, sometimes it's explicitly labeled
        if (preg_match('/NIK\s*[:\-\s]*(\d{16})/i', $fullText, $matches)) {
            $nik = $matches[1];
        }
    }
    
    // Clean NIK if it contains non-digits (e.g., spaces accidentally captured by OCR)
    $nik = preg_replace('/\D/', '', $nik);
    if (strlen($nik) !== 16) {
        $nik = ''; // If not exactly 16 digits, consider it invalid for KTP NIK
    }


    // 2. Extract Nama (Name)
    // This is very challenging as "Nama" can appear anywhere.
    // We'll try to find "Nama" label and then capture the text after it.
    // You might need to refine this significantly based on how names appear relative to other fields.
    if (preg_match('/Nama\s*[:\-\s]*([A-Z\s\.]+)/i', $fullText, $matches)) {
        $nama = trim($matches[1]);
        // Simple heuristic: A name should not contain numbers, and usually not special characters
        $nama = preg_replace('/[^a-zA-Z\s\.]/', '', $nama);
        // Remove common KTP terms that might get falsely identified as part of the name
        $nama = preg_replace('/\b(Provinsi|Kabupaten|Kota|Jalan|RT|RW|Desa|Kelurahan|Kecamatan|Agama|Status|Pekerjaan|Warganegara)\b/i', '', $nama);
        $nama = trim(strtoupper($nama)); // Convert to uppercase for consistency
    }
    // Further complex logic might be needed: e.g., if "Nama" is on one line and the name on the next.


    // 3. Extract Alamat (Address)
    // This is also complex due to varying address formats.
    // We try to capture text after "Alamat" until we hit another known KTP field.
    if (preg_match('/Alamat\s*[:\-\s]*(.+?)(?=\bRT\/RW\b|\bAgama\b|\bStatus Perkawinan\b|\bPekerjaan\b|\bKewarganegaraan\b|\n\n|$)/is', $fullText, $matches)) {
        $alamat = trim($matches[1]);
        // Clean up common artifacts or excessive whitespace
        $alamat = preg_replace('/\s+/', ' ', $alamat); // Replace multiple spaces with single
        $alamat = preg_replace('/(RT|RW)\s*[:\-\s]*(\d+\/\d+)/i', 'RT/RW $2', $alamat); // Normalize RT/RW
        $alamat = trim(strtoupper($alamat)); // Convert to uppercase for consistency
    }
    // The (?!...) is a negative lookahead to ensure we stop before the next major field.


    // --- Final Response ---
    // Delete the temporary file after processing to free up space and for security
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data KTP berhasil diekstraksi. Harap periksa kembali akurasi data.',
        'data' => [
            'nama' => $nama,
            'nik' => $nik,
            'alamat' => $alamat
        ]
    ]);

} catch (Exception $e) {
    // Log the error for debugging purposes (e.g., API errors, network issues, parsing errors)
    error_log("OCR Processing Error in process_ocr.php: " . $e->getMessage() . " | File: " . $fileName);

    // Clean up temporary file in case of an error
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    // Send a generic error message to the client
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memproses foto KTP. Mohon pastikan foto jelas dan coba lagi, atau isi data secara manual.'
    ]);
}
?>