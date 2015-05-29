<?php

class Sensei_Options {

    private $options_column_name;

    private $raw_options = array();

    //in order to simplify checks and calculation, we are going to create 3 arrays instead of one (multidim)
    private $options = array();

    private $option_properties = array();

    private $option_conditions = array();

    
    
    /**
     * Constructor of this class will populate properties of this class
     * Main property is $options, array which will contain all options values handled within this class
     * 
     * @param array $args
     * @param string $options_column_name
     */
    public function __construct( $args, $options_column_name = '' ) {
        $this->options_column_name = $options_column_name;

        $this->raw_options = $args;

        foreach ( $this->raw_options as $tab ) {
            foreach ( $tab['options'] as $option ) {
                if ( isset( $option['type'] ) && isset( $option['id'] ) ) {
                    $class = $this->get_class_name_by_option_type( $option['type'] );

                    if ( ! class_exists( $class ) ) {
                        continue;
                    }

                    $this->option_properties[ $option['id'] ]['tab_id'] = $tab['id'];
                    $this->option_properties[ $option['id'] ]['option_type'] = $option['type'];
                    $this->options[ $option['id'] ] = $class::get_default_option( $option );

                    if ( isset( $option['condition'] ) && is_array( $option['condition'] ) ) {
                        $this->option_conditions[ $option['id'] ] = $option['condition'];
                    }
                }
            }
        }

        //get options from database
        $db_options = maybe_unserialize( get_option( $this->options_column_name ) );

        //merge default option values with values from database (database ones will overwrite default ones)
        if ( ! empty( $db_options ) ) {
            $this->options = array_merge( $this->options, $db_options );
        }

        //if one option depends from value of other option, prepare old condition to compare
        foreach ( $this->option_conditions as $option_id => $option_condition ) {
            if ( 'option' == $option_condition['type'] ) {
                $this->option_conditions[ $option_id ]['old_condition_option_value'] = $this->get_option( $option_condition['value'] );
            }
        }

        //prepare ajax functions for saving/reseting options (wp_ajax, registered user only)
        add_action( 'wp_ajax_save_options', array( $this, 'ajax_save_options' ) );
        add_action( 'wp_ajax_reset_options_tab', array( $this, 'ajax_reset_options_tab' ) );
        add_action( 'wp_ajax_reset_options_all', array( $this, 'ajax_reset_options_all' ) );
    }


    
    /**
     * Because we will have only one instance of this class which we are going to use through the code
     * as global variable, we will implement static method, get_instance
     * 
     * @global Sensei_Options $sensei_options
     * @param array $args
     * @param string $options_column_name
     * @return \Sensei_Options
     */
    public static function get_instance( $args = array(), $options_column_name = '' ){
        global $sensei_options;

        if ( null == $sensei_options ) {
            $sensei_options = new Sensei_Options( $args, $options_column_name );
        }

        return $sensei_options;
    }


  
    /**
     * 
     * If option contains condition, this method will check condition based on option id
     * 
     * @param string $option_id
     * @return bool
     */
    public function is_option_condition_ok( $option_id ) {

        $condition_status = true;
        $condition = $this->get_condition( $option_id );

        if ( false !== $condition ) {
            if ( 'option' == $condition['type'] ) {
                $class_name = $this->get_class_name_by_option_type( $this->get_option_type( $condition['value'] ) );

                if ( class_exists( $class_name ) ) {
                    $condition_status = $class_name::is_option_condition( $condition['value'] );
                }
            }
            else if ( 'custom' == $condition['type'] ) {
                $function = $condition['value'];

                //it can be an method as wel
                if ( is_array( $function ) ) {
                    $object = $function[0];
                    $method_name = $function[1];

                    if ( is_object( $object ) && is_callable( $function ) ) {
                        $condition_status = $object->$method_name();
                    }
                    else if ( is_string( $object ) && is_callable( $function ) ) {
                        $condition_status = $object::$method_name();
                    }
                }
                else {
                    if ( is_callable( $function ) ) {
                        $condition_status = call_user_func( $function );
                    }
                }
            }
        }

        return $condition_status;
    }

    
    
    /**
     * This method will return value of condition based on option id.
     * If we have value which contains condition, this means that condition will
     * determine are we going to show that option on frontend side or not.
     * 
     * @param string $option_id
     * @return boolean or string (option value)
     */
    public function get_condition( $option_id ) {
        if ( isset( $this->option_conditions[ $option_id ] ) && is_array( $this->option_conditions[ $option_id ] ) ) {
            return $this->option_conditions[ $option_id ];
        }
        else {
            return false;
        }
    }
    
    

    /**
     * This method will return option type based on option id.
     * 
     * @param string $option_id
     * @return string or null (if option type for this option id do not exists)
     */
    public function get_option_type( $option_id ) {
        if ( isset ( $this->option_properties[ $option_id ] ) ) {
            return $this->option_properties[ $option_id ]['option_type'];
        }
        else {
            return null;
        }
    }
    
    
    
    /**
     * This method will return tab id (to whom option bellongs) based on option id
     * 
     * @param string $option_id
     * @return string or null if does not exists
     */
    public function get_option_tab_id( $option_id ) {
        if ( isset ( $this->option_properties[ $option_id ] ) ) {
            return $this->option_properties[ $option_id ]['tab_id'];
        }
        else {
            return null;
        }
    }
    
    
    
    /**
     * This method will return option value based on option id
     * 
     * @param string $option_id
     * @return string
     */
    public function get_option( $option_id ) {

        $option_type = $this->get_option_type( $option_id );
        $option_value = '';

        if ( 'checkbox' == $option_type ) {
            $option_value = (bool) $this->options[ $option_id ];
        }
        else {
            $option_value = $this->options[ $option_id ];
        }

        return $option_value;
    }
    
    
    
    public function set_option( $option_id, $option_value ) {
        $this->options[ $option_id ] = $option_value;
    }
    
    
    
    /**
     * Each option (checkbox, input, textarea etc..) will have own class which will handle
     * actions related with that option. This method constructs class name based on option type.
     * 
     * @param string $option_type
     * @return string $class_name
     */
    public function get_class_name_by_option_type( $option_type ) {
        $class_name = 'Sensei_Option';
        $pieces = explode( '_', $option_type );

        foreach ( $pieces as $piece ) {
            $class_name .= '_' . ucfirst( $piece );
        }

        return $class_name;
    }
    
    
    
    /************************ AJAX METHODS **************************************/
    /****************************************************************************/
    
    
    /**
     * Ajax method for saving options
     */
    public function ajax_save_options() {

        $status = false;
        
        $tab_id = '';
        if ( isset( $_POST['tab'] ) && ! empty( $_POST['tab'] ) ) {
            $tab_id = filter_input( INPUT_POST, 'tab' );
        }

        //these conditions are required in order to proceed with save
        if ( ! empty( $tab_id ) && check_ajax_referer( 'sensei-options-nonce-'.$tab_id, 'sensei_options_nonce_'.$tab_id, false ) ) {
            
            // @codingStandardsIgnoreStart
            unset( $_POST['tab'] );
            unset( $_POST['action'] );
            unset( $_POST['sensei_options_nonce'] );
            // @codingStandardsIgnoreEnd

            //updating options
            foreach ( $this->options as $option_id => $option_value ) {

                if ( $tab_id != $this->get_option_tab_id( $option_id ) ) {
                    continue;
                }

                //check is updated allowed, if not, use old value and skip iteration
                if ( ! $this->is_option_condition_ok( $option_id ) ) {
                    $this->options[ $option_id ] = $option_value;
                    continue;
                }

                //if update is allowed, proceed
                $option_type = $this->get_option_type( $option_id );

                if ( ! empty ( $option_type ) ) {
                    $class = $this->get_class_name_by_option_type( $option_type );

                    if ( class_exists( $class ) ) {
                        $option_value = $class::get_value_from_post( $option_id );
                        $this->options[ $option_id ] = $option_value;
                    }
                }
            }

            update_option( $this->options_column_name, $this->options );
            $status = true;
        }
        else {
            $status = false;
        }

        $updated_conditions = array();
        if ( $status ) {
            //check did any of condition values updated (so we can update frontend)
            //TODO: This should be implemented within separate function
            foreach ( $this->option_conditions as $option_id => $option_condition ) {
                    if ( 'option' == $option_condition['type'] ) {
                            if (
                                    ( $option_condition['old_condition_option_value'] != $this->get_option( $option_condition['value'] ) || (bool) $option_condition['old_condition_option_value'] != (bool) $this->get_option( $option_condition['value'] ) ) &&
                                    ! in_array( $option_condition['value'], $updated_conditions )
                            ) {
                                    $updated_conditions[] = $option_condition['value'];
                            }
                    }
            }
        }
        
        $return = array(
            'status' => $status,
            'updated_conditions' => $updated_conditions,
        );

        wp_send_json( $return );
    }
    
    
    
    /**
     * Ajax method for reseting options specifically for one tab
     */
    public function ajax_reset_options_tab() {
        $status = true;

        $tab_id = '';
        if ( isset( $_POST['tab_id'] ) && ! empty( $_POST['tab_id'] ) ) {
            $tab_id = filter_input( INPUT_POST, 'tab_id' );
        }

        if ( ! empty( $tab_id ) && check_ajax_referer( 'sensei-options-nonce-'.$tab_id, 'sensei_options_nonce', false ) ) {
            foreach ( $this->raw_options as $tab ) {

                if ( $tab_id != $tab['id'] ) {
                    continue;
                }

                foreach ( $tab['options'] as $option ) {
                    if ( isset( $option['type'] ) && isset( $option['id'] ) ) {
                        $class = $this->get_class_name_by_option_type( $option['type'] );

                        if ( ! class_exists( $class ) ) {
                            continue;
                        }

                        $this->options[ $option['id'] ] = $class::get_default_option( $option );
                    }
                }
            }

            update_option( $this->options_column_name, $this->options );
        }
        else {
            $status = false;
        }

        $return = array(
            'status' => $status,
        );

        wp_send_json( $return );
    }
    
    
    
    /**
     * Ajax method. This method will reset all options to default values.
     */
    public function ajax_reset_options_all() {
        $status = true;
        
        if ( check_ajax_referer( 'sensei-nonce-resetall', 'sensei_nonce_resetall', false ) ) {
            foreach ( $this->raw_options as $tab ) {

                foreach ( $tab['options'] as $option ) {
                    if ( isset( $option['type'] ) && isset( $option['id'] ) ) {
                        $class = $this->get_class_name_by_option_type( $option['type'] );

                        if ( ! class_exists( $class ) ) {
                            continue;
                        }

                        $this->options[ $option['id'] ] = $class::get_default_option( $option );
                    }
                }
            }
            
            update_option( $this->options_column_name, $this->options );
        }
        else {
            $status = false;
        }

        $return = array(
            'status' => $status,
            'aaa' => check_ajax_referer( 'sensei-nonce-resetall', 'sensei-nonce-resetall', false )
        );

        wp_send_json( $return );
    }
    
}

