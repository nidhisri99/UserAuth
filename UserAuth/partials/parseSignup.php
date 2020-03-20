<?php
//add scripts
include_once 'resource/Database.php';
include_once 'resource/utilities.php';
include_once 'resource/send-email.php';

//process the form
if(isset($_POST['signupBtn'], $_POST['token'])){

    if(validate_token($_POST['token'])){
        //process the form
        //initialize an array to store any error message from the form
        $form_errors = array();

        //Form validation
        $required_fields = array('email', 'username', 'password');

        //call the function to check empty field and merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_empty_fields($required_fields));

        //Fields that requires checking for minimum length
        $fields_to_check_length = array('username' => 4, 'password' => 6);

        //call the function to check minimum required length and merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_min_length($fields_to_check_length));

        //email validation / merge the return data into form_error array
        $form_errors = array_merge($form_errors, check_email($_POST));

        //collect form data and store in variables
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        if(checkDuplicateEntries("users", "email", $email, $db)){
            $result = flashMessage("Email is already taken, please try another one");
        }
        else if(checkDuplicateEntries("users", "username", $username, $db)){
            $result = flashMessage("Username is already taken, please try another one");
        }
        //check if error array is empty, if yes process form data and insert record
        else if(empty($form_errors)){
            //hashing the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try{
                //create SQL insert statement
                $sqlInsert = "INSERT INTO users (username, email, password, join_date)
              VALUES (:username, :email, :password, now())";

                //use PDO prepared to sanitize data
                $statement = $db->prepare($sqlInsert);

                //add the data into the database
                $statement->execute(array(':username' => $username, ':email' => $email, ':password' => $hashed_password));
                //check if one new row was created
                if($statement->rowCount() == 1){

                    //get the last inserted ID
                    $user_id = $db->lastInsertId();
                    //encode the ID
                    $encode_id = base64_encode("encodeuserid{$user_id}");

                    //prepare email body
                    $mail_body = '<html>
                    <body style="background-color:#CCCCCC; color:#000; font-family: Arial, Helvetica, sans-serif;
                                        line-height:1.8em;">
                    <h2>User Authentication: Code A Secured Login System</h2>
                    <p>Dear '.$username.'<br><br>Thank you for registering, please click on the link below to
                        confirm your email address</p>
                    <p><a href="http://auth.dev/activate.php?id='.$encode_id.'"> Confirm Email</a></p>
                    <p><strong>&copy;2016 ICT DesighHUB</strong></p>
                    </body>
                    </html>';

                    $mail->addAddress($email, $username);
                    $mail->Subject = "Message from ICT DesignHUB";
                    $mail->Body = $mail_body;

                    //Error Handling for PHPMailer
                    if(!$mail->Send()){
                        $result = "<script type=\"text/javascript\">
                    swal(\"Error\",\" Email sending failed: $mail->ErrorInfo \",\"error\");</script>";
                    }
                    else{
                        $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"Congratulations $username!\",
                            text: \"Registration Completed Successfully. Please check your email for confirmation link\",
                            type: 'success',
                            confirmButtonText: \"Thank You!\" });
                        </script>";
                    }
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
        //display error
        $result = "<script type='text/javascript'>
                      swal('Error','This request originates from an unknown source, posible attack'
                      ,'error');
                      </script>";
    }

}
//activation
else if(isset($_GET['id'])) {
    $encoded_id = $_GET['id'];
    $decode_id = base64_decode($encoded_id);
    $user_id_array = explode("encodeuserid", $decode_id);
    $id = $user_id_array[1];

    $sql = "UPDATE users SET activated =:activated WHERE id=:id AND activated='0'";

    $statement = $db->prepare($sql);
    $statement->execute(array(':activated' => "1", ':id' => $id));

    if ($statement->rowCount() == 1) {
        $result = '<h2>Email Confirmed </h2>
        <p>Your email address has been verified, you can now <a href="login.php">login</a> with your email and password.</p>';
    } else {
        $result = "<p class='lead'>No changes made please contact site admin,
    if you have not confirmed your email before</p>";
    }
}