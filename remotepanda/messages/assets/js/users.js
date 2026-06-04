(function ($) {
    'use strict';
    $(() => {

        $("#modalEditUser").on('show.bs.modal', async (event) => {
            const element = $(event.relatedTarget);

            const userId = element.data("user");

            if (userId) {
                const userName = element.data("user-name");
                const userLogin = element.data("user-login");
                const userEmail = element.data("user-email");

                $("#modalEditUser .modal-title").text("Edit User " + userName);

                $("#modalEditUser input[name='user-id']").val(userId);
                $("#modalEditUser input[name='user-name']").val(userName);
                $("#modalEditUser input[name='user-login']").val(userLogin);
                $("#modalEditUser input[name='user-email']").val(userEmail);
            }
        });

        $(".btn-delete").on("click", async (event) => {
            let element = $(event.target);
            const userId = element.data("user");
            const userName = element.data("user-name");

            if (window.confirm("Sure to delete " + userName)) {
                try {
                    await fetch(window.hillpaul.base_url + "/api/user/delete/" + userId, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'xmlhttprequest'
                        },
                    });

                } catch (error) {

                } finally {
                    $(".table tr.row-" + userId).hide();
                }
            }

        });

    });
})(window.jQuery);