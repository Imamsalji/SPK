<?php

namespace App\Commands;

use App\Models\CriterionModel;
use App\Models\LaptopModel;
use App\Services\SAWService;
use App\Services\SAWValidationException;
use App\Services\ScoreService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Menjalankan SAW pada data di database (laptop aktif, kriteria aktif, bobot default).
 * Hanya untuk verifikasi; tidak menyimpan apa pun.
 */
class SawDemo extends BaseCommand
{
    protected $group       = 'SPK';
    protected $name        = 'saw:demo';
    protected $description = 'Jalankan perhitungan SAW pada data laptop aktif dengan bobot default (tidak menyimpan hasil).';
    protected $usage       = 'saw:demo [detail]';
    protected $arguments   = [
        'detail' => 'Tampilkan juga matriks keputusan, normalisasi, dan kontribusi.',
    ];

    public function run(array $params)
    {
        $detail = in_array('detail', $params, true);

        $criteria = model(CriterionModel::class)->where('is_active', 1)->ordered()->findAll();
        if ($criteria === []) {
            CLI::error('Tidak ada kriteria aktif.');

            return;
        }

        $laptops = model(LaptopModel::class)->listing(['status' => '1'])->orderBy('laptops.id', 'ASC')->findAll();
        $scores  = (new ScoreService())->scoresForLaptops(array_map('intval', array_column($laptops, 'id')));

        $alternatives = [];
        $skipped      = [];

        foreach ($laptops as $laptop) {
            $values   = [];
            $complete = true;

            foreach ($criteria as $criterion) {
                $row = $scores[(int) $laptop['id']][(int) $criterion['id']] ?? null;
                if ($row === null) {
                    $complete = false;
                    break;
                }
                $values[$criterion['code']] = $row['value'];
            }

            if (! $complete) {
                $skipped[] = $laptop['name'];
                continue;
            }

            $alternatives[] = ['id' => (int) $laptop['id'], 'name' => $laptop['name'], 'values' => $values];
        }

        $weights = [];
        foreach ($criteria as $criterion) {
            $weights[$criterion['code']] = $criterion['default_weight'];
        }

        CLI::write(
            'Perhitungan SAW: ' . count($alternatives) . ' alternatif, ' . count($criteria) . ' kriteria, bobot default.',
            'yellow'
        );
        foreach ($skipped as $name) {
            CLI::write('  Dilewati (nilai belum lengkap): ' . $name, 'light_red');
        }
        CLI::newLine();

        try {
            $result = (new SAWService())->calculate($alternatives, $criteria, $weights);
        } catch (SAWValidationException $e) {
            CLI::error('Input tidak valid:');
            foreach ($e->getErrors() as $error) {
                CLI::error(' - ' . $error);
            }

            return;
        }

        $codes = array_column($result['criteria'], 'code');

        if ($detail) {
            $this->printMatrix('Matriks keputusan (X)', $result, $result['decision_matrix'], $codes, 2);

            $refRow = ['Acuan (max/min)'];
            foreach ($codes as $code) {
                $ref      = $result['reference'][$code];
                $refRow[] = ($ref['type'] === 'cost' ? 'min ' : 'max ') . number_format($ref['value'], 2, '.', '');
            }
            CLI::table([$refRow], array_merge(['Acuan'], $codes));
            CLI::newLine();

            $this->printMatrix('Matriks ternormalisasi (R)', $result, $result['normalized_matrix'], $codes, 8);
            $this->printMatrix('Kontribusi (w x r)', $result, $result['weighted_matrix'], $codes, 8);
        }

        $rows = [];
        foreach ($result['ranking'] as $item) {
            $rows[] = [
                (string) $item['rank'],
                $item['name'],
                sprintf('%.8f', $item['preference']),
                $item['tied'] ? 'seri' : '',
                (string) ($item['tie_break'] ?? ''),
            ];
        }

        CLI::write('Ranking', 'yellow');
        CLI::table($rows, ['Rank', 'Alternatif', 'Nilai V', 'Seri', 'Keterangan']);
        CLI::write('Terbaik: ' . $result['best']['name'] . ' (V = ' . sprintf('%.8f', $result['best']['preference']) . ')', 'green');
    }

    private function printMatrix(string $title, array $result, array $matrix, array $codes, int $decimals): void
    {
        $rows = [];

        foreach ($result['alternatives'] as $alt) {
            $row = [$alt['name']];
            foreach ($codes as $code) {
                $row[] = number_format((float) $matrix[$alt['key']][$code], $decimals, '.', '');
            }
            $rows[] = $row;
        }

        CLI::write($title, 'yellow');
        CLI::table($rows, array_merge(['Alternatif'], $codes));
        CLI::newLine();
    }
}
