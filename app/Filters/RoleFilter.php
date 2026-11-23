<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pemakaian di route: 'filter' => 'role:admin'  atau  'role:admin,mahasiswa'
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['auth', 'url']);

        // Pastikan sudah login & akun masih aktif.
        $authResult = (new AuthFilter())->before($request);
        if ($authResult !== null) {
            return $authResult;
        }

        // Fail-closed: tanpa argumen role, akses ditolak.
        if (empty($arguments) || ! auth_is(...$arguments)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/forbidden', ['title' => 'Akses ditolak']));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
