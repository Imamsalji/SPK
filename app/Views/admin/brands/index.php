<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $offset = ($pager->getCurrentPage() - 1) * $pager->getPerPage(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="get" action="<?= site_url('admin/merek') ?>" class="d-flex gap-2">
                <input type="search" name="q" value="<?= esc($keyword) ?>" class="form-control"
                    placeholder="Cari merek..." maxlength="100">
                <button type="submit" class="btn btn-outline-secondary" aria-label="Cari"><i class="bi bi-search"></i></button>
                <?php if ($keyword !== ''): ?>
                    <a href="<?= site_url('admin/merek') ?>" class="btn btn-link">Reset</a>
                <?php endif; ?>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#brandModal" data-mode="create">
                <i class="bi bi-plus-lg me-1"></i>Tambah Merek
            </button>
        </div>

        <?php if ($brands === []): ?>
            <div class="empty-state">
                <i class="bi bi-tags"></i>
                <h2 class="h6 mt-2">Belum ada data merek</h2>
                <p class="text-body-secondary mb-0">
                    <?= $keyword !== '' ? 'Tidak ada merek yang cocok dengan pencarian Anda.' : 'Klik "Tambah Merek" untuk membuat data pertama.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Nama Merek</th>
                            <th class="text-center">Jumlah Laptop</th>
                            <th>Dibuat</th>
                            <th class="text-end" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $i => $brand): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc($brand['name']) ?></td>
                                <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $brand['laptop_count'] ?></span></td>
                                <td class="text-body-secondary small"><?= esc(date('d/m/Y', strtotime($brand['created_at'] ?? 'now'))) ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#brandModal" data-mode="edit"
                                        data-values="<?= esc(json_encode(['id' => (int) $brand['id'], 'name' => $brand['name']]), 'attr') ?>"
                                        aria-label="Ubah"><i class="bi bi-pencil"></i></button>
                                    <?php if ((int) $brand['laptop_count'] > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                            title="Masih dipakai oleh laptop" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-confirm-delete
                                            data-action="<?= site_url('admin/merek/' . $brand['id'] . '/hapus') ?>"
                                            data-name="<?= esc($brand['name'], 'attr') ?>"
                                            aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
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

<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true" data-crud-modal<?= reopen_attr() ?>>
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content" action="<?= site_url('admin/merek') ?>" data-loading
            data-create-url="<?= site_url('admin/merek') ?>"
            data-update-url="<?= site_url('admin/merek/__ID__/ubah') ?>"
            data-title-create="Tambah Merek" data-title-edit="Ubah Merek">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" data-modal-title>Tambah Merek</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <label for="brand_name" class="form-label">Nama merek</label>
                <input type="text" id="brand_name" name="name" class="form-control<?= invalid_class('name') ?>"
                    maxlength="100" required>
                <?= field_feedback('name') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>