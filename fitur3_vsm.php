<?php
/**
 * Fitur 3: Mesin Hitung VSM & Matriks Pembobotan TF-IDF
 * Menampilkan rincian matematis: Vocabulary, TF, DF, IDF, Matriks Bobot W, Norma L2, dan Simulator Kueri
 */
require_once __DIR__ . '/includes/TfidfEngine.php';
require_once __DIR__ . '/includes/VsmEngine.php';

$index = TfidfEngine::getIndex();
$docs = $index['docs'];
$vocab = $index['vocab'];
$df = $index['df'];
$idf = $index['idf'];
$tf = $index['tf'];
$W = $index['W'];
$norms = $index['norms'];

$simQuery = trim($_GET['sim_q'] ?? (!empty($vocab) ? 'jaringan komputer' : ''));
$simOutput = null;
if ($simQuery !== '' && !empty($vocab)) {
    $simOutput = VsmEngine::search($simQuery, $index, 10);
}

// Invert vocab array untuk lookup
$invVocab = array_flip($vocab);

$pageTitle = "Fitur 3: Mesin Hitung VSM & Matriks TF-IDF";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero" style="padding-top: 1rem; padding-bottom: 1.5rem;">
  <div class="hero-pill">
    <span>Fitur 3: Komputasi Matematis VSM & Perangkingan</span>
  </div>
  <h1 class="hero-title" style="font-size: 2.25rem;">Mesin Hitung VSM & Matriks TF-IDF</h1>
  <p class="hero-desc">
    Transparansi penuh perhitungan Term Frequency, Document Frequency, Inverse Document Frequency, Bobot W, hingga Cosine Similarity.
  </p>
</div>

<!-- Kartu Landasan Teori Rumus Baku -->
<div class="card-section">
  <div class="section-header">
    <div class="section-title">
      <span>Formula Matematika Vector Space Model (VSM)</span>
    </div>
  </div>

  <div class="vsm-formula-grid">
    <div class="vsm-formula-card vsm-formula-tf">
      <div class="vsm-formula-header">
        <span class="vsm-formula-badge">01</span>
        <span class="vsm-formula-title">Term Frequency (TF)</span>
      </div>
      <div class="vsm-formula-math">TF(t, d) = f<sub>t,d</sub></div>
      <p class="vsm-formula-desc">Frekuensi kemunculan term <em>t</em> pada dokumen <em>d</em>.</p>
    </div>

    <div class="vsm-formula-card vsm-formula-idf">
      <div class="vsm-formula-header">
        <span class="vsm-formula-badge">02</span>
        <span class="vsm-formula-title">Inverse Document Frequency (IDF)</span>
      </div>
      <div class="vsm-formula-math">IDF(t) = log<sub>10</sub>(N / DF(t))</div>
      <p class="vsm-formula-desc">N = total dokumen, DF(t) = jumlah dokumen yang memuat term <em>t</em>.</p>
    </div>

    <div class="vsm-formula-card vsm-formula-w">
      <div class="vsm-formula-header">
        <span class="vsm-formula-badge">03</span>
        <span class="vsm-formula-title">Matriks Bobot Dokumen (W)</span>
      </div>
      <div class="vsm-formula-math">W(t, d) = TF(t, d) &times; IDF(t)</div>
      <p class="vsm-formula-desc">Pembobotan vektor dimensi dokumen terhadap vocabulary.</p>
    </div>

    <div class="vsm-formula-card vsm-formula-cos">
      <div class="vsm-formula-header">
        <span class="vsm-formula-badge">04</span>
        <span class="vsm-formula-title">Kesamaan Kosinus (Cosine)</span>
      </div>
      <div class="vsm-formula-math">cos(q, d) = (q &middot; d) / (||q|| &times; ||d||)</div>
      <p class="vsm-formula-desc">Rentang skor 0 hingga 1. Semakin mendekati 1, semakin relevan.</p>
    </div>
  </div>
</div>

<!-- Simulator Interaktif Langkah Hitung Kueri -->
<div class="card-section">
  <div class="section-header">
    <div class="section-title">
      <span>Simulator Hitung Manual Cosine Similarity</span>
    </div>
  </div>

  <form method="GET" style="margin-bottom: 1.5rem;">
    <div class="search-box">
      <input 
        type="text" 
        name="sim_q" 
        class="search-input" 
        value="<?= htmlspecialchars($simQuery) ?>" 
        placeholder="Ketik kueri uji coba, contoh: 'jaringan komputer'..."
        required
      >
      <button type="submit" class="btn-primary">
        <span>Hitung Vektor & Cosine</span>
      </button>
    </div>
  </form>

  <?php if ($simOutput): ?>
    <div class="sim-result-box">
      <h4 class="sim-result-title">
        Hasil Dekomposisi Kueri: "<?= htmlspecialchars($simQuery) ?>"
      </h4>
      <div class="sim-meta-wrap">
        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem;">Token Kueri:</span>
          <?php foreach ($simOutput['tokens'] as $tok): ?>
            <span class="token-pill"><?= htmlspecialchars($tok) ?></span>
          <?php endforeach; ?>
        </div>
        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem;">Norma Kueri (||q||):</span>
          <strong class="metric-norm-cyan"><?= $simOutput['query_norm'] ?></strong>
        </div>
        <div>
          <span style="color: var(--text-muted); font-size: 0.85rem;">Waktu Komputasi:</span>
          <span class="token-pill metric-time"><?= $simOutput['elapsed_ms'] ?> ms</span>
        </div>
      </div>

      <!-- Tabel Perhitungan Cosine Dokumen Teratas -->
      <h5 class="sim-subheading">
        Perhitungan Cosine Similarity pada Dokumen Terkait:
      </h5>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Peringkat</th>
              <th>ID Dok</th>
              <th>Judul Dokumen</th>
              <th>Dot Product (q &middot; d)</th>
              <th>Norma Dok (||d||)</th>
              <th>Formula Cosine: (q&middot;d) / (||q|| &times; ||d||)</th>
              <th>Skor Cosine</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($simOutput['hits'])): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                  Tidak ada dokumen dengan kecocokan term (Skor Cosine = 0)
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($simOutput['hits'] as $hit): ?>
                <?php 
                  $dNorm = $norms[$hit['doc_index']] ?? 0.0;
                  $denom = $simOutput['query_norm'] * $dNorm;
                ?>
                <tr>
                  <td><strong>#<?= $hit['rank'] ?></strong></td>
                  <td><span class="token-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($hit['doc_id']) ?></span></td>
                  <td><strong><?= htmlspecialchars($hit['title']) ?></strong></td>
                  <td class="metric-dot-val"><?= $hit['dot'] ?></td>
                  <td class="metric-norm-val"><?= number_format($dNorm, 4) ?></td>
                  <td style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
                    <?= $hit['dot'] ?> / (<?= $simOutput['query_norm'] ?> &times; <?= number_format($dNorm, 4) ?>)
                  </td>
                  <td>
                    <span class="score-badge" style="font-size: 0.9rem;">
                      cos: <?= number_format($hit['score'], 4) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Tabel Vocabulary & Nilai DF/IDF Korpus -->
<div class="card-section">
  <div class="section-header">
    <div class="section-title" style="font-size: 1.15rem;">
      <span>Tabel Vocabulary & Nilai IDF Korpus (<?= count($vocab) ?> Term Unik)</span>
    </div>
  </div>

  <?php if (empty($vocab)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Belum ada dokumen yang diindeks.</p>
  <?php else: ?>
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 80px;">Indeks #</th>
            <th>Term / Kata Unik</th>
            <th style="width: 140px;">Document Frequency (DF)</th>
            <th style="width: 180px;">Formula IDF: log10(N / DF)</th>
            <th style="width: 140px;">Nilai IDF</th>
          </tr>
        </thead>
        <tbody>
          <?php $nTotal = count($docs); ?>
          <?php foreach (array_slice($vocab, 0, 50, true) as $term => $tIdx): ?>
            <?php 
              $dfVal = $df[$tIdx] ?? 1; 
              $idfVal = $idf[$tIdx] ?? 0.0;
            ?>
            <tr>
              <td style="color: var(--text-muted); font-family: monospace;"><?= $tIdx ?></td>
              <td><span class="token-pill" style="font-size: 0.85rem;"><?= htmlspecialchars($term) ?></span></td>
              <td style="font-family: monospace;"><?= $dfVal ?> dokumen</td>
              <td style="font-family: monospace; color: var(--text-muted); font-size: 0.8rem;">
                log10(<?= $nTotal ?> / <?= $dfVal ?>)
              </td>
              <td><strong style="color: #38bdf8; font-family: monospace;"><?= number_format($idfVal, 4) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($vocab) > 50): ?>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; text-align: right;">
        * Menampilkan 50 term pertama dari total <?= count($vocab) ?> vocabulary.
      </p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Tabel Matriks Bobot Dokumen W & Norma L2 -->
<div class="card-section">
  <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
    <div class="section-title" style="font-size: 1.15rem;">
      <span>Matriks Bobot Dokumen W (TF &times; IDF) & Norma L2</span>
    </div>

    <!-- Switcher Mode Tampilan: Simpel vs Matriks Kolom -->
    <div class="vsm-view-switcher">
      <button type="button" class="vsm-tab-btn active" id="tabBtnSimple" onclick="switchMatrixView('simple')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
        <span>Ringkasan Vektor (Simpel)</span>
      </button>
      <button type="button" class="vsm-tab-btn" id="tabBtnRaw" onclick="switchMatrixView('raw')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
        <span>Matriks Kolom (Raw)</span>
      </button>
    </div>
  </div>

  <?php if (empty($docs)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 2rem;">Belum ada dokumen yang diindeks.</p>
  <?php else: ?>

    <!-- Tampilan 1 (Default): Ringkasan Vektor Berbobot Dokumen (Jauh Lebih Bersih & Jelas) -->
    <div id="viewSimpleMatrix">
      <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th style="width: 250px;">Judul Dokumen</th>
              <th>Kata Kunci & Bobot W (TF &times; IDF)</th>
              <th style="width: 120px; text-align: center;">Total Term</th>
              <th class="matrix-norm-header" style="width: 160px;">Norma Dokumen (||d||)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($docs, 0, 40) as $i => $d): ?>
              <?php 
                $docWeights = $W[$i] ?? [];
                arsort($docWeights); // Urutkan bobot tertinggi di awal
                $topWeights = array_slice($docWeights, 0, 7, true);
                $remainingCount = count($docWeights) - count($topWeights);
              ?>
              <tr>
                <td><span class="token-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($d['id']) ?></span></td>
                <td>
                  <strong><?= htmlspecialchars(mb_substr($d['title'], 0, 35)) ?><?= mb_strlen($d['title']) > 35 ? '...' : '' ?></strong>
                </td>
                <td>
                  <div class="weight-chips-wrap">
                    <?php if (empty($topWeights)): ?>
                      <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Tidak ada term bernilai</span>
                    <?php else: ?>
                      <?php foreach ($topWeights as $sIdx => $wVal): ?>
                        <?php $termName = $invVocab[$sIdx] ?? "term#{$sIdx}"; ?>
                        <span class="weight-chip" title="Kata: <?= htmlspecialchars($termName) ?> | Bobot TF-IDF: <?= number_format($wVal, 4) ?>">
                          <span class="weight-chip-term"><?= htmlspecialchars($termName) ?></span>
                          <span class="weight-chip-val"><?= number_format($wVal, 3) ?></span>
                        </span>
                      <?php endforeach; ?>
                      <?php if ($remainingCount > 0): ?>
                        <span class="weight-chip-more">+<?= $remainingCount ?> kata lainnya</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
                <td style="text-align: center; font-family: var(--font-mono); font-size: 0.85rem; color: var(--text-secondary);">
                  <?= count($docWeights) ?> term
                </td>
                <td class="matrix-norm-val-total">
                  <?= number_format($norms[$i] ?? 0.0, 4) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; display: flex; justify-content: space-between; flex-wrap: wrap;">
        <span>* Menampilkan kata-kata yang benar-benar memiliki bobot di tiap dokumen (tanpa kolom kosong bernilai 0).</span>
        <span>Menampilkan 40 dari <?= count($docs) ?> dokumen</span>
      </p>
    </div>

    <!-- Tampilan 2: Format Matriks Kolom Konvensional (Raw Sparse Matrix) -->
    <div id="viewRawMatrix" style="display: none;">
      <?php 
        $sampleTerms = array_slice($vocab, 0, 8, true);
      ?>
      <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 60px;">ID</th>
              <th style="width: 200px;">Judul Dokumen</th>
              <?php foreach ($sampleTerms as $sTerm => $sIdx): ?>
                <th style="text-align: center; font-size: 0.7rem;" title="Term: <?= htmlspecialchars($sTerm) ?>">
                  <?= htmlspecialchars(mb_substr($sTerm, 0, 8)) ?>..
                </th>
              <?php endforeach; ?>
              <th class="matrix-norm-header">Norma Dokumen (||d||)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($docs, 0, 30) as $i => $d): ?>
              <tr>
                <td><span class="token-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($d['id']) ?></span></td>
                <td><strong><?= htmlspecialchars(mb_substr($d['title'], 0, 30)) ?>...</strong></td>
                <?php foreach ($sampleTerms as $sTerm => $sIdx): ?>
                  <?php 
                    $weight = $W[$i][$sIdx] ?? 0.0;
                  ?>
                  <td class="<?= $weight > 0 ? 'matrix-weight-val' : 'matrix-zero-val' ?>">
                    <?= $weight > 0 ? number_format($weight, 3) : '0' ?>
                  </td>
                <?php endforeach; ?>
                <td class="matrix-norm-val-total">
                  <?= number_format($norms[$i] ?? 0.0, 4) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; text-align: right;">
        * Cuplikan matriks konvensional untuk 8 term pertama dan 30 dokumen pertama.
      </p>
    </div>

    <!-- Script Pengganti Tab Interaktif -->
    <script>
      function switchMatrixView(mode) {
        const viewSimple = document.getElementById('viewSimpleMatrix');
        const viewRaw = document.getElementById('viewRawMatrix');
        const btnSimple = document.getElementById('tabBtnSimple');
        const btnRaw = document.getElementById('tabBtnRaw');

        if (mode === 'simple') {
          viewSimple.style.display = 'block';
          viewRaw.style.display = 'none';
          btnSimple.classList.add('active');
          btnRaw.classList.remove('active');
        } else {
          viewSimple.style.display = 'none';
          viewRaw.style.display = 'block';
          btnSimple.classList.remove('active');
          btnRaw.classList.add('active');
        }
      }
    </script>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
