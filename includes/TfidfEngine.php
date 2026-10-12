<?php
/**
 * Fitur 3: Mesin Hitung VSM & Matriks Pembobotan TF-IDF
 * Menghitung: Vocabulary, TF, DF, IDF = log10(N/DF), Matriks Bobot W, dan Norma L2
 */

require_once __DIR__ . '/Preprocessor.php';
require_once __DIR__ . '/DataManager.php';

class TfidfEngine
{
    private static string $cachePath = __DIR__ . '/../cache/index_cache.json';

    public static function invalidateCache(): void
    {
        if (file_exists(self::$cachePath)) {
            @unlink(self::$cachePath);
        }
    }

    /**
     * Membangun atau memuat indeks korpus dari dokumen aktif
     */
    public static function getIndex(bool $forceRebuild = false): array
    {
        if (!$forceRebuild && file_exists(self::$cachePath)) {
            $cached = json_decode(file_get_contents(self::$cachePath), true);
            if (is_array($cached) && isset($cached['docs'], $cached['vocab'], $cached['idf'])) {
                return $cached;
            }
        }

        return self::buildIndex();
    }

    /**
     * Membangun seluruh komponen matematis VSM & TF-IDF
     */
    public static function buildIndex(): array
    {
        $docs = DataManager::getDocuments();
        $nDocs = count($docs);

        if ($nDocs === 0) {
            $emptyIndex = [
                'docs' => [],
                'docs_tokens' => [],
                'vocab' => [],
                'df' => [],
                'idf' => [],
                'tf' => [],
                'W' => [],
                'norms' => [],
                'built_at' => date('Y-m-d H:i:s'),
            ];
            self::saveCache($emptyIndex);
            return $emptyIndex;
        }

        // 1. Preprocessing tiap dokumen
        $docsTokens = [];
        $uniqueTerms = [];

        foreach ($docs as $i => $doc) {
            $combined = ($doc['title'] ?? '') . ' ' . ($doc['content'] ?? '');
            $tokens = Preprocessor::preprocess($combined);
            $docsTokens[$i] = $tokens;

            foreach ($tokens as $t) {
                $uniqueTerms[$t] = true;
            }
        }

        // 2. Bangun Vocabulary unik terurut alfabetis
        $terms = array_keys($uniqueTerms);
        sort($terms);
        $vocab = [];
        foreach ($terms as $idx => $t) {
            $vocab[$t] = $idx;
        }

        // 3. Hitung Matriks TF (Raw Count) dan DF (Document Frequency)
        $tf = [];
        $df = array_fill(0, count($vocab), 0);

        foreach ($docsTokens as $i => $tokens) {
            $tf[$i] = [];
            $counts = array_count_values($tokens);

            foreach ($counts as $term => $cnt) {
                if (isset($vocab[$term])) {
                    $termIdx = $vocab[$term];
                    $tf[$i][$termIdx] = (float)$cnt;
                    $df[$termIdx]++;
                }
            }
        }

        // 4. Hitung Nilai IDF = log10(N / DF)
        $idf = [];
        foreach ($vocab as $term => $termIdx) {
            $docFreq = max(1, $df[$termIdx]);
            // Rumus teori baku VSM: log10(N / DF)
            $idfVal = log10($nDocs / $docFreq);
            $idf[$termIdx] = (float)round($idfVal, 6);
        }

        // 5. Bangun Matriks Bobot W = TF x IDF dan Norma L2
        $W = [];
        $norms = [];

        foreach ($docs as $i => $doc) {
            $W[$i] = [];
            $sumSq = 0.0;

            if (isset($tf[$i])) {
                foreach ($tf[$i] as $termIdx => $tfVal) {
                    $weight = $tfVal * $idf[$termIdx];
                    $W[$i][$termIdx] = (float)round($weight, 6);
                    $sumSq += ($weight * $weight);
                }
            }

            $norms[$i] = (float)round(sqrt($sumSq), 6);
        }

        $index = [
            'docs' => $docs,
            'docs_tokens' => $docsTokens,
            'vocab' => $vocab,
            'df' => $df,
            'idf' => $idf,
            'tf' => $tf,
            'W' => $W,
            'norms' => $norms,
            'built_at' => date('Y-m-d H:i:s'),
        ];

        self::saveCache($index);
        return $index;
    }

    private static function saveCache(array $index): void
    {
        $dir = dirname(self::$cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::$cachePath, json_encode($index));
    }
}
