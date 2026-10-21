<?php
/**
 * Fitur 1: Dashboard Pencarian & Manajemen Dataset Input Sendiri
 */
require_once __DIR__ . '/includes/DataManager.php';
require_once __DIR__ . '/includes/TfidfEngine.php';

$alert = null;
$alertType = 'info';

// Penanganan Aksi Manajemen Dataset (Upload CSV, Input Manual, Hapus, Reset)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'upload_csv') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
      $tmpPath = $_FILES['csv_file']['tmp_name'];
      $maxRows = max(1, (int) ($_POST['max_rows'] ?? 1000));
      $mode = $_POST['import_mode'] ?? 'replace';
      $append = ($mode === 'append');
      $count = DataManager::importUploadedCsv($tmpPath, $maxRows, $append);
      if ($count > 0) {
        TfidfEngine::buildIndex();
        $alert = "Berhasil mengimpor {$count} dokumen dari file CSV yang Anda unggah!";
        $alertType = 'success';
      } else {
        $alert = "Gagal membaca isi file CSV. Pastikan file berformat CSV dan memuat baris data yang valid.";
        $alertType = 'error';
      }
    } else {
      $errCode = $_FILES['csv_file']['error'] ?? 'tidak diketahui';
      $alert = "Gagal mengunggah file (Kode error: {$errCode}). Silakan pilih file CSV yang valid.";
      $alertType = 'error';
    }
  } elseif ($action === 'add_manual') {
    $id = trim($_POST['doc_id'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($content !== '') {
      $docs = DataManager::getDocuments();
      if ($id === '') {
        $maxId = 0;
        foreach ($docs as $d) {
          if (is_numeric($d['id']) && (int) $d['id'] > $maxId) {
            $maxId = (int) $d['id'];
          }
        }
        $id = ($maxId > 0) ? ($maxId + 1) : (count($docs) + 1);
      }
      $title = $content;
      $category = 'Umum';
      DataManager::addDocument($id, $title, $content, $category);
      TfidfEngine::buildIndex();
      $alert = "Dokumen baru dengan ID '{$id}' berhasil disimpan dan diindeks!";
      $alertType = 'success';
    } else {
      $alert = "Isi teks dokumen wajib diisi.";
      $alertType = 'error';
    }
  } elseif ($action === 'add_bulk') {
    $rawText = trim($_POST['bulk_text'] ?? '');
    if ($rawText !== '') {
      $count = DataManager::addBulkFromText($rawText);
      if ($count > 0) {
        TfidfEngine::buildIndex();
        $alert = "Berhasil menambahkan {$count} dokumen dari input teks massal!";
        $alertType = 'success';
      } else {
        $alert = "Tidak ada baris dokumen yang valid ditemukan dalam teks input.";
        $alertType = 'error';
      }
    } else {
      $alert = "Teks dokumen massal tidak boleh kosong.";
      $alertType = 'error';
    }
  } elseif ($action === 'delete_doc') {
    $id = $_POST['id'] ?? '';
    if ($id !== '') {
      DataManager::deleteDocument($id);
      TfidfEngine::buildIndex();
      $alert = "Dokumen dengan ID '{$id}' berhasil dihapus.";
      $alertType = 'success';
    }
  } elseif ($action === 'clear_all') {
    DataManager::clearAll();
    TfidfEngine::buildIndex();
    $alert = "Seluruh dataset telah dikosongkan. Silakan unggah CSV atau input dokumen baru.";
    $alertType = 'info';
  } elseif ($action === 'rebuild_index') {
    TfidfEngine::buildIndex();
    $alert = "Indeks korpus berhasil dibangun ulang!";
    $alertType = 'success';
  }
}

// Ambil data indeks korpus terkini
$index = TfidfEngine::getIndex();
$docs = $index['docs'];
$nDocs = count($docs);
$nVocab = count($index['vocab']);
$builtAt = $index['built_at'];

$pageTitle = "Fitur 1: Dashboard Pencarian & Input Dataset";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<div class="hero">
  <div class="hero-pill">
    <span>STKI UTS &bull; Vector Space Model (VSM) & TF-IDF</span>
  </div>
  <h1 class="hero-title">Mesin Pencari Dokumen Teks</h1>
  <p class="hero-desc">
    Sistem temu kembali informasi dengan pembobotan <strong>TF-IDF</strong> baku dan perangkingan <strong>Cosine
      Similarity</strong>.
  </p>
</div>

<!-- Notifikasi Alert -->
<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alertType) ?>">
    <?= htmlspecialchars($alert) ?>
  </div>
<?php endif; ?>

<!-- Fitur 1: Form Pencarian Utama -->
<div class="search-form-wrapper">
  <form action="fitur4_hasil.php" method="GET">
    <div class="search-box">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; opacity: 0.6; flex-shrink: 0;">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
      <input type="text" name="q" class="search-input"
        placeholder="Ketik kata kunci pencarian, contoh: 'jaringan komputer'..." autocomplete="off" autofocus
        maxlength="200">
      <div class="search-actions">
        <div class="topk-wrap" title="Pilihan Top-K hasil pencarian">
          <span>K:</span>
          <select name="k" class="topk-select">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>
        <button type="submit" class="btn-primary">
          <span>Cari</span>
          <span>&rarr;</span>
        </button>
      </div>
    </div>
  </form>

  <!-- Quick Queries Chips -->
  <?php if ($nDocs > 0): ?>
    <div class="quick-chips">
      <span style="font-size: 0.8rem; color: var(--text-muted);">Contoh Kueri Cepat:</span>
      <a href="fitur4_hasil.php?q=jaringan+komputer&k=10" class="chip">jaringan komputer</a>
      <a href="fitur4_hasil.php?q=sistem+temu+kembali+informasi&k=10" class="chip">sistem temu kembali informasi</a>
      <a href="fitur4_hasil.php?q=basis+data&k=10" class="chip">basis data</a>
      <a href="fitur4_hasil.php?q=kecerdasan+buatan&k=10" class="chip">kecerdasan buatan</a>
      <a href="fitur4_hasil.php?q=rekam+medis&k=10" class="chip">rekam medis</a>
      <a href="fitur4_hasil.php?q=hak+cipta+digital&k=10" class="chip">hak cipta digital</a>
    </div>
  <?php else: ?>
    <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
      Sistem saat ini kosong. Silakan unggah file CSV Anda atau gunakan form di bawah untuk menginputkan dokumen.
    </div>
  <?php endif; ?>
</div>

<!-- Panel Metrik Statistik Korpus -->
<div class="stats-grid">
  <div class="stat-card" style="--stat-accent: #6366f1;">
    <div class="stat-card-header">
      <span class="stat-title">Dokumen Terindeks</span>
      <div class="stat-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
      </div>
    </div>
    <div class="stat-value"><?= number_format($nDocs) ?></div>
    <div class="stat-note">Total dokumen aktif dalam sistem</div>
  </div>

  <div class="stat-card" style="--stat-accent: #06b6d4;">
    <div class="stat-card-header">
      <span class="stat-title">Ukuran Vocabulary</span>
      <div class="stat-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <polyline points="4 7 4 4 20 4 20 7"></polyline>
          <line x1="9" y1="20" x2="15" y2="20"></line>
          <line x1="12" y1="4" x2="12" y2="20"></line>
        </svg>
      </div>
    </div>
    <div class="stat-value"><?= number_format($nVocab) ?></div>
    <div class="stat-note">Term unik setelah preprocessing</div>
  </div>

  <div class="stat-card" style="--stat-accent: #10b981;">
    <div class="stat-card-header">
      <span class="stat-title">Metode Perangkingan</span>
      <div class="stat-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 3v18"></path>
          <rect width="18" height="18" x="3" y="3" rx="2"></rect>
          <path d="M3 9h18"></path>
          <path d="M3 15h18"></path>
        </svg>
      </div>
    </div>
    <div class="stat-value" style="font-size: 1.5rem; color: #34d399;">VSM & TF-IDF</div>
    <div class="stat-note">W = TF &times; log10(N/DF)</div>
  </div>
</div>

<!-- Area Pengelolaan Dataset (Input Sendiri & Upload CSV) -->
<div class="card-section">
  <div class="section-header">
    <div class="section-title">
      <span>Manajemen Dataset (Input Dokumen Mandiri)</span>
    </div>
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
      <form method="POST" style="display: inline;">
        <input type="hidden" name="action" value="rebuild_index">
        <button type="submit" class="btn-secondary btn-sm" title="Bangun Ulang Matriks Bobot">
          Rebuild Indeks
        </button>
      </form>
      <?php if ($nDocs > 0): ?>
        <form method="POST" style="display: inline;"
          onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh dataset?');">
          <input type="hidden" name="action" value="clear_all">
          <button type="submit" class="btn-danger btn-sm" title="Kosongkan seluruh data">
            Kosongkan Semua
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Segmented Tabs Navigation -->
  <div class="tabs-nav">
    <button type="button" class="tab-btn active" data-tab="tab-manual">
      <span>1. Input Dokumen Manual (Satuan)</span>
    </button>
    <button type="button" class="tab-btn" data-tab="tab-csv">
      <span>2. Unggah File CSV Milik Anda</span>
    </button>
  </div>

  <!-- Tab 1: Input Dokumen Manual (Satuan) -->
  <div class="tab-pane active" id="tab-manual">
    <div class="tab-content-card">
      <div style="margin-bottom: 1.25rem;">
        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; color: #38bdf8; margin-bottom: 0.35rem;">
          Input Dokumen Teks
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">
          Masukkan isi teks dokumen melalui formulir di bawah ini. Dokumen akan langsung disimpan dan diindeks ke
          sistem.
        </p>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="add_manual">
        <div class="form-group">
          <label class="form-label">
            <span>Isi Dokumen</span>
            <span style="color: #f87171;">* (Wajib)</span>
          </label>
          <textarea name="content" class="form-textarea" rows="5"
            placeholder="Ketik atau tempel isi teks dokumen di sini..." required></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
          <button type="submit" class="btn-primary">
            <span>Simpan & Indeks Dokumen</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tab 2: Unggah File CSV Milik Anda -->
  <div class="tab-pane" id="tab-csv">
    <div class="tab-content-card">
      <div style="margin-bottom: 1.25rem;">
        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; color: #a5b4fc; margin-bottom: 0.35rem;">
          Unggah File CSV Milik Anda
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">
          Pilih file CSV dari komputer Anda. Kolom teks otomatis dikenali (misal: <em>isi / content / teks</em>).
        </p>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_csv">
        <div class="form-group">
          <label class="form-label">
            <span>Pilih File CSV Komputer</span>
            <span style="color: #f87171;">*</span>
          </label>
          <input type="file" name="csv_file" id="csv_file" class="form-input" accept=".csv" required>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Mode Impor:</label>
            <select name="import_mode" class="form-select">
              <option value="replace" selected>Ganti Seluruh Data (Default)</option>
              <option value="append">Tambahkan ke Data yang Sudah Ada</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Batas Maksimal Baris:</label>
            <select name="max_rows" class="form-select">
              <option value="1000" selected>1.000 Dokumen</option>
              <option value="500">500 Dokumen</option>
              <option value="2000">2.000 Dokumen</option>
              <option value="5000">5.000 Dokumen</option>
              <option value="100000">Semua Baris (Sesuai Kapasitas)</option>
            </select>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
          <button type="submit" class="btn-primary">
            <span>Unggah & Indeks CSV</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Pratinjau Dokumen yang Tersimpan -->
  <div class="section-header" style="margin-top: 2rem;">
    <div class="section-title" style="font-size: 1.15rem;">
      <span>Daftar Dokumen yang Sedang Aktif (<?= count($docs) ?> Dokumen)</span>
    </div>
    <?php if ($nDocs > 0): ?>
      <div style="max-width: 280px; width: 100%;">
        <input type="text" id="tableFilterInput" class="form-input" placeholder="Filter tabel..."
          style="padding: 0.4rem 0.75rem; font-size: 0.82rem;">
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($docs)): ?>
    <div class="empty-state-card">
      <div class="empty-state-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path
            d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z">
          </path>
        </svg>
      </div>
      <h3 style="font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--text-bright);">
        Belum Ada Dokumen di Sistem
      </h3>
      <p style="font-size: 0.9rem; color: var(--text-secondary); max-width: 450px; margin: 0 auto 1.5rem;">
        Sistem saat ini bersih dan siap menerima data. Silakan pilih tab di atas untuk menginput dokumen manual atau
        mengunggah file CSV milik Anda.
      </p>
    </div>
  <?php else: ?>
    <div class="table-responsive" style="max-height: 440px; overflow-y: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 75px;">ID</th>
            <th>Isi</th>
            <th style="width: 140px; text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($docs, 0, 50) as $d): ?>
            <?php
            $docContent = !empty($d['content']) ? $d['content'] : ($d['title'] ?? '');
            ?>
            <tr>
              <td><span class="token-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($d['id']) ?></span></td>
              <td style="font-size: 0.88rem; line-height: 1.5;">
                <a href="fitur2_preprocess.php?id=<?= urlencode($d['id']) ?>" class="table-doc-content"
                  title="Buka di Pipeline Pra-proses Teks">
                  <?= htmlspecialchars(mb_strlen($docContent) > 220 ? mb_substr($docContent, 0, 220) . '...' : $docContent) ?>
                </a>
              </td>
              <td style="text-align: center; white-space: nowrap;">
                <a href="fitur2_preprocess.php?id=<?= urlencode($d['id']) ?>" class="btn-secondary btn-sm"
                  style="padding: 0.25rem 0.6rem; font-size: 0.75rem;" title="Lihat Pipeline Preprocessing">
                  Lihat
                </a>
                <form method="POST" style="display: inline;"
                  onsubmit="return confirm('Hapus dokumen ID <?= htmlspecialchars($d['id']) ?>?');">
                  <input type="hidden" name="action" value="delete_doc">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                  <button type="submit" class="btn-danger btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($docs) > 50): ?>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; text-align: right;">
        * Menampilkan 50 dokumen pertama dari total <?= count($docs) ?> dokumen.
      </p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>