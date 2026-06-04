<!-- send message modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalEditUser" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open("users", ["id"=>"manageUSerForm"]) ?>
                <?php echo form_hidden("user-id", DEFAULT_ID) ?>
                <div class="form-group">
                    <label for="user-name">User Name</label>
                    <?php echo form_input("user-name", "", ["class" => "form-control", "placeholder" => "Name of user", "required" => "required"]) ?>
                </div>
                <div class="form-group">
                    <label for="user-name">User Login</label>
                    <?php echo form_input("user-login", "", ["class" => "form-control", "placeholder" => "Alternative login identifier of user", "required" => "required"]) ?>
                </div>
                <div class="form-group">
                    <label for="user-name">User Email</label>
                    <?php echo form_input("user-email", "", ["class" => "form-control", "placeholder" => "Email of user", "required" => "required"], "email") ?>
                    <small>Will not be confirmed</small>
                </div>
                <div class="form-group">
                    <label for="user-password">User Password</label>
                    <?php echo form_input("user-password", "", ["class" => "form-control", "placeholder" => "Password of user", "required" => "required", "min-length" => "6"], "password") ?>
                </div>
                <div class="form-group">
                    <label for="user-password-confirm">Confirm Password</label>
                    <?php echo form_input("user-password-confirm", "", ["class" => "form-control", "placeholder" => "Password Confirmation", "required" => "required"], "password") ?>
                </div>
                <?php echo form_close() ?>
            </div>
            <div class="modal-footer">
                <button type="submit" form="manageUSerForm" name="submit" class="btn btn-primary btn-send-message">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- send message modal ends -->