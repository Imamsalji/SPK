<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BrandModel;
use App\Models\LaptopModel;
use App\Services\ScoreService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Scores extends BaseController
{
    private const URI = 'admin/nilai';

    /** Ringkasan matriks nilai semua laptop. */
    public function index()
    {
        $service = new ScoreService();

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'brand'    => (string) $this->request->getGet('brand'),
            'complete' => (string) $this->request->getGet('complete'),
        ];

        $summary = $service->summary(); // sebelum query utama

        $model = model(LaptopModel::class);
        $model->listing(['q' => $filters['q'], 'brand' => $filters['brand']]);

        if ($filters['complete'] === '1' || $filters['complete'] === '0') {
            $model->where($service->completenessCondition($filters['complete'] === '1'), null, false);
        }

        $laptops = $model->orderBy('brands.name', 'ASC')->orderBy('laptops.name', 'ASC')->paginate(15);

        return view('admin/scores/index', [
            'title'    => 'Penilaian Alternatif',
            'laptops'  => $laptops,
            'pager'    => $model->pager,
            'filters'  => $filters,
            'brands'   => model(BrandModel::class)->orderBy('name', 'ASC')->findAll(),
            'criteria' => $service->activeCriteria(),
            'scores'   => $service->scoresForLaptops(array_map('intval', array_column($laptops, 'id'))),
            'summary'  => $summary,
        ]);
    }

    /** Form nilai untuk satu laptop. */
    public function edit(int $id)
    {
        $laptop  = $this->findLaptop($id);
        $service = new ScoreService();

        return view('admin/scores/form', [
            'title'    => 'Nilai Alternatif',
            'laptop'   => $laptop,
            'criteria' => $service->activeCriteria(),
            'scores'   => $service->scoresForLaptop($id),
        ]);
    }

    public function update(int $id)
    {
        $laptop   = $this->findLaptop($id);
        $service  = new ScoreService();
        $criteria = $service->activeCriteria();
        $formUri  = 'admin/laptop/' . $laptop['id'] . '/nilai';

        $values = $this->request->getPost('values');
        $notes  = $this->request->getPost('notes');

        $result = $service->validateInput(
            $criteria,
            is_array($values) ? $values : [],
            is_array($notes) ? $notes : []
        );

        if ($result['errors'] !== []) {
            return redirect()->to(site_url($formUri))
                ->with('form_errors', $result['errors'])
                ->with('old_scores', $result['old'])
                ->with('error', 'Ada isian yang belum valid. Periksa kembali kolom yang ditandai merah.');
        }

        if (! $service->saveLaptopScores((int) $laptop['id'], $result['clean'])) {
            return redirect()->to(site_url($formUri))
                ->with('old_scores', $result['old'])
                ->with('error', 'Nilai gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Nilai laptop "' . $laptop['name'] . '" berhasil disimpan.');
    }

    private function findLaptop(int $id): array
    {
        $laptop = model(LaptopModel::class)->listing()->where('laptops.id', $id)->first();

        if ($laptop === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $laptop;
    }
}
