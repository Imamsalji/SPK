<?php

namespace App\Controllers;

/** Placeholder sementara untuk menu yang belum dibuat. Dihapus bertahap di Tahap 8 dan 10. */
class ComingSoon extends BaseController
{
    private const PAGES = [
        'results' => ['Hasil Perhitungan', 'bi-trophy',            'Tahap 8'],
        'history' => ['Riwayat',           'bi-clock-history',     'Tahap 8'],
        'reports' => ['Laporan',           'bi-file-earmark-text', 'Tahap 10'],
    ];

    public function results()
    {
        return $this->page('results');
    }
    public function history()
    {
        return $this->page('history');
    }
    public function reports()
    {
        return $this->page('reports');
    }

    private function page(string $key)
    {
        [$title, $icon, $stage] = self::PAGES[$key];

        return view('pages/coming_soon', [
            'title' => $title,
            'icon'  => $icon,
            'stage' => $stage,
        ]);
    }
}
