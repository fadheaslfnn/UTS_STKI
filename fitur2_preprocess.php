<?php
/**
 * Fitur 2: Pipeline Pra-proses Teks Bahasa Indonesia
 * Menampilkan demonstrasi langkah demi langkah transformasi teks:
 * Case Folding -> Cleaning -> Tokenizing -> Stopword Removal -> Stemming
 */
require_once __DIR__ . '/includes/DataManager.php';
require_once __DIR__ . '/includes/Preprocessor.php';

$sampleInput = "Guru membagikan modul pembelajaran digital secara gratis di portal sekolah, agar seluruh muridnya memahami jaringan komputer!";
$inputText = null;
$selectedDoc = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['text'])) {
    $inputText = (string)$_POST['text'];
} elseif (isset($_GET['id'])) {
    $docId = (string)$_GET['id'];
    $docs = DataManager::getDocuments();
    foreach ($docs as $d) {
        if ((string)$d['id'] === $docId) {
            $selectedDoc = $d;
            $inputText = !empty($d['content']) ? $d['content'] : ($d['title'] ?? '');
            break;
        }
    }
} elseif (isset($_GET['text'])) {
    $inputText = (string)$_GET['text'];
}

if ($inputText === null || trim($inputText) === '') {
    $inputText = $sampleInput;
}

$steps = Preprocessor::preprocessSteps($inputText);

$pageTitle = "Fitur 2: Pipeline Pra-proses Teks";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero" style="padding-top: 1rem; padding-bottom: 1.5rem;">
  <div class="hero-pill">
    <span>Fitur 2: Natural Language Processing (NLP) Pipeline</span>
  </div>
  <h1 class="hero-title" style="font-size: 2.25rem;">Pipeline Pra-proses Teks</h1>
  <p class="hero-desc">
    Mempersiapkan teks mentah dokumen maupun kueri agar berada pada ruang term yang seragam dan konsisten.
  </p>
</div>

<!-- Input Uji Teks Interaktif -->
<div class="card-section" style="max-width: 900px; margin: 0 auto 2.5rem;">
  <?php if ($selectedDoc): ?>
    <div class="selected-doc-banner">
      <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
        <span class="token-pill" style="font-size: 0.78rem;">Dokumen ID: <?= htmlspecialchars($selectedDoc['id']) ?></span>
        <span style="font-size: 0.85rem; color: var(--text-secondary); max-width: 480px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          <?= htmlspecialchars(mb_strlen($inputText) > 80 ? mb_substr($inputText, 0, 80) . '...' : $inputText) ?>
        </span>
      </div>
      <a href="index.php" class="btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem; text-decoration: none;">
        &larr; Kembali ke Daftar Dokumen
      </a>
    </div>
  <?php endif; ?>

  <div class="section-header">
    <div class="section-title" style="font-size: 1.15rem;">
      <span>Uji Transformasi Teks Interaktif</span>
    </div>
  </div>

  <form method="POST" action="fitur2_preprocess.php">
    <div class="form-group" style="margin-bottom: 1rem;">
      <label class="form-label">Masukkan kalimat atau teks uji coba:</label>
      <textarea name="text" class="form-textarea" placeholder="Ketik teks di sini..." required
        style="min-height: 80px;"><?= htmlspecialchars($inputText) ?></textarea>
    </div>
    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
      <button type="submit" class="btn-primary">
        <span>Eksekusi Pipeline Preprocessing</span>
      </button>
      <a href="fitur4_hasil.php?q=<?= urlencode($inputText) ?>" class="btn-secondary">
        <span>Cari Dokumen Terkait Teks Ini</span>
      </a>
    </div>
  </form>
</div>

<!-- Rincian Tahapan Pipeline -->
<div style="max-width: 900px; margin: 0 auto;">

  <!-- Tahap 0: Teks Masukan Asli -->
  <div class="card-section stage-card stage-0" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge" style="width: 26px; height: 26px; font-size: 0.8rem;">0</span>
      <span>Teks Masukan Asli (Raw Input Text)</span>
    </div>
    <div class="pipeline-code-box">
      <?= htmlspecialchars($steps['original']) ?>
    </div>
  </div>

  <!-- Tahap 1: Case Folding -->
  <div class="card-section stage-card stage-1" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge"
        style="width: 26px; height: 26px; font-size: 0.8rem; background: var(--primary); color: white;">1</span>
      <span>Case Folding (Konversi Seluruh Karakter ke Huruf Kecil)</span>
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
      Menghilangkan sensitivitas huruf besar/kecil agar kata seperti 'Jaringan' dan 'jaringan' bernilai identik.
    </p>
    <div class="pipeline-code-box">
      <?= htmlspecialchars($steps['case_folding']) ?>
    </div>
  </div>

  <!-- Tahap 2: Cleaning -->
  <div class="card-section stage-card stage-2" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge"
        style="width: 26px; height: 26px; font-size: 0.8rem; background: var(--cyan); color: white;">2</span>
      <span>Cleaning (Pembersihan URL, Tag HTML, Angka, & Tanda Baca)</span>
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
      Menghilangkan noise sintaksis yang tidak mengandung makna semantik pencarian teks.
    </p>
    <div class="pipeline-code-box">
      <?= htmlspecialchars($steps['cleaning']) ?>
    </div>
  </div>

  <!-- Tahap 3: Tokenizing -->
  <div class="card-section stage-card stage-3" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge"
        style="width: 26px; height: 26px; font-size: 0.8rem; background: #8b5cf6; color: white;">3</span>
      <span>Tokenizing (Pemotongan Teks Menjadi Array Token Kata)</span>
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
      Memecah kalimat menjadi token diskrit berdasarkan pemisah spasi (<?= count($steps['tokens']) ?> token).
    </p>
    <div class="pipeline-code-box" style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
      <?php foreach ($steps['tokens'] as $tok): ?>
        <span class="token-pill"><?= htmlspecialchars($tok) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tahap 4: Stopword Removal -->
  <div class="card-section stage-card stage-4" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge"
        style="width: 26px; height: 26px; font-size: 0.8rem; background: var(--emerald); color: white;">4</span>
      <span>Stopword Removal (Penyaringan Kata Sambung & Partikel Umum)</span>
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
      Menghapus kata-kata fungsional umum (seperti: <em>di, dan, agar, secara</em>) serta kata dengan panjang &lt; 2
      huruf (<?= count($steps['stopword_removed']) ?> token tersisa).
    </p>
    <div class="pipeline-code-box" style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
      <?php foreach ($steps['stopword_removed'] as $tok): ?>
        <span class="token-pill token-pill-stopword"><?= htmlspecialchars($tok) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tahap 5: Stemming -->
  <div class="card-section stage-card stage-5" style="padding: 1.25rem; margin-bottom: 1rem;">
    <div class="pipeline-step-header">
      <span class="rank-badge"
        style="width: 26px; height: 26px; font-size: 0.8rem; background: var(--amber); color: black;">5</span>
      <span>Stemming Bahasa Indonesia (Nazief & Adriani Algorithm)</span>
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
      Meretensi kata berimbuhan ke bentuk dasarnya (contoh: <em>membagikan &rarr; bagi, pembelajaran &rarr; ajar, muridnya &rarr; murid</em>).
    </p>

    <?php
      $uniqueChanged = [];
      $seenWords = [];
      if (!empty($steps['stem_pairs'])) {
          foreach ($steps['stem_pairs'] as $pair) {
              if ($pair['changed'] && !isset($seenWords[$pair['from']])) {
                  $seenWords[$pair['from']] = true;
                  $uniqueChanged[] = $pair;
              }
          }
      }
    ?>

    <!-- Hasil Transformasi Pemotongan Imbuhan: kata_asal -> kata_dasar -->
    <div style="margin-bottom: 1.15rem;">
      <div class="stem-transform-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
        <span>Hasil Transformasi Stemming (Kata Berimbuhan &rarr; Kata Dasar):</span>
      </div>
      <div class="pipeline-code-box" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
        <?php if (!empty($uniqueChanged)): ?>
          <?php foreach ($uniqueChanged as $p): ?>
            <span class="stem-pair-pill">
              <span class="stem-from"><?= htmlspecialchars($p['from']) ?></span>
              <span class="stem-arrow">&rarr;</span>
              <span class="stem-to"><?= htmlspecialchars($p['to']) ?></span>
            </span>
          <?php endforeach; ?>
        <?php else: ?>
          <span style="font-size: 0.82rem; color: var(--text-muted); font-style: italic;">
            Semua token kata pada teks ini sudah merupakan bentuk kata dasar (tidak ada imbuhan yang dipotong).
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Array Token Hasil Akhir Stemming -->
    <div>
      <div style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 600; margin-bottom: 0.45rem;">
        Daftar Token Hasil Akhir (<?= count($steps['stemmed']) ?> token):
      </div>
      <div class="pipeline-code-box" style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
        <?php foreach ($steps['stemmed'] as $tok): ?>
          <span class="token-pill token-pill-stemmed"><?= htmlspecialchars($tok) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>