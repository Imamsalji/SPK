<?php

namespace App\Models;

use CodeIgniter\Model;

class LaptopScoreModel extends Model
{
    protected $table         = 'laptop_scores';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['laptop_id', 'criterion_id', 'value', 'source_note'];

    /** Nilai kriteria aktif milik satu laptop, urut sesuai kriteria. */
    public function forLaptop(int $laptopId): array
    {
        return $this->select('criteria.code, criteria.name, criteria.type, criteria.unit, laptop_scores.value, laptop_scores.source_note')
            ->join('criteria', 'criteria.id = laptop_scores.criterion_id')
            ->where('laptop_scores.laptop_id', $laptopId)
            ->where('criteria.is_active', 1)
            ->orderBy('criteria.sort_order', 'ASC')
            ->findAll();
    }
}
