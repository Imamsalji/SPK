<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BrandModel;
use App\Models\LaptopModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Brands extends BaseController
{
    private const URI = 'admin/merek';

    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $model   = model(BrandModel::class);

        $brands = $model->listing($keyword)->orderBy('brands.name', 'ASC')->paginate(10);

        return view('admin/brands/index', [
            'title'   => 'Merek Laptop',
            'brands'  => $brands,
            'pager'   => $model->pager,
            'keyword' => $keyword,
        ]);
    }

    public function store()
    {
        $name = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->rules())) {
            return redirect_form_errors(self::URI, 'create', null, $this->validator->getErrors(), ['name' => $name]);
        }

        model(BrandModel::class)->insert(['name' => $name]);

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Merek "' . $name . '" berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $brand = $this->findOr404($id);
        $name  = trim((string) $this->request->getPost('name'));

        if (! $this->validateData(['name' => $name], $this->rules($id))) {
            return redirect_form_errors(self::URI, 'edit', $id, $this->validator->getErrors(), ['name' => $name]);
        }

        model(BrandModel::class)->update($brand['id'], ['name' => $name]);

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Merek berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $brand = $this->findOr404($id);

        $used = model(LaptopModel::class)->where('brand_id', $brand['id'])->countAllResults();
        if ($used > 0) {
            return redirect()->to(site_url(self::URI))
                ->with('error', 'Merek "' . $brand['name'] . '" masih dipakai oleh ' . $used . ' laptop dan tidak dapat dihapus.');
        }

        model(BrandModel::class)->delete($brand['id']);

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Merek "' . $brand['name'] . '" berhasil dihapus.');
    }

    private function rules(?int $ignoreId = null): array
    {
        $unique = $ignoreId === null
            ? 'is_unique[brands.name]'
            : 'is_unique[brands.name,id,' . $ignoreId . ']';

        return [
            'name' => [
                'label'  => 'Nama merek',
                'rules'  => 'required|min_length[2]|max_length[100]|' . $unique,
                'errors' => [
                    'required'   => 'Nama merek wajib diisi.',
                    'min_length' => 'Nama merek minimal 2 karakter.',
                    'max_length' => 'Nama merek maksimal 100 karakter.',
                    'is_unique'  => 'Nama merek sudah digunakan.',
                ],
            ],
        ];
    }

    private function findOr404(int $id): array
    {
        $brand = model(BrandModel::class)->find($id);

        if ($brand === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $brand;
    }
}
