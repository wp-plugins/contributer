<?php

class User_Custom_Fields {
	
    private $user_fields;
	
    public function __construct( $fields ) {
		
        $this->user_fields = $fields;
		
        add_action( 'show_user_profile', array( $this, 'add_user_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_fields' ) );
        
        add_action( 'personal_options_update', array( $this, 'save_user_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_fields' ) );
    }
    
    
    
    public function add_user_fields( $user ) {
		
        foreach ( $this->user_fields as $user_fields_section ) {
            ?>
            <h3><?php echo $user_fields_section['title'] ?></h3>
            <table class="form-table">
                <?php foreach ( $user_fields_section['fields'] as $field ) { ?>
                    <tr>
                        <th><label for="<?php echo $field['id']; ?>"><?php echo $field['label']; ?></label></th>
                        <td>
                            <input type="text" name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value="<?php echo esc_attr( get_the_author_meta( $field['id'], $user->ID ) ); ?>" class="regular-text" /><br />
                            <span class="description"><?php echo $field['desc']; ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <?php
        }
		
    }
    
    
    public function save_user_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
                   
        foreach ( $this->user_fields as $user_fields_section ) {
            foreach ( $user_fields_section['fields'] as $field ) {
                update_user_meta( $user_id, $field['id'], $_POST[ $field['id'] ] );
            }
        }
    }
    
    
}