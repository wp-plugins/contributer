<?php

abstract class Sensei_Option_Abstract {
    //static function -> php strict standard not allowed
    //but we will keep them here commented out, so we can easly see which methods are "required" to define in order for module to work
    //when we are creating new option

    //abstract public static function is_option_condition( $option_id );
    //abstract public static function get_default_option( $option_array );
    //abstract public static function get_value_from_post( $option_id );
    abstract public function render();
}

