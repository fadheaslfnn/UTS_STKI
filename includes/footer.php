  </main>

  <!-- Footer Proyek Modern & Berstruktur -->
  <footer class="site-footer">
    <div class="footer-top-accent"></div>
    <div class="footer-main-container">
      <div class="footer-grid">
        
        <!-- Kolom 1: Branding & Deskripsi Sistem -->
        <div class="footer-col footer-col-brand">
          <div class="footer-brand">
            <div class="footer-brand-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
              </svg>
            </div>
            <div>
              <div class="footer-brand-title">VSM &amp; TF-IDF Search Engine</div>
              <div class="footer-brand-subtitle">Sistem Temu Kembali Informasi</div>
            </div>
          </div>
          <p class="footer-desc">
            Platform penelusuran informasi berbasis temu kembali teks untuk mengindeks dokumen, melakukan pra-proses bahasa alami, dan menyajikan dokumen yang paling relevan berdasarkan kecocokan semantik kueri.
          </p>
          <div class="footer-badges">
            <span class="footer-pill">Pra-proses Teks NLP</span>
            <span class="footer-pill">Pembobotan TF-IDF</span>
            <span class="footer-pill">Ruang Vektor VSM</span>
            <span class="footer-pill">Perangkingan Relevansi</span>
          </div>
        </div>

        <!-- Kolom 2: Navigasi 4 Fitur Utama -->
        <div class="footer-col footer-col-links">
          <div class="footer-col-title">Navigasi Fitur</div>
          <ul class="footer-nav-list">
            <li>
              <a href="index.php" class="footer-nav-link">
                <span class="footer-nav-num">01</span>
                <span>Dashboard &amp; Input Dataset</span>
              </a>
            </li>
            <li>
              <a href="fitur2_preprocess.php" class="footer-nav-link">
                <span class="footer-nav-num">02</span>
                <span>Pipeline Pra-proses NLP</span>
              </a>
            </li>
            <li>
              <a href="fitur3_vsm.php" class="footer-nav-link">
                <span class="footer-nav-num">03</span>
                <span>Mesin Hitung Matriks VSM</span>
              </a>
            </li>
            <li>
              <a href="fitur4_hasil.php" class="footer-nav-link">
                <span class="footer-nav-num">04</span>
                <span>Hasil Pencarian &amp; Temu Kembali</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Kolom 3: Spesifikasi Teknis & Quick Action -->
        <div class="footer-col footer-col-spec">
          <div class="footer-col-title">Spesifikasi Sistem</div>
          <div class="footer-spec-item">
            <span class="footer-spec-label">Mata Kuliah:</span>
            <span class="footer-spec-value">Sistem Temu Kembali Informasi (STKI)</span>
          </div>
          <div class="footer-spec-item">
            <span class="footer-spec-label">Skema Pembobotan:</span>
            <span class="footer-spec-value">Term Frequency &times; Inverse Doc Frequency</span>
          </div>
          <div class="footer-spec-item">
            <span class="footer-spec-label">Metode Perangkingan:</span>
            <span class="footer-spec-value">Normalisasi Cosine Similarity &isin; [0, 1]</span>
          </div>
          <div class="footer-spec-item">
            <span class="footer-spec-label">Representasi Korpus:</span>
            <span class="footer-spec-value">Matriks Ruang Vektor Multidimensi</span>
          </div>
          
          <button type="button" id="backToTopBtn" class="footer-back-to-top" title="Kembali ke Bagian Atas Halaman">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="m18 15-6-6-6 6"/>
            </svg>
            <span>Kembali ke Atas</span>
          </button>
        </div>

      </div>

      <!-- Bar Bawah / Copyright & Status -->
      <div class="footer-bottom-bar">
        <div class="footer-copyright">
          &copy; <?= date('Y') ?> <strong>UTS STKI</strong> &bull; Sistem Temu Kembali Informasi
        </div>
        <div class="footer-status-tag">
          <span class="footer-status-dot"></span>
          <span>Mesin Pencari Aktif &amp; Siap Digunakan</span>
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>
