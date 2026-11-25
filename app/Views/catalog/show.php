<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $img = laptop_image_url($laptop['image']); ?>

<a href="<?= site_url('katalog') ?>" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left me-1"></i>Kembali ke katalog</a>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-5">
                <?php if ($img): ?>
                    <img src="<?= esc($img, 'attr') ?>" class="img-fluid rounded w-100" alt="<?= esc($laptop['name'], 'attr') ?>">
                <?php else: ?>
                    <div class="catalog-img catalog-img-empty rounded"><i class="bi bi-laptop"></i></div>
                <?php endif; ?>
            </div>
            <div class="col-md-7">
                <div class="text-body-secondary"><?= esc($laptop['brand_name']) ?> &middot; <?= esc($laptop['model']) ?></div>
                <h2 class="h4 mb-2"><?= esc($laptop['name']) ?></h2>
                <?php if ((int) $laptop['is_demo'] === 1): ?>
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Data demo (ilustrasi), bukan spesifikasi produk nyata.
                    </div>
                <?php endif; ?>
                <div class="fs-3 fw-bold text-primary mb-3"><?= rupiah($laptop['price']) ?></div>

                <?php if (! empty($laptop['processor_name'])): ?>
                    <p class="mb-2"><i class="bi bi-cpu me-2"></i><?= esc($laptop['processor_name']) ?></p>
                <?php endif; ?>
                <?php if (! empty($laptop['description'])): ?>
                    <p class="text-body-secondary"><?= nl2br(esc($laptop['description'])) ?></p>
                <?php endif; ?>

                <h3 class="h6 mt-4">Spesifikasi untuk penilaian</h3>
                <?php if ($scores === []): ?>
                    <p class="text-body-secondary small mb-0">Nilai kriteria belum diisi oleh admin.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                <?php foreach ($scores as $s): ?>
                                    <tr>
                                        <th class="fw-normal text-body-secondary" style="width:45%"><?= esc($s['code']) ?> &ndash; <?= esc($s['name']) ?></th>
                                        <td class="fw-semibold"><?= esc(format_score($s['value'], $s['unit'])) ?></td>
                                        <td class="text-end">
                                            <span class="badge text-bg-<?= $s['type'] === 'cost' ? 'danger' : 'success' ?>">
                                                <?= $s['type'] === 'cost' ? 'Cost' : 'Benefit' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <a href="<?= site_url('simulasi?pilih=' . (int) $laptop['id']) ?>" class="btn btn-primary mt-4">
                    <i class="bi bi-calculator me-1"></i>Gunakan dalam simulasi SPK
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>