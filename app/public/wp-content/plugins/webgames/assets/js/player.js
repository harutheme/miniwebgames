document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Lazy Load Iframe ---
    const playBtn = document.getElementById('webgames-play-btn');
    const cover = document.getElementById('webgames-cover');
    const iframe = document.getElementById('webgames-iframe');

    if (playBtn && iframe) {
        playBtn.addEventListener('click', function() {
            const src = this.getAttribute('data-src');
            iframe.src = src;
            cover.style.opacity = '0';
            setTimeout(() => {
                cover.style.display = 'none';
            }, 300);
        });
    }

    // --- 2. Fullscreen ---
    const fsBtn = document.getElementById('wg-btn-fullscreen');
    const playerWrapper = document.getElementById('webgames-player-wrapper');
    if (fsBtn && playerWrapper) {
        fsBtn.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                playerWrapper.requestFullscreen().catch(err => {
                    alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
                });
            } else {
                document.exitFullscreen();
            }
        });
    }

    // Old Share Logic Removed

    // --- 4. WooCommerce Style Notification System ---
    class WebgamesNotifier {
        static init() {
            if (!document.getElementById('wg-woo-toast-container')) {
                const container = document.createElement('div');
                container.id = 'wg-woo-toast-container';
                document.body.appendChild(container);
            }
        }

        static notify(thumbUrl, status, title) {
            this.init();
            const container = document.getElementById('wg-woo-toast-container');
            const toast = document.createElement('div');
            toast.className = 'wg-woo-toast';
            
            const thumbHtml = thumbUrl ? `<div class="wg-woo-toast-thumb"><img src="${thumbUrl}" alt="Game Thumbnail" /></div>` : '';
            
            toast.innerHTML = `
                ${thumbHtml}
                <div class="wg-woo-toast-content">
                    <div class="wg-woo-toast-status">${status}</div>
                    <div class="wg-woo-toast-title">${title}</div>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Trigger entry animation
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.classList.add('show');
                });
            });
            
            // Remove after 3.5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 400); // Wait for CSS transition
            }, 3500);
        }
    }

    // --- 4.1 Favorites (LocalStorage) ---
    const favBtn = document.querySelector('.wg-btn-fav');
    if (favBtn) {
        const postId = favBtn.getAttribute('data-post-id');
        const originalTooltip = favBtn.getAttribute('data-tooltip') || 'Favorite';
        const unfavTooltip = (typeof webgames_ajax !== 'undefined' && webgames_ajax.i18n && webgames_ajax.i18n.unfav) ? webgames_ajax.i18n.unfav : 'Unfavorite';
        
        let favorites = [];
        try {
            favorites = JSON.parse(localStorage.getItem('wg_favorites')) || [];
        } catch (e) {
            favorites = [];
        }
        
        if (favorites.includes(postId)) {
            favBtn.classList.add('active');
            favBtn.setAttribute('data-tooltip', unfavTooltip);
        }

        favBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            try {
                favorites = JSON.parse(localStorage.getItem('wg_favorites')) || [];
            } catch (err) {
                favorites = [];
            }
            
            const title = favBtn.getAttribute('data-game-title') || '';
            const thumbUrl = favBtn.getAttribute('data-game-thumb') || '';
            
            // Safe i18n fallback
            const msgAdd = (typeof webgames_ajax !== 'undefined' && webgames_ajax.i18n) ? webgames_ajax.i18n.fav_add : 'Added to favorites';
            const msgRemove = (typeof webgames_ajax !== 'undefined' && webgames_ajax.i18n) ? webgames_ajax.i18n.fav_remove : 'Removed from favorites';
            
            if (favorites.includes(postId)) {
                favorites = favorites.filter(id => id !== postId);
                favBtn.classList.remove('active');
                favBtn.setAttribute('data-tooltip', originalTooltip);
                WebgamesNotifier.notify(thumbUrl, msgRemove, title);
            } else {
                favorites.push(postId);
                favBtn.classList.add('active');
                favBtn.setAttribute('data-tooltip', unfavTooltip);
                WebgamesNotifier.notify(thumbUrl, msgAdd, title);
            }
            
            localStorage.setItem('wg_favorites', JSON.stringify(favorites));
        });
    }

    // --- 5. Like / Dislike (AJAX) ---
    const likeBtn = document.querySelector('.wg-btn-like');
    const dislikeBtn = document.querySelector('.wg-btn-dislike');
    
    if (likeBtn) {
        const postId = likeBtn.getAttribute('data-post-id');
        const voteKey = 'wg_voted_' + postId;
        const previousVote = localStorage.getItem(voteKey);
        
        if (previousVote === 'like') {
            likeBtn.classList.add('active');
            likeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.unlike);
        } else if (previousVote === 'dislike' && dislikeBtn) {
            dislikeBtn.classList.add('active');
            dislikeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.remove_dislike);
        }
    }

    function handleVote(btn, type) {
        if (!btn || btn.classList.contains('disabled')) return;
        
        const postId = btn.getAttribute('data-post-id');
        const voteKey = 'wg_voted_' + postId;

        jQuery.ajax({
            url: webgames_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'webgames_like',
                post_id: postId,
                action_type: type,
                security: webgames_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    if (data.action_result === 'unvoted') {
                        localStorage.removeItem(voteKey);
                        likeBtn.classList.remove('active');
                        likeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.like);
                        if (dislikeBtn) {
                            dislikeBtn.classList.remove('active');
                            dislikeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.dislike);
                        }
                    } else {
                        localStorage.setItem(voteKey, data.action_result);
                        if (data.action_result === 'like') {
                            likeBtn.classList.add('active');
                            likeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.unlike);
                            if (dislikeBtn) {
                                dislikeBtn.classList.remove('active');
                                dislikeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.dislike);
                            }
                        } else if (data.action_result === 'dislike') {
                            dislikeBtn.classList.add('active');
                            dislikeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.remove_dislike);
                            if (likeBtn) {
                                likeBtn.classList.remove('active');
                                likeBtn.setAttribute('data-tooltip', webgames_ajax.i18n.like);
                            }
                        }
                    }
                    
                    // Update counts
                    const likeCountSpan = document.querySelector('.wg-btn-like .wg-count-like');
                    if (likeCountSpan && data.total_like !== undefined) {
                        likeCountSpan.innerText = data.total_like;
                    }
                    const dislikeCountSpan = document.querySelector('.wg-btn-dislike .wg-count-dislike');
                    if (dislikeCountSpan && data.total_dislike !== undefined) {
                        dislikeCountSpan.innerText = data.total_dislike;
                    }
                    
                    // Update rating
                    const ratingVal = document.getElementById('wg-game-rating-val');
                    if (ratingVal && data.rating !== undefined) {
                        ratingVal.innerText = data.rating + '%';
                    }
                } else {
                    alert(response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Request failed: ' + error);
            }
        });
    }

    if (likeBtn) {
        likeBtn.addEventListener('click', () => handleVote(likeBtn, 'like'));
    }
    if (dislikeBtn) {
        dislikeBtn.addEventListener('click', () => handleVote(dislikeBtn, 'dislike'));
    }

    // --- 5.5 Share Sidebar ---
    const shareBtn = document.getElementById('wg-btn-share');
    const shareSidebar = document.getElementById('wg-share-sidebar');
    const shareCloseBtn = document.getElementById('wg-share-close');
    
    if (shareBtn && shareSidebar) {
        let shareOverlay = document.querySelector('.wg-share-overlay');
        if (!shareOverlay) {
            shareOverlay = document.createElement('div');
            shareOverlay.className = 'wg-share-overlay';
            document.body.appendChild(shareOverlay);
        }

        const gameTitle = shareBtn.getAttribute('data-game-title');
        const gameUrl = shareBtn.getAttribute('data-game-url');
        // Extract thumb from the fav button or main thumbnail if available
        const favBtnThumb = document.querySelector('.wg-btn-fav');
        const gameThumb = favBtnThumb ? favBtnThumb.getAttribute('data-game-thumb') : '';

        // Native share setup
        const nativeShareBtn = document.getElementById('wg-share-native');
        let isSharing = false;
        
        if (navigator.share && nativeShareBtn) {
            nativeShareBtn.style.display = 'flex';
            nativeShareBtn.addEventListener('click', async () => {
                if (isSharing) return;
                isSharing = true;
                try {
                    await navigator.share({
                        title: gameTitle,
                        url: gameUrl
                    });
                } catch (err) {
                    // AbortError is normal if user cancels the share sheet
                    if (err.name !== 'AbortError') {
                        console.error('Error sharing:', err);
                    }
                } finally {
                    setTimeout(() => { isSharing = false; }, 500);
                }
            });
        }

        const openSidebar = () => {
            shareSidebar.classList.add('active');
            shareOverlay.classList.add('active');
        };

        const closeSidebar = () => {
            shareSidebar.classList.remove('active');
            shareOverlay.classList.remove('active');
        };

        shareBtn.addEventListener('click', openSidebar);
        if (shareCloseBtn) shareCloseBtn.addEventListener('click', closeSidebar);
        shareOverlay.addEventListener('click', closeSidebar);

        // Social Buttons
        const fbBtn = document.getElementById('wg-share-fb');
        if (fbBtn) {
            fbBtn.addEventListener('click', () => {
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(gameUrl)}`, '_blank', 'width=600,height=400');
                closeSidebar();
            });
        }

        const xBtn = document.getElementById('wg-share-x');
        if (xBtn) {
            xBtn.addEventListener('click', () => {
                window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(gameUrl)}&text=${encodeURIComponent(gameTitle)}`, '_blank', 'width=600,height=400');
                closeSidebar();
            });
        }

        function copyTextToClipboard(text, onSuccess) {
            // First try modern clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    navigator.clipboard.writeText(text)
                        .then(onSuccess)
                        .catch(err => {
                            console.warn('Clipboard writeText failed asynchronously:', err);
                            doFallbackCopy(text, onSuccess);
                        });
                } catch (err) {
                    console.warn('Clipboard writeText failed synchronously:', err);
                    doFallbackCopy(text, onSuccess);
                }
            } else {
                doFallbackCopy(text, onSuccess);
            }
        }

        function doFallbackCopy(text, onSuccess) {
            try {
                const tempInput = document.createElement('textarea');
                tempInput.value = text;
                // Keep input in viewport but invisible
                tempInput.style.position = 'fixed';
                tempInput.style.top = '50%';
                tempInput.style.left = '50%';
                tempInput.style.width = '1px';
                tempInput.style.height = '1px';
                tempInput.style.opacity = '0.01';
                document.body.appendChild(tempInput);
                tempInput.focus();
                tempInput.select();
                tempInput.setSelectionRange(0, 99999); // iOS Safari
                
                const success = document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                if (success) {
                    onSuccess();
                } else {
                    window.prompt("Please copy this link manually (Ctrl+C / Cmd+C):", text);
                    onSuccess();
                }
            } catch (e) {
                console.error('execCommand failed', e);
                window.prompt("Please copy this link manually (Ctrl+C / Cmd+C):", text);
                onSuccess();
            }
        }

        const instaBtn = document.getElementById('wg-share-instagram');
        if (instaBtn) {
            instaBtn.addEventListener('click', () => {
                copyTextToClipboard(gameUrl, () => {
                    closeSidebar();
                    WebgamesNotifier.notify(gameThumb, 'Link Copied. Open Instagram!', gameTitle);
                    // Provide a real 'action' by opening the app page
                    setTimeout(() => {
                        window.open('https://www.instagram.com/', '_blank');
                    }, 300);
                });
            });
        }

        const tiktokBtn = document.getElementById('wg-share-tiktok');
        if (tiktokBtn) {
            tiktokBtn.addEventListener('click', () => {
                copyTextToClipboard(gameUrl, () => {
                    closeSidebar();
                    WebgamesNotifier.notify(gameThumb, 'Link Copied. Open TikTok!', gameTitle);
                    // Provide a real 'action' by opening the app page
                    setTimeout(() => {
                        window.open('https://www.tiktok.com/', '_blank');
                    }, 300);
                });
            });
        }

        const copyBtn = document.getElementById('wg-share-copy');
        if (copyBtn) {
            const originalContent = copyBtn.innerHTML;
            copyBtn.addEventListener('click', () => {
                copyTextToClipboard(gameUrl, () => {
                    // 1. Button level visual feedback
                    copyBtn.innerHTML = '<span class="dashicons dashicons-yes-alt" style="color: #4cd137;"></span> <span style="color: #4cd137;">Copied!</span>';
                    
                    // 2. Wait 1 second before closing sidebar to allow user to see the success
                    setTimeout(() => {
                        closeSidebar();
                        // Restore original button state
                        setTimeout(() => { copyBtn.innerHTML = originalContent; }, 300);
                        // 3. Show global notification
                        WebgamesNotifier.notify(gameThumb, 'Link Copied', gameTitle);
                    }, 1000);
                });
            });
        }
    }

    // --- 6. Report Sidebar ---
    const reportBtn = document.getElementById('wg-btn-report');
    const reportSidebar = document.getElementById('wg-report-sidebar');
    const reportCloseBtn = document.getElementById('wg-report-close');
    const reportForm = document.getElementById('wg-report-form');
    
    if (reportBtn && reportSidebar) {
        let shareOverlay = document.querySelector('.wg-share-overlay');
        if (!shareOverlay) {
            shareOverlay = document.createElement('div');
            shareOverlay.className = 'wg-share-overlay';
            document.body.appendChild(shareOverlay);
        }

        const openReportSidebar = () => {
            reportSidebar.classList.add('active');
            shareOverlay.classList.add('active');
        };

        const closeReportSidebar = () => {
            reportSidebar.classList.remove('active');
            // Check if share sidebar is also active, if not remove overlay
            const shareSidebar = document.getElementById('wg-share-sidebar');
            if (!shareSidebar || !shareSidebar.classList.contains('active')) {
                shareOverlay.classList.remove('active');
            }
        };

        reportBtn.addEventListener('click', openReportSidebar);
        
        if (reportCloseBtn) {
            reportCloseBtn.addEventListener('click', closeReportSidebar);
        }

        // Close on overlay click
        shareOverlay.addEventListener('click', closeReportSidebar);

        if (reportForm) {
            const reasonInput = document.getElementById('wg-report-reason');
            const charCount = document.getElementById('wg-report-chars');
            const submitBtn = document.getElementById('wg-btn-submit-report');

            // Front-end Anti-Spam: Character counter
            reasonInput.addEventListener('input', function() {
                const len = this.value.length;
                charCount.textContent = len;
                if (len < 10 || len > 500) {
                    charCount.style.color = '#ff4757';
                } else {
                    charCount.style.color = '#2ed573';
                }
            });

            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Front-end Anti-Spam: Length validation
                const len = reasonInput.value.trim().length;
                if (len < 10) {
                    WebgamesNotifier.notify('', 'Error', 'Reason must be at least 10 characters.');
                    return;
                }
                if (len > 500) {
                    WebgamesNotifier.notify('', 'Error', 'Reason cannot exceed 500 characters.');
                    return;
                }

                // Disable submit button instantly to prevent double-click spam
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Sending...';

                const formData = new FormData(reportForm);
                formData.append('action', 'webgames_report');
                formData.append('security', webgames_ajax.nonce);

                jQuery.ajax({
                    url: webgames_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            WebgamesNotifier.notify('', 'Success', response.data || 'Report submitted.');
                            reportForm.reset();
                            charCount.textContent = '0';
                            charCount.style.color = '';
                            setTimeout(() => {
                                closeReportSidebar();
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }, 1500);
                        } else {
                            WebgamesNotifier.notify('', 'Error', response.data || 'Failed to submit.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    },
                    error: function() {
                        WebgamesNotifier.notify('', 'Error', 'Server error. Please try again later.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            });
        }
    }

    // --- 7. Sidebar Toggle ---
    const toggleBtn = document.getElementById('wg-toggle-sidebar');
    const sidebar = document.getElementById('wg-sidebar-vertical');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('is-mobile-open');
            } else {
                sidebar.classList.toggle('is-collapsed');
            }
        });
    }

    // --- 8. Expandable Content (Show More) ---
    const expandableContents = document.querySelectorAll('.wg-expandable-content');
    expandableContents.forEach(container => {
        const wrapper = container.closest('.wg-content-section');
        if (!wrapper) return;
        
        const btnContainer = wrapper.querySelector('.wg-show-more-container');
        if (!btnContainer) return;
        
        const btn = btnContainer.querySelector('.wg-btn-show-more');
        if (!btn) return;

        // Bind the click event exactly once
        btn.addEventListener('click', function() {
            if (container.classList.contains('is-collapsed')) {
                // EXPAND
                container.classList.remove('is-collapsed');
                btn.innerHTML = 'Show Less <span class="dashicons dashicons-arrow-up-alt2"></span>';
                
                // Set exact target height for perfect CSS transition timing
                container.style.maxHeight = container.scrollHeight + 'px';
                
                // After transition ends, remove inline style to allow responsive resizing
                setTimeout(() => {
                    if (!container.classList.contains('is-collapsed')) {
                        container.style.maxHeight = 'none';
                    }
                }, 500); // matches CSS transition duration
                
            } else {
                // COLLAPSE
                // 1. Lock current exact height
                container.style.maxHeight = container.scrollHeight + 'px';
                // 2. Force browser reflow to register the inline style
                void container.offsetHeight; 
                
                // 3. Apply class and remove inline style to trigger transition to 735px
                container.classList.add('is-collapsed');
                container.style.maxHeight = null;
                
                btn.innerHTML = 'Show More <span class="dashicons dashicons-arrow-down-alt2"></span>';
                
                // 4. Smart scroll: if top of content is above viewport, smoothly scroll up
                const wrapperRect = wrapper.getBoundingClientRect();
                if (wrapperRect.top < 0) {
                    window.scrollTo({
                        top: window.scrollY + wrapperRect.top - 80,
                        behavior: 'smooth'
                    });
                }
            }
        });

        // Function to determine if button should be shown based on height
        const checkHeight = () => {
            // Buffer of 15px so it doesn't show button for slightly taller content
            if (container.scrollHeight > 750) { 
                btnContainer.style.display = 'block';
            } else {
                btnContainer.style.display = 'none';
                container.classList.remove('is-collapsed');
            }
        };

        // Check on DOMContentLoaded
        checkHeight();
        
        // Check again after all resources (images) have loaded
        window.addEventListener('load', checkHeight);
    });
});
