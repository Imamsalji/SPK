<?php

namespace App\Services;

use App\Models\CalculationModel;
use App\Models\CriterionModel;
use RuntimeException;
use Throwable;

/**
 * Menyimpan hasil SAW sebagai snapshot dan membacanya kembali.
 * Pembacaan (find) hanya memakai tabel calculation_*, tidak pernah tabel master.
 */
class CalculationService
{
    /**
     * Simpan hasil perhitungan dalam satu transaksi.
     *
     * @param array{id:int|string, name:string} $user
     * @param array                             $result keluaran SAWService::calculate()
     * @param array<int,array>                  $meta   [id laptop => brand_name, name, model, price]
     *
     * @return int ID perhitungan
     *
     * @throws RuntimeException bila penyimpanan gagal (semua perubahan dibatalkan)
     */
    public function save(array $user, string $name, array $result, array $meta): int
    {
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $name = trim($name);

        if ($name === '') {
            $name = 'Simulasi ' . date('d/m/Y H:i', strtotime($now));
        }

        $criterionIds = array_column(model(CriterionModel::class, false)->findAll(), 'id', 'code');
        $best         = $result['best'];

        try {
            $db->transBegin();

            // 1. calculations
            $calculationId = model(CalculationModel::class, false)->insert([
                'user_id'               => (int) $user['id'],
                'name'                  => $name,
                'method'                => (string) $result['method'],
                'user_name_snapshot'    => (string) $user['name'],
                'total_alternatives'    => count($result['alternatives']),
                'total_criteria'        => count($result['criteria']),
                'total_weight'          => $this->dec((float) $result['total_weight_percent'], 2),
                'best_alternative_name' => (string) $best['name'],
                'best_score'            => $this->dec((float) $best['preference'], 8),
                'calculated_at'         => $now,
            ]);

            if ($calculationId === false) {
                throw new RuntimeException('Gagal menyimpan data perhitungan.');
            }
            $calculationId = (int) $calculationId;

            // 2. calculation_criteria (snapshot kriteria dan bobot)
            $criterionRowIds = []; // kode => id baris snapshot
            foreach ($result['criteria'] as $i => $c) {
                $criterionRowIds[$c['code']] = $this->insertRow('calculation_criteria', [
                    'calculation_id'    => $calculationId,
                    'criterion_id'      => isset($criterionIds[$c['code']]) ? (int) $criterionIds[$c['code']] : null,
                    'code'              => $c['code'],
                    'name'              => $c['name'],
                    'type'              => $c['type'],
                    'unit'              => $c['unit'],
                    'weight_percent'    => $this->dec((float) $c['weight_percent'], 2),
                    'weight_normalized' => $this->dec((float) $c['weight_normalized'], 8),
                    'sort_order'        => $i + 1,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }

            // 3. calculation_alternatives (snapshot laptop)
            $alternativeRowIds = []; // id laptop => id baris snapshot
            foreach ($result['alternatives'] as $alt) {
                $key = $alt['key'];

                if (! isset($meta[$key])) {
                    throw new RuntimeException('Data laptop untuk snapshot tidak lengkap (ID ' . $key . ').');
                }
                $m = $meta[$key];

                $alternativeRowIds[$key] = $this->insertRow('calculation_alternatives', [
                    'calculation_id' => $calculationId,
                    'laptop_id'      => (int) $key,
                    'brand_name'     => (string) $m['brand_name'],
                    'laptop_name'    => (string) $m['name'],
                    'model'          => (string) $m['model'],
                    'price'          => $this->dec((float) $m['price'], 2),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            // 4. calculation_normalizations (nilai mentah, normalisasi, kontribusi)
            $rows = [];
            foreach ($result['alternatives'] as $alt) {
                $key = $alt['key'];

                foreach ($result['criteria'] as $c) {
                    $code   = $c['code'];
                    $rows[] = [
                        'calculation_id'           => $calculationId,
                        'alternative_id'           => $alternativeRowIds[$key],
                        'calculation_criterion_id' => $criterionRowIds[$code],
                        'raw_value'                => $this->dec((float) $result['decision_matrix'][$key][$code], 2),
                        'normalized_value'         => $this->dec((float) $result['normalized_matrix'][$key][$code], 8),
                        'weighted_value'           => $this->dec((float) $result['weighted_matrix'][$key][$code], 8),
                        'created_at'               => $now,
                        'updated_at'               => $now,
                    ];
                }
            }
            if ($db->table('calculation_normalizations')->insertBatch($rows) === false) {
                throw new RuntimeException('Gagal menyimpan matriks perhitungan.');
            }

            // 5. calculation_results (nilai preferensi dan ranking)
            $rows = [];
            foreach ($result['ranking'] as $item) {
                $rows[] = [
                    'calculation_id'   => $calculationId,
                    'alternative_id'   => $alternativeRowIds[$item['key']],
                    'preference_value' => $this->dec((float) $item['preference'], 8),
                    'rank'             => (int) $item['rank'],
                    'is_best'          => (int) $item['rank'] === 1 ? 1 : 0,
                    'is_tied'          => $item['tied'] ? 1 : 0,
                    'tie_break'        => $item['tie_break'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
            if ($db->table('calculation_results')->insertBatch($rows) === false) {
                throw new RuntimeException('Gagal menyimpan peringkat.');
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Transaksi database gagal.');
            }

            $db->transCommit();

            return $calculationId;
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'CalculationService::save: ' . $e->getMessage());

            throw new RuntimeException('Hasil perhitungan gagal disimpan.', 0, $e);
        }
    }

    /**
     * Baca satu perhitungan dari snapshot, dalam format yang sama dengan keluaran SAWService
     * (siap dipakai partial saw_result). Mengembalikan null bila tidak ada atau bukan milik $viewer.
     *
     * @param array{id?:int|string, role?:string} $viewer
     *
     * @return array{calculation:array, result:array, meta:array, laptop_ids:list<int>, orphan_laptops:int, orphan_criteria:int}|null
     */
    public function find(int $id, array $viewer): ?array
    {
        $calculation = model(CalculationModel::class, false)
            ->visibleTo($viewer)
            ->where('calculations.id', $id)
            ->first();

        if ($calculation === null) {
            return null;
        }

        $db = db_connect();

        $criteriaRows = $db->table('calculation_criteria')->where('calculation_id', $id)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $altRows = $db->table('calculation_alternatives')->where('calculation_id', $id)
            ->orderBy('id', 'ASC')->get()->getResultArray();
        $normRows = $db->table('calculation_normalizations')->where('calculation_id', $id)
            ->get()->getResultArray();
        $resultRows = $db->table('calculation_results')->where('calculation_id', $id)
            ->orderBy('rank', 'ASC')->get()->getResultArray();

        if ($criteriaRows === [] || $altRows === [] || $resultRows === []) {
            return null;
        }

        $criteria        = [];
        $codeByRowId     = [];
        $orphanCriteria  = 0;
        foreach ($criteriaRows as $row) {
            $criteria[] = [
                'code'              => $row['code'],
                'name'              => $row['name'],
                'type'              => $row['type'],
                'unit'              => $row['unit'],
                'weight_percent'    => (float) $row['weight_percent'],
                'weight_normalized' => (float) $row['weight_normalized'],
            ];
            $codeByRowId[(int) $row['id']] = $row['code'];

            if ($row['criterion_id'] === null) {
                $orphanCriteria++;
            }
        }

        $alternatives   = [];
        $names          = [];
        $meta           = [];
        $laptopIds      = [];
        $orphanLaptops  = 0;
        foreach ($altRows as $row) {
            $key            = (int) $row['id'];
            $alternatives[] = ['key' => $key, 'name' => $row['laptop_name']];
            $names[$key]    = $row['laptop_name'];
            $meta[$key]     = [
                'brand_name' => $row['brand_name'],
                'name'       => $row['laptop_name'],
                'model'      => $row['model'],
                'price'      => (float) $row['price'],
            ];

            if ($row['laptop_id'] === null) {
                $orphanLaptops++;
            } else {
                $laptopIds[] = (int) $row['laptop_id'];
            }
        }

        $decision   = [];
        $normalized = [];
        $weighted   = [];
        foreach ($normRows as $row) {
            $altKey = (int) $row['alternative_id'];
            $code   = $codeByRowId[(int) $row['calculation_criterion_id']] ?? null;

            if ($code === null) {
                continue;
            }

            $decision[$altKey][$code]   = (float) $row['raw_value'];
            $normalized[$altKey][$code] = (float) $row['normalized_value'];
            $weighted[$altKey][$code]   = (float) $row['weighted_value'];
        }

        $ranking     = [];
        $preferences = [];
        foreach ($resultRows as $row) {
            $key         = (int) $row['alternative_id'];
            $preference  = (float) $row['preference_value'];
            $tieBreak    = (string) ($row['tie_break'] ?? '');

            $preferences[$key] = $preference;
            $ranking[]         = [
                'key'        => $key,
                'name'       => $names[$key] ?? '',
                'rank'       => (int) $row['rank'],
                'preference' => $preference,
                'tied'       => (int) $row['is_tied'] === 1,
                'tie_break'  => $tieBreak !== '' ? $tieBreak : null,
            ];
        }

        $saw = new SAWService();

        $result = [
            'method'               => $calculation['method'],
            'precision'            => SAWService::PRECISION,
            'total_weight_percent' => (float) $calculation['total_weight'],
            'criteria'             => $criteria,
            'alternatives'         => $alternatives,
            'decision_matrix'      => $decision,
            'reference'            => $saw->referenceValues($decision, $criteria), // dari nilai mentah snapshot
            'normalized_matrix'    => $normalized,
            'weighted_matrix'      => $weighted,
            'preferences'          => $preferences,
            'ranking'              => $ranking,
            'best'                 => $ranking[0],
            'tie_break_rule'       => $saw->tieBreakRule(),
        ];

        return [
            'calculation'     => $calculation,
            'result'          => $result,
            'meta'            => $meta,
            'laptop_ids'      => $laptopIds,
            'orphan_laptops'  => $orphanLaptops,
            'orphan_criteria' => $orphanCriteria,
        ];
    }

    /** Pengguna yang memiliki riwayat (untuk filter admin). @return list<array{user_id:int|string,name:string}> */
    public function usersWithCalculations(): array
    {
        return db_connect()->table('calculations')
            ->select('user_id, MAX(user_name_snapshot) AS name')
            ->groupBy('user_id')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    private function insertRow(string $table, array $row): int
    {
        $db = db_connect();

        if ($db->table($table)->insert($row) === false) {
            throw new RuntimeException("Gagal menyimpan ke tabel {$table}.");
        }

        return (int) $db->insertID();
    }

    /** Angka sebagai string desimal tetap, tanpa notasi ilmiah. */
    private function dec(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }
}
