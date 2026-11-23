<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= site_url('katalog') ?>" class="row g-2">
            <div class="col-12 col-md-6">
                <input type="search" name="q" value="<?= esc($filters['q']) ?>" class="form-control"
                    placeholder="Cari nama, model, atau merek..." maxlength="100">
            </div>
            <div class="col-8 col-md-3">
                <select name="brand" class="form-select" aria-label="Filter merek">
                    <option value="">Semua merek</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= $filters['brand'] === (string) $b['id'] ? 'selected' : '' ?>><?= esc($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <?php if ($filters['q'] !== '' || $filters['brand'] !== ''): ?>
                    <a href="<?= site_url('katalog') ?>" class="btn btn-link">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($laptops === []): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body empty-state">
            <i class="bi bi-laptop"></i>
            <h2 class="h6 mt-2">Laptop tidak ditemukan</h2>
            <p class="text-body-secondary mb-0">Coba ubah kata kunci atau filter merek.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($laptops as $l): ?>
            <?php $img = laptop_image_url($l['image']); ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if ($img): ?>
                        <img src="<?= esc($img, 'attr') ?>" class="catalog-img" alt="<?= esc($l['name'], 'attr') ?>" loading="lazy">
                    <?php else: ?>
                        <div class="catalog-img catalog-img-empty"><i class="bi bi-laptop"></i></div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="small text-body-secondary">
                            <?= esc($l['brand_name']) ?>
                            <?php if ((int) $l['is_demo'] === 1): ?><span class="badge text-bg-warning ms-1">Data demo</span><?php endif; ?>
                        </div>
                        <h2 class="h6 mb-1"><?= esc($l['name']) ?></h2>
                        <div class="small text-body-secondary mb-2"><?= esc($l['model']) ?></div>
                        <div class="fw-bold fs-5 text-primary mb-3"><?= rupiah($l['price']) ?></div>
                        <a href="<?= site_url('katalog/' . $l['id']) ?>" class="btn btn-outline-primary btn-sm mt-auto">
                            Lihat detail <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?= $this->include('layouts/pagination', ['pager' => $pager]) ?>
<?php endif; ?>
<?= $this->endSection() ?>