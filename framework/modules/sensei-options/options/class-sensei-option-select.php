<?php
/*
array(
    'name' => 'My Select Option',
    'id' => 'my_select_option',
    'type' => 'select',
    'desc' => 'This is our option',
    'options' => array(
        '1' => 'Option one',
        '2' => 'Option two',
        '3' => 'Option three',
    ),
    'value' => '2',
    'condition' => array(
        'type' => 'option',
        'value' => 'option_id_as_dependence'
    )
),
 */


class Sensei_Option_Select extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $options;

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

        if ( isset( $args['options'] ) && ! empty( $args['options'] ) ) {
            $this->options = $args['options'];
        }

        $this->value = Sensei_Options::get_instance()->get_option( $this->id );

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
        if ( isset( $_POST[ $option_id ] ) ) {
            return filter_input( INPUT_POST, $option_id );
        }
        else {
            return '';
        }
    }


    public static function get_default_option( $option_array ) {
        if ( isset( $option_array['value'] ) ) {
            return $option_array['value'];
        }
        else {
            return '';
        }
    }


    public static function is_option_condition( $option_id ) {

    }


    public function render() {
        ?>

        <?php if ( 'disabled' == $this->disabled_type ) { ?>
            <div style="<?php if ( $this->disabled ) { echo 'display:block;'; }  ?>" class="sensei-option-blocker"></div>
        <?php } ?>

        <div class="sensei-field-label field-select-label">
            <?php echo esc_html( $this->name ); ?>
        </div>

        <div class="sensei-field-container field-select-container">
            <select class="sensei-field field-select" name="<?php echo sanitize_key( $this->id ); ?>">
                <?php foreach ( $this->options as $key => $option ) { ?>
                    <option value="<?php echo $key; //xss ok ?>" <?php if ( $key == $this->value ) { echo 'selected'; } ?>><?php echo esc_html( $option ); ?></option>
                <?php } ?>
            </select>
            <p class="sensei-field-description field-select-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>

        <div class="clearfix"></div>
        <?php 
    }

}

