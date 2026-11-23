<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['auth', 'url']);

        $session = session();
        $current = auth_user();

        if ($current === null) {
            return $this->deny($request, 'Silakan login terlebih dahulu.');
        }

        // Muat ulang dari DB: akun yang dihapus/dinonaktifkan langsung ditolak.
        $user = model(UserModel::class)->findWithRole($current['id']);

        if ($user === null || (int) $user['is_active'] !== 1) {
            $session->remove('auth_user');

            return $this->deny($request, 'Sesi Anda tidak valid atau akun dinonaktifkan.');
        }

        // Sinkronkan data session (nama/role bisa berubah).
        $session->set('auth_user', UserModel::toSession($user));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function deny(RequestInterface $request, string $message)
    {
        // Simpan tujuan awal (hanya GET) agar setelah login kembali ke sana.
        if (strtolower($request->getMethod()) === 'get') {
            session()->set('intended_url', uri_string());
        }

        return redirect()->to(site_url('login'))->with('warning', $message);
    }
}
