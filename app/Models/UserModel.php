<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'role_id',
        'name',
        'username',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
    ];

    public function findByUsername(string $username): ?array
    {
        return $this->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.username', $username)
            ->first();
    }

    public function findWithRole(int $id): ?array
    {
        return $this->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id)
            ->first();
    }

    /** Data minimal yang disimpan di session (tanpa password hash). */
    public static function toSession(array $user): array
    {
        return [
            'id'       => (int) $user['id'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'role'     => $user['role_name'],
        ];
    }

    /** Daftar untuk halaman admin. Sengaja TIDAK memuat password_hash. */
    public function listing(string $keyword = '', ?int $roleId = null): static
    {
        $this->select('users.id, users.role_id, users.name, users.username, users.email, '
            . 'users.is_active, users.last_login_at, users.created_at, '
            . 'roles.name AS role_name, roles.label AS role_label')
            ->join('roles', 'roles.id = users.role_id');

        if ($keyword !== '') {
            $this->groupStart()
                ->like('users.name', $keyword)
                ->orLike('users.username', $keyword)
                ->orLike('users.email', $keyword)
                ->groupEnd();
        }
        if ($roleId !== null) {
            $this->where('users.role_id', $roleId);
        }

        return $this;
    }

    public function countActiveAdmins(?int $excludeId = null): int
    {
        $this->join('roles', 'roles.id = users.role_id')
            ->where('roles.name', 'admin')
            ->where('users.is_active', 1);

        if ($excludeId !== null) {
            $this->where('users.id !=', $excludeId);
        }

        return $this->countAllResults();
    }
}
