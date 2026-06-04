(function ($) {
    'use strict';
    $(() => {

        let cancelled = true;

        const broadcastControls = $(".broadcast-controls-container .broadcast-controls");
        const broadcastProgress = $(".broadcast-controls-container .broadcast-progress");

        broadcastControls.show();
        broadcastProgress.hide();

        $("#broadcast").on("submit", async (event) => {
            event.preventDefault();

            cancelled = false;

            // retrieve customer list
            try {
                const response = await fetch(window.hillpaul.base_url + "/api/customers/", {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());

                broadcastControls.hide();
                broadcastProgress.show();

                const params = new URLSearchParams();
                params.append("message", window.tinymce.activeEditor.getContent());

                const customers = response.data;

                for (let i = 0; i < customers.length; i++) {
                    if (cancelled) {
                        break;
                    }

                    const customer = customers[i];
                    const position = i + 1;

                    broadcastProgress.find(".progress-text span").first().text(customer.name + " (" + position + " of " + customers.length + ")");

                    const progressBar = broadcastProgress.find(".progress-bar");

                    const progress = position / customers.length * 100;

                    progressBar.css("width", progress + "%");
                    progressBar.attr("aria-valuenow", progress);

                    try {
                        await fetch(window.hillpaul.base_url + "/api/message/" + customer["id"], {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'xmlhttprequest'
                            },
                            body: params
                        });
                    } catch (error) {

                    }
                }

                broadcastControls.show();
                broadcastProgress.hide();

            } catch (error) {

            }
        });

        broadcastProgress.find("button").on("click", (event) => {
            cancelled = true;
        });

    });
})(window.jQuery);