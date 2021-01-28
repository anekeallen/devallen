<?php

/**
 * PHPMailer simple contact form example.
 * If you want to accept and send uploads in your form, look at the send_file_upload example.
 */

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;

// Alias the League Google OAuth2 provider class
use League\OAuth2\Client\Provider\Google;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');


require '../autoload.php';

$template = file_get_contents('form-contato.tpl');
$template = str_replace(
    array("<!-- #{SiteName} -->"),
    array( $_SERVER['SERVER_NAME']),
    $template);



$err = false;
$msg = '';
$email = '';
    //Apply some basic validation and filtering to the subject
if (array_key_exists('subject', $_POST)) {
    $subject = substr(strip_tags($_POST['subject']), 0, 255);
    $template = str_replace(
        array("<!-- #{Subject} -->", "<!-- #{Assunto} -->"),
        array("Assunto:", $_POST['subject']),
        $template); 

    
} else {
    $subject = 'Nenhum assunto';
}
    //Apply some basic validation and filtering to the query
if (array_key_exists('message', $_POST)) {
        //Limit length and strip HTML tags
    $query = substr(strip_tags($_POST['message']), 0, 16384);
    $template = str_replace(
        array("<!-- #{MessageState} -->", "<!-- #{MessageDescription} -->"),
        array("Messagem:", $_POST['message']),
        $template);
} else {
    $query = '';
    $msg = 'No query provided!';
    $err = true;
}
    //Apply some basic validation and filtering to the name

if (array_key_exists('name', $_POST)) {
        //Limit length and strip HTML tags
    $name = substr(strip_tags($_POST['name']), 0, 255);
} else {
    $name = '';
}
    //Validate to address
    //Never allow arbitrary input for the 'to' address as it will turn your form into a spam gateway!
    //Substitute appropriate addresses from your own domain, or simply use a single, fixed address
if (array_key_exists('to', $_POST) && in_array($_POST['to'], ['sales', 'support', 'accounts'], true)) {
    $to = $_POST['to'] . '@example.com';
} else {
    $to = 'anekeapj@gmail.com';
}
    //Make sure the address they provided is valid before trying to use it
if (array_key_exists('email', $_POST) && PHPMailer::validateAddress($_POST['email'])) {
    $email = $_POST['email'];
    $template = str_replace(
        array("<!-- #{FromState} -->", "<!-- #{FromEmail} -->"),
        array("Email:", $_POST['email']),
        $template);
} else {
    $msg .= 'Error: invalid email address provided';
    $err = true;
}




if (!$err) {
    $mail = new PHPMailer();
    $mail->isSMTP();
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->SMTPAuth = true;

    //Set AuthType to use XOAUTH2
    $mail->AuthType = 'XOAUTH2';
    //Fill in authentication details here
//Either the gmail account owner, or the user that gave consent
    $email_user = 'anekeapj@gmail.com';
    $clientId = '647956545146-h3l5l46uve0pdtjl37accrcnik8ltgr5.apps.googleusercontent.com';
    $clientSecret = 'xJ3aF7Qo3mhchvTIRux2yjYj';

    //Obtained by configuring and running get_oauth_token.php
//after setting up an app in Google Developer Console.
    $refreshToken = '1//0h8080oFQ266eCgYIARAAGBESNgF-L9IrxXQiy6nZi8oxIX1UA9QI4ovDIPoNs3caO_EeOoVzwVtMQnMtIxenE_8iakQQjK_q8Q';

    //Create a new OAuth2 provider instance
    $provider = new Google(
        [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ]
    );

    //Pass the OAuth provider instance to PHPMailer
    $mail->setOAuth(
        new OAuth(
            [
                'provider' => $provider,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'refreshToken' => $refreshToken,
                'userName' => $email_user,
            ]
        )
    );
    
    $mail->setFrom($email, (empty($name) ? 'Contact form' : $name));
    $mail->addAddress($to);

    if (isset($_POST['name'])){
        $mail->FromName = $_POST['name'];
    }else{
        $mail->FromName = "Visitante do Site";
    }

    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Subject = $subject;


//Set the subject line
    


    $mail->MsgHTML($template);


//send the message, check for errors
    if (!$mail->send()) {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        echo 'OK';

    }



}



//Section 2: IMAP
//IMAP commands requires the PHP IMAP Extension, found at: https://php.net/manual/en/imap.setup.php
//Function to call which uses the PHP imap_*() functions to save messages: https://php.net/manual/en/book.imap.php
//You can use imap_getmailboxes($imapStream, '/imap/ssl', '*' ) to get a list of available folders or labels, this can
//be useful if you are trying to get this working on a non-Gmail IMAP server.
function save_mail($mail)
{
    //You can change 'Sent Mail' to any other folder or tag
    $path = '{imap.gmail.com:993/imap/ssl}[Gmail]/Sent Mail';

    //Tell your server to open an IMAP connection using the same username and password as you used for SMTP
    $imapStream = imap_open($path, $mail->Username, $mail->Password);

    $result = imap_append($imapStream, $path, $mail->getSentMIMEMessage());
    imap_close($imapStream);

    return $result;
} ?>