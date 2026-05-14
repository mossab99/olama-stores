/**
 * Olama Stores — Admin JS
 *
 * This file is loaded in <head> (in_footer=false) so it executes BEFORE any
 * inline <script> blocks inside the PHP view files.
 *
 * Primary job here: guarantee that window.wp.apiFetch exists using a native-
 * Promise + jQuery AJAX polyfill, so view inline scripts never hit
 * "wp is not defined" regardless of whether @wordpress/api-fetch has
 * initialised yet.
 */

/* ── 1. wp.apiFetch polyfill ──────────────────────────────────────────────────
 * olamaStores is always defined at this point because wp_localize_script()
 * outputs the variable immediately before this <script> tag.
 * --------------------------------------------------------------------------- */
window.wp = window.wp || {};

if ( typeof window.wp.apiFetch !== 'function' ) {

    // Use the explicit REST base from PHP, otherwise fallback to guessing.
    var _root = ( window.olamaStores && olamaStores.restBase )
        ? olamaStores.restBase.replace( /\/$/, '' )
        : ( window.location.origin + '/wp-json' );

    /**
     * Minimal wp.apiFetch polyfill.
     * Accepts: { path, url, method, data }
     */
    window.wp.apiFetch = function ( options ) {
        var path = options.path ? options.path.replace( /^\//, '' ) : '';
        var url  = options.url || '';

        if ( path ) {
            // Handle both "pretty" (/wp-json/path) and "plain" (?rest_route=/path) URLs
            var separator = _root.indexOf( '?' ) !== -1 ? '&' : '/';
            if ( separator === '&' ) {
                url = _root + '&rest_route=/' + path;
            } else {
                url = _root + '/' + path;
            }
        }

        var method    = ( options.method || 'GET' ).toUpperCase();
        var isWrite   = ( method !== 'GET' );
        var nonce     = ( window.olamaStores && olamaStores.nonce ) ? olamaStores.nonce : '';

        return new Promise( function ( resolve, reject ) {
            var ajaxOpts = {
                url:      url,
                method:   method,
                dataType: 'json',
                headers:  { 'X-WP-Nonce': nonce },
                success:  resolve,
                error: function ( jqXHR ) {
                    var body = jqXHR.responseJSON || {};
                    reject( {
                        message : body.message  || jqXHR.statusText || 'Request failed',
                        code    : body.code     || jqXHR.status,
                        data    : body.data     || null,
                    } );
                },
            };

            if ( isWrite ) {
                ajaxOpts.contentType = 'application/json; charset=utf-8';
                ajaxOpts.data        = JSON.stringify( options.data || {} );
            }

            jQuery.ajax( ajaxOpts );
        } );
    };
}

/* ── 2. General UI helpers ────────────────────────────────────────────────── */
( function ( $ ) {
    'use strict';

    // Barcode scanner: any <input data-barcode="1"> fires os:barcode-scan on Enter.
    $( document ).on( 'keydown', '[data-barcode]', function ( e ) {
        if ( e.key === 'Enter' ) {
            e.preventDefault();
            $( this ).trigger( 'os:barcode-scan', [ $( this ).val() ] );
        }
    } );

    // Click-outside closes modals.
    $( document ).on( 'click', '.os-modal', function ( e ) {
        if ( e.target === this ) { $( this ).hide(); }
    } );

    // ESC closes modals.
    $( document ).on( 'keyup', function ( e ) {
        if ( e.key === 'Escape' ) { $( '.os-modal:visible' ).hide(); }
    } );

    // Close button inside modals.
    $( document ).on( 'click', '.os-modal-close', function () {
        $( this ).closest( '.os-modal' ).hide();
    } );

    // Print helper.
    window.osPrint = function ( url ) {
        var win = window.open( url, '_blank', 'width=900,height=700' );
        if ( win ) { win.focus(); }
    };

    // Hook into real wp.apiFetch middleware if it later becomes available.
    if ( typeof wp !== 'undefined' && wp.apiFetch && typeof wp.apiFetch.use === 'function' ) {
        wp.apiFetch.use( function ( options, next ) {
            return next( options ).catch( function ( err ) {
                console.error( '[Olama Stores] API Error:', err.message || err );
                return Promise.reject( err );
            } );
        } );
    }

    /* ── 3. Centralized Item Search Utility ─────────────────────────────────── */
    window.osSearchItems = function(query, $targetSelect, callback) {
        var params = '?per_page=50&academic_year_id=' + (window.olamaStores.activeYearId || '');
        if (query) params += '&search=' + encodeURIComponent(query);
        return wp.apiFetch({ path: '/olama-stores/v1/items' + params }).then(function(items) {
            var opts = '<option value=""></option>';
            items.forEach(function(i) {
                opts += '<option value="' + i.id + '">' + i.name + ' (' + i.sku + ')' + '</option>';
            });
            $targetSelect.html(opts);
            $targetSelect.trigger('os:search-results', [items]);
            if (callback) callback(items);
            return items;
        });
    };

    var modalSearchTimer;
    $(document).on('input', '.os-modal-item-search', function(){
        var $input = $(this), q = $input.val(), target = $input.data('target');
        clearTimeout(modalSearchTimer);
        modalSearchTimer = setTimeout(function(){ 
            window.osSearchItems(q, $(target)); 
        }, 400);
    });

    // Sync selected item back to search box
    $(document).on('change', 'select', function(){
        var $select = $(this);
        var $input = $select.closest('.os-input-group').find('.os-modal-item-search');
        if ($input.length) {
            var selectedText = $select.find('option:selected').text();
            if (selectedText && $select.val()) {
                $input.val(selectedText);
            }
        }
    });

    $(document).on('focus', '.os-modal-item-search', function(){
        $(this).select();
    });

} )( jQuery );

