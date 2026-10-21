<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/DataManager.php';
require_once __DIR__ . '/../includes/TfidfEngine.php';
require_once __DIR__ . '/../includes/VsmEngine.php';

$backupDocs = DataManager::getDocuments();

echo "=== 1. TEST CLEAR ALL ===\n";
DataManager::clearAll();
assert(count(DataManager::getDocuments()) === 0, "Docs must be 0 after clear");
echo "PASS: Korpus kosong bersih.\n";

echo "=== 2. TEST INPUT MANUAL (SATUAN) ===\n";
DataManager::addDocument('DOC-1', 'Jaringan Komputer dan Topologi', 'Konsep topologi mesh dan protokol TCP/IP pada jaringan komputer.', 'Jaringan');
$docs = DataManager::getDocuments();
assert(count($docs) === 1, "Must have 1 doc");
assert($docs[0]['title'] === 'Jaringan Komputer dan Topologi', "Title mismatch");
echo "PASS: Dokumen satuan tersimpan: " . $docs[0]['title'] . "\n";

echo "=== 3. TEST INPUT MASSAL (BULK PASTE TEXT) ===\n";
$bulkText = "Sistem Temu Kembali Informasi | Information Retrieval menggunakan VSM dan TF-IDF | STKI\n"
          . "Basis Data Relasional | Perancangan basis data dan normalisasi tabel | Database\n"
          . "Kecerdasan Buatan Modern | Perkembangan AI dan deep learning | AI";
$added = DataManager::addBulkFromText($bulkText);
assert($added === 3, "Must add 3 bulk docs");
$docs = DataManager::getDocuments();
assert(count($docs) === 4, "Total must be 4 docs");
echo "PASS: Berhasil menambahkan {$added} dokumen massal. Total korpus: " . count($docs) . "\n";

echo "=== 4. TEST BUILD INDEX & VSM ===\n";
$index = TfidfEngine::buildIndex();
assert(count($index['docs']) === 4, "Indexed docs must be 4");
assert(count($index['vocab']) > 0, "Vocab must not be empty");
echo "PASS: Indeks selesai. Vocab: " . count($index['vocab']) . " term unik.\n";

echo "=== 5. TEST SEARCH COSINE SIMILARITY ===\n";
$res = VsmEngine::search('jaringan komputer', $index, 5);
assert(count($res['hits']) >= 1, "Should find matching document");
echo "PASS: Pencarian 'jaringan komputer' menemukan: " . $res['hits'][0]['title'] . " (Score: " . $res['hits'][0]['score'] . ")\n";

echo "=== 6. TEST UPLOAD CSV ===\n";
$tmpCsv = __DIR__ . '/test_upload.csv';
file_put_contents($tmpCsv, "title,content,category\nKriptografi Kunci Publik,Algoritma RSA dan enkripsi data digital,Keamanan\nCloud Computing AWS,Layanan komputasi awan dan virtualisasi server,Cloud\n");
$csvCount = DataManager::importUploadedCsv($tmpCsv, 100, true); // append mode
assert($csvCount === 2, "Must import 2 CSV docs");
$docs = DataManager::getDocuments();
assert(count($docs) === 6, "Total docs must be 6 after CSV append");
@unlink($tmpCsv);
echo "PASS: Impor CSV berhasil. Total dokumen sekarang: " . count($docs) . "\n";

$index2 = TfidfEngine::buildIndex();
$res2 = VsmEngine::search('kriptografi rsa', $index2, 5);
assert(count($res2['hits']) >= 1, "Should find RSA doc");
echo "PASS: Pencarian dari CSV 'kriptografi rsa': " . $res2['hits'][0]['title'] . " (Score: " . $res2['hits'][0]['score'] . ")\n";

echo "=== CLEANUP: KEMBALIKAN DATASET ASLI SETELAH TEST ===\n";
DataManager::saveDocuments($backupDocs);
TfidfEngine::buildIndex();
echo "PASS: Dataset asli (" . count($backupDocs) . " dokumen) berhasil dipulihkan secara aman.\n";

echo "=== ALL INPUT AND SEARCH TESTS PASSED SUCCESSFULLY! ===\n";
