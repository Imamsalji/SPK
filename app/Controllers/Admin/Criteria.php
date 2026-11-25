<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CriterionModel;
use App\Services\ScoreService;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;

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

        $criterionId = $this->persist(null, $input);
        if ($criterionId === false) {
            return redirect_form_errors(self::URI, 'create', null, ['code' => 'Data gagal disimpan. Silakan coba lagi.'], $input);
        }

        return $this->done('Kriteria ' . $input['code'] . ' berhasil ditambahkan.', $criterionId);
    }

    public function update(int $id)
    {
        $criterion = $this->findOr404($id);
        $input     = $this->collect();

        if (! $this->validateData($input, $this->rules($id))) {
            return redirect_form_errors(self::URI, 'edit', $id, $this->validator->getErrors(), $input);
        }

        if ($this->persist((int) $criterion['id'], $input) === false) {
            return redirect_form_errors(self::URI, 'edit', $id, ['code' => 'Data gagal disimpan. Silakan coba lagi.'], $input);
        }

        return $this->done('Kriteria ' . $input['code'] . ' berhasil diperbarui.', (int) $criterion['id']);
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

    /**
     * Simpan kriteria. Bila kriteria ini adalah sumber harga, seluruh nilai harga laptop
     * disinkronkan dalam transaksi yang sama.
     *
     * @return int|false ID kriteria, atau false bila gagal
     */
    private function persist(?int $id, array $input): int|false
    {
        $db    = db_connect();
        $model = model(CriterionModel::class);
        $row   = $this->toRow($input);

        try {
            $db->transBegin();

            if ($id === null) {
                $saved = $model->insert($row);
                if ($saved === false) {
                    throw new RuntimeException('Gagal menyimpan kriteria.');
                }
                $criterionId = (int) $saved;
            } else {
                if ($model->update($id, $row) === false) {
                    throw new RuntimeException('Gagal memperbarui kriteria.');
                }
                $criterionId = $id;
            }

            if ($row['source_field'] === ScoreService::PRICE_FIELD) {
                (new ScoreService())->syncPriceForAllLaptops();
            }

            $db->transCommit();

            return $criterionId;
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Criteria::persist: ' . $e->getMessage());

            return false;
        }
    }

    private function collect(): array
    {
        return [
            'code'           => strtoupper(trim((string) $this->request->getPost('code'))),
            'name'           => trim((string) $this->request->getPost('name')),
            'description'    => trim((string) $this->request->getPost('description')),
            'type'           => (string) $this->request->getPost('type'),
            'unit'           => trim((string) $this->request->getPost('unit')),
            'source_field'   => (string) $this->request->getPost('source_field'),
            'require_source' => (string) $this->request->getPost('require_source') === '1' ? '1' : '0',
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
            'source_field'   => $input['source_field'] !== '' ? $input['source_field'] : null,
            'require_source' => (int) $input['require_source'],
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

        $uniqueSource = $ignoreId === null
            ? 'is_unique[criteria.source_field]'
            : 'is_unique[criteria.source_field,id,' . $ignoreId . ']';

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
            'source_field' => [
                'label'  => 'Sumber nilai',
                'rules'  => 'permit_empty|in_list[price]|' . $uniqueSource,
                'errors' => [
                    'in_list'   => 'Sumber nilai tidak valid.',
                    'is_unique' => 'Sudah ada kriteria lain yang memakai harga laptop sebagai sumber nilai.',
                ],
            ],
            'require_source' => [
                'label'  => 'Wajib sumber data',
                'rules'  => 'required|in_list[0,1]',
                'errors' => ['required' => 'Pilihan wajib sumber data harus diisi.', 'in_list' => 'Pilihan tidak valid.'],
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

    /**
     * Redirect sukses. Menambahkan peringatan bila total bobot aktif belum 100%,
     * dan info bila ada laptop yang belum punya nilai untuk kriteria aktif ini.
     */
    private function done(string $message, ?int $criterionId = null)
    {
        $redirect = redirect()->to(site_url(self::URI))->with('success', $message);

        $total = model(CriterionModel::class)->totalActiveWeight();
        if (abs($total - 100) >= 0.005) {
            $redirect->with('warning', 'Total bobot default kriteria aktif saat ini ' . num_id($total)
                . '%, belum 100%. Gunakan tombol "Atur Bobot Default" untuk menyesuaikannya.');
        }

        if ($criterionId !== null) {
            $criterion = model(CriterionModel::class)->find($criterionId);

            if ($criterion !== null && (int) $criterion['is_active'] === 1) {
                $missing = (new ScoreService())->laptopsMissingCriterion($criterionId);

                if ($missing > 0) {
                    $redirect->with('info', $missing . ' laptop belum memiliki nilai untuk kriteria '
                        . $criterion['code'] . '. Lengkapi di menu Penilaian Alternatif.');
                }
            }
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
