<?php

require_once( 'options/class-sensei-option-abstract.php' );
require_once( 'options/class-sensei-option-text.php' );
require_once( 'options/class-sensei-option-checkbox.php' );
require_once( 'options/class-sensei-option-wysiwyg.php' );
require_once( 'options/class-sensei-option-select.php' );
require_once( 'options/class-sensei-option-select-posts.php' );
require_once( 'options/class-sensei-option-select-categories.php' );

class Sensei_Options_Renderer {

    private $option_args = array();

    public function __construct( $args ) {
        $this->option_args = $args;
        $this->init_option();
    }


    public function init_option() {

        $option_object = null;

        $dynamic_container_classes = $this->get_field_container_css_classes();

        $class_name = Sensei_Options::get_instance()->get_class_name_by_option_type( $this->option_args['type'] );
        if ( class_exists( $class_name ) ) {
            $option_object = new $class_name( $this->option_args );
        }

        if ( is_object( $option_object ) && null != $option_object ) {
            ?>
            <div class="sensei-option-container <?php echo $dynamic_container_classes; ?>">
                <?php
                    // @codingStandardsIgnoreStart
                    echo $option_object->render();
                    // @codingStandardsIgnoreEnd
                ?>
            </div>
            <?php
        }
    }


    private function get_field_container_css_classes() {
        $dynamic_container_classes = '';

        if (
            isset( $this->option_args['condition'] ) &&
            is_array( $this->option_args['condition'] ) &&
            ! empty( $this->option_args['condition'] ) &&
            isset( $this->option_args['condition']['value'] ) &&
            isset( $this->option_args['condition']['type'] )
        ){
            if ( 'option' == $this->option_args['condition']['type'] ) {
                $dynamic_container_classes .= "dependence-{$this->option_args['condition']['value']}";
            }

            if (
                isset( $this->option_args['condition']['disabled_type'] ) &&
                'hidden' == $this->option_args['condition']['disabled_type']
            ){
                $dynamic_container_classes .= ' sensei-option-hidden-mark';
            }
            else {
                $dynamic_container_classes .= ' sensei-option-disabled-mark';
            }

            if ( ! Sensei_Options::get_instance()->is_option_condition_ok( $this->option_args['id'] ) ) {
                if (
                    isset( $this->option_args['condition']['disabled_type'] ) &&
                    'hidden' == $this->option_args['condition']['disabled_type']
                ){
                    $dynamic_container_classes .= ' sensei-option-hidden';
                }
                else {
                    $dynamic_container_classes .= ' sensei-option-disabled';
                }

                $this->option_args['disabled'] = true;
            }
        }
        return $dynamic_container_classes;
    }
}