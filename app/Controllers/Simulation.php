<?php

namespace App\Controllers;

use App\Services\SAWService;
use App\Services\SimulationService;

class Simulation extends BaseController
{
    private const DRAFT = 'sim_draft';

    /** Halaman pilih laptop dan atur bobot. */
    public function index()
    {
        $service = new SimulationService();
        $options = $service->options();

        // Prioritas isian: hasil validasi gagal (flash) > draft terakhir > default.
        $flash = session()->getFlashdata('sim_old');
        $state = is_array($flash) ? $flash : ($this->draft() ?? []);

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
            'min'      => SAWService::MIN_ALTERNATIVES,
            'max'      => SimulationService::MAX_ALTERNATIVES,
        ]);
    }

    /** Validasi dan simpan draft, lalu tampilkan hasil. */
    public function calculate()
    {
        $input = $this->collect();
        $state = [
            'name'       => $input['name'],
            'laptop_ids' => $input['laptop_ids'],
            'weights'    => $input['weights'],
        ];

        $errors = $input['errors'];

        if ($errors === []) {
            $run    = (new SimulationService())->run($input['laptop_ids'], $input['weights']);
            $errors = $run['errors'];
        }

        if ($errors !== []) {
            return redirect()->to(site_url('simulasi'))
                ->with('errors', array_values(array_unique($errors)))
                ->with('sim_old', $state);
        }

        session()->set(self::DRAFT, $state + [
            'user_id'       => (int) auth_user()['id'],
            'calculated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('simulasi/hasil'));
    }

    /** Tampilkan hasil dari draft (dihitung ulang dengan data master saat ini). */
    public function result()
    {
        $draft = $this->draft();

        if ($draft === null) {
            return redirect()->to(site_url('simulasi'))
                ->with('info', 'Belum ada simulasi. Pilih laptop dan atur bobot terlebih dahulu.');
        }

        $run = (new SimulationService())->run($draft['laptop_ids'], $draft['weights']);

        if ($run['errors'] !== []) {
            return redirect()->to(site_url('simulasi'))
                ->with('warning', 'Data berubah sejak perhitungan terakhir. Periksa kembali pilihan Anda.')
                ->with('errors', $run['errors'])
                ->with('sim_old', [
                    'name'       => $draft['name'],
                    'laptop_ids' => $draft['laptop_ids'],
                    'weights'    => $draft['weights'],
                ]);
        }

        $calculatedAt = (string) ($draft['calculated_at'] ?? date('Y-m-d H:i:s'));
        $name         = $draft['name'] !== ''
            ? $draft['name']
            : 'Simulasi ' . date('d/m/Y H:i', strtotime($calculatedAt));

        return view('simulation/result', [
            'title'        => 'Hasil Simulasi',
            'simName'      => $name,
            'calculatedAt' => $calculatedAt,
            'userName'     => auth_user()['name'],
            'result'       => $run['result'],
            'meta'         => $run['meta'],
        ]);
    }

    /** Hapus draft dan mulai simulasi baru. */
    public function reset()
    {
        session()->remove(self::DRAFT);

        return redirect()->to(site_url('simulasi'));
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

    /** Draft milik pengguna yang sedang login, atau null. */
    private function draft(): ?array
    {
        $draft = session()->get(self::DRAFT);

        $valid = is_array($draft)
            && (int) ($draft['user_id'] ?? 0) === (int) auth_user()['id']
            && is_array($draft['laptop_ids'] ?? null)
            && is_array($draft['weights'] ?? null)
            && is_string($draft['name'] ?? null);

        if (! $valid) {
            session()->remove(self::DRAFT);

            return null;
        }

        return $draft;
    }
}
