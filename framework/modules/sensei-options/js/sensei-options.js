jQuery(document).ready(function($) {
    // turn checkboxes into switches
    $('.field-checkbox').each(function() {
        $(this).wrap('<div class="switch"></div>');
    });
    $('.switch').prepend('<div class="ball"></div>');

    if ($('input[type="checkbox"]').prop('checked')) {
        $('input[type="checkbox"]:checked').parent().addClass('on');
    } else {
        $('input[type="checkbox"]:checked').parent().removeClass('on');
    }

    $('.switch').click(function() {
        $(this).children('input[type="checkbox"]:checked').parent().removeClass('on');
        $(this).children('input[type="checkbox"]').trigger('click');
        if ($(this).children('input[type="checkbox"]').prop('checked')) {
            $(this).children('input[type="checkbox"]:checked').parent().addClass('on');
        } else {
            $(this).children('input[type="checkbox"]:checked').parent().removeClass('on');
        }
    });

    //tab click (switching tabs)
    $( ".sensei-tab" ).click(function() {
        $( ".sensei-tab" ).removeClass( "tab-selected" );
        $( this ).addClass( "tab-selected" );
        $( ".tab-content" ).hide();
        $( "#tab-content-" + $(this).attr( "id" ) ).show();
    });
    
    //reseting options for specific tab
    $( ".sensei-reset-tab" ).click(function() {
        
        if( ! confirm( sensei_js_object.sensei_message_are_you_sure_reset_tab ) ) {
            return;
        }

        var tab_id = $( this ).data('tab');

        $.ajax({
            type: 'post',
            cache: false,
            url: ajaxurl,
            data: {
                action: 'reset_options_tab',
                tab_id: tab_id,
                sensei_options_nonce: $( "#sensei_options_nonce_" + tab_id ).val()
            },
            success: function(data) {
                if( data.status ) {
                    location.reload();
                }
                else {
                    alert( sensei_js_object.sensei_message_something_is_wrong );
                }
            }
        });

    });
    
    //reseting all options
    $( ".sensei-reset-all" ).click(function() {
        if( ! confirm( sensei_js_object.sensei_message_are_you_sure_reset ) ) {
            return;
        }

        $.ajax({
            type: 'post',
            cache: false,
            url: ajaxurl,
            data: {
                action: 'reset_options_all',
                sensei_nonce_resetall: sensei_js_object.sensei_nonce_resetall
            },
            success: function(data) {
                if( data.status ) {
                    location.reload();
                }
                else {
                    alert( sensei_js_object.sensei_message_something_is_wrong );
                }
            }
        });
    });
	
    //when form for saving option is submited
    $( ".sensei-options-form" ).submit(function( event ) {

        //we need to populate manually from visual to textarea (because we are submiting via ajax)
        //ugly hack to be able to use wysiwyg via ajax
        //TODO: are we able to handle this differently?
        $( ".field-wysiwyg-container" ).each(function( index ) {
            var is_tmce_tab = $( this ).find( ".tmce-active" );
            if( is_tmce_tab.length ) {
                var sensei_wysiwyg_iframe = $(this).find('iframe');
                $(this).find('textarea').val(sensei_wysiwyg_iframe.contents().find("#tinymce").html());
            }
        });

        var form_object = $( this );
        form_object.find('.spinner').show();
        form_object.find('.sensei-submit').hide();

        $.ajax({
            type: 'post',
            cache: false,
            url: ajaxurl,
            data: $( this ).serialize(),
            success: function(data) {
                form_object.find('.spinner').hide();
                form_object.find('.sensei-submit').show();
                if(data.status) {
                    if( data.updated_conditions.length !== 0 ) {
                        $.each(data.updated_conditions, function( index, value ) {
                            rstate_dependent_options(value);
                        });
                    }
                }
                else {
                    alert( sensei_js_object.sensei_message_something_is_wrong );
                }
            }
        });

        event.preventDefault();
    });		
	
});

//revert state of fields which depends from specific field
function rstate_dependent_options( field_name ) {
    var sensei_field_container = jQuery( ".dependence-" + field_name );

    jQuery( sensei_field_container ).each(function( index ) {
        if( jQuery( this ).hasClass('sensei-option-disabled-mark') ) {
            disable_enable_options( jQuery( this ) );
        }
        else if( jQuery( this ).hasClass('sensei-option-hidden-mark') ) {
            hide_show_options( jQuery( this ) );
        }
    });
}


function disable_enable_options( sensei_field_container ) {
    var sensei_field_blocker = sensei_field_container.find( ".sensei-option-blocker" );
    if( sensei_field_blocker.length ) {	
        if( sensei_field_container.hasClass( 'sensei-option-disabled' ) ) {
            sensei_field_blocker.hide( "slow", function() {
                sensei_field_container.removeClass( 'sensei-option-disabled' );
            });
        }
        else {
            sensei_field_blocker.show( "slow", function() {
                sensei_field_container.addClass( 'sensei-option-disabled' );
            });
        }
    }
}


function hide_show_options( sensei_field_container ) {
    if( sensei_field_container.hasClass('sensei-option-hidden') ) {
        sensei_field_container.slideDown( "slow", function() {
            sensei_field_container.removeClass('sensei-option-hidden');
        });
    }
    else {
        sensei_field_container.slideUp( "slow", function() {
            sensei_field_container.addClass('sensei-option-hidden');
        });
    }
}
