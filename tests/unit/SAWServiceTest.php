<?php

use App\Services\SAWService;
use App\Services\SAWValidationException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pengujian algoritma SAW. Tidak memerlukan database.
 *
 * Dataset utama (manualSet) dihitung tangan:
 *   Acuan   : C1 min 8.000.000 | C2 max 20.000 | C3 max 16 | C4 max 1.024 | C5 max 12
 *   r  A    : 1      ; 0,5   ; 0,5 ; 0,25 ; 0,66666667
 *   r  B    : 0,8    ; 0,75  ; 1   ; 0,5  ; 0,83333333
 *   r  C    : 0,64   ; 1     ; 1   ; 1    ; 1
 *   V  A = 0,62916667 | B = 0,78583333 | C = 0,892
 *
 * @internal
 */
final class SAWServiceTest extends CIUnitTestCase
{
    private const DELTA = 1e-9;

    private SAWService $saw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saw = new SAWService();
    }

    // =================================================================
    // Helper
    // =================================================================

    private function criteria(): array
    {
        return [
            ['code' => 'C1', 'name' => 'Harga',              'type' => 'cost',    'unit' => 'Rp'],
            ['code' => 'C2', 'name' => 'Performa prosesor',  'type' => 'benefit', 'unit' => 'poin'],
            ['code' => 'C3', 'name' => 'Kapasitas RAM',      'type' => 'benefit', 'unit' => 'GB'],
            ['code' => 'C4', 'name' => 'Kapasitas SSD',      'type' => 'benefit', 'unit' => 'GB'],
            ['code' => 'C5', 'name' => 'Daya tahan baterai', 'type' => 'benefit', 'unit' => 'jam'],
        ];
    }

    private function weights(): array
    {
        return ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 10];
    }

    private function alt(int|string $id, string $name, array $v): array
    {
        return [
            'id'     => $id,
            'name'   => $name,
            'values' => ['C1' => $v[0], 'C2' => $v[1], 'C3' => $v[2], 'C4' => $v[3], 'C5' => $v[4]],
        ];
    }

    private function manualSet(): array
    {
        return [
            $this->alt(1, 'Laptop A', [8000000, 10000, 8, 256, 8]),
            $this->alt(2, 'Laptop B', [10000000, 15000, 16, 512, 10]),
            $this->alt(3, 'Laptop C', [12500000, 20000, 16, 1024, 12]),
        ];
    }

    private function validate(array $alternatives, array $weights = null, array $criteria = null): array
    {
        return $this->saw->validateInput($alternatives, $criteria ?? $this->criteria(), $weights ?? $this->weights());
    }

    private function assertHasError(array $errors, string $needle): void
    {
        $found = false;
        foreach ($errors as $error) {
            if (str_contains($error, $needle)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Tidak ada pesan error yang memuat "' . $needle . '". Pesan: ' . implode(' | ', $errors));
    }

    private function twoCriteria(): array
    {
        return [
            ['code' => 'C1', 'name' => 'Biaya',  'type' => 'cost'],
            ['code' => 'C2', 'name' => 'Kinerja', 'type' => 'benefit'],
        ];
    }

    private function alt2(int|string $id, string $name, float|int $c1, float|int $c2): array
    {
        return ['id' => $id, 'name' => $name, 'values' => ['C1' => $c1, 'C2' => $c2]];
    }

    private function rankedKeys(array $result): array
    {
        return array_column($result['ranking'], 'key');
    }

    // =================================================================
    // Normalisasi
    // =================================================================

    public function testNormalisasiBenefit(): void
    {
        $matrix = $this->saw->buildDecisionMatrix($this->manualSet(), $this->criteria());
        $r      = $this->saw->normalizeMatrix($matrix, $this->criteria());

        // C2 (max 20000)
        $this->assertEqualsWithDelta(0.5, $r[1]['C2'], self::DELTA);
        $this->assertEqualsWithDelta(0.75, $r[2]['C2'], self::DELTA);
        $this->assertEqualsWithDelta(1.0, $r[3]['C2'], self::DELTA);
        // C3 (max 16)
        $this->assertEqualsWithDelta(0.5, $r[1]['C3'], self::DELTA);
        $this->assertEqualsWithDelta(1.0, $r[2]['C3'], self::DELTA);
        // C4 (max 1024)
        $this->assertEqualsWithDelta(0.25, $r[1]['C4'], self::DELTA);
        $this->assertEqualsWithDelta(0.5, $r[2]['C4'], self::DELTA);
        // C5 (max 12), dibulatkan 8 desimal
        $this->assertEqualsWithDelta(0.66666667, $r[1]['C5'], self::DELTA);
        $this->assertEqualsWithDelta(0.83333333, $r[2]['C5'], self::DELTA);
        $this->assertEqualsWithDelta(1.0, $r[3]['C5'], self::DELTA);
    }

    public function testNormalisasiCost(): void
    {
        $matrix = $this->saw->buildDecisionMatrix($this->manualSet(), $this->criteria());
        $r      = $this->saw->normalizeMatrix($matrix, $this->criteria());

        // C1 (min 8.000.000): min / x
        $this->assertEqualsWithDelta(1.0, $r[1]['C1'], self::DELTA);
        $this->assertEqualsWithDelta(0.8, $r[2]['C1'], self::DELTA);
        $this->assertEqualsWithDelta(0.64, $r[3]['C1'], self::DELTA);
    }

    // =================================================================
    // Validasi bobot
    // =================================================================

    public function testBobot100Diterima(): void
    {
        $this->assertSame([], $this->validate($this->manualSet()));
    }

    public function testBobotDenganPembulatanDuaDesimalDiterima(): void
    {
        $weights = ['C1' => 33.33, 'C2' => 33.33, 'C3' => 33.34, 'C4' => 0, 'C5' => 0];

        $this->assertSame([], $this->validate($this->manualSet(), $weights));
    }

    public function testInputBerbentukStringDariDatabaseDiterima(): void
    {
        $strings = [
            $this->alt(1, 'Laptop A', ['8000000.00', '10000.00', '8.00', '256.00', '8.00']),
            $this->alt(2, 'Laptop B', ['10000000.00', '15000.00', '16.00', '512.00', '10.00']),
            $this->alt(3, 'Laptop C', ['12500000.00', '20000.00', '16.00', '1024.00', '12.00']),
        ];
        $weights = ['C1' => '30.00', 'C2' => '25', 'C3' => '20', 'C4' => '15', 'C5' => '10.0'];

        $this->assertSame([], $this->validate($strings, $weights));

        $expected = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());
        $actual   = $this->saw->calculate($strings, $this->criteria(), $weights);

        foreach ([1, 2, 3] as $id) {
            $this->assertEqualsWithDelta($expected['preferences'][$id], $actual['preferences'][$id], self::DELTA);
        }
    }

    public function testBobotKurangDari100Ditolak(): void
    {
        $weights = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 9.99];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'Total bobot harus tepat 100%');
    }

    public function testBobotLebihDari100Ditolak(): void
    {
        $weights = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 10.01];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'Total bobot harus tepat 100%');
    }

    public function testBobotDiLuarRentangDitolak(): void
    {
        $negative = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 30, 'C5' => -5];
        $tooBig   = ['C1' => 150, 'C2' => 0, 'C3' => 0, 'C4' => 0, 'C5' => 0];

        $this->assertHasError($this->validate($this->manualSet(), $negative), 'harus antara 0 dan 100');
        $this->assertHasError($this->validate($this->manualSet(), $tooBig), 'harus antara 0 dan 100');
    }

    public function testBobotLebihDariDuaDesimalDitolak(): void
    {
        $weights = ['C1' => 30.005, 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 9.995];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'maksimal 2 angka');
    }

    public function testBobotTidakLengkapDitolak(): void
    {
        $weights = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 25];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'belum diisi');
    }

    public function testBobotKriteriaTidakDikenalDitolak(): void
    {
        $weights = $this->weights() + ['C9' => 0];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'tidak dikenal');
    }

    public function testBobotBukanAngkaDitolak(): void
    {
        $weights = ['C1' => 'abc', 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 10];

        $this->assertHasError($this->validate($this->manualSet(), $weights), 'harus berupa angka');
    }

    // =================================================================
    // Validasi nilai alternatif
    // =================================================================

    public function testNilaiNolPadaCostDitolak(): void
    {
        $set                     = $this->manualSet();
        $set[0]['values']['C1'] = 0;

        $this->assertHasError($this->validate($set), 'harus lebih dari 0');
    }

    public function testNilaiNegatifDitolak(): void
    {
        $set                     = $this->manualSet();
        $set[1]['values']['C3'] = -1;

        $this->assertHasError($this->validate($set), 'tidak boleh negatif');
    }

    public function testDataTidakLengkapDitolak(): void
    {
        $missing = $this->manualSet();
        unset($missing[1]['values']['C4']);
        $this->assertHasError($this->validate($missing), 'belum diisi');

        $null                     = $this->manualSet();
        $null[2]['values']['C5'] = null;
        $this->assertHasError($this->validate($null), 'belum diisi');

        $blank                     = $this->manualSet();
        $blank[0]['values']['C2'] = '  ';
        $this->assertHasError($this->validate($blank), 'belum diisi');
    }

    public function testNilaiBukanAngkaDitolak(): void
    {
        foreach (['abc', INF, NAN] as $bad) {
            $set                     = $this->manualSet();
            $set[0]['values']['C2'] = $bad;

            $this->assertHasError($this->validate($set), 'harus berupa angka');
        }
    }

    public function testKurangDariDuaAlternatifDitolak(): void
    {
        $this->assertHasError($this->validate([$this->manualSet()[0]]), 'Minimal harus ada 2 alternatif');
        $this->assertHasError($this->validate([]), 'Minimal harus ada 2 alternatif');
    }

    public function testKriteriaBenefitSemuaNolDitolak(): void
    {
        $set = $this->manualSet();
        foreach ($set as $i => $alt) {
            $set[$i]['values']['C4'] = 0;
        }

        $this->assertHasError($this->validate($set), 'Semua alternatif bernilai 0');
    }

    public function testNilaiNolPadaBenefitDiterimaBilaAdaNilaiLebihBesar(): void
    {
        $set                     = $this->manualSet();
        $set[0]['values']['C4'] = 0;

        $this->assertSame([], $this->validate($set));

        $result = $this->saw->calculate($set, $this->criteria(), $this->weights());
        $this->assertEqualsWithDelta(0.0, $result['normalized_matrix'][1]['C4'], self::DELTA);
    }

    public function testKriteriaTidakValidDitolak(): void
    {
        $dup = array_merge($this->criteria(), [['code' => 'C1', 'name' => 'Ganda', 'type' => 'cost']]);
        $this->assertHasError($this->validate($this->manualSet(), null, $dup), 'duplikat');

        $badType        = $this->criteria();
        $badType[2]['type'] = 'netral';
        $this->assertHasError($this->validate($this->manualSet(), null, $badType), 'harus benefit atau cost');

        $this->assertHasError($this->validate($this->manualSet(), [], []), 'Minimal harus ada satu kriteria');
    }

    public function testIdAlternatifDuplikatDitolak(): void
    {
        $set          = $this->manualSet();
        $set[1]['id'] = 1;

        $this->assertHasError($this->validate($set), 'duplikat');
    }

    // =================================================================
    // Perhitungan dibandingkan dengan hitungan manual
    // =================================================================

    public function testNilaiPreferensiSesuaiPerhitunganManual(): void
    {
        $result = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());

        $this->assertEqualsWithDelta(0.62916667, $result['preferences'][1], self::DELTA);
        $this->assertEqualsWithDelta(0.78583333, $result['preferences'][2], self::DELTA);
        $this->assertEqualsWithDelta(0.892, $result['preferences'][3], self::DELTA);
    }

    public function testRankingSesuaiPerhitunganManual(): void
    {
        $result = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());

        $this->assertSame([3, 2, 1], $this->rankedKeys($result));
        $this->assertSame([1, 2, 3], array_column($result['ranking'], 'rank'));
        $this->assertSame(['Laptop C', 'Laptop B', 'Laptop A'], array_column($result['ranking'], 'name'));
        $this->assertSame([false, false, false], array_column($result['ranking'], 'tied'));
    }

    public function testMatriksKontribusiSesuaiPerhitunganManual(): void
    {
        $w = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights())['weighted_matrix'];

        $expected = [
            1 => ['C1' => 0.3,   'C2' => 0.125,  'C3' => 0.1, 'C4' => 0.0375, 'C5' => 0.06666667],
            2 => ['C1' => 0.24,  'C2' => 0.1875, 'C3' => 0.2, 'C4' => 0.075,  'C5' => 0.08333333],
            3 => ['C1' => 0.192, 'C2' => 0.25,   'C3' => 0.2, 'C4' => 0.15,   'C5' => 0.1],
        ];

        foreach ($expected as $id => $row) {
            foreach ($row as $code => $value) {
                $this->assertEqualsWithDelta($value, $w[$id][$code], self::DELTA, "Kontribusi {$code} alternatif {$id}");
            }
        }
    }

    public function testKontribusiBerjumlahSamaDenganNilaiPreferensi(): void
    {
        $result = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());

        foreach ($result['weighted_matrix'] as $id => $row) {
            $this->assertEqualsWithDelta(array_sum($row), $result['preferences'][$id], self::DELTA);
        }
    }

    public function testHasilTidakBerubahBilaSkalaDikalikan(): void
    {
        $scaled = [
            $this->alt(1, 'Laptop A', [16000000, 100000, 8, 256, 8]),
            $this->alt(2, 'Laptop B', [20000000, 150000, 16, 512, 10]),
            $this->alt(3, 'Laptop C', [25000000, 200000, 16, 1024, 12]),
        ];

        $base   = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());
        $result = $this->saw->calculate($scaled, $this->criteria(), $this->weights());

        foreach ([1, 2, 3] as $id) {
            $this->assertEqualsWithDelta($base['preferences'][$id], $result['preferences'][$id], self::DELTA);
        }
        $this->assertSame($this->rankedKeys($base), $this->rankedKeys($result));
    }

    public function testKriteriaBerbobotNolTidakBerkontribusi(): void
    {
        $weights = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 25, 'C5' => 0];
        $result  = $this->saw->calculate($this->manualSet(), $this->criteria(), $weights);

        foreach ([1, 2, 3] as $id) {
            $this->assertEqualsWithDelta(0.0, $result['weighted_matrix'][$id]['C5'], self::DELTA);
        }
        // C = 0,192 + 0,25 + 0,2 + 0,25 + 0
        $this->assertEqualsWithDelta(0.892, $result['preferences'][3], self::DELTA);
    }

    public function testStrukturHasilCalculate(): void
    {
        $result = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());

        foreach (
            [
                'method',
                'precision',
                'total_weight_percent',
                'criteria',
                'alternatives',
                'decision_matrix',
                'reference',
                'normalized_matrix',
                'weighted_matrix',
                'preferences',
                'ranking',
                'best',
                'tie_break_rule'
            ] as $key
        ) {
            $this->assertArrayHasKey($key, $result);
        }

        $this->assertSame('SAW', $result['method']);
        $this->assertEqualsWithDelta(100.0, $result['total_weight_percent'], self::DELTA);
        $this->assertSame('Laptop C', $result['best']['name']);
        $this->assertSame(1, $result['best']['rank']);

        $this->assertSame('cost', $result['reference']['C1']['type']);
        $this->assertEqualsWithDelta(8000000.0, $result['reference']['C1']['value'], self::DELTA);
        $this->assertEqualsWithDelta(20000.0, $result['reference']['C2']['value'], self::DELTA);

        $this->assertEqualsWithDelta(0.3, $result['criteria'][0]['weight_normalized'], self::DELTA);
        $this->assertEqualsWithDelta(30.0, $result['criteria'][0]['weight_percent'], self::DELTA);
    }

    public function testCalculateMelemparExceptionBilaInputTidakValid(): void
    {
        $set                     = $this->manualSet();
        $set[0]['values']['C1'] = 0;
        $weights                 = ['C1' => 30, 'C2' => 25, 'C3' => 20, 'C4' => 15, 'C5' => 5];

        try {
            $this->saw->calculate($set, $this->criteria(), $weights);
            $this->fail('SAWValidationException seharusnya dilempar.');
        } catch (SAWValidationException $e) {
            $this->assertHasError($e->getErrors(), 'harus lebih dari 0');
            $this->assertHasError($e->getErrors(), 'Total bobot harus tepat 100%');
        }
    }

    public function testUrutanInputTidakMempengaruhiRanking(): void
    {
        $normal   = $this->saw->calculate($this->manualSet(), $this->criteria(), $this->weights());
        $reversed = $this->saw->calculate(array_reverse($this->manualSet()), $this->criteria(), $this->weights());

        $this->assertSame($this->rankedKeys($normal), $this->rankedKeys($reversed));
    }

    // =================================================================
    // Tie-break
    // =================================================================

    public function testSeriDiputuskanOlehKriteriaPertamaBilaBobotSama(): void
    {
        // V X = V Y = 0,75
        $set    = [$this->alt2(10, 'X', 10, 20), $this->alt2(11, 'Y', 20, 40)];
        $result = $this->saw->calculate($set, $this->twoCriteria(), ['C1' => 50, 'C2' => 50]);

        $this->assertEqualsWithDelta(0.75, $result['preferences'][10], self::DELTA);
        $this->assertEqualsWithDelta(0.75, $result['preferences'][11], self::DELTA);

        $this->assertSame([10, 11], $this->rankedKeys($result));
        $this->assertTrue($result['ranking'][0]['tied']);
        $this->assertTrue($result['ranking'][1]['tied']);
        $this->assertNull($result['ranking'][0]['tie_break']);
        $this->assertStringContainsString('C1', (string) $result['ranking'][1]['tie_break']);
    }

    public function testSeriDiputuskanOlehKriteriaBerbobotTerbesar(): void
    {
        // Bobot C1 40, C2 60 -> V X = V Y = 0,7; C2 berbobot lebih besar sehingga Y menang.
        $set    = [$this->alt2(10, 'X', 10, 20), $this->alt2(11, 'Y', 40, 40)];
        $result = $this->saw->calculate($set, $this->twoCriteria(), ['C1' => 40, 'C2' => 60]);

        $this->assertEqualsWithDelta(0.7, $result['preferences'][10], self::DELTA);
        $this->assertEqualsWithDelta(0.7, $result['preferences'][11], self::DELTA);

        $this->assertSame([11, 10], $this->rankedKeys($result));
        $this->assertStringContainsString('C2', (string) $result['ranking'][1]['tie_break']);
    }

    public function testSeriDiputuskanOlehNama(): void
    {
        $criteria = [['code' => 'C1', 'name' => 'Skor', 'type' => 'benefit']];
        $set      = [
            ['id' => 1, 'name' => 'Beta',  'values' => ['C1' => 10]],
            ['id' => 2, 'name' => 'alpha', 'values' => ['C1' => 10]],
        ];

        $result = $this->saw->calculate($set, $criteria, ['C1' => 100]);

        $this->assertSame([2, 1], $this->rankedKeys($result));
        $this->assertStringContainsString('abjad', (string) $result['ranking'][1]['tie_break']);
    }

    public function testSeriDiputuskanOlehId(): void
    {
        $criteria = [['code' => 'C1', 'name' => 'Skor', 'type' => 'benefit']];
        $set      = [
            ['id' => 5, 'name' => 'Sama', 'values' => ['C1' => 10]],
            ['id' => 3, 'name' => 'Sama', 'values' => ['C1' => 10]],
        ];

        $result = $this->saw->calculate($set, $criteria, ['C1' => 100]);

        $this->assertSame([3, 5], $this->rankedKeys($result));
        $this->assertStringContainsString('ID', (string) $result['ranking'][1]['tie_break']);
    }

    public function testSeriKonsistenBilaUrutanInputDibalik(): void
    {
        $equalWeights = [$this->alt2(10, 'X', 10, 20), $this->alt2(11, 'Y', 20, 40)];
        $a            = $this->saw->calculate($equalWeights, $this->twoCriteria(), ['C1' => 50, 'C2' => 50]);
        $b            = $this->saw->calculate(array_reverse($equalWeights), $this->twoCriteria(), ['C1' => 50, 'C2' => 50]);
        $this->assertSame($this->rankedKeys($a), $this->rankedKeys($b));

        $unequalWeights = [$this->alt2(10, 'X', 10, 20), $this->alt2(11, 'Y', 40, 40)];
        $c              = $this->saw->calculate($unequalWeights, $this->twoCriteria(), ['C1' => 40, 'C2' => 60]);
        $d              = $this->saw->calculate(array_reverse($unequalWeights), $this->twoCriteria(), ['C1' => 40, 'C2' => 60]);
        $this->assertSame($this->rankedKeys($c), $this->rankedKeys($d));
    }

    // =================================================================
    // Regresi: data demo hasil seeder, bobot default 30/25/20/15/10
    // (V dihitung tangan, ketelitian sekitar 4 desimal)
    // =================================================================

    public function testDataDemoMenghasilkanPeringkatYangDiharapkan(): void
    {
        $set = [
            $this->alt(1, 'DEMO-A1', [7999000, 9500, 8, 256, 7.0]),
            $this->alt(2, 'DEMO-A2', [10499000, 14200, 16, 512, 10.0]),
            $this->alt(3, 'DEMO-B1', [8999000, 11800, 8, 512, 8.0]),
            $this->alt(4, 'DEMO-B2', [13999000, 18500, 16, 512, 12.0]),
            $this->alt(5, 'DEMO-C1', [7499000, 8900, 8, 256, 6.5]),
            $this->alt(6, 'DEMO-C2', [11999000, 16300, 16, 1024, 11.0]),
            $this->alt(7, 'DEMO-D1', [9999000, 12700, 16, 512, 9.0]),
            $this->alt(8, 'DEMO-E1', [12499000, 15100, 16, 512, 8.5]),
        ];

        $result = $this->saw->calculate($set, $this->criteria(), $this->weights());

        $this->assertSame(
            ['DEMO-C2', 'DEMO-B2', 'DEMO-A2', 'DEMO-D1', 'DEMO-E1', 'DEMO-B1', 'DEMO-C1', 'DEMO-A1'],
            array_column($result['ranking'], 'name')
        );

        $expected = [
            'DEMO-C2' => 0.8494,
            'DEMO-B2' => 0.7857,
            'DEMO-A2' => 0.7645,
            'DEMO-D1' => 0.7466,
            'DEMO-E1' => 0.7299,
            'DEMO-B1' => 0.6511,
            'DEMO-C1' => 0.6119,
            'DEMO-A1' => 0.6055,
        ];

        foreach ($result['ranking'] as $item) {
            $this->assertEqualsWithDelta($expected[$item['name']], $item['preference'], 0.0005, $item['name']);
        }
    }
}
