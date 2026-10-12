<?php
/**
 * Halaman Detail Dokumen (Lihat Isi Teks Lengkap & Ekstraksi Token)
 */
require_once __DIR__ . '/includes/DataManager.php';
require_once __DIR__ . '/includes/Preprocessor.php';

$docId = $_GET['id'] ?? '';
$docs = DataManager::getDocuments();

$foundDoc = null;
foreach ($docs as $d) {
    if ((string)$d['id'] === (string)$docId) {
        $foundDoc = $d;
        break;
    }
}

$pageTitle = $foundDoc ? htmlspecialchars($foundDoc['title']) : "Dokumen Tidak Ditemukan";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
  <a href="javascript:history.back()" class="btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
    &larr; Kembali ke Hasil Pencarian
  </a>
</div>

<?php if (!$foundDoc): ?>
  <div class="empty-state-card">
    <div class="empty-state-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
    </div>
    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
      Dokumen Tidak Ditemukan
    </h2>
    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
      Dokumen dengan ID "<?= htmlspecialchars($docId) ?>" tidak ditemukan dalam dataset aktif.
    </p>
    <a href="index.php" class="btn-primary" style="text-decoration: none;">&larr; Kembali ke Beranda</a>
  </div>
<?php else: ?>
  <?php 
    $combined = $foundDoc['title'] . ' ' . $foundDoc['content'];
    $docTokens = Preprocessor::preprocess($combined);
  ?>
  <article class="card-section" style="padding: 2.25rem;">
    <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
      <span class="token-pill" style="font-size: 0.85rem; padding: 0.25rem 0.65rem;">
        ID Dokumen: <?= htmlspecialchars($foundDoc['id']) ?>
      </span>
      <span class="cat-badge" style="font-size: 0.85rem; padding: 0.25rem 0.65rem;">
        <?= htmlspecialchars($foundDoc['category'] ?? 'Umum') ?>
      </span>
    </div>

    <h1 style="font-family: var(--font-heading); font-size: 1.85rem; font-weight: 700; margin-bottom: 1.25rem; line-height: 1.3;">
      <?= htmlspecialchars($foundDoc['title']) ?>
    </h1>

    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 0.5rem;">
      Isi Teks Lengkap:
    </div>
    <div class="doc-content-box">
      <?= nl2br(htmlspecialchars($foundDoc['content'])) ?>
    </div>

    <!-- Token Ekstraksi -->
    <div style="padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
      <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.75rem;">
        Token Hasil Pra-proses Dokumen (<?= count($docTokens) ?> token unik/dasar):
      </div>
      <div class="doc-tokens-box">
        <?php foreach ($docTokens as $tok): ?>
          <span class="token-pill"><?= htmlspecialchars($tok) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center;">
      <a href="fitur4_hasil.php?q=<?= urlencode($foundDoc['title']) ?>" class="btn-primary" style="font-size: 0.9rem;">
        Cari Dokumen Terkait Judul Ini
      </a>
    </div>
  </article>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
