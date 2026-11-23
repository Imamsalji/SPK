<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CriterionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Criteria extends BaseController
{
    private const URI = 'admin/kriteria';

    public function index()
    {
        $model = model(CriterionModel::class);

        return view('admin/criteria/index', [
            'title'       => 'Kriteria',
            'criteria'    => $model->ordered()->findAll(),
            'totalWeight' => $model->totalActiveWeight(),
            'nextOrder'   => $model->nextSortOrder(),
        ]);
    }

    public function store()
    {
        $input = $this->collect();

        if (! $this->validateData($input, $this->rules())) {
            return redirect_form_errors(self::URI, 'create', null, $this->validator->getErrors(), $input);
        }

        model(CriterionModel::class)->insert($this->toRow($input));

        return $this->done('Kriteria ' . $input['code'] . ' berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $criterion = $this->findOr404($id);
        $input     = $this->collect();

        if (! $this->validateData($input, $this->rules($id))) {
            return redirect_form_errors(self::URI, 'edit', $id, $this->validator->getErrors(), $input);
        }

        model(CriterionModel::class)->update($criterion['id'], $this->toRow($input));

        return $this->done('Kriteria ' . $input['code'] . ' berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $criterion = $this->findOr404($id);

        model(CriterionModel::class)->delete($criterion['id']);

        return $this->done('Kriteria ' . $criterion['code'] . ' berhasil dihapus. Nilai alternatif untuk kriteria ini ikut terhapus; riwayat perhitungan tidak berubah.');
    }

    /** Atur bobot default semua kriteria aktif sekaligus. Total harus tepat 100,00. */
    public function updateWeights()
    {
        $model  = model(CriterionModel::class);
        $active = $model->where('is_active', 1)->findAll();
        $input  = $this->request->getPost('weights');

        if ($active === [] || ! is_array($input)) {
            return $this->fail('Tidak ada bobot yang dapat disimpan.');
        }

        $cents   = 0;
        $weights = [];

        foreach ($active as $criterion) {
            $raw = $input[$criterion['id']] ?? null;

            if (! is_numeric($raw)) {
                return $this->fail('Bobot ' . $criterion['code'] . ' harus berupa angka.');
            }

            $weight = round((float) $raw, 2);
            if ($weight < 0 || $weight > 100) {
                return $this->fail('Bobot ' . $criterion['code'] . ' harus antara 0 dan 100.');
            }

            $weights[$criterion['id']] = $weight;
            $cents += (int) round($weight * 100);
        }

        if ($cents !== 10000) {
            return $this->fail('Total bobot harus tepat 100%. Total yang Anda masukkan: ' . num_id($cents / 100) . '%.');
        }

        $db = db_connect();
        $db->transStart();
        foreach ($weights as $criterionId => $weight) {
            $model->update($criterionId, ['default_weight' => $weight]);
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->fail('Bobot gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(site_url(self::URI))->with('success', 'Bobot default berhasil disimpan (total 100%).');
    }

    // ------------------------------------------------------------------

    private function collect(): array
    {
        return [
            'code'           => strtoupper(trim((string) $this->request->getPost('code'))),
            'name'           => trim((string) $this->request->getPost('name')),
            'description'    => trim((string) $this->request->getPost('description')),
            'type'           => (string) $this->request->getPost('type'),
            'unit'           => trim((string) $this->request->getPost('unit')),
            'default_weight' => trim((string) $this->request->getPost('default_weight')),
            'sort_order'     => trim((string) $this->request->getPost('sort_order')),
            'is_active'      => (string) $this->request->getPost('is_active') === '1' ? '1' : '0',
        ];
    }

    private function toRow(array $input): array
    {
        return [
            'code'           => $input['code'],
            'name'           => $input['name'],
            'description'    => $input['description'] !== '' ? $input['description'] : null,
            'type'           => $input['type'],
            'unit'           => $input['unit'] !== '' ? $input['unit'] : null,
            'default_weight' => round((float) $input['default_weight'], 2),
            'sort_order'     => (int) $input['sort_order'],
            'is_active'      => (int) $input['is_active'],
        ];
    }

    private function rules(?int $ignoreId = null): array
    {
        $unique = $ignoreId === null
            ? 'is_unique[criteria.code]'
            : 'is_unique[criteria.code,id,' . $ignoreId . ']';

        return [
            'code' => [
                'label'  => 'Kode',
                'rules'  => 'required|alpha_numeric|max_length[10]|' . $unique,
                'errors' => [
                    'required'      => 'Kode wajib diisi.',
                    'alpha_numeric' => 'Kode hanya boleh huruf dan angka (contoh: C6).',
                    'max_length'    => 'Kode maksimal 10 karakter.',
                    'is_unique'     => 'Kode sudah digunakan.',
                ],
            ],
            'name' => [
                'label'  => 'Nama',
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama kriteria wajib diisi.',
                    'max_length' => 'Nama kriteria maksimal 100 karakter.',
                ],
            ],
            'description' => [
                'label'  => 'Deskripsi',
                'rules'  => 'permit_empty|max_length[500]',
                'errors' => ['max_length' => 'Deskripsi maksimal 500 karakter.'],
            ],
            'type' => [
                'label'  => 'Jenis',
                'rules'  => 'required|in_list[benefit,cost]',
                'errors' => [
                    'required' => 'Jenis kriteria wajib dipilih.',
                    'in_list'  => 'Jenis harus Benefit atau Cost.',
                ],
            ],
            'unit' => [
                'label'  => 'Satuan',
                'rules'  => 'permit_empty|max_length[20]',
                'errors' => ['max_length' => 'Satuan maksimal 20 karakter.'],
            ],
            'default_weight' => [
                'label'  => 'Bobot default',
                'rules'  => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
                'errors' => [
                    'required'              => 'Bobot wajib diisi.',
                    'numeric'               => 'Bobot harus berupa angka.',
                    'greater_than_equal_to' => 'Bobot tidak boleh negatif.',
                    'less_than_equal_to'    => 'Bobot maksimal 100.',
                ],
            ],
            'sort_order' => [
                'label'  => 'Urutan',
                'rules'  => 'required|is_natural|less_than_equal_to[999]',
                'errors' => [
                    'required'           => 'Urutan wajib diisi.',
                    'is_natural'         => 'Urutan harus bilangan bulat 0 atau lebih.',
                    'less_than_equal_to' => 'Urutan maksimal 999.',
                ],
            ],
            'is_active' => [
                'label'  => 'Status',
                'rules'  => 'required|in_list[0,1]',
                'errors' => ['required' => 'Status wajib dipilih.', 'in_list' => 'Status tidak valid.'],
            ],
        ];
    }

    /** Redirect sukses, dengan peringatan bila total bobot aktif belum 100%. */
    private function done(string $message)
    {
        $redirect = redirect()->to(site_url(self::URI))->with('success', $message);

        $total = model(CriterionModel::class)->totalActiveWeight();
        if (abs($total - 100) >= 0.005) {
            $redirect->with('warning', 'Total bobot default kriteria aktif saat ini ' . num_id($total)
                . '%, belum 100%. Gunakan tombol "Atur Bobot Default" untuk menyesuaikannya.');
        }

        return $redirect;
    }

    private function fail(string $message)
    {
        return redirect()->to(site_url(self::URI))->with('error', $message);
    }

    private function findOr404(int $id): array
    {
        $criterion = model(CriterionModel::class)->find($id);

        if ($criterion === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $criterion;
    }
}
