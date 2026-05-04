/**
 * Browser Language Detection for TranslatePress
 * 
 * Simple implementation:
 * 1. Get the <html> tag's lang attribute as the default (e.g., lang--en)
 * 2. Get the current TranslatePress language (e.g., lang--es)
 * 3. Check for changes to the <html> lang attribute and update to the language with -browser suffix (e.g., lang--fr-browser)
 */

/**
 * Two-letter language code from Google Translate URL (?_x_tr_tl=), or empty.
 * Uses PHP-localized config and falls back to the current query string.
 *
 * @return {string}
 */
function mhaGetGoogleTranslateLangCode() {
    var c = '';
    if (typeof mhaBrowserLanguageConfig !== 'undefined' && mhaBrowserLanguageConfig.googleTranslateLang) {
        c = String(mhaBrowserLanguageConfig.googleTranslateLang).toLowerCase().substring(0, 2);
    }
    if (!c || !/^[a-z]{2}$/.test(c)) {
        try {
            var p = new URLSearchParams(window.location.search).get('_x_tr_tl');
            if (p) {
                c = String(p).substring(0, 2).toLowerCase();
            }
        } catch (e) {
            c = '';
        }
    }
    return /^[a-z]{2}$/.test(c) ? c : '';
}

/**
 * Update Gravity Forms field with label "Language" when language changes
 * 
 * Searches for fields with values starting with "lang--" prefix (e.g., "lang--en")
 * This works for hidden fields that don't have visible labels.
 * 
 * When ?_x_tr_tl= is present (Google Translate), uses that language and "lang--xx-google"
 * so server-rendered values are not overwritten with a plain two-letter code.
 * 
 * @param {string} languageCode - Two-letter language code to set (e.g., 'en', 'es')
 * @param {boolean} addBrowserSuffix - If true, adds "-browser" suffix (e.g., "lang--fr-browser"); ignored if Google Translate URL is active
 */
function mhaUpdateGravityFormsLanguageField(languageCode, addBrowserSuffix) {
    var googleCode = mhaGetGoogleTranslateLangCode();
    var baseCode = googleCode || languageCode;
    if (!baseCode) {
        //console.log('[MHA Browser Language] No language code provided for Gravity Forms update');
        return;
    }
    
    // Find all Gravity Forms on the page
    var forms = document.querySelectorAll('form[id^="gform_"]');
    
    if (forms.length === 0) {
        //console.log('[MHA Browser Language] No Gravity Forms found on page');
        return;
    }
    
    var fieldUpdated = false;
    var newValue = 'lang--' + baseCode;
    if (googleCode) {
        newValue += '-google';
    } else if (addBrowserSuffix) {
        newValue += '-browser';
    }
    
    //console.log('[MHA Browser Language] Updating Gravity Forms Language field to:', newValue);
    
    // Iterate through each form
    forms.forEach(function(form) {
        // Find all input fields in the form (including hidden fields)
        var inputs = form.querySelectorAll('input[type="hidden"], input[type="text"], select');
        
        inputs.forEach(function(input) {
            var currentValue = input.value || '';
            
            // Check if this field's value starts with "lang--"
            if (currentValue.indexOf('lang--') === 0) {
                // Check if the field is inside a hidden .gform_page div (pagination)
                var gformPage = input.closest('.gform_page');
                if (gformPage) {
                    // Check if the page is hidden
                    var style = window.getComputedStyle(gformPage);
                    if (style.display === 'none' || style.visibility === 'hidden' ||
                        gformPage.classList.contains('gf_hidden') || 
                        gformPage.classList.contains('gform_page_hidden') ||
                        gformPage.hasAttribute('hidden')) {
                        //console.log('[MHA Browser Language] Skipping update - field is in hidden .gform_page');
                        return; // Skip this field
                    }
                }
                
                var oldValue = currentValue;
                input.value = newValue;
                
                // Trigger change event for Gravity Forms
                var changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
                
                // Also trigger input event
                var inputEvent = new Event('input', { bubbles: true });
                input.dispatchEvent(inputEvent);
                
                // If jQuery is available, also trigger jQuery events
                if (typeof jQuery !== 'undefined') {
                    jQuery(input).val(newValue).trigger('change');
                    
                    // Trigger Gravity Forms specific events
                    var formId = form.id.replace('gform_', '');
                    if (formId) {
                        var inputId = input.id || input.name;
                        if (inputId) {
                            // Extract field ID from input name (e.g., "input_1_5" -> "1.5")
                            var fieldIdMatch = inputId.match(/input_(\d+)(?:_(\d+))?/);
                            if (fieldIdMatch) {
                                var fieldId = fieldIdMatch[2] ? fieldIdMatch[1] + '.' + fieldIdMatch[2] : fieldIdMatch[1];
                                jQuery(document).trigger('gform_input_change', [input, formId, fieldId]);
                            }
                        }
                    }
                }
                
                //console.log('[MHA Browser Language] Updated Gravity Forms Language field:', oldValue, '->', newValue, '(input:', input.id || input.name + ')');
                fieldUpdated = true;
            }
        });
    });
    
    if (!fieldUpdated) {
        //console.log('[MHA Browser Language] No Gravity Forms Language field found (no field with value starting with "lang--")');
    }
}

/**
 * Get language code from HTML lang attribute
 * 
 * @return {string} Two-letter language code or empty string
 */
function mhaGetHtmlLang() {
    var htmlLang = document.documentElement.lang;
    if (htmlLang) {
        var langCode = htmlLang.substring(0, 2).toLowerCase();
        //console.log('[MHA Browser Language] HTML lang attribute:', langCode, '(original:', htmlLang + ')');
        return langCode;
    }
    return '';
}

/**
 * Initialize language field with HTML lang attribute value
 */
function mhaInitializeLanguageField() {
    var htmlLang = mhaGetHtmlLang();
    if (htmlLang) {
        //console.log('[MHA Browser Language] Initializing language field with HTML lang:', htmlLang);
        mhaUpdateGravityFormsLanguageField(htmlLang, false);
    } else {
        //console.log('[MHA Browser Language] No HTML lang attribute found for initialization');
    }
}

/**
 * Watch for changes to the HTML lang attribute
 * When it changes, update the field with the new language and -browser suffix
 */
function mhaWatchHtmlLangAttribute() {
    // Check if MutationObserver is supported
    if (typeof MutationObserver === 'undefined') {
        //console.log('[MHA Browser Language] MutationObserver not supported, cannot watch HTML lang attribute');
        return;
    }
    
    // Store the last known lang value
    var lastLang = document.documentElement.lang || '';
    //console.log('[MHA Browser Language] Starting to watch HTML lang attribute (initial value:', lastLang + ')');
    
    // Create observer to watch for attribute changes on the html element
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'lang') {
                var newLang = document.documentElement.lang || '';
                var normalizedNewLang = newLang ? newLang.substring(0, 2).toLowerCase() : '';
                var normalizedLastLang = lastLang ? lastLang.substring(0, 2).toLowerCase() : '';
                
                //console.log('[MHA Browser Language] HTML lang attribute changed - Old:', lastLang, 'New:', newLang);
                
                // Only update if the language code actually changed (not just the format)
                if (normalizedNewLang !== normalizedLastLang && normalizedNewLang) {
                    //console.log('[MHA Browser Language] Language code changed from', normalizedLastLang, 'to', normalizedNewLang, '- updating with -browser suffix');
                    // Update Gravity Forms Language field with new language and -browser suffix
                    mhaUpdateGravityFormsLanguageField(normalizedNewLang, true);
                }
                
                lastLang = newLang;
            }
        });
    });
    
    // Start observing the html element for attribute changes
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['lang']
    });
    
    //console.log('[MHA Browser Language] HTML lang attribute observer active');
}

// Auto-initialize on DOM ready
(function() {
    var initFunction = function() {
        // Initialize with HTML lang attribute (no suffix)
        mhaInitializeLanguageField();
        
        // Watch for HTML lang attribute changes (with -browser suffix)
        mhaWatchHtmlLangAttribute();
        
        // Listen for Gravity Forms post-render events (forms loaded dynamically)
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('gform_post_render', function(event, formId, currentPage) {
                //console.log('[MHA Browser Language] Gravity Form rendered (formId:', formId, 'page:', currentPage + ')');
                // Re-initialize language field when form is rendered
                mhaInitializeLanguageField();
            });
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFunction);
    } else {
        // DOM already loaded
        initFunction();
    }
})();
