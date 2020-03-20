<?php
//add our database connection script
include_once 'resource/Database.php';
include_once 'resource/utilities.php';
include_once 'resource/send-email.php';

//process the form if the reset password button is clicked
if(isset($_POST['passwordResetBtn'], $_POST['token'])){

    if(validate_token($_POST['token'])){
        //process the form
        //initialize an array to store any error message from the form
        $form_errors = array();

        //Form validation
        $required_fields = array('email', 'reset_token', 'new_password', 'confirm_password');

        //call the function to check empty field and merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_empty_fields($required_fields));

        //Fields that requires checking for minimum length
        $fields_to_check_length = array('new_password' => 6, 'confirm_password' => 6);

        //call the function to check minimum required length and merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_min_length($fields_to_check_length));


        //check if error array is empty, if yes process form data
        if(empty($form_errors)){
            //collect form data and store in variables
            $email = $_POST['email'];
            $reset_token = $_POST['reset_token'];
            $password1 = $_POST['new_password'];
            $password2 = $_POST['confirm_password'];

            //check if new password and confirm password is same
            if($password1 != $password2){
                $result = flashMessage("New password and confirm password does not match");
            }else{
                try{
                    //validate email and token
                    $query = "SELECT * FROM password_resets WHERE email =:email";
                    $queryStatement = $db->prepare($query);
                    $queryStatement->execute([
                        ':email' => $email
                    ]);

                    $isValid = true;

                    if($rows = $queryStatement->fetch()) {
                        //email found
                        $stored_token = $rows['token'];
                        $expire_time = $rows['expire_time'];

                        if($stored_token !== $reset_token) {
                            $isValid = false;
                            $result = flashMessage("You have entered an invalid token");
                        }

                        if ($expire_time < date('Y-m-d H:i:s')) {
                            $isValid = false;
                            $result = flashMessage("This reset token has expired, request a new one");
                            //delete token
                            $db->exec("DELETE FROM password_resets
                                      WHERE email ='$email' AND token = '$stored_token'");
                        }
                    }else{
                        $isValid = false;
                        goto invalid_email;
                    }

                    //if token verification pass
                    if($isValid){
                        //create SQL select statement to verify if email address input exist in the database
                        $sqlQuery = "SELECT id FROM users WHERE email =:email";

                        //use PDO prepared to sanitize data
                        $statement = $db->prepare($sqlQuery);

                        //execute the query
                        $statement->execute(array(':email' => $email));

                        //check if record exist
                        if($rs = $statement->fetch()){
                            //hash the password
                            $hashed_password = password_hash($password1, PASSWORD_DEFAULT);
                            $id = $rs['id'];
                            //SQL statement to update password
                            $sqlUpdate = "UPDATE users SET password =:password WHERE id=:id";

                            //use PDO prepared to sanitize SQL statement
                            $statement = $db->prepare($sqlUpdate);

                            //execute the statement
                            $statement->execute(array(':password' => $hashed_password, ':id' => $id));

                            if ($statement->rowCount() == 1) {
                                //delete token
                                $db->exec("DELETE FROM password_resets
                                      WHERE email = '$email' AND token = '$stored_token'");
                            }

                            $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"Updated!\",
                            text: \"Password Reset Successful.\",
                            type: 'success',
                            confirmButtonText: \"Thank You!\" });
                        </script>";
                        }
                        else{
                            invalid_email:
                            $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"OOPS!!\",
                            text: \"The email address provided does not exist in our database, please try again.\",
                            type: 'error',
                            confirmButtonText: \"Ok!\" });
                        </script>";
                        }
                    }

                }catch (PDOException $ex){
                    $result = flashMessage("An error occurred: " .$ex->getMessage());
                }
            }
        }
        else{
            if(count($form_errors) == 1){
                $result = flashMessage("There was 1 error in the form<br>");
            }else{
                $result = flashMessage("There were " .count($form_errors). " errors in the form <br>");
            }
        }
    }else{
        $result = "<script type='text/javascript'>
                      swal('Error','This request originates from an unknown source, posible attack'
                      ,'error');
                      </script>";
    }

}else if(isset($_POST['passwordRecoveryBtn'], $_POST['token'])){

    if(validate_token($_POST['token'])){

        //process the form
        //initialize an array to store any error message from the form
        $form_errors = array();

        //Form validation
        $required_fields = array('email');

        //call the function to check empty field and merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_empty_fields($required_fields));

        //email validation / merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_email($_POST));

        //check if error array is empty, if yes process form data
        if(empty($form_errors)){
            //collect form data and store in variables
            $email = $_POST['email'];

            try{
                //create SQL select statement to verify if email address input exist in the database
                $sqlQuery = "SELECT * FROM users WHERE email =:email";

                //use PDO prepared to sanitize data
                $statement = $db->prepare($sqlQuery);

                //execute the query
                $statement->execute(array(':email' => $email));

                //check if record exist
                if($rs = $statement->fetch()){
                    $username = $rs['username'];
                    $email = $rs['email'];

                    //create and store token
                    $expire_time = date('Y-m-d H:i:s', strtotime('1 hour'));
                    $random_string = base64_encode(openssl_random_pseudo_bytes(10));
                    $reset_token = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $random_string));

                    $insertToken = "INSERT INTO password_resets (email, token, expire_time)
                                    VALUES (:email, :token, :expire_time)";
                    $token_statement = $db->prepare($insertToken);
                    $token_statement->execute([
                        ':email' => $email, ':token' => $reset_token, ':expire_time' => $expire_time
                    ]);

                    //prepare email body
                    $mail_body = '<html>
                        <body style="background-color:#CCCCCC; color:#000; font-family: Arial, Helvetica, sans-serif;
                                            line-height:1.8em;">
                        <h2>User Authentication: Code A Secured Login System</h2>
                        <p>Dear '.$username.'<br><br> To reset your login password, copy the token below and 
                        click on the Reset Password link then paste the token in the token field on the form:
                        <br /><br />
                        Token: '.$reset_token.' <br />
                        This token will expire after 1 hour
                        </p>
                        <p><a href="http://auth.dev/forgot_password.php"> Reset Password</a></p>
                        <p><strong>&copy;'.date('Y').' DEVSCREENCAST</strong></p>
                        </body>
                        </html>';

                    $mail->addAddress($email, $username);
                    $mail->Subject = "Password Recovery Message from DEVSCREENCAST";
                    $mail->Body = $mail_body;

                    //Error Handling for PHPMailer
                    if(!$mail->Send()){
                        $result = "<script type=\"text/javascript\">
                         swal(\"Error\",\" Email sending failed: $mail->ErrorInfo \",\"error\");</script>";
                    }else{
                        $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"Password Recovery!\",
                            text: \"Password Reset link sent successfully, please check your email address.\",
                            type: 'success',
                            confirmButtonText: \"Thank You!\" });
                        </script>";
                    }
                }
                else{
                    $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"OOPS!!\",
                            text: \"The email address provided does not exist in our database, please try again.\",
                            type: 'error',
                            confirmButtonText: \"Ok!\" });
                        </script>";
                }
            }catch (PDOException $ex){
                $result = flashMessage("An error occurred: " .$ex->getMessage());
            }

        }
        else{
            if(count($form_errors) == 1){
                $result = flashMessage("There was 1 error in the form<br>");
            }else{
                $result = flashMessage("There were " .count($form_errors). " errors in the form <br>");
            }
        }
    }else{
        $result = "<script type='text/javascript'>
                      swal('Error','This request originates from an unknown source, posible attack'
                      ,'error');
                      </script>";
    }

}

