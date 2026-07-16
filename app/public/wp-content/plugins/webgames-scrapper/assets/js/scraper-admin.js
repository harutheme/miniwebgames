jQuery(document).ready(function($) {
    const $fetchBtn = $('#wg-btn-fetch');
    const $urlInput = $('#wg-scraper-url');
    const $statusMsg = $('#wg-scraper-status');

    if ($fetchBtn.length === 0) return;

    $fetchBtn.on('click', function(e) {
        e.preventDefault();
        const url = $urlInput.val().trim();
        const source = $('#wg-scraper-source').val();
        const raw_html = $('#wg-scraper-raw-html').val() || '';

        if (!url) {
            alert('Please enter a valid URL.');
            return;
        }

        // Client-side domain validation
        if (wgScraperAjax.sources && wgScraperAjax.sources[source]) {
            const sourceData = wgScraperAjax.sources[source];
            if (sourceData.domain && url.toLowerCase().indexOf(sourceData.domain.toLowerCase()) === -1) {
                $statusMsg.removeClass('wg-status-success wg-status-error')
                          .addClass('wg-status-error')
                          .html('<strong>' + wgScraperAjax.domain_err + '</strong>');
                return;
            }
        }

        $statusMsg.removeClass('wg-status-success wg-status-error').html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span>' + wgScraperAjax.fetching);
        $fetchBtn.prop('disabled', true);

        $.ajax({
            url: wgScraperAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'webgames_scrape_url',
                security: wgScraperAjax.nonce,
                url: url,
                source: source,
                raw_html: raw_html
            },
            success: function(response) {
                $fetchBtn.prop('disabled', false);
                
                if (response.success) {
                    const data = response.data;
                    let msg = wgScraperAjax.success;
                    if (data.download_msg) {
                        msg = msg + '<br><span style="color:#e6a23c;">' + data.download_msg + '</span>';
                    }
                    if (data.is_fallback) {
                        msg = msg + '<br><span style="color:#ff4757; font-weight:bold;">' + wgScraperAjax.fallback_msg + '</span>';
                    }
                    $statusMsg.addClass('wg-status-success').html(msg);
                    
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
                    // Set wg_scraped_source_url for tracking
                    if (data.source_url) {
                        $('#wg_scraped_source_url').val(data.source_url);
                    }
                    if (data.original_iframe_url) {
                        $('#wg_original_iframe_url').val(data.original_iframe_url);
                    }
                    if (data.custom_meta) {
                        $('#wg_gamepix_metadata').val(JSON.stringify(data.custom_meta));
                    } else {
                        $('#wg_gamepix_metadata').val('');
                    }

                } else {
                    $statusMsg.addClass('wg-status-error').html('<strong>' + wgScraperAjax.error + '</strong><br>' + response.data);
                }
            },
            error: function() {
                $fetchBtn.prop('disabled', false);
                $statusMsg.addClass('wg-status-error').text(wgScraperAjax.error);
            }
        });
    });
});
