<?php
include_once 'resource/Database.php';
include_once 'resource/utilities.php';
include_once 'resource/send-email.php';

if(isset($_POST['deleteAccountBtn'], $_POST['token'])){

    if(validate_token($_POST['token'])){
        //process the form
        $id = $_POST['hidden_id'];

        try{
            //STEP 1: Retrieve user information from the database
            $sqlQuery = "SELECT * FROM users WHERE id =:id";
            $statement = $db->prepare($sqlQuery);
            $statement->execute(array(':id' => $id));

            if ($row = $statement->fetch()){
                //STEP 2 deactivate the account
                $username = $row['username'];
                $email = $row['email'];
                $user_id = $row['id'];

                $deactivateQuery = $db->prepare("UPDATE users SET activated = :activated WHERE id = :id");
                $deactivateQuery->execute(array(':activated' => 0, ':id' => $user_id));

                if ($deactivateQuery->rowCount() === 1){
                    //STEP 3 Insert record into the deleted users table (trash)
                    $insertRecord = $db->prepare("INSERT INTO trash(user_id, deleted_at)
                                                   VALUES(:id, now())");

                    $insertRecord->execute(array(':id' => $user_id));

                    if ($insertRecord->rowCount() === 1){
                        //STEP 4 notification the user via email and display confirmation alert
                        //prepare email body
                        $mail_body = '<html>
                        <body style="background-color:#CCCCCC; color:#000; font-family: Arial, 
                            Helvetica, sans-serif; line-height:1.8em;">
                            <h2>User Authentication: Code A Secured Login System</h2>
                            <p>Dear '.$username.'<br><br>You have requested to deactivate your account,
                            your account information will be kept for 14 days, 
                            if you wish to continue using this system login within the next 14 days 
                            to reactivate your account or it will be permanently deleted.</p>
                            <p><a href="http://auth.dev/login.php">Sign In</a></p>
                            <p><strong>&copy;2016 ICT DesighHUB</strong></p>
                        </body>
                        </html>';

                        $mail->addAddress($email, $username);
                        $mail->Subject = "Message from ICT DesignHUB: Account Deactivated";
                        $mail->Body = $mail_body;

                        //Error Handling for PHPMailer
                        if(!$mail->Send()){
                            $result = "<script type=\"text/javascript\">
                            swal(\"Error\",\" Email sending failed: $mail->ErrorInfo \",\"error\");</script>";
                        }
                        else{
                            $result = "<script type=\"text/javascript\">
                            swal({
                            title: \"Dear $username!\",
                            text: \"Your account Information will be kept for 14 days, if you wish to continue using this system login within 14 days to reactivate your account or it will be permanently deleted.\",
                            type: 'success',
                            confirmButtonText: \"Thank You!\" });
                            setTimeout(function(){
                               window.location.href = 'logout.php';
                            }, 5000);
                            </script>";


                        }
                    }else{
                        $result = flashMessage("Couldn't complete the operation please try again");
                    }
                }else{
                    $result = flashMessage("Couldn't complete the operation please try again");
                }

            }else{
                //something fishing delete cookies and sessions
                signout();
            }


            //STEP 3 Insert record into the deleted users table
            //STEP 4 send notification to the user via email and display confirmation alert
        }catch (PDOException $ex){
            $result = flashMessage("An error occurred: " .$ex->getMessage());
        }

    }else{
        //display error
        $result = "<script type='text/javascript'>
                      swal('Error','This request originates from an unknown source, posible attack'
                      ,'error');
                  </script>";
    }

}