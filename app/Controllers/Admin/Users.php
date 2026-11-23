<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CalculationModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Users extends BaseController
{
    private const URI = 'admin/pengguna';

    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $role    = (string) $this->request->getGet('role');

        $model = model(UserModel::class);
        $users = $model->listing($keyword, ctype_digit($role) ? (int) $role : null)
            ->orderBy('users.name', 'ASC')
            ->paginate(10);

        return view('admin/users/index', [
            'title'   => 'Manajemen Pengguna',
            'users'   => $users,
            'pager'   => $model->pager,
            'keyword' => $keyword,
            'role'    => $role,
            'roles'   => model(RoleModel::class)->orderBy('id', 'ASC')->findAll(),
            'selfId'  => (int) auth_user()['id'],
        ]);
    }

    public function store()
    {
        $input  = $this->collect();
        $errors = $this->check($input, null);

        if ($errors !== []) {
            return redirect_form_errors(self::URI, 'create', null, $errors, $this->withoutPassword($input));
        }

        model(UserModel::class)->insert($this->toRow($input));

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Pengguna "' . $input['username'] . '" berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $target = $this->findOr404($id);
        $input  = $this->collect();
        $errors = $this->check($input, $target);

        if ($errors !== []) {
            return redirect_form_errors(self::URI, 'edit', $id, $errors, $this->withoutPassword($input));
        }

        model(UserModel::class)->update($id, $this->toRow($input));

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Data pengguna "' . $input['username'] . '" berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $target = $this->findOr404($id);
        $users  = model(UserModel::class);

        if ($id === (int) auth_user()['id']) {
            return $this->fail('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($target['role_name'] === 'admin' && (int) $target['is_active'] === 1 && $users->countActiveAdmins($id) === 0) {
            return $this->fail('Tidak dapat menghapus admin aktif terakhir.');
        }

        $calculations = model(CalculationModel::class)->where('user_id', $id)->countAllResults();
        if ($calculations > 0) {
            return $this->fail('Pengguna "' . $target['username'] . '" memiliki ' . $calculations
                . ' riwayat perhitungan dan tidak dapat dihapus. Nonaktifkan saja akunnya.');
        }

        $users->delete($id);

        return redirect()->to(site_url(self::URI))
            ->with('success', 'Pengguna "' . $target['username'] . '" berhasil dihapus.');
    }

    // ------------------------------------------------------------------

    private function collect(): array
    {
        return [
            'name'      => trim((string) $this->request->getPost('name')),
            'username'  => strtolower(trim((string) $this->request->getPost('username'))),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'role_id'   => trim((string) $this->request->getPost('role_id')),
            'is_active' => (string) $this->request->getPost('is_active') === '1' ? '1' : '0',
            'password'  => (string) $this->request->getPost('password'),
        ];
    }

    private function withoutPassword(array $input): array
    {
        unset($input['password']);

        return $input;
    }

    private function toRow(array $input): array
    {
        $row = [
            'role_id'   => (int) $input['role_id'],
            'name'      => $input['name'],
            'username'  => $input['username'],
            'email'     => $input['email'] !== '' ? $input['email'] : null,   // NULL agar tidak bentrok unique
            'is_active' => (int) $input['is_active'],
        ];

        if ($input['password'] !== '') {
            $row['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        return $row;
    }

    /** Validasi + aturan keamanan akun. Mengembalikan array error per field. */
    private function check(array $input, ?array $target): array
    {
        $id = $target['id'] ?? null;

        $uniqueUsername = $id === null ? 'is_unique[users.username]' : 'is_unique[users.username,id,' . $id . ']';
        $uniqueEmail    = $id === null ? 'is_unique[users.email]' : 'is_unique[users.email,id,' . $id . ']';

        $rules = [
            'name' => [
                'label'  => 'Nama',
                'rules'  => 'required|min_length[3]|max_length[100]',
                'errors' => [
                    'required'   => 'Nama wajib diisi.',
                    'min_length' => 'Nama minimal 3 karakter.',
                    'max_length' => 'Nama maksimal 100 karakter.',
                ],
            ],
            'username' => [
                'label'  => 'Username',
                'rules'  => 'required|alpha_dash|min_length[4]|max_length[50]|' . $uniqueUsername,
                'errors' => [
                    'required'   => 'Username wajib diisi.',
                    'alpha_dash' => 'Username hanya boleh huruf, angka, underscore, dan strip.',
                    'min_length' => 'Username minimal 4 karakter.',
                    'max_length' => 'Username maksimal 50 karakter.',
                    'is_unique'  => 'Username sudah digunakan.',
                ],
            ],
            'email' => [
                'label'  => 'Email',
                'rules'  => 'permit_empty|valid_email|max_length[150]|' . $uniqueEmail,
                'errors' => [
                    'valid_email' => 'Format email tidak valid.',
                    'max_length'  => 'Email maksimal 150 karakter.',
                    'is_unique'   => 'Email sudah digunakan.',
                ],
            ],
            'role_id' => [
                'label'  => 'Role',
                'rules'  => 'required|is_natural_no_zero|is_not_unique[roles.id]',
                'errors' => [
                    'required'           => 'Role wajib dipilih.',
                    'is_natural_no_zero' => 'Role tidak valid.',
                    'is_not_unique'      => 'Role tidak ditemukan.',
                ],
            ],
            'is_active' => [
                'label'  => 'Status',
                'rules'  => 'required|in_list[0,1]',
                'errors' => ['required' => 'Status wajib dipilih.', 'in_list' => 'Status tidak valid.'],
            ],
            'password' => [
                'label'  => 'Password',
                'rules'  => ($target === null ? 'required' : 'permit_empty') . '|min_length[8]|max_length[72]',
                'errors' => [
                    'required'   => 'Password wajib diisi.',
                    'min_length' => 'Password minimal 8 karakter.',
                    'max_length' => 'Password maksimal 72 karakter.',
                ],
            ],
        ];

        if (! $this->validateData($input, $rules)) {
            return $this->validator->getErrors();
        }

        $errors = [];

        if (
            $input['password'] !== ''
            && (! preg_match('/[A-Za-z]/', $input['password']) || ! preg_match('/\d/', $input['password']))
        ) {
            $errors['password'] = 'Password harus mengandung huruf dan angka.';
        }

        if ($target !== null) {
            $users       = model(UserModel::class);
            $newRole     = model(RoleModel::class)->find((int) $input['role_id']);
            $newIsAdmin  = ($newRole['name'] ?? '') === 'admin';
            $newIsActive = $input['is_active'] === '1';

            $wasActiveAdmin = $target['role_name'] === 'admin' && (int) $target['is_active'] === 1;

            if ((int) $target['id'] === (int) auth_user()['id']) {
                if (! $newIsActive) {
                    $errors['is_active'] = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
                }
                if (! $newIsAdmin) {
                    $errors['role_id'] = 'Anda tidak dapat mengubah role akun Anda sendiri.';
                }
            }

            if (
                $wasActiveAdmin && ! ($newIsAdmin && $newIsActive)
                && $users->countActiveAdmins((int) $target['id']) === 0
            ) {
                $errors['is_active'] ??= 'Minimal harus ada satu admin aktif.';
            }
        }

        return $errors;
    }

    private function fail(string $message)
    {
        return redirect()->to(site_url(self::URI))->with('error', $message);
    }

    private function findOr404(int $id): array
    {
        $user = model(UserModel::class)->findWithRole($id);

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $user;
    }
}
