<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$best     = $result['best'];
$bestMeta = $meta[$best['key']] ?? ['brand_name' => '', 'model' => '', 'price' => 0];
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
                <div class="fw-semibold"><?= esc($simName) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Pengguna</div>
                <div class="fw-semibold"><?= esc($userName) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Tanggal perhitungan</div>
                <div class="fw-semibold"><?= esc(date('d/m/Y H:i', strtotime($calculatedAt))) ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-body-secondary">Alternatif / kriteria</div>
                <div class="fw-semibold"><?= count($result['alternatives']) ?> / <?= count($result['criteria']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Hasil ini adalah pratinjau dan belum tersimpan ke riwayat. Penyimpanan ke riwayat ditambahkan pada Tahap 8.
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= site_url('simulasi') ?>" class="btn btn-outline-primary"><i class="bi bi-sliders me-1"></i>Ubah bobot / pilihan</a>
    <form method="post" action="<?= site_url('simulasi/baru') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>Simulasi baru</button>
    </form>
</div>

<?= $this->include('partials/saw_result', ['result' => $result, 'meta' => $meta]) ?>
<?= $this->endSection() ?>