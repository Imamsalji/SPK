(function () {
    'use strict';

    var form = document.getElementById('simForm');
    if (!form) { return; }

    var MIN = parseInt(form.getAttribute('data-min'), 10) || 2;
    var MAX = parseInt(form.getAttribute('data-max'), 10) || 12;
    var FORMAT = /^\d{1,3}(\.\d{1,2})?$/;

    var numberInputs = [].slice.call(form.querySelectorAll('[data-weight-input]'));
    var rangeInputs = {};
    form.querySelectorAll('[data-weight-range]').forEach(function (r) {
        rangeInputs[r.getAttribute('data-weight-range')] = r;
    });

    var rows = [].slice.call(form.querySelectorAll('[data-laptop-row]'));
    var checks = [].slice.call(form.querySelectorAll('[data-laptop-check]'));

    var totalEl = form.querySelector('[data-total]');
    var badgeEl = form.querySelector('[data-total-badge]');
    var barEl = form.querySelector('[data-total-bar]');
    var hintEl = form.querySelector('[data-total-hint]');
    var simHint = form.querySelector('[data-sim-hint]');
    var submitBtn = form.querySelector('[data-sim-submit]');
    var countEl = form.querySelector('[data-selected-count]');

    function fmt(cents) {
        return (cents / 100).toFixed(2).replace('.', ',');
    }

    /** Bobot dalam satuan 0,01%; null bila format tidak valid. */
    function centsOf(input) {
        var v = input.value.trim();
        if (!FORMAT.test(v)) { return null; }
        var n = Math.round(parseFloat(v) * 100);
        return (n < 0 || n > 10000) ? null : n;
    }

    function syncRange(input) {
        var range = rangeInputs[input.getAttribute('data-weight-input')];
        if (!range) { return; }
        var c = centsOf(input);
        range.value = c === null ? 0 : Math.round(c / 100);
    }

    function setCents(input, cents) {
        input.value = String(+(cents / 100).toFixed(2));
        syncRange(input);
    }

    /** Bagi 100,00% secara proporsional dengan metode largest remainder. Hasil dalam satuan 0,01%. */
    function distribute(weights) {
        var sum = weights.reduce(function (a, b) { return a + b; }, 0);
        if (sum <= 0) {
            weights = weights.map(function () { return 1; });
            sum = weights.length;
        }
        var raw = weights.map(function (w) { return w / sum * 10000; });
        var base = raw.map(Math.floor);
        var rest = 10000 - base.reduce(function (a, b) { return a + b; }, 0);
        var order = raw.map(function (v, i) { return { i: i, frac: v - Math.floor(v) }; })
            .sort(function (a, b) { return (b.frac - a.frac) || (a.i - b.i); });
        for (var k = 0; k < rest; k++) { base[order[k].i] += 1; }
        return base;
    }

    function refresh() {
        var sum = 0;
        var allValid = true;

        numberInputs.forEach(function (input) {
            var c = centsOf(input);
            input.classList.toggle('is-invalid', c === null && input.value.trim() !== '');
            if (c === null) { allValid = false; } else { sum += c; }
        });

        var weightsOk = allValid && sum === 10000;

        totalEl.textContent = fmt(sum);
        barEl.style.width = Math.min(100, sum / 100) + '%';
        barEl.className = 'progress-bar ' + (sum === 10000 ? 'bg-success' : (sum > 10000 ? 'bg-danger' : 'bg-warning'));
        badgeEl.textContent = weightsOk ? 'OK' : 'Harus 100%';
        badgeEl.className = 'badge ms-1 text-bg-' + (weightsOk ? 'success' : 'danger');

        if (!allValid) {
            hintEl.textContent = 'Ada bobot yang tidak valid';
        } else if (sum < 10000) {
            hintEl.textContent = 'Kurang ' + fmt(10000 - sum) + '%';
        } else if (sum > 10000) {
            hintEl.textContent = 'Lebih ' + fmt(sum - 10000) + '%';
        } else {
            hintEl.textContent = 'Total sudah 100%';
        }
        hintEl.className = 'small ' + (weightsOk ? 'text-success' : 'text-danger');

        var count = checks.filter(function (c) { return c.checked; }).length;
        var countOk = count >= MIN && count <= MAX;

        countEl.textContent = count;
        countEl.className = countOk ? '' : 'text-danger';
        rows.forEach(function (row) {
            row.classList.toggle('is-selected', row.querySelector('[data-laptop-check]').checked);
        });

        if (!countOk) {
            simHint.textContent = 'Pilih ' + MIN + ' sampai ' + MAX + ' laptop (saat ini ' + count + ').';
        } else if (!weightsOk) {
            simHint.textContent = 'Total bobot harus tepat 100%.';
        } else {
            simHint.textContent = '';
        }
        submitBtn.disabled = !(weightsOk && countOk);
    }

    // Bobot: slider dan kolom angka saling sinkron
    numberInputs.forEach(function (input) {
        input.addEventListener('input', function () { syncRange(input); refresh(); });
    });
    Object.keys(rangeInputs).forEach(function (code) {
        rangeInputs[code].addEventListener('input', function () {
            var input = form.querySelector('[data-weight-input="' + code + '"]');
            input.value = rangeInputs[code].value;
            refresh();
        });
    });

    // Tombol bantu bobot
    function applyDistribution(weights) {
        distribute(weights).forEach(function (cents, i) { setCents(numberInputs[i], cents); });
        refresh();
    }

    var btnEqual = form.querySelector('[data-weights-equal]');
    var btnNormalize = form.querySelector('[data-weights-normalize]');
    var btnReset = form.querySelector('[data-weights-reset]');

    if (btnEqual) {
        btnEqual.addEventListener('click', function () {
            applyDistribution(numberInputs.map(function () { return 1; }));
        });
    }
    if (btnNormalize) {
        btnNormalize.addEventListener('click', function () {
            applyDistribution(numberInputs.map(function (input) {
                var c = centsOf(input);
                return c === null ? 0 : c;
            }));
        });
    }
    if (btnReset) {
        btnReset.addEventListener('click', function () {
            numberInputs.forEach(function (input) {
                input.value = input.getAttribute('data-default');
                syncRange(input);
            });
            refresh();
        });
    }

    // Pilihan laptop
    checks.forEach(function (c) { c.addEventListener('change', refresh); });

    var search = form.querySelector('[data-laptop-search]');
    var emptyMsg = form.querySelector('[data-laptop-empty]');

    if (search) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var visible = 0;
            rows.forEach(function (row) {
                var show = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                row.classList.toggle('d-none', !show);
                if (show) { visible++; }
            });
            if (emptyMsg) { emptyMsg.classList.toggle('d-none', visible !== 0); }
        });
    }

    function setVisibleChecked(value) {
        rows.forEach(function (row) {
            if (!row.classList.contains('d-none')) {
                row.querySelector('[data-laptop-check]').checked = value;
            }
        });
        refresh();
    }
    var btnAll = form.querySelector('[data-select-all]');
    var btnNone = form.querySelector('[data-select-none]');
    if (btnAll) { btnAll.addEventListener('click', function () { setVisibleChecked(true); }); }
    if (btnNone) { btnNone.addEventListener('click', function () { setVisibleChecked(false); }); }

    numberInputs.forEach(syncRange);
    refresh();
    window.addEventListener('pageshow', refresh);
})();