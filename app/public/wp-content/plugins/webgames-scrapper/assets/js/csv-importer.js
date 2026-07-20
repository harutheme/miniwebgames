jQuery(document).ready(function($) {
    const $fileInput = $('#wg-csv-file');
    const $startBtn = $('#wg-btn-start-import');
    const $sourceSelect = $('#wg-csv-source');
    const $progressContainer = $('.wg-csv-progress-container');
    const $progressFill = $('#wg-csv-progress-fill');
    const $counter = $('#wg-csv-counter');
    const $logBody = $('#wg-csv-log-body');

    let importQueue = [];
    let currentIndex = 0;
    let totalRows = 0;
    let isImporting = false;

    $startBtn.on('click', function(e) {
        e.preventDefault();

        if (isImporting) return;

        const file = $fileInput[0].files[0];
        if (!file) {
            alert('Please select a CSV file first.');
            return;
        }

        const source = $sourceSelect.val();
        if (!source) {
            alert('Please select a Game Source.');
            return;
        }

        const duplicateStrategy = $('input[name="duplicate_strategy"]:checked').val() || 'skip';

        // Reset UI
        $logBody.empty();
        $progressContainer.show();
        $progressFill.css('width', '0%');
        $counter.text('(0/0)');
        
        isImporting = true;
        $startBtn.prop('disabled', true).text(wgCsvAjax.msg_parsing);

        // Parse CSV
        Papa.parse(file, {
            header: true,
            skipEmptyLines: true,
            complete: function(results) {
                if (results.errors.length && results.data.length === 0) {
                    alert('Error parsing CSV file. Please check the format.');
                    resetUI();
                    return;
                }

                importQueue = results.data;
                totalRows = importQueue.length;
                currentIndex = 0;

                if (totalRows === 0) {
                    alert('CSV file is empty.');
                    resetUI();
                    return;
                }

                $startBtn.text(wgCsvAjax.msg_importing);
                $counter.text(`(0/${totalRows})`);
                
                // Initialize Table
                importQueue.forEach((row, index) => {
                    const stt = row['STT'] || (index + 1);
                    const url = row['URL'] || '';
                    $logBody.append(`
                        <tr id="wg-csv-row-${index}">
                            <td>${stt}</td>
                            <td style="word-break: break-all;">${url}</td>
                            <td class="wg-csv-status">Pending</td>
                            <td class="wg-csv-msg">-</td>
                        </tr>
                    `);
                });

                // Start Queue
                processQueue(source, duplicateStrategy);
            },
            error: function(err) {
                alert('File read error: ' + err.message);
                resetUI();
            }
        });
    });

    function processQueue(source, duplicateStrategy) {
        if (currentIndex >= totalRows) {
            // Finished
            $startBtn.text(wgCsvAjax.msg_complete);
            isImporting = false;
            return;
        }

        const row = importQueue[currentIndex];
        const $rowEl = $(`#wg-csv-row-${currentIndex}`);
        
        $rowEl.find('.wg-csv-status').html('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span> Processing');
        
        // Scroll to the current row in the log table
        const container = $rowEl.closest('.wg-csv-progress-container');
        // A simple scrolling logic if container has a scrollbar
        // container.scrollTop($rowEl.offset().top - container.offset().top + container.scrollTop());

        $.ajax({
            url: wgCsvAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'webgames_csv_import_row',
                security: wgCsvAjax.nonce,
                source: source,
                duplicate_strategy: duplicateStrategy,
                url: row['URL'] || '',
                raw_html: row['HTML'] || '',
                categories: row['Categories'] || '',
                tags: row['Tags'] || '',
                title_override: row['Title'] || ''
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    if (data.status === 'skipped') {
                        $rowEl.find('.wg-csv-status').html('<strong style="color:#e6a23c;">Skipped</strong>');
                    } else {
                        $rowEl.find('.wg-csv-status').html('<strong style="color:#4caf50;">Success</strong>');
                    }
                    $rowEl.find('.wg-csv-msg').text(data.msg || '');
                } else {
                    $rowEl.find('.wg-csv-status').html('<strong style="color:#ff4757;">Error</strong>');
                    $rowEl.find('.wg-csv-msg').text(response.data || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                $rowEl.find('.wg-csv-status').html('<strong style="color:#ff4757;">Failed</strong>');
                $rowEl.find('.wg-csv-msg').text('Server error or timeout');
            },
            complete: function() {
                currentIndex++;
                updateProgress();
                processQueue(source, duplicateStrategy);
            }
        });
    }

    function updateProgress() {
        const percent = Math.round((currentIndex / totalRows) * 100);
        $progressFill.css('width', percent + '%');
        $counter.text(`(${currentIndex}/${totalRows})`);
    }

    function resetUI() {
        isImporting = false;
        $startBtn.prop('disabled', false).text('Start Import');
        $fileInput.val('');
    }
});
