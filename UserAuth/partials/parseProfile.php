<?php
include_once 'resource/Database.php';
include_once 'resource/utilities.php';

if(isset($_GET['u'])){
    $username = $_GET['u'];

    $sqlQuery = "SELECT * FROM users WHERE username =:username";
    $statement = $db->prepare($sqlQuery);
    $statement->execute(array(':username' => $username));

    while($rs = $statement->fetch()){
        $username = $rs['username'];
        $profile_picture = $rs['avatar'];
        $date_joined =  strftime("%b %d, %Y", strtotime($rs["join_date"]));

        $rs['activated'] = 1 ? $status = "Activated" : $status = "Not Activated";
    }
}
else if((isset($_SESSION['id']) || isset($_GET['user_identity'])) && !isset($_POST['updateProfileBtn'])){
    if(isset($_GET['user_identity'])){
        $url_encoded_id = $_GET['user_identity'];
        $decode_id = base64_decode($url_encoded_id);
        $user_id_array = explode("encodeuserid", $decode_id);
        $id = $user_id_array[1];
    }else{
        $id = $_SESSION['id'];
    }

    $sqlQuery = "SELECT * FROM users WHERE id = :id";
    $statement = $db->prepare($sqlQuery);
    $statement->execute(array(':id' => $id));

    while($rs = $statement->fetch()){
        $username = $rs['username'];
        $email = $rs['email'];
        $profile_picture = $rs['avatar'];
        $date_joined =  strftime("%b %d, %Y", strtotime($rs["join_date"]));
    }

    $encode_id = base64_encode("encodeuserid{$id}");

}
else if(isset($_POST['updateProfileBtn'], $_POST['token'])){

        if(validate_token($_POST['token'])){
            //process the form
            //initialize an array to store any error message from the form
            $form_errors = array();

            //Form validation
            $required_fields = array('email', 'username');

            //call the function to check empty field and merge the return data into form_error array
            $form_errors = array_merge($form_errors, check_empty_fields($required_fields));

            //Fields that requires checking for minimum length
            $fields_to_check_length = array('username' => 4);

            //call the function to check minimum required length and merge the return data into form_error array
            $form_errors = array_merge($form_errors, check_min_length($fields_to_check_length));

            //email validation / merge the return data into form_error array
            $form_errors = array_merge($form_errors, check_email($_POST));

            //validate if file has a valid extension
            isset($_FILES['avatar']['name']) ? $avatar = $_FILES['avatar']['name'] : $avatar = null;

            if($avatar != null){
                $form_errors = array_merge($form_errors, isValidImage($avatar));
            }

            //collect form data and store in variables
            $email = $_POST['email'];
            $username = $_POST['username'];
            $hidden_id = $_POST['hidden_id'];

            $result = false;
            $emailExistForAnotherUser = $db->query("SELECT email FROM users 
            WHERE email = '$email' AND id <> '$hidden_id'");

            if($emailExistForAnotherUser->fetch()){
                $result = flashMessage("Email is already used by another user");
            }

            if(empty($form_errors) && !$result){
                try{
                    $query = "SELECT avatar FROM users WHERE id =:id";
                    $oldAvatarStatement = $db->prepare($query);
                    $oldAvatarStatement->execute([':id' => $hidden_id]);

                    if($rs = $oldAvatarStatement->fetch()) {
                        $oldAvatar = $rs['avatar'];
                    }
                    //create SQL update statement
                    $sqlUpdate = "UPDATE users SET username =:username, email =:email WHERE id =:id";

                    //use PDO prepared to sanitize data
                    $statement = $db->prepare($sqlUpdate);

                    if($avatar != null) {
                        //create SQL update statement
                        $sqlUpdate = "UPDATE users SET username =:username, email =:email, avatar = :avatar WHERE id =:id";

                        $avatar_path = uploadAvatar($username);
                        if(!$avatar_path){
                            $avatar_path = "uploads/default.jpg";
                        }
                        //use PDO prepared to sanitize data
                        $statement = $db->prepare($sqlUpdate);
                        //update the record in the database
                        $statement->execute(array(':username' => $username, ':email' => $email,
                            'avatar' => $avatar_path, ':id' => $hidden_id));

                        if(isset($oldAvatar)) {
                            unlink($oldAvatar);
                        }

                    }else{
                        //update the record in the database
                        $statement->execute(array(':username' => $username, ':email' => $email, ':id' => $hidden_id));
                    }
                    //check if one new row was created
                    if($statement->rowCount() == 1){
                        $result = "<script type=\"text/javascript\">
                swal({title:\"Updated!\", text:\"Profile Update Successfully.\", type:\"success\"}, 
                    function() {
                        window.location.replace(window.location.href);
                    });
                </script>";
                    }else{
                        $result = "<script type=\"text/javascript\">
                swal({title:\"Nothing Happened\", text:\"You have not made any changes.\"}, 
                function() {
                    window.location.replace(window.location.href);
                }
                );</script>";
                    }

                }catch (PDOException $ex){
                    $result = flashMessage("An error occurred in : " .$ex->getMessage());
                }
            }
            else{
                if(!$result){
                    if(count($form_errors) == 1){
                        $result = flashMessage("There was 1 error in the form<br>");
                    }else{
                        $result = flashMessage("There were " .count($form_errors). " errors in the form <br>");
                    }
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
