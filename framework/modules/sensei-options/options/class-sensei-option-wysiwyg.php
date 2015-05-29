<?php
/*
 array(
    'name' => 'Option name (label)',
    'id' => 'option_id',
    'desc'  => 'Option description',
    'type'  => 'wysiwyg',
    'value'   => 'test',
    'condition' => array(
        'type' => 'option',
        'value' => 'option_id_as_dependence'
    )
),
 */


class Sensei_Option_Wysiwyg extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $value;

    private $disabled = false;

    private $disabled_type = 'disabled';

    public function __construct( $args ) {

        if (isset($args['name']) && !empty($args['name'])) {
            $this->name = $args['name'];
        }

        if (isset($args['id']) && !empty($args['id'])) {
            $this->id = $args['id'];
        }

        if (isset($args['desc']) && !empty($args['desc'])) {
            $this->desc = $args['desc'];
        }

        if (isset($args['value']) && !empty($args['value'])) {
            $this->value = $args['value'];
        }

        if (isset($args['disabled']) && $args['disabled']) {
            $this->disabled = true;
        }

        if (
                isset($args['condition']) &&
                isset($args['condition']['disabled_type']) &&
                !empty($args['condition']['disabled_type'])
        ) {
            $this->disabled_type = $args['condition']['disabled_type'];
        }
    }


    public static function get_default_option( $option_array ) {
        if (isset($option_array['value'])) {
            return $option_array['value'];
        } else {
            return $option_array['value'];
        }
    }


    // @codingStandardsIgnoreStart
    public static function get_value_from_post( $option_id ) {
        if (isset($_POST[$option_id])) {
            return $_POST[$option_id];
        } else {
            return '';
        }
    }
    // @codingStandardsIgnoreEnd


    public static function is_option_condition( $option_id ) {
        $value = Sensei_Options::get_instance()->get_option( $option_id );
        if (isset($value) && !empty($value) && strlen(trim($value)) > 0) {
            return false;
        } else {
            return false;
        }
    }


    public function render() {
        ?>

        <?php if ( 'disabled' == $this->disabled_type ) { ?>
            <div style="<?php if ( $this->disabled ) { echo 'display:block;'; }  ?>" class="sensei-option-blocker"></div>
        <?php } ?>

        <div class="sensei-field-label field-wysiwyg-label">
            <?php echo esc_html( $this->name ); ?>
        </div>
                
        <div class="sensei-field-container field-wysiwyg-container">
            <?php
            wp_editor (
                Sensei_Options::get_instance()->get_option( $this->id ),
                $this->id,
                array(
                    'media_buttons' => false,
                    'editor_class' => 'sensei-field field-wysiwyg',
                    'textarea_rows' => 7,
                )
            );
            ?>
            <p class="sensei-field-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>
        <div class="clearfix"></div>
        <?php
    }

}

