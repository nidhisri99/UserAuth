<?php
require 'class.phpmailer.php';
$config = require __DIR__ .'/../config/app.php';

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = $config['mail']['transport'];
$mail->SMTPSecure = $config['mail']['encrption'];
$mail->Port = $config['mail']['port'];
$mail->Host = $config['mail']['host'];
$mail->IsHTML(true);

$mail->SMTPAuth = true;
$mail->Username = $config['mail']['username'];
$mail->Password = $config['mail']['password'];

//Sender Info
$mail->From = $config['mail']['from'];
$mail->FromName = $config['mail']['sender_name'];
