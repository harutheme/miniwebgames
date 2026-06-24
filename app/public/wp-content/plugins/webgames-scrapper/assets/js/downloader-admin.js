jQuery(document).ready(function($) {
    const $btn = $('#wg-btn-download');
    const $urlInput = $('#wg-download-url');
    const $status = $('#wg-download-status');
    const $spinner = $('#wg-download-spinner');

    $btn.on('click', function(e) {
        e.preventDefault();
        const url = $urlInput.val().trim();
        const source = $('#wg-download-source').val();

        if (!url) {
            alert('Please enter a valid iframe URL.');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.css('color', '#007cba').text(wgDownloaderAjax.packing);

        $.ajax({
            url: wgDownloaderAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'webgames_download_source',
                security: wgDownloaderAjax.nonce,
                url: url,
                source: source
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    $status.css('color', '#46b450').text(wgDownloaderAjax.success);
                    // Trigger download
                    window.location.href = response.data.zip_url;
                } else {
                    $status.css('color', '#dc3232').text(wgDownloaderAjax.error + ' ' + response.data);
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $status.css('color', '#dc3232').text(wgDownloaderAjax.error);
            }
        });
    });
});
