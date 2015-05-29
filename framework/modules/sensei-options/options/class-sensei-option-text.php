<?php
/**
 * Example:
 * array(
        'name' => 'My Field Label (Option Title)',
        'id' => 'my_field_id',
        'desc'  => 'My field description',
        'type'  => 'text',
        'value'   => 'My default value',
        'condition' => array(
            'type' => 'option',
            'value' => 'option_from_which_this_depends'
        )
    )
 */
class Sensei_Option_Text extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $disabled = false;

    private $disabled_type = 'disabled';

    public function __construct( $args ) {

        if ( isset( $args['name'] ) && ! empty( $args['name'] ) ) {
            $this->name = $args['name'];
        }

        if ( isset( $args['id'] ) && ! empty( $args['id'] ) ) {
            $this->id = $args['id'];
        }

        if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
            $this->desc = $args['desc'];
        }

        if ( isset( $args['disabled'] ) && $args['disabled'] ) {
            $this->disabled = true;
        }

        if (
            isset( $args['condition'] ) &&
            isset( $args['condition']['disabled_type'] ) &&
            ! empty( $args['condition']['disabled_type'] )
        ){
            $this->disabled_type = $args['condition']['disabled_type'];
        }

    }


    // @codingStandardsIgnoreStart
    public static function get_value_from_post( $option_id ) {
        if ( isset( $_POST[ $option_id ] ) ) {
            return $_POST[ $option_id ];
        }
        else {
            return '';
        }
    }
    // @codingStandardsIgnoreEnd


    public static function get_default_option( $option_array ) {
        if ( isset( $option_array['value'] ) ) {
            return $option_array['value'];
        }
        else {
            return '';
        }
    }


    public static function is_option_condition( $option_id ) {
        $value = Sensei_Options::get_instance()->get_option( $option_id );
        if ( isset( $value ) && ! empty( $value ) && strlen( trim( $value ) ) > 0 ) {
            return false;
        }
        else {
            return false;
        }
    }


    public function render() {
        ?>

        <?php if ( 'disabled' == $this->disabled_type ) { ?>
            <div style="<?php if ( $this->disabled ) { echo 'display:block;'; }  ?>" class="sensei-option-blocker"></div>
        <?php } ?>

        <div class="sensei-field-label field-text-label">
            <?php echo esc_html( $this->name ); ?>
        </div>

        <div class="sensei-field-container field-text-container">
            <input class="sensei-field field-text" type="text" id="<?php echo sanitize_html_class( $this->id ); ?>" name="<?php echo sanitize_key( $this->id ); ?>" value="<?php echo Sensei_Options::get_instance()->get_option( $this->id ); //xss ok ?>" />
            <p class="sensei-field-description field-text-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>

        <div class="clearfix"></div>
        <?php 
    }

}

