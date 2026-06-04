<?php echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Manage Customers"]) ?>
</head>

<body>

<?php
/**
 * @var object $pager
 */
?>

<div class="container-scroller">

    <?php echo view("partials/top-bar") ?>

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
                                    <h2>Manage Customers,</h2>
                                    <p class="mb-md-0">System Customers</p>
                                </div>
                                <div class="d-flex">
                                    <i class="mdi mdi-account-multiple text-muted hover-cursor"></i>
                                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Home&nbsp;/&nbsp;</p>
                                    <p class="text-primary mb-0 hover-cursor">Customers</p>
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
                                <h4 class="card-title">System Customers</h4>
                                <p class="card-description">
                                    All customers in the system
                                </p>
                                <?php echo $pager->links(); ?>
                                <div class="table-responsive">
                                    <style>
                                        table td:last-child, table td:first-child, table td:nth-child(2) {
                                            width: 1%;
                                        }
                                    </style>
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>
                                                &nbsp;
                                            </th>
                                            <th>
                                                #
                                            </th>
                                            <th>
                                                Name
                                            </th>
                                            <th>
                                                Number
                                            </th>
                                            <th>
                                                Birthday
                                            </th>
                                            <th>
                                                Member Since
                                            </th>
                                            <th>
                                                &nbsp;
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($customers ?? [] as $customer) : ?>
                                            <tr>
                                                <td>
                                                    <?php echo img_initials("$customer->name") ?>
                                                </td>
                                                <td>
                                                    <?php echo $customer->id ?>
                                                </td>
                                                <td>
                                                    <?php echo $customer->name ?>
                                                </td>
                                                <td>
                                                    <p><?php echo $customer->mobileNumber ?></p>
                                                    <?php $normalised = normaliseNumber($customer->mobileNumber) ?>
                                                    <?php if ($normalised): ?>
                                                        <small>(<?php echo $normalised ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $customer->dateOfBirth->toLocalizedString('MMM d'); ?>
                                                </td>
                                                <td>
                                                    <?php echo $customer->created_at->toLocalizedString('MMM d, yyyy'); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                                class="btn btn-outline-secondary dropdown-toggle"
                                                                data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                    class="mdi mdi-menu"></i></button>
                                                        <div class="dropdown-menu">
                                                            <button type="button" class="dropdown-item btn-send"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modalSendMessage"
                                                                    data-customer="<?php echo $customer->id ?>"
                                                                    data-customer-name="<?php echo $customer->name ?>"
                                                                    data-message="<?php echo $message ?? "" ?>">
                                                                Send Message
                                                            </button>
                                                            <button type="button" class="dropdown-item btn-method"
                                                               data-customer="<?php echo $customer->id ?>">
                                                                <?php echo ucfirst($customer->method ?? METHOD_MESSAGE) ?>
                                                            </button>
                                                            <button type="button" class="dropdown-item btn-subscribe"
                                                               data-customer="<?php echo $customer->id ?>">
                                                                <?php echo in_array($customer->subscribed, ["true", "1"]) ? "Subscribed" : "UnSubscribed" ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                            </tr>
                                        <?php endforeach; ?>

                                        </tbody>
                                    </table>
                                </div>
                                <?php echo $pager->links(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- content-wrapper ends -->

            <?php echo view("partials/send-message") ?>

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
<script src="<?php echo assets_url("js/customers.js") ?>"></script>
<!-- End custom js for this page-->

<script src="<?php echo assets_url("js/jquery.cookie.js") ?>" type="text/javascript"></script>

</body>

</html>
