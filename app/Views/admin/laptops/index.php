<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $offset = ($pager->getCurrentPage() - 1) * $pager->getPerPage(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <form method="get" action="<?= site_url('admin/laptop') ?>" class="row g-2 flex-grow-1">
                <div class="col-12 col-md-4">
                    <input type="search" name="q" value="<?= esc($filters['q']) ?>" class="form-control"
                        placeholder="Cari nama, model, atau merek..." maxlength="100">
                </div>
                <div class="col-6 col-md-3">
                    <select name="brand" class="form-select" aria-label="Filter merek">
                        <option value="">Semua merek</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= (int) $b['id'] ?>" <?= $filters['brand'] === (string) $b['id'] ? 'selected' : '' ?>>
                                <?= esc($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if ($filters['q'] !== '' || $filters['brand'] !== '' || $filters['status'] !== ''): ?>
                        <a href="<?= site_url('admin/laptop') ?>" class="btn btn-link">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#laptopModal" data-mode="create"
                <?= $brands === [] ? 'disabled title="Tambahkan merek terlebih dahulu"' : '' ?>>
                <i class="bi bi-plus-lg me-1"></i>Tambah Laptop
            </button>
        </div>

        <?php if ($laptops === []): ?>
            <div class="empty-state">
                <i class="bi bi-laptop"></i>
                <h2 class="h6 mt-2">Belum ada data laptop</h2>
                <p class="text-body-secondary mb-0">Tidak ada laptop yang cocok dengan filter, atau data belum dibuat.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th style="width:80px">Gambar</th>
                            <th>Laptop</th>
                            <th>Harga</th>
                            <th>Prosesor</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width:170px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laptops as $i => $l): ?>
                            <?php
                            $img      = laptop_image_url($l['image']);
                            $complete = (int) $l['score_count'] >= $activeCriteria && $activeCriteria > 0;
                            $values   = [
                                'id'             => (int) $l['id'],
                                'brand_id'       => (string) $l['brand_id'],
                                'name'           => $l['name'],
                                'model'          => $l['model'],
                                'price'          => plain_number($l['price']),
                                'processor_name' => $l['processor_name'] ?? '',
                                'description'    => $l['description'] ?? '',
                                'is_active'      => (string) (int) $l['is_active'],
                                'image_url'      => $img ?? '',
                            ];
                            ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td>
                                    <?php if ($img): ?>
                                        <img src="<?= esc($img, 'attr') ?>" class="laptop-thumb" alt="<?= esc($l['name'], 'attr') ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="laptop-thumb laptop-thumb-empty"><i class="bi bi-laptop"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc($l['name']) ?>
                                        <?php if ((int) $l['is_demo'] === 1): ?><span class="badge text-bg-warning ms-1">Demo</span><?php endif; ?>
                                    </div>
                                    <div class="small text-body-secondary"><?= esc($l['brand_name']) ?> &middot; <?= esc($l['model']) ?></div>
                                </td>
                                <td class="text-nowrap"><?= rupiah($l['price']) ?></td>
                                <td class="small"><?= esc($l['processor_name'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= $complete ? 'success' : 'warning' ?>"
                                        title="Nilai kriteria aktif yang sudah terisi">
                                        <?= (int) $l['score_count'] ?>/<?= (int) $activeCriteria ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= (int) $l['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                        <?= (int) $l['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= site_url('admin/laptop/' . $l['id'] . '/nilai') ?>"
                                        class="btn btn-sm btn-outline-success" title="Isi nilai kriteria" aria-label="Isi nilai"><i class="bi bi-list-check"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#laptopModal" data-mode="edit"
                                        data-values="<?= esc(json_encode($values, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                                        aria-label="Ubah"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-confirm-delete
                                        data-action="<?= site_url('admin/laptop/' . $l['id'] . '/hapus') ?>"
                                        data-name="<?= esc($l['name'], 'attr') ?>"
                                        data-note="Nilai kriteria laptop ini ikut terhapus. Riwayat perhitungan yang sudah tersimpan tidak berubah."
                                        aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= $this->include('layouts/pagination', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="laptopModal" tabindex="-1" aria-hidden="true" data-crud-modal<?= reopen_attr() ?>>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" enctype="multipart/form-data" class="modal-content" action="<?= site_url('admin/laptop') ?>" data-loading
            data-create-url="<?= site_url('admin/laptop') ?>"
            data-update-url="<?= site_url('admin/laptop/__ID__/ubah') ?>"
            data-title-create="Tambah Laptop" data-title-edit="Ubah Laptop">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" data-modal-title>Tambah Laptop</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="lp_brand" class="form-label">Merek</label>
                        <select id="lp_brand" name="brand_id" class="form-select<?= invalid_class('brand_id') ?>" required>
                            <option value="">-- Pilih merek --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_feedback('brand_id') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="lp_model" class="form-label">Model</label>
                        <input type="text" id="lp_model" name="model" class="form-control<?= invalid_class('model') ?>" maxlength="100" required>
                        <?= field_feedback('model') ?>
                    </div>
                    <div class="col-12">
                        <label for="lp_name" class="form-label">Nama laptop</label>
                        <input type="text" id="lp_name" name="name" class="form-control<?= invalid_class('name') ?>" maxlength="150" required>
                        <?= field_feedback('name') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="lp_price" class="form-label">Harga (Rp)</label>
                        <input type="number" id="lp_price" name="price" class="form-control<?= invalid_class('price') ?>"
                            min="1" step="1" inputmode="numeric" required>
                        <?= field_feedback('price') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="lp_cpu" class="form-label">Prosesor <span class="text-body-secondary">(opsional)</span></label>
                        <input type="text" id="lp_cpu" name="processor_name" class="form-control<?= invalid_class('processor_name') ?>" maxlength="150">
                        <?= field_feedback('processor_name') ?>
                    </div>
                    <div class="col-12">
                        <label for="lp_desc" class="form-label">Deskripsi <span class="text-body-secondary">(opsional)</span></label>
                        <textarea id="lp_desc" name="description" rows="3" class="form-control<?= invalid_class('description') ?>" maxlength="2000"></textarea>
                        <?= field_feedback('description') ?>
                    </div>
                    <div class="col-12">
                        <label for="lp_image" class="form-label">Gambar <span class="text-body-secondary">(JPG/PNG/WEBP, maks. 2 MB)</span></label>
                        <input type="file" id="lp_image" name="image" class="form-control<?= invalid_class('image') ?>"
                            accept="image/jpeg,image/png,image/webp">
                        <?= field_feedback('image') ?>
                        <div class="mt-2 d-none" data-image-box>
                            <img alt="Gambar saat ini" class="laptop-preview" data-image-preview>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="lp_remove_image">
                                <label class="form-check-label" for="lp_remove_image">Hapus gambar saat ini</label>
                            </div>
                            <div class="form-text">Memilih file baru di atas akan menggantikan gambar saat ini.</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="lp_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="lp_active">Aktif (tampil di katalog dan bisa dipilih untuk simulasi)</label>
                        </div>
                        <?php if (form_err('is_active')): ?><div class="text-danger small"><?= esc(form_err('is_active')) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>