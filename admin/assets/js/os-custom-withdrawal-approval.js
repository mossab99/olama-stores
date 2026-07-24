(function ($) {
    'use strict';

    const dataElement = document.getElementById('os-cwa-data');
    if (!dataElement) return;

    const APP = JSON.parse(dataElement.textContent || '{}');
    const I18N = APP.i18n || {};
    const API_PATH = APP.apiPath || '';
    const FAMILIES_API_PATH = APP.familiesApiPath || '';
    const YEAR_ID = parseInt(APP.activeYearId || 0, 10);
    let currentFamilyId = '';
    let currentStudents = [];
    let currentReceipt = null;

    if (APP.nonce && wp.apiFetch.createNonceMiddleware) {
        wp.apiFetch.use(wp.apiFetch.createNonceMiddleware(APP.nonce));
    }

    $('.os-cwa-tab').on('click', function () {
        const target = $(this).data('tab');
        $('.os-cwa-tab').removeClass('active');
        $('.os-cwa-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + target).addClass('active');
    });

    function setButtonBusy($button, busy, busyLabel, idleLabel) {
        $button.prop('disabled', busy);
        $button.empty();
        if (busy) {
            $button.append($('<span class="os-cwa-spinner">'));
        }
        $button.append(document.createTextNode(' ' + (busy ? busyLabel : idleLabel)));
    }

    function apiRequest(options, $button, busyLabel, idleLabel) {
        setButtonBusy($button, true, busyLabel, idleLabel);
        return wp.apiFetch(options)
            .catch(function (error) {
                window.alert(error.message || I18N.errorGeneric);
                throw error;
            })
            .finally(function () {
                setButtonBusy($button, false, busyLabel, idleLabel);
            });
    }

    function statusBadge(approved) {
        return $('<span>')
            .addClass('os-cwa-status ' + (approved ? 'os-cwa-status-approved' : 'os-cwa-status-blocked'))
            .text(approved ? I18N.approved : I18N.blocked);
    }

    function renderStudents(response) {
        currentStudents = response.students || [];
        currentFamilyId = response.family.family_uid || response.family.oracle_family_id || '';
        $('#os-cwa-family-matches').prop('hidden', true).empty();

        const familyNumber = response.family.oracle_family_id || currentFamilyId;
        const familyName = response.family.sponsor_full_name || response.family.father_name || '';
        $('#os-cwa-family-title').text(I18N.familyMembers + ': ' + familyNumber + (familyName ? ' — ' + familyName : ''));

        const $body = $('#os-cwa-students').empty();
        currentStudents.forEach(function (student) {
            const approved = student.custom_withdrawal_allowed === true || student.custom_withdrawal_allowed === 1;
            const $checkbox = $('<input type="checkbox" class="os-cwa-student-check">')
                .val(student.student_uid)
                .prop('checked', approved);
            const $name = $('<div>').append(
                $('<strong>').text(student.student_name || student.student_uid),
                $('<small>').text(I18N.studentUid + ' ' + student.student_uid)
            );
            const gradeSection = [student.grade_name || '', student.section_name || ''].filter(Boolean).join(' — ');

            $('<tr>').append(
                $('<td class="os-cwa-check-column">').append($checkbox),
                $('<td>').append($name),
                $('<td>').text(gradeSection),
                $('<td class="os-cwa-row-status">').append(statusBadge(approved))
            ).appendTo($body);
        });

        $('#os-cwa-results').prop('hidden', false);
        updateSelectionState();
    }

    function updateSelectionState() {
        const total = $('.os-cwa-student-check').length;
        const selected = $('.os-cwa-student-check:checked').length;
        $('#os-cwa-check-all')
            .prop('checked', total > 0 && selected === total)
            .prop('indeterminate', selected > 0 && selected < total);
        $('#os-cwa-selected-count').text(
            String(I18N.selected || '').replace('%d', selected).replace('%d', total)
        );

        $('.os-cwa-student-check').each(function () {
            const approved = $(this).is(':checked');
            $(this).closest('tr').find('.os-cwa-row-status').empty().append(statusBadge(approved));
        });
    }

    function familyIdentifier(family) {
        return String(family.oracle_family_id || family.family_uid || '');
    }

    function renderFamilyMatches(families) {
        const $matches = $('#os-cwa-family-matches').empty().prop('hidden', false);

        if (!families.length) {
            $matches.append($('<p class="os-cwa-family-empty">').text(I18N.noFamilies));
            return;
        }

        const $table = $('<table class="os-cwa-family-match-table">');
        const $head = $('<thead>').append(
            $('<tr>').append(
                $('<th>').text(I18N.familyNumber),
                $('<th>').text(I18N.familyName),
                $('<th class="os-cwa-family-match-action">').text(I18N.selectFamily)
            )
        );
        const $body = $('<tbody>');

        families.forEach(function (family) {
            const identifier = familyIdentifier(family);
            if (!identifier) return;

            const familyName = [family.father_name || '', family.mother_name || '']
                .filter(Boolean)
                .join(' / ') || family.sponsor_full_name || '';
            const $button = $('<button type="button" class="button button-small os-cwa-select-family">')
                .attr('data-family-id', identifier)
                .text(I18N.selectFamily);

            $('<tr>').append(
                $('<td>').append($('<strong>').text(identifier)),
                $('<td>').text(familyName),
                $('<td class="os-cwa-family-match-action">').append($button)
            ).appendTo($body);
        });

        $table.append($head, $body);
        $matches.append(
            $('<h3>').text(I18N.matchingFamilies),
            $('<div class="os-cwa-table-wrap">').append($table)
        );
    }

    function loadSelectedFamily(familyId, $button) {
        return apiRequest({
            path: API_PATH + '?family_id=' + encodeURIComponent(familyId) + '&academic_year_id=' + YEAR_ID
        }, $button, I18N.loading, I18N.selectFamily)
            .then(renderStudents)
            .catch(function () {});
    }

    function searchFamilies() {
        const search = $('#os-cwa-family-id').val().trim();
        if (!search) {
            window.alert(I18N.familyRequired);
            return;
        }

        const $button = $('#os-cwa-load-family');
        currentFamilyId = '';
        currentStudents = [];
        $('#os-cwa-results').prop('hidden', true);
        $('#os-cwa-family-matches').prop('hidden', true).empty();

        setButtonBusy($button, true, I18N.loading, I18N.loadFamily);
        wp.apiFetch({
            path: FAMILIES_API_PATH
                + '?search=' + encodeURIComponent(search)
                + '&limit=50&academic_year_id=' + YEAR_ID
        }).then(function (families) {
            const normalizedSearch = search.toLocaleLowerCase();
            const exactMatch = families.find(function (family) {
                return [family.family_uid, family.oracle_family_id]
                    .filter(Boolean)
                    .some(function (identifier) {
                        return String(identifier).toLocaleLowerCase() === normalizedSearch;
                    });
            });

            if (exactMatch || families.length === 1) {
                const selected = exactMatch || families[0];
                return wp.apiFetch({
                    path: API_PATH
                        + '?family_id=' + encodeURIComponent(familyIdentifier(selected))
                        + '&academic_year_id=' + YEAR_ID
                }).then(renderStudents);
            }

            renderFamilyMatches(families);
        }).catch(function (error) {
            window.alert(error.message || I18N.errorGeneric);
        }).finally(function () {
            setButtonBusy($button, false, I18N.loading, I18N.loadFamily);
        });
    }

    $('#os-cwa-load-family').on('click', searchFamilies);
    $('#os-cwa-family-id').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchFamilies();
        }
    });
    $(document).on('click', '.os-cwa-select-family', function () {
        loadSelectedFamily(String($(this).attr('data-family-id') || ''), $(this));
    });

    $('#os-cwa-check-all').on('change', function () {
        $('.os-cwa-student-check').prop('checked', $(this).is(':checked'));
        updateSelectionState();
    });
    $(document).on('change', '.os-cwa-student-check', updateSelectionState);

    $('#os-cwa-save').on('click', function () {
        if (!currentFamilyId || !currentStudents.length) return;

        const approved = $('.os-cwa-student-check:checked').map(function () {
            return $(this).val();
        }).get();
        const $button = $(this);

        apiRequest({
            path: API_PATH,
            method: 'POST',
            data: {
                family_id: currentFamilyId,
                academic_year_id: YEAR_ID,
                approved_student_uids: approved
            }
        }, $button, I18N.saving, I18N.saveApprovals)
            .then(function (response) {
                renderStudents(response);
                window.alert(I18N.saved);
            })
            .catch(function () {});
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function activeCustomWithdrawals(payload) {
        return (payload && payload.withdrawals ? payload.withdrawals : []).filter(function (withdrawal) {
            const remaining = parseInt(withdrawal.quantity_assigned || 0, 10)
                - parseInt(withdrawal.quantity_returned || 0, 10);
            return withdrawal.warehouse_type === 'custom'
                && withdrawal.status === 'active'
                && remaining > 0;
        });
    }

    function uniqueValues(values) {
        return values.filter(function (value, index, allValues) {
            return value && allValues.indexOf(value) === index;
        });
    }

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString();
    }

    function buildReceiptHtml(payload, withdrawals) {
        const family = payload.family || {};
        const familyId = family.oracle_family_id || family.family_uid || '';
        const fatherName = family.father_name || family.sponsor_full_name || '';
        const actionDates = uniqueValues(withdrawals.map(function (withdrawal) {
            return formatDate(withdrawal.assigned_date || withdrawal.created_at);
        }));
        const administrators = uniqueValues(withdrawals.map(function (withdrawal) {
            return withdrawal.issued_by_name || I18N.unknownAdministrator;
        }));
        const rows = withdrawals.map(function (withdrawal) {
            const quantity = parseInt(withdrawal.quantity_assigned || 0, 10)
                - parseInt(withdrawal.quantity_returned || 0, 10);

            return '<tr>'
                + '<td>' + escapeHtml(withdrawal.student_name) + '</td>'
                + '<td>' + escapeHtml(withdrawal.item_name) + (withdrawal.sku ? '<br><small>' + escapeHtml(withdrawal.sku) + '</small>' : '') + '</td>'
                + '<td class="number">' + escapeHtml(quantity) + '</td>'
                + '<td>' + escapeHtml(formatDate(withdrawal.assigned_date || withdrawal.created_at)) + '</td>'
                + '<td>' + escapeHtml(withdrawal.issued_by_name || I18N.unknownAdministrator) + '</td>'
                + '</tr>';
        }).join('');

        return '<!doctype html><html dir="auto"><head><meta charset="utf-8"><title>'
            + escapeHtml(I18N.receiptTitle)
            + '</title><style>'
            + '@page{size:A4;margin:16mm}body{font-family:Arial,sans-serif;color:#1d2327;font-size:13px}'
            + 'h1{text-align:center;font-size:22px;margin:0 0 22px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;margin-bottom:22px}'
            + '.meta div{border-bottom:1px solid #dcdcde;padding:7px 0}.label{font-weight:700}'
            + 'table{width:100%;border-collapse:collapse}th,td{border:1px solid #8c8f94;padding:8px;text-align:start;vertical-align:top}'
            + 'th{background:#f0f0f1}.number{text-align:center}.footer{margin-top:18px;color:#50575e;font-size:11px}'
            + 'small{color:#646970}@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}'
            + '</style></head><body><h1>' + escapeHtml(I18N.receiptTitle) + '</h1>'
            + '<div class="meta">'
            + '<div><span class="label">' + escapeHtml(I18N.familyId) + ':</span> ' + escapeHtml(familyId) + '</div>'
            + '<div><span class="label">' + escapeHtml(I18N.fatherName) + ':</span> ' + escapeHtml(fatherName) + '</div>'
            + '<div><span class="label">' + escapeHtml(I18N.academicYear) + ':</span> ' + escapeHtml(APP.activeYearName || YEAR_ID) + '</div>'
            + '<div><span class="label">' + escapeHtml(I18N.actionDates) + ':</span> ' + escapeHtml(actionDates.join(', ')) + '</div>'
            + '<div><span class="label">' + escapeHtml(I18N.issuedBy) + ':</span> ' + escapeHtml(administrators.join(', ')) + '</div>'
            + '</div><table><thead><tr>'
            + '<th>' + escapeHtml(I18N.student) + '</th>'
            + '<th>' + escapeHtml(I18N.item) + '</th>'
            + '<th>' + escapeHtml(I18N.quantity) + '</th>'
            + '<th>' + escapeHtml(I18N.actionDate) + '</th>'
            + '<th>' + escapeHtml(I18N.issuedBy) + '</th>'
            + '</tr></thead><tbody>' + rows + '</tbody></table>'
            + '<div class="footer">' + escapeHtml(I18N.printedOn) + ': ' + escapeHtml(new Date().toLocaleString()) + '</div>'
            + '</body></html>';
    }

    $(document).on('os:cwa-family-history', function (event, payload) {
        currentReceipt = payload || null;
        $('#os-btn-family-receipt').toggle(activeCustomWithdrawals(currentReceipt).length > 0);
    });

    $('#os-btn-family-receipt').on('click', function () {
        const withdrawals = activeCustomWithdrawals(currentReceipt);
        if (!withdrawals.length) {
            window.alert(I18N.noReceiptItems);
            return;
        }

        const receiptWindow = window.open('', '_blank', 'width=1000,height=800');
        if (!receiptWindow) {
            window.alert(I18N.receiptPopupBlocked);
            return;
        }

        receiptWindow.document.open();
        receiptWindow.document.write(buildReceiptHtml(currentReceipt, withdrawals));
        receiptWindow.document.close();
        receiptWindow.focus();
        window.setTimeout(function () {
            receiptWindow.print();
        }, 250);
    });
})(jQuery);
