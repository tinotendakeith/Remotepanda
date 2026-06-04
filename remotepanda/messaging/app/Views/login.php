<?php echo doctype(); ?>
<html lang="en">

<head>
<?php echo view("partials/head",["title"=>"Login"]) ?>
</head>

<body>

<?php
/**
 * @var object $validation
 */
?>

<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
            <div class="row w-100 mx-0">
                <div class="col-lg-4 mx-auto">
                    <div class="auth-form-light text-left py-5 px-4 px-sm-5">
                        <div class="brand-logo">
                            <img src="<?php echo assets_url("images/logo.png") ?>" alt="logo">
                        </div>
                        <h4>Hello! let's get started</h4>
                        <h6 class="font-weight-light">Sign in to continue.</h6>

                        <?php if ($validation->hasError('login')) :?>
                            <div class='bg-danger text-white p-1'>
                                <?php echo $validation->listErrors(); ?>
                            </div>
                        <?php endif; ?>

                        <?php echo form_open("login", ["class"=>"pt-3"]) ?>
                            <div class="form-group">
                                <input type="email" class="form-control form-control-lg" name="login" placeholder="Username">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control form-control-lg" name="password" placeholder="Password">
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" name="submit">SIGN IN</button>
                            </div>
                            <div class="my-2 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <label class="form-check-label text-muted">
                                        <input type="checkbox" name="stay-signed-in" class="form-check-input">
                                        Keep me signed in
                                    </label>
                                </div>
                            </div>
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->

<?php echo view("partials/footer") ?>

</body>

</html>
