<?php 
/* Template Name: Dev Screen Entry Updater */
// Use this to assign multiple form entries with a specific user
get_header(); 

if ( current_user_can( 'manage_options' ) ) {

    // Update the following entry IDs with the provided information
    //$entry_ids = [23664673,23664580]; // Demo

    /** Update GF Entry */
    /*
    foreach($entry_ids as $eid){
        $entry = GFAPI::get_entry( $eid );
        $form = GFAPI::get_form($entry['form_id']);
        if(!is_wp_error($entry)){
            foreach($entry as $k => $v){
                $field = GFFormsModel::get_field( $form, $k );  
                if (isset($field->label) && strpos($field->label, 'uid') !== false) { 
                    $entry[$field->id] = 'example@email.com'; // Just need to assign the email to the uid field
                }
            }
            $updated_status = GFAPI::update_entry( $entry );
            pre($updated_status);
        }
    }
    */
}

get_footer();