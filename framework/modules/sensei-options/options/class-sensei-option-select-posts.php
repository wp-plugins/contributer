<?php
/*
 * Example: 
 array(
    'name' => 'Select a page',
    'id' => 'option_id_here',
    'type' => 'select_posts',
    'desc' => 'This page will be the page where user will be redirected after he/she clicks on the link which we are going to send to their email.',
    'post_type' => 'post',
    'taxonomy' => 'taxonomy_name',
    'taxonomy_ids' => array(2, 3),
    'condition' => array(
        'type' => 'option',
        'value' => 'option_id_as_dependence'
    )
),
 */

class Sensei_Option_Select_Posts extends Sensei_Option_Abstract {

    private $name;

    private $id;

    private $desc;

    private $value;

    private $options;

    private $post_type = 'post';

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

        if (isset($args['post_type']) && !empty($args['post_type'])) {
            $this->post_type = $args['post_type'];
        }

        // @codingStandardsIgnoreStart
        $args_post = array(
            'posts_per_page' => -1,
            'post_type' => $this->post_type,
        );
        // @codingStandardsIgnoreEnd

        if (isset($args['taxonomy_ids']) && !empty($args['taxonomy_ids'])) {

            if (isset($args['taxonomy']) && !empty($args['taxonomy'])) {
                $taxonomy = $args['taxonomy'];
            } else {
                $taxonomy = 'category';
            }

            $args_post['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'id',
                    'terms' => $args['taxonomy_ids'],
                ),
            );
        }

        $this->options = get_posts($args_post);
        $this->value = Sensei_Options::get_instance()->get_option($this->id);
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

        <div class="sensei-field-label field-select-posts-label">
            <?php echo esc_html( $this->name ); ?>
        </div>

        <div class="sensei-field-container field-select-posts-container">
            <select class="sensei-field field-select-posts" name="<?php echo sanitize_key( $this->id ); ?>">
                <option value="0">Select Post</option>
                <?php foreach ( $this->options as $option ) { ?>
                    <option value="<?php echo $option->ID; //xss ok ?>" <?php if ( $option->ID == $this->value ) { echo 'selected'; } ?>><?php echo esc_html( $option->post_title ); ?></option>
                <?php } ?>
            </select>
            <p class="sensei-field-description field-select-posts-description">
                <?php echo esc_html( $this->desc ); ?>
            </p>
        </div>

        <div class="clearfix"></div>

        <?php 
    }

}