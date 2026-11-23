<?php
$role = auth_user()['role'] ?? '';

$both  = ['admin', 'mahasiswa'];
$admin = ['admin'];
$mhs   = ['mahasiswa'];

$menu = [
    ['type' => 'item',    'label' => 'Dashboard',          'url' => 'dashboard',      'icon' => 'bi-speedometer2',      'match' => 'dashboard*',      'roles' => $both],

    ['type' => 'heading', 'label' => 'Master Data',                                                                                                    'roles' => $admin],
    ['type' => 'item',    'label' => 'Merek Laptop',       'url' => 'admin/merek',    'icon' => 'bi-tags',              'match' => 'admin/merek*',    'roles' => $admin],
    ['type' => 'item',    'label' => 'Data Laptop',        'url' => 'admin/laptop',   'icon' => 'bi-laptop',            'match' => 'admin/laptop*',   'roles' => $admin],
    ['type' => 'item',    'label' => 'Kriteria',           'url' => 'admin/kriteria', 'icon' => 'bi-sliders',           'match' => 'admin/kriteria*', 'roles' => $admin],

    ['type' => 'heading', 'label' => 'Pemilihan Laptop',                                                                                               'roles' => $mhs],
    ['type' => 'item',    'label' => 'Katalog Laptop',     'url' => 'katalog',        'icon' => 'bi-grid',              'match' => 'katalog*',        'roles' => $mhs],
    ['type' => 'item',    'label' => 'Simulasi SPK',       'url' => 'simulasi',       'icon' => 'bi-calculator',        'match' => 'simulasi*',       'roles' => $mhs],

    ['type' => 'heading', 'label' => 'Analisis',                                                                                                       'roles' => $both],
    ['type' => 'item',    'label' => 'Hasil Perhitungan',  'url' => 'hasil',          'icon' => 'bi-trophy',            'match' => 'hasil*',          'roles' => $both],
    ['type' => 'item',    'label' => 'Riwayat',            'url' => 'riwayat',        'icon' => 'bi-clock-history',     'match' => 'riwayat*',        'roles' => $both],
    ['type' => 'item',    'label' => 'Laporan',            'url' => 'laporan',        'icon' => 'bi-file-earmark-text', 'match' => 'laporan*',        'roles' => $both],

    ['type' => 'heading', 'label' => 'Administrasi',                                                                                                   'roles' => $admin],
    ['type' => 'item',    'label' => 'Manajemen Pengguna', 'url' => 'admin/pengguna', 'icon' => 'bi-people',            'match' => 'admin/pengguna*', 'roles' => $admin],
];
?>
<aside class="sidebar" id="appSidebar" aria-label="Navigasi utama">
    <a href="<?= site_url('dashboard') ?>" class="sidebar-brand">
        <i class="bi bi-laptop"></i>
        <span>Laptop DSS</span>
    </a>

    <nav class="sidebar-nav">
        <?php foreach ($menu as $item): ?>
            <?php if (! in_array($role, $item['roles'], true)) {
                continue;
            } ?>

            <?php if ($item['type'] === 'heading'): ?>
                <div class="sidebar-heading"><?= esc($item['label']) ?></div>
            <?php else: ?>
                <a href="<?= site_url($item['url']) ?>"
                    class="sidebar-link<?= url_is($item['match']) ? ' active' : '' ?>">
                    <i class="bi <?= esc($item['icon'], 'attr') ?>"></i>
                    <span><?= esc($item['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">Metode SAW</div>
</aside>