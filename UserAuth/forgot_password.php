<?php
$page_title = "User Authentication - Password Reset";
include_once 'partials/headers.php';
include_once 'partials/parsePasswordReset.php';
?>

<div class="container">
    <section class="col col-lg-7">
        <h2>Password Reset Form</h2><hr>

        <div>
            <?php if(isset($result)) echo $result; ?>
            <?php if(!empty($form_errors)) echo show_errors($form_errors); ?>
        </div>
        <div class="clearfix"></div>
        <form action="" method="post">
            <div class="form-group">
                <label for="emailField">Email</label>
                <input type="text" name="email" class="form-control" id="emailField" placeholder="Your Email Address">
            </div>

            <div class="form-group">
                <label for="tokenField">Token</label>
                <input type="text" name="reset_token" class="form-control" id="tokenField" placeholder="Reset Token">
            </div>

            <div class="form-group">
                <label for="passwordField">New Password</label>
                <input type="password" name="new_password" class="form-control" id="passwordField" placeholder="New Password">
            </div>

            <div class="form-group">
                <label for="passwordField">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" id="passwordField" placeholder="Confirm Password">
            </div>
            <input type="hidden" name="token" value="<?php if(function_exists('_token')) echo _token(); ?>">
            <button type="submit" name="passwordResetBtn" class="btn btn-primary pull-right">Reset Password</button>
        </form>
    </section>
    <p><a href="index.php">Back</a> </p>
</div>

<?php include_once 'partials/footers.php'; ?>
</body>
</html>