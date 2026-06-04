<!-- send message modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalSendMessage" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Message to <span></span></h5>
                <button type="button" class="btn close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open("settings", []) ?>
                <div class="form-group">
                    <label for="message" class="text-secondary">Message</label>
                    <?php echo form_textarea("message", "", ["class" => "form-control"]) ?>
                    <?php echo view("partials/legend") ?>
                </div>
                <?php echo form_close() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-send-message">Send Message</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- send message modal ends -->