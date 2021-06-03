<?php

// Give editors the ability to see 'Menus' but hide the other items that appear with "edit_theme_options"
function mha_shard__allow_editors_menu_access() {
    if (current_user_can('editor')) {
        $role_object = get_role( 'editor' );
        $role_object->add_cap( 'edit_theme_options' );
    }
}
add_action('admin_head', 'mha_shard__allow_editors_menu_access');


// Disable editor access to gravity forms
function wd_gravity_forms_roles() {
    $role = get_role( 'editor' );
    $role->remove_cap( 'gform_full_access' );
    $role->remove_cap('gravityforms_view_entries');
    $role->remove_cap('gravityforms_edit_entries');
    $role->remove_cap('gravityforms_delete_entries');
    $role->remove_cap('gravityforms_export_entries');
}
add_action( 'admin_init', 'wd_gravity_forms_roles' );



// Hide Menu Items from Toolbar
add_action( 'admin_bar_menu', 'mha_shard__override_menu_toolbar_buttons', 999 );
function mha_shard__override_menu_toolbar_buttons( $wp_admin_bar ) {
    $wp_admin_bar->remove_menu( 'customize' );
}

// Hide Menu Items for Roles
function mha_shard__hide_admin_pages() {

    if (current_user_can('editor')) {
        remove_submenu_page( 'themes.php', 'themes.php' ); // hide the theme selection submenu
        remove_submenu_page( 'themes.php', 'widgets.php' ); // hide the widgets submenu
        
        // hide the customizer submenu
        $customizer_url = add_query_arg( 'return', urlencode( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), 'customize.php' );
        remove_submenu_page( 'themes.php', $customizer_url );

        // Hide WCK
        remove_submenu_page( 'admin.php', 'wck-page' );
        remove_submenu_page( 'admin.php', 'sas-page' );
        remove_submenu_page( 'admin.php', 'cptc-page' );
        remove_submenu_page( 'admin.php', 'ctc-page' );
        remove_submenu_page( 'wck-page', 'sas-page' );
        remove_submenu_page( 'wck-page', 'wck-page' );
        remove_submenu_page( 'wck-page', 'cptc-page' );
        remove_submenu_page( 'wck-page', 'ctc-page' );
        remove_submenu_page( 'wck-page', 'wck-page' );
        remove_menu_page('edit.php?post_type=wck-meta-box');
        remove_menu_page('admin.php?page=wck-page');
        remove_menu_page('wck-page');
    }
}
add_action('admin_menu', 'mha_shard__hide_admin_pages', 999);


/* Hide problematic duplicated taxonomy term boxes */
add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {

    // Hide condition fields entirely on articles to avoid the non-saving ACF field bug
    echo '<style>
        #acf-group_5feded5e680d2 p.description,
        .post-type-article #age_groupdiv,
        .post-type-article #conditiondiv {
            display: none !important;
        } 
    </style>';

}