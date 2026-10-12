<?php
/**
 * Fitur 3 & 4: VsmEngine - Vektorisasi Kueri, Cosine Similarity, & Peringkat Relevansi
 */

require_once __DIR__ . '/Preprocessor.php';
require_once __DIR__ . '/TfidfEngine.php';

class VsmEngine
{
    /**
     * Vektorisasi kueri dengan membobotkan TF kueri x IDF korpus
     */
    public static function vectorizeQuery(array $queryTokens, array $vocab, array $idf): array
    {
        $qVec = [];
        $counts = array_count_values($queryTokens);
        $sumSq = 0.0;

        foreach ($counts as $term => $cnt) {
            if (isset($vocab[$term])) {
                $termIdx = $vocab[$term];
                $weight = (float)$cnt * (float)$idf[$termIdx];
                $qVec[$termIdx] = $weight;
                $sumSq += ($weight * $weight);
            }
        }

        $qNorm = sqrt($sumSq);
        return [
            'vector' => $qVec,
            'norm' => (float)$qNorm
        ];
    }

    /**
     * Mesin Pencari Cosine Similarity (VSM)
     */
    public static function search(string $query, array $index, int $topK = 10): array
    {
        $startTime = microtime(true);
        $cleanQuery = trim($query);

        if (empty($index['docs'])) {
            return [
                'query' => $cleanQuery,
                'tokens' => ($cleanQuery !== '') ? Preprocessor::preprocess($cleanQuery) : [],
                'hits' => [],
                'total_relevant' => 0,
                'elapsed_ms' => 0.0,
                'empty_reason' => 'empty_corpus'
            ];
        }

        if ($cleanQuery === '') {
            return [
                'query' => '',
                'tokens' => [],
                'hits' => [],
                'total_relevant' => 0,
                'elapsed_ms' => 0.0,
                'empty_reason' => 'empty_query'
            ];
        }

        $tokens = Preprocessor::preprocess($cleanQuery);
        if (empty($tokens)) {
            $elapsed = (microtime(true) - $startTime) * 1000;
            return [
                'query' => $cleanQuery,
                'tokens' => [],
                'hits' => [],
                'total_relevant' => 0,
                'elapsed_ms' => round($elapsed, 2),
                'empty_reason' => 'no_match'
            ];
        }

        $qData = self::vectorizeQuery($tokens, $index['vocab'], $index['idf']);
        $qVec = $qData['vector'];
        $qNorm = $qData['norm'];

        if ($qNorm <= 0) {
            $elapsed = (microtime(true) - $startTime) * 1000;
            return [
                'query' => $cleanQuery,
                'tokens' => $tokens,
                'hits' => [],
                'total_relevant' => 0,
                'elapsed_ms' => round($elapsed, 2),
                'empty_reason' => 'no_match'
            ];
        }

        $hits = [];
        $docs = $index['docs'];
        $W = $index['W'];
        $norms = $index['norms'];

        foreach ($docs as $docIdx => $doc) {
            $dNorm = $norms[$docIdx] ?? 0.0;
            if ($dNorm <= 0) {
                continue;
            }

            // Hitung Dot Product: q · d
            $dot = 0.0;
            if (isset($W[$docIdx])) {
                foreach ($qVec as $termIdx => $qWeight) {
                    if (isset($W[$docIdx][$termIdx])) {
                        $dot += ($qWeight * $W[$docIdx][$termIdx]);
                    }
                }
            }

            if ($dot > 0) {
                // Cosine Similarity = (q · d) / (||q|| * ||d||)
                $score = $dot / ($qNorm * $dNorm);
                $hits[] = [
                    'doc_index' => $docIdx,
                    'doc_id' => $doc['id'],
                    'title' => $doc['title'],
                    'content' => $doc['content'],
                    'category' => $doc['category'] ?? 'Umum',
                    'score' => (float)round($score, 4),
                    'dot' => (float)round($dot, 4),
                ];
            }
        }

        // Urutkan skor menurun (Descending)
        usort($hits, fn($a, $b) => $b['score'] <=> $a['score']);

        $totalRelevant = count($hits);
        $topHits = array_slice($hits, 0, $topK);

        // Pasang rank urutan dan snippet berhighlight
        foreach ($topHits as $rankIdx => &$hit) {
            $hit['rank'] = $rankIdx + 1;
            $hit['snippet'] = self::makeSnippet($hit['content'], $tokens);
        }
        unset($hit);

        $elapsed = (microtime(true) - $startTime) * 1000;

        return [
            'query' => $cleanQuery,
            'tokens' => $tokens,
            'hits' => $topHits,
            'total_relevant' => $totalRelevant,
            'elapsed_ms' => round($elapsed, 2),
            'empty_reason' => ($totalRelevant === 0) ? 'no_match' : null,
            'query_vector' => $qVec,
            'query_norm' => round($qNorm, 4),
        ];
    }

    /**
     * Membuat cuplikan teks di sekitar kata kunci dan memberi penanda <mark>
     */
    public static function makeSnippet(string $text, array $queryTokens, int $length = 220): string
    {
        if (trim($text) === '') {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $lowText = mb_strtolower($escaped, 'UTF-8');

        $bestPos = -1;
        foreach ($queryTokens as $tok) {
            if (strlen($tok) < 2) continue;
            $pos = mb_strpos($lowText, mb_strtolower($tok, 'UTF-8'));
            if ($pos !== false && ($bestPos === -1 || $pos < $bestPos)) {
                $bestPos = $pos;
            }
        }

        if ($bestPos === -1) {
            $start = 0;
        } else {
            $start = max(0, $bestPos - (int)($length / 3));
        }

        $snippet = mb_substr($escaped, $start, $length, 'UTF-8');
        $prefix = ($start > 0) ? '...' : '';
        $suffix = (($start + $length) < mb_strlen($escaped, 'UTF-8')) ? '...' : '';

        $result = $prefix . trim($snippet) . $suffix;

        // Berikan highlight untuk setiap token kueri
        if (!empty($queryTokens)) {
            $words = array_filter($queryTokens, fn($t) => strlen($t) > 1);
            if (!empty($words)) {
                $regexParts = array_map(fn($w) => preg_quote($w, '/'), $words);
                $pattern = '/\b(' . implode('|', $regexParts) . ')\w*/i';
                $result = preg_replace($pattern, '<mark class="highlight">$0</mark>', $result);
            }
        }

        return $result;
    }
}
