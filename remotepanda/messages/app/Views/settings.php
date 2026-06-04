<?php echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Settings"]) ?>
</head>

<body>

<?php
/**
 * @var object $validation
 */
?>

<div class="container-scroller">

    <?php echo view("partials/top-bar", ["search" => false]) ?>

    <!-- partial -->
    <div class="container-fluid page-body-wrapper">

        <?php echo view("partials/side-bar") ?>

        <!-- partial -->
        <div class="main-panel">
            <div class="content-wrapper">

                <div class="row">
                    <div class="col-md-12 grid-margin">
                        <div class="d-flex justify-content-between flex-wrap">
                            <div class="d-flex align-items-end flex-wrap">
                                <div class="me-md-3 me-xl-5">
                                    <h2>Manage Settings,</h2>
                                    <p class="mb-md-0">System Settings</p>
                                </div>
                                <div class="d-flex">
                                    <i class="mdi mdi-home text-muted hover-cursor"></i>
                                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Home&nbsp;/&nbsp;</p>
                                    <p class="text-primary mb-0 hover-cursor">Settings</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-end flex-wrap">

                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($validation->listErrors()) : ?>
                    <div class="row">
                        <div class='my-3 bg-danger text-white p-1'>
                            <?php echo $validation->listErrors(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php $flashMessage = session()->getFlashdata('message'); ?>
                <?php if ($flashMessage ?? "") : ?>
                    <div class="row">
                        <div class='my-3 bg-success text-white p-2'>
                            <?php echo $flashMessage ?? ""; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Settings</h4>
                                <p class="card-description">
                                    System Settings
                                </p>
                                <?php echo form_open("settings", ["class" => "mt-5", "method" => "GET"]) ?>
                                <div class="form-check form-check-flat form-check-primary">
                                    <label class="form-check-label">
                                        <?php echo form_checkbox("enabled", "true", $enabled ?? false, ["class" => "form-control"]) ?>
                                        Enable Messaging System
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="number">Test Messages Number</label>
                                    <?php echo form_input("test-number", $testNumber ?? "", ["class" => "form-control", "placeholder" => "Number to send messages with country code e.g +267774790262", "required" => "required", "autocomplete" => "tel"], "tel") ?>
                                    <small class="d-block text-secondary mt-1">When system is disabled all messages are
                                        sent to this number</small>
                                </div>
                                <div class="form-group">
                                    <label for="page-limit">Items Per Page</label>
                                    <?php $options = [
                                        "10" => "10",
                                        "25" => "25",
                                        "50" => "50",
                                        "100" => "100",
                                    ]; ?>
                                    <?php echo form_dropdown("page-limit", $options, $pageLimit ?? DEFAULT_PER_PAGE, ["class" => "form-control"]) ?>
                                </div>

                                <div class="form-group">
                                    <label for="site-name">Company Name</label>
                                    <?php echo form_input("site-name", $siteName ?? "", ["class" => "form-control", "required" => "required", "placeholder" => "Name of site", "autocomplete" => "organization"]) ?>
                                </div>

                                <div class="form-group">
                                    <label for="message">Message</label>
                                    <?php echo form_textarea("message", $message ?? "", ["class" => "form-control", "min-length" => 5]) ?>
                                    <?php echo view("partials/legend") ?>
                                </div>

                                <button type="submit" class="btn btn-primary me-2" name="system">Save</button>
                                <?php echo form_close() ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Twilio Settings</h4>
                                <p class="card-description">
                                    Connection Settings to Twilio
                                </p>
                                <?php echo form_open("settings", ["class" => "mt-5", "method" => "GET"]) ?>

                                <div class="form-group">
                                    <label for="twilio-sid">Twilio SID</label>
                                    <?php echo form_input("twilio-sid", "", ["class" => "form-control", "required" => "required", "placeholder" => "ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"], "password") ?>
                                    <small class="text-secondary">Modifies value set in <code>.env</code>
                                        file</small>
                                </div>
                                <div class="form-group">
                                    <label for="twilio-token">Twilio Token</label>
                                    <?php echo form_input("twilio-token", "", ["class" => "form-control", "required" => "required", "placeholder" => "your_auth_token"], "password") ?>
                                    <small class="text-secondary">Modifies value set in <code>.env</code> file</small>
                                </div>
                                <div class="form-group">
                                    <label for="twilio-from">Twilio From Number</label>
                                    <?php echo form_input("twilio-from", $twilio_from ?? "", ["class" => "form-control", "placeholder" => "A Twilio phone number you purchased at twilio.com/console", "required" => "required", "autocomplete" => "tel"], "tel") ?>
                                    <small class="text-secondary">Modifies value set in <code>.env</code> file</small>
                                </div>

                                <button type="submit" class="btn btn-primary me-2" name="twilio">Save</button>
                                <?php echo form_close() ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">System Tests</h4>
                                <p class="card-description">
                                    Test Message Sending
                                </p>
                                <?php echo form_open("settings", ["class" => "mt-5", "method" => "GET"]) ?>

                                <div class="form-group">
                                    <label for="number">Test Number</label>
                                    <?php echo form_input("test-number", "", ["class" => "form-control", "placeholder" => "Test number to send test message", "required" => "required", "autocomplete" => "tel"], "tel") ?>
                                </div>

                                <label>Method for sending message</label>
                                <div class="form-check form-check-flat form-check-primary">
                                    <label class="form-check-label">
                                        <?php echo form_radio("test-method", "whatsapp", false, ["class" => "form-control", "required" => "required"]) ?>
                                        Whatsapp
                                    </label>
                                </div>
                                <div class="form-check form-check-flat form-check-primary">
                                    <label class="form-check-label">
                                        <?php echo form_radio("test-method", "message", true, ["class" => "form-control", "required" => "required"]) ?>
                                        Message
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary me-2" name="test">Send Test Message
                                </button>
                                <?php echo form_close() ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- content-wrapper ends -->

            <?php echo view("partials/copyright") ?>

        </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<?php echo view("partials/footer") ?>
<?php echo view("partials/tinymce") ?>

</body>

</html>
