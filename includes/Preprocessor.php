<?php
/**
 * Fitur 2: Pipeline Pra-proses Teks Bahasa Indonesia
 * Tahapan: Case Folding -> Cleaning -> Tokenizing -> Stopword Removal -> Stemming (Nazief-Adriani)
 */

class Preprocessor
{
    private static ?array $stopwords = null;
    private static ?array $rootWords = null;
    private static array $stemCache = [];

    /**
     * Memuat daftar stopword bahasa Indonesia (Sastrawi + Standar Tala/STKI)
     */
    public static function getStopwords(): array
    {
        if (self::$stopwords !== null) {
            return self::$stopwords;
        }

        $list = [
            'ada', 'adalah', 'adanya', 'adapun', 'agak', 'agaknya', 'agar', 'akan', 'akankah', 'akhir',
            'akhiri', 'akhirnya', 'aku', 'akulah', 'amat', 'amatlah', 'anda', 'andalah', 'antar',
            'antara', 'antaranya', 'apa', 'apaan', 'apabila', 'apakah', 'apalagi', 'apatah', 'artinya',
            'asal', 'asalkan', 'atas', 'atau', 'ataukah', 'ataupun', 'awal', 'awalnya', 'bagai',
            'bagaikan', 'bagaimana', 'bagaimanakah', 'bagaimanapun', 'bagi', 'bagian', 'bahkan',
            'bahwa', 'bahwasanya', 'baik', 'bakal', 'bakalan', 'balik', 'banyak', 'bapak', 'barangkali',
            'baru', 'bawah', 'beberapa', 'begini', 'beginian', 'beginikah', 'beginilah', 'begitu',
            'begitukah', 'begitulah', 'begitupun', 'bekerja', 'belakang', 'belakangan', 'belum',
            'belumlah', 'benar', 'benarkah', 'benarlah', 'berada', 'berakhir', 'berakhirlah',
            'berakhirnya', 'berapa', 'berapakah', 'berapalah', 'berapapun', 'berarti', 'berawal',
            'berbagai', 'berdatangan', 'beri', 'berikan', 'berikut', 'berikutnya', 'berjumlah',
            'berkali-kali', 'berkata', 'berkehendak', 'berkeinginan', 'berkenaan', 'berlainan',
            'berlalu', 'berlangsung', 'berlebihan', 'bermacam', 'bermacam-macam', 'bermaksud',
            'bermula', 'bersama', 'bersama-sama', 'bersiap', 'bersiap-siap', 'bertanya', 'bertanya-tanya',
            'berturut', 'berturut-turut', 'bertutur', 'berujar', 'berupa', 'besar', 'betul', 'betulkah',
            'biasa', 'biasanya', 'bila', 'bilakah', 'bisa', 'bisakah', 'boleh', 'bolehkah', 'bolehlah',
            'buat', 'bukan', 'bukankah', 'bukanlah', 'bukannya', 'cuma', 'cukup', 'cukupkah', 'cukuplah',
            'dan', 'dapat', 'dari', 'daripada', 'dekat', 'demi', 'demikian', 'demikianlah', 'dengan',
            'depan', 'di', 'dia', 'diakhiri', 'diakhirinya', 'dialah', 'diantara', 'diantaranya',
            'diberi', 'diberikan', 'diberikannya', 'dibuat', 'dibuatnya', 'didapat', 'didatangkan',
            'digunakan', 'diibaratkan', 'diibaratkannya', 'diingat', 'diingatkan', 'diinginkan',
            'dijawab', 'dijelaskan', 'dijelaskannya', 'dikarenakan', 'dikatakan', 'dikatakannya',
            'dikerjakan', 'diketahui', 'diketahuinya', 'dikira', 'dilakukan', 'dilalui', 'dilihat',
            'dimaksud', 'dimaksudkan', 'dimaksudkannya', 'dimaksudnya', 'diminta', 'dimintai',
            'dimisalkan', 'dimulai', 'dimulailah', 'dimulainya', 'dimungkinkan', 'dini', 'dipastikan',
            'diperbuat', 'diperbuatnya', 'dipergunakan', 'diperkirakan', 'diperlihatkan', 'diperlukan',
            'diperlukannya', 'dipersoalkan', 'dipertanyakan', 'dipunyai', 'diri', 'dirinya', 'disampaikan',
            'disebut', 'disebutkan', 'disebutkannya', 'disini', 'disinilah', 'disitulah', 'ditambahkan',
            'ditandaskan', 'ditanya', 'ditanyai', 'ditanyakan', 'ditegaskan', 'ditemukan', 'ditentukan',
            'ditentukannya', 'diterima', 'diterimanya', 'diterangkan', 'diterangkannya', 'diteruskan',
            'ditiadakan', 'ditujukan', 'ditunjuk', 'ditunjuki', 'ditunjukkan', 'ditunjukkannya',
            'ditunjuknya', 'dituturkan', 'dituturkannya', 'diucapkan', 'diucapkannya', 'diungkapkan',
            'hampir', 'hanya', 'hanyalah', 'harus', 'haruslah', 'harusnya', 'ia', 'ialah', 'ibarat',
            'ibaratkan', 'ibaratnya', 'ibu', 'ikut', 'ingat', 'ingat-ingat', 'ingin', 'inginkah',
            'inginkan', 'ini', 'inikah', 'inilah', 'itu', 'itukah', 'itulah', 'jadi', 'jadilah',
            'jadinya', 'jangan', 'jangankan', 'janganlah', 'jauh', 'jawab', 'jawaban', 'jawabnya',
            'jelas', 'jelaskan', 'jelaslah', 'jelasnya', 'jika', 'jikalau', 'juga', 'jumlah',
            'jumlahnya', 'justru', 'kala', 'kalau', 'kalaulah', 'kalaupun', 'kalian', 'kami', 'kamilah',
            'kamu', 'kamulah', 'kan', 'kapan', 'kapankah', 'kapanpun', 'karena', 'karenanya', 'kasus',
            'kata', 'katakan', 'katakanlah', 'katanya', 'ke', 'keadaan', 'kebetulan', 'kecil', 'kedua',
            'keduanya', 'keinginan', 'kelamaan', 'kelihatan', 'kelihatannya', 'kelima', 'keluar',
            'kembali', 'kemudian', 'kemungkinan', 'kemungkinannya', 'kenapa', 'kepada', 'kepadanya',
            'kesampaian', 'keseluruhan', 'keseluruhannya', 'keterlaluan', 'ketika', 'khususnya', 'kini',
            'kinilah', 'kira', 'kira-kira', 'kiranya', 'kita', 'kitalah', 'kurang', 'lagi', 'lagian',
            'lah', 'lain', 'lainnya', 'lalu', 'lama', 'lamanya', 'lanjut', 'lanjutnya', 'lebih',
            'lewat', 'macam', 'maka', 'makanya', 'makin', 'malah', 'malahan', 'mampu', 'mampukah',
            'mana', 'manakala', 'manalagi', 'masing', 'masing-masing', 'masuk', 'mata', 'mau', 'maupun',
            'melainkan', 'melakukan', 'melalui', 'melihat', 'melihatnya', 'memang', 'memastikan',
            'memberi', 'memberikan', 'membuat', 'memerlukan', 'memihak', 'meminta', 'memintakan',
            'memisalkan', 'memperbuat', 'mempergunakan', 'memperkirakan', 'memperlihatkan',
            'mempersiapkan', 'mempersoalkan', 'mempertanyakan', 'mempunyai', 'memulai', 'memungkinkan',
            'menaiki', 'menandaskan', 'menanti', 'menanti-nanti', 'menantikan', 'menanya', 'menanyai',
            'menanyakan', 'mendapat', 'mendapatkan', 'mendatang', 'mendatangi', 'mendatangkan',
            'menegaskan', 'mengakhiri', 'mengapa', 'mengatakan', 'mengatakannya', 'mengenai',
            'mengerjakan', 'mengetahui', 'menggunakan', 'menghendaki', 'mengibaratkan',
            'mengibaratkannya', 'mengingat', 'mengingatkan', 'menginginkan', 'mengira', 'mengucapkan',
            'mengucapkannya', 'mengungkapkan', 'menjadi', 'menjawab', 'menjelaskan', 'menuju',
            'menunjuk', 'menunjuki', 'menunjukkan', 'menunjuknya', 'menurut', 'menurutnya',
            'menuturkan', 'menyampaikan', 'menyangkut', 'menyatakan', 'menyebutkan', 'menyeluruh',
            'menyiapkan', 'merasa', 'mereka', 'merekalah', 'merupakan', 'meski', 'meskipun', 'meyakini',
            'meyakinkan', 'minta', 'mirip', 'misal', 'misalkan', 'misalnya', 'mula', 'mulai', 'mulailah',
            'mulanya', 'mungkin', 'mungkinkah', 'nah', 'naik', 'namun', 'nanti', 'nantinya', 'nyaris',
            'nyatanya', 'oleh', 'olehnya', 'pada', 'padahal', 'padanya', 'pak', 'paling', 'panjang',
            'pantas', 'para', 'pasti', 'pastilah', 'penting', 'pentingnya', 'per', 'para', 'pernah',
            'pula', 'pun', 'punya', 'rasa', 'rasanya', 'saat', 'saatnya', 'saja', 'sajalah', 'saling',
            'sama', 'sama-sama', 'sambil', 'sampai', 'sampai-sampai', 'sana', 'sangat', 'sangatlah',
            'satu', 'saya', 'sayalah', 'sebaliknya', 'sebanyak', 'sebegini', 'sebegitu', 'sebelum',
            'sebelumnya', 'sebenarnya', 'seberapa', 'sebesar', 'sebetulnya', 'sebisanya', 'sebuah',
            'sebut', 'sebutlah', 'sebutnya', 'secara', 'secukupnya', 'sedang', 'sedangkan', 'sedemikian',
            'sedikit', 'sedikitnya', 'seenaknya', 'segala', 'segalanya', 'segera', 'seharusnya',
            'sehingga', 'seingat', 'sejak', 'sejauh', 'sejenak', 'sejumlah', 'sekadar', 'sekadarnya',
            'sekali', 'sekalian', 'sekaligus', 'sekalipun', 'sekarang', 'sekaranglah', 'sekecil',
            'seketika', 'sekiranya', 'sekitar', 'sekitarnya', 'sela', 'selain', 'selalu', 'selama',
            'selama-lamanya', 'selamanya', 'selanjutnya', 'seluruh', 'seluruhnya', 'semacam', 'semakin',
            'semampu', 'semampunya', 'semasa', 'semasih', 'semata', 'semata-mata', 'semaunya',
            'sementara', 'semisal', 'semisalnya', 'sempat', 'semua', 'semuanya', 'semula', 'sendiri',
            'sendirinya', 'seolah', 'seolah-olah', 'seorang', 'sepanjang', 'sepantasnya', 'sepantasnyalah',
            'seperlunya', 'seperti', 'sepertinya', 'sepihak', 'sering', 'seringnya', 'serta', 'serupa',
            'sesaat', 'sesama', 'sesampai', 'sesegera', 'sesekali', 'seseorang', 'sesuatu', 'sesuatunya',
            'sesudah', 'sesudahnya', 'setelah', 'setempat', 'setengah', 'seterusnya', 'setiap',
            'setiba', 'setibanya', 'setidak-tidaknya', 'setidaknya', 'setinggi', 'seusai', 'sewaktu',
            'siap', 'siapa', 'siapakah', 'siapapun', 'sini', 'sinilah', 'suatu', 'sudah', 'sudahkah',
            'sudahlah', 'supaya', 'tadi', 'tadinya', 'tahu', 'tahun', 'tak', 'tambah', 'tambahnya',
            'tampak', 'tampaknya', 'tandas', 'tandasnya', 'tanpa', 'tanya', 'tanyakan', 'tanyanya',
            'tapi', 'tegas', 'tegasnya', 'telah', 'tempat', 'tengah', 'tentang', 'tentu', 'tentulah',
            'tentunya', 'tepat', 'terakhir', 'terasa', 'terbanyak', 'terdahulu', 'terdapat', 'terdiri',
            'terhadap', 'terhadapnya', 'teringat', 'teringat-ingat', 'terjadi', 'terjadilah',
            'terjadinya', 'terkira', 'terlalu', 'terlebih', 'terlihat', 'termasuk', 'ternyata',
            'tersampaikan', 'tersebut', 'tersebutlah', 'tertentu', 'tertuju', 'terus', 'terutama',
            'tetap', 'tetapi', 'tiap', 'tiba', 'tiba-tiba', 'tidak', 'tidakkah', 'tidaklah', 'tiga',
            'tinggi', 'toh', 'tunjuk', 'turut', 'tutur', 'tuturnya', 'ucap', 'ucapnya', 'ujar',
            'ujarnya', 'umum', 'umumnya', 'ungkap', 'ungkapnya', 'untuk', 'wah', 'wahai', 'waktu',
            'waktunya', 'walau', 'walaupun', 'wong', 'yaitu', 'yakin', 'yakni', 'yang'
        ];

        self::$stopwords = array_fill_keys($list, true);
        return self::$stopwords;
    }

    /**
     * Kamus Kata Dasar Acuan Bahasa Indonesia untuk Algoritma Stemming
     * Dimuat dari file data/kata_dasar.txt
     */
    public static function getRootWords(): array
    {
        if (self::$rootWords !== null) {
            return self::$rootWords;
        }

        $candidates = [
            __DIR__ . '/../data/kata-dasar.txt',
            __DIR__ . '/../data/kata_dasar.txt',
        ];
        foreach ($candidates as $filePath) {
            if (file_exists($filePath)) {
                $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines !== false && !empty($lines)) {
                    $cleaned = array_map('trim', $lines);
                    self::$rootWords = array_fill_keys($cleaned, true);
                    return self::$rootWords;
                }
            }
        }

        // Fallback cadangan
        $roots = [
            'ajar', 'jaring', 'komputer', 'basis', 'data', 'nirkabel', 'temu', 'kembali',
            'informasi', 'sistem', 'hitung', 'bobot', 'ranking', 'pustaka', 'tahu', 'latih',
            'kembang', 'cerdas', 'buat', 'mesin', 'proses', 'bahasa', 'alami', 'olah', 'tabel',
            'indeks', 'koneksi', 'hubung', 'aman', 'lindung', 'curi', 'periksa', 'uji', 'kaji',
            'teliti', 'sekolah', 'guru', 'murid', 'didik', 'modul', 'paham', 'ajar', 'buku',
            'baca', 'tulis', 'karya', 'kelompok', 'lapor', 'kaji', 'obat', 'sembuh', 'sakit',
            'rawat', 'sehat', 'dokter', 'imun', 'organ', 'mental', 'tumbuh', 'atur', 'hukum',
            'langgar', 'cipta', 'denda', 'transaksi', 'bisnis', 'pasar', 'uang', 'saham',
            'modal', 'investasi', 'ekonomi', 'untung', 'tingkat', 'nilai', 'atur', 'pandang',
            'jalan', 'gerak', 'bangun', 'bantu', 'pakai', 'guna', 'hasil', 'ambil', 'kumpul',
            'simpan', 'tata', 'kelola', 'pimpin', 'cipta', 'arah', 'tuju', 'cari', 'temu',
            'buka', 'tutup', 'tambah', 'kurang', 'bagi', 'kali', 'kirim', 'terima', 'rupa',
            'milik', 'sedia', 'bentuk', 'dasar', 'utama', 'khusus', 'umum', 'cepat', 'tepat',
            'stabil', 'akurat', 'presisi', 'luas', 'sempit', 'mudah', 'sukar', 'sulit', 'jelas'
        ];

        self::$rootWords = array_fill_keys($roots, true);
        return self::$rootWords;
    }

    /**
     * Tahap 1: Case Folding (Konversi ke huruf kecil)
     */
    public static function caseFolding(?string $text): string
    {
        return mb_strtolower($text ?? '', 'UTF-8');
    }

    /**
     * Tahap 2: Cleaning (Pembersihan URL, tag HTML, angka, tanda baca, simbol, spasi ganda)
     */
    public static function cleaning(string $text): string
    {
        // Hapus URL
        $text = preg_replace('~https?://\S+|www\.\S+~i', ' ', $text);
        // Hapus Tag HTML
        $text = preg_replace('~<[^>]+>~', ' ', $text);
        // Hapus Angka dan Karakter Non-Alfabet
        $text = preg_replace('~[^a-zA-Z\s]~', ' ', $text);
        // Normalisasi Spasi Berlebih
        $text = preg_replace('~\s+~', ' ', $text);
        return trim($text);
    }

    /**
     * Tahap 3: Tokenizing (Memecah teks menjadi array token kata)
     */
    public static function tokenize(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }
        return preg_split('~\s+~', trim($text));
    }

    /**
     * Tahap 4: Stopword Removal (Menghapus stopword bahasa Indonesia dan token < 2 huruf)
     */
    public static function removeStopwords(array $tokens): array
    {
        $stopwords = self::getStopwords();
        $filtered = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if (strlen($token) >= 2 && !isset($stopwords[$token])) {
                $filtered[] = $token;
            }
        }
        return $filtered;
    }

    /**
     * Menghasilkan kandidat kata jika akhiran derivasi (-kan, -an, -i) dipotong
     */
    private static function stripSuffix(string $w): array
    {
        $cands = [];
        $len = strlen($w);
        if ($len <= 3) {
            return [];
        }

        // -kan (bisa dari -kan atau kata berakhiran 'k' + an)
        if (str_ends_with($w, 'kan')) {
            $cands[] = substr($w, 0, -3); // e.g. kirimkan -> kirim
            $cands[] = substr($w, 0, -2); // e.g. masukan -> masuk
        }
        // -an
        if (str_ends_with($w, 'an')) {
            $cands[] = substr($w, 0, -2); // e.g. makanan -> makan, pengambilan -> pengambil
        }
        // -i
        if (str_ends_with($w, 'i')) {
            $cands[] = substr($w, 0, -1); // e.g. memahami -> memaham, mengawasi -> mengawas
        }

        $valid = [];
        foreach ($cands as $c) {
            $c = trim($c);
            if (strlen($c) >= 2 && !isset($valid[$c])) {
                $valid[$c] = true;
            }
        }
        return array_keys($valid);
    }

    /**
     * Menghasilkan kandidat kata dasar berdasarkan aturan awalan (Prefix Disambiguation Rules) Nazief-Adriani
     */
    private static function stripPrefix(string $w): array
    {
        $cands = [];
        $len = strlen($w);
        if ($len < 4) {
            return [];
        }

        // 1. memper-
        if (str_starts_with($w, 'memper')) {
            $cands[] = substr($w, 6); // memperjelas -> jelas
            $cands[] = substr($w, 3); // memperjelas -> perjelas
        }

        // 2. meng- / peng-
        if (preg_match('/^(meng|peng)(.+)$/', $w, $m)) {
            $rest = $m[2];
            $firstChar = $rest[0];
            if (in_array($firstChar, ['a', 'i', 'u', 'e', 'o'])) {
                if ($rest === 'ira') {
                    $cands[] = 'kira';
                    $cands[] = 'ira';
                } else {
                    $cands[] = $rest;            // mengalir -> alir, pengawas -> awas, mengambil -> ambil
                    $cands[] = 'k' . $rest;      // mengupas -> kupas, mengarang -> karang, mengunci -> kunci
                }
            }
            if (in_array($firstChar, ['g', 'h', 'k', 'q'])) {
                $cands[] = $rest;            // menggali -> gali, penghubung -> hubung
            }
            if (in_array($firstChar, ['l', 'r'])) {
                $cands[] = $rest;            // penglihatan -> lihat, pengrusak -> rusak
            }
        }

        // 3. menge- / penge- (1 suku kata)
        if (preg_match('/^(menge|penge)(.+)$/', $w, $m)) {
            $cands[] = $m[2]; // mengecat -> cat, mengebom -> bom
        }

        // 4. meny- / peny- (Peluluhan s)
        if (preg_match('/^(meny|peny)(.+)$/', $w, $m)) {
            $rest = $m[2];
            $cands[] = 's' . $rest;          // menyapu -> sapu, penyusunan -> susun
            $cands[] = $rest;
            $cands[] = 'ny' . $rest;         // menyanyi -> nyanyi
        }

        // 5. mem- / pem- (Konsonan b, f, v, p dan peluluhan p)
        if (preg_match('/^(mem|pem)(.+)$/', $w, $m)) {
            $rest = $m[2];
            $firstChar = $rest[0];
            if (in_array($firstChar, ['a', 'i', 'u', 'e', 'o'])) {
                $cands[] = 'p' . $rest;      // memahami -> paham, memilih -> pilih, memakai -> pakai
                $cands[] = $rest;
            }
            if (in_array($firstChar, ['b', 'f', 'p', 'v'])) {
                $cands[] = $rest;            // membaca -> baca, pembeli -> beli, mempunyai -> punya
            }
        }

        // 6. men- / pen- (Konsonan c, d, j, s, z dan peluluhan t)
        if (preg_match('/^(men|pen)(.+)$/', $w, $m)) {
            $rest = $m[2];
            $firstChar = $rest[0];
            if (in_array($firstChar, ['a', 'i', 'u', 'e', 'o'])) {
                $cands[] = 't' . $rest;      // menulis -> tulis, penolong -> tolong
                $cands[] = $rest;
            }
            if (in_array($firstChar, ['c', 'd', 'j', 's', 'z'])) {
                $cands[] = $rest;            // mencari -> cari, pendidik -> didik
            }
        }

        // 7. per- / pel-
        if (str_starts_with($w, 'per')) {
            $cands[] = substr($w, 3);        // perjanjian -> janji, perkembang -> kembang
        }
        if (str_starts_with($w, 'pel')) {
            $cands[] = substr($w, 3);        // pelopor -> opor
        }

        // 8. ber- / be-
        if (str_starts_with($w, 'ber')) {
            $rest = substr($w, 3);
            $cands[] = $rest;                // berjalan -> jalan
            if (isset($rest[0]) && in_array($rest[0], ['a', 'i', 'u', 'e', 'o'])) {
                $cands[] = 'r' . $rest;      // beragam -> ragam
            }
        }
        if (preg_match('/^be([bcdfghjklmnpqrstvwxyz])er(.+)$/', $w, $m)) {
            $cands[] = $m[1] . 'er' . $m[2]; // bekerja -> kerja
        }
        if (str_starts_with($w, 'be')) {
            $cands[] = substr($w, 2);
        }

        // 9. ter- / te-
        if (str_starts_with($w, 'ter')) {
            $rest = substr($w, 3);
            $cands[] = $rest;                // terbaca -> baca
            if (isset($rest[0]) && in_array($rest[0], ['a', 'i', 'u', 'e', 'o'])) {
                $cands[] = 'r' . $rest;      // terasa -> rasa
            }
        }
        if (preg_match('/^te([bcdfghjklmnpqrstvwxyz])er(.+)$/', $w, $m)) {
            $cands[] = $m[1] . 'er' . $m[2]; // tepercaya -> percaya
        }
        if (str_starts_with($w, 'te')) {
            $cands[] = substr($w, 2);
        }

        // 10. me- / pe- (Untuk konsonan l, m, n, r, w, y)
        if (preg_match('/^(me|pe)([lmnrwy])(.+)$/', $w, $m)) {
            $cands[] = $m[2] . $m[3];        // melihat -> lihat, merawat -> rawat, memasak -> masak
        }
        if (str_starts_with($w, 'me')) {
            $cands[] = substr($w, 2);
        }
        if (str_starts_with($w, 'pe')) {
            $cands[] = substr($w, 2);
        }

        // 11. di-, ke-, se-
        if (str_starts_with($w, 'di')) {
            $cands[] = substr($w, 2);        // dikirim -> kirim, dibuat -> buat
        }
        if (str_starts_with($w, 'ke')) {
            $cands[] = substr($w, 2);        // ketua -> tua, kekasih -> kasih
        }
        if (str_starts_with($w, 'se')) {
            $cands[] = substr($w, 2);        // serupa -> rupa, sebuah -> buah
        }

        $valid = [];
        foreach ($cands as $c) {
            $c = trim($c);
            if (strlen($c) >= 2 && !isset($valid[$c])) {
                $valid[$c] = true;
            }
        }
        return array_keys($valid);
    }

    /**
     * Tahap 5: Stemming Kata Bahasa Indonesia (Nazief & Adriani Rule-Based Stemmer)
     */
    public static function stemWord(string $word): string
    {
        $word = trim(mb_strtolower($word, 'UTF-8'));
        if (strlen($word) < 4) {
            return $word;
        }

        if (isset(self::$stemCache[$word])) {
            return self::$stemCache[$word];
        }

        $roots = self::getRootWords();

        // 0. Cek langsung kata dasar di kamus
        if (isset($roots[$word])) {
            return self::$stemCache[$word] = $word;
        }

        $original = $word;

        // 1. Partikel Infleksional (-lah, -kah, -pun, -tah)
        $wNoParticle = preg_replace('/(lah|kah|pun|tah)$/', '', $word);
        if ($wNoParticle !== $word) {
            if (isset($roots[$wNoParticle])) {
                return self::$stemCache[$original] = $wNoParticle;
            }
            $word = $wNoParticle;
        }

        // 2. Pronoun Kepunyaan (-ku, -mu, -nya)
        $wNoPronoun = preg_replace('/(ku|mu|nya)$/', '', $word);
        if ($wNoPronoun !== $word) {
            if (isset($roots[$wNoPronoun])) {
                return self::$stemCache[$original] = $wNoPronoun;
            }
            $word = $wNoPronoun;
        }

        // 3. Kasus khusus: belajar, pelajar, pembelajaran
        if (preg_match('/^(belajar|pelajar|pembelajaran)/', $word)) {
            if (isset($roots['ajar'])) {
                return self::$stemCache[$original] = 'ajar';
            }
        }

        $sufCands = self::stripSuffix($word);
        $prefCands = self::stripPrefix($word);

        // 4. Jika pola nominal konfiks (peN-...-an, ke-...-an, per-...-an), dahulukan konfiks
        // Contoh: "pembelian" -> "beli", "pengambilan" -> "ambil", "keterbukaan" -> "buka"
        $isNominalConfix = (bool)preg_match('/^(pe|pem|pen|peng|peny|per|ke).+an$/', $word);
        if ($isNominalConfix) {
            foreach ($sufCands as $sCand) {
                $pCands = self::stripPrefix($sCand);
                foreach ($pCands as $cand) {
                    if (isset($roots[$cand])) {
                        return self::$stemCache[$original] = $cand;
                    }
                }
            }
        }

        // 5. Cek prefiks saja (e.g. "memakai" -> "pakai", "berjalan" -> "jalan", "membaca" -> "baca")
        foreach ($prefCands as $cand) {
            if (isset($roots[$cand])) {
                return self::$stemCache[$original] = $cand;
            }
        }

        // 6. Coba konfiks umum (e.g. "memahami" -> cut '-i' = "memaham" -> "paham", "mengurangi" -> "kurang")
        foreach ($sufCands as $sCand) {
            $pCands = self::stripPrefix($sCand);
            foreach ($pCands as $cand) {
                if (isset($roots[$cand])) {
                    return self::$stemCache[$original] = $cand;
                }
            }
        }

        // 7. Konfiks sebaliknya: Prefiks LALU Sufiks
        foreach ($prefCands as $pCand) {
            $sCands = self::stripSuffix($pCand);
            foreach ($sCands as $cand) {
                if (isset($roots[$cand])) {
                    return self::$stemCache[$original] = $cand;
                }
            }
        }

        // 8. Multi-Prefix + Sufiks (e.g. "memperbaiki" -> baik, "pembelajaran" -> ajar)
        foreach ($prefCands as $pCand) {
            $subP = self::stripPrefix($pCand);
            foreach ($subP as $subCand) {
                if (isset($roots[$subCand])) {
                    return self::$stemCache[$original] = $subCand;
                }
                $subS = self::stripSuffix($subCand);
                foreach ($subS as $sc) {
                    if (isset($roots[$sc])) {
                        return self::$stemCache[$original] = $sc;
                    }
                }
            }
        }

        // 9. Cek sufiks saja (e.g. "makanan" -> "makan", "lukisan" -> "lukis")
        foreach ($sufCands as $cand) {
            if (isset($roots[$cand])) {
                return self::$stemCache[$original] = $cand;
            }
        }

        // 10. Fallback jika kata tidak ada di kamus kata dasar (istilah serapan/asing/baru)
        if (!empty($sufCands)) {
            foreach ($sufCands as $sc) {
                $pc = self::stripPrefix($sc);
                if (!empty($pc) && strlen($pc[0]) >= 3) {
                    return self::$stemCache[$original] = $pc[0];
                }
            }
        }
        if (!empty($prefCands) && strlen($prefCands[0]) >= 3) {
            return self::$stemCache[$original] = $prefCands[0];
        }

        return self::$stemCache[$original] = $word;
    }

    /**
     * Stemming untuk seluruh array token
     */
    public static function stem(array $tokens): array
    {
        $stemmed = [];
        foreach ($tokens as $t) {
            $s = self::stemWord($t);
            if (strlen($s) >= 2) {
                $stemmed[] = $s;
            }
        }
        return $stemmed;
    }

    /**
     * Pipeline lengkap preprocessing (sama persis untuk dokumen & kueri)
     */
    public static function preprocess(?string $text): array
    {
        if (!$text) {
            return [];
        }
        $folded = self::caseFolding($text);
        $cleaned = self::cleaning($folded);
        $tokens = self::tokenize($cleaned);
        $noStop = self::removeStopwords($tokens);
        return self::stem($noStop);
    }

    /**
     * Mengembalikan rincian setiap tahap (untuk tampilan demo & laporan)
     */
    public static function preprocessSteps(?string $text): array
    {
        $original = $text ?? '';
        $folded = self::caseFolding($original);
        $cleaned = self::cleaning($folded);
        $tokens = self::tokenize($cleaned);
        $noStop = self::removeStopwords($tokens);
        $stemmed = [];
        $stemPairs = [];

        foreach ($noStop as $w) {
            $s = self::stemWord($w);
            if (strlen($s) >= 2) {
                $stemmed[] = $s;
            }
            $stemPairs[] = [
                'from' => $w,
                'to' => $s,
                'changed' => ($w !== $s)
            ];
        }

        return [
            'original' => $original,
            'case_folding' => $folded,
            'cleaning' => $cleaned,
            'tokens' => $tokens,
            'stopword_removed' => $noStop,
            'stemmed' => $stemmed,
            'stem_pairs' => $stemPairs,
        ];
    }
}
