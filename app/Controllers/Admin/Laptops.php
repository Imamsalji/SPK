<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BrandModel;
use App\Models\CriterionModel;
use App\Models\LaptopModel;
use App\Services\ScoreService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use Throwable;

class Laptops extends BaseController
{
    private const URI        = 'admin/laptop';
    private const UPLOAD_DIR = 'uploads/laptops/';

    public function index()
    {
        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'brand'  => (string) $this->request->getGet('brand'),
            'status' => (string) $this->request->getGet('status'),
        ];

        $model   = model(LaptopModel::class);
        $laptops = $model->listing($filters)
            ->orderBy('laptops.created_at', 'DESC')
            ->orderBy('laptops.id', 'DESC')
            ->paginate(10);

        return view('admin/laptops/index', [
            'title'          => 'Data Laptop',
            'laptops'        => $laptops,
            'pager'          => $model->pager,
            'filters'        => $filters,
            'brands'         => model(BrandModel::class)->orderBy('name', 'ASC')->findAll(),
            'activeCriteria' => model(CriterionModel::class)->where('is_active', 1)->countAllResults(),
        ]);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($this->findOr404($id));
    }

    public function destroy(int $id)
    {
        $laptop = $this->findOr404($id);

        model(LaptopModel::class)->delete($laptop['id']);
        $this->deleteImage($laptop['image']);

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Laptop "' . $laptop['name'] . '" berhasil dihapus. Riwayat perhitungan yang sudah tersimpan tidak berubah.');
    }

    // ------------------------------------------------------------------

    private function save(?array $laptop)
    {
        $id   = $laptop['id'] ?? null;
        $mode = $laptop ? 'edit' : 'create';

        $file    = $this->request->getFile('image');
        $hasFile = $file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE;

        $input = [
            'brand_id'       => trim((string) $this->request->getPost('brand_id')),
            'name'           => trim((string) $this->request->getPost('name')),
            'model'          => trim((string) $this->request->getPost('model')),
            'price'          => trim((string) $this->request->getPost('price')),
            'processor_name' => trim((string) $this->request->getPost('processor_name')),
            'description'    => trim((string) $this->request->getPost('description')),
            'is_active'      => (string) $this->request->getPost('is_active') === '1' ? '1' : '0',
        ];
        $old = $input + ['image_url' => $laptop ? (laptop_image_url($laptop['image']) ?? '') : ''];

        $model  = model(LaptopModel::class);
        $errors = [];

        if (! $this->validate($this->rules($hasFile))) {
            $errors = $this->validator->getErrors();
        } elseif ($model->isDuplicate((int) $input['brand_id'], $input['name'], $input['model'], $id)) {
            $errors['name'] = 'Laptop dengan merek, nama, dan model yang sama sudah ada.';
        }

        if ($errors !== []) {
            return redirect_form_errors(self::URI, $mode, $id, $errors, $old);
        }

        $data = [
            'brand_id'       => (int) $input['brand_id'],
            'name'           => $input['name'],
            'model'          => $input['model'],
            'price'          => (float) $input['price'],
            'processor_name' => $input['processor_name'] !== '' ? $input['processor_name'] : null,
            'description'    => $input['description'] !== '' ? $input['description'] : null,
            'is_active'      => (int) $input['is_active'],
        ];

        $newImage = null;
        if ($hasFile) {
            $newImage      = $this->storeImage($file);
            $data['image'] = $newImage;
        } elseif ($laptop && $this->request->getPost('remove_image') === '1') {
            $data['image'] = null;
        }

        // Simpan laptop + sinkronkan nilai kriteria harga dalam satu transaksi.
        $db       = db_connect();
        $laptopId = (int) $id;

        try {
            $db->transBegin();

            $saved = $laptop ? $model->update($id, $data) : $model->insert($data);
            if ($saved === false) {
                throw new RuntimeException('Gagal menyimpan data laptop.');
            }

            $laptopId = $laptop ? (int) $id : (int) $saved;

            if (! (new ScoreService())->syncPriceForLaptop($laptopId, (float) $data['price'])) {
                throw new RuntimeException('Gagal menyinkronkan nilai harga.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Laptops::save: ' . $e->getMessage());
            $this->deleteImage($newImage);

            return redirect_form_errors(self::URI, $mode, $id, ['name' => 'Data gagal disimpan. Silakan coba lagi.'], $old);
        }

        // Hapus file lama hanya setelah data baru berhasil tersimpan.
        if ($laptop && array_key_exists('image', $data)) {
            $this->deleteImage($laptop['image']);
        }

        if (! $laptop) {
            return redirect()->to(site_url('admin/laptop/' . $laptopId . '/nilai'))
                ->with('success', 'Laptop "' . $input['name'] . '" berhasil ditambahkan. Lanjutkan dengan mengisi nilai kriterianya.');
        }

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Laptop "' . $input['name'] . '" berhasil diperbarui.');
    }

    private function rules(bool $withImage): array
    {
        $rules = [
            'brand_id' => [
                'label'  => 'Merek',
                'rules'  => 'required|is_natural_no_zero|is_not_unique[brands.id]',
                'errors' => [
                    'required'          => 'Merek wajib dipilih.',
                    'is_natural_no_zero' => 'Merek tidak valid.',
                    'is_not_unique'     => 'Merek tidak ditemukan.',
                ],
            ],
            'name' => [
                'label'  => 'Nama laptop',
                'rules'  => 'required|min_length[3]|max_length[150]',
                'errors' => [
                    'required'   => 'Nama laptop wajib diisi.',
                    'min_length' => 'Nama laptop minimal 3 karakter.',
                    'max_length' => 'Nama laptop maksimal 150 karakter.',
                ],
            ],
            'model' => [
                'label'  => 'Model',
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Model wajib diisi.',
                    'max_length' => 'Model maksimal 100 karakter.',
                ],
            ],
            'price' => [
                'label'  => 'Harga',
                'rules'  => 'required|numeric|greater_than[0]|less_than_equal_to[9999999999999]',
                'errors' => [
                    'required'           => 'Harga wajib diisi.',
                    'numeric'            => 'Harga harus berupa angka.',
                    'greater_than'       => 'Harga harus lebih dari 0.',
                    'less_than_equal_to' => 'Harga terlalu besar.',
                ],
            ],
            'processor_name' => [
                'label'  => 'Prosesor',
                'rules'  => 'permit_empty|max_length[150]',
                'errors' => ['max_length' => 'Nama prosesor maksimal 150 karakter.'],
            ],
            'description' => [
                'label'  => 'Deskripsi',
                'rules'  => 'permit_empty|max_length[2000]',
                'errors' => ['max_length' => 'Deskripsi maksimal 2000 karakter.'],
            ],
            'is_active' => [
                'label'  => 'Status',
                'rules'  => 'required|in_list[0,1]',
                'errors' => [
                    'required' => 'Status wajib dipilih.',
                    'in_list'  => 'Status tidak valid.',
                ],
            ],
        ];

        if ($withImage) {
            $rules['image'] = [
                'label'  => 'Gambar',
                'rules'  => 'uploaded[image]|is_image[image]'
                    . '|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|ext_in[image,jpg,jpeg,png,webp]'
                    . '|max_size[image,2048]|max_dims[image,4000,4000]',
                'errors' => [
                    'uploaded' => 'Gambar gagal diunggah (periksa batas ukuran upload pada php.ini).',
                    'is_image' => 'File harus berupa gambar yang valid.',
                    'mime_in'  => 'Tipe gambar harus JPG, PNG, atau WEBP.',
                    'ext_in'   => 'Ekstensi gambar harus jpg, jpeg, png, atau webp.',
                    'max_size' => 'Ukuran gambar maksimal 2 MB.',
                    'max_dims' => 'Dimensi gambar maksimal 4000 x 4000 piksel.',
                ],
            ];
        }

        return $rules;
    }

    private function storeImage(UploadedFile $file): string
    {
        $dir = FCPATH . self::UPLOAD_DIR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = $file->getRandomName();
        $file->move($dir, $name);

        return $name;
    }

    private function deleteImage(?string $name): void
    {
        if ($name === null || $name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            return;
        }

        $path = FCPATH . self::UPLOAD_DIR . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function findOr404(int $id): array
    {
        $laptop = model(LaptopModel::class)->find($id);

        if ($laptop === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $laptop;
    }
}
