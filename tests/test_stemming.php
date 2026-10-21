<?php
require_once __DIR__ . '/../includes/Preprocessor.php';

$testWords = [
    'memahami'    => 'paham',
    'pemahaman'   => 'paham',
    'memilih'     => 'pilih',
    'pemilihan'   => 'pilih',
    'memakai'     => 'pakai',
    'pemakaian'   => 'pakai',
    'menulis'     => 'tulis',
    'penulisan'   => 'tulis',
    'menyapu'     => 'sapu',
    'penyusunan'  => 'susun',
    'mengira'     => 'kira',
    'membaca'     => 'baca',
    'pengambilan' => 'ambil',
    'pembelian'   => 'beli',
    'penglihatan' => 'lihat',
    'berjalan'    => 'jalan',
    'pembelajaran'=> 'ajar',
    'belajar'     => 'ajar',
    'dikirimkan'  => 'kirim',
    'terbaca'     => 'baca',
    'mengurangi'  => 'kurang',
    'memperbaiki' => 'baik',
    'keterbukaan' => 'buka',
    'perkembangan'=> 'kembang',
    'penghitungan'=> 'hitung',
    'menyimpan'   => 'simpan',
    'melihat'     => 'lihat',
];

echo "Testing Preprocessor::stemWord results:\n";
$allPass = true;
foreach ($testWords as $word => $expected) {
    $result = Preprocessor::stemWord($word);
    $status = ($result === $expected) ? "[OK]" : "[FAIL]";
    if ($result !== $expected) $allPass = false;
    echo sprintf("%-6s %-15s => got: %-12s expected: %s\n", $status, $word, $result, $expected);
}

if ($allPass) {
    echo "\n>>> ALL WORDS PASSED PERFECTLY ON PREPROCESSOR.PHP! <<<\n";
} else {
    echo "\n>>> SOME WORDS FAILED! <<<\n";
    exit(1);
}

echo "\n--- Uji Kalimat Sampel di fitur2_preprocess.php ---\n";
$sample = "Guru membagikan modul pembelajaran digital secara gratis di portal sekolah, agar seluruh muridnya memahami jaringan komputer!";
$res = Preprocessor::preprocessSteps($sample);
echo "Teks Asli: $sample\n";
echo "Hasil Stemmed: " . implode(', ', $res['stemmed']) . "\n";
assert(in_array('paham', $res['stemmed']), "'paham' must be in stemmed tokens!");
echo "PASS: Kata 'memahami' terbukti sukses di-stemming menjadi 'paham' di pipeline!\n";
