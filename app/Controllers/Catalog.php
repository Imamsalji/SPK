<?php

namespace App\Controllers;

use App\Models\BrandModel;
use App\Models\LaptopModel;
use App\Models\LaptopScoreModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Katalog baca-saja untuk mahasiswa. Hanya laptop berstatus aktif. */
class Catalog extends BaseController
{
    public function index()
    {
        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'brand'  => (string) $this->request->getGet('brand'),
            'status' => '1',
        ];

        $model   = model(LaptopModel::class);
        $laptops = $model->listing($filters)
            ->orderBy('brands.name', 'ASC')
            ->orderBy('laptops.name', 'ASC')
            ->paginate(9);

        return view('catalog/index', [
            'title'   => 'Katalog Laptop',
            'laptops' => $laptops,
            'pager'   => $model->pager,
            'filters' => $filters,
            'brands'  => model(BrandModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function show(int $id)
    {
        $laptop = model(LaptopModel::class)->listing(['status' => '1'])
            ->where('laptops.id', $id)
            ->first();

        if ($laptop === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('catalog/show', [
            'title'  => 'Detail Laptop',
            'laptop' => $laptop,
            'scores' => model(LaptopScoreModel::class)->forLaptop($id),
        ]);
    }
}
