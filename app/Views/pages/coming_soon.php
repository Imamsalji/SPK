<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm">
    <div class="card-body empty-state">
        <i class="bi <?= esc($icon, 'attr') ?>"></i>
        <h2 class="h5">Modul <?= esc($title) ?> segera hadir</h2>
        <p class="text-body-secondary mb-0">Halaman ini akan dibuat pada <?= esc($stage) ?>.</p>
    </div>
</div>
<?= $this->endSection() ?>