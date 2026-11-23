<?php

namespace App\Models;

use CodeIgniter\Model;

class BrandModel extends Model
{
    protected $table         = 'brands';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name'];

    /** Daftar merek beserta jumlah laptop yang memakainya. */
    public function listing(string $keyword = ''): static
    {
        $this->select('brands.*, (SELECT COUNT(*) FROM laptops WHERE laptops.brand_id = brands.id) AS laptop_count', false);

        if ($keyword !== '') {
            $this->like('brands.name', $keyword);
        }

        return $this;
    }
}
