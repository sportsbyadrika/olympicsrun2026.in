/*!
 * Olympics Run 2026 — app.js
 * jQuery is loaded before this file; Bootstrap 5 bundle (with Popper) too.
 * Feature modules will register on $(document).ready in their own files.
 */
(function ($) {
    'use strict';

    // CSRF helper — inject _csrf into every same-origin AJAX POST/PUT/DELETE.
    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            var unsafe = /^(POST|PUT|PATCH|DELETE)$/i.test(settings.type || '');
            if (!unsafe) return;
            // Look for a meta tag (set by base layout later) or window var.
            var token = (window.APP && window.APP.csrf) ||
                $('meta[name="csrf-token"]').attr('content');
            if (token) {
                xhr.setRequestHeader('X-CSRF-Token', token);
            }
        }
    });

    $(function () {
        // Auto-close flash alerts after 5 seconds.
        $('.alert.alert-dismissible').each(function () {
            var $a = $(this);
            setTimeout(function () { $a.alert('close'); }, 5000);
        });
    });
}(jQuery));
