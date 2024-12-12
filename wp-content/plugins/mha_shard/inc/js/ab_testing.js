jQuery(function ($) {
    
    function getRandomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    /**
     * Check for lazy loading iframes and display them only when visible
     */
    function mha_gform_lazyload_iframe() {
                    
        $('iframe[data-src]').each(function () {

            let $iframe = $(this),
                $gfieldHtml = $iframe.parents('.gfield--type-html'),
                $layoutAction = $iframe.parents('.layout-action');

            if ($gfieldHtml.length > 0 && $gfieldHtml.is(':visible')) {
                if ($layoutAction.length > 0) {
                    if ($layoutAction.is(':visible')) {
                        // A/B testing detected, load the relevant iframe
                        mha_swap_iframe_src($iframe);
                    }
                } else {
                    // A/B testing not detected, load the relevant iframe
                    mha_swap_iframe_src($iframe);
                }
            }

        });

    }
    function mha_lazyload_iframe() {     
        $('iframe[data-src]').each(function () {

            let $iframe = $(this),
                $layoutAction = $iframe.parents('.layout-action');

            if ($layoutAction.length > 0) {
                if ($layoutAction.is(':visible')) {

                    // A/B testing detected, display the relevant iframe
                    mha_swap_iframe_src($iframe);

                }
            }

        });
    }
    
    function mha_swap_iframe_src($iframe) {
        let dataSrc = $iframe.attr('data-src'),
            $globalVideos = $('#mha-global-video-titles'),
            globalVideoTitles = $globalVideos.length ? JSON.parse($globalVideos.val()) : null;

        // Display the provided iframe
        $iframe.attr('src', dataSrc).removeAttr('data-src');

        /**
         * TIPP Form Overrides
         */
        let fieldPrefix = '#input_56_'; // 56 prod, 61 dev

        // Update video URL field         
        for (let i = 0; i < globalVideoTitles.length; i++) {
            if( dataSrc.indexOf(globalVideoTitles[i].video_id) !== -1 ){

                // Update video ID field
                if($(fieldPrefix+'84').val() == ''){
                    $(fieldPrefix+'84').val(globalVideoTitles[i].video_id);
                }

                // Update video title field
                if($(fieldPrefix+'85').val() == ''){
                    $(fieldPrefix+'85').val(globalVideoTitles[i].video_title);
                }

                break;
            }
        }


    }

    function mha_article_footer_ab_test(){
        
        //if($('.single-article .layout-action-random').length){
        if($('.layout-action-random').length){
            let total_actions = $('.layout-action-random').length - 1,
                url = new URL(window.location.href),
                layout = url.searchParams.get("layout");
            
            if(!layout){
                
                let index = getRandomInt(0, total_actions),
                    el = document.getElementsByClassName('layout-action-random');

                if (index >= 0 && index < el.length) {
                    let selected = el[index];
                    $(selected).show();
                    
                    window.dataLayer.push({
                        'event': 'article_footer_cta_displayed',
                        'cta_id': $(selected).find('form').attr('id')
                    });

                    /*
                    if(selected){
                        let layout_class = el[index].className,
                            layout_search = 'show-actions_';

                        let startIndex = layout_class.indexOf(layout_search);
                        if (startIndex !== -1) {
                            let endIndex = layout_class.indexOf(" ", startIndex);
                            if (endIndex === -1) {
                                endIndex = layout_class.length;
                            }

                            // Loads random article footer item
                            let extracted = layout_class.substring(startIndex, endIndex);
                            $('.'+extracted).show();

                            // Redirects to specific layout
                            //let extracted = layout_class.substring(startIndex, endIndex).replace('show-','');
                            //url.searchParams.append('layout', extracted); 
                            //window.location.replace(url.href);

                        }
                    }
                    */
                }

            }
        }

        // Lazy load normal iframes
        mha_lazyload_iframe();

    }

    mha_article_footer_ab_test();

    // Lazy load iframes when visible in HTML fields
    $(document).on('gform_post_conditional_logic', function(){
        mha_gform_lazyload_iframe();
    });

});