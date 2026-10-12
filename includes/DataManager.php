<?php
/**
 * DataManager: Pengelola Penyimpanan & Input Dataset Dinamis (Murni Input Pengguna)
 * Mendukung: Upload file CSV sendiri, Input Manual Dokumen Form, & Hapus Dokumen.
 * Tanpa dataset dummy bawaan.
 */

class DataManager
{
    private static string $storagePath = __DIR__ . '/../data/user_documents.json';

    /**
     * Memastikan folder data tersedia
     */
    private static function initStorage(): void
    {
        $dir = dirname(self::$storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Mendapatkan seluruh dokumen aktif yang telah diinput/diunggah
     */
    public static function getDocuments(): array
    {
        self::initStorage();

        if (file_exists(self::$storagePath)) {
            $raw = file_get_contents(self::$storagePath);
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * Menyimpan array dokumen ke file JSON
     */
    public static function saveDocuments(array $docs): bool
    {
        self::initStorage();
        // Hapus cache indeks lama saat data berubah
        if (class_exists('TfidfEngine')) {
            TfidfEngine::invalidateCache();
        }
        return (bool) file_put_contents(
            self::$storagePath, 
            json_encode(array_values($docs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Menambah atau memperbarui satu dokumen baru secara manual dari form input
     */
    public static function addDocument(string|int $id, string $title, string $content, string $category = ''): bool
    {
        $docs = self::getDocuments();
        $idStr = trim((string)$id);
        $cleanTitle = trim($title);
        $cleanContent = trim($content);
        $cleanCat = trim($category);

        // Jika isi konten kosong, gunakan judul sebagai konten agar dokumen tetap memiliki isi
        if ($cleanContent === '') {
            $cleanContent = $cleanTitle;
        }

        if ($cleanCat === '') {
            $cleanCat = 'Umum';
        }

        if ($idStr === '') {
            $idStr = (string)(count($docs) + 1);
        }

        $updated = false;
        foreach ($docs as $idx => $d) {
            if ((string)$d['id'] === $idStr) {
                $docs[$idx] = [
                    'id' => $idStr,
                    'title' => $cleanTitle,
                    'content' => $cleanContent,
                    'category' => $cleanCat
                ];
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $docs[] = [
                'id' => $idStr,
                'title' => $cleanTitle,
                'content' => $cleanContent,
                'category' => $cleanCat
            ];
        }

        return self::saveDocuments($docs);
    }

    /**
     * Menambahkan banyak dokumen sekaligus dari input teks massal pengguna
     * Mendukung format per baris:
     * - Judul | Isi Teks | Kategori
     * - Judul ; Isi Teks ; Kategori
     * - Atau satu baris = satu judul/konten dokumen
     */
    public static function addBulkFromText(string $rawText): int
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
        if (empty($lines)) {
            return 0;
        }

        $docs = self::getDocuments();
        $currCount = count($docs);
        $added = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Coba deteksi pemisah pipa | atau titik koma ; atau tab
            if (str_contains($line, '|')) {
                $parts = array_map('trim', explode('|', $line));
            } elseif (str_contains($line, ';')) {
                $parts = array_map('trim', explode(';', $line));
            } elseif (str_contains($line, "\t")) {
                $parts = array_map('trim', explode("\t", $line));
            } else {
                // Satu baris dianggap sebagai judul
                $parts = [$line];
            }

            $title = $parts[0] ?? '';
            if ($title === '') {
                continue;
            }

            $content = $parts[1] ?? $title;
            if ($content === '') {
                $content = $title;
            }
            $category = $parts[2] ?? 'Umum';
            if ($category === '') {
                $category = 'Umum';
            }

            $currCount++;
            $docs[] = [
                'id' => (string)$currCount,
                'title' => $title,
                'content' => $content,
                'category' => $category
            ];
            $added++;
        }

        if ($added > 0) {
            self::saveDocuments($docs);
        }

        return $added;
    }

    /**
     * Menghapus dokumen berdasarkan ID
     */
    public static function deleteDocument(string|int $id): bool
    {
        $docs = self::getDocuments();
        $idStr = trim((string)$id);
        $filtered = array_values(array_filter($docs, fn($d) => (string)$d['id'] !== $idStr));
        return self::saveDocuments($filtered);
    }

    /**
     * Mengosongkan seluruh dataset pengguna
     */
    public static function clearAll(): bool
    {
        return self::saveDocuments([]);
    }

    /**
     * Deteksi otomatis pemisah (delimiter) CSV: koma, titik koma, tab, atau pipa
     */
    private static function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ',';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if (!$firstLine) {
            return ',';
        }

        $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $d => &$cnt) {
            $cnt = substr_count($firstLine, $d);
        }

        arsort($delimiters);
        return key($delimiters) ?: ',';
    }

    /**
     * Mengimpor dataset dari file CSV yang diunggah oleh pengguna
     * Default batas maks 1.000 baris sesuai spesifikasi tugas UTS STKI
     */
    public static function importUploadedCsv(string $tmpPath, int $maxRows = 1000, bool $append = false): int
    {
        $imported = self::parseCsvFile($tmpPath, $maxRows);
        if (!empty($imported)) {
            if ($append) {
                $existing = self::getDocuments();
                $merged = array_merge($existing, $imported);
                self::saveDocuments($merged);
            } else {
                self::saveDocuments($imported);
            }
            return count($imported);
        }
        return 0;
    }

    /**
     * Parser file CSV fleksibel yang menangani:
     * - PHP 8.1 escape parameter
     * - Karakter BOM UTF-8
     * - Auto-detect delimiter (, ; \t)
     * - Fleksibilitas nama kolom (id/no, title/judul/headline, content/isi/teks/article, category/kategori/rubrik)
     * - Fallback content otomatis ke title jika CSV hanya memuat judul berita
     */
    public static function parseCsvFile(string $filePath, int $maxRows = 1000): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $delimiter = self::detectDelimiter($filePath);
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Baca header dengan seluruh 5 parameter untuk mencegah Deprecated error PHP 8.1
        $header = fgetcsv($handle, 0, $delimiter, '"', "\\");
        if (!$header || empty($header)) {
            fclose($handle);
            return [];
        }

        // Bersihkan BOM UTF-8 pada kolom pertama
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // Normalisasi header (lowercase + trim)
        $cleanHeader = array_map(fn($col) => strtolower(trim((string)$col)), $header);

        // Cari pemetaan indeks kolom
        $idIdx = self::findColumnIndex($cleanHeader, ['id', 'no', 'nomor', 'doc_id', 'id_dokumen', 'kode']);
        $titleIdx = self::findColumnIndex($cleanHeader, ['title', 'judul', 'headline', 'subject', 'nama', 'topik', 'name']);
        $contentIdx = self::findColumnIndex($cleanHeader, ['content', 'isi', 'teks', 'isi_dokumen', 'body', 'deskripsi', 'article', 'artikel', 'text', 'uraian']);
        $catIdx = self::findColumnIndex($cleanHeader, ['category', 'kategori', 'rubrik', 'topik', 'label', 'tag', 'kelas']);
        $urlIdx = self::findColumnIndex($cleanHeader, ['url', 'link', 'tautan']);

        // Jika tidak ditemukan kolom judul eksplisit, gunakan kolom teks pertama
        if ($titleIdx === false) {
            foreach ($cleanHeader as $idx => $name) {
                if (!in_array($name, ['id', 'no', 'nomor', 'date', 'tanggal'])) {
                    $titleIdx = $idx;
                    break;
                }
            }
        }

        $docs = [];
        $rowNum = 1;

        // Baca baris data dengan parameter escape eksplisit "\\", cegah Deprecated warning PHP 8.1
        while (($row = fgetcsv($handle, 0, $delimiter, '"', "\\")) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $id = ($idIdx !== false && isset($row[$idIdx]) && trim($row[$idIdx]) !== '') 
                ? trim($row[$idIdx]) 
                : $rowNum;

            $title = ($titleIdx !== false && isset($row[$titleIdx])) 
                ? trim($row[$titleIdx]) 
                : "Dokumen {$id}";

            $content = ($contentIdx !== false && isset($row[$contentIdx]) && trim($row[$contentIdx]) !== '') 
                ? trim($row[$contentIdx]) 
                : '';

            $category = ($catIdx !== false && isset($row[$catIdx]) && trim($row[$catIdx]) !== '') 
                ? trim($row[$catIdx]) 
                : 'Umum';

            // KUNCI: Jika kolom content kosong (misal pada dataset judul berita),
            // gunakan title sebagai content agar teks dapat dibobotkan dan dicari!
            if ($content === '') {
                $content = $title;
            }

            $docs[] = [
                'id' => $id,
                'title' => $title,
                'content' => $content,
                'category' => $category
            ];

            $rowNum++;

            // Batasi baris impor sesuai parameter agar performa tetap cepat
            if (count($docs) >= $maxRows) {
                break;
            }
        }

        fclose($handle);
        return $docs;
    }

    private static function findColumnIndex(array $headers, array $candidates): int|false
    {
        foreach ($candidates as $cand) {
            $idx = array_search($cand, $headers, true);
            if ($idx !== false) {
                return $idx;
            }
        }
        return false;
    }
}
