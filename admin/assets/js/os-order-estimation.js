(function ($) {
    'use strict';

    const RAW  = JSON.parse(document.getElementById('os-estimation-data').textContent || '{}');
    const AJAX_URL = RAW.ajaxUrl || '';
    const NONCE    = RAW.nonce   || '';
    let   DRAFTS   = RAW.savedDrafts || [];

    // ── Custom Model selection state ─────────────────────────────────────────────
    // Tracks which custom model IDs the user has toggled on (survey-enabled only).
    // Initialised from the PHP-rendered chips (all survey models start selected).
    const ALL_MODELS = RAW.allCustomModels || []; // [{id, name, include_in_survey, calculation_type}]
    let selectedModelIds = ALL_MODELS
        .filter(m => m.include_in_survey === 1)
        .map(m => m.id);

    // ── Manual Calculation state ───────────────────────────────────────────────────
    // Set of model names that use manual entry instead of auto calculation.
    let MANUAL_MODELS = new Set(
        ALL_MODELS.filter(m => m.calculation_type === 'manual').map(m => m.name)
    );
    // Stores user-entered quantities: { 'Sport Suit': { '22': 10, '24': 20 } }
    let manualQuantities = {};

    // Chip toggle — only survey chips (not disabled ones) are clickable
    $(document).on('click', '.os-cat-chip-survey', function () {
        const $chip  = $(this);
        const $cb    = $chip.find('.os-cat-selector');
        const modelId  = parseInt($cb.data('cat-id'));
        const isNowChecked = !$cb.prop('checked');
        $cb.prop('checked', isNowChecked);
        $chip.toggleClass('os-cat-deselected', !isNowChecked);

        if (isNowChecked) {
            if (!selectedModelIds.includes(modelId)) selectedModelIds.push(modelId);
        } else {
            selectedModelIds = selectedModelIds.filter(id => id !== modelId);
        }

        // Reset cached supplier data so next calculate re-fetches with new filter
        supplierReportData = null;
    });

    const DEFAULT_DISTRIBUTIONS = {
        KG1:   { 22:33.33, 24:66.67 },
        KG2:   { 24:14.29, 26:57.14, 28:28.57 },
        G1:    { 28:40, 30:60 },
        G2:    { 30:40, 32:60 },
        G3:    { 32:80, 34:20 },
        G4:    { 36:71.43, 38:28.57 },
        G5:    { 36:33.33, 38:66.67 },
        G6:    { 40:80, 42:20 },
        G7:    { 40:40, 42:60 },
        G8:    { 40:40, 42:40, 44:20 },
        G9:    { 42:50, 44:50 },
        G10_12:{ 44:13.33, 46:26.67, 48:26.67, 50:13.33, 52:13.33, 54:6.67 },
    };

    const ALL_SIZES = [22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54];
    const GRADE_LABELS = {
        KG1:'KG1', KG2:'KG2', G1:'Grade 1', G2:'Grade 2', G3:'Grade 3',
        G4:'Grade 4', G5:'Grade 5', G6:'Grade 6', G7:'Grade 7',
        G8:'Grade 8', G9:'Grade 9', G10_12:'G10/G11/G12',
    };

    function deepCopy(o) { return JSON.parse(JSON.stringify(o)); }

    // Active distribution — start from saved or defaults
    let activeDist = RAW.savedDistribution ? deepCopy(RAW.savedDistribution) : deepCopy(DEFAULT_DISTRIBUTIONS);

    // Actual size data loaded from AJAX (grade => { size => count })
    // Fetched when calculate is clicked
    let actualSizeData = {};
    let supplierReportData = null;

    let currentMargin  = 0;
    let isManualMode   = false;
    let lastGrandTotal = {};
    let pieChart = null, barChart = null;

    /* ── Tab switching ── */
    $(document).on('click', '.os-est-tab', function () {
        $('.os-est-tab').removeClass('active');
        $('.os-est-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('tab')).addClass('active');
        if ($(this).data('tab') === 'tab-distribution') renderDistTab();
    });

    /* ── Safety margin ── */
    $(document).on('click', '.os-est-margin-btn', function () {
        $('.os-est-margin-btn').removeClass('active');
        $(this).addClass('active');
        const v = $(this).data('margin');
        if (v === 'custom') { $('#os-custom-margin-wrap').show(); currentMargin = parseFloat($('#os-custom-margin-val').val()) || 0; }
        else { $('#os-custom-margin-wrap').hide(); currentMargin = parseFloat(v) || 0; }
    });
    $('#os-custom-margin-val').on('input', function () { currentMargin = parseFloat($(this).val()) || 0; });

    /* ── Manual mode ── */
    $('#os-manual-mode').on('change', function () {
        isManualMode = this.checked;
        if (Object.keys(lastGrandTotal).length) renderGrandTotal(lastGrandTotal);
    });

    /* ── Reset inputs ── */
    $('#os-reset-btn').on('click', function () {
        $('.os-est-grade-input').val(0);
        $('#os-grade-tables-wrap').hide();
        $('#os-results-wrap, #os-supplier-wrap').hide();
        $('#os-no-results-msg, #os-no-supplier-msg').show();
        lastGrandTotal = {};
        if (pieChart) { pieChart.destroy(); pieChart = null; }
        if (barChart)  { barChart.destroy(); barChart = null; }
    });

    /* ════════════════════════════════════════
       DISTRIBUTION TAB
    ════════════════════════════════════════ */
    function renderDistTab() {
        const $c = $('#os-dist-grades-container').empty();
        Object.keys(DEFAULT_DISTRIBUTIONS).forEach(grade => {
            const dist = activeDist[grade] || deepCopy(DEFAULT_DISTRIBUTIONS[grade]);
            $c.append(buildGradeCard(grade, dist));
        });
        updateAllSumBadges();
    }

    function buildGradeCard(grade, dist) {
        const label = GRADE_LABELS[grade] || grade;
        let rows = '';
        Object.entries(dist).forEach(([sz, pct]) => {
            rows += sizeRow(grade, sz, pct);
        });

        const usedSizes = Object.keys(dist).map(Number);
        const available = ALL_SIZES.filter(s => !usedSizes.includes(s));
        let opts = available.map(s => `<option value="${s}">Size ${s}</option>`).join('');

        return `<div class="os-dist-grade-card" data-grade="${grade}">
            <div class="os-dist-grade-header">
                <strong>${label}</strong>
                <span class="os-dist-sum-badge" data-grade="${grade}">–</span>
                <button class="button os-dist-reset-grade-btn" data-grade="${grade}" title="Reset to default">
                    <span class="dashicons dashicons-undo"></span>
                </button>
            </div>
            <table class="os-dist-table">
                <thead><tr><th>Size</th><th>% Share</th><th></th></tr></thead>
                <tbody class="os-dist-tbody" data-grade="${grade}">${rows}</tbody>
            </table>
            <div class="os-dist-add-row">
                <select class="os-dist-add-size-sel" data-grade="${grade}">${opts || '<option value="">No sizes left</option>'}</select>
                <button class="button os-dist-add-size-btn" data-grade="${grade}">+ Add Size</button>
            </div>
        </div>`;
    }

    function sizeRow(grade, sz, pct) {
        return `<tr data-size="${sz}">
            <td><span class="os-dist-size-chip">Size ${sz}</span></td>
            <td><input type="number" class="os-dist-pct-input" data-grade="${grade}" data-size="${sz}" value="${pct}" min="0" max="100" step="0.01" /></td>
            <td><button class="button os-dist-remove-btn" data-grade="${grade}" data-size="${sz}" title="Remove size"><span class="dashicons dashicons-no-alt"></span></button></td>
        </tr>`;
    }

    /* update sum badge for one grade */
    function updateSumBadge(grade) {
        let sum = 0;
        $(`.os-dist-pct-input[data-grade="${grade}"]`).each(function () { sum += parseFloat($(this).val()) || 0; });
        sum = Math.round(sum * 100) / 100;
        const $b = $(`.os-dist-sum-badge[data-grade="${grade}"]`);
        $b.text('Σ ' + sum + '%');
        $b.removeClass('dist-ok dist-err');
        $b.addClass(Math.abs(sum - 100) <= 0.5 ? 'dist-ok' : 'dist-err');
    }

    function updateAllSumBadges() {
        Object.keys(DEFAULT_DISTRIBUTIONS).forEach(g => updateSumBadge(g));
    }

    /* pct input change */
    $(document).on('input', '.os-dist-pct-input', function () {
        updateSumBadge($(this).data('grade'));
    });

    /* remove size */
    $(document).on('click', '.os-dist-remove-btn', function () {
        const grade = $(this).data('grade');
        const sz    = parseInt($(this).data('size'));
        const $tbody = $(`.os-dist-tbody[data-grade="${grade}"]`);
        $(this).closest('tr').remove();
        // add back to available dropdown
        const $sel = $(`.os-dist-add-size-sel[data-grade="${grade}"]`);
        if (!$sel.find(`option[value="${sz}"]`).length) {
            $sel.append(`<option value="${sz}">Size ${sz}</option>`);
            // keep sorted
            const opts = $sel.find('option').get().sort((a,b) => +a.value - +b.value);
            $sel.empty().append(opts);
        }
        updateSumBadge(grade);
    });

    /* add size */
    $(document).on('click', '.os-dist-add-size-btn', function () {
        const grade = $(this).data('grade');
        const $sel  = $(`.os-dist-add-size-sel[data-grade="${grade}"]`);
        const sz    = parseInt($sel.val());
        if (!sz) return;
        const $tbody = $(`.os-dist-tbody[data-grade="${grade}"]`);
        $tbody.append(sizeRow(grade, sz, 0));
        $sel.find(`option[value="${sz}"]`).remove();
        updateSumBadge(grade);
    });

    /* reset one grade */
    $(document).on('click', '.os-dist-reset-grade-btn', function () {
        const grade = $(this).data('grade');
        activeDist[grade] = deepCopy(DEFAULT_DISTRIBUTIONS[grade]);
        const $card = $(`.os-dist-grade-card[data-grade="${grade}"]`);
        $card.replaceWith(buildGradeCard(grade, activeDist[grade]));
        updateSumBadge(grade);
    });

    /* reset ALL */
    $('#os-dist-reset-all-btn').on('click', function () {
        if (!confirm('Reset all distributions to factory defaults?')) return;
        activeDist = deepCopy(DEFAULT_DISTRIBUTIONS);
        renderDistTab();
    });

    /* read active distributions from UI inputs */
    function readDistFromUI() {
        const result = {};
        Object.keys(DEFAULT_DISTRIBUTIONS).forEach(grade => {
            const sizes = {};
            $(`.os-dist-pct-input[data-grade="${grade}"]`).each(function () {
                const sz  = parseInt($(this).data('size'));
                const pct = parseFloat($(this).val()) || 0;
                if (pct > 0) sizes[sz] = pct;
            });
            if (Object.keys(sizes).length) result[grade] = sizes;
            else result[grade] = deepCopy(DEFAULT_DISTRIBUTIONS[grade]);
        });
        return result;
    }

    /* validate distributions on calculate */
    function validateDist(dist, grades) {
        const errors = [];
        Object.entries(grades).forEach(([grade, count]) => {
            if (!count) return;
            const d = dist[grade];
            if (!d) return;
            const sum = Object.values(d).reduce((a,b) => a+b, 0);
            if (Math.abs(sum - 100) > 0.5) errors.push(GRADE_LABELS[grade] + ' (Σ=' + Math.round(sum*100)/100 + '%)');
        });
        return errors;
    }

    /* save distribution */
    $('#os-dist-save-btn').on('click', function () {
        const dist = readDistFromUI();
        const $btn = $(this).prop('disabled', true).html('<span class="os-est-spinner"></span> Saving…');
        $.post(AJAX_URL, {
            action: 'os_save_estimation_distribution',
            nonce:  NONCE,
            distribution: JSON.stringify(dist),
        }, function (res) {
            if (res.success) { activeDist = dist; alert('Distribution saved!'); }
            else alert('Error: ' + (res.data || 'Save failed.'));
        }).always(() => $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Distribution'));
    });

    /* ════════════════════════════════════════
       CALCULATE
    ════════════════════════════════════════ */
    $('#os-calculate-btn').on('click', function () {
        const grades = collectGradeInputs();
        if (!Object.values(grades).some(v => v > 0)) { alert('Please enter at least one grade count.'); return; }

        // Sync activeDist from UI if dist tab was visited
        if ($('#os-dist-grades-container').children().length) activeDist = readDistFromUI();

        const errors = validateDist(activeDist, grades);
        if (errors.length) {
            alert('Distribution percentages do not sum to 100% for:\n' + errors.join('\n') + '\n\nPlease fix in the Distribution Config tab.');
            return;
        }

        const $btn = $(this).prop('disabled', true).html('<span class="os-est-spinner"></span> Calculating…');

        fetchSupplierReportData(function() {
            runCalculation(grades);
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-calculator"></span> Calculate Estimation');
        });
    });

    function fetchSupplierReportData(callback) {
        if (supplierReportData !== null) {
            if (callback) callback();
            return;
        }
        const activeYearId = RAW.activeYearId || 0;
        if (activeYearId) {
            $.post(AJAX_URL, {
                action: 'os_get_supplier_report_data',
                nonce : NONCE,
                year_id: activeYearId,
                selected_model_ids: JSON.stringify(selectedModelIds),
            }, function (res) {
                if (res.success) {
                    supplierReportData = res.data;
                    // Update dynamic TYPES from what PHP returned
                    if (res.data.qualifying_categories && res.data.qualifying_categories.length) {
                        SupplierSummaryService.TYPES = res.data.qualifying_categories.map(c => c.name);
                        // Rebuild MANUAL_MODELS from server response (authoritative)
                        MANUAL_MODELS = new Set(
                            res.data.qualifying_categories
                                .filter(c => c.calculation_type === 'manual')
                                .map(c => c.name)
                        );
                    }
                }
                if (callback) callback();
            }).fail(function () {
                if (callback) callback();
            });
        } else {
            if (callback) callback();
        }
    }

    // Fetch initially to make reports 2 & 4 available immediately
    fetchSupplierReportData(function() {
        renderSupplierTable(lastGrandTotal);
    });

    function runCalculation(grades) {
        const { perGrade, grandTotal } = calculate(grades, currentMargin);
        lastGrandTotal = grandTotal;

        renderGradeTables(perGrade);
        renderGrandTotal(grandTotal);
        renderItemsBreakdown(grandTotal);
        renderSupplierTable(grandTotal);
        renderCharts(perGrade, grandTotal);

        $('#os-grade-tables-wrap').show();
        $('#os-no-results-msg').hide(); $('#os-results-wrap').show();
    }


    function collectGradeInputs() {
        const g = {};
        $('.os-est-grade-input').each(function () { g[$(this).data('grade')] = Math.max(0, parseInt($(this).val()) || 0); });
        return g;
    }

    function calculate(grades, marginPct) {
        const perGrade = {}, grandRaw = {}, grandFinal = {};
        Object.entries(grades).forEach(([grade, count]) => {
            if (!count || !activeDist[grade]) return;

            const dist  = activeDist[grade];
            const sizes = Object.keys(dist).map(Number).sort((a,b)=>a-b);
            const raw   = {};

            // ── If we have actual size data for this grade, use it ──
            const actual = actualSizeData[grade] || null;
            if (actual && Object.keys(actual).length > 0) {
                const actualTotal = Object.values(actual).reduce((a,b)=>a+b,0);
                const unsized     = Math.max(0, count - actualTotal);

                // Start with actual counts
                Object.entries(actual).forEach(([sz, cnt]) => {
                    raw[parseInt(sz)] = cnt;
                });

                // Estimate only the remaining unsized students using historical distribution
                if (unsized > 0) {
                    let assigned = 0;
                    sizes.forEach((sz, i) => {
                        const est = i < sizes.length - 1
                            ? Math.round(unsized * dist[sz] / 100)
                            : unsized - assigned;
                        assigned += est;
                        raw[sz] = (raw[sz] || 0) + est;
                    });
                }
            } else {
                // No actual data — use pure historical distribution
                let assigned = 0;
                sizes.forEach((sz, i) => {
                    if (i < sizes.length - 1) { raw[sz] = Math.round(count * dist[sz] / 100); assigned += raw[sz]; }
                    else raw[sz] = count - assigned;
                });
            }

            perGrade[grade] = raw;
            Object.keys(raw).forEach(sz => { grandRaw[sz] = (grandRaw[sz] || 0) + raw[sz]; });
        });
        ALL_SIZES.forEach(sz => { if (grandRaw[sz]) grandFinal[sz] = Math.ceil(grandRaw[sz] * (1 + marginPct / 100)); });
        return { perGrade, grandTotal: grandFinal };
    }


    /* ── Render per-grade tables ── */
    function renderGradeTables(perGrade) {
        let html = '<div class="os-est-grid-2">';
        Object.entries(perGrade).forEach(([grade, sizes]) => {
            const total = Object.values(sizes).reduce((a,b)=>a+b,0);
            html += `<table class="os-grade-estimation-table">
                <caption>${GRADE_LABELS[grade]||grade}</caption>
                <thead><tr><th>Size</th><th>Qty</th><th>%</th></tr></thead><tbody>`;
            Object.entries(sizes).forEach(([sz,qty]) => {
                html += `<tr><td><strong>${sz}</strong></td><td>${qty}</td><td>${total?((qty/total)*100).toFixed(1):0}%</td></tr>`;
            });
            html += `<tr class="total-row"><td colspan="2"><strong>Total</strong></td><td><strong>${total}</strong></td></tr></tbody></table>`;
        });
        html += '</div>';
        $('#os-grade-tables-inner').html(html);
    }

    /* ── Render grand total ── */
    function renderGrandTotal(grandTotal) {
        const overall = Object.values(grandTotal).reduce((a,b)=>a+b,0);
        let html = `<table class="os-grand-total-table widefat"><thead><tr>
            <th>Size</th><th>Base Qty</th>${currentMargin>0?`<th>+${currentMargin}% Buffer</th>`:''}<th>Final Qty</th>${isManualMode?'<th>Manual Override</th>':''}
        </tr></thead><tbody>`;
        ALL_SIZES.forEach(sz => {
            if (!grandTotal[sz]) return;
            const f = grandTotal[sz], b = Math.round(f/(1+currentMargin/100)), buf = f-b;
            html += `<tr><td><strong>Size ${sz}</strong></td><td class="qty-cell">${b}</td>
                ${currentMargin>0?`<td class="margin-cell">+${buf}</td>`:''}
                <td class="final-cell">${f}</td>
                ${isManualMode?`<td><input type="number" class="os-manual-input" data-size="${sz}" value="${f}" min="0"/></td>`:''}
            </tr>`;
        });
        html += `<tr class="total-row"><td><strong>TOTAL</strong></td><td colspan="${currentMargin>0?3:2}" class="qty-cell">${overall}</td>${isManualMode?'<td></td>':''}</tr></tbody></table>`;
        $('#os-grand-total-wrap').html(html);
        if (isManualMode) {
            $(document).off('input.manual').on('input.manual', '.os-manual-input', function () {
                lastGrandTotal[$(this).data('size')] = parseInt($(this).val())||0;
                renderItemsBreakdown(lastGrandTotal); renderSupplierTable(lastGrandTotal);
            });
        }
    }

    /* ── Items breakdown ── */
    function renderItemsBreakdown(gt) {
        // Build dynamic item list from the qualifying models returned by PHP,
        // or fall back to the user-selected models if AJAX hasn't run yet.
        const types = SupplierSummaryService.TYPES.length
            ? SupplierSummaryService.TYPES
            : selectedModelIds.map(id => {
                const m = ALL_MODELS.find(x => x.id === id);
                return m ? m.name : 'Model ' + id;
            });

        const icons = ['👕','🧥','👖','🎽','🧣','🧤','👟','🎒'];
        let html = '<div class="os-items-grid">';
        types.forEach((label, idx) => {
            let total = 0;
            const icon = icons[idx] || '📦';
            html += `<div class="os-item-card"><div class="os-item-card-header">${icon} ${label}</div><div class="os-item-card-body">`;
            ALL_SIZES.forEach(sz => {
                if (!gt[sz]) return;
                total += gt[sz];
                html += `<div class="os-item-size-row"><span class="os-item-size-lbl">Size ${sz}</span><span class="os-item-size-qty">${gt[sz]}</span></div>`;
            });
            html += `<div class="os-item-size-row" style="border-top:2px solid #ccc;margin-top:6px;padding-top:6px;">
                <span class="os-item-size-lbl"><strong>Total</strong></span>
                <span class="os-item-size-qty" style="color:var(--est-success);"><strong>${total}</strong></span>
            </div></div></div>`;
        });
        html += '</div>';
        $('#os-items-breakdown-wrap').html(html);
    }

    /* ── Supplier Summary Calculation Service ── */
    const SupplierSummaryService = {
        // TYPES is dynamic — populated from qualifying_categories returned by PHP.
        // Falls back to survey-marked models from allCustomModels if PHP hasn't responded yet.
        TYPES: ALL_MODELS.filter(m => m.include_in_survey === 1).map(m => m.name),
        
        getReportConfig: function(reportType) {
            return {
                isCostBased: reportType >= 5,
                useActualScans: [2, 4, 6, 8].includes(reportType),
                useInventoryDeduction: [3, 4, 7, 8].includes(reportType)
            };
        },

        calculateData: function(gt, reportType) {
            const config = this.getReportConfig(reportType);
            const rows = [];
            let totals = { req: 0, stock: 0, net: 0, cost: 0, missingPrices: 0 };
            
            ALL_SIZES.forEach(sz => {
                const baseQty = config.useActualScans ? (supplierReportData?.actual_scans?.[sz] || 0) : (gt[sz] || 0);
                if (baseQty === 0) return;

                const rowData = { size: sz, items: {}, rowTotalCost: 0, hasMissingPrice: false };

                this.TYPES.forEach(type => {
                    const isManual = MANUAL_MODELS.has(type);
                    let reqQty;
                    if (isManual) {
                        // Use manually entered value (null = not yet entered)
                        const entered = manualQuantities[type]?.[sz];
                        reqQty = (entered !== undefined && entered !== '') ? parseInt(entered) : null;
                    } else {
                        reqQty = baseQty;
                    }

                    const stockQty = supplierReportData?.inventory?.[type]?.[sz] || 0;
                    const effectiveReq = reqQty ?? 0;
                    const netQty = config.useInventoryDeduction ? Math.max(0, effectiveReq - stockQty) : effectiveReq;
                    
                    let unitCost = null;
                    let totalCost = null;
                    
                    if (config.isCostBased) {
                        unitCost = supplierReportData?.supplier_pricing?.[type]?.[sz];
                        if (unitCost !== undefined && unitCost !== null) {
                            totalCost = netQty * unitCost;
                        } else {
                            unitCost = null;
                            rowData.hasMissingPrice = true;
                            totals.missingPrices++;
                        }
                    }

                    rowData.items[type] = {
                        req: reqQty,          // null for un-entered manual values
                        effectiveReq,         // 0 for null manuals
                        stock: stockQty,
                        net: netQty,
                        unitCost: unitCost,
                        totalCost: totalCost,
                        isManual: isManual,
                    };

                    totals.req += effectiveReq;
                    totals.stock += stockQty;
                    totals.net += netQty;
                    if (totalCost !== null) totals.cost += totalCost;
                });
                
                rows.push(rowData);
            });
            
            return { rows, totals, config, supplier: supplierReportData?.active_supplier || 'Unknown' };
        }
    };

    /* ── Supplier table ── */
    $('#os-supplier-report-type').on('change', function() {
        renderSupplierTable(lastGrandTotal);
    });

    function renderSupplierTable(gt) {
        const reportType = parseInt($('#os-supplier-report-type').val()) || 1;
        const config = SupplierSummaryService.getReportConfig(reportType);
        const TYPES  = SupplierSummaryService.TYPES;
        
        if (!config.useActualScans && Object.keys(gt).length === 0) {
            $('#os-no-supplier-msg').show();
            $('#os-supplier-table-wrap').hide();
            return;
        }

        $('#os-no-supplier-msg').hide();
        $('#os-supplier-table-wrap').show();

        const data = SupplierSummaryService.calculateData(gt, reportType);
        const hasManual = MANUAL_MODELS.size > 0;
        let html = '';

        // ── "Apply Manual Quantities" button (shown only when manual models exist)
        if (hasManual) {
            html += `<div style="margin-bottom:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <button type="button" id="os-apply-manual-btn" class="button button-primary" style="display:flex;align-items:center;gap:6px;">
                    <span class="dashicons dashicons-saved" style="line-height:1.8;"></span>
                    Apply Manual Quantities
                </button>
                <span style="font-size:12px; color:#6b7280;">Fill in the ✏️ Manual columns below, then click Apply to recalculate totals.</span>
            </div>`;
        }

        if (config.isCostBased && data.supplier) {
            html += `<div style="margin-bottom: 12px; font-weight: 600;">Supplier: <span style="color:var(--os-primary);">${data.supplier}</span></div>`;
        }

        html += '<table class="os-supplier-table widefat"><thead><tr><th style="text-align:center;">Size</th>';
        
        if (config.isCostBased) {
            html += '<th style="text-align:center;">Item</th><th style="text-align:center;">Qty</th><th style="text-align:center;">Unit Cost</th><th style="text-align:center;">Total Cost</th><th style="text-align:center;">Price Status</th></tr></thead><tbody>';
            
            data.rows.forEach(row => {
                TYPES.forEach((type, idx) => {
                    const item = row.items[type];
                    if (!item) return; // guard against missing types
                    const sizeLabel = idx === 0 ? `<td rowspan="${TYPES.length}" style="text-align:center; vertical-align:middle; border-right:1px solid #ccc;"><span class="size-badge">${row.size}</span></td>` : '';
                    const priceStatus = item.unitCost === null ? '<span style="color:#d63638; font-weight:bold;">Missing</span>' : '<span style="color:#198754;">OK</span>';
                    const unitCostStr = item.unitCost === null ? 'N/A' : parseFloat(item.unitCost).toFixed(2);
                    const totalCostStr = item.totalCost === null ? 'N/A' : parseFloat(item.totalCost).toFixed(2);
                    
                    let qtyHtml = '';
                    if (item.isManual) {
                        const savedVal = manualQuantities[type]?.[row.size] ?? '';
                        if (config.useInventoryDeduction) {
                            qtyHtml = `<div style="display:flex; flex-direction:column; align-items:center; gap:2px; padding:4px;">
                                <input type="number" min="0" class="os-manual-qty" data-model="${type}" data-size="${row.size}" value="${savedVal}" style="width:65px; text-align:center; border:1px solid #d97706; border-radius:4px; padding:2px 4px;" placeholder="Req">
                                <span style="font-size:10px; color:#6b7280; white-space:nowrap;">Stock: ${item.stock} | Net: <strong style="color:#d63638;">${item.net}</strong></span>
                            </div>`;
                        } else {
                            qtyHtml = `<input type="number" min="0" class="os-manual-qty" data-model="${type}" data-size="${row.size}" value="${savedVal}" style="width:65px; text-align:center; border:1px solid #d97706; border-radius:4px; padding:2px 4px;">`;
                        }
                    } else {
                        qtyHtml = item.net;
                    }

                    html += `<tr>
                        ${sizeLabel}
                        <td style="text-align:center; font-weight:600; vertical-align:middle;">${type}</td>
                        <td style="text-align:center; vertical-align:middle;">${qtyHtml}</td>
                        <td style="text-align:center; vertical-align:middle;">${unitCostStr}</td>
                        <td style="text-align:center; vertical-align:middle;">${totalCostStr}</td>
                        <td style="text-align:center; vertical-align:middle;">${priceStatus}</td>
                    </tr>`;
                });
            });
            
            html += `<tr style="font-weight:800;background:#e6f4ea;">
                <td colspan="2" style="text-align:center; border-right:1px solid #ccc;"><strong>GRAND TOTAL</strong></td>
                <td style="text-align:center;">${data.totals.net}</td>
                <td></td>
                <td style="text-align:center;">${parseFloat(data.totals.cost).toFixed(2)}</td>
                <td style="text-align:center; color:${data.totals.missingPrices > 0 ? '#d63638' : '#198754'};">${data.totals.missingPrices > 0 ? data.totals.missingPrices + ' Missing' : 'All OK'}</td>
            </tr>`;
        } else {
            if (!config.useInventoryDeduction) {
                // Simple view: one column per model, same qty for each (size-based)
                TYPES.forEach(type => {
                    const isManual = MANUAL_MODELS.has(type);
                    html += `<th style="text-align:center;">${type}${isManual ? ' <span title="Manual entry" style="color:#92400e;">✏️</span>' : ''}</th>`;
                });
                html += '<th style="text-align:center; background:#e6f4ea;">Total Units</th></tr></thead><tbody>';
                data.rows.forEach(row => {
                    const firstAutoType = TYPES.find(t => !MANUAL_MODELS.has(t));
                    const autoQ = firstAutoType && row.items[firstAutoType] ? row.items[firstAutoType].effectiveReq : 0;
                    let rowTotal = 0;
                    html += `<tr><td style="text-align:center;"><span class="size-badge">${row.size}</span></td>`;
                    TYPES.forEach(type => {
                        const item = row.items[type] || {};
                        if (MANUAL_MODELS.has(type)) {
                            const savedVal = manualQuantities[type]?.[row.size] ?? '';
                            html += `<td style="text-align:center; background:#fffbeb;">`;
                            html += `<input type="number" min="0" class="os-manual-qty" data-model="${type}" data-size="${row.size}" value="${savedVal}" style="width:65px; text-align:center; border:1px solid #d97706; border-radius:4px; padding:2px 4px;"></td>`;
                            rowTotal += savedVal !== '' ? parseInt(savedVal) || 0 : 0;
                        } else {
                            html += `<td style="text-align:center;">${item.effectiveReq ?? autoQ}</td>`;
                            rowTotal += item.effectiveReq ?? autoQ;
                        }
                    });
                    html += `<td style="text-align:center; font-weight:bold; background:#e6f4ea;">${rowTotal}</td></tr>`;
                });
                // Grand total row
                let grandTotal = 0;
                html += `<tr style="font-weight:800;background:#dcfce7;"><td style="text-align:center;"><strong>GRAND TOTAL</strong></td>`;
                TYPES.forEach(type => {
                    if (MANUAL_MODELS.has(type)) {
                        const typeTotal = Object.values(manualQuantities[type] || {}).reduce((a,b) => a + (parseInt(b)||0), 0);
                        grandTotal += typeTotal;
                        html += `<td style="text-align:center; background:#fffbeb; font-weight:700; color:#92400e;">${typeTotal || '—'}</td>`;
                    } else {
                        const typeTotal = data.rows.reduce((s, r) => s + (r.items[type]?.effectiveReq || 0), 0);
                        grandTotal += typeTotal;
                        html += `<td style="text-align:center;">${typeTotal}</td>`;
                    }
                });
                html += `<td class="total-col" style="text-align:center; background:#dcfce7; color:var(--est-success); font-size:16px;"><strong>${grandTotal}</strong></td></tr>`;
            } else {
                // Stock-deduction view: Req | Stock | Net per model
                TYPES.forEach(type => {
                    html += `<th colspan="3" style="text-align:center; border-left:2px solid #ccc;">${type} (Req | Stock | Net)</th>`;
                });
                html += '</tr></thead><tbody>';

                // Per-model totals
                const modelTotals = {};
                TYPES.forEach(t => { modelTotals[t] = { req: 0, stk: 0, net: 0 }; });

                data.rows.forEach(r => {
                    html += `<tr><td style="text-align:center;"><span class="size-badge">${r.size}</span></td>`;
                    TYPES.forEach(type => {
                        const it = r.items[type] || { req: 0, stock: 0, net: 0 };
                        if (MANUAL_MODELS.has(type)) {
                            // Manual model: show input for req, auto-calc net
                            const savedVal = manualQuantities[type]?.[r.size] ?? '';
                            const effectiveReq = savedVal !== '' ? parseInt(savedVal) || 0 : 0;
                            const net = Math.max(0, effectiveReq - (it.stock || 0));
                            if (modelTotals[type]) {
                                modelTotals[type].req += effectiveReq;
                                modelTotals[type].stk += it.stock || 0;
                                modelTotals[type].net += net;
                            }
                            html += `<td style="text-align:center; border-left:2px solid #ccc; background:#fffbeb;">`;
                            html += `<input type="number" min="0" class="os-manual-qty" data-model="${type}" data-size="${r.size}" value="${savedVal}" style="width:55px;text-align:center;border:1px solid #d97706;border-radius:4px;padding:1px 3px;"></td>`;
                            html += `<td style="text-align:center;">${it.stock}</td>`;
                            html += `<td style="text-align:center; color:#d63638; font-weight:bold;">${net}</td>`;
                        } else {
                            if (modelTotals[type]) {
                                modelTotals[type].req += it.effectiveReq || 0;
                                modelTotals[type].stk += it.stock || 0;
                                modelTotals[type].net += it.net || 0;
                            }
                            html += `<td style="text-align:center; border-left:2px solid #ccc;">${it.effectiveReq}</td><td style="text-align:center;">${it.stock}</td><td style="text-align:center; color:#d63638; font-weight:bold;">${it.net}</td>`;
                        }
                    });
                    html += `</tr>`;
                });

                html += `<tr style="font-weight:800;background:#e6f4ea;"><td style="text-align:center;"><strong>GRAND TOTAL</strong></td>`;
                TYPES.forEach(type => {
                    const t = modelTotals[type] || { req: 0, stk: 0, net: 0 };
                    html += `<td style="text-align:center; border-left:2px solid #ccc;">${t.req}</td><td style="text-align:center;">${t.stk}</td><td style="text-align:center; color:#d63638;">${t.net}</td>`;
                });
                html += `</tr>`;
            }
        }
        
        html += `</tbody></table>`;
        $('#os-supplier-table-wrap').html(html);
    }

    /* ── Apply Manual Quantities handler ── */
    $(document).on('click', '#os-apply-manual-btn', function () {
        // Read all manual inputs from the table, store in manualQuantities
        $('#os-supplier-table-wrap .os-manual-qty').each(function () {
            const modelName = $(this).data('model');
            const size      = String($(this).data('size'));
            const val       = $(this).val().trim();
            if (!manualQuantities[modelName]) manualQuantities[modelName] = {};
            manualQuantities[modelName][size] = val;
        });
        // Re-render the table with updated values (no AJAX needed)
        renderSupplierTable(lastGrandTotal);
    });

    /* ── Charts ── */
    function renderCharts(perGrade, gt) {
        if (typeof Chart === 'undefined') return;
        const sizes = ALL_SIZES.filter(sz=>gt[sz]), qtys = sizes.map(sz=>gt[sz]);
        const pal   = sizes.map((_,i)=>`hsl(${Math.round(i/sizes.length*360)},65%,52%)`);
        if (pieChart) pieChart.destroy();
        pieChart = new Chart(document.getElementById('os-pie-chart'), {
            type:'doughnut',
            data:{ labels:sizes.map(s=>'Size '+s), datasets:[{data:qtys,backgroundColor:pal,borderWidth:2,borderColor:'#fff'}] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{position:'right'} } },
        });
        if (barChart) barChart.destroy();
        const gk = Object.keys(perGrade);
        barChart = new Chart(document.getElementById('os-bar-chart'), {
            type:'bar',
            data:{ labels:gk.map(g=>GRADE_LABELS[g]||g), datasets:sizes.map((sz,i)=>({ label:'Size '+sz, data:gk.map(g=>perGrade[g]?.[sz]||0), backgroundColor:pal[i]+'cc', borderColor:pal[i], borderWidth:1 })) },
            options:{ responsive:true, maintainAspectRatio:false, scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}}, plugins:{legend:{display:false}} },
        });
    }

    /* ── Export CSV ── */
    $('#os-export-csv-btn, #os-export-supplier-csv-btn').on('click', function () {
        const sup = $(this).is('#os-export-supplier-csv-btn');
        downloadCSV(sup ? buildSupplierCSV() : buildGrandCSV(), sup ? 'supplier.csv' : 'estimation.csv');
    });
    function buildGrandCSV() {
        const types = SupplierSummaryService.TYPES.length
            ? SupplierSummaryService.TYPES
            : selectedModelIds.map(id => {
                const m = ALL_MODELS.find(x => x.id === id);
                return m ? m.name : 'Model ' + id;
            });
        
        const headers = ['Size', 'Base Qty', 'Final Qty', ...types];
        const r = [headers];
        
        ALL_SIZES.forEach(sz => {
            if (!lastGrandTotal[sz]) return;
            const f = lastGrandTotal[sz];
            const b = Math.round(f / (1 + currentMargin / 100));
            const row = [sz, b, f];
            types.forEach(type => {
                if (MANUAL_MODELS.has(type)) {
                    const savedVal = manualQuantities[type]?.[sz] ?? '';
                    row.push(savedVal !== '' ? parseInt(savedVal) || 0 : 0);
                } else {
                    row.push(f);
                }
            });
            r.push(row);
        });
        return r;
    }
    function buildSupplierCSV() {
        const reportType = parseInt($('#os-supplier-report-type').val()) || 1;
        const config = SupplierSummaryService.getReportConfig(reportType);
        const data = SupplierSummaryService.calculateData(lastGrandTotal, reportType);
        const r = [];

        if (!config.useActualScans && Object.keys(lastGrandTotal).length === 0) {
            return [['Please calculate an estimation first.']];
        }

        if (config.isCostBased) {
            r.push(['Supplier:', data.supplier]);
            r.push(['Size', 'Item', 'Qty', 'Unit Cost', 'Total Cost', 'Price Status']);
            data.rows.forEach(row => {
                SupplierSummaryService.TYPES.forEach((type, idx) => {
                    const item = row.items[type];
                    if (!item) return;
                    const unitCostStr = item.unitCost === null ? 'N/A' : parseFloat(item.unitCost).toFixed(2);
                    const totalCostStr = item.totalCost === null ? 'N/A' : parseFloat(item.totalCost).toFixed(2);
                    const statusStr = item.unitCost === null ? 'Missing' : 'OK';
                    r.push([idx === 0 ? row.size : '', type, item.net, unitCostStr, totalCostStr, statusStr]);
                });
            });
            r.push(['GRAND TOTAL', '', data.totals.net, '', parseFloat(data.totals.cost).toFixed(2), data.totals.missingPrices > 0 ? data.totals.missingPrices + ' Missing' : 'All OK']);
        } else {
            if (!config.useInventoryDeduction) {
                r.push(['Size', ...SupplierSummaryService.TYPES, 'Total Units']);
                data.rows.forEach(row => {
                    const firstAutoType = SupplierSummaryService.TYPES.find(t => !MANUAL_MODELS.has(t));
                    const autoQ = firstAutoType && row.items[firstAutoType] ? row.items[firstAutoType].effectiveReq : 0;
                    const rowData = [row.size];
                    let rowTotal = 0;
                    SupplierSummaryService.TYPES.forEach(type => {
                        const item = row.items[type] || {};
                        if (MANUAL_MODELS.has(type)) {
                            const savedVal = manualQuantities[type]?.[row.size] ?? '';
                            const val = savedVal !== '' ? parseInt(savedVal) || 0 : 0;
                            rowData.push(val);
                            rowTotal += val;
                        } else {
                            const val = item.effectiveReq ?? autoQ;
                            rowData.push(val);
                            rowTotal += val;
                        }
                    });
                    rowData.push(rowTotal);
                    r.push(rowData);
                });
                
                const totalRow = ['GRAND TOTAL'];
                let grandTotal = 0;
                SupplierSummaryService.TYPES.forEach(type => {
                    if (MANUAL_MODELS.has(type)) {
                        const typeTotal = Object.values(manualQuantities[type] || {}).reduce((a,b) => a + (parseInt(b)||0), 0);
                        grandTotal += typeTotal;
                        totalRow.push(typeTotal);
                    } else {
                        const typeTotal = data.rows.reduce((s, r) => s + (r.items[type]?.effectiveReq || 0), 0);
                        grandTotal += typeTotal;
                        totalRow.push(typeTotal);
                    }
                });
                totalRow.push(grandTotal);
                r.push(totalRow);
            } else {
                const headers = ['Size'];
                SupplierSummaryService.TYPES.forEach(type => {
                    headers.push(`${type} Req`, `${type} Stock`, `${type} Net`);
                });
                r.push(headers);
                
                const modelTotals = {};
                SupplierSummaryService.TYPES.forEach(t => { modelTotals[t] = { req: 0, stk: 0, net: 0 }; });

                data.rows.forEach(row => {
                    const rowData = [row.size];
                    SupplierSummaryService.TYPES.forEach(type => {
                        const it = row.items[type] || { req: 0, stock: 0, net: 0 };
                        if (MANUAL_MODELS.has(type)) {
                            const savedVal = manualQuantities[type]?.[row.size] ?? '';
                            const effectiveReq = savedVal !== '' ? parseInt(savedVal) || 0 : 0;
                            const net = Math.max(0, effectiveReq - (it.stock || 0));
                            if (modelTotals[type]) {
                                modelTotals[type].req += effectiveReq;
                                modelTotals[type].stk += it.stock || 0;
                                modelTotals[type].net += net;
                            }
                            rowData.push(effectiveReq, it.stock || 0, net);
                        } else {
                            if (modelTotals[type]) {
                                modelTotals[type].req += it.effectiveReq || 0;
                                modelTotals[type].stk += it.stock || 0;
                                modelTotals[type].net += it.net || 0;
                            }
                            rowData.push(it.effectiveReq, it.stock || 0, it.net || 0);
                        }
                    });
                    r.push(rowData);
                });
                
                const grandTotalRow = ['GRAND TOTAL'];
                SupplierSummaryService.TYPES.forEach(type => {
                    const t = modelTotals[type] || { req: 0, stk: 0, net: 0 };
                    grandTotalRow.push(t.req, t.stk, t.net);
                });
                r.push(grandTotalRow);
            }
        }
        return r;
    }
    function downloadCSV(rows, fn) {
        const blob=new Blob([rows.map(r=>r.join(',')).join('\n')],{type:'text/csv'});
        const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=fn; a.click();
    }

    /* ── Export Excel ── */
    $('#os-export-excel-btn, #os-export-supplier-excel-btn').on('click', function () {
        if (typeof XLSX==='undefined'){alert('SheetJS loading, try again.');return;}
        const sup=$(this).is('#os-export-supplier-excel-btn');
        const ws=XLSX.utils.aoa_to_sheet(sup?buildSupplierCSV():buildGrandCSV());
        const wb=XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb,ws,sup?'Supplier':'Estimation');
        XLSX.writeFile(wb, sup?'supplier.xlsx':'estimation.xlsx');
    });

    /* ── Print ── */
    $('#os-print-btn').on('click', ()=>window.print());

    /* ── Save draft ── */
    $('#os-save-draft-btn').on('click', function () {
        const name = $('#os-draft-name').val().trim();
        if (!name) { alert('Enter a draft name.'); return; }
        const grades = collectGradeInputs();
        if (!Object.values(grades).some(v=>v>0)) { alert('Enter grade counts first.'); return; }
        const $b=$(this).prop('disabled',true).html('<span class="os-est-spinner"></span> Saving…');
        $.post(AJAX_URL,{action:'os_save_estimation_draft',nonce:NONCE,name,grades:JSON.stringify(grades),margin:currentMargin},function(res){
            if(res.success){DRAFTS=res.data.drafts;refreshDraftsList();alert('Draft saved!');}
            else alert('Error: '+(res.data||'Failed.'));
        }).always(()=>$b.prop('disabled',false).html('<span class="dashicons dashicons-saved"></span> Save Draft'));
    });

    /* ── Load draft ── */
    $(document).on('click','.os-load-draft-btn',function(){
        const d=DRAFTS[parseInt($(this).data('idx'))]; if(!d)return;
        Object.entries(d.grades||{}).forEach(([g,v])=>$(`#grade-${g.toUpperCase()}`).val(v));
        currentMargin=parseFloat(d.margin)||0;
        $('.os-est-margin-btn').removeClass('active');
        [0,5,10].includes(currentMargin)?$(`.os-est-margin-btn[data-margin="${currentMargin}"]`).addClass('active'):($(`.os-est-margin-btn[data-margin="custom"]`).addClass('active'),$('#os-custom-margin-wrap').show(),$('#os-custom-margin-val').val(currentMargin));
        $('.os-est-tab[data-tab="tab-input"]').trigger('click');
        $('#os-calculate-btn').trigger('click');
    });

    /* ── Delete draft ── */
    $(document).on('click','.os-delete-draft-btn',function(){
        if(!confirm('Delete this draft?'))return;
        const idx=parseInt($(this).data('idx'));
        $.post(AJAX_URL,{action:'os_delete_estimation_draft',nonce:NONCE,idx},function(res){ if(res.success){DRAFTS=res.data.drafts;refreshDraftsList();} });
    });

    function refreshDraftsList() {
        if(!DRAFTS.length){$('#os-drafts-list-wrap').html('<p class="os-est-notice"><span class="dashicons dashicons-info"></span> No drafts saved yet.</p>');return;}
        let html=`<table class="wp-list-table widefat striped os-drafts-table"><thead><tr><th>Name</th><th>Saved On</th><th>Students</th><th>Actions</th></tr></thead><tbody>`;
        DRAFTS.forEach((d,i)=>{
            const t=Object.values(d.grades||{}).reduce((a,b)=>a+parseInt(b),0);
            html+=`<tr><td><strong>${d.name||'-'}</strong></td><td>${d.saved_at||'-'}</td><td>${t}</td><td>
                <button class="button os-load-draft-btn" data-idx="${i}"><span class="dashicons dashicons-download"></span> Load</button>
                <button class="button os-delete-draft-btn" data-idx="${i}"><span class="dashicons dashicons-trash"></span> Delete</button>
            </td></tr>`;
        });
        html+='</tbody></table>';
        $('#os-drafts-list-wrap').html(html);
    }

})(jQuery);
