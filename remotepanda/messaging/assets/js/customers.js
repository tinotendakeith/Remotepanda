(function ($) {
    'use strict';
    $(() => {

        $(".btn-method").click(async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            try {
                const  response = await fetch(window.hillpaul.base_url + "/method/" + customerId, {
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());

                element.text(response.method);
            } catch (error) {

            }

        });

        $(".btn-subscribe").click(async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            try {
               const  response = await fetch(window.hillpaul.base_url + "/subscribe/" + customerId, {
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());

               element.text(response.subscribed);
            } catch (error) {

            }

        });

    });
})(jQuery);