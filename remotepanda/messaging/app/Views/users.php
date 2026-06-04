<?php echo doctype(); ?>
<html lang="en">

<head>
    <?php echo view("partials/head", ["title" => "Manage Users"]) ?>
</head>

<body>

<?php
/**
 * @var object $pager
 * @var object $validation
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
                                    <h2>Manage Users,</h2>
                                    <p class="mb-md-0">System Administrators</p>
                                </div>
                                <div class="d-flex">
                                    <i class="mdi mdi-account text-muted hover-cursor"></i>
                                    <p class="text-muted mb-0 hover-cursor">&nbsp;/&nbsp;Home&nbsp;/&nbsp;</p>
                                    <p class="text-primary mb-0 hover-cursor">Users</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-end flex-wrap">
                                <button type="button" class="btn btn-primary btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditUser"
                                        data-user="<?php echo DEFAULT_ID ?>">
                                    Add User
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($validation->listErrors()) :?>
                    <div class="row">
                        <div class='my-3 bg-danger text-white p-1'>
                            <?php echo $validation->listErrors(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">

                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">System Users</h4>
                                <p class="card-description">
                                    All users in the system
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
                                                Login
                                            </th>
                                            <th>
                                                Email
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
                                        <?php foreach ($users ?? [] as $user) : ?>
                                            <tr class="row-<?php echo $user->id ?>">
                                                <td>
                                                    <?php echo img_initials("$user->name") ?>
                                                </td>
                                                <td>
                                                    <?php echo $user->id ?>
                                                </td>
                                                <td>
                                                    <?php echo $user->name ?>
                                                </td>
                                                <td>
                                                    <?php echo $user->login ?>
                                                </td>
                                                <td>
                                                    <?php echo $user->email ?>
                                                </td>
                                                <td>
                                                    <?php echo $user->created_at->toLocalizedString('MMM d, yyyy'); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button"
                                                                class="btn btn-outline-secondary dropdown-toggle"
                                                                data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                    class="mdi mdi-menu"></i></button>
                                                        <div class="dropdown-menu">
                                                            <button type="button" class="dropdown-item btn-edit"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modalEditUser"
                                                                    data-user-name="<?php echo $user->name ?>"
                                                                    data-user-login="<?php echo $user->login ?>"
                                                                    data-user-email="<?php echo $user->email ?>"
                                                                    data-user="<?php echo $user->id ?>">
                                                                Edit
                                                            </button>
                                                            <?php if (count($users ?? []) > 1 && $user->id !== current_user_id()): ?>
                                                                <button type="button" class="dropdown-item btn-delete"
                                                                        data-user-name="<?php echo $user->name ?>"
                                                                        data-user="<?php echo $user->id ?>">
                                                                    Delete
                                                                </button>
                                                            <?php endif; ?>
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

            <?php echo view("partials/edit-user") ?>

            <?php echo view("partials/copyright") ?>

        </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<?php echo view("partials/footer") ?>

<!-- Custom js for this page-->
<script src="<?php echo assets_url("js/users.js") ?>"></script>
<!-- End custom js for this page-->

<script src="<?php echo assets_url("js/jquery.cookie.js") ?>" type="text/javascript"></script>

</body>

</html>
