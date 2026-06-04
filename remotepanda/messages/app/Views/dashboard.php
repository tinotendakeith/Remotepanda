<?php echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Dashboard"]) ?>

</head>
<body>

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
                                <h2>Welcome back,</h2>
                                <p class="mb-md-0">Birthday message notifier dashboard.</p>
                            </div>
                            <div class="d-flex">
                                <i class="mdi mdi-home text-muted hover-cursor"></i>
                                <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Dashboard&nbsp;/&nbsp;</p>
                                <p class="text-primary mb-0 hover-cursor">Analytics</p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-end flex-wrap">

                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body dashboard-tabs p-0">
                            <ul class="nav nav-tabs px-4" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview"
                                       role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                                </li>
                            </ul>
                            <div class="tab-content py-0 px-0">
                                <div class="tab-pane fade show active" id="overview" role="tabpanel"
                                     aria-labelledby="overview-tab">
                                    <div class="d-flex flex-wrap justify-content-md-between">
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-calendar-minus me-3 icon-lg text-success"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Last 7 Days Birthdays</small>
                                                <h5 class="me-2 mb-0"><?php echo count($recentBirthdays ?? []) ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-calendar-plus me-3 icon-lg text-success"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Next 7 Days Birthdays</small>
                                                <h5 class="me-2 mb-0"><?php echo count($upcomingBirthdays ?? []) ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-calendar-today me-3 icon-lg text-success"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Today's Birthdays</small>
                                                <h5 class="me-2 mb-0"><?php echo count($currentBirthdays ?? []) ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-send me-3 icon-lg text-success"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Sent Messages</small>
                                                <h5 class="me-2 mb-0"><?php echo $sentMessages ?? 0 ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-flag me-3 icon-lg text-warning"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Failed Messages</small>
                                                <h5 class="me-2 mb-0"><?php echo $failedMessages ?? 0 ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-account me-3 icon-lg text-danger"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Customer Count</small>
                                                <h5 class="me-2 mb-0"><?php echo $customerCount ?? 0 ?></h5>
                                            </div>
                                        </div>
                                        <div class="d-flex py-3 border-md-right flex-grow-1 align-items-center justify-content-center p-3 item">
                                            <i class="mdi mdi-send-lock me-3 icon-lg text-danger"></i>
                                            <div class="d-flex flex-column justify-content-around">
                                                <small class="mb-1 text-muted">Unsubscribed</small>
                                                <h5 class="me-2 mb-0"><?php echo $unsubscribedCount ?? 0 ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-title">Sent Messages</p>
                            <p class="mb-4">Sent messages summary</p>
                            <div id="history-chart-legend" class="d-flex justify-content-center pt-3"></div>
                            <canvas id="history-chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-title">Today's Birthdays (<?php echo count($currentBirthdays ?? []) ?>)</p>
                            <small class="card-subtitle">Birthdays being celebrated today</small>
                            <?php if (count($currentBirthdays ?? []) === 0): ?>
                                <?php echo view("partials/empty-list", ["message" => "No Birthdays Today"]) ?>
                            <?php else: ?>
                                <?php echo view("partials/dashboard/birthdays", ["customers" => $currentBirthdays ?? []]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-title">Recent Birthdays (<?php echo count($recentBirthdays ?? []) ?>)</p>
                            <small class="card-subtitle">Birthdays in the recent 1 week</small>
                            <?php if (count($recentBirthdays ?? []) === 0): ?>
                                <?php echo view("partials/empty-list", ["message" => "No recent birthdays in the last 1 week"]) ?>
                            <?php else: ?>
                                <?php echo view("partials/dashboard/birthdays", ["customers" => $recentBirthdays ?? []]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-title">Upcoming Birthdays (<?php echo count($upcomingBirthdays ?? []) ?>)</p>
                            <small class="card-subtitle">Birthdays in the upcoming 1 week</small>
                            <?php if (count($upcomingBirthdays ?? []) === 0): ?>
                                <?php echo view("partials/empty-list", ["message" => "No upcoming birthdays in the next 1 week"]) ?>
                            <?php else: ?>
                                <?php echo view("partials/dashboard/birthdays", ["customers" => $upcomingBirthdays ?? []]) ?>
                            <?php endif; ?>
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

<!-- Plugin js for this page-->
<script src="<?php echo assets_url("vendors/chart.js/Chart.min.js") ?>"></script>
<!-- End plugin js for this page-->

<!-- Custom js for this page-->
<script src="<?php echo assets_url("js/luxon.min.js") ?>"></script>
<script src="<?php echo assets_url("js/lodash.js") ?>"></script>
<script src="<?php echo assets_url("js/dashboard.js") ?>"></script>
<!-- End custom js for this page-->

<script src="<?php echo assets_url("js/jquery.cookie.js") ?>" type="text/javascript"></script>
</body>

</html>

