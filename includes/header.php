<?php
/**
 * Header & Navbar Layout untuk Mesin Pencari VSM + TF-IDF (PHP Native)
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>VSM Search Engine (STKI)</title>
  
  <!-- Deteksi tema instan sebelum render untuk mencegah flicker -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('stki_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', savedTheme);
    })();
  </script>

  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='8'/><path d='m21 21-4.35-4.35'/></svg>">
</head>
<body>

  <!-- Navbar Navigasi 4 Fitur Utama -->
  <header class="navbar">
    <div class="nav-container">
      <a href="index.php" class="brand" title="Kembali ke Dashboard">
        <div class="brand-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </div>
        <div class="brand-text">
          <div class="brand-name">VSM &amp; TF-IDF</div>
          <div class="brand-tag">Sistem Temu Kembali Informasi</div>
        </div>
      </a>

      <nav class="nav-links">
        <a href="index.php" class="nav-link <?= in_array($currentPage, ['index.php', '']) ? 'active' : '' ?>">
          <span class="nav-num">01</span>
          <span class="nav-label">Input &amp; Dataset</span>
        </a>
        <a href="fitur2_preprocess.php" class="nav-link <?= $currentPage === 'fitur2_preprocess.php' ? 'active' : '' ?>">
          <span class="nav-num">02</span>
          <span class="nav-label">Pipeline NLP</span>
        </a>
        <a href="fitur3_vsm.php" class="nav-link <?= $currentPage === 'fitur3_vsm.php' ? 'active' : '' ?>">
          <span class="nav-num">03</span>
          <span class="nav-label">Mesin VSM</span>
        </a>
        <a href="fitur4_hasil.php" class="nav-link <?= $currentPage === 'fitur4_hasil.php' ? 'active' : '' ?>">
          <span class="nav-num">04</span>
          <span class="nav-label">Hasil Pencarian</span>
        </a>
      </nav>

      <div class="nav-actions">
        <!-- Tombol Pengalih Mode Gelap / Terang -->
        <button type="button" id="themeToggle" class="theme-toggle-btn" title="Ganti Mode Gelap / Terang" aria-label="Toggle Theme">
          <span class="theme-toggle-icon-wrap">
            <svg class="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="4"></circle>
              <path d="M12 2v2"></path>
              <path d="M12 20v2"></path>
              <path d="m4.93 4.93 1.41 1.41"></path>
              <path d="m17.66 17.66 1.41 1.41"></path>
              <path d="M2 12h2"></path>
              <path d="M20 12h2"></path>
              <path d="m6.34 17.66-1.41 1.41"></path>
              <path d="m19.07 4.93-1.41 1.41"></path>
            </svg>
            <svg class="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
            </svg>
          </span>
          <span id="themeLabelText" class="theme-text">Terang</span>
        </button>
      </div>
    </div>
  </header>

  <main class="main-content">
