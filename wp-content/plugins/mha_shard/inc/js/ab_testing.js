jQuery(function ($) {
    
    function getRandomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function mha_article_footer_ab_test(){
        
        if($('.single-article .layout-action-random').length){
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

    }

    mha_article_footer_ab_test();

});