jQuery(document).ready(function($) {
    const $fetchBtn = $('#wg-btn-fetch');
    const $urlInput = $('#wg-scraper-url');
    const $statusMsg = $('#wg-scraper-status');

    if ($fetchBtn.length === 0) return;

    $fetchBtn.on('click', function(e) {
        e.preventDefault();
        const url = $urlInput.val().trim();
        const source = $('#wg-scraper-source').val();

        if (!url) {
            alert('Please enter a valid URL.');
            return;
        }

        $statusMsg.removeClass('wg-status-success wg-status-error').text(wgScraperAjax.fetching);
        $fetchBtn.prop('disabled', true);

        $.ajax({
            url: wgScraperAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'webgames_scrape_url',
                security: wgScraperAjax.nonce,
                url: url,
                source: source
            },
            success: function(response) {
                $fetchBtn.prop('disabled', false);
                
                if (response.success) {
                    $statusMsg.addClass('wg-status-success').text(wgScraperAjax.success);
                    
                    const data = response.data;
                    
                    // Fill WP Title
                    if (data.title) {
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                            // Gutenberg
                            wp.data.dispatch('core/editor').editPost({ title: data.title });
                        } else if ($('#title').length) {
                            // Classic
                            $('#title').val(data.title).focus().blur();
                        }
                    }
                    
                    // Fill Description (Gutenberg or Classic)
                    if (data.description) {
                        // Try classic editor first
                        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                            tinymce.get('content').setContent(data.description);
                        } else if ($('#content').length) {
                            $('#content').val(data.description);
                        }
                        
                        // For Gutenberg, we can dispatch to the data store
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                            wp.data.dispatch('core/editor').editPost({ content: data.description });
                        }
                    }

                    // Auto-select "External Iframe" radio button
                    const $sourceTypeRadios = $('.acf-field[data-name="source_type"] input[type="radio"]');
                    if ($sourceTypeRadios.length) {
                        $sourceTypeRadios.filter('[value="iframe"]').prop('checked', true).trigger('change');
                    }

                    // Fill ACF Iframe URL
                    const $iframeUrlField = $('.acf-field[data-name="iframe_url"] input[type="url"], .acf-field[data-name="iframe_url"] input[type="text"]');
                    if ($iframeUrlField.length && data.iframe_url) {
                        $iframeUrlField.val(data.iframe_url).trigger('change');
                    }

                    // Fill ACF Game Cover
                    // If it's an image ID field
                    const $coverField = $('.acf-field[data-name="game_cover"] input[type="hidden"]');
                    if ($coverField.length && data.image_id) {
                        $coverField.val(data.image_id).trigger('change');
                        // visually update the ACF image uploader if possible
                        const $uploader = $coverField.closest('.acf-image-uploader');
                        if ($uploader.length) {
                            $uploader.addClass('has-value');
                            $uploader.find('img[data-name="image"]').attr('src', data.image_url);
                        }
                    }

                    // Set WP Default Featured Image (Gutenberg)
                    if (data.image_id) {
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                            wp.data.dispatch('core/editor').editPost({ featured_media: parseInt(data.image_id, 10) });
                        } else if ($('#_thumbnail_id').length) {
                            $('#_thumbnail_id').val(data.image_id);
                            // We can also trigger a visual update if needed, but usually save is enough
                        }
                    }

                } else {
                    $statusMsg.addClass('wg-status-error').text(wgScraperAjax.error + ' ' + response.data);
                }
            },
            error: function() {
                $fetchBtn.prop('disabled', false);
                $statusMsg.addClass('wg-status-error').text(wgScraperAjax.error);
            }
        });
    });
});
