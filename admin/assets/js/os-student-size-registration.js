/**
 * os-student-size-registration.js
 * Handles the "Student Size Registration" tab — school-integrated version.
 * Grade/section dropdowns are populated from Olama School DB via AJAX.
 * Academic year and semester are read-only from the active school year.
 */
(function ($) {
    'use strict';

    /* ═══════════════════════════════════
       CONFIG
    ═══════════════════════════════════ */
    const RAW      = JSON.parse(document.getElementById('os-estimation-data')?.textContent || '{}');
    const AJAX_URL = RAW.ajaxUrl || '';
    const NONCE    = RAW.uniformSizeNonce || '';

    // Read active year/semester from hidden inputs in the view
    const $yearId   = $('#os-sr-year-id');
    const $yearName = $('#os-sr-year-name');
    const $semId    = $('#os-sr-sem-id');

    // State
    let currentStudents = [];
    let currentAllowed  = [];
    let currentGradeId  = 0;
    let currentGradeName = '';
    let currentSectionId = 0;
    let pendingSaves     = new Set();
    let saveTimers       = {};

    /* ═══════════════════════════════════
       GRADE → SECTIONS CASCADE
    ═══════════════════════════════════ */
    $('#os-sr-grade').on('change', function () {
        const gradeId   = parseInt( $(this).val() ) || 0;
        const gradeName = $(this).find(':selected').data('name') || '';

        currentGradeId   = gradeId;
        currentGradeName = gradeName;
        currentSectionId = 0;

        const $secSel = $('#os-sr-section');
        const $spin   = $('#os-sr-section-spinner');

        if ( ! gradeId ) {
            $secSel.html('<option value="">— Select Grade First —</option>').prop('disabled', true);
            return;
        }

        // Fetch sections from Olama School for this grade + active year
        $secSel.html('<option value="">Loading…</option>').prop('disabled', true);
        $spin.show();

        $.post(AJAX_URL, {
            action   : 'os_get_sections_for_grade',
            nonce    : NONCE,
            grade_id : gradeId,
            year_id  : $yearId.val(),
        }, function (res) {
            $spin.hide();
            if (!res.success || !res.data.sections.length) {
                $secSel.html('<option value="">No sections found</option>').prop('disabled', true);
                return;
            }
            let opts = '<option value="0">— All Sections —</option>';
            res.data.sections.forEach(s => {
                opts += `<option value="${s.id}">${esc(s.section_name)}</option>`;
            });
            $secSel.html(opts).prop('disabled', false);
        }).fail(function () {
            $spin.hide();
            $secSel.html('<option value="">Error loading sections</option>').prop('disabled', true);
        });
    });

    // Track selected section
    $('#os-sr-section').on('change', function () {
        currentSectionId = parseInt( $(this).val() ) || 0;
    });

    /* ═══════════════════════════════════
       LOAD STUDENTS
    ═══════════════════════════════════ */
    $('#os-sr-load-btn').on('click', loadStudents);

    function loadStudents() {
        const yearId    = parseInt( $yearId.val() ) || 0;
        const gradeId   = parseInt( $('#os-sr-grade').val() ) || 0;
        const sectionId = parseInt( $('#os-sr-section').val() ) || 0;
        const filter    = $('#os-sr-filter').val() || 'all';

        if (!yearId)  { alert('No active academic year found.'); return; }
        if (!gradeId) { alert('Please select a grade.'); return; }

        currentGradeId   = gradeId;
        currentGradeName = $('#os-sr-grade option:selected').data('name') || '';
        currentSectionId = sectionId;

        showState('spinner');

        $.post(AJAX_URL, {
            action     : 'os_get_students_for_sizing',
            nonce      : NONCE,
            year_id    : yearId,
            grade_id   : gradeId,
            section_id : sectionId,
            filter     : filter,
        }, function (res) {
            if (!res.success) {
                showState('initial');
                alert(res.data?.message || 'Failed to load students.');
                return;
            }
            currentStudents = res.data.students || [];
            currentAllowed  = res.data.allowed_sizes || [];

            if (!currentStudents.length) {
                showState('empty');
                updateStats(res.data.completion);
                return;
            }

            showState('table');
            updateStats(res.data.completion);
            buildTable(currentStudents, currentAllowed);
            buildSizeSummaryFromStudents();

        }).fail(function () {
            showState('initial');
            alert('Network error. Please try again.');
        });
    }

    /* ═══════════════════════════════════
       BUILD TABLE
    ═══════════════════════════════════ */
    function buildTable(students, sizes) {
        let thead = '<tr><th class="os-sr-th-name">Student Name</th>';
        sizes.forEach(sz => {
            thead += `<th class="os-sr-th-size"><span class="os-sr-size-badge">${sz}</span></th>`;
        });
        thead += '<th class="os-sr-th-status">Status</th></tr>';
        $('#os-sr-table-head').html(thead);

        let tbody = '';
        students.forEach(s => {
            const uid    = s.student_uid || '';
            const name   = s.student_name || uid;
            const curSz  = s.current_size;
            const isSized = curSz !== null && curSz !== undefined;
            const rowCls  = isSized ? 'os-sr-row-sized' : 'os-sr-row-unsized';
            const sectionLabel = s.section_name ? ` <span class="os-sr-section-tag">${esc(s.section_name)}</span>` : '';

            tbody += `<tr class="os-sr-row ${rowCls}" data-uid="${esc(uid)}" data-sized="${isSized ? 1 : 0}">`;
            tbody += `<td class="os-sr-td-name">
                        <div class="os-sr-student-name">${esc(name)}${sectionLabel}</div>
                        ${s.measured_at ? `<div class="os-sr-measured-at">📅 ${s.measured_at}</div>` : ''}
                      </td>`;
            sizes.forEach(sz => {
                const checked = curSz === sz;
                tbody += `<td class="os-sr-td-radio ${checked ? 'os-sr-radio-selected' : ''}">
                            <label class="os-sr-radio-label">
                                <input type="radio" class="os-sr-radio"
                                    name="size_${esc(uid)}" data-uid="${esc(uid)}" data-size="${sz}"
                                    value="${sz}" ${checked ? 'checked' : ''} />
                                <span class="os-sr-radio-custom"></span>
                            </label>
                          </td>`;
            });
            const statusHtml = isSized
                ? `<span class="os-sr-status-badge os-sr-status-sized">✓ Size ${curSz}</span>`
                : `<span class="os-sr-status-badge os-sr-status-unsized">—</span>`;
            const deleteBtn = isSized
                ? `<button class="os-sr-clear-btn" data-uid="${esc(uid)}" title="Clear">✕</button>`
                : '';
            tbody += `<td class="os-sr-td-status">${statusHtml} ${deleteBtn}</td></tr>`;
        });

        $('#os-sr-table-body').html(tbody);
        updateRowCount();
    }

    /* ═══════════════════════════════════
       AUTO-SAVE ON RADIO CHANGE
    ═══════════════════════════════════ */
    $(document).on('change', '.os-sr-radio', function () {
        const $r   = $(this);
        const uid  = $r.data('uid');
        const size = parseInt( $r.data('size') );

        $r.closest('tr').find('.os-sr-td-radio').removeClass('os-sr-radio-selected');
        $r.closest('td').addClass('os-sr-radio-selected');

        clearTimeout(saveTimers[uid]);
        saveTimers[uid] = setTimeout(() => saveStudentSize(uid, size, $r.closest('tr')), 300);
    });

    function saveStudentSize(uid, size, $row) {
        if (pendingSaves.has(uid)) return;
        pendingSaves.add(uid);

        const $st = $row.find('.os-sr-td-status');
        $st.html('<span class="os-sr-saving">💾 Saving…</span>');

        $.post(AJAX_URL, {
            action     : 'os_save_student_size',
            nonce      : NONCE,
            student_uid : uid,
            year_id     : $yearId.val(),
            grade_id    : currentGradeId,
            section_id  : currentSectionId,
            uniform_size: size,
        }, function (res) {
            pendingSaves.delete(uid);
            if (!res.success) {
                $st.html(`<span class="os-sr-error">❌ ${res.data?.message || 'Error'}</span>`);
                return;
            }
            $row.addClass('os-sr-row-sized').removeClass('os-sr-row-unsized').attr('data-sized', 1);
            $st.html(
                `<span class="os-sr-status-badge os-sr-status-sized">✓ Size ${size}</span>
                 <button class="os-sr-clear-btn" data-uid="${esc(uid)}" title="Clear">✕</button>`
            );
            const stu = currentStudents.find(s => s.student_uid === uid);
            if (stu) { stu.current_size = size; stu.measured_at = new Date().toISOString().slice(0, 16).replace('T', ' '); }
            updateStats(res.data.completion);
            buildSizeSummaryFromStudents();
            $row.addClass('os-sr-flash-ok');
            setTimeout(() => $row.removeClass('os-sr-flash-ok'), 1200);
        }).fail(() => {
            pendingSaves.delete(uid);
            $st.html('<span class="os-sr-error">❌ Network error</span>');
        });
    }

    /* ═══════════════════════════════════
       CLEAR SIZE
    ═══════════════════════════════════ */
    $(document).on('click', '.os-sr-clear-btn', function () {
        if (!confirm('Remove size for this student?')) return;
        const uid  = $(this).data('uid');
        const $row = $(`.os-sr-row[data-uid="${uid}"]`);
        const $st  = $row.find('.os-sr-td-status');

        $st.html('<span class="os-sr-saving">🗑 Removing…</span>');

        $.post(AJAX_URL, {
            action      : 'os_delete_student_size',
            nonce       : NONCE,
            student_uid : uid,
            year_id     : $yearId.val(),
        }, function (res) {
            if (!res.success) {
                $st.html(`<span class="os-sr-error">❌ ${res.data?.message || 'Error'}</span>`);
                return;
            }
            $row.find('.os-sr-radio').prop('checked', false);
            $row.find('.os-sr-td-radio').removeClass('os-sr-radio-selected');
            $row.removeClass('os-sr-row-sized').addClass('os-sr-row-unsized').attr('data-sized', 0);
            $st.html('<span class="os-sr-status-badge os-sr-status-unsized">—</span>');
            const stu = currentStudents.find(s => s.student_uid === uid);
            if (stu) { stu.current_size = null; stu.measured_at = null; }
            reCalcStatsLocally();
            buildSizeSummaryFromStudents();
        }).fail(() => $st.html('<span class="os-sr-error">❌ Network error</span>'));
    });

    /* ═══════════════════════════════════
       LIVE SEARCH
    ═══════════════════════════════════ */
    let searchTimer;
    $('#os-sr-search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const q = $(this).val().toLowerCase();
            let visible = 0;
            $('#os-sr-table-body tr').each(function () {
                const show = $(this).find('.os-sr-student-name').text().toLowerCase().includes(q);
                $(this).toggle(show);
                if (show) visible++;
            });
            updateRowCount(visible);
        }, 150);
    });

    /* ═══════════════════════════════════
       STATS
    ═══════════════════════════════════ */
    function updateStats(c) {
        if (!c) return;
        $('#os-sr-stats-bar').show();
        $('#os-sr-stat-total').text(c.total);
        $('#os-sr-stat-sized').text(c.sized);
        $('#os-sr-stat-unsized').text(c.unsized);
        updateRing(c.percentage);
    }

    function reCalcStatsLocally() {
        const total = currentStudents.length;
        const sized = currentStudents.filter(s => s.current_size !== null && s.current_size !== undefined).length;
        updateStats({ total, sized, unsized: total - sized, percentage: total > 0 ? Math.round((sized/total)*1000)/10 : 0 });
    }

    function updateRing(pct) {
        $('#os-sr-ring-pct').text(pct + '%');
        const fill = Math.min(100, Math.max(0, pct));
        $('#os-sr-ring-fill').attr('stroke-dasharray', fill + ' ' + (100 - fill));
    }

    /* ═══════════════════════════════════
       SIZE SUMMARY CHIPS
    ═══════════════════════════════════ */
    function buildSizeSummaryFromStudents() {
        const counts = {};
        currentAllowed.forEach(sz => counts[sz] = 0);
        currentStudents.forEach(s => {
            if (s.current_size !== null && s.current_size !== undefined) {
                counts[s.current_size] = (counts[s.current_size] || 0) + 1;
            }
        });
        let html  = '<div class="os-sr-summary-title">📊 Size Distribution (Actual)</div>';
        html += '<div class="os-sr-summary-chips">';
        let total = 0;
        currentAllowed.forEach(sz => {
            const cnt = counts[sz] || 0;
            total += cnt;
            html += `<div class="os-sr-summary-chip ${cnt > 0 ? 'has-data' : ''}">
                        <span class="os-sr-chip-size">${sz}</span>
                        <span class="os-sr-chip-count">${cnt}</span>
                     </div>`;
        });
        html += `<div class="os-sr-summary-chip os-sr-chip-total">
                    <span class="os-sr-chip-size">Total</span>
                    <span class="os-sr-chip-count">${total}</span>
                 </div></div>`;
        $('#os-sr-size-summary').html(html).show();
    }

    /* ═══════════════════════════════════
       ROW COUNT
    ═══════════════════════════════════ */
    function updateRowCount(n) {
        const count = n !== undefined ? n : currentStudents.length;
        $('#os-sr-row-count').text(count + ' student(s) shown');
    }

    /* ═══════════════════════════════════
       EXPORT CSV
    ═══════════════════════════════════ */
    $('#os-sr-export-csv-btn').on('click', function () {
        if (!currentGradeId) { alert('Load students first.'); return; }
        const form = $('<form method="POST" action="' + AJAX_URL + '" style="display:none">')
            .append($('<input>').attr({type:'hidden', name:'action',     value:'os_export_sizes_csv'}))
            .append($('<input>').attr({type:'hidden', name:'nonce',      value:NONCE}))
            .append($('<input>').attr({type:'hidden', name:'year_id',    value:$yearId.val()}))
            .append($('<input>').attr({type:'hidden', name:'grade_id',   value:currentGradeId}))
            .append($('<input>').attr({type:'hidden', name:'section_id', value:currentSectionId}));
        $('body').append(form);
        form.submit();
        setTimeout(() => form.remove(), 2000);
    });

    /* ═══════════════════════════════════
       PRINT
    ═══════════════════════════════════ */
    $('#os-sr-print-btn').on('click', () => printStudentRegistration(false));
    $('#os-sr-print-empty-btn').on('click', () => printStudentRegistration(true));

    function printStudentRegistration(isEmptyForm) {
        if (!currentGradeId) { alert('Load students first.'); return; }
        
        const gradeName = currentGradeName || 'Unknown Grade';
        const sectionName = currentSectionId ? $('#os-sr-section option:selected').text() : 'All Sections';
        const yearName = $yearName.val() || '';
        const title = isEmptyForm ? 'Student Uniform Size Registration (Empty Form)' : 'Student Uniform Size Registration';
        
        let html = `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Print - Student Size Registration</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; margin: 0; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
                    .header h1 { margin: 0 0 10px 0; font-size: 24px; }
                    .header p { margin: 0; font-size: 16px; color: #555; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #ccc; padding: 12px 12px; text-align: left; font-size: 14px; }
                    th { background-color: #f9f9f9; font-weight: bold; }
                    .center { text-align: center; }
                    .size-badge { font-weight: bold; }
                    .date-text { font-size: 12px; color: #777; }
                    .summary-box { margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #fdfdfd; font-size: 14px; }
                    .no-border { border: none !important; padding: 0 !important; background: transparent !important; }
                    @media print {
                        body { padding: 0; }
                        button { display: none; }
                        .summary-box { border-color: #000; }
                        thead { display: table-header-group; }
                        tr { page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
        `;

        if (!currentStudents || currentStudents.length === 0) {
            html += `
                <div class="header">
                    <h1>${title}</h1>
                    <p>
                        <strong>Year:</strong> ${esc(yearName)} &nbsp;|&nbsp; 
                        <strong>Grade:</strong> ${esc(gradeName)} &nbsp;|&nbsp; 
                        <strong>Section:</strong> ${esc(sectionName)} &nbsp;|&nbsp; 
                        <strong>Suggested Sizes:</strong> ${currentAllowed.join(', ')}
                    </p>
                </div>
                <p class="center">No students to print.</p>
            `;
        } else {
            let summaryBoxHtml = '';
            
            if (!isEmptyForm) {
                let sized = 0;
                const counts = {};
                currentStudents.forEach(s => {
                    if (s.current_size !== null && s.current_size !== undefined) {
                        sized++;
                        counts[s.current_size] = (counts[s.current_size] || 0) + 1;
                    }
                });

                const sizeStrs = [];
                currentAllowed.forEach(sz => {
                    if (counts[sz]) sizeStrs.push(`Size ${sz}: <strong>${counts[sz]}</strong>`);
                });
                const sizeStrHtml = sizeStrs.length ? sizeStrs.join(' &nbsp;|&nbsp; ') : 'No sizes recorded.';

                summaryBoxHtml = `
                    <div class="summary-box">
                        <strong>Summary:</strong> Total Students: ${currentStudents.length} &nbsp;|&nbsp; Sized: ${sized} &nbsp;|&nbsp; Remaining: ${currentStudents.length - sized}
                        <br><br><strong>Size Distribution:</strong> ${sizeStrHtml}
                    </div>
                `;
            }

            const col4 = 'Uniform Size';
            const col5 = isEmptyForm ? 'Signature / Notes' : 'Measured Date';

            html += `
                <table>
                    <thead>
                        <tr>
                            <td colspan="5" class="no-border">
                                <div class="header">
                                    <h1>${title}</h1>
                                    <p>
                                        <strong>Year:</strong> ${esc(yearName)} &nbsp;|&nbsp; 
                                        <strong>Grade:</strong> ${esc(gradeName)} &nbsp;|&nbsp; 
                                        <strong>Section:</strong> ${esc(sectionName)} &nbsp;|&nbsp; 
                                        <strong>Suggested Sizes:</strong> ${currentAllowed.join(', ')}
                                    </p>
                                </div>
                                ${summaryBoxHtml}
                            </td>
                        </tr>
                        <tr>
                            <th width="5%" class="center">#</th>
                            <th width="15%">Student ID</th>
                            <th width="40%">Student Name</th>
                            <th width="20%" class="center">${col4}</th>
                            <th width="20%" class="center">${col5}</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            const q = $('#os-sr-search').val().toLowerCase();
            const filterState = $('#os-sr-filter').val();
            let visibleIndex = 1;
            
            currentStudents.forEach(s => {
                const isSized = s.current_size !== null && s.current_size !== undefined;
                if (filterState === 'unsized' && isSized) return;
                
                const uid = s.student_uid || '';
                const name = s.student_name || uid;
                
                if (q && !name.toLowerCase().includes(q)) return;

                const section = s.section_name ? ` <span style="color:#777;">(${s.section_name})</span>` : '';
                
                let sizeHtml = '';
                let dateHtml = '';

                if (!isEmptyForm) {
                    sizeHtml = isSized ? `<span class="size-badge">${s.current_size}</span>` : '—';
                    dateHtml = s.measured_at ? `<span class="date-text">${s.measured_at.substring(0, 10)}</span>` : '—';
                }

                html += `
                    <tr>
                        <td class="center">${visibleIndex++}</td>
                        <td>${esc(uid)}</td>
                        <td><strong>${esc(name)}</strong>${section}</td>
                        <td class="center">${sizeHtml}</td>
                        <td class="center">${dateHtml}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;
        }

        html += `
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 250);
                    };
                </script>
            </body>
            </html>
        `;

        const printWin = window.open('', '_blank');
        printWin.document.open();
        printWin.document.write(html);
        printWin.document.close();
    }

    /* ═══════════════════════════════════
       HELPERS
    ═══════════════════════════════════ */
    function showState(state) {
        $('#os-sr-initial, #os-sr-spinner, #os-sr-empty, #os-sr-table-wrap, #os-sr-stats-bar, #os-sr-size-summary').hide();
        if (state === 'initial') $('#os-sr-initial').show();
        if (state === 'spinner') $('#os-sr-spinner').show();
        if (state === 'empty')   { $('#os-sr-empty').show(); $('#os-sr-stats-bar').show(); }
        if (state === 'table')   { $('#os-sr-table-wrap').show(); }
    }

    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

})(jQuery);
