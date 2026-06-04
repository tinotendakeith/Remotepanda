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

            window.tinymce.activeEditor.execCommand('mceSetContent', false, message);
        });

        $("#modalSendMessage .btn-send-message").on("click", async (event) => {
            const element = $(event.target);
            const customerId = element.data("customer");

            const params = new URLSearchParams();
            params.append("message", window.tinymce.activeEditor.getContent());

            try {
                await fetch(window.hillpaul.base_url + "/api/message/" + customerId, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'xmlhttprequest'
                    },
                    body: params
                });
            } catch (error) {

            } finally {
                $("#modalSendMessage").modal("hide");
            }
        });

    });
})(window.jQuery);