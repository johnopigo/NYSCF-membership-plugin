jQuery(document).ready(function ($) {
    var mediaUploader;

    $('#upload-avatar').click(function (e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Avatar',
            button: {
                text: 'Choose Avatar'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#avatar').val(attachment.url);
            $('#avatar-preview').html('<img src="' + attachment.url + '" width="100" />');
            $('#remove-avatar').show();
        });

        mediaUploader.open();
    });

    $('#remove-avatar').click(function (e) {
        e.preventDefault();
        $('#avatar').val('');
        $('#avatar-preview').html('');
        $(this).hide();
    });
});