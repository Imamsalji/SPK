<?php

namespace App\Controllers;

use App\Models\CalculationModel;
use App\Services\CalculationService;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Riwayat dan hasil perhitungan tersimpan. Admin melihat semua; mahasiswa hanya miliknya. */
class Calculations extends BaseController
{
    /** Daftar riwayat. */
    public function index()
    {
        $viewer  = auth_user();
        $filters = [
            'q'    => trim((string) $this->request->getGet('q')),
            'user' => (string) $this->request->getGet('user'),
        ];

        $model        = model(CalculationModel::class);
        $calculations = $model->listing($viewer, $filters)
            ->orderBy('calculations.calculated_at', 'DESC')
            ->orderBy('calculations.id', 'DESC')
            ->paginate(10);

        return view('calculations/index', [
            'title'        => 'Riwayat Perhitungan',
            'calculations' => $calculations,
            'pager'        => $model->pager,
            'filters'      => $filters,
            'users'        => auth_is('admin') ? (new CalculationService())->usersWithCalculations() : [],
        ]);
    }

    /** Detail satu perhitungan. Milik orang lain (bagi mahasiswa) menghasilkan 404. */
    public function show(int $id)
    {
        $data = (new CalculationService())->find($id, auth_user());

        if ($data === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->detail('Detail Perhitungan', $data, false);
    }

    /** Hasil perhitungan terbaru dalam cakupan pengguna. */
    public function latest()
    {
        $viewer = auth_user();

        $row = model(CalculationModel::class)
            ->visibleTo($viewer)
            ->orderBy('calculations.calculated_at', 'DESC')
            ->orderBy('calculations.id', 'DESC')
            ->first();

        $data = $row === null ? null : (new CalculationService())->find((int) $row['id'], $viewer);

        if ($data === null) {
            return view('calculations/empty', ['title' => 'Hasil Perhitungan']);
        }

        return $this->detail('Hasil Perhitungan', $data, true);
    }

    private function detail(string $title, array $data, bool $isLatest)
    {
        return view('calculations/show', [
            'title'          => $title,
            'isLatest'       => $isLatest,
            'calc'           => $data['calculation'],
            'result'         => $data['result'],
            'meta'           => $data['meta'],
            'orphanLaptops'  => $data['orphan_laptops'],
            'orphanCriteria' => $data['orphan_criteria'],
        ]);
    }
}
