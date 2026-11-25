<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php if (! empty($notice)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i><?= esc($notice) ?>
    </div>
<?php endif; ?>
<?php
$criteria   = $options['criteria'];
$eligible   = array_values($options['eligible']);
$incomplete = array_values($options['incomplete']);
?>

<?php if ($criteria === []): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Belum ada kriteria aktif, sehingga simulasi belum dapat dijalankan. Hubungi administrator.
    </div>
<?php elseif (count($eligible) < $min): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Belum cukup laptop untuk dibandingkan. Dibutuhkan minimal <?= (int) $min ?> laptop aktif dengan nilai kriteria lengkap.
        Hubungi administrator.
    </div>
<?php else: ?>
    <form id="simForm" method="post" action="<?= site_url('simulasi/hitung') ?>" novalidate
        data-loading data-loading-text="Menghitung..."
        data-min="<?= (int) $min ?>" data-max="<?= (int) $max ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <!-- Kiri: nama dan pilihan laptop -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6">1. Nama simulasi <span class="text-body-secondary fw-normal">(opsional)</span></h2>
                        <input type="text" name="name" class="form-control" maxlength="150"
                            value="<?= esc($name, 'attr') ?>" placeholder="Contoh: Laptop kuliah desain grafis">
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h2 class="h6 mb-0">2. Pilih laptop yang dibandingkan</h2>
                            <div class="small">Dipilih: <strong data-selected-count>0</strong>
                                <span class="text-body-secondary">(<?= (int) $min ?>&ndash;<?= (int) $max ?>)</span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <input type="search" class="form-control flex-grow-1" style="min-width:180px;max-width:320px"
                                placeholder="Cari laptop..." aria-label="Cari laptop" data-laptop-search>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-select-all>Pilih semua</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-select-none>Kosongkan</button>
                        </div>

                        <div class="sim-laptop-list">
                            <?php foreach ($eligible as $l): ?>
                                <?php
                                $img  = laptop_image_url($l['image']);
                                $find = mb_strtolower($l['brand_name'] . ' ' . $l['name'] . ' ' . $l['model']);
                                ?>
                                <label class="sim-laptop d-flex align-items-center gap-3" data-laptop-row
                                    data-search="<?= esc($find, 'attr') ?>">
                                    <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" name="laptop_ids[]"
                                        value="<?= (int) $l['id'] ?>" data-laptop-check
                                        <?= in_array((int) $l['id'], $selected, true) ? 'checked' : '' ?>>
                                    <?php if ($img): ?>
                                        <img src="<?= esc($img, 'attr') ?>" class="laptop-thumb flex-shrink-0" alt="" loading="lazy">
                                    <?php else: ?>
                                        <div class="laptop-thumb laptop-thumb-empty flex-shrink-0"><i class="bi bi-laptop"></i></div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            <?= esc($l['name']) ?>
                                            <?php if ((int) $l['is_demo'] === 1): ?><span class="badge text-bg-warning ms-1">Demo</span><?php endif; ?>
                                        </div>
                                        <div class="small text-body-secondary">
                                            <?= esc($l['brand_name']) ?> &middot; <?= esc($l['model']) ?>
                                            <?php if (! empty($l['processor_name'])): ?> &middot; <?= esc($l['processor_name']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="fw-semibold text-nowrap"><?= rupiah($l['price']) ?></div>
                                </label>
                            <?php endforeach; ?>
                            <div class="text-body-secondary small d-none" data-laptop-empty>Tidak ada laptop yang cocok.</div>
                        </div>

                        <?php if ($incomplete !== []): ?>
                            <div class="alert alert-secondary small mt-3 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                <?= count($incomplete) ?> laptop belum dapat dipilih karena nilai kriterianya belum lengkap:
                                <?= esc(implode(', ', array_column($incomplete, 'name'))) ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kanan: bobot -->
            <div class="col-xl-5">
                <div class="sticky-xl">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h6">3. Atur bobot preferensi</h2>
                            <p class="small text-body-secondary">
                                Geser slider atau ketik angka. Total harus tepat 100%. Pengaturan ini hanya berlaku
                                untuk simulasi Anda dan tidak mengubah bobot default kriteria.
                            </p>

                            <?php foreach ($criteria as $c): ?>
                                <?php
                                $code   = $c['code'];
                                $value  = $weights[$code];
                                $rangeV = is_numeric($value) ? (int) round((float) $value) : 0;
                                ?>
                                <div class="weight-row">
                                    <label for="w_<?= esc($code, 'attr') ?>" class="form-label small fw-semibold mb-1">
                                        <?= esc($code) ?> &ndash; <?= esc($c['name']) ?>
                                        <span class="badge fw-normal text-bg-<?= $c['type'] === 'cost' ? 'danger' : 'success' ?> ms-1">
                                            <?= $c['type'] === 'cost' ? 'Cost' : 'Benefit' ?>
                                        </span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="0" max="100" step="1"
                                            value="<?= $rangeV ?>" data-weight-range="<?= esc($code, 'attr') ?>"
                                            aria-label="Slider bobot <?= esc($c['name'], 'attr') ?>">
                                        <div class="input-group input-group-sm" style="width:120px">
                                            <input type="number" id="w_<?= esc($code, 'attr') ?>" name="weights[<?= esc($code, 'attr') ?>]"
                                                class="form-control text-end" min="0" max="100" step="0.01" inputmode="decimal"
                                                value="<?= esc($value, 'attr') ?>"
                                                data-weight-input="<?= esc($code, 'attr') ?>"
                                                data-default="<?= esc(plain_number($c['default_weight']), 'attr') ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>Total: <strong><span data-total>0,00</span>%</strong>
                                    <span class="badge ms-1" data-total-badge></span>
                                </div>
                                <div class="small" data-total-hint></div>
                            </div>
                            <div class="progress mt-2" style="height:8px" role="progressbar" aria-label="Total bobot">
                                <div class="progress-bar" data-total-bar style="width:0"></div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-weights-equal>Bagi rata</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-weights-normalize
                                    title="Skalakan bobot saat ini agar totalnya 100%">Normalisasikan ke 100%</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-weights-reset>Reset ke default</button>
                            </div>

                            <hr>
                            <div class="small text-danger mb-2" data-sim-hint></div>
                            <button type="submit" class="btn btn-primary w-100 py-2" data-sim-submit disabled>
                                <i class="bi bi-calculator me-1"></i>Hitung dengan SAW
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/simulation.js') ?>"></script>
<?= $this->endSection() ?>