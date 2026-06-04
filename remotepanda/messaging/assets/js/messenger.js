(function ($) {
    'use strict';
    $(() => {

        $("#modalSendMessage").on('show.bs.modal', async (event) => {
            const element = $(event.relatedTarget);

            const customerId = element.data("customer");
            const customerName = element.data("customer-name");
            const message = element.data("message");

            $("#modalSendMessage .modal-title span").text(customerName);
            $("#modalSendMessage .btn-send-message").data("customer", customerId);

            window.tinymce.execCommand('mceSetContent', false, message);
        });

        $("#modalSendMessage .btn-send-message").click(async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            try {
                await fetch(window.hillpaul.base_url + "/notify/" + customerId, {
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                }).then(response => response.json());
            } catch (error) {

            } finally {
                $("#modalSendMessage").modal("hide");
            }
        });

    });
})(jQuery);