<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$isAdmin = auth_is('admin');
$cards   = [
    ['Total Laptop Aktif', $stats['laptops'],      'bi-laptop',        'primary'],
    ['Total Merek',        $stats['brands'],       'bi-tags',          'success'],
    ['Kriteria Aktif',     $stats['criteria'],     'bi-sliders',       'warning'],
    [$isAdmin ? 'Total Perhitungan' : 'Perhitungan Saya', $stats['calculations'], 'bi-calculator', 'info'],
];
?>
<div class="card border-0 shadow-sm mb-4 welcome-card">
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Halo, <?= esc($user['name']) ?> 👋</h2>
        <p class="mb-0 opacity-75">
            <?= $isAdmin
                ? 'Kelola data laptop, kriteria, dan pantau seluruh hasil perhitungan SAW.'
                : 'Bandingkan laptop dan temukan pilihan terbaik sesuai prioritas Anda.' ?>
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= esc((string) $value) ?></div>
                        <div class="text-body-secondary small"><?= esc($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Grafik, rekomendasi, dan ringkasan aktivitas akan ditambahkan pada Tahap 9.
</div>
<?= $this->endSection() ?>