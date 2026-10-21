<?php
/**
 * Fitur 4: Halaman Hasil Pencarian & Perangkingan Cosine Similarity
 */
require_once __DIR__ . '/includes/TfidfEngine.php';
require_once __DIR__ . '/includes/VsmEngine.php';

$query = trim($_GET['q'] ?? '');
$topK = max(1, min((int)($_GET['k'] ?? 10), 100));
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;

$index = TfidfEngine::getIndex();
$searchResult = VsmEngine::search($query, $index, $topK);

$tokens = $searchResult['tokens'];
$allHits = $searchResult['hits'];
$totalRelevant = $searchResult['total_relevant'];
$elapsedMs = $searchResult['elapsed_ms'];
$emptyReason = $searchResult['empty_reason'];

// Pagination
$totalPages = max(1, (int)ceil(count($allHits) / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $pageSize;
$pageHits = array_slice($allHits, $offset, $pageSize);

$pageTitle = ($query !== '') ? "Hasil Pencarian: \"{$query}\"" : "Fitur 4: Hasil Pencarian";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Persistent Search Bar -->
<div class="search-form-wrapper" style="margin-bottom: 2rem;">
  <form action="fitur4_hasil.php" method="GET">
    <div class="search-box">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; opacity: 0.6; flex-shrink: 0;">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
      <input 
        type="text" 
        name="q" 
        class="search-input" 
        value="<?= htmlspecialchars($query) ?>" 
        placeholder="Ketik kata kunci pencarian..." 
        autocomplete="off"
        maxlength="200"
        autofocus
      >
      <div class="search-actions">
        <div class="topk-wrap" title="Pilihan Top-K hasil">
          <span>K:</span>
          <select name="k" class="topk-select">
            <?php foreach ([5, 10, 20, 50] as $kOpt): ?>
              <option value="<?= $kOpt ?>" <?= ($kOpt === $topK) ? 'selected' : '' ?>><?= $kOpt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-primary">
          <span>Cari</span>
        </button>
      </div>
    </div>
  </form>
</div>

<!-- Header Informasi Hasil & Metrik -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
  <div style="font-size: 0.95rem; color: var(--text-secondary);">
    <?php if ($emptyReason === null): ?>
      Menampilkan <strong style="color: var(--text-primary);"><?= count($pageHits) ?></strong> dari <strong style="color: var(--text-primary);"><?= $totalRelevant ?></strong> dokumen relevan (Top-<?= $topK ?>)
      <span class="token-pill" style="border-color: rgba(16,185,129,0.3); color: #34d399; margin-left: 0.5rem;"><?= $elapsedMs ?> ms</span>
    <?php else: ?>
      Pencarian Selesai
      <span class="token-pill" style="margin-left: 0.5rem;"><?= $elapsedMs ?> ms</span>
    <?php endif; ?>
  </div>

  <?php if (!empty($tokens)): ?>
    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
      <span style="font-size: 0.8rem; color: var(--text-muted);">Token Hasil Pra-proses Kueri:</span>
      <?php foreach ($tokens as $tok): ?>
        <span class="token-pill"><?= htmlspecialchars($tok) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Hasil Pencarian ATAU Empty State -->
<?php if ($emptyReason === 'empty_corpus'): ?>
  <div class="empty-state-card">
    <div class="empty-state-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
      </svg>
    </div>
    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
      Belum Ada Dokumen di Dalam Sistem
    </h2>
    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
      Sistem saat ini kosong (tidak ada dokumen). Silakan unggah file CSV atau input dokumen baru terlebih dahulu melalui halaman Dashboard.
    </p>
    <a href="index.php" class="btn-primary" style="text-decoration: none;">&larr; Buka Dashboard untuk Input Dokumen</a>
  </div>

<?php elseif ($emptyReason === 'empty_query'): ?>
  <div class="empty-state-card">
    <div class="empty-state-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20h9"></path>
        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
      </svg>
    </div>
    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
      Kueri Masih Kosong
    </h2>
    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
      Silakan ketikkan kata kunci pencarian pada kotak di atas untuk mulai mencari.
    </p>
    <a href="index.php" class="btn-primary" style="text-decoration: none;">&larr; Kembali ke Dashboard</a>
  </div>

<?php elseif ($emptyReason === 'no_match'): ?>
  <div class="empty-state-card">
    <div class="empty-state-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
        <line x1="8" y1="11" x2="14" y2="11"></line>
      </svg>
    </div>
    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
      Tidak Ada Dokumen yang Cocok
    </h2>
    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
      Tidak ditemukan dokumen dengan kesamaan kosinus &gt; 0 untuk kueri <strong>"<?= htmlspecialchars($query) ?>"</strong>.<br>
      Cobalah gunakan kata kunci lain atau kata dasar yang lebih umum.
    </p>
    <a href="index.php" class="btn-primary" style="text-decoration: none;">&larr; Kembali ke Dashboard</a>
  </div>

<?php else: ?>
  <!-- Daftar Kartu Hasil Pencarian -->
  <div class="results-list">
    <?php foreach ($pageHits as $item): ?>
      <article class="result-card">
        <div class="card-top">
          <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <span class="rank-badge <?= ($item['rank'] === 1) ? 'rank-1' : (($item['rank'] === 2) ? 'rank-2' : (($item['rank'] === 3) ? 'rank-3' : '')) ?>">
              #<?= $item['rank'] ?>
            </span>
            <span class="doc-id-badge" title="ID Dokumen">
              ID: <?= htmlspecialchars($item['doc_id']) ?>
            </span>
          </div>

          <div class="card-badges">
            <span class="score-badge" title="Skor Cosine Similarity">
              <span>cos:</span> <?= number_format($item['score'], 4) ?>
            </span>
          </div>
        </div>

        <!-- Isi Dokumen (dengan Penanda Kata Kunci Kueri) -->
        <div style="margin: 0.85rem 0 1rem;">
          <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.35rem;">
            Isi Dokumen:
          </div>
          <a href="fitur2_preprocess.php?id=<?= urlencode($item['doc_id']) ?>" class="table-doc-content" title="Klik untuk memproses teks di Pipeline Pra-proses">
            <p class="card-snippet" style="margin: 0; font-size: 0.95rem; line-height: 1.65;">
              <?= $item['snippet'] ?>
            </p>
          </a>
        </div>

        <div class="card-footer-meta">
          <span class="card-footer-score">
            Bobot Relevansi Cosine: <strong class="score-percent-val"><?= number_format($item['score'] * 100, 2) ?>%</strong>
          </span>
          <a href="fitur2_preprocess.php?id=<?= urlencode($item['doc_id']) ?>" class="card-doc-link">
            Lihat Pra-proses Teks Lengkap &rarr;
          </a>
        </div>
        <div class="score-progress-wrap">
          <div class="score-progress-bar" style="width: <?= min(100, max(4, round($item['score'] * 100, 1))) ?>%;"></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2.5rem;">
      <?php if ($page > 1): ?>
        <a href="fitur4_hasil.php?q=<?= urlencode($query) ?>&k=<?= $topK ?>&page=<?= $page - 1 ?>" class="btn-secondary btn-sm">&laquo; Sebelumnya</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="fitur4_hasil.php?q=<?= urlencode($query) ?>&k=<?= $topK ?>&page=<?= $p ?>" class="<?= ($p === $page) ? 'btn-primary btn-sm' : 'btn-secondary btn-sm' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="fitur4_hasil.php?q=<?= urlencode($query) ?>&k=<?= $topK ?>&page=<?= $page + 1 ?>" class="btn-secondary btn-sm">Selanjutnya &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
