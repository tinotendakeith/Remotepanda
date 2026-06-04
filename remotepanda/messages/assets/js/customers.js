(function ($) {
    'use strict';
    $(() => {

        $(".btn-method").click(async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            try {
                const response = await fetch(window.hillpaul.base_url + "/api/method/" + customerId, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());

                element.text(response.description);

                const iconElement = $("tr[data-customer='" + customerId + "'] .icon-method").first();
                iconElement.removeClass("mdi-message mdi-whatsapp");
                iconElement.addClass("mdi-" + response.method);

            } catch (error) {

            }

        });

        $(".btn-subscribe").on("click", async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            try {
                const response = await fetch(window.hillpaul.base_url + "/api/subscribe/" + customerId, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());

                element.text(response.description);

                const iconElement = $("tr[data-customer='" + customerId + "'] .icon-subscribe").first();
                iconElement.removeClass("mdi-toggle-switch mdi-toggle-switch-off");
                iconElement.addClass(response["subscribed"] ? "mdi-toggle-switch" : "mdi-toggle-switch-off");

            } catch (error) {

            }

        });

    });
})(window.jQuery);