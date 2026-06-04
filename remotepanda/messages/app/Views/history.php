<?php use CodeIgniter\I18n\Time;

echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Manage Users"]) ?>
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
                                    <h2>Manage History,</h2>
                                    <p class="mb-md-0">System Messaging History</p>
                                </div>
                                <div class="d-flex">
                                    <i class="mdi mdi-home text-muted hover-cursor"></i>
                                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Home&nbsp;/&nbsp;</p>
                                    <p class="text-primary mb-0 hover-cursor">History</p>
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
                                <h4 class="card-title">History</h4>
                                <p class="card-description">
                                    Message send history
                                </p>
                                <?php if (count($history ?? []) === 0): ?>
                                    <?php echo view("partials/empty-list", ["message" => "No Messages sent yet"]) ?>
                                <?php else: ?>
                                    <?php echo $pager->links(); ?>
                                    <div class="table-responsive">
                                        <style>
                                            table td:last-child, table td:first-child, table td:nth-child(2) {
                                                width: 1%;
                                            }
                                        </style>
                                        <table class="table table-striped">
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
                                                    Sent
                                                </th>
                                                <th>
                                                    Status
                                                </th>
                                                <th>
                                                    Message
                                                </th>
                                                <th>
                                                    &nbsp;
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($history ?? [] as $historyItem) : ?>
                                                <tr>
                                                    <td>
                                                        <?php echo img_initials("$historyItem->name") ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $historyItem->id ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $historyItem->name ?>
                                                    </td>
                                                    <td>
                                                        <p><?php echo $historyItem->mobileNumber ?></p>
                                                        <?php if ($historyItem->send_number): ?>
                                                            <small>(<?php echo $historyItem->send_number ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo Time::parse($historyItem->sent_at)->toLocalizedString('MMM d, yyyy'); ?>
                                                    </td>
                                                    <td title="<?php echo $historyItem->send_status ?? "Success"; ?>">
                                                        <?php echo ellipsize($historyItem->send_status ?? "Success", 20); ?>
                                                    </td>
                                                    <td title="<?php echo $historyItem->message_sent ?? "Null"; ?>">
                                                        <small class="text-break">
                                                            <?php echo ellipsize($historyItem->message_sent ?? "Null", 50); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group w-100">
                                                            <button type="button"
                                                                    class="btn-send btn btn-outline-primary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modalSendMessage"
                                                                    data-message="<?php echo $historyItem->message_sent ?? "" ?>"
                                                                    data-customer="<?php echo $historyItem->id ?>"
                                                                    data-customer-name="<?php echo $historyItem->name ?>">
                                                                Resend Message
                                                            </button>
                                                        </div>
                                                </tr>
                                            <?php endforeach; ?>

                                            </tbody>
                                        </table>
                                    </div>
                                    <?php echo $pager->links(); ?>
                                <?php endif; ?>
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
<!-- End custom js for this page-->

<script src="<?php echo assets_url("js/jquery.cookie.js") ?>" type="text/javascript"></script>

</body>

</html>
