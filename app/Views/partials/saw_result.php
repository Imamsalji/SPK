<?php

/**
 * Tampilan hasil SAW (dipakai ulang di Tahap 8).
 *
 * @var array $result keluaran SAWService::calculate()
 * @var array $meta   [kunci alternatif => ['brand_name', 'name', 'model', 'price']]
 */
$palette  = ['#19376d', '#2f80ed', '#27ae60', '#f2994a', '#9b51e0', '#eb5757', '#00a8a8', '#7f8c8d'];
$criteria = $result['criteria'];
$ranking  = $result['ranking'];

$colors = [];
foreach ($criteria as $i => $c) {
    $colors[$c['code']] = $palette[$i % count($palette)];
}

$criteriaHead = static function (array $criteria): string {
    $html = '';
    foreach ($criteria as $c) {
        $isCost = $c['type'] === 'cost';
        $html  .= '<th class="text-end text-nowrap">' . esc($c['code'])
            . '<div class="small fw-normal text-body-secondary">' . esc($c['name']) . '</div>'
            . '<span class="badge fw-normal text-bg-' . ($isCost ? 'danger' : 'success') . '">'
            . ($isCost ? 'Cost' : 'Benefit') . '</span></th>';
    }

    return $html;
};
?>

<!-- Peringkat -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Peringkat dan nilai preferensi</h2>
        <div class="mb-3">
            <?php foreach ($criteria as $c): ?>
                <span class="me-3 small text-nowrap"><span class="legend-dot" style="background:<?= $colors[$c['code']] ?>"></span><?= esc($c['code']) ?> <?= esc($c['name']) ?></span>
            <?php endforeach; ?>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px">Rank</th>
                        <th>Alternatif</th>
                        <th class="text-end">Nilai V</th>
                        <th style="min-width:220px">Kontribusi kriteria</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $item): ?>
                        <?php $key = $item['key'];
                        $m = $meta[$key] ?? null; ?>
                        <tr class="<?= $item['rank'] === 1 ? 'row-best' : '' ?>">
                            <td><span class="rank-badge rank-<?= (int) $item['rank'] ?>"><?= (int) $item['rank'] ?></span></td>
                            <td>
                                <div class="fw-semibold">
                                    <?= esc($item['name']) ?>
                                    <?php if ($item['tied']): ?><span class="badge text-bg-warning ms-1">Seri</span><?php endif; ?>
                                </div>
                                <?php if ($m): ?>
                                    <div class="small text-body-secondary"><?= esc($m['brand_name']) ?> &middot; <?= esc($m['model']) ?> &middot; <?= rupiah($m['price']) ?></div>
                                <?php endif; ?>
                                <?php if (! empty($item['tie_break'])): ?>
                                    <div class="small text-warning-emphasis"><?= esc($item['tie_break']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold" title="<?= esc(fmt_dec($item['preference'], 8), 'attr') ?>"><?= fmt_dec($item['preference'], 4) ?></td>
                            <td>
                                <div class="stack-bar" role="img" aria-label="Komposisi nilai preferensi">
                                    <?php foreach ($criteria as $c): ?>
                                        <?php $w = (float) $result['weighted_matrix'][$key][$c['code']]; ?>
                                        <?php if ($w > 0): ?>
                                            <span style="width:<?= number_format($w * 100, 4, '.', '') ?>%;background:<?= $colors[$c['code']] ?>"
                                                title="<?= esc($c['code'] . ' ' . $c['name'] . ': ' . fmt_dec($w, 4), 'attr') ?>"></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="small text-body-secondary mt-2">
            Panjang batang penuh = nilai 1,0000. Arahkan kursor ke bagian berwarna untuk melihat kontribusi kriteria tersebut.
        </div>
    </div>
</div>

<!-- Bobot -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Bobot yang digunakan</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Kriteria</th>
                        <th>Jenis</th>
                        <th class="text-end">Bobot (%)</th>
                        <th class="text-end">Proporsi (w)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($criteria as $c): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($c['code']) ?></td>
                            <td><?= esc($c['name']) ?></td>
                            <td><span class="badge text-bg-<?= $c['type'] === 'cost' ? 'danger' : 'success' ?>"><?= $c['type'] === 'cost' ? 'Cost' : 'Benefit' ?></span></td>
                            <td class="text-end"><?= fmt_dec($c['weight_percent'], 2) ?></td>
                            <td class="text-end"><?= fmt_dec($c['weight_normalized'], 4) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="3">Total</td>
                        <td class="text-end"><?= fmt_dec($result['total_weight_percent'], 2) ?></td>
                        <td class="text-end">1,0000</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Matriks keputusan -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Matriks keputusan (X)</h2>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alternatif</th><?= $criteriaHead($criteria) ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($item['name']) ?></td>
                            <?php foreach ($criteria as $c): ?>
                                <td class="text-end text-nowrap"><?= esc(format_score($result['decision_matrix'][$item['key']][$c['code']], $c['unit'])) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td>Acuan <span class="fw-normal text-body-secondary small">(min untuk Cost, max untuk Benefit)</span></td>
                        <?php foreach ($criteria as $c): ?>
                            <td class="text-end text-nowrap">
                                <?= $c['type'] === 'cost' ? 'min ' : 'max ' ?><?= esc(format_score($result['reference'][$c['code']]['value'], $c['unit'])) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Matriks normalisasi -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Matriks normalisasi (R)</h2>
        <p class="small text-body-secondary">
            Benefit: r = x / max &nbsp;&middot;&nbsp; Cost: r = min / x. Tabel menampilkan 4 desimal; perhitungan memakai <?= (int) $result['precision'] ?> desimal.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alternatif</th><?= $criteriaHead($criteria) ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($item['name']) ?></td>
                            <?php foreach ($criteria as $c): ?>
                                <?php $r = (float) $result['normalized_matrix'][$item['key']][$c['code']]; ?>
                                <td class="text-end" title="<?= esc(fmt_dec($r, 8), 'attr') ?>"><?= fmt_dec($r, 4) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Kontribusi -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Kontribusi kriteria terhadap nilai akhir (w &times; r)</h2>
        <p class="small text-body-secondary">V = jumlah seluruh kontribusi pada satu baris.</p>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alternatif</th><?= $criteriaHead($criteria) ?><th class="text-end">V</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-light">
                        <td class="text-body-secondary">Bobot (w)</td>
                        <?php foreach ($criteria as $c): ?>
                            <td class="text-end text-body-secondary"><?= fmt_dec($c['weight_normalized'], 4) ?></td>
                        <?php endforeach; ?>
                        <td></td>
                    </tr>
                    <?php foreach ($ranking as $item): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($item['name']) ?></td>
                            <?php foreach ($criteria as $c): ?>
                                <?php $w = (float) $result['weighted_matrix'][$item['key']][$c['code']]; ?>
                                <td class="text-end" title="<?= esc(fmt_dec($w, 8), 'attr') ?>"><?= fmt_dec($w, 4) ?></td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold" title="<?= esc(fmt_dec($item['preference'], 8), 'attr') ?>"><?= fmt_dec($item['preference'], 4) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Aturan peringkat -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <details>
            <summary class="fw-semibold">Aturan peringkat dan penanganan nilai seri</summary>
            <ol class="mt-2 mb-0 small">
                <?php foreach ($result['tie_break_rule'] as $rule): ?>
                    <li><?= esc($rule) ?></li>
                <?php endforeach; ?>
            </ol>
        </details>
    </div>
</div>