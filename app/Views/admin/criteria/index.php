<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$totalOk = abs($totalWeight - 100) < 0.005;
$active  = array_values(array_filter($criteria, static fn($c) => (int) $c['is_active'] === 1));
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 mb-0">Daftar Kriteria</h2>
                        <div class="small text-body-secondary">Total bobot aktif:
                            <span class="badge text-bg-<?= $totalOk ? 'success' : 'danger' ?>"><?= esc(num_id($totalWeight)) ?>%</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#criterionModal" data-mode="create">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Kriteria
                    </button>
                </div>

                <?php if ($criteria === []): ?>
                    <div class="empty-state">
                        <i class="bi bi-sliders"></i>
                        <h2 class="h6 mt-2">Belum ada kriteria</h2>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Bobot</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end" style="width:120px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $c): ?>
                                    <?php
                                    $values = [
                                        'id'             => (int) $c['id'],
                                        'code'           => $c['code'],
                                        'name'           => $c['name'],
                                        'description'    => $c['description'] ?? '',
                                        'type'           => $c['type'],
                                        'unit'           => $c['unit'] ?? '',
                                        'default_weight' => plain_number($c['default_weight']),
                                        'sort_order'     => (string) (int) $c['sort_order'],
                                        'is_active'      => (string) (int) $c['is_active'],
                                        'source_field'   => $c['source_field'] ?? '',
                                        'require_source' => (string) (int) ($c['require_source'] ?? 0),
                                    ];
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc($c['code']) ?></td>
                                        <td>
                                            <?= esc($c['name']) ?>
                                            <?php if (($c['source_field'] ?? '') === 'price'): ?>
                                                <span class="badge text-bg-info ms-1" title="Nilai diambil otomatis dari harga laptop">Otomatis: harga</span>
                                            <?php endif; ?>
                                            <?php if ((int) ($c['require_source'] ?? 0) === 1): ?>
                                                <span class="badge text-bg-secondary ms-1" title="Sumber data wajib dicantumkan">Wajib sumber</span>
                                            <?php endif; ?>
                                            <?php if (! empty($c['description'])): ?>
                                                <div class="small text-body-secondary"><?= esc($c['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-<?= $c['type'] === 'cost' ? 'danger' : 'success' ?>">
                                                <?= $c['type'] === 'cost' ? 'Cost' : 'Benefit' ?>
                                            </span>
                                        </td>
                                        <td><?= esc($c['unit'] ?? '-') ?></td>
                                        <td class="text-end"><?= esc(num_id($c['default_weight'])) ?>%</td>
                                        <td class="text-center">
                                            <span class="badge text-bg-<?= (int) $c['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                                <?= (int) $c['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#criterionModal" data-mode="edit"
                                                data-values="<?= esc(json_encode($values, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                                                aria-label="Ubah"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-confirm-delete
                                                data-action="<?= site_url('admin/kriteria/' . $c['id'] . '/hapus') ?>"
                                                data-name="<?= esc($c['code'] . ' - ' . $c['name'], 'attr') ?>"
                                                data-note="Nilai semua laptop untuk kriteria ini ikut terhapus. Riwayat perhitungan yang sudah tersimpan tidak berubah."
                                                aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Atur Bobot Default</h2>
                <p class="small text-body-secondary">
                    Bobot awal yang muncul di halaman simulasi. Total kriteria aktif harus tepat 100%.
                    Mahasiswa tetap bisa mengubah bobot saat simulasi tanpa mengubah nilai ini.
                </p>

                <?php if ($active === []): ?>
                    <p class="text-body-secondary mb-0">Belum ada kriteria aktif.</p>
                <?php else: ?>
                    <form method="post" action="<?= site_url('admin/kriteria/bobot') ?>" data-weight-form data-loading>
                        <?= csrf_field() ?>
                        <?php foreach ($active as $c): ?>
                            <div class="mb-2">
                                <label for="w_<?= (int) $c['id'] ?>" class="form-label small mb-1">
                                    <?= esc($c['code']) ?> &ndash; <?= esc($c['name']) ?>
                                    <span class="text-body-secondary">(<?= $c['type'] === 'cost' ? 'Cost' : 'Benefit' ?>)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="w_<?= (int) $c['id'] ?>"
                                        name="weights[<?= (int) $c['id'] ?>]" value="<?= esc(plain_number($c['default_weight']), 'attr') ?>"
                                        min="0" max="100" step="0.01" required data-weight-input>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                            <div>Total:
                                <strong><span data-weight-total><?= esc(number_format($totalWeight, 2, ',', '')) ?></span>%</strong>
                                <span class="badge text-bg-<?= $totalOk ? 'success' : 'danger' ?> ms-1" data-weight-badge>
                                    <?= $totalOk ? 'OK' : 'Harus 100%' ?>
                                </span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" data-weight-submit <?= $totalOk ? '' : 'disabled' ?>>
                                <i class="bi bi-check-lg me-1"></i>Simpan
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="criterionModal" tabindex="-1" aria-hidden="true" data-crud-modal<?= reopen_attr() ?>>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" class="modal-content" action="<?= site_url('admin/kriteria') ?>" data-loading
            data-create-url="<?= site_url('admin/kriteria') ?>"
            data-update-url="<?= site_url('admin/kriteria/__ID__/ubah') ?>"
            data-title-create="Tambah Kriteria" data-title-edit="Ubah Kriteria">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" data-modal-title>Tambah Kriteria</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="cr_code" class="form-label">Kode</label>
                        <input type="text" id="cr_code" name="code" class="form-control text-uppercase<?= invalid_class('code') ?>"
                            maxlength="10" placeholder="C6" required>
                        <?= field_feedback('code') ?>
                    </div>
                    <div class="col-md-8">
                        <label for="cr_name" class="form-label">Nama</label>
                        <input type="text" id="cr_name" name="name" class="form-control<?= invalid_class('name') ?>" maxlength="100" required>
                        <?= field_feedback('name') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="cr_type" class="form-label">Jenis</label>
                        <select id="cr_type" name="type" class="form-select<?= invalid_class('type') ?>" required>
                            <option value="benefit">Benefit (makin besar makin baik)</option>
                            <option value="cost">Cost (makin kecil makin baik)</option>
                        </select>
                        <?= field_feedback('type') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="cr_unit" class="form-label">Satuan</label>
                        <input type="text" id="cr_unit" name="unit" class="form-control<?= invalid_class('unit') ?>" maxlength="20" placeholder="GB">
                        <?= field_feedback('unit') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="cr_weight" class="form-label">Bobot default (%)</label>
                        <input type="number" id="cr_weight" name="default_weight" class="form-control<?= invalid_class('default_weight') ?>"
                            min="0" max="100" step="0.01" value="0" required>
                        <?= field_feedback('default_weight') ?>
                    </div>
                    <div class="col-md-8">
                        <label for="cr_source" class="form-label">Sumber nilai</label>
                        <select id="cr_source" name="source_field" class="form-select<?= invalid_class('source_field') ?>">
                            <option value="">Input manual per laptop</option>
                            <option value="price">Otomatis dari harga laptop</option>
                        </select>
                        <?= field_feedback('source_field') ?>
                        <div class="form-text">Hanya satu kriteria yang boleh memakai harga laptop (biasanya berjenis Cost).</div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div>
                            <input type="hidden" name="require_source" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="cr_reqsrc" name="require_source" value="1">
                                <label class="form-check-label" for="cr_reqsrc">Wajib cantumkan sumber data</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="cr_desc" class="form-label">Deskripsi <span class="text-body-secondary">(opsional)</span></label>
                        <textarea id="cr_desc" name="description" rows="2" class="form-control<?= invalid_class('description') ?>" maxlength="500"></textarea>
                        <?= field_feedback('description') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="cr_order" class="form-label">Urutan</label>
                        <input type="number" id="cr_order" name="sort_order" class="form-control<?= invalid_class('sort_order') ?>"
                            min="0" max="999" step="1" value="<?= (int) $nextOrder ?>" required>
                        <?= field_feedback('sort_order') ?>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div>
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="cr_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="cr_active">Aktif</label>
                            </div>
                            <?php if (form_err('is_active')): ?><div class="text-danger small"><?= esc(form_err('is_active')) ?></div><?php endif; ?>
                        </div>
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