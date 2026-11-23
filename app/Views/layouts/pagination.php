<?php

/** @var \CodeIgniter\Pager\Pager $pager */
$total = $pager->getTotal();

if ($total > 0):
    $perPage = $pager->getPerPage();
    $current = $pager->getCurrentPage();
    $from    = ($current - 1) * $perPage + 1;
    $to      = min($total, $current * $perPage);
?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <div class="small text-body-secondary">Menampilkan <?= $from ?>&ndash;<?= $to ?> dari <?= $total ?> data</div>
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
<?php endif; ?>