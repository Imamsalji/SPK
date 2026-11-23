<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    private const MAX_ATTEMPTS = 5;      // per menit, per IP + username

    public function login()
    {
        return view('auth/login', ['title' => 'Masuk']);
    }

    public function attempt()
    {
        $rules = [
            'username' => 'required|max_length[50]',
            'password' => 'required|max_length[255]',
        ];

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('login'))
                ->with('errors', $this->validator->getErrors())
                ->with('old_username', $username);
        }

        // Pembatasan percobaan login (anti brute force).
        $throttler = service('throttler');
        $key       = 'login_' . md5($this->request->getIPAddress() . '|' . strtolower($username));

        if ($throttler->check($key, self::MAX_ATTEMPTS, MINUTE) === false) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam ' . $throttler->getTokenTime() . ' detik.')
                ->with('old_username', $username);
        }

        $users = model(UserModel::class);
        $user  = $users->findByUsername($username);

        // Tetap lakukan operasi hash walau user tidak ada (waktu respons seragam).
        $hash       = $user['password_hash'] ?? password_hash('dummy-password', PASSWORD_DEFAULT);
        $passwordOk = password_verify($password, $hash);

        if ($user === null || ! $passwordOk) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Username atau password salah.')
                ->with('old_username', $username);
        }

        if ((int) $user['is_active'] !== 1) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Akun Anda dinonaktifkan. Hubungi administrator.')
                ->with('old_username', $username);
        }

        // Rehash otomatis jika algoritma/cost default berubah.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->update($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $session = session();
        $session->regenerate(true);
        $session->set('auth_user', UserModel::toSession($user));
        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $intended = $session->get('intended_url');
        $session->remove('intended_url');

        if (! is_string($intended) || $intended === '' || str_starts_with($intended, 'login')) {
            $intended = 'dashboard';
        }

        return redirect()->to(site_url($intended))
            ->with('success', 'Selamat datang, ' . $user['name'] . '.');
    }

    public function logout()
    {
        $session = session();
        $session->remove(['auth_user', 'intended_url']);
        $session->regenerate(true);

        return redirect()->to(site_url('login'))->with('success', 'Anda telah keluar.');
    }
}

