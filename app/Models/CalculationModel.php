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

    /**
     * Batasi data sesuai pemilik. Admin melihat semua; selain admin hanya miliknya.
     * Bila ID pengguna tidak ada, hasilnya kosong (fail-closed).
     *
     * @param array{id?:int|string, role?:string} $viewer
     */
    public function visibleTo(array $viewer): static
    {
        if (($viewer['role'] ?? '') !== 'admin') {
            $this->where('calculations.user_id', (int) ($viewer['id'] ?? 0));
        }

        return $this;
    }

    /**
     * Daftar riwayat. $filters: q (nama simulasi / laptop terbaik / nama pengguna), user (ID, khusus admin).
     */
    public function listing(array $viewer, array $filters = []): static
    {
        $this->visibleTo($viewer);

        $q    = trim((string) ($filters['q'] ?? ''));
        $user = (string) ($filters['user'] ?? '');

        if ($q !== '') {
            $this->groupStart()
                ->like('calculations.name', $q)
                ->orLike('calculations.best_alternative_name', $q)
                ->orLike('calculations.user_name_snapshot', $q)
                ->groupEnd();
        }

        if (($viewer['role'] ?? '') === 'admin' && $user !== '' && ctype_digit($user)) {
            $this->where('calculations.user_id', (int) $user);
        }

        return $this;
    }
}
