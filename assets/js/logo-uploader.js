jQuery(document).ready(function($) {
    let mediaUploader;

    // Upload logo button
    $(document).on('click', '#upload-logo-btn', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: leaveManagerLogo.uploadText,
            button: {
                text: leaveManagerLogo.selectText
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update preview
            $('#logo-preview-img').attr('src', attachment.url);
            $('#logo-url').val(attachment.url);

            // Save to database via AJAX
            $.ajax({
                url: leaveManagerLogo.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'upload_leave_logo',
                    nonce: leaveManagerLogo.nonce,
                    logo_url: attachment.url,
                    logo_id: attachment.id
                },
                success: function(response) {
                    if (response.success) {
                        // Show delete button if not visible
                        if ($('#delete-logo-btn').length === 0) {
                            $('.logo-actions').append(
                                '<button type="button" class="button button-secondary" id="delete-logo-btn">' +
                                'Delete Logo' +
                                '</button>'
                            );
                        }
                        // Show success message
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                }
            });
        });

        mediaUploader.open();
    });

    // Delete logo button
    $(document).on('click', '#delete-logo-btn', function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to delete the logo?')) {
            $.ajax({
                url: leaveManagerLogo.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_leave_logo',
                    nonce: leaveManagerLogo.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#logo-preview-img').html('No logo uploaded');
                        $('#logo-url').val('');
                        $('#delete-logo-btn').remove();
                        showNotification('Logo deleted successfully', 'success');
                    }
                }
            });
        }
    });

    // Show notification
    function showNotification(message, type) {
        const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notification = $('<div class="notice ' + notificationClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        if ($('.leave-manager-logo-field').length) {
            $('.leave-manager-logo-field').before($notification);
        } else {
            $('body').prepend($notification);
        }

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
