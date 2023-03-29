<?php
    // Check if organization already exists
    global $wpdb;
					
    // Setup query parameters
    $meta_key = 'msf_org_application_entry_id';
    $meta_value = $entry['id'];
    $post_type = MSF_ORG_POST_TYPE;
    $post_status = 'publish';
    
    // Query WPDB for post id of organization with matching application entry id
    $org_id = $wpdb->get_var(
        $wpdb->prepare( "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = %s
            AND meta_value = %s
            AND post_id IN (
                SELECT ID
                FROM $wpdb->posts
                WHERE post_type = %s
                AND post_status = %s
            )
            ", 
            array(
                $meta_key, 
                $meta_value, 
                $post_type, 
                $post_status 
            )
        )
    );
?>
