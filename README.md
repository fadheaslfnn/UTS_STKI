# Mesin Pencari Mandiri VSM & TF-IDF (PHP Native - Laragon)

Aplikasi web mesin pencari mandiri untuk **Ujian Tengah Semester (UTS) Sistem Temu Kembali Informasi (STKI)**. 

---

## Struktur 4 Fitur Utama

Sistem ini memisahkan 4 fitur utama secara terstruktur dan terintegrasi:

| Fitur | Halaman File | Deskripsi & Fungsi |
|---|---|---|
| **Fitur 1** | `index.php` | **Dashboard Pencarian & Manajemen Dataset Input Sendiri**: Input kueri pencarian, pilihan Top-K, metrik korpus (jumlah dokumen & vocabulary), sistem tab input mandiri (Input Satuan, Tempel Teks Massal, Upload CSV), dan tombol Rebuild/Kosongkan. |
| **Fitur 2** | `fitur2_preprocess.php` | **Pipeline Pra-proses Teks Interaktif**: Demonstrasi transparan 5 tahap: *Case Folding*, *Cleaning*, *Tokenizing*, *Stopword Removal* (Sastrawi), dan *Stemming* (Nazief & Adriani) dengan acuan kamus 29.932 kata dasar bahasa Indonesia di `data/kata-dasar.txt`. |
| **Fitur 3** | `fitur3_vsm.php` | **Mesin Hitung VSM & Matriks TF-IDF**: Formula matematika baku, simulator hitung manual kesamaan kosinus per kueri, tabel Vocabulary & Nilai IDF $\log_{10}(N / \text{DF})$, serta matriks bobot dokumen $W = \text{TF} \times \text{IDF}$ dan norma L2 $\|d\|$. |
| **Fitur 4** | `fitur4_hasil.php` | **Halaman Hasil Pencarian & Detail**: Perangkingan relevansi skor Cosine Similarity (4 desimal), bilah visual persentase relevansi, token kueri, cuplikan teks (*smart snippet*) dengan penanda kata kunci (`<mark class="highlight">`), badge peringkat (#1, #2, #3), serta halaman detail utuh `document.php?id=...`. |

---

## Fitur Mode Gelap & Terang (Dark / Light Mode)

Aplikasi mendukung pengalihan tema gelap dan terang secara instan melalui tombol pada bilah navigasi kanan atas. Pilihan tema tersimpan otomatis di penyimpanan lokal peramban (*localStorage*).

---

## Cara Input Dataset Sendiri

Anda tidak perlu mengedit kode atau memodifikasi file secara manual:
1. Buka halaman utama **Fitur 1 (Dashboard)**: `http://localhost/uts_stki/`
2. Pada bagian **Manajemen Dataset**, pilih salah satu tab:
   - **Tab 1 (Input Dokumen Manual)**: Masukkan isi teks dokumen secara langsung ke form.
   - **Tab 2 (Upload File CSV Milik Anda)**: Pilih file CSV dari komputer Anda, pilih batas baris, lalu klik **Unggah & Indeks CSV**.

---

## Cara Menjalankan Menggunakan Laragon

1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All** (Apache & MySQL akan menyala).
3. Buka browser Anda dan akses:
   - **http://localhost/uts_stki/**
   - atau **http://uts_stki.test/**
4. Aplikasi langsung berjalan tanpa perlu menjalankan server Python.

---

## Verifikasi Pengujian

Untuk memverifikasi keakuratan fungsi secara CLI:
```bash
php tests/test_all_features.php
```
