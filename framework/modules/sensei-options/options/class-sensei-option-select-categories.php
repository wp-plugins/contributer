<?php
/*
 * Example: 
 array(
    'name' => 'Select Categories',
    'id' => 'select_categories',
    'type' => 'select_categories',
    'desc' => 'This is an option',
    'condition' => array(
            'type' => 'option',
            'value' => 'option_id_as_dependence'
    ),
    'taxonomy' => 'post_tag',
),
 */

class Sensei_Option_Select_Categories extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $value;

    private $options;

    private $post_type = 'post';

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

        if ( isset( $args['condition'] ) && isset( $args['condition']['disabled_type'] ) && ! empty( $args['condition']['disabled_type'] ) ) {
            $this->disabled_type = $args['condition']['disabled_type'];
        }

        if ( isset( $args['taxonomy'] ) && ! empty( $args['taxonomy'] ) ) {
            $taxonomy = $args['taxonomy'];
        }
        else {
            $taxonomy = 'category';
        }

        $this->options = get_terms( $taxonomy, array( 'hide_empty' => false, ) );
        $this->value = Sensei_Options::get_instance()->get_option( $this->id );
    }

    
    // @codingStandardsIgnoreStart
    public static function get_value_from_post( $option_id ) {
        if ( isset( $_POST[ $option_id ] ) ) {
            return $_POST[ $option_id ];
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

        <div class="sensei-field-label field-select-categories-label">
            <?php echo esc_html( $this->name ); ?>
        </div>

        <div class="sensei-field-container field-select-categories-container">
            <select class="sensei-field field-select-categories" name="<?php echo sanitize_key( $this->id ); ?>">
                <option value="0">Select category</option>
                <?php foreach ( $this->options as $option ) { ?>
                    <option value="<?php echo $option->term_id; //xss ok ?>" <?php if ( $option->term_id == $this->value ) { echo 'selected'; } ?>><?php echo esc_html( $option->name ); ?></option>
                <?php } ?>
            </select>
            <p class="sensei-field-description field-select-categories-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>

        <div class="clearfix"></div>

        <?php 
    }

}