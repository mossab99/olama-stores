/**
 * Books Withdrawal Javascript Controller
 * Path: admin/assets/js/os-books-withdrawal.js
 */

(function ($) {
    'use strict';

    // ── Bootstrap ────────────────────────────────────────────────────────────
    const el = document.getElementById('os-books-withdrawal-data');
    if (!el) return;

    const APP     = JSON.parse(el.textContent || '{}');
    const NONCE   = APP.nonce   || '';
    const API     = APP.apiRoot || '';
    const I18N     = APP.i18n    || {};
    const GRADES  = APP.grades  || [];
    const SECTIONS = APP.sections || [];
    const BOOKS   = APP.books   || [];
    let STATE     = {
        familyBooks: [],
        classBooks: [],
        familyStudents: [],
        classStudents: []
    };

    // Set up AJAX header globally for apiFetch if needed, but apiFetch handles it
    
    // ── AJAX / REST API Helper ───────────────────────────────────────────────
    function ajaxRequest(endpointPath, method, data, $btn) {
        if ($btn) { 
            $btn.prop('disabled', true).prepend('<span class="os-books-withdrawal-spinner"></span> '); 
        }

        return wp.apiFetch({
            path: endpointPath,
            method: method,
            headers: { 'X-WP-Nonce': NONCE },
            data: data
        })
        .catch(function (err) {
            alert(err.message || I18N.errorGeneric);
            throw err;
        })
        .finally(function () {
            if ($btn) { 
                $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove(); 
            }
        });
    }

    // ── Tab Switching ────────────────────────────────────────────────────────
    $(document).on('click', '.os-books-withdrawal-tab', function () {
        const target = $(this).data('tab');
        $('.os-books-withdrawal-tab').removeClass('active');
        $('.os-books-withdrawal-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + target).addClass('active');
    });

    // ── Cascaded Dropdowns: Grade & Section ──────────────────────────────────
    function setupGradeSectionCascading($gradeSelect, $sectionSelect, defaultSectionText) {
        $gradeSelect.on('change', function () {
            const gradeId = $(this).val();
            const gradeText = $(this).find('option:selected').text();
            
            $sectionSelect.html('<option value="">' + defaultSectionText + '</option>');
            
            if (!gradeId) {
                $sectionSelect.prop('disabled', true);
                return;
            }

            // Filter sections that belong to the selected grade name
            const filtered = SECTIONS.filter(function (s) {
                return s.grade_name === gradeText;
            });

            if (filtered.length > 0) {
                filtered.forEach(function (s) {
                    $sectionSelect.append('<option value="' + s.id + '">' + s.section_name + '</option>');
                });
                $sectionSelect.prop('disabled', false);
            } else {
                $sectionSelect.prop('disabled', true);
            }
        });
    }

    setupGradeSectionCascading($('#os-class-grade'), $('#os-class-section'), I18N.selectSection);
    setupGradeSectionCascading($('#os-rpt-grade'), $('#os-rpt-section'), 'All Sections');

    // ── Book Search Dropdown Logic ───────────────────────────────────────────
    function setupBookSearch($input, $dropdown, addCallback) {
        $input.on('keyup focus', function () {
            const query = $(this).val().toLowerCase().trim();
            $dropdown.empty().hide();

            if (query.length < 1) {
                // Show first 8 books by default on focus
                renderItems(BOOKS.slice(0, 8));
                return;
            }

            const matches = BOOKS.filter(function (b) {
                return b.name.toLowerCase().includes(query) || 
                       (b.sku && b.sku.toLowerCase().includes(query)) ||
                       (b.barcode && b.barcode.toLowerCase().includes(query));
            });

            renderItems(matches.slice(0, 10));
        });

        function renderItems(itemsList) {
            if (!itemsList.length) {
                $dropdown.append('<div class="os-books-dropdown-item" style="color:#999; cursor:default;">' + I18N.noResults + '</div>').show();
                return;
            }

            itemsList.forEach(function (b) {
                const $item = $('<div class="os-books-dropdown-item" data-id="' + b.id + '">' +
                    '<div><strong>' + b.name + '</strong><br><small>' + (b.sku || '') + '</small></div>' +
                    '<span class="os-badge os-badge-info">' + I18N.available + '</span>' +
                '</div>');

                $item.on('click', function () {
                    addCallback(b);
                    $dropdown.hide();
                    $input.val('');
                });

                $dropdown.append($item);
            });
            $dropdown.show();
        }

        // Hide dropdown on click outside
        $(document).on('click', function (e) {
            if (!$input.is(e.target) && !$dropdown.is(e.target) && $dropdown.has(e.target).length === 0) {
                $dropdown.hide();
            }
        });
    }

    // Add Book to Family List
    setupBookSearch($('#os-family-book-search'), $('#os-family-book-dropdown'), function (book) {
        if (STATE.familyBooks.some(b => b.id === book.id)) return;
        STATE.familyBooks.push({ id: book.id, name: book.name, sku: book.sku, qty: 1 });
        renderSelectedBooksTable('family');
    });

    // Add Book to Class List
    setupBookSearch($('#os-class-book-search'), $('#os-class-book-dropdown'), function (book) {
        if (STATE.classBooks.some(b => b.id === book.id)) return;
        STATE.classBooks.push({ id: book.id, name: book.name, sku: book.sku, qty: 1 });
        renderSelectedBooksTable('class');
    });

    // Render Books Table
    function renderSelectedBooksTable(type) {
        const booksList = (type === 'family') ? STATE.familyBooks : STATE.classBooks;
        const $tbody = $('#os-' + type + '-books-tbody');
        const $btn = $('#os-btn-issue-' + type);

        $tbody.empty();

        if (!booksList.length) {
            $tbody.append('<tr class="os-empty-row"><td colspan="4" style="text-align:center; color:#999;">' + I18N.emptyBooksList + '</td></tr>');
            $btn.prop('disabled', true);
            return;
        }

        booksList.forEach(function (b, index) {
            const $row = $('<tr>' +
                '<td><strong>' + b.name + '</strong></td>' +
                '<td><code>' + (b.sku || '') + '</code></td>' +
                '<td style="text-align:center;"><input type="number" class="os-qty-input" min="1" value="' + b.qty + '" style="width:60px; text-align:center;"></td>' +
                '<td><button type="button" class="button button-link-delete os-btn-remove-book">' + I18N.remove + '</button></td>' +
            '</tr>');

            // Qty change
            $row.find('.os-qty-input').on('change', function () {
                booksList[index].qty = Math.max(1, parseInt($(this).val()) || 1);
            });

            // Remove click
            $row.find('.os-btn-remove-book').on('click', function () {
                booksList.splice(index, 1);
                renderSelectedBooksTable(type);
            });

            $tbody.append($row);
        });

        // Toggle submit buttons based on complete form state
        validateFormState(type);
    }

    function validateFormState(type) {
        const booksList = (type === 'family') ? STATE.familyBooks : STATE.classBooks;
        const studentsList = (type === 'family') ? STATE.familyStudents : STATE.classStudents;
        const $btn = $('#os-btn-issue-' + type);

        if (type === 'family') {
            const hasCheckedStudents = $('.os-family-student-cb:checked').length > 0;
            const hasBooks = booksList.length > 0;
            const warehouseSelected = $('#os-family-warehouse').val() !== '';
            $btn.prop('disabled', !(hasCheckedStudents && hasBooks && warehouseSelected));
        } else {
            const hasStudents = studentsList.length > 0;
            const hasBooks = booksList.length > 0;
            const warehouseSelected = $('#os-class-warehouse').val() !== '';
            const sectionSelected = $('#os-class-section').val() !== '';
            $btn.prop('disabled', !(hasStudents && hasBooks && warehouseSelected && sectionSelected));
        }
    }

    // ── PATHWAY 1: Family-Based Distribution ─────────────────────────────────
    
    // Find Family Click
    $('#os-btn-family-search').on('click', function () {
        const term = $('#os-family-search-input').val().trim();
        if (!term) return;

        const $btn = $(this);
        $btn.prop('disabled', true).prepend('<span class="os-books-withdrawal-spinner"></span> ');
        $('#os-family-empty-state').html('<span class="os-books-withdrawal-spinner"></span> ' + I18N.searching).show();
        $('#os-family-members-wrap').hide();

        wp.apiFetch({ path: '/olama-stores/v1/students?search=' + encodeURIComponent(term) + '&academic_year_id=' + (APP.activeYearId || '') })
        .then(function (students) {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            
            if (!students || !students.length) {
                $('#os-family-empty-state').html('<span class="dashicons dashicons-warning" style="color:var(--os-warning);"></span> ' + I18N.noResults).show();
                $('#os-btn-load-allocated-family').hide();
                STATE.familyStudents = [];
                validateFormState('family');
                return;
            }

            STATE.familyStudents = students;
            $('#os-family-empty-state').hide();
            $('#os-family-members-wrap').show();
            $('#os-btn-load-allocated-family').show();

            const $tbody = $('#os-family-students-tbody');
            $tbody.empty();

            students.forEach(function (s) {
                $tbody.append('<tr>' +
                    '<td><input type="checkbox" class="os-family-student-cb" value="' + s.student_uid + '" data-grade-id="' + (s.grade_id || '') + '" checked></td>' +
                    '<td><strong>' + s.student_name + '</strong><br><code>' + s.student_uid + '</code></td>' +
                    '<td>' + (s.grade_name || '') + '</td>' +
                    '<td>' + (s.section_name || '') + '</td>' +
                '</tr>');
            });

            validateFormState('family');
        })
        .catch(function () {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            $('#os-family-empty-state').html('<span class="dashicons dashicons-warning"></span> ' + I18N.errorGeneric).show();
            $('#os-btn-load-allocated-family').hide();
        });
    });

    // Select all family checkbox
    $(document).on('change', '#os-family-select-all', function () {
        $('.os-family-student-cb').prop('checked', $(this).is(':checked'));
        validateFormState('family');
    });

    $(document).on('change', '.os-family-student-cb', function () {
        validateFormState('family');
    });

    // Load allocated books for checked family students
    $('#os-btn-load-allocated-family').on('click', function () {
        const gradeIds = [];
        $('.os-family-student-cb:checked').each(function () {
            const gId = $(this).attr('data-grade-id');
            if (gId && !gradeIds.includes(gId)) {
                gradeIds.push(gId);
            }
        });

        if (!gradeIds.length) {
            alert('Please select at least one student.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).prepend('<span class="os-books-withdrawal-spinner"></span> ');

        const promises = gradeIds.map(function (gId) {
            return wp.apiFetch({ path: '/olama-stores/v1/books-withdrawal/allocations?grade_id=' + gId });
        });

        Promise.all(promises)
        .then(function (results) {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            let totalAdded = 0;
            results.forEach(function (items) {
                if (items && items.length) {
                    items.forEach(function (book) {
                        if (!STATE.familyBooks.some(b => b.id === book.id)) {
                            STATE.familyBooks.push({ id: book.id, name: book.name, sku: book.sku, qty: 1 });
                            totalAdded++;
                        }
                    });
                }
            });
            if (totalAdded === 0) {
                alert('No books are allocated to the selected students\' grades yet. Please configure allocations in the Book Allocations tab.');
            } else {
                renderSelectedBooksTable('family');
            }
        })
        .catch(function () {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            alert(I18N.errorGeneric);
        });
    });

    // Submit Family Distribution
    $('#os-btn-issue-family').on('click', function () {
        if (!confirm(I18N.confirmBatchIssue)) return;

        const warehouseId = $('#os-family-warehouse').val();
        const notes = $('#os-family-notes').val();
        
        // Collect checked students
        const checkedStudents = [];
        $('.os-family-student-cb:checked').each(function () {
            checkedStudents.push($(this).val());
        });

        if (!checkedStudents.length) {
            alert(I18N.noStudents);
            return;
        }

        // Map items
        const items = STATE.familyBooks.map(function (b) {
            return { item_id: b.id, quantity: b.qty };
        });

        const payload = {
            family_id: $('#os-family-search-input').val().trim(),
            warehouse_id: parseInt(warehouseId),
            notes: notes,
            items: items,
            academic_year_id: APP.activeYearId
        };

        const $btn = $(this);
        ajaxRequest('/olama-stores/v1/books-withdrawal/distribute/family', 'POST', payload, $btn)
        .then(function (res) {
            alert(res.message || I18N.distributionSuccess);
            // Reset
            STATE.familyBooks = [];
            STATE.familyStudents = [];
            $('#os-family-search-input').val('');
            $('#os-family-notes').val('');
            renderSelectedBooksTable('family');
            $('#os-family-members-wrap').hide();
            $('#os-btn-load-allocated-family').hide();
            $('#os-family-empty-state').show();
        });
    });

    // ── PATHWAY 2: Grade-Section Distribution ────────────────────────────────
    
    // Select section triggers student loading
    $('#os-class-section').on('change', function () {
        const sectionId = $(this).val();
        if (!sectionId) {
            $('#os-class-students-wrap').hide();
            $('#os-class-empty-state').show();
            $('#os-btn-load-allocated-class').hide();
            STATE.classStudents = [];
            validateFormState('class');
            return;
        }

        $('#os-class-empty-state').html('<span class="os-books-withdrawal-spinner"></span> ' + I18N.loading).show();
        $('#os-class-students-wrap').hide();

        wp.apiFetch({ path: '/olama-stores/v1/students?section_id=' + sectionId + '&academic_year_id=' + (APP.activeYearId || '') })
        .then(function (students) {
            if (!students || !students.length) {
                $('#os-class-empty-state').html('<span class="dashicons dashicons-warning" style="color:var(--os-warning);"></span> ' + I18N.noResults).show();
                STATE.classStudents = [];
                validateFormState('class');
                return;
            }

            STATE.classStudents = students;
            $('#os-class-empty-state').hide();
            $('#os-class-students-wrap').show();
            $('#os-btn-load-allocated-class').show();
            $('#os-class-students-count').text(students.length);

            const $tbody = $('#os-class-students-tbody');
            $tbody.empty();

            students.forEach(function (s) {
                $tbody.append('<tr>' +
                    '<td><code>' + s.student_uid + '</code></td>' +
                    '<td><strong>' + s.student_name + '</strong></td>' +
                    '<td>' + (s.family_id || '') + '</td>' +
                '</tr>');
            });

            validateFormState('class');
        })
        .catch(function () {
            $('#os-class-empty-state').html('<span class="dashicons dashicons-warning"></span> ' + I18N.errorGeneric).show();
        });
    });

    // Load allocated books UX shortcut
    $('#os-btn-load-allocated-class').on('click', function () {
        const gradeId = $('#os-class-grade').val();
        if (!gradeId) return;

        const $btn = $(this);
        $btn.prop('disabled', true).prepend('<span class="os-books-withdrawal-spinner"></span> ');

        wp.apiFetch({ path: '/olama-stores/v1/books-withdrawal/allocations?grade_id=' + gradeId })
        .then(function (items) {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            
            if (!items || !items.length) {
                alert('No books are allocated to this grade yet. Please configure allocations in the Book Allocations tab.');
                return;
            }

            items.forEach(function (book) {
                if (!STATE.classBooks.some(b => b.id === book.id)) {
                    STATE.classBooks.push({ id: book.id, name: book.name, sku: book.sku, qty: 1 });
                }
            });
            renderSelectedBooksTable('class');
        })
        .catch(function () {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            alert(I18N.errorGeneric);
        });
    });

    // Submit Class Distribution
    $('#os-btn-issue-class').on('click', function () {
        if (!confirm(I18N.confirmBatchIssue)) return;

        const warehouseId = $('#os-class-warehouse').val();
        const sectionId = $('#os-class-section').val();
        const notes = $('#os-class-notes').val();

        const items = STATE.classBooks.map(function (b) {
            return { item_id: b.id, quantity: b.qty };
        });

        const payload = {
            section_id: parseInt(sectionId),
            warehouse_id: parseInt(warehouseId),
            notes: notes,
            items: items,
            academic_year_id: APP.activeYearId
        };

        const $btn = $(this);
        ajaxRequest('/olama-stores/v1/books-withdrawal/distribute/class', 'POST', payload, $btn)
        .then(function (res) {
            alert(res.message || I18N.distributionSuccess);
            // Reset
            STATE.classBooks = [];
            STATE.classStudents = [];
            $('#os-class-grade').val('').trigger('change');
            $('#os-class-notes').val('');
            renderSelectedBooksTable('class');
            $('#os-class-students-wrap').hide();
            $('#os-class-empty-state').show();
        });
    });

    // ── PATHWAY 3: Grade Book Allocations Configuration ──────────────────────
    
    $('#os-alloc-grade-selector').on('change', function () {
        const gradeId = $(this).val();
        if (!gradeId) {
            $('#os-allocations-list-wrap').hide();
            $('#os-allocations-empty-state').show();
            $('#os-allocations-footer').hide();
            return;
        }

        $('#os-allocations-empty-state').html('<span class="os-books-withdrawal-spinner"></span> ' + I18N.loading).show();
        $('#os-allocations-list-wrap').hide();
        $('#os-allocations-footer').hide();

        wp.apiFetch({ path: '/olama-stores/v1/books-withdrawal/allocations?grade_id=' + gradeId })
        .then(function (allocatedItems) {
            $('#os-allocations-empty-state').hide();
            $('#os-allocations-list-wrap').show();
            $('#os-allocations-footer').show();

            const allocatedIds = allocatedItems.map(function (item) { return parseInt(item.id); });

            const $tbody = $('#os-allocations-tbody');
            $tbody.empty();

            if (!BOOKS.length) {
                $tbody.append('<tr><td colspan="4" style="text-align:center; color:#999;">No books cataloged in the system. Create items first!</td></tr>');
                return;
            }

            BOOKS.forEach(function (b) {
                const isChecked = allocatedIds.includes(parseInt(b.id)) ? 'checked' : '';
                $tbody.append('<tr>' +
                    '<td><input type="checkbox" class="os-alloc-book-cb" value="' + b.id + '" ' + isChecked + '></td>' +
                    '<td><strong>' + b.name + '</strong></td>' +
                    '<td><code>' + (b.sku || '') + '</code></td>' +
                    '<td>' + (b.barcode || '') + '</td>' +
                '</tr>');
            });
        })
        .catch(function () {
            $('#os-allocations-empty-state').html('<span class="dashicons dashicons-warning"></span> ' + I18N.errorGeneric).show();
        });
    });

    // Select all allocations checkbox
    $(document).on('change', '#os-allocations-select-all', function () {
        $('.os-alloc-book-cb').prop('checked', $(this).is(':checked'));
    });

    // Save Book Allocations
    $('#os-btn-save-allocations').on('click', function () {
        const gradeId = $('#os-alloc-grade-selector').val();
        if (!gradeId) return;

        const checkedIds = [];
        $('.os-alloc-book-cb:checked').each(function () {
            checkedIds.push(parseInt($(this).val()));
        });

        const payload = {
            grade_id: parseInt(gradeId),
            item_ids: checkedIds
        };

        const $btn = $(this);
        ajaxRequest('/olama-stores/v1/books-withdrawal/allocations', 'POST', payload, $btn)
        .then(function () {
            alert(I18N.savedSuccess);
        });
    });

    // ── PATHWAY 4: Books Reporting System ────────────────────────────────────
    
    // Toggle Report Filters based on Report Selection
    $('#os-rpt-selector').on('change', function () {
        const rpt = $(this).val();
        
        // Hide all filters by default
        $('.os-rpt-filter-item').hide();

        if (rpt === 'store-stock') {
            $('.rpt-filter-search').show();
        } else if (rpt === 'books-received') {
            $('.rpt-filter-date, .rpt-filter-grade, .rpt-filter-section, .rpt-filter-family, .rpt-filter-search').show();
        } else if (rpt === 'missing-books') {
            $('.rpt-filter-grade, .rpt-filter-section, .rpt-filter-family').show();
        } else if (rpt === 'grade-coverage') {
            // Coverage report has no major dynamic filters besides academic year (which is auto-active)
        }
    });

    // Run Report click
    $('#os-btn-load-report').on('click', function () {
        const rpt = $('#os-rpt-selector').val();
        const $wrap = $('#os-report-results-wrap');

        $wrap.html('<div class="os-books-withdrawal-notice"><span class="os-books-withdrawal-spinner"></span> ' + I18N.loading + '</div>');
        $('#os-btn-print-report, #os-btn-export-report').prop('disabled', true);

        // Build query params
        let path = '';
        if (rpt === 'store-stock') {
            path = '/books-withdrawal/reports/stock-report?search=' + encodeURIComponent($('#os-rpt-search').val());
        } else if (rpt === 'books-received') {
            path = '/books-withdrawal/reports/books-received?academic_year_id=' + (APP.activeYearId || '') +
                   '&date_from=' + $('#os-rpt-date-from').val() +
                   '&date_to=' + $('#os-rpt-date-to').val() +
                   '&grade_id=' + $('#os-rpt-grade').val() +
                   '&section_id=' + $('#os-rpt-section').val() +
                   '&family_id=' + encodeURIComponent($('#os-rpt-family').val()) +
                   '&student_name=' + encodeURIComponent($('#os-rpt-search').val());
        } else if (rpt === 'missing-books') {
            path = '/books-withdrawal/reports/missing-books?academic_year_id=' + (APP.activeYearId || '') +
                   '&grade_id=' + $('#os-rpt-grade').val() +
                   '&section_id=' + $('#os-rpt-section').val() +
                   '&family_id=' + encodeURIComponent($('#os-rpt-family').val());
        } else if (rpt === 'grade-coverage') {
            path = '/books-withdrawal/reports/grade-coverage?academic_year_id=' + (APP.activeYearId || '');
        }

        const $btn = $(this);
        $btn.prop('disabled', true).prepend('<span class="os-books-withdrawal-spinner"></span> ');

        wp.apiFetch({ path: '/olama-stores/v1' + path })
        .then(function (rows) {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();

            if (!rows || !rows.length) {
                $wrap.html('<div class="os-books-withdrawal-notice notice-warning"><span class="dashicons dashicons-warning"></span> ' + I18N.noResults + '</div>');
                return;
            }

            renderReportTable(rpt, rows);
            $('#os-btn-print-report, #os-btn-export-report').prop('disabled', false);
        })
        .catch(function () {
            $btn.prop('disabled', false).find('.os-books-withdrawal-spinner').remove();
            $wrap.html('<div class="os-books-withdrawal-notice notice-warning"><span class="dashicons dashicons-warning"></span> ' + I18N.errorGeneric + '</div>');
        });
    });

    // Render Report Tables dynamically in HTML
    function renderReportTable(rpt, rows) {
        const $wrap = $('#os-report-results-wrap');
        $wrap.empty();

        let html = '<div style="overflow-x: auto;"><table class="wp-list-table widefat striped" id="os-rpt-table-data">';

        if (rpt === 'store-stock') {
            html += '<thead><tr>' +
                '<th>' + I18N.bookName + '</th>' +
                '<th>' + I18N.sku + '</th>' +
                '<th>' + I18N.barcode + '</th>' +
                '<th>' + I18N.warehouse + '</th>' +
                '<th style="text-align:center;">' + I18N.onHand + '</th>' +
                '<th style="text-align:center;">' + I18N.reserved + '</th>' +
                '<th style="text-align:center;">' + I18N.available + '</th>' +
            '</tr></thead><tbody>';

            rows.forEach(function (r) {
                const availClass = parseInt(r.quantity_available) <= 0 ? 'color:var(--os-warning); font-weight:bold;' : 'color:var(--os-success); font-weight:bold;';
                html += '<tr>' +
                    '<td><strong>' + r.name + '</strong></td>' +
                    '<td><code>' + (r.sku || '') + '</code></td>' +
                    '<td>' + (r.barcode || '') + '</td>' +
                    '<td>' + (r.warehouse_name || '') + '</td>' +
                    '<td style="text-align:center;">' + r.quantity_on_hand + '</td>' +
                    '<td style="text-align:center;">' + r.quantity_reserved + '</td>' +
                    '<td style="text-align:center; ' + availClass + '">' + r.quantity_available + '</td>' +
                '</tr>';
            });

        } else if (rpt === 'books-received') {
            html += '<thead><tr>' +
                '<th>' + I18N.date + '</th>' +
                '<th>' + I18N.student + '</th>' +
                '<th>' + I18N.grade + ' / ' + I18N.section + '</th>' +
                '<th>' + I18N.family + '</th>' +
                '<th>' + I18N.bookName + '</th>' +
                '<th>' + I18N.sku + '</th>' +
                '<th style="text-align:center;">' + I18N.qty + '</th>' +
                '<th>' + I18N.performedBy + '</th>' +
                '<th>' + I18N.notes + '</th>' +
            '</tr></thead><tbody>';

            rows.forEach(function (r) {
                html += '<tr>' +
                    '<td>' + r.assigned_date + '</td>' +
                    '<td><strong>' + r.student_name + '</strong><br><code>' + r.assignee_id + '</code></td>' +
                    '<td>' + (r.grade_name || '') + ' ' + (r.section_name || '') + '</td>' +
                    '<td><code>' + (r.family_id || '') + '</code></td>' +
                    '<td><strong>' + r.item_name + '</strong></td>' +
                    '<td><code>' + (r.sku || '') + '</code></td>' +
                    '<td style="text-align:center;">' + r.quantity_assigned + '</td>' +
                    '<td>' + (r.warehouse_name || '') + '</td>' +
                    '<td><small>' + (r.notes || '') + '</small></td>' +
                '</tr>';
            });

        } else if (rpt === 'missing-books') {
            html += '<thead><tr>' +
                '<th>' + I18N.student + '</th>' +
                '<th>' + I18N.grade + ' / ' + I18N.section + '</th>' +
                '<th>' + I18N.family + '</th>' +
                '<th>' + I18N.missingBooks + '</th>' +
            '</tr></thead><tbody>';

            rows.forEach(function (r) {
                let booksHtml = '<ul style="margin:0; padding-left:15px; list-style-type:square;">';
                r.missing_books.forEach(function (b) {
                    booksHtml += '<li><strong>' + b.name + '</strong> (<code>' + b.sku + '</code>)</li>';
                });
                booksHtml += '</ul>';

                html += '<tr>' +
                    '<td><strong>' + r.student_name + '</strong><br><code>' + r.student_uid + '</code></td>' +
                    '<td>' + (r.grade_name || '') + ' ' + (r.section_name || '') + '</td>' +
                    '<td><code>' + (r.family_id || '') + '</code></td>' +
                    '<td>' + booksHtml + '</td>' +
                '</tr>';
            });

        } else if (rpt === 'grade-coverage') {
            html += '<thead><tr>' +
                '<th>' + I18N.grade + '</th>' +
                '<th>' + I18N.section + '</th>' +
                '<th style="text-align:center;">' + I18N.students + '</th>' +
                '<th style="text-align:center;">allocated books</th>' +
                '<th style="text-align:center;">' + I18N.totalExpected + '</th>' +
                '<th style="text-align:center;">' + I18N.totalReceived + '</th>' +
                '<th style="width:250px;">' + I18N.coverage + '</th>' +
            '</tr></thead><tbody>';

            rows.forEach(function (r) {
                const barWidth = r.coverage_pct + '%';
                
                // Color levels depending on coverage percentage
                let barColor = 'background:linear-gradient(90deg, #F59E0B 0%, #D97706 100%);'; // default orange
                if (r.coverage_pct >= 85) {
                    barColor = 'background:linear-gradient(90deg, #34D399 0%, #059669 100%);'; // success green
                } else if (r.coverage_pct < 40) {
                    barColor = 'background:linear-gradient(90deg, #F87171 0%, #DC2626 100%);'; // warning red
                }

                const progressBarHtml = '<div class="os-progress-container">' +
                    '<div class="os-progress-bar" style="width:' + barWidth + '; ' + barColor + '"></div>' +
                    '<span class="os-progress-label">' + r.coverage_pct + '%</span>' +
                '</div>';

                html += '<tr>' +
                    '<td><strong>' + r.grade_name + '</strong></td>' +
                    '<td><strong>' + r.section_name + '</strong></td>' +
                    '<td style="text-align:center;">' + r.student_count + '</td>' +
                    '<td style="text-align:center;">' + r.allocated_count + '</td>' +
                    '<td style="text-align:center;">' + r.total_expected + '</td>' +
                    '<td style="text-align:center;">' + r.total_received + '</td>' +
                    '<td>' + progressBarHtml + '</td>' +
                '</tr>';
            });
        }

        html += '</tbody></table></div>';
        $wrap.html(html);
    }

    // Print report action
    $('#os-btn-print-report').on('click', function () {
        window.print();
    });

    // Dynamic CSV Exporter (Client-side, fast and secure!)
    $('#os-btn-export-report').on('click', function () {
        const rptName = $('#os-rpt-selector').val();
        const csv = [];
        const rows = document.querySelectorAll('#os-rpt-table-data tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Clean text for CSV compatibility
                let text = cols[j].textContent.trim();
                text = text.replace(/"/g, '""'); // Escape quotes
                text = text.replace(/\s+/g, ' '); // Clean duplicate whitespaces
                row.push('"' + text + '"');
            }
            csv.push(row.join(','));
        }

        const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'os-books-report-' + rptName + '-' + new Date().toISOString().slice(0, 10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

})(jQuery);
