<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm">
    <div class="card-body empty-state">
        <i class="bi bi-trophy"></i>
        <h2 class="h5 mt-2">Belum ada hasil perhitungan</h2>
        <p class="text-body-secondary mb-3">
            <?= auth_is('admin')
                ? 'Hasil akan muncul setelah mahasiswa menjalankan simulasi.'
                : 'Jalankan simulasi untuk melihat rekomendasi laptop terbaik.' ?>
        </p>
        <?php if (auth_is('mahasiswa')): ?>
            <a href="<?= site_url('simulasi') ?>" class="btn btn-primary">Mulai simulasi</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>