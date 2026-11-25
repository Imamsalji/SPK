<?php

namespace App\Controllers;

/** Placeholder sementara untuk menu yang belum dibuat. Dihapus di Tahap 10. */
class ComingSoon extends BaseController
{
    public function reports()
    {
        return view('pages/coming_soon', [
            'title' => 'Laporan',
            'icon'  => 'bi-file-earmark-text',
            'stage' => 'Tahap 10',
        ]);
    }
}
