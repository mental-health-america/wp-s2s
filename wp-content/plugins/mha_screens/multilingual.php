<?php
/**
 * Parse browser Accept-Language header and return primary language code
 * 
 * @param string $accept_language The Accept-Language header value (e.g., "en-US,en;q=0.9,es;q=0.8")
 * @return string Two-letter language code (e.g., "en") or empty string if not found
 */
function mha_parse_browser_language( $accept_language ) {
    if ( empty( $accept_language ) ) {
        return '';
    }
    
    // Parse the Accept-Language header
    // Format: "en-US,en;q=0.9,es;q=0.8" or "en,es;q=0.8"
    $languages = array();
    
    // Split by comma to get individual language preferences
    $lang_parts = explode( ',', $accept_language );
    
    foreach ( $lang_parts as $lang_part ) {
        // Remove quality values (q=0.9) and whitespace
        $lang_part = trim( $lang_part );
        
        // Extract language code (remove quality parameter if present)
        if ( strpos( $lang_part, ';' ) !== false ) {
            $lang_part = trim( substr( $lang_part, 0, strpos( $lang_part, ';' ) ) );
        }
        
        // Extract two-letter language code (e.g., "en" from "en-US")
        if ( strpos( $lang_part, '-' ) !== false ) {
            $lang_code = substr( $lang_part, 0, strpos( $lang_part, '-' ) );
        } else {
            $lang_code = $lang_part;
        }
        
        // Normalize to lowercase and validate (2-letter code)
        $lang_code = strtolower( trim( $lang_code ) );
        if ( strlen( $lang_code ) === 2 && ctype_alpha( $lang_code ) ) {
            $languages[] = $lang_code;
        }
    }
    
    // Return the first (highest priority) language code
    return !empty( $languages ) ? $languages[0] : '';
}

/**
 * Get current language code with TranslatePress support and URL override
 */
function mha_get_current_language_code() {

    // Check for URL parameter override first (e.g., from Google Translate)
    if ( isset( $_GET['_x_tr_tl'] ) && ! empty( $_GET['_x_tr_tl'] ) ) {
        $url_language = strtolower( substr( sanitize_text_field( wp_unslash( $_GET['_x_tr_tl'] ) ), 0, 2 ) );
        if ( strlen( $url_language ) === 2 && ctype_alpha( $url_language ) ) {
            return $url_language . '-google';
        }
    }
    
    // Check if TranslatePress is active and get current language
    if ( function_exists( 'trp_get_current_language' ) ) {
        $trp_language = trp_get_current_language();
        if ( !empty( $trp_language ) ) {
            return $trp_language;
        }
    }
    
    // Check browser's Accept-Language header
    if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) && !empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
        $browser_language = mha_parse_browser_language( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
        if ( !empty( $browser_language ) ) {
            return $browser_language;
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
 * Normalize a language code for shortcode comparison (two-letter prefix, lowercase).
 *
 * @param string $code Raw code from TranslatePress, locale, etc.
 * @return string
 */
function mha_normalize_lang_code( $code ) {
    $code = strtolower( trim( (string) $code ) );
    if ( $code === '' ) {
        return '';
    }
    if ( strlen( $code ) >= 2 && ctype_alpha( substr( $code, 0, 2 ) ) ) {
        return substr( $code, 0, 2 );
    }
    return $code;
}

/**
 * Show inner content only when the current language matches the `is` attribute.
 *
 * Example: [mha_lang is="es"]Spanish only[/mha_lang]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Enclosed content.
 * @return string
 */
function mha_lang_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts(
        array(
            'is' => '',
        ),
        $atts,
        'mha_lang'
    );

    if ( $atts['is'] === '' ) {
        return '';
    }

    $target  = mha_normalize_lang_code( sanitize_text_field( $atts['is'] ) );
    $current = mha_normalize_lang_code( mha_get_current_language_code() );

    if ( $target !== '' && $target === $current ) {
        return do_shortcode( (string) $content );
    }

    return '';
}
add_shortcode( 'mha_lang', 'mha_lang_shortcode' );

/**
 * Pre-render handler to populate language field in forms using TranslatePress
 * 
 * Sets the default value with "lang--" prefix (e.g., "lang--en") so JavaScript
 * can identify and update the field even if it's hidden without a label.
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
                $language_code = mha_get_current_language_code();
                // Prefix with "lang--" so JavaScript can find it
                $field->defaultValue = 'lang--' . $language_code;
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

/**
 * Get available languages from TranslatePress
 * 
 * @return array Array of language codes (e.g., ['en', 'es'])
 */
function mha_get_translatepress_available_languages() {
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
    
    // Extract language codes (2-letter codes)
    foreach ( $trp_languages as $lang_code => $lang_data ) {
        // Get 2-letter code from full locale (e.g., 'en_US' -> 'en')
        $two_letter_code = substr( $lang_code, 0, 2 );
        if ( !in_array( $two_letter_code, $available_languages ) ) {
            $available_languages[] = $two_letter_code;
        }
    }
    
    return $available_languages;
}

/**
 * Get default language from TranslatePress
 * 
 * @return string Two-letter language code (e.g., 'en')
 */
function mha_get_translatepress_default_language() {
    $default_language = 'en';
    
    if ( function_exists( 'trp_get_default_language' ) ) {
        $default_language = trp_get_default_language();
    } else {
        // Fallback: get default language from options
        $trp_settings = get_option( 'trp_settings', array() );
        if ( isset( $trp_settings['default-language'] ) ) {
            $default_language = $trp_settings['default-language'];
        }
    }
    
    // Convert to 2-letter code (e.g., 'en_US' -> 'en')
    return substr( $default_language, 0, 2 );
}

/**
 * Resolve TranslatePress language from the request URL subdirectory slug.
 *
 * @return string Locale code (e.g. es_ES) or empty string when not found.
 */
function mha_get_translatepress_language_from_url() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        return '';
    }

    $trp_settings = get_option( 'trp_settings', array() );
    if ( empty( $trp_settings['url-slugs'] ) || ! is_array( $trp_settings['url-slugs'] ) ) {
        return '';
    }

    $path = trim( (string) parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
    if ( '' === $path ) {
        return '';
    }

    $slug = strtok( $path, '/' );
    if ( ! $slug ) {
        return '';
    }

    foreach ( $trp_settings['url-slugs'] as $locale => $url_slug ) {
        if ( (string) $url_slug === $slug ) {
            return $locale;
        }
    }

    return '';
}

/**
 * Get the language of the page being viewed in TranslatePress.
 *
 * Uses TRP globals, filters, and URL slug detection. Does not use the browser
 * Accept-Language header, which can incorrectly report English on /es/ URLs.
 *
 * @return string Language or locale code.
 */
function mha_get_translatepress_current_language() {
    global $TRP_LANGUAGE;

    if ( ! empty( $TRP_LANGUAGE ) ) {
        return $TRP_LANGUAGE;
    }

    if ( function_exists( 'trp_get_current_language' ) ) {
        $language = trp_get_current_language();
        if ( ! empty( $language ) ) {
            return $language;
        }
    }

    $filtered_language = apply_filters( 'trp_user_language', '' );
    if ( ! empty( $filtered_language ) ) {
        return $filtered_language;
    }

    $language_from_url = mha_get_translatepress_language_from_url();
    if ( '' !== $language_from_url ) {
        return $language_from_url;
    }

    if ( function_exists( 'trp_get_default_language' ) ) {
        return trp_get_default_language();
    }

    $trp_settings = get_option( 'trp_settings', array() );
    if ( ! empty( $trp_settings['default-language'] ) ) {
        return $trp_settings['default-language'];
    }

    return 'en_US';
}

/**
 * Whether the current page is rendered in a non-default TranslatePress language.
 *
 * @return bool
 */
function mha_is_non_default_language_page() {
    $default = mha_normalize_lang_code( mha_get_translatepress_default_language() );
    $current = mha_normalize_lang_code( mha_get_translatepress_current_language() );

    return $current !== '' && $default !== '' && $current !== $default;
}

/**
 * Enqueue browser language check JavaScript
 * 
 * This function enqueues a JavaScript file that checks if the browser's language
 * differs from the current TranslatePress language selection.
 * 
 * The script will:
 * - Parse the browser's language preference (navigator.language)
 * - Compare it to the current TranslatePress language
 * - Optionally redirect to the browser's language if available
 * - Trigger a custom event 'mhaBrowserLanguageMismatch' if languages differ
 * 
 * To enable automatic redirect, add this filter in your theme's functions.php:
 * add_filter( 'mha_browser_language_auto_redirect', '__return_true' );
 * 
 * To listen for language mismatch events in JavaScript:
 * document.addEventListener('mhaBrowserLanguageMismatch', function(e) {
 *     console.log('Browser language:', e.detail.browserLanguage);
 *     console.log('Current language:', e.detail.currentLanguage);
 *     console.log('Available:', e.detail.available);
 * });
 */
function mha_enqueue_browser_language_check() {
    // Only enqueue on frontend
    if ( is_admin() ) {
        return;
    }
    
    // Check if TranslatePress is active
    if ( !function_exists( 'trp_get_languages' ) ) {
        return;
    }
    
    // Get plugin directory URL
    $plugin_url = plugin_dir_url( __FILE__ );
    
    // Enqueue the JavaScript file
    wp_enqueue_script(
        'mha-browser-language-check',
        $plugin_url . 'js/browser-language-check.js',
        array(), // No dependencies
        '1.0.2',
        true // Load in footer
    );
    
    // Get available languages and default language
    $available_languages = mha_get_translatepress_available_languages();
    $default_language = mha_get_translatepress_default_language();
    
    // Get current language
    $current_language = '';
    if ( function_exists( 'trp_get_current_language' ) ) {
        $current_language = trp_get_current_language();
        // Convert to 2-letter code
        $current_language = substr( $current_language, 0, 2 );
    }
    
    // Allow filtering of auto-redirect setting
    $auto_redirect = apply_filters( 'mha_browser_language_auto_redirect', false );

    // Two-letter code from Google Translate URL param (matches mha_get_current_language_code Google branch)
    $google_translate_lang = '';
    if ( isset( $_GET['_x_tr_tl'] ) && ! empty( $_GET['_x_tr_tl'] ) ) {
        $gt = strtolower( substr( sanitize_text_field( wp_unslash( $_GET['_x_tr_tl'] ) ), 0, 2 ) );
        if ( strlen( $gt ) === 2 && ctype_alpha( $gt ) ) {
            $google_translate_lang = $gt;
        }
    }
    
    // Localize script with configuration
    wp_localize_script(
        'mha-browser-language-check',
        'mhaBrowserLanguageConfig',
        array(
            'autoRedirect' => $auto_redirect, // Set to true to enable automatic redirect
            'availableLanguages' => $available_languages,
            'defaultLanguage' => $default_language,
            'currentLanguage' => $current_language,
            'googleTranslateLang' => $google_translate_lang,
        )
    );
}
add_action( 'wp_enqueue_scripts', 'mha_enqueue_browser_language_check' );

