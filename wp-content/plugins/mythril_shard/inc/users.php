<?php

// Give editors the ability to see 'Menus' but hide the other items that appear with "edit_theme_options"
function mythril_allow_editors_menu_access() {
    /*
    if (current_user_can('editor')) {
        $role_object = get_role( 'editor' );
        $role_object->add_cap( 'edit_theme_options' );
    }
    */
}
add_action('admin_head', 'mythril_allow_editors_menu_access');

// Hide Menu Items from Toolbar
add_action( 'admin_bar_menu', 'mythril_override_menu_toolbar_buttons', 999 );
function mythril_override_menu_toolbar_buttons( $wp_admin_bar ) {
    $wp_admin_bar->remove_menu( 'customize' );
}

// Hide Menu Items for Roles
function mythril_hide_admin_pages() {

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
add_action('admin_menu', 'mythril_hide_admin_pages', 999);

?>