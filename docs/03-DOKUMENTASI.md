# 03 — Dokumentasi Proyek

# Aplikasi Web Mesin Pencari Sederhana (VSM + TF-IDF)

---

## 1. Ringkasan

Aplikasi web mesin pencari mandiri yang mengindeks **1.000 dokumen** dari file CSV, membobotkan kata dengan **TF-IDF**, dan merangking dokumen terhadap kueri pengguna dengan **Cosine Similarity** pada **Vector Space Model (VSM)**.

| Aspek | Keterangan |
|---|---|
| Bahasa | Python 3.10+ |
| Framework web | Flask |
| Pengolah data | pandas, numpy |
| Stemming | Sastrawi (ID) / NLTK (EN) |
| Penyimpanan index | `joblib` (file cache) |
| Dataset | `data/dataset.csv` (1.000 dokumen) |

---

## 2. Arsitektur

```
 ┌────────────┐   GET /search?q=...   ┌─────────────┐
 │  Browser   │ ────────────────────► │  Flask app  │
 │ (Fitur 1,4)│ ◄──────────────────── │   app.py    │
 └────────────┘      HTML hasil       └──────┬──────┘
                                             │ search(query, index)
                                      ┌──────▼──────┐
                                      │ engine/vsm  │  Fitur 3
                                      └──────┬──────┘
                          ┌──────────────────┼──────────────────┐
                   ┌──────▼──────┐    ┌──────▼──────┐    ┌──────▼──────┐
                   │ preprocess  │    │   tfidf     │    │   index     │
                   │  (Fitur 2)  │    │ TF·IDF · W  │    │ build/cache │
                   └─────────────┘    └─────────────┘    └──────┬──────┘
                                                         data/dataset.csv
```

### Dua alur utama

**Alur indeksasi (sekali, saat start / rebuild)**
1. Baca CSV → validasi kolom → isi nilai kosong
2. Gabungkan `title` + `content`
3. Preprocessing tiap dokumen
4. Bangun vocabulary → TF → IDF → matriks bobot W → norma dokumen
5. Simpan ke `cache/index.joblib`

**Alur pencarian (tiap request)**
1. Terima kueri `q`
2. Preprocessing kueri (pipeline sama)
3. Vektorisasi kueri dengan IDF korpus
4. Hitung cosine similarity ke seluruh dokumen
5. Urutkan menurun, buang skor 0, ambil Top-K
6. Buat snippet, render halaman hasil

---

## 3. Fitur

### Fitur 1 — Halaman Utama (Dashboard Pencarian)
- Route: `GET /`
- Berisi form pencarian, pilihan Top-K, dan statistik sistem (jumlah dokumen, ukuran vocabulary, waktu index dibuat).

### Fitur 2 — Pipeline Pra-proses Teks
Urutan tahap (modul `engine/preprocess.py`):

| # | Tahap | Contoh |
|---|---|---|
| 1 | Case folding | `Belajar Jaringan!` → `belajar jaringan!` |
| 2 | Cleaning | `belajar jaringan!` → `belajar jaringan` |
| 3 | Tokenizing | → `["belajar", "jaringan"]` |
| 4 | Stopword removal | buang `yang`, `dan`, `di`, dst. |
| 5 | Stemming | `pembelajaran` → `ajar` |

Fungsi yang sama dipakai untuk dokumen dan kueri agar ruang term konsisten.

### Fitur 3 — Mesin Hitung VSM & Perangkingan
Lihat bagian 4 (landasan teori) dan 5 (contoh perhitungan).

### Fitur 4 — Halaman Hasil Pencarian
- Route: `GET /search?q=<kueri>&k=<topk>&page=<n>`
- Menampilkan: kueri, jumlah dokumen relevan, waktu eksekusi, dan daftar hasil (peringkat, judul, snippet, skor cosine, ID dokumen).
- Empty state bila kueri kosong atau tidak ada dokumen dengan skor > 0.

---

## 4. Landasan Teori

### 4.1 Term Frequency (TF)
Jumlah kemunculan term *t* pada dokumen *d*:

$$\text{TF}(t,d) = f_{t,d}$$

### 4.2 Inverse Document Frequency (IDF)

$$\text{IDF}(t) = \log_{10}\left(\frac{N}{\text{DF}(t)}\right)$$

dengan *N* = jumlah dokumen (1.000) dan DF(t) = jumlah dokumen yang memuat *t*.

> Catatan: `TfidfVectorizer` scikit-learn memakai IDF *smoothed* `ln((1+N)/(1+DF)) + 1`, sehingga nilainya berbeda dari rumus di atas. Aplikasi ini memakai rumus teori standar.

### 4.3 Bobot TF-IDF

$$w_{t,d} = \text{TF}(t,d) \times \text{IDF}(t)$$

### 4.4 Cosine Similarity

$$\cos(\vec{q},\vec{d}) = \frac{\vec{q}\cdot\vec{d}}{\lVert\vec{q}\rVert\,\lVert\vec{d}\rVert} = \frac{\sum_t w_{t,q}\,w_{t,d}}{\sqrt{\sum_t w_{t,q}^2}\,\sqrt{\sum_t w_{t,d}^2}}$$

Nilai berada pada rentang 0–1 (bobot non-negatif). Semakin dekat ke 1, semakin mirip.

---

## 5. Contoh Perhitungan Manual (untuk Laporan)

Korpus mini, *N* = 3 (setelah preprocessing):

| Dokumen | Token |
|---|---|
| D1 | `jaringan komputer jaringan` |
| D2 | `komputer basis data` |
| D3 | `jaringan nirkabel` |

Kueri: `jaringan komputer`

**DF dan IDF**

| Term | DF | IDF = log10(3/DF) |
|---|---|---|
| jaringan | 2 | 0,1761 |
| komputer | 2 | 0,1761 |
| basis | 1 | 0,4771 |
| data | 1 | 0,4771 |
| nirkabel | 1 | 0,4771 |

**Bobot (TF × IDF)**

| Term | D1 | D2 | D3 | Query |
|---|---|---|---|---|
| jaringan | 2×0,1761 = 0,3522 | 0 | 0,1761 | 0,1761 |
| komputer | 0,1761 | 0,1761 | 0 | 0,1761 |
| basis | 0 | 0,4771 | 0 | 0 |
| data | 0 | 0,4771 | 0 | 0 |
| nirkabel | 0 | 0 | 0,4771 | 0 |

**Cosine** (norma Q = √(0,1761²+0,1761²) = 0,2490)

- D1: dot = 0,3522×0,1761 + 0,1761×0,1761 = 0,0930; ‖D1‖ = 0,3938 → cos ≈ **0,9486**
- D2: dot = 0,1761×0,1761 = 0,0310; ‖D2‖ = 0,6973 → cos ≈ **0,1786**
- D3: dot = 0,1761×0,1761 = 0,0310; ‖D3‖ = 0,5086 → cos ≈ **0,2449**

**Ranking:** D1 (0,9486) > D3 (0,2449) > D2 (0,1786)

> Gunakan angka-angka ini sebagai kasus uji di `tests/test_tfidf.py`.

---

## 6. Instalasi & Menjalankan

```bash
git clone <repo-url> && cd search-engine
python -m venv venv && source venv/bin/activate
pip install -r requirements.txt
# letakkan CSV di data/dataset.csv, sesuaikan kolom di config.py
python app.py
```

Buka `http://127.0.0.1:5000`. Pada start pertama index dibangun (bisa memakan waktu karena stemming), start berikutnya memakai cache.

Bangun ulang index: hapus `cache/index.joblib` atau jalankan `python scripts/build_index.py`.

---

## 7. Konfigurasi

| Variabel (`config.py`) | Fungsi | Default |
|---|---|---|
| `COLUMN_ID/TITLE/CONTENT` | Mapping nama kolom CSV | `id/title/content` |
| `LANGUAGE` | Bahasa stopword & stemmer | `id` |
| `USE_STEMMING` | Aktif/nonaktif stemming | `True` |
| `DEFAULT_TOP_K` | Jumlah hasil bawaan | `10` |
| `MAX_TOP_K` | Batas atas Top-K | `50` |
| `PAGE_SIZE` | Hasil per halaman | `10` |
| `SNIPPET_LENGTH` | Panjang cuplikan (karakter) | `220` |

---

## 8. Strategi Pengujian

| Kasus | Ekspektasi |
|---|---|
| Kueri kosong | Empty state, tanpa error |
| Kueri hanya stopword | Hasil kosong |
| Kueri simbol acak (`@#$%`) | Hasil kosong |
| Kata di luar vocabulary | Hasil kosong |
| Kueri = judul sebuah dokumen | Dokumen tsb di peringkat atas |
| Contoh manual bagian 5 | Skor sama dengan perhitungan tangan (±1e-4) |
| Top-K > `MAX_TOP_K` | Dipotong ke `MAX_TOP_K` |
| CSV kolom hilang | Error jelas saat start |

---

## 9. Keterbatasan & Pengembangan Lanjut

- Dense matrix 1000 × |V| cukup untuk skala ini; gunakan `scipy.sparse` bila korpus membesar.
- Belum ada sinonim/ekspansi kueri.
- Pengembangan: BM25 sebagai pembanding, evaluasi Precision/Recall, highlight term, filter kategori.
