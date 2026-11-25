<?php

namespace App\Services;

use App\Models\LaptopModel;

/**
 * Menyiapkan data simulasi dari database lalu menjalankan SAWService.
 * Tidak menulis apa pun ke database.
 */
class SimulationService
{
    public const MAX_ALTERNATIVES = 12;

    /**
     * Opsi untuk form simulasi.
     *
     * @return array{criteria:array, eligible:array<int,array>, incomplete:array<int,array>}
     *   eligible   : laptop aktif dengan nilai lengkap (ditambah kunci 'values' => [kode => nilai])
     *   incomplete : laptop aktif yang nilainya belum lengkap
     */
    public function options(): array
    {
        $scoreService = new ScoreService();
        $criteria     = $scoreService->activeCriteria();

        $laptops = model(LaptopModel::class, false)
            ->listing(['status' => '1'])
            ->orderBy('brands.name', 'ASC')
            ->orderBy('laptops.name', 'ASC')
            ->findAll();

        $scores = $scoreService->scoresForLaptops(array_map('intval', array_column($laptops, 'id')));

        $eligible   = [];
        $incomplete = [];

        foreach ($laptops as $laptop) {
            $id       = (int) $laptop['id'];
            $values   = [];
            $complete = $criteria !== [];

            foreach ($criteria as $criterion) {
                $row = $scores[$id][(int) $criterion['id']] ?? null;
                if ($row === null) {
                    $complete = false;
                    break;
                }
                $values[$criterion['code']] = $row['value'];
            }

            if ($complete) {
                $laptop['values'] = $values;
                $eligible[$id]    = $laptop;
            } else {
                $incomplete[$id] = $laptop;
            }
        }

        return ['criteria' => $criteria, 'eligible' => $eligible, 'incomplete' => $incomplete];
    }

    /**
     * Ubah daftar ID laptop menjadi alternatif untuk SAWService.
     *
     * @return array{alternatives:list<array>, meta:array<int,array>, errors:list<string>}
     */
    public function prepare(array $laptopIds, array $options): array
    {
        $errors       = [];
        $alternatives = [];
        $meta         = [];
        $ids          = array_values(array_unique(array_map('intval', $laptopIds)));

        if ($options['criteria'] === []) {
            $errors[] = 'Belum ada kriteria aktif. Hubungi administrator.';
        }
        if (count($ids) < SAWService::MIN_ALTERNATIVES) {
            $errors[] = 'Pilih minimal ' . SAWService::MIN_ALTERNATIVES . ' laptop untuk dibandingkan.';
        }
        if (count($ids) > self::MAX_ALTERNATIVES) {
            $errors[] = 'Pilih maksimal ' . self::MAX_ALTERNATIVES . ' laptop dalam satu simulasi.';
        }

        foreach ($ids as $id) {
            if (isset($options['eligible'][$id])) {
                $laptop         = $options['eligible'][$id];
                $alternatives[] = ['id' => $id, 'name' => (string) $laptop['name'], 'values' => $laptop['values']];
                $meta[$id]      = [
                    'brand_name' => (string) $laptop['brand_name'],
                    'name'       => (string) $laptop['name'],
                    'model'      => (string) $laptop['model'],
                    'price'      => (float) $laptop['price'],
                ];
            } elseif (isset($options['incomplete'][$id])) {
                $errors[] = $options['incomplete'][$id]['name'] . ': nilai kriteria belum lengkap, belum dapat dibandingkan.';
            } else {
                $errors[] = 'Laptop yang dipilih tidak tersedia (ID ' . $id . ').';
            }
        }

        return ['alternatives' => $alternatives, 'meta' => $meta, 'errors' => $errors];
    }

    /**
     * Jalankan simulasi.
     *
     * @param list<int>             $laptopIds
     * @param array<string,string>  $weights   kode kriteria => bobot (persen)
     *
     * @return array{errors:list<string>, result:?array, meta:array<int,array>}
     */
    public function run(array $laptopIds, array $weights): array
    {
        $options  = $this->options();
        $prepared = $this->prepare($laptopIds, $options);

        if ($prepared['errors'] !== []) {
            return ['errors' => $prepared['errors'], 'result' => null, 'meta' => []];
        }

        try {
            $result = (new SAWService())->calculate($prepared['alternatives'], $options['criteria'], $weights);
        } catch (SAWValidationException $e) {
            return ['errors' => $e->getErrors(), 'result' => null, 'meta' => []];
        }

        return ['errors' => [], 'result' => $result, 'meta' => $prepared['meta']];
    }
}
