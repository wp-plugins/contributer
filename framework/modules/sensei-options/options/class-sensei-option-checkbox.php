<?php
/*
 * Declaration example
    array(
        'name' => 'Some kind of label for field',
        'id' => 'option_id',
        'desc'  => 'Full option description',
        'type'  => 'checkbox',
        'value'   => false,
        'condition' => array(
            'type' => 'option',
            'value' => 'option_id_from_which_depends'
        )
    ),
 */

class Sensei_Option_Checkbox extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $value;

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

        if ( isset( $args['value'] ) && ! empty( $args['value'] ) ) {
            $this->value = $args['value'];
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


    public static function get_value_from_post( $option_id ) {
        if ( isset( $_POST[ $option_id ] ) && 'checked' == $_POST[ $option_id ] ) {
            $option_value = true;
        }
        else {
            $option_value = false;
        }
        return $option_value;
    }


    public static function get_default_option( $option_array ) {
        if ( isset( $option_array['value'] ) ) {
            return $option_array['value'];
        }
        else {
            return false;
        }
    }


    /**
     * When some kind of other option have as condition checkbox option, 
     * this function will define rules and logic which will be executed in this case.
     * Its proper way of checking checkbox condition.
     * 
     * @param type $option_id
     * @return bool
     */
    public static function is_option_condition( $option_id ) {
        return Sensei_Options::get_instance()->get_option( $option_id );
    }


    public function render() {
        ?>

        <?php if ( 'disabled' == $this->disabled_type ) { ?>
            <div style="<?php if ( $this->disabled ) { echo 'display:block;'; }  ?>" class="sensei-option-blocker"></div>
        <?php } ?>

        <div class="sensei-field-label field-checkbox-label">
            <?php echo esc_html( $this->name ); ?>
        </div>
        <div class="sensei-field-container field-checkbox-container">
            <input class="sensei-field field-checkbox" type="checkbox" <?php if ( Sensei_Options::get_instance()->get_option( $this->id ) ) { ?> checked <?php } ?> id="<?php echo sanitize_html_class( $this->id ); ?>" name="<?php echo sanitize_key( $this->id ); ?>" value="checked" />
            <p class="sensei-field-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>
        <div class="clearfix"></div>
        <?php 
    }
}