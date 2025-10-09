<?php

/**
 * Get current language code with TranslatePress support and URL override
 */
function mha_get_current_language_code() {

    // Check for URL parameter override first (e.g., from Google Translate)
    if ( isset( $_GET['_x_tr_tl'] ) && !empty( $_GET['_x_tr_tl'] ) ) {
        $url_language = sanitize_text_field( $_GET['_x_tr_tl'] );
        // Convert to language code format if needed (e.g., 'en_US' -> 'en')
        return substr( $url_language, 0, 2 );
    }
    
    // Check if TranslatePress is active and get current language
    if ( function_exists( 'trp_get_current_language' ) ) {
        $trp_language = trp_get_current_language();
        if ( !empty( $trp_language ) ) {
            return $trp_language;
        }
    }
    
    // Alternative TranslatePress method
    if ( function_exists( 'get_locale' ) ) {
        $current_locale = get_locale();
        if ( !empty( $current_locale ) ) {
            // Convert to language code format (e.g., 'en_US' -> 'en')
            return substr( $current_locale, 0, 2 );
        }
    }
    
    // Default fallback
    return 'en-default';
    
}

/**
 * Pre-render handler to populate language field in forms using TranslatePress
 */
add_filter( 'gform_pre_render', 'mha_populate_language_field' );
function mha_populate_language_field( $form ) {
        
    // Check for a "language" hidden field
    foreach( $form['fields'] as $field ) {
        if( 
            $field->type == 'hidden' && 
            ( strtolower( $field->label ) == 'language' || 
            strtolower( $field->adminLabel ) == 'language' ||
            $field->inputName == 'language' ) ) {
                $field->defaultValue = mha_get_current_language_code();
            break;
        }
    }
    
    return $form;
}

/**
 * Add TranslatePress languages column to Pages admin table
 */
add_filter( 'manage_pages_columns', 'mha_add_translatepress_languages_column' );
function mha_add_translatepress_languages_column( $columns ) {
    // Insert the new column before the 'date' column
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        if ( $key === 'date' ) {
            $new_columns['translatepress_languages'] = 'Available Languages';
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}

/**
 * Populate the TranslatePress languages column content
 */
add_action( 'manage_pages_custom_column', 'mha_populate_translatepress_languages_column', 10, 2 );
function mha_populate_translatepress_languages_column( $column, $post_id ) {
    if ( $column === 'translatepress_languages' ) {
        $available_languages = mha_get_page_available_languages( $post_id );
        if ( !empty( $available_languages ) ) {
            echo '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
            foreach ( $available_languages as $lang_code => $lang_name ) {
                $flag_emoji = mha_get_language_flag_emoji( $lang_code );
                echo '<span style="display: inline-flex; align-items: center; background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin: 1px;">';
                echo $flag_emoji . ' ' . strtoupper( $lang_code );
                echo '</span>';
            }
            echo '</div>';
        } else {
            echo '<span style="color: #999; font-style: italic;">No translations</span>';
        }
    }
}

/**
 * Get available languages for a specific page
 */
function mha_get_page_available_languages( $post_id ) {
    $available_languages = array();
    
    // Check if TranslatePress is active
    if ( !function_exists( 'trp_get_languages' ) ) {
        return $available_languages;
    }
    
    // Get all available languages from TranslatePress
    $trp_languages = trp_get_languages();
    if ( empty( $trp_languages ) ) {
        return $available_languages;
    }
    
    // Get the original post
    $original_post = get_post( $post_id );
    if ( !$original_post ) {
        return $available_languages;
    }
    
    // Get the default language from TranslatePress settings
    $default_language = '';
    if ( function_exists( 'trp_get_default_language' ) ) {
        $default_language = trp_get_default_language();
    } else {
        // Fallback: get default language from options
        $trp_settings = get_option( 'trp_settings', array() );
        $default_language = isset( $trp_settings['default-language'] ) ? $trp_settings['default-language'] : '';
    }
    
    // Check each language for translations by looking at the database
    global $wpdb;
    
    foreach ( $trp_languages as $lang_code => $lang_data ) {
        // Skip the default language (usually the original)
        if ( $lang_code === $default_language ) {
            continue;
        }
        
        // Check if there are any translations for this post in this language
        // TranslatePress stores translations in trp_dictionary_[language] tables
        $table_name = $wpdb->prefix . 'trp_dictionary_' . $lang_code;
        
        // Check if the table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            "SHOW TABLES LIKE %s", 
            $table_name 
        ) );
        
        if ( $table_exists ) {
            // Check if there are any translations for this post's content or title
            $has_translations = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE original IN (%s, %s) AND translated != '' AND translated IS NOT NULL",
                $original_post->post_title,
                $original_post->post_content
            ) );
            
            if ( $has_translations > 0 ) {
                $available_languages[$lang_code] = $lang_data['english_name'] ?? $lang_code;
            }
        }
    }
    
    return $available_languages;
}

/**
 * Get flag emoji for language code
 */
function mha_get_language_flag_emoji( $lang_code ) {
    $flag_map = array(
        'en' => '🇺🇸',
        'es' => '🇪🇸',
        'fr' => '🇫🇷',
        'de' => '🇩🇪',
        'it' => '🇮🇹',
        'pt' => '🇵🇹',
        'ru' => '🇷🇺',
        'ja' => '🇯🇵',
        'ko' => '🇰🇷',
        'zh' => '🇨🇳',
        'ar' => '🇸🇦',
        'hi' => '🇮🇳',
        'th' => '🇹🇭',
        'vi' => '🇻🇳',
        'pl' => '🇵🇱',
        'nl' => '🇳🇱',
        'sv' => '🇸🇪',
        'da' => '🇩🇰',
        'no' => '🇳🇴',
        'fi' => '🇫🇮',
        'tr' => '🇹🇷',
        'he' => '🇮🇱',
        'uk' => '🇺🇦',
        'cs' => '🇨🇿',
        'hu' => '🇭🇺',
        'ro' => '🇷🇴',
        'bg' => '🇧🇬',
        'hr' => '🇭🇷',
        'sk' => '🇸🇰',
        'sl' => '🇸🇮',
        'et' => '🇪🇪',
        'lv' => '🇱🇻',
        'lt' => '🇱🇹',
        'el' => '🇬🇷',
        'ca' => '🇪🇸',
        'eu' => '🇪🇸',
        'gl' => '🇪🇸',
    );
    
    return $flag_map[$lang_code] ?? '🌐';
}

