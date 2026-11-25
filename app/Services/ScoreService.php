<?php

namespace App\Services;

use App\Models\CriterionModel;
use App\Models\LaptopModel;
use App\Models\LaptopScoreModel;
use RuntimeException;
use Throwable;

/**
 * Logika bisnis nilai alternatif: validasi input, penyimpanan, sinkronisasi harga,
 * dan pemeriksaan kelengkapan. Tidak ada perhitungan SAW di sini.
 */
class ScoreService
{
    public const PRICE_FIELD = 'price';
    public const PRICE_NOTE  = 'Otomatis dari harga laptop.';

    /** Kriteria aktif, berurutan sesuai sort_order. */
    public function activeCriteria(): array
    {
        return model(CriterionModel::class, false)->where('is_active', 1)->ordered()->findAll();
    }

    /** Kriteria yang nilainya diambil dari harga laptop (atau null). */
    public function priceCriterion(): ?array
    {
        return model(CriterionModel::class, false)->where('source_field', self::PRICE_FIELD)->first();
    }

    /** @return array<int, array> nilai satu laptop, dengan kunci criterion_id */
    public function scoresForLaptop(int $laptopId): array
    {
        $rows = model(LaptopScoreModel::class, false)->where('laptop_id', $laptopId)->findAll();

        return array_column($rows, null, 'criterion_id');
    }

    /** @return array<int, array<int, array>> [laptop_id][criterion_id] => baris nilai */
    public function scoresForLaptops(array $laptopIds): array
    {
        if ($laptopIds === []) {
            return [];
        }

        $rows = model(LaptopScoreModel::class, false)->whereIn('laptop_id', $laptopIds)->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(int) $row['laptop_id']][(int) $row['criterion_id']] = $row;
        }

        return $map;
    }

    /**
     * Validasi nilai yang diinput untuk satu laptop.
     * Kriteria berstatus harga dilewati (diisi otomatis).
     *
     * @param array $values [criterion_id => string]
     * @param array $notes  [criterion_id => string]
     *
     * @return array{errors:array<string,string>, clean:array<int,array{value:string,note:string}>, old:array}
     */
    public function validateInput(array $criteria, array $values, array $notes): array
    {
        $errors = [];
        $clean  = [];
        $old    = [];

        foreach ($criteria as $criterion) {
            if ($criterion['source_field'] === self::PRICE_FIELD) {
                continue;
            }

            $id   = (int) $criterion['id'];
            $raw  = $values[$id] ?? '';
            $raw  = is_string($raw) ? trim($raw) : '';
            $note = $notes[$id] ?? '';
            $note = is_string($note) ? trim($note) : '';

            $old[$id] = ['value' => $raw, 'note' => $note];

            if ($raw === '') {
                $errors['value_' . $id] = 'Nilai wajib diisi.';
            } elseif (! preg_match('/^\d{1,12}(\.\d{1,2})?$/', $raw)) {
                $errors['value_' . $id] = 'Gunakan angka positif dengan titik sebagai pemisah desimal (maksimal 2 angka di belakang titik).';
            } elseif ($criterion['type'] === 'cost' && (float) $raw <= 0) {
                $errors['value_' . $id] = 'Nilai kriteria Cost harus lebih dari 0.';
            }

            if (mb_strlen($note) > 255) {
                $errors['note_' . $id] = 'Sumber data maksimal 255 karakter.';
            } elseif ((int) $criterion['require_source'] === 1 && $note === '') {
                $errors['note_' . $id] = 'Sumber data wajib dicantumkan untuk kriteria ini.';
            }

            if (! isset($errors['value_' . $id]) && ! isset($errors['note_' . $id])) {
                $clean[$id] = [
                    'value' => number_format((float) $raw, 2, '.', ''),
                    'note'  => $note,
                ];
            }
        }

        return ['errors' => $errors, 'clean' => $clean, 'old' => $old];
    }

    /** Simpan (insert/update) nilai satu laptop dalam satu transaksi. */
    public function saveLaptopScores(int $laptopId, array $clean): bool
    {
        $model    = model(LaptopScoreModel::class, false);
        $existing = $this->scoresForLaptop($laptopId);
        $db       = db_connect();

        try {
            $db->transBegin();

            foreach ($clean as $criterionId => $item) {
                $data = [
                    'value'       => $item['value'],
                    'source_note' => $item['note'] !== '' ? $item['note'] : null,
                ];

                if (isset($existing[$criterionId])) {
                    $ok = $model->update((int) $existing[$criterionId]['id'], $data);
                } else {
                    $ok = $model->insert($data + ['laptop_id' => $laptopId, 'criterion_id' => (int) $criterionId]);
                }

                if ($ok === false) {
                    throw new RuntimeException('Gagal menyimpan nilai untuk kriteria ID ' . $criterionId);
                }
            }

            $db->transCommit();

            return true;
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'saveLaptopScores: ' . $e->getMessage());

            return false;
        }
    }

    /** Samakan nilai kriteria harga satu laptop dengan harganya. Tidak ada kriteria harga = tidak ada yang dilakukan. */
    public function syncPriceForLaptop(int $laptopId, float $price): bool
    {
        $criterion = $this->priceCriterion();
        if ($criterion === null) {
            return true;
        }

        $model    = model(LaptopScoreModel::class, false);
        $existing = $model->where('laptop_id', $laptopId)->where('criterion_id', (int) $criterion['id'])->first();
        $data     = [
            'value'       => number_format($price, 2, '.', ''),
            'source_note' => self::PRICE_NOTE,
        ];

        if ($existing !== null) {
            return $model->update((int) $existing['id'], $data) !== false;
        }

        return $model->insert($data + ['laptop_id' => $laptopId, 'criterion_id' => (int) $criterion['id']]) !== false;
    }

    /** Samakan nilai kriteria harga SEMUA laptop. Dipakai saat sebuah kriteria dijadikan sumber harga. */
    public function syncPriceForAllLaptops(): int
    {
        $criterion = $this->priceCriterion();
        if ($criterion === null) {
            return 0;
        }

        $count = 0;

        foreach (model(LaptopModel::class, false)->select('id, price')->findAll() as $laptop) {
            if (! $this->syncPriceForLaptop((int) $laptop['id'], (float) $laptop['price'])) {
                throw new RuntimeException('Sinkronisasi harga gagal untuk laptop ID ' . $laptop['id']);
            }
            $count++;
        }

        return $count;
    }

    /** Jumlah laptop yang belum punya nilai untuk kriteria tertentu. */
    public function laptopsMissingCriterion(int $criterionId): int
    {
        return model(LaptopModel::class, false)
            ->where(
                'NOT EXISTS (SELECT 1 FROM laptop_scores ls WHERE ls.laptop_id = laptops.id AND ls.criterion_id = '
                    . $criterionId . ')',
                null,
                false
            )
            ->countAllResults();
    }

    /**
     * Kondisi SQL (untuk where mentah): laptop dengan nilai lengkap / belum lengkap
     * dibandingkan seluruh kriteria aktif.
     */
    public function completenessCondition(bool $complete): string
    {
        $have = '(SELECT COUNT(*) FROM laptop_scores ls JOIN criteria c ON c.id = ls.criterion_id '
            . 'WHERE ls.laptop_id = laptops.id AND c.is_active = 1)';
        $need = '(SELECT COUNT(*) FROM criteria WHERE is_active = 1)';

        return $have . ($complete ? ' >= ' : ' < ') . $need;
    }

    /** @return array{total:int, complete:int, incomplete:int} */
    public function summary(): array
    {
        $total    = model(LaptopModel::class, false)->countAllResults();
        $complete = model(LaptopModel::class, false)
            ->where($this->completenessCondition(true), null, false)
            ->countAllResults();

        return ['total' => $total, 'complete' => $complete, 'incomplete' => $total - $complete];
    }
}
