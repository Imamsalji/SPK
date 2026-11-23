<?php

namespace App\Models;

use CodeIgniter\Model;

class CalculationModel extends Model
{
    protected $table         = 'calculations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id',
        'name',
        'method',
        'user_name_snapshot',
        'total_alternatives',
        'total_criteria',
        'total_weight',
        'best_alternative_name',
        'best_score',
        'notes',
        'calculated_at',
    ];
}
