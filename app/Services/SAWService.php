<?php

namespace App\Services;

use DomainException;

/**
 * Seluruh proses algoritma Simple Additive Weighting (SAW).
 *
 * Kelas ini murni logika: tidak mengakses database, session, atau request.
 *
 * Rumus:
 *   Benefit : r(i,j) = x(i,j) / max(x(j))
 *   Cost    : r(i,j) = min(x(j)) / x(i,j)
 *   V(i)    = jumlah( w(j) * r(i,j) ),  w dalam proporsi 0..1
 *
 * Presisi: r, kontribusi w*r, dan V dibulatkan ke PRECISION desimal. V adalah jumlah
 * kontribusi yang sudah dibulatkan, sehingga angka pada setiap tabel menjumlah persis.
 *
 * Format input calculate():
 *   $alternatives : [ ['id' => 1, 'name' => 'Laptop A', 'values' => ['C1' => 8000000, 'C2' => 9500, ...]], ... ]
 *   $criteria     : [ ['code' => 'C1', 'name' => 'Harga', 'type' => 'cost', 'unit' => 'Rp'], ... ]
 *   $weights      : [ 'C1' => 30, 'C2' => 25, ... ]   (persen)
 */
class SAWService
{
    public const METHOD           = 'SAW';
    public const PRECISION        = 8;
    public const MIN_ALTERNATIVES = 2;
    public const WEIGHT_UNITS     = 10000; // 100,00% dalam satuan 0,01%

    public const TYPE_BENEFIT = 'benefit';
    public const TYPE_COST    = 'cost';

    /** Penjelasan aturan peringkat dan tie-break (untuk ditampilkan di hasil/laporan). */
    public function tieBreakRule(): array
    {
        return [
            'Alternatif diurutkan dari nilai preferensi (V) terbesar. Nilai dibandingkan pada ketelitian ' . self::PRECISION . ' angka desimal.',
            'Jika nilai V sama, bandingkan nilai ternormalisasi (r) pada kriteria dengan bobot terbesar; nilai r yang lebih besar menang. Jika bobot sama, kriteria dengan urutan lebih awal didahulukan. Kriteria berbobot 0 dilewati.',
            'Jika masih sama, alternatif diurutkan berdasarkan nama secara abjad (tidak peka huruf besar/kecil).',
            'Jika masih sama, alternatif dengan ID lebih kecil didahulukan.',
            'Setiap alternatif selalu mendapat peringkat unik (1, 2, 3, ...). Alternatif dengan nilai V yang sama ditandai sebagai seri.',
        ];
    }

    /**
     * Jalankan seluruh proses SAW.
     *
     * @throws SAWValidationException bila input tidak valid
     */
    public function calculate(array $alternatives, array $criteria, array $weights): array
    {
        $errors = $this->validateInput($alternatives, $criteria, $weights);
        if ($errors !== []) {
            throw new SAWValidationException($errors);
        }

        $alternatives = array_values($alternatives);
        $criteria     = array_values($criteria);

        $matrix      = $this->buildDecisionMatrix($alternatives, $criteria);
        $reference   = $this->referenceValues($matrix, $criteria);
        $normalized  = $this->normalizeMatrix($matrix, $criteria);
        $proportions = $this->weightProportions($criteria, $weights);
        $preference  = $this->calculatePreference($normalized, $criteria, $proportions);
        $ranking     = $this->rankAlternatives($preference['preferences'], $alternatives, $normalized, $criteria, $proportions);

        $criteriaOut = [];
        $totalWeight = 0.0;
        foreach ($criteria as $criterion) {
            $code         = (string) $criterion['code'];
            $percent      = round($proportions[$code] * 100, 2);
            $totalWeight += $percent;

            $criteriaOut[] = [
                'code'              => $code,
                'name'              => (string) ($criterion['name'] ?? $code),
                'type'              => $criterion['type'],
                'unit'              => $criterion['unit'] ?? null,
                'weight_percent'    => $percent,
                'weight_normalized' => $proportions[$code],
            ];
        }

        $alternativesOut = [];
        foreach ($alternatives as $alt) {
            $alternativesOut[] = ['key' => $alt['id'], 'name' => (string) $alt['name']];
        }

        return [
            'method'               => self::METHOD,
            'precision'            => self::PRECISION,
            'total_weight_percent' => round($totalWeight, 2),
            'criteria'             => $criteriaOut,
            'alternatives'         => $alternativesOut,
            'decision_matrix'      => $matrix,
            'reference'            => $reference,
            'normalized_matrix'    => $normalized,
            'weighted_matrix'      => $preference['weighted'],
            'preferences'          => $preference['preferences'],
            'ranking'              => $ranking,
            'best'                 => $ranking[0],
            'tie_break_rule'       => $this->tieBreakRule(),
        ];
    }

    /**
     * Validasi seluruh input. Mengembalikan daftar pesan error (kosong = valid).
     *
     * @return list<string>
     */
    public function validateInput(array $alternatives, array $criteria, array $weights): array
    {
        $errors   = [];
        $criteria = array_values($criteria);
        $types    = []; // kode kriteria => jenis

        // --- Kriteria ---
        if ($criteria === []) {
            $errors[] = 'Minimal harus ada satu kriteria.';
        }

        foreach ($criteria as $i => $criterion) {
            $code = is_array($criterion) ? (string) ($criterion['code'] ?? '') : '';

            if (trim($code) === '') {
                $errors[] = 'Kriteria ke-' . ($i + 1) . ' tidak memiliki kode.';
                continue;
            }
            if (array_key_exists($code, $types)) {
                $errors[] = "Kode kriteria {$code} duplikat.";
                continue;
            }

            $type = $criterion['type'] ?? null;
            if (! in_array($type, [self::TYPE_BENEFIT, self::TYPE_COST], true)) {
                $errors[] = "Jenis kriteria {$code} harus benefit atau cost.";
            }
            $types[$code] = $type;
        }

        // --- Alternatif dan nilainya ---
        $alternatives = array_values($alternatives);

        if (count($alternatives) < self::MIN_ALTERNATIVES) {
            $errors[] = 'Minimal harus ada ' . self::MIN_ALTERNATIVES . ' alternatif untuk dibandingkan.';
        }

        $seenIds    = [];
        $columns    = []; // kode => daftar nilai valid
        $badColumns = []; // kode => true bila ada nilai tidak valid

        foreach ($alternatives as $i => $alt) {
            if (! is_array($alt)) {
                $errors[] = 'Alternatif ke-' . ($i + 1) . ' tidak valid.';
                continue;
            }

            $id = $alt['id'] ?? null;
            if (! (is_int($id) || (is_string($id) && trim($id) !== ''))) {
                $errors[] = 'Alternatif ke-' . ($i + 1) . ' tidak memiliki ID yang valid.';
                continue;
            }
            if (isset($seenIds[(string) $id])) {
                $errors[] = "ID alternatif {$id} duplikat.";
                continue;
            }
            $seenIds[(string) $id] = true;

            $name = trim((string) ($alt['name'] ?? ''));
            if ($name === '') {
                $errors[] = 'Alternatif ke-' . ($i + 1) . ' tidak memiliki nama.';
            }
            $label  = $name !== '' ? $name : 'Alternatif ID ' . $id;
            $values = is_array($alt['values'] ?? null) ? $alt['values'] : [];

            foreach ($types as $code => $type) {
                $raw = $values[$code] ?? null;

                if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                    $errors[]           = "{$label}: nilai kriteria {$code} belum diisi.";
                    $badColumns[$code]  = true;
                    continue;
                }

                if (! is_numeric($raw) || ! is_finite((float) $raw)) {
                    $errors[]           = "{$label}: nilai kriteria {$code} harus berupa angka.";
                    $badColumns[$code]  = true;
                    continue;
                }

                $number = (float) $raw;

                if ($number < 0) {
                    $errors[]          = "{$label}: nilai kriteria {$code} tidak boleh negatif.";
                    $badColumns[$code] = true;
                } elseif ($type === self::TYPE_COST && $number <= 0.0) {
                    $errors[]          = "{$label}: nilai kriteria Cost {$code} harus lebih dari 0.";
                    $badColumns[$code] = true;
                } else {
                    $columns[$code][] = $number;
                }
            }
        }

        // Kriteria Benefit yang semua nilainya 0: max = 0, normalisasi menjadi 0/0.
        foreach ($types as $code => $type) {
            if (
                $type === self::TYPE_BENEFIT
                && ! isset($badColumns[$code])
                && isset($columns[$code])
                && max($columns[$code]) <= 0.0
            ) {
                $errors[] = "Semua alternatif bernilai 0 pada kriteria Benefit {$code}; normalisasi tidak dapat dihitung (pembagian dengan nol).";
            }
        }

        // --- Bobot (persen, dihitung dalam satuan 0,01%) ---
        $units         = 0;
        $weightProblem = false;

        foreach ($types as $code => $type) {
            if (! array_key_exists($code, $weights)) {
                $errors[]      = "Bobot kriteria {$code} belum diisi.";
                $weightProblem = true;
                continue;
            }

            $raw = $weights[$code];
            if (! is_numeric($raw) || ! is_finite((float) $raw)) {
                $errors[]      = "Bobot kriteria {$code} harus berupa angka.";
                $weightProblem = true;
                continue;
            }

            $percent = (float) $raw;
            if ($percent < 0 || $percent > 100) {
                $errors[]      = "Bobot kriteria {$code} harus antara 0 dan 100.";
                $weightProblem = true;
                continue;
            }

            $scaled = $percent * 100;
            if (abs($scaled - round($scaled)) > 1e-6) {
                $errors[]      = "Bobot kriteria {$code} maksimal 2 angka di belakang koma.";
                $weightProblem = true;
                continue;
            }

            $units += (int) round($scaled);
        }

        foreach (array_keys($weights) as $key) {
            if (! array_key_exists($key, $types)) {
                $errors[]      = "Bobot diberikan untuk kriteria yang tidak dikenal: {$key}.";
                $weightProblem = true;
            }
        }

        if (! $weightProblem && $units !== self::WEIGHT_UNITS) {
            $errors[] = 'Total bobot harus tepat 100%. Total saat ini: ' . number_format($units / 100, 2, ',', '.') . '%.';
        }

        return $errors;
    }

    /**
     * Matriks keputusan X: [id alternatif][kode kriteria] => nilai (float).
     * Mengasumsikan input sudah lolos validateInput().
     */
    public function buildDecisionMatrix(array $alternatives, array $criteria): array
    {
        $matrix = [];

        foreach ($alternatives as $alt) {
            $row = [];
            foreach ($criteria as $criterion) {
                $code       = (string) $criterion['code'];
                $row[$code] = (float) $alt['values'][$code];
            }
            $matrix[$alt['id']] = $row;
        }

        return $matrix;
    }

    /** Nilai acuan tiap kriteria: max untuk Benefit, min untuk Cost. */
    public function referenceValues(array $matrix, array $criteria): array
    {
        $reference = [];

        foreach ($criteria as $criterion) {
            $code   = (string) $criterion['code'];
            $column = [];
            foreach ($matrix as $row) {
                $column[] = (float) $row[$code];
            }

            $isCost            = $criterion['type'] === self::TYPE_COST;
            $reference[$code] = [
                'type'  => $criterion['type'],
                'value' => $isCost ? min($column) : max($column),
            ];
        }

        return $reference;
    }

    /**
     * Matriks ternormalisasi R.
     *
     * @throws DomainException bila terjadi pembagian dengan nol
     */
    public function normalizeMatrix(array $matrix, array $criteria): array
    {
        $reference  = $this->referenceValues($matrix, $criteria);
        $normalized = [];

        foreach ($matrix as $key => $row) {
            foreach ($criteria as $criterion) {
                $code = (string) $criterion['code'];
                $x    = (float) $row[$code];
                $ref  = (float) $reference[$code]['value'];

                if ($criterion['type'] === self::TYPE_COST) {
                    if ($x <= 0.0) {
                        throw new DomainException("Nilai kriteria Cost {$code} harus lebih dari 0.");
                    }
                    $r = $ref / $x;
                } else {
                    if ($ref <= 0.0) {
                        throw new DomainException("Nilai maksimum kriteria Benefit {$code} adalah 0; normalisasi tidak dapat dihitung.");
                    }
                    $r = $x / $ref;
                }

                $normalized[$key][$code] = round($r, self::PRECISION);
            }
        }

        return $normalized;
    }

    /**
     * Bobot persen menjadi proporsi 0..1: [kode] => float.
     * Mengasumsikan bobot sudah lolos validateInput().
     */
    public function weightProportions(array $criteria, array $weights): array
    {
        $proportions = [];

        foreach ($criteria as $criterion) {
            $code  = (string) $criterion['code'];
            $units = (int) round((float) $weights[$code] * 100);

            $proportions[$code] = (float) ($units / self::WEIGHT_UNITS);
        }

        return $proportions;
    }

    /**
     * Kontribusi tiap kriteria (w * r) dan nilai preferensi V = jumlah kontribusi.
     *
     * @return array{weighted: array, preferences: array}
     */
    public function calculatePreference(array $normalized, array $criteria, array $proportions): array
    {
        $weighted    = [];
        $preferences = [];

        foreach ($normalized as $key => $row) {
            $sum = 0.0;

            foreach ($criteria as $criterion) {
                $code                = (string) $criterion['code'];
                $contribution        = round($proportions[$code] * $row[$code], self::PRECISION);
                $weighted[$key][$code] = $contribution;
                $sum                += $contribution;
            }

            $preferences[$key] = round($sum, self::PRECISION);
        }

        return ['weighted' => $weighted, 'preferences' => $preferences];
    }

    /**
     * Urutkan alternatif dari V terbesar dengan aturan tie-break (lihat tieBreakRule()).
     *
     * @return list<array{key:int|string,name:string,rank:int,preference:float,tied:bool,tie_break:?string}>
     */
    public function rankAlternatives(array $preferences, array $alternatives, array $normalized, array $criteria, array $proportions): array
    {
        $order = $this->tieBreakCriteriaOrder($criteria, $proportions);
        $items = [];

        foreach ($alternatives as $alt) {
            $items[] = [
                'key'        => $alt['id'],
                'name'       => (string) $alt['name'],
                'rank'       => 0,
                'preference' => (float) $preferences[$alt['id']],
                'tied'       => false,
                'tie_break'  => null,
            ];
        }

        usort($items, fn(array $a, array $b): int => $this->compareAlternatives($a, $b, $order, $normalized)[0]);

        $count = count($items);
        for ($i = 0; $i < $count; $i++) {
            $items[$i]['rank'] = $i + 1;

            if ($i === 0) {
                continue;
            }

            [, $level] = $this->compareAlternatives($items[$i - 1], $items[$i], $order, $normalized);

            if ($level !== '') { // V sama dengan alternatif di atasnya
                $items[$i - 1]['tied'] = true;
                $items[$i]['tied']     = true;
                $items[$i]['tie_break'] = $this->describeTieBreak($level);
            }
        }

        return $items;
    }

    // ------------------------------------------------------------------

    /** Kode kriteria berbobot > 0, urut bobot terbesar dulu, lalu urutan kriteria. */
    private function tieBreakCriteriaOrder(array $criteria, array $proportions): array
    {
        $list = [];

        foreach ($criteria as $index => $criterion) {
            $code = (string) $criterion['code'];

            if (($proportions[$code] ?? 0.0) > 0.0) {
                $list[] = ['code' => $code, 'weight' => $proportions[$code], 'index' => $index];
            }
        }

        usort($list, static fn(array $a, array $b): int => [$b['weight'], $a['index']] <=> [$a['weight'], $b['index']]);

        return array_column($list, 'code');
    }

    /**
     * Bandingkan dua alternatif. Hasil negatif = $a lebih unggul.
     *
     * @return array{0:int,1:string} [hasil, tingkat penentu]; tingkat '' bila diputuskan oleh nilai V
     */
    private function compareAlternatives(array $a, array $b, array $order, array $normalized): array
    {
        $pa = $this->toUnits($a['preference']);
        $pb = $this->toUnits($b['preference']);
        if ($pa !== $pb) {
            return [$pb <=> $pa, ''];
        }

        foreach ($order as $code) {
            $ra = $this->toUnits($normalized[$a['key']][$code]);
            $rb = $this->toUnits($normalized[$b['key']][$code]);
            if ($ra !== $rb) {
                return [$rb <=> $ra, 'kriteria:' . $code];
            }
        }

        $na = mb_strtolower($a['name']);
        $nb = mb_strtolower($b['name']);
        if ($na !== $nb) {
            return [strcmp($na, $nb), 'nama'];
        }

        return [strnatcmp((string) $a['key'], (string) $b['key']), 'id'];
    }

    private function describeTieBreak(string $level): string
    {
        $base = 'Nilai preferensi sama dengan peringkat di atasnya; ';

        if (str_starts_with($level, 'kriteria:')) {
            return $base . 'dibedakan oleh nilai ternormalisasi pada kriteria ' . substr($level, 9) . '.';
        }
        if ($level === 'nama') {
            return $base . 'dibedakan menurut urutan abjad nama alternatif.';
        }

        return $base . 'dibedakan menurut urutan ID alternatif (ID lebih kecil didahulukan).';
    }

    /** Ubah nilai ke bilangan bulat pada skala PRECISION agar perbandingan bebas galat float. */
    private function toUnits(float $value): int
    {
        return (int) round($value * (10 ** self::PRECISION));
    }
}
