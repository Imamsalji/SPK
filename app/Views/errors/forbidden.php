<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm">
    <div class="card-body empty-state">
        <i class="bi bi-shield-lock text-danger"></i>
        <h2 class="h5">403 - Akses ditolak</h2>
        <p class="text-body-secondary">Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-primary">Kembali ke Dashboard</a>
    </div>
</div>
<?= $this->endSection() ?>