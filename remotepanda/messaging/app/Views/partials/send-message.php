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
                    <label for="message">Message</label>
                    <?php echo form_textarea("message", "", ["class"=>"form-control"]) ?>
                    <div class="mt-4">
                        <p>Legend</p>
                        <small class="d-block text-secondary"><code>{{name}}</code> will be replaced by the user's name</small>
                        <small class="d-block text-secondary"><code>{{ordinal}}</code> will be replaced by the user's ordinalized age'</small>
                        <small class="d-block text-secondary"><code>{{age}}</code> will be replaced by the user's age'</small>
                        <small class="d-block text-secondary"><code>{{company}}</code> will be replaced by the company name</small>
                    </div>
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