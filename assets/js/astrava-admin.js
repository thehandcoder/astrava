
jQuery("#astrava-admin-meta").delegate('.astrava-editor-link', 'click', function() {   
        event.stopPropagation();
        var shortcode = '[astrava activity="' + jQuery(this).data('activity-id') + '"]';
        if (tinymce.activeEditor != null) {
            tinymce.activeEditor.execCommand('mceInsertContent', false, shortcode); 
        } else {
            var currentPos = document.getElementById("content").selectionStart;
            var textAreaCotents = jQuery("#content").val();

            jQuery("#content").val(textAreaCotents.substring(0, currentPos) + shortcode + textAreaTxt.substring(currentPos));
        }

    });

 jQuery("#astrava-admin-meta").delegate('.astrava-nav', 'click', function() { 
        jQuery("#astrava-activities").html('Retrieving activities...')
        jQuery.ajax({
            url: ajaxurl,
            data: {
                'action':'get_strava_activities',
                'strava_page' : jQuery(this).data('page')
            },
            success:function(data) {
                jQuery("#astrava-activities").replaceWith(data)
            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });  

    }); 