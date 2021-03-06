<?php
// Init
error_reporting(NULL);
ob_start();
session_start();
$TAB = 'USER';
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

if (empty($_SESSION['user'])) {
    header("Location: /login/");
}

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

// Are you admin?
if ($_SESSION['user'] == 'admin') {
    if (!empty($_POST['ok'])) {
        // Check input
        if (empty($_POST['v_username'])) $errors[] = 'user';
        if (empty($_POST['v_password'])) $errors[] = 'password';
        if (empty($_POST['v_package'])) $errrors[] = 'package';
        if (empty($_POST['v_email'])) $errors[] = 'email';
        if (empty($_POST['v_fname'])) $errors[] = 'first name';
        if (empty($_POST['v_lname'])) $errors[] = 'last name';

        // Protect input
        $v_username = escapeshellarg($_POST['v_username']);
        $v_password = escapeshellarg($_POST['v_password']);
        $v_package = escapeshellarg($_POST['v_package']);
        $v_email = escapeshellarg($_POST['v_email']);
        $v_fname = escapeshellarg($_POST['v_fname']);
        $v_lname = escapeshellarg($_POST['v_lname']);
        if (empty($_POST['v_notify'])) $v_notify = 'off';

        // Validate email
        if (!filter_var($_POST['v_email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_msg'] = 'Please enter valid email address.';
        }

        // Check for errors
        if (!empty($errors[0])) {
            foreach ($errors as $i => $error) {
                if ( $i == 0 ) {
                    $error_msg = $error;
                } else {
                    $error_msg = $error_msg.", ".$error;
                }
            }
            $_SESSION['error_msg'] = "Error: field ".$error_msg." can not be blank.";
        }

        if (empty($_SESSION['error_msg'])) {
            exec (VESTA_CMD."v-add-user ".$v_username." ".$v_password." ".$v_email." ".$v_package." ".$v_fname." ".$v_lname, $output, $return_var);
            if ($return_var != 0) {
                $error = implode('<br>', $output);
                if (empty($error)) $error = 'Error: vesta did not return any output.';
                $_SESSION['error_msg'] = $error;
            } else {
                if (empty($v_notify)) {
                    $to = $_POST['v_email'];
                    $subject = "Welcome to Vesta Control Panel";
                    $hostname = exec('hostname');
                    $from = "Vesta Control Panel <noreply@".$hostname.">";
                    if (!empty($_POST['v_fname'])) {
                        $mailtext = "Hello ".$_POST['v_fname']." ".$_POST['v_lname'].",\n";
                    } else {
                        $mailtext = "Hello,\n";
                    }
                    $mailtext .= "Your account has been created successfully and is ready to use.\n\n";
                    $mailtext .= "https://".$_SERVER['HTTP_HOST']."/login/\n";
                    $mailtext .= "username: ".$_POST['v_username']."\n";
                    $mailtext .= "password: ".$_POST['v_password']."\n\n";
                    $mailtext .= "--\nVesta Control Panel\n";
                    send_email($to, $subject, $mailtext, $from);
                }

                $_SESSION['ok_msg'] = "OK: user <b>".$_POST[v_username]."</b> has been created successfully.";
                unset($v_username);
                unset($v_password);
                unset($v_email);
                unset($v_fname);
                unset($v_lname);
            }
            unset($output);
        }
    }

    exec (VESTA_CMD."v-list-user-packages json", $output, $return_var);
    check_error($return_var);
    $data = json_decode(implode('', $output), true);
    unset($output);

    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_user.html');
    unset($_SESSION['error_msg']);
    unset($_SESSION['ok_msg']);
}

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
