<?php

// Check if the NW_Purechat class exists, if not, define it.
    class NW_Purechat
    {
        /**
         * Initialize the Purechat functionality by hooking into 'wp_head'.
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_feature_purechat')) {
                return;
            }

            add_action('wp_head', __CLASS__ . '::purechat_script');
        }

        /**
         *Function to add the Purechat script to the front-end.
         */

        public static function purechat_script()
        {
            if (!is_front_page()) :
?>
                <script type='text/javascript' data-cfasync='false'>
                    window.purechatApi = {
                        l: [],
                        t: [],
                        on: function() {
                            this.l.push(arguments);
                        }
                    };
                    (function() {
                        var done = false;
                        var script = document.createElement('script');
                        script.async = true;
                        script.type = 'text/javascript';
                        script.src = 'https://app.purechat.com/VisitorWidget/WidgetScript';
                        document.getElementsByTagName('HEAD').item(0).appendChild(script);
                        script.onreadystatechange = script.onload = function(e) {
                            if (!done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) {
                                var w = new PCWidget({
                                    c: 'c49017fa-8bca-4df8-9fc5-1d32c9a9119d',
                                    f: true
                                });
                                done = true;
                            }
                        };
                    })();
                </script>
<?php
            endif;
        }
    }

    // Initialize the NW_Purechat class and Purechat functionality.

    NW_Purechat::init();
?>