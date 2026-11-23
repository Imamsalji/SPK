<?php

namespace App\Models;

use CodeIgniter\Model;

class CriterionModel extends Model
{
    protected $table         = 'criteria';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code',
        'name',
        'description',
        'type',
        'unit',
        'default_weight',
        'sort_order',
        'is_active',
    ];

    public function ordered(): static
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('code', 'ASC');
    }

    /** Total bobot default kriteria aktif (persen). */
    public function totalActiveWeight(): float
    {
        $row = $this->db->table($this->table)
            ->selectSum('default_weight', 'total')
            ->where('is_active', 1)
            ->get()->getRowArray();

        return round((float) ($row['total'] ?? 0), 2);
    }

    public function nextSortOrder(): int
    {
        $row = $this->db->table($this->table)
            ->selectMax('sort_order', 'max_order')
            ->get()->getRowArray();

        return (int) ($row['max_order'] ?? 0) + 1;
    }
}
