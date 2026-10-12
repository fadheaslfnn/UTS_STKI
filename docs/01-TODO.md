# 01 — TODO: Aplikasi Web Mesin Pencari (VSM + TF-IDF)

> Target: aplikasi web mandiri yang mengindeks, membobotkan, dan merangking **1.000 dokumen** (CSV) berdasarkan kueri pengguna.
> Legenda: `[ ]` belum · `[x]` selesai · 🔴 wajib · 🟡 disarankan · 🟢 bonus

---

## Fase 0 — Setup

- [x] 🔴 Buat repositori dan struktur folder (lihat `02-PROJECT-SCAFFOLDING.md`)
- [x] 🔴 Buat virtual environment dan `requirements.txt`
- [x] 🔴 Letakkan CSV dari asisten di `data/dataset.csv`
- [x] 🔴 Cek kolom CSV, lalu isi mapping kolom di `config.py` (lihat `04-DATA-MODEL-CONTRACT.md`)
- [x] 🟡 Buat `.gitignore` (venv, `__pycache__`, `cache/`)

---

## Fitur 2 — Pipeline Pra-proses Teks (`engine/preprocess.py`)

> Dikerjakan lebih dulu karena Fitur 3 bergantung padanya.

- [x] 🔴 **Case folding**: ubah ke huruf kecil
- [x] 🔴 **Cleaning**: hapus URL, tag HTML, angka, tanda baca, karakter khusus, spasi berlebih
- [x] 🔴 **Tokenizing**: pecah teks menjadi token kata
- [x] 🔴 **Stopword removal**: daftar stopword sesuai bahasa dataset (ID/EN)
- [x] 🔴 **Stemming**: Sastrawi (ID) atau Porter/Snowball (EN)
- [x] 🔴 Fungsi tunggal `preprocess(text) -> list[str]` yang dipakai **sama persis** untuk dokumen dan kueri
- [x] 🟡 Fungsi `preprocess_steps(text)` yang mengembalikan hasil tiap tahap (untuk laporan/demo)
- [x] 🟡 Buang token dengan panjang < 2 karakter
- [x] 🟡 Unit test tiap tahap (`tests/test_preprocess.py`)

---

## Fitur 3 — Mesin Hitung VSM & Perangkingan (`engine/`)

### Indeksasi (`engine/index.py`)
- [x] 🔴 Muat CSV dengan pandas, validasi kolom, tangani nilai kosong
- [x] 🔴 Gabungkan kolom teks (`title` + `content`) menjadi satu teks per dokumen
- [x] 🔴 Preprocessing seluruh 1.000 dokumen
- [x] 🔴 Simpan hasil index ke `cache/index.joblib` agar tidak dihitung ulang tiap start
- [x] 🔴 Saat start: muat cache bila ada, bila tidak ada bangun ulang
- [x] 🟡 Tombol/endpoint **rebuild index**

### Pembobotan (`engine/tfidf.py`)
- [x] 🔴 Bangun **vocabulary** (term unik → indeks kolom)
- [x] 🔴 Hitung **TF** (raw count; opsi log-normalization)
- [x] 🔴 Hitung **DF** dan **IDF** = `log10(N / DF)`
- [x] 🔴 Bangun matriks bobot dokumen **W = TF × IDF** (ukuran 1000 × |V|)
- [x] 🔴 Simpan norma tiap vektor dokumen (pra-hitung)

### Pencarian (`engine/vsm.py`)
- [x] 🔴 Preprocess kueri dengan pipeline yang sama
- [x] 🔴 Vektorisasi kueri memakai **IDF korpus** (bukan IDF dihitung dari kueri)
- [x] 🔴 **Cosine similarity** kueri vs semua dokumen
- [x] 🔴 Urutkan skor menurun, buang skor `<= 0`, ambil Top-K
- [x] 🔴 Kueri kosong / semua term OOV → hasil kosong (cegah pembagian 0/0)
- [x] 🟡 Ukur waktu eksekusi (ms)
- [x] 🟡 Buat snippet: potong teks asli di sekitar term kueri pertama yang ditemukan
- [x] 🟢 Highlight term kueri di snippet

---

## Fitur 1 — Halaman Utama / Dashboard Pencarian

- [x] 🔴 Route `GET /` dan template `index.html`
- [x] 🔴 Form pencarian (input kueri + tombol Cari), method `GET` ke `/search`
- [x] 🔴 Panel info sistem: jumlah dokumen terindeks, ukuran vocabulary, waktu build index
- [x] 🟡 Pilihan Top-K (5 / 10 / 20 / 50)
- [x] 🟡 Contoh kueri yang bisa diklik
- [x] 🟢 Tampilkan beberapa term paling sering / IDF tertinggi

---

## Fitur 4 — Halaman Hasil Pencarian

- [x] 🔴 Route `GET /search?q=...&k=...&page=...` dan template `results.html`
- [x] 🔴 Header hasil: kueri asli, jumlah dokumen relevan, waktu eksekusi
- [x] 🔴 Kartu hasil: peringkat, judul, snippet, **skor cosine**, **ID dokumen**
- [x] 🔴 Empty state yang informatif (kueri kosong / tidak ada kecocokan)
- [x] 🔴 Kotak pencarian tetap tampil di halaman hasil
- [x] 🟡 Pagination (10 per halaman)
- [x] 🟡 Halaman detail dokumen `GET /doc/<id>`
- [x] 🟢 Tampilkan token kueri setelah preprocessing

---

## Fase Akhir — Pengujian & Dokumentasi

- [x] 🔴 Uji edge case: kueri kosong, hanya stopword, simbol acak, kata di luar vocabulary, kueri sangat panjang
- [x] 🔴 Verifikasi manual TF-IDF pada 3 dokumen kecil (hitung tangan vs output sistem)
- [x] 🔴 Pastikan kueri yang berasal dari judul sebuah dokumen menempatkan dokumen itu di peringkat atas
- [x] 🟡 Bandingkan hasil dengan `TfidfVectorizer` scikit-learn sebagai sanity check (urutan serupa, nilai bisa berbeda karena formula IDF)
- [x] 🔴 Lengkapi `README.md` (instalasi + cara menjalankan)
- [x] 🔴 Siapkan screenshot: dashboard, hasil, empty state
- [x] 🟡 Siapkan contoh perhitungan TF-IDF satu kueri untuk laporan

---

## Urutan Pengerjaan yang Disarankan

1. Fase 0 → 2. Fitur 2 (preprocess) → 3. Fitur 3 (engine, uji via terminal) → 4. Fitur 1 + Fitur 4 (web) → 5. Pengujian & dokumentasi
