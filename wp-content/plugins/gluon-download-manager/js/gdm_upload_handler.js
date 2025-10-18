jQuery(document).ready(function ($) {
    var selectFileFrame;
    var selectThumbFramel
    // Run media uploader for file upload
    $('#upload_image_button').click(function (e) {
        e.preventDefault();
        selectFileFrame = wp.media({
            title: gdm_translations.select_file,
            button: {
                text: gdm_translations.insert,
            },
            multiple: false
        });

        if(typeof gdm_file_protection !== 'undefined' && gdm_file_protection['gdm_upload_to_protected_dir'] == '1'){
            selectFileFrame.on('open', function() {
                selectFileFrame.uploader.uploader.param('gdm_upload_to_protected_dir', true);
            });
        }
            
        selectFileFrame.open();

        selectFileFrame.on('select', function () {
            var attachment = selectFileFrame.state().get('selection').first().toJSON();

            $('#gdm_upload').val(attachment.url);
        });
        return false;
    });

    // Run media uploader for thumbnail upload
    $('#upload_thumbnail_button').click(function (e) {
        e.preventDefault();
        selectFileFrame = wp.media({
            title: gdm_translations.select_thumbnail,
            button: {
                text: gdm_translations.insert,
            },
            multiple: false,
            library: {type: 'image'},
        });
        selectFileFrame.open();
        selectFileFrame.on('select', function () {
            var attachment = selectFileFrame.state().get('selection').first().toJSON();

            $('#gdm_thumbnail_image').remove();
            $('#gdm_admin_thumb_preview').html('<img id="gdm_thumbnail_image" src="' + attachment.url + '" style="max-width:200px;" />');

            $('#gdm_upload_thumbnail').val(attachment.url);
        });
        return false;
    });

    // Remove thumbnail image from CPT
    $('#remove_thumbnail_button').click( function () {
        if ($('#gdm_thumbnail_image').length === 0) {
            return;
        }
        $.post(
            gdm_admin.ajax_url,
            {
                action: 'gdm_remove_thumbnail_image',
                post_id_del: gdm_admin.post_id,
                _ajax_nonce: $('#gdm_remove_thumbnail_nonce').val()
            },
            function (response) {
                if (response) {  // ** If response was successful
                    $('#gdm_thumbnail_image').remove();
                    $('#gdm_upload_thumbnail').val('');
                    alert(gdm_translations.image_removed);
                } else {  // ** Else response was unsuccessful
                    alert(gdm_translations.ajax_error);
                }
            }
        );
    });
});