<?php
if (isset($_POST['name'])) {$name = $_POST['name'];}
if (isset($_POST['phone'])) {$phone = $_POST['phone'];}
if (isset($_POST['email'])) {$email = $_POST['email'];}

//проверка на дубль телефона
$oldPhone = file_get_contents('end_phone', htmlspecialchars($phone));
if($oldPhone != $phone)
{

$to = "sales@@def.ii"; /*Укажите ваш адрес электоронной почты*/
$headers = "Content-type: text/plain; charset = utf-8"."\r\n".           "From:sales@def.ii";
$subject = "Заявка с вашего сайта ";
$message = "Имя пославшего: ".$name."\nТелефон: ".$phone."\nEmail: ".$email."";
$send = mail ($to, $subject, $message, $headers);
include 'toamo.php';
file_put_contents('end_phone', htmlspecialchars($phone));
}	
header('Location: /thanks.html');
?>