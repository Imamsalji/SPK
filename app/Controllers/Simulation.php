<?php

namespace App\Controllers;

use App\Services\CalculationService;
use App\Services\SAWService;
use App\Services\SimulationService;
use Throwable;

class Simulation extends BaseController
{
    /** Halaman pilih laptop dan atur bobot. */
    public function index()
    {
        $service = new SimulationService();
        $options = $service->options();

        // Prioritas isian: hasil validasi gagal (flash) > dimuat dari riwayat (?ulang=) > default.
        $flash  = session()->getFlashdata('sim_old');
        $state  = is_array($flash) ? $flash : [];
        $notice = null;

        if ($state === []) {
            $again = (int) $this->request->getGet('ulang');

            if ($again > 0) {
                $data = (new CalculationService())->find($again, auth_user());

                if ($data !== null) {
                    $weights = [];
                    foreach ($data['result']['criteria'] as $c) {
                        $weights[$c['code']] = plain_number($c['weight_percent']);
                    }

                    $state  = ['name' => '', 'laptop_ids' => $data['laptop_ids'], 'weights' => $weights];
                    $notice = 'Pilihan dan bobot dimuat dari "' . $data['calculation']['name']
                        . '". Laptop yang sudah dihapus, nonaktif, atau belum lengkap nilainya tidak ikut dipilih.';
                }
            }
        }

        $selected = array_map('intval', (array) ($state['laptop_ids'] ?? []));
        if ($state === []) {
            $preselect = (int) $this->request->getGet('pilih');
            if ($preselect > 0) {
                $selected = [$preselect];
            }
        }

        $stateWeights = is_array($state['weights'] ?? null) ? $state['weights'] : [];
        $weights      = [];
        foreach ($options['criteria'] as $criterion) {
            $code           = $criterion['code'];
            $weights[$code] = (string) ($stateWeights[$code] ?? plain_number($criterion['default_weight']));
        }

        return view('simulation/index', [
            'title'    => 'Simulasi SPK',
            'options'  => $options,
            'selected' => $selected,
            'weights'  => $weights,
            'name'     => (string) ($state['name'] ?? ''),
            'notice'   => $notice,
            'min'      => SAWService::MIN_ALTERNATIVES,
            'max'      => SimulationService::MAX_ALTERNATIVES,
        ]);
    }

    /** Validasi, hitung dengan SAW, simpan snapshot, lalu buka halaman riwayat. */
    public function calculate()
    {
        $input = $this->collect();
        $state = [
            'name'       => $input['name'],
            'laptop_ids' => $input['laptop_ids'],
            'weights'    => $input['weights'],
        ];

        $errors = $input['errors'];
        $run    = null;

        if ($errors === []) {
            $run    = (new SimulationService())->run($input['laptop_ids'], $input['weights']);
            $errors = $run['errors'];
        }

        if ($errors !== []) {
            return redirect()->to(site_url('simulasi'))
                ->with('errors', array_values(array_unique($errors)))
                ->with('sim_old', $state);
        }

        try {
            $id = (new CalculationService())->save(auth_user(), $input['name'], $run['result'], $run['meta']);
        } catch (Throwable $e) {
            log_message('error', 'Simulation::calculate: ' . $e->getMessage());

            return redirect()->to(site_url('simulasi'))
                ->with('error', 'Hasil perhitungan gagal disimpan. Silakan coba lagi.')
                ->with('sim_old', $state);
        }

        return redirect()->to(site_url('riwayat/' . $id))
            ->with('success', 'Perhitungan berhasil dan tersimpan di riwayat.');
    }

    // ------------------------------------------------------------------

    /**
     * Baca dan periksa format input form. Validasi aturan SAW (total 100%, dan sebagainya)
     * dilakukan SAWService.
     *
     * @return array{name:string, laptop_ids:list<int>, weights:array<string,string>, errors:list<string>}
     */
    private function collect(): array
    {
        $errors = [];

        $name = trim((string) $this->request->getPost('name'));
        if (mb_strlen($name) > 150) {
            $errors[] = 'Nama simulasi maksimal 150 karakter.';
        }

        $ids    = [];
        $rawIds = $this->request->getPost('laptop_ids');
        if ($rawIds !== null && ! is_array($rawIds)) {
            $errors[] = 'Pilihan laptop tidak valid.';
            $rawIds   = [];
        }
        foreach ((array) $rawIds as $id) {
            if (is_string($id) && ctype_digit($id) && strlen($id) <= 10) {
                $ids[] = (int) $id;
            } else {
                $errors[] = 'Pilihan laptop tidak valid.';
                break;
            }
        }

        $weights    = [];
        $rawWeights = $this->request->getPost('weights');
        if (! is_array($rawWeights)) {
            $errors[]   = 'Bobot kriteria tidak diterima.';
            $rawWeights = [];
        }
        foreach ($rawWeights as $code => $value) {
            $code = (string) $code;

            if (! preg_match('/^[A-Za-z0-9]{1,10}$/', $code) || ! is_string($value)) {
                $errors[] = 'Data bobot tidak valid.';
                continue;
            }

            $value = trim($value);
            if ($value === '') {
                $errors[] = "Bobot kriteria {$code} wajib diisi.";
                continue;
            }
            if (! preg_match('/^\d{1,3}(\.\d{1,2})?$/', $value)) {
                $errors[] = "Bobot kriteria {$code} tidak valid. Gunakan angka 0-100 dengan titik sebagai pemisah desimal (maksimal 2 angka di belakang titik).";
                continue;
            }

            $weights[$code] = $value;
        }

        return ['name' => $name, 'laptop_ids' => $ids, 'weights' => $weights, 'errors' => $errors];
    }
}
