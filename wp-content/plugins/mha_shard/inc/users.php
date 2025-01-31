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

/**
 * Create Partner role
 */
function create_partner_role() {
    // Remove the role first if it already exists (optional, for updating capabilities)
    if (get_role('partner')) {
        remove_role('partner');
    }

    // Create the "Partner" role with no capabilities initially
    add_role('partner', 'Partner', []);

    // Get the "Partner" role object
    $partner_role = get_role('partner');

    // Add capabilities for "Partner" role
    $capabilities = [
        'read' => true, // Allow user to read content
        'edit_posts' => true, // Allow user to edit their own posts
        'edit_others_posts' => false,
        'delete_posts' => true, // Allow user to delete their own posts
        'upload_files' => false, // Allow user to upload files

        // Custom post type "cta" capabilities
        'edit_ctas' => true, // Allow editing their own CTA posts
        'delete_ctas' => true, // Allow deleting their own CTA posts
        'read_ctas' => false, // Allow reading CTA posts

        // Custom post type "partner" capabilities
        'edit_partners' => true, // Allow editing their own Partner posts
        'delete_partners' => true, // Allow deleting their own Partner posts
        'read_partners' => false, // Allow reading Partner posts
    ];

    foreach ($capabilities as $cap => $grant) {
        $partner_role->add_cap($cap, $grant);
    }

    // Grant only the capabilities to create and manage their own posts for both post types
    $partner_role->add_cap('read_others_ctas', false);
    $partner_role->add_cap('edit_others_ctas', false);
    $partner_role->add_cap('delete_others_ctas', false);
    $partner_role->add_cap('read_others_partners', false);
    $partner_role->add_cap('edit_others_partners', false);
    $partner_role->add_cap('delete_others_partners', false);
    $partner_role->add_cap('can_partner', true);
}
add_action('init', 'create_partner_role');

// Add support for capabilities in custom post types (if not already configured)
function add_custom_post_type_capabilities() {
    $post_types = ['cta', 'partner'];

    foreach ($post_types as $post_type) {
        $post_type_object = get_post_type_object($post_type);
        if ($post_type_object) {
            $post_type_object->capability_type = $post_type;
            $post_type_object->map_meta_cap = true;
        }
    }
}
add_action('init', 'add_custom_post_type_capabilities');

function hide_other_post_types_for_partner() {
    if (current_user_can('partner')) {
        remove_menu_page('edit.php');
        remove_menu_page('admin.php?page=acf-options-mha-redirects');
        remove_menu_page('admin.php?page=acf-options-mha-global-options');
        $hidden_post_types = ['post', 'page', 'article', 'reading_path', 'thought', 'thought_activity', 'screen', 'diy', 'diy_responses' ];
        foreach ($hidden_post_types as $post_type) {
            $menu_slug = 'edit.php?post_type=' . $post_type;
            remove_menu_page($menu_slug);
        }
    }
}
add_action('admin_menu', 'hide_other_post_types_for_partner', 99);


// Hide ACF option pages from Partners
function hide_acf_admin_for_partner($show_admin) {
    if (current_user_can('can_partner')) {
        return false;
    }
    return $show_admin;
}
add_filter('acf/settings/show_admin', 'hide_acf_admin_for_partner');

function restrict_cta_posts_to_own_for_partner($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'cta') {
        if (current_user_can('partner')) {
            $query->set('author', get_current_user_id());
        }
    }
}
add_action('pre_get_posts', 'restrict_cta_posts_to_own_for_partner');