<?php echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Broadcast Message"]) ?>
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
                                    <h2>Broadcast Message,</h2>
                                    <p class="mb-md-0">Send a single messages to all customers in the system</p>
                                </div>
                                <div class="d-flex">
                                    <i class="mdi mdi-home text-muted hover-cursor"></i>
                                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Home&nbsp;/&nbsp;</p>
                                    <p class="text-primary mb-0 hover-cursor">Broadcast</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-end flex-wrap">

                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="">

                                    <?php if ($validation->listErrors()) : ?>
                                        <div class="row">
                                            <div class='my-3 bg-danger text-white p-1'>
                                                <?php echo $validation->listErrors(); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php $message = session()->getFlashdata('message'); ?>
                                    <?php if ($message ?? "") : ?>
                                        <div class="row">
                                            <div class='my-3 bg-success text-white p-2'>
                                                <?php echo $message ?? ""; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php echo form_open("broadcast", ["id" => "broadcast", "method" => "GET"]) ?>
                                    <div class="form-group">
                                        <label for="message" class="text-secondary mb-3"> Broadcast message
                                            to <?php echo $customerCount ?? 0 ?> customers</label>
                                        <?php echo form_textarea("message", "Hello, from {{company}}", ["class" => "form-control"]) ?>
                                        <?php echo view("partials/legend") ?>
                                    </div>

                                    <?php echo form_close() ?>
                                </div>
                                <div class="broadcast-controls-container">
                                    <div class="broadcast-controls flex-column align-items-end gap-1"
                                         style="display: flex;">
                                        <button type="submit" form="broadcast" name="submit" class="btn btn-primary">
                                            Send Message
                                        </button>
                                        <small class="text-secondary mt-1">Make sure you have enough funds</small>
                                    </div>

                                    <div class="broadcast-progress my-5 flex-column gap-1 hide" style="display: flex;">
                                        <div class="d-flex flex-row justify-content-between">
                                            <p class="progress-text">Sending message to <span></span></p>
                                            <button type="button" class="btn btn-primary">Stop</button>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"
                                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
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

<!-- Custom js for this page-->
<script src="<?php echo assets_url("js/messenger.js") ?>"></script>
<script src="<?php echo assets_url("js/broadcast.js") ?>"></script>
<!-- End custom js for this page-->

<script src="<?php echo assets_url("js/jquery.cookie.js") ?>" type="text/javascript"></script>

</body>

</html>
