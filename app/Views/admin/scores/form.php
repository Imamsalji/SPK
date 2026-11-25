<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$old = session()->getFlashdata('old_scores');
$old = is_array($old) ? $old : [];
?>

<a href="<?= site_url('admin/nilai') ?>" class="btn btn-link px-0 mb-2">
    <i class="bi bi-arrow-left me-1"></i>Kembali ke penilaian alternatif
</a>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="text-body-secondary small"><?= esc($laptop['brand_name']) ?> &middot; <?= esc($laptop['model']) ?></div>
            <h2 class="h5 mb-0">
                <?= esc($laptop['name']) ?>
                <?php if ((int) $laptop['is_demo'] === 1): ?><span class="badge text-bg-warning ms-1">Data demo</span><?php endif; ?>
            </h2>
        </div>
        <div class="text-end">
            <div class="small text-body-secondary">Harga</div>
            <div class="fw-bold fs-5"><?= rupiah($laptop['price']) ?></div>
        </div>
    </div>
</div>

<?php if ($criteria === []): ?>
    <div class="alert alert-warning mb-0">
        Belum ada kriteria aktif. Aktifkan kriteria di menu
        <a href="<?= site_url('admin/kriteria') ?>" class="alert-link">Kriteria</a>.
    </div>
<?php else: ?>
    <form method="post" action="<?= site_url('admin/laptop/' . $laptop['id'] . '/nilai') ?>" data-loading novalidate>
        <?= csrf_field() ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php foreach ($criteria as $c): ?>
                    <?php
                    $cid     = (int) $c['id'];
                    $isPrice = $c['source_field'] === 'price';
                    $isCost  = $c['type'] === 'cost';
                    $reqSrc  = (int) $c['require_source'] === 1;
                    $value   = $old[$cid]['value'] ?? (isset($scores[$cid]) ? plain_number($scores[$cid]['value']) : '');
                    $note    = $old[$cid]['note'] ?? ($scores[$cid]['source_note'] ?? '');
                    ?>
                    <div class="score-row">
                        <div class="row g-2 align-items-start">
                            <div class="col-md-4">
                                <div class="fw-semibold">
                                    <?= esc($c['code']) ?> &ndash; <?= esc($c['name']) ?>
                                    <span class="badge text-bg-<?= $isCost ? 'danger' : 'success' ?> ms-1">
                                        <?= $isCost ? 'Cost' : 'Benefit' ?>
                                    </span>
                                </div>
                                <div class="small text-body-secondary">
                                    <?= $isCost ? 'Makin kecil makin baik.' : 'Makin besar makin baik.' ?>
                                    <?= ! empty($c['description']) ? esc($c['description']) : '' ?>
                                </div>
                            </div>

                            <?php if ($isPrice): ?>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="text" class="form-control" value="<?= esc(rupiah($laptop['price']), 'attr') ?>" readonly aria-label="Harga (otomatis)">
                                    </div>
                                </div>
                                <div class="col-md-5 small text-body-secondary pt-2">
                                    Otomatis dari harga laptop. Ubah harga di
                                    <a href="<?= site_url('admin/laptop') ?>">Data Laptop</a>.
                                </div>
                            <?php else: ?>
                                <div class="col-md-3">
                                    <div class="input-group<?= form_err('value_' . $cid) ? ' has-validation' : '' ?>">
                                        <input type="number" id="v_<?= $cid ?>" name="values[<?= $cid ?>]"
                                            class="form-control<?= invalid_class('value_' . $cid) ?>"
                                            value="<?= esc($value, 'attr') ?>"
                                            min="<?= $isCost ? '0.01' : '0' ?>" step="0.01" inputmode="decimal"
                                            aria-label="Nilai <?= esc($c['name'], 'attr') ?>" required>
                                        <?php if (! empty($c['unit'])): ?>
                                            <span class="input-group-text"><?= esc($c['unit']) ?></span>
                                        <?php endif; ?>
                                        <?= field_feedback('value_' . $cid) ?>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" id="n_<?= $cid ?>" name="notes[<?= $cid ?>]"
                                        class="form-control<?= invalid_class('note_' . $cid) ?>"
                                        value="<?= esc($note, 'attr') ?>" maxlength="255"
                                        placeholder="<?= $reqSrc ? 'Sumber data (wajib), mis. nama benchmark + URL + tanggal akses' : 'Sumber data (opsional)' ?>"
                                        aria-label="Sumber data <?= esc($c['name'], 'attr') ?>"
                                        <?= $reqSrc ? 'required' : '' ?>>
                                    <?= field_feedback('note_' . $cid) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                <a href="<?= site_url('admin/nilai') ?>" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan nilai</button>
            </div>
        </div>
    </form>
<?php endif; ?>
<?= $this->endSection() ?>