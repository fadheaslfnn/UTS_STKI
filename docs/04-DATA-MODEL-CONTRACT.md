# 04 — Data Model & Kontrak

Dokumen ini mendefinisikan bentuk data di setiap batas modul: CSV masuk, struktur index, antarmuka fungsi engine, serta kontrak route/API web. Semua modul harus patuh pada kontrak ini.

---

## 1. Kontrak Dataset (CSV Masuk)

File: `data/dataset.csv` · Encoding: UTF-8 · Header: wajib ada · Baris: 1.000

| Kolom (default) | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | int / string | Ya | Unik, tidak boleh duplikat |
| `title` | string | Ya | Boleh kosong → dianggap `""` |
| `content` | string | Ya | Boleh kosong → dianggap `""` |
| kolom lain (kategori, tanggal, dll.) | string | Tidak | Diabaikan oleh engine kecuali dipetakan di `config.py` |

> Nama kolom aktual dari asisten mungkin berbeda (mis. `judul`, `isi`, `teks`). Cukup ubah `COLUMN_*` di `config.py`; kode lain tidak berubah.

**Aturan validasi saat load**

| Kondisi | Tindakan |
|---|---|
| Kolom wajib tidak ada | Hentikan start, error: `Kolom CSV tidak ditemukan: [...]` |
| `id` duplikat | Hentikan start, daftar id duplikat ditampilkan |
| `title`/`content` bernilai NaN | Ganti dengan string kosong |
| Teks gabungan kosong | Dokumen tetap diindeks, vektor nol, tidak pernah muncul di hasil |
| Jumlah baris ≠ 1.000 | Peringatan di log (bukan error) |

---

## 2. Model Data Internal (`engine/models.py`)

```python
from dataclasses import dataclass, field
import numpy as np


@dataclass(frozen=True)
class Document:
    id: str | int            # ID asli dari dataset
    title: str
    content: str             # teks asli (untuk snippet), BUKAN hasil preprocessing


@dataclass
class SearchIndex:
    docs: list[Document]     # urutan = baris matriks W
    vocab: dict[str, int]    # term -> indeks kolom
    idf: np.ndarray          # shape (V,), float32
    W: np.ndarray            # shape (N, V), bobot TF-IDF dokumen
    norms: np.ndarray        # shape (N,), ‖d‖ tiap dokumen
    built_at: str            # ISO 8601, "2026-10-12T09:30:00"


@dataclass(frozen=True)
class Hit:
    doc_index: int           # indeks baris di SearchIndex.docs
    score: float             # cosine similarity, 0 < score <= 1


@dataclass(frozen=True)
class SearchOutput:
    query: str               # kueri asli
    tokens: list[str]        # token kueri setelah preprocessing
    hits: list[Hit]          # terurut menurun, hanya score > 0
    elapsed_ms: float
```

**Invarian index (harus selalu benar)**

1. `W.shape == (len(docs), len(vocab))`
2. `idf.shape == (len(vocab),)` dan `idf >= 0`
3. `norms[i] == np.linalg.norm(W[i])`
4. Urutan `docs` identik dengan urutan baris `W`
5. Index di-load dari cache **atau** dibangun ulang; tidak boleh keadaan setengah jadi

---

## 3. Kontrak Fungsi Engine

### 3.1 Preprocessing (`engine/preprocess.py`)

| Fungsi | Input | Output | Catatan |
|---|---|---|---|
| `preprocess(text)` | `str \| None` | `list[str]` | Deterministik; `None`/`""` → `[]` |
| `preprocess_steps(text)` | `str \| None` | `dict` (lihat bawah) | Hanya untuk demo/laporan |

Bentuk `preprocess_steps`:

```json
{
  "original": "Belajar Jaringan Komputer!",
  "case_folding": "belajar jaringan komputer!",
  "cleaning": "belajar jaringan komputer",
  "tokens": ["belajar", "jaringan", "komputer"],
  "stopword_removed": ["belajar", "jaringan", "komputer"],
  "stemmed": ["ajar", "jaring", "komputer"]
}
```

Jaminan: tidak ada token kosong, tidak ada token berisi spasi/angka/tanda baca, semua huruf kecil.

### 3.2 TF-IDF (`engine/tfidf.py`)

| Fungsi | Input | Output |
|---|---|---|
| `build_vocabulary(docs_tokens)` | `list[list[str]]` | `dict[str,int]` (terurut alfabet) |
| `term_frequency(docs_tokens, vocab)` | token + vocab | `ndarray (N,V)` hitungan mentah |
| `inverse_document_frequency(tf)` | `ndarray (N,V)` | `ndarray (V,)` = `log10(N/DF)` |
| `weight_matrix(tf, idf)` | keduanya | `ndarray (N,V)` = `tf * idf` |

Syarat: setiap term di vocab muncul di ≥ 1 dokumen (DF ≥ 1), sehingga tidak ada pembagian nol.

### 3.3 Pencarian (`engine/vsm.py`)

```python
def search(query: str, index: SearchIndex) -> SearchOutput
```

| Kondisi | Hasil |
|---|---|
| `query` kosong / hanya spasi | `hits=[]`, `tokens=[]` |
| Semua token tidak ada di vocab | `hits=[]` |
| Norma kueri = 0 | `hits=[]` (tanpa `NaN`) |
| Normal | `hits` terurut skor menurun, hanya skor > 0 |

Fungsi mengembalikan **semua** hit; pemotongan Top-K/pagination dilakukan di lapisan web.

---

## 4. Kontrak Web (Route)

### 4.1 `GET /` — Dashboard (Fitur 1)

Respons: HTML. Konteks template `index.html`:

```json
{
  "stats": { "n_docs": 1000, "n_terms": 8421, "built_at": "2026-10-12T09:30:00" },
  "default_k": 10,
  "k_options": [5, 10, 20, 50]
}
```

### 4.2 `GET /search` — Hasil (Fitur 4)

**Query string**

| Param | Tipe | Wajib | Default | Aturan |
|---|---|---|---|---|
| `q` | string | Tidak | `""` | di-`strip()`; maks 200 karakter (dipotong) |
| `k` | int | Tidak | `10` | dijepit ke `1..MAX_TOP_K`; nilai tidak valid → default |
| `page` | int | Tidak | `1` | ≥ 1; di luar jangkauan → halaman terakhir |

**Konteks template `results.html`**

```json
{
  "q": "jaringan komputer",
  "tokens": ["jaring", "komputer"],
  "k": 10,
  "page": 1,
  "total_pages": 1,
  "total_relevant": 37,
  "elapsed_ms": 4.82,
  "items": [
    {
      "rank": 1,
      "doc_id": 245,
      "title": "Pengantar Jaringan Komputer",
      "snippet": "...jaringan komputer adalah sekumpulan perangkat yang...",
      "score": 0.9487
    }
  ],
  "empty_reason": null
}
```

**`empty_reason`**

| Nilai | Kondisi | Pesan UI |
|---|---|---|
| `null` | Ada hasil | — |
| `"empty_query"` | `q` kosong | "Masukkan kata kunci untuk mulai mencari." |
| `"no_match"` | Tidak ada dokumen skor > 0 | "Tidak ada dokumen yang cocok dengan kata kunci Anda." |

Aturan `items`: `rank` berurutan mulai 1 (global, bukan per halaman) · `score` dibulatkan 4 desimal · `snippet` ≤ `SNIPPET_LENGTH` karakter + elipsis.

### 4.3 `GET /doc/<doc_id>` — Detail (opsional)

| Kondisi | Respons |
|---|---|
| ID ada | 200, HTML dengan judul + isi lengkap |
| ID tidak ada | 404, halaman "Dokumen tidak ditemukan" |

### 4.4 `GET /api/search` — JSON (opsional, untuk debug/uji)

Parameter sama dengan `/search`. Respons `200 application/json`:

```json
{
  "query": "jaringan komputer",
  "tokens": ["jaring", "komputer"],
  "total_relevant": 37,
  "elapsed_ms": 4.82,
  "results": [
    { "rank": 1, "doc_id": 245, "title": "Pengantar Jaringan Komputer", "score": 0.9487 }
  ]
}
```

Kueri kosong → `200` dengan `results: []` (bukan error).

---

## 5. Kontrak Cache

| Item | Nilai |
|---|---|
| Lokasi | `cache/index.joblib` |
| Isi | Objek `SearchIndex` |
| Dibuat | Saat start bila file tidak ada |
| Invalidasi | Hapus file, atau jalankan `scripts/build_index.py` |
| Bila file rusak/gagal dimuat | Bangun ulang, timpa file |
| Di-commit ke Git | **Tidak** |

---

## 6. Penanganan Error

| Situasi | Perilaku |
|---|---|
| CSV tidak ditemukan | Gagal start, pesan jelas dengan path yang dicari |
| Kolom wajib hilang | Gagal start, daftar kolom yang hilang |
| `k`/`page` bukan angka | Pakai nilai default, tanpa error 500 |
| Kueri sangat panjang | Dipotong 200 karakter |
| Error tak terduga di `/search` | Halaman error ramah, detail hanya di log |

---

## 7. Checklist Kepatuhan Kontrak

- [ ] Kueri dan dokumen memakai fungsi `preprocess` yang sama
- [ ] `IDF` kueri diambil dari index, bukan dihitung ulang
- [ ] Tidak ada `NaN`/`inf` pada skor
- [ ] `hits` terurut menurun dan hanya berisi skor > 0
- [ ] ID dokumen yang tampil = ID asli dari CSV
- [ ] Setiap `empty_reason` punya pesan UI
