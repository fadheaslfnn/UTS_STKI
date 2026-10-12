<?php
/**
 * Fitur 2: Pipeline Pra-proses Teks Bahasa Indonesia
 * Menampilkan demonstrasi langkah demi langkah transformasi teks:
 * Case Folding -> Cleaning -> Tokenizing -> Stopword Removal -> Stemming
 */
require_once __DIR__ . '/includes/Preprocessor.php';

$sampleInput = "Guru membagikan modul pembelajaran digital secara gratis di portal sekolah, agar seluruh muridnya memahami jaringan komputer!";
$inputText = $_POST['text'] ?? ($_GET['text'] ?? $sampleInput);
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
  <div class="section-header">
    <div class="section-title" style="font-size: 1.15rem;">
      <span>Uji Transformasi Teks Interaktif</span>
    </div>
  </div>

  <form method="POST">
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
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">
      Meretensi kata ke bentuk dasarnya (contoh: <em>membagikan &rarr; bagi, pembelajaran &rarr; ajar, muridnya &rarr;
        murid</em>).
    </p>
    <div class="pipeline-code-box" style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
      <?php foreach ($steps['stemmed'] as $tok): ?>
        <span class="token-pill token-pill-stemmed"><?= htmlspecialchars($tok) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>