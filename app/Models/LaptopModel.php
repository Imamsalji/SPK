<?php

namespace App\Models;

use CodeIgniter\Model;

class LaptopModel extends Model
{
    protected $table         = 'laptops';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'brand_id',
        'name',
        'model',
        'price',
        'processor_name',
        'image',
        'description',
        'is_active',
        'is_demo',
    ];

    /**
     * Daftar laptop + nama merek + jumlah nilai kriteria aktif yang sudah terisi.
     * $filters: q (string), brand (id), status ('1' aktif / '0' nonaktif / '' semua)
     */
    public function listing(array $filters = []): static
    {
        $this->select(
            'laptops.*, brands.name AS brand_name, '
                . '(SELECT COUNT(*) FROM laptop_scores ls '
                . 'JOIN criteria c ON c.id = ls.criterion_id '
                . 'WHERE ls.laptop_id = laptops.id AND c.is_active = 1) AS score_count',
            false
        )->join('brands', 'brands.id = laptops.brand_id');

        $q      = trim((string) ($filters['q'] ?? ''));
        $brand  = (string) ($filters['brand'] ?? '');
        $status = (string) ($filters['status'] ?? '');

        if ($q !== '') {
            $this->groupStart()
                ->like('laptops.name', $q)
                ->orLike('laptops.model', $q)
                ->orLike('brands.name', $q)
                ->groupEnd();
        }
        if ($brand !== '' && ctype_digit($brand)) {
            $this->where('laptops.brand_id', (int) $brand);
        }
        if ($status === '0' || $status === '1') {
            $this->where('laptops.is_active', (int) $status);
        }

        return $this;
    }

    /** Kombinasi merek + nama + model harus unik. */
    public function isDuplicate(int $brandId, string $name, string $model, ?int $ignoreId = null): bool
    {
        $this->where('brand_id', $brandId)->where('name', $name)->where('model', $model);

        if ($ignoreId !== null) {
            $this->where('id !=', $ignoreId);
        }

        return $this->countAllResults() > 0;
    }
}
