jQuery(document).ready(function($) {  
    
    var featured_image_data_raw = {};
    var gallery_image_data_raw = {};
	
        
    //submited profile update
    $( "#profile-form" ).submit(function( event ) {
        $(".message-handler").hide();
        $("#profile-loader").removeClass('hidden_loader');
        $.ajax({
            type: "POST",
            url: contributer_object.ajaxurl,
            data: $( "#profile-form" ).serialize(),
            success: function(data) {                
                if( data.status ) {
                    $("#contributer-success").html( data.message );
                    $("#contributer-success").show();
                }
                else {
                    $("#contributer-failure").html( data.message );
                    $("#contributer-failure").show();
                }
                $("html, body").animate( { scrollTop: 0 }, "slow" );
                $("#profile-loader").addClass('hidden_loader');
            }
        });

        event.preventDefault();
    });
    
    //submit on profile image change
    $("#profile-image-upload").on("change", function() {
        $("#file_form").submit();
    });
    $('#file_form').ajaxForm({
        url: contributer_object.ajaxurl,
        type: 'POST',
        contentType: 'json',
        success: function( data ){
            if ( data.status ) {
                $("#profile-image").attr("src", data.image_url );
                $("#contributer-success").html( data.message );
                $("#contributer-success").show();
            }
            else {
                $("#contributer-failure").html( data.message );
                $("#contributer-failure").show();
            }
            $("html, body").animate( { scrollTop: 0 }, "slow" );
            $("#profile-loader").addClass('hidden_loader');
        },
        beforeSubmit: function( data ) {
            $(".message-handler").hide();
            $("#profile-loader").removeClass('hidden_loader');
        }
    });
    
    
    //submited 
    //TODO: Handle submiting more dynamicly. Maybe to implement js objects per option type?
    $( "#contributer-editor" ).submit( function( event ) {
        $(".message-handler").hide();
        
        var ce_data = new FormData();
           
        var tmce_tab = $( "#contributer-editor" ).find( ".tmce-active" );
        if( tmce_tab.length ) {
            var contributer_wysiwyg_iframe = $(this).find('iframe');
            tmce_tab.find('textarea').val( contributer_wysiwyg_iframe.contents().find("#tinymce").html() );
        }
        
        $('#contributer-editor').find('#action, #title, #add_post_nonce, #cat, #post-content, #vid-url, #tags, #recaptcha_response_field, #recaptcha_challenge_field').each(function(){
            ce_data.append( this.name, $(this).val() );
        });
        
        ce_data.append( 'post-format', $("input[name=post-format]:checked").val() );
        
        $.each($('#featured-image')[0].files, function(i, file) {
            featured_image_data_raw = {};
            featured_image_data_raw["image"] = file;
        });

        $.each($('#gallery-images')[0].files, function(i, file) {
            gallery_image_data_raw["gallery-image-"+i] = file;
        });
        
        ce_data.append('featured-image', featured_image_data_raw["image"]);
        
        for ( var key in gallery_image_data_raw ) {
            ce_data.append(key, gallery_image_data_raw[key]);
        }

        $("#publish-loader").removeClass('hidden_loader');

        $.ajax({
            url: contributer_object.ajaxurl,
            data: ce_data,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            //clearForm: true,
            success: function( data ){
                if( data.status ) {
                    $("#contributer-success").html( data.message );
                    $("#contributer-success").show();
                    post_fields_cleanup();
                    show_featured_image_field();
                }
                else {
                    $("#contributer-failure").html( data.message );
                    $("#contributer-failure").show();
                    
                    //if user is logged out and if using recaptcha we are going to reload it on error
                    if ( contributer_object.logged_off_with_recaptcha ) {
                        Recaptcha.reload();
                    }
                }
                $("html, body").animate( { scrollTop: 0 }, "slow" );
                $("#publish-loader").addClass('hidden_loader');
            }
        });

        event.preventDefault();
    } );

    	
    //featured image drag and drop handlers
    $('#featured-image-upload-area').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
    });
    $("#featured-image-upload-area").bind( "drop", function(e) {
        var files = e.originalEvent.dataTransfer.files;
        
        if( files.length > 0 ) { // checks if any files were dropped
            for(var f = 0; f < files.length; f++) {
                featured_image_data_raw = {};
                featured_image_data_raw["image"] = files[f];
            }
            hide_featured_image_field( featured_image_data_raw );
        }
        
        e.stopPropagation();
        e.preventDefault(); 
    });
    
    
    //gallery images drag and drop handlers
    $('#gallery-images-upload-area').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
    });
    $("#gallery-images-upload-area").bind( "drop", function(e) {
        var files = e.originalEvent.dataTransfer.files;
        gallery_image_data_raw  = {};
        if( files.length > 0 ) { // checks if any files were dropped
            for(var f = 0; f < files.length; f++) {
                gallery_image_data_raw ["gallery-image-"+f] = files[f];
            }
            hide_gallery_image_field( files.length );
        }
        
        e.stopPropagation();
        e.preventDefault(); 
    });
    
    
    /**
     * BINDINGS
     */
    $('#featured-image').on('change', function(){
        $.each($('#featured-image')[0].files, function(i, file) {
            featured_image_data_raw = {};
            featured_image_data_raw["image"] = file;
        });
        hide_featured_image_field( featured_image_data_raw );
    });  
    $('#gallery-images').on('change', function(){
        gallery_image_data_raw = {};
        var number_of_gallery_images = 0;
        $.each($('#gallery-images')[0].files, function(i, file) {
            gallery_image_data_raw["gallery-image-"+i] = file;
            number_of_gallery_images++;
        });
        hide_gallery_image_field( number_of_gallery_images );
    });
    $('#featured-image-upload-different').on('click', function() {
        show_featured_image_field();
    });
    $('#gallery-images-upload-different').on('click', function() {
        show_gallery_image_field();
    });
	
});





jQuery(document).ready( function($) {
    var standard = $('.contributer-editor input[type="radio"]');

    show_hide(['gallery-field','video-field']);
    
    standard.on('change', function(){
        switch($(this).val()) {
            case 'standard':
                show_hide(['gallery-field','video-field']);
                break;
            case 'image':
                show_hide(['gallery-field','video-field']);
                // also: make featured image required
                break;
            case 'video':
                show_hide(['gallery-field']);
                break;
            case 'gallery':
                show_hide(['video-field']);
                break;
            default:
                show_hide(['gallery-field','video-field']);
        }       
    });
    
});






/**
 * HELPER FUNCTIONS BELLOW
 */
function set_tinymce_content() {
    if ( jQuery("#wp-content-wrap").hasClass("tmce-active")) {
        tinyMCE.activeEditor.setContent('');
    } 
    else {
        jQuery('#post-content').val("");
    } 
}


function get_tinymce_content() {
    if ( jQuery("#wp-content-wrap").hasClass("tmce-active")) {
        return tinyMCE.activeEditor.getContent();
    } 
    else {
        return jQuery('#post-content').val();
    }
}


function show_hide( trigger ){
    jQuery('.field').each(function(){
        if( jQuery.inArray( jQuery(this).attr('id'), trigger ) !== -1 ){
            jQuery(this).slideUp();
        }
        else{
            jQuery(this).slideDown();
        }
    });
}


function post_fields_cleanup() {
    jQuery("#title, #post-content, #featured-image, #tags, #vid-url, #gallery-images").val('');
    jQuery("#standard").attr( 'checked', 'checked' );
    jQuery('#cat').val( '-1' );
    set_tinymce_content()
}


function hide_featured_image_field( featured_image_data  ) {
    for ( var key in featured_image_data ) {
        jQuery("#featured-image-uploaded").html(featured_image_data[key].name)
    }
    jQuery("#featured-image-upload-holder").show();
    jQuery("#featured-image-upload-here").hide();
}


function show_featured_image_field() {
    jQuery("#featured-image-uploaded").html("");
    jQuery("#featured-image-upload-holder").hide();
    jQuery("#featured-image-upload-here").show();
}


function hide_gallery_image_field( number_of_images ) {
    jQuery("#gallery-images-uploaded").html( "Images selected: " + number_of_images );
    jQuery("#gallery-images-upload-holder").show();
    jQuery("#gallery-images-upload-here").hide();
}


function show_gallery_image_field() {
    jQuery("#gallery-images-uploaded").html( "" );
    jQuery("#gallery-images-upload-holder").hide();
    jQuery("#gallery-images-upload-here").show();
}
