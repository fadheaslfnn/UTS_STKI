# 02 — Project Scaffolding

Kerangka proyek siap pakai: struktur folder, dependensi, konfigurasi, dan kerangka kode (stub) tiap modul.

---

## 1. Struktur Folder

```
search-engine/
├── app.py                      # Entry point Flask + routes (Fitur 1 & 4)
├── config.py                   # Konfigurasi & mapping kolom CSV
├── requirements.txt
├── README.md
├── .gitignore
│
├── data/
│   └── dataset.csv             # Dataset 1.000 dokumen dari asisten
│
├── cache/
│   └── index.joblib            # Hasil index (di-generate, jangan di-commit)
│
├── engine/
│   ├── __init__.py
│   ├── preprocess.py           # Fitur 2: pipeline pra-proses
│   ├── tfidf.py                # Fitur 3: vocabulary, TF, IDF, matriks bobot
│   ├── vsm.py                  # Fitur 3: vektorisasi kueri, cosine, ranking
│   ├── index.py                # Build / load / save index
│   └── models.py               # Dataclass kontrak data (lihat dokumen 04)
│
├── templates/
│   ├── base.html               # Layout dasar
│   ├── index.html              # Fitur 1: dashboard
│   ├── results.html            # Fitur 4: hasil
│   └── document.html           # (opsional) detail dokumen
│
├── static/
│   ├── css/style.css
│   └── js/app.js               # (opsional)
│
├── tests/
│   ├── test_preprocess.py
│   ├── test_tfidf.py
│   └── test_search.py
│
└── scripts/
    └── build_index.py          # CLI: bangun ulang index
```

---

## 2. Dependensi (`requirements.txt`)

```
flask>=3.0
pandas>=2.0
numpy>=1.26
scipy>=1.11
joblib>=1.3
Sastrawi>=1.0.1        # hapus bila dataset berbahasa Inggris
nltk>=3.8              # hanya bila dataset berbahasa Inggris
pytest>=8.0
```

Setup:

```bash
python -m venv venv
source venv/bin/activate          # Windows: venv\Scripts\activate
pip install -r requirements.txt
```

---

## 3. Konfigurasi (`config.py`)

```python
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent

DATASET_PATH = BASE_DIR / "data" / "dataset.csv"
CACHE_PATH = BASE_DIR / "cache" / "index.joblib"

# Mapping kolom CSV -> field internal. SESUAIKAN dengan CSV dari asisten.
COLUMN_ID = "id"
COLUMN_TITLE = "title"
COLUMN_CONTENT = "content"
COLUMN_CATEGORY = None            # isi nama kolom bila ada, jika tidak None

LANGUAGE = "id"                   # "id" atau "en"
USE_STEMMING = True
IDF_FORMULA = "log10_n_df"        # log10(N / DF), sesuai teori VSM

DEFAULT_TOP_K = 10
MAX_TOP_K = 50
PAGE_SIZE = 10
SNIPPET_LENGTH = 220
```

---

## 4. Kerangka Kode

### `engine/preprocess.py`

```python
import re
import config

_URL = re.compile(r"https?://\S+|www\.\S+")
_HTML = re.compile(r"<[^>]+>")
_NON_ALPHA = re.compile(r"[^a-z\s]")
_SPACES = re.compile(r"\s+")

_stemmer = None
_stopwords: set[str] = set()


def _init():
    global _stemmer, _stopwords
    if _stemmer is not None:
        return
    if config.LANGUAGE == "id":
        from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
        from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory
        _stemmer = StemmerFactory().create_stemmer()
        _stopwords = set(StopWordRemoverFactory().get_stop_words())
    else:
        from nltk.stem import PorterStemmer
        from nltk.corpus import stopwords
        _stemmer = PorterStemmer()
        _stopwords = set(stopwords.words("english"))


def case_folding(text: str) -> str:
    return text.lower()


def cleaning(text: str) -> str:
    text = _URL.sub(" ", text)
    text = _HTML.sub(" ", text)
    text = _NON_ALPHA.sub(" ", text)
    return _SPACES.sub(" ", text).strip()


def tokenize(text: str) -> list[str]:
    return text.split()


def remove_stopwords(tokens: list[str]) -> list[str]:
    _init()
    return [t for t in tokens if t not in _stopwords and len(t) > 1]


def stem(tokens: list[str]) -> list[str]:
    _init()
    if not config.USE_STEMMING:
        return tokens
    if config.LANGUAGE == "id":
        return [_stemmer.stem(t) for t in tokens]
    return [_stemmer.stem(t) for t in tokens]


def preprocess(text: str) -> list[str]:
    """Pipeline lengkap. Dipakai SAMA untuk dokumen dan kueri."""
    text = case_folding(text or "")
    text = cleaning(text)
    tokens = tokenize(text)
    tokens = remove_stopwords(tokens)
    return stem(tokens)


def preprocess_steps(text: str) -> dict:
    """Hasil tiap tahap, untuk demo/laporan."""
    folded = case_folding(text or "")
    cleaned = cleaning(folded)
    tokens = tokenize(cleaned)
    no_stop = remove_stopwords(tokens)
    return {
        "original": text,
        "case_folding": folded,
        "cleaning": cleaned,
        "tokens": tokens,
        "stopword_removed": no_stop,
        "stemmed": stem(no_stop),
    }
```

### `engine/tfidf.py`

```python
from collections import Counter
import numpy as np


def build_vocabulary(docs_tokens: list[list[str]]) -> dict[str, int]:
    terms = sorted({t for doc in docs_tokens for t in doc})
    return {term: i for i, term in enumerate(terms)}


def term_frequency(docs_tokens, vocab) -> np.ndarray:
    tf = np.zeros((len(docs_tokens), len(vocab)), dtype=np.float32)
    for i, doc in enumerate(docs_tokens):
        for term, count in Counter(doc).items():
            tf[i, vocab[term]] = count
    return tf


def inverse_document_frequency(tf: np.ndarray) -> np.ndarray:
    n_docs = tf.shape[0]
    df = (tf > 0).sum(axis=0)
    return np.log10(n_docs / df).astype(np.float32)


def weight_matrix(tf: np.ndarray, idf: np.ndarray) -> np.ndarray:
    return tf * idf
```

### `engine/vsm.py`

```python
import time
from collections import Counter
import numpy as np
from engine.preprocess import preprocess


def vectorize_query(tokens, vocab, idf) -> np.ndarray:
    q = np.zeros(len(vocab), dtype=np.float32)
    for term, count in Counter(tokens).items():
        if term in vocab:
            q[vocab[term]] = count
    return q * idf


def search(query: str, index, top_k: int = 10) -> dict:
    start = time.perf_counter()
    tokens = preprocess(query)
    q = vectorize_query(tokens, index.vocab, index.idf)
    q_norm = float(np.linalg.norm(q))

    hits = []
    if q_norm > 0:
        scores = (index.W @ q) / (index.norms * q_norm + 1e-12)
        order = np.argsort(-scores)
        hits = [(int(i), float(scores[i])) for i in order if scores[i] > 0]

    elapsed_ms = (time.perf_counter() - start) * 1000
    return {"tokens": tokens, "hits": hits, "elapsed_ms": elapsed_ms}
```

### `engine/index.py`

```python
from dataclasses import dataclass
import joblib
import numpy as np
import pandas as pd
import config
from engine.preprocess import preprocess
from engine import tfidf


@dataclass
class SearchIndex:
    docs: list[dict]              # metadata dokumen asli
    vocab: dict[str, int]
    idf: np.ndarray
    W: np.ndarray                 # matriks bobot (N x V)
    norms: np.ndarray             # norma tiap dokumen
    built_at: str


def load_dataframe() -> pd.DataFrame:
    df = pd.read_csv(config.DATASET_PATH)
    required = [config.COLUMN_ID, config.COLUMN_TITLE, config.COLUMN_CONTENT]
    missing = [c for c in required if c not in df.columns]
    if missing:
        raise ValueError(f"Kolom CSV tidak ditemukan: {missing}")
    df[config.COLUMN_TITLE] = df[config.COLUMN_TITLE].fillna("")
    df[config.COLUMN_CONTENT] = df[config.COLUMN_CONTENT].fillna("")
    return df


def build_index() -> SearchIndex:
    from datetime import datetime
    df = load_dataframe()
    docs = [
        {"id": r[config.COLUMN_ID], "title": r[config.COLUMN_TITLE],
         "content": r[config.COLUMN_CONTENT]}
        for _, r in df.iterrows()
    ]
    tokens = [preprocess(f"{d['title']} {d['content']}") for d in docs]
    vocab = tfidf.build_vocabulary(tokens)
    tf = tfidf.term_frequency(tokens, vocab)
    idf = tfidf.inverse_document_frequency(tf)
    W = tfidf.weight_matrix(tf, idf)
    norms = np.linalg.norm(W, axis=1)
    return SearchIndex(docs, vocab, idf, W, norms, datetime.now().isoformat(timespec="seconds"))


def load_or_build() -> SearchIndex:
    if config.CACHE_PATH.exists():
        return joblib.load(config.CACHE_PATH)
    index = build_index()
    config.CACHE_PATH.parent.mkdir(exist_ok=True)
    joblib.dump(index, config.CACHE_PATH)
    return index
```

### `app.py`

```python
from flask import Flask, render_template, request
import config
from engine.index import load_or_build
from engine.vsm import search

app = Flask(__name__)
INDEX = load_or_build()


def make_snippet(text: str, tokens: list[str], length: int) -> str:
    low = text.lower()
    pos = next((low.find(t) for t in tokens if t in low), 0)
    start = max(0, pos - length // 3)
    snippet = text[start:start + length].strip()
    return ("..." if start > 0 else "") + snippet + ("..." if start + length < len(text) else "")


@app.get("/")
def home():
    stats = {"n_docs": len(INDEX.docs), "n_terms": len(INDEX.vocab), "built_at": INDEX.built_at}
    return render_template("index.html", stats=stats, default_k=config.DEFAULT_TOP_K)


@app.get("/search")
def results():
    q = request.args.get("q", "").strip()
    k = min(int(request.args.get("k", config.DEFAULT_TOP_K)), config.MAX_TOP_K)
    page = max(int(request.args.get("page", 1)), 1)

    out = search(q, INDEX) if q else {"tokens": [], "hits": [], "elapsed_ms": 0.0}
    hits = out["hits"][:k]
    items = []
    for rank, (idx, score) in enumerate(hits, start=1):
        d = INDEX.docs[idx]
        items.append({
            "rank": rank, "doc_id": d["id"], "title": d["title"],
            "snippet": make_snippet(d["content"], out["tokens"], config.SNIPPET_LENGTH),
            "score": round(score, 4),
        })
    return render_template("results.html", q=q, k=k, items=items,
                           total=len(out["hits"]), tokens=out["tokens"],
                           elapsed_ms=round(out["elapsed_ms"], 2))


if __name__ == "__main__":
    app.run(debug=True)
```

---

## 5. Menjalankan

```bash
python scripts/build_index.py     # opsional: bangun index manual
python app.py                     # buka http://127.0.0.1:5000
pytest                            # jalankan test
```

## 6. `.gitignore`

```
venv/
__pycache__/
cache/
*.pyc
.env
```
