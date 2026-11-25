<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$best     = $result['best'];
$bestMeta = $meta[$best['key']] ?? ['brand_name' => '', 'model' => '', 'price' => 0];
$isOwner  = (int) $calc['user_id'] === (int) auth_user()['id'];
?>

<div class="card border-0 shadow-sm mb-3 welcome-card">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-lg-7">
                <div class="small opacity-75 text-uppercase">Rekomendasi terbaik</div>
                <h2 class="h4 mb-1"><i class="bi bi-trophy-fill me-2"></i><?= esc($best['name']) ?></h2>
                <div class="opacity-75"><?= esc($bestMeta['brand_name']) ?> &middot; <?= esc($bestMeta['model']) ?> &middot; <?= rupiah($bestMeta['price']) ?></div>
            </div>
            <div class="col-lg-5 text-lg-end">
                <div class="small opacity-75">Nilai preferensi (V)</div>
                <div class="display-6 fw-bold" title="<?= esc(fmt_dec($best['preference'], 8), 'attr') ?>">
                    <?= fmt_dec($best['preference'], 4) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Nama simulasi</div>
                <div class="fw-semibold"><?= esc($calc['name']) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Pengguna</div>
                <div class="fw-semibold"><?= esc($calc['user_name_snapshot']) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Tanggal perhitungan</div>
                <div class="fw-semibold"><?= esc(date('d/m/Y H:i', strtotime($calc['calculated_at']))) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Alternatif / kriteria</div>
                <div class="fw-semibold"><?= (int) $calc['total_alternatives'] ?> / <?= (int) $calc['total_criteria'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-camera me-1"></i>
    Halaman ini menampilkan <strong>snapshot</strong> data pada saat perhitungan dilakukan. Perubahan pada data laptop,
    kriteria, atau bobot default tidak mempengaruhi hasil ini.
    <?php if ($isLatest): ?>
        Ini adalah perhitungan terbaru; lihat perhitungan lainnya di <a href="<?= site_url('riwayat') ?>" class="alert-link">Riwayat</a>.
    <?php endif; ?>
</div>

<?php if ($orphanLaptops > 0 || $orphanCriteria > 0): ?>
    <div class="alert alert-secondary">
        <i class="bi bi-archive me-1"></i>
        <?php if ($orphanLaptops > 0): ?><?= (int) $orphanLaptops ?> laptop pada perhitungan ini sudah dihapus dari data master. <?php endif; ?>
    <?php if ($orphanCriteria > 0): ?><?= (int) $orphanCriteria ?> kriteria pada perhitungan ini sudah dihapus dari data master. <?php endif; ?>
Datanya tetap tersimpan utuh di riwayat.
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= site_url('riwayat') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Semua riwayat</a>
    <?php if (auth_is('mahasiswa') && $isOwner): ?>
        <a href="<?= site_url('simulasi?ulang=' . (int) $calc['id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-sliders me-1"></i>Gunakan ulang pengaturan ini
        </a>
        <a href="<?= site_url('simulasi') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Simulasi baru</a>
    <?php endif; ?>
</div>

<?= $this->include('partials/saw_result', ['result' => $result, 'meta' => $meta]) ?>
<?= $this->endSection() ?>