<?php
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/includes/database_config.php';

// connect to database
$db = rp_remote_database_connect();

// variable declaration
$username = "";
$email    = "";
$errors   = array();
$branch = "";

// call the register() function if register_btn is clicked
if (isset($_POST['register_btn'])) {
    register();
}

// REGISTER USER
function register(){
    // call these variables with the global keyword to make them available in function
    global $db, $errors, $username, $email;

    // receive all input values from the form. Call the e() function
    // defined below to escape form values
    $username    = e($_POST['username']);
    $email       = e($_POST['email']);
    $password_1  = isset($_POST['password_1']) ? (string) $_POST['password_1'] : '';
    $password_2  = isset($_POST['password_2']) ? (string) $_POST['password_2'] : '';

    // form validation: ensure that the form is correctly filled
    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($email)) {
        array_push($errors, "Email is required");
    }
    if (empty($password_1)) {
        array_push($errors, "Password is required");
    }
    if ($password_1 != $password_2) {
        array_push($errors, "The two passwords do not match");
    }

    // register user if there are no errors in the form
    if (count($errors) == 0) {
        $passwordHash = password_hash($password_1, PASSWORD_DEFAULT);

        $user_type = 'user';
        if (isset($_POST['user_type'])) {
            $submittedType = e($_POST['user_type']);
            $allowedTypes = ['admin', 'radiologist', 'typist', 'user'];
            if (in_array($submittedType, $allowedTypes, true)) {
                $user_type = $submittedType;
            }
        }

        $stmt = mysqli_prepare($db, "INSERT INTO users (username, email, user_type, password) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            array_push($errors, "Registration failed.");
            return;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $user_type, $passwordHash);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            array_push($errors, "Registration failed.");
            return;
        }

        if (isset($_POST['user_type'])) {
            $_SESSION['success'] = "New user successfully created!!";
            header('location: dashboards/admin/users.php');
        } else {
            // get id of the created user
            $logged_in_user_id = mysqli_insert_id($db);

            $_SESSION['user'] = getUserById($logged_in_user_id); // put logged in user in session
            $_SESSION['success_message'] = "You are now logged in.";
            header('location: radpanda/dashboards/admin/users.php');
        }
    }
}

// return user array from their id
function getUserById($id){
    global $db;
    $stmt = mysqli_prepare($db, "SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

function isLegacyMd5Hash($hash){
    return is_string($hash) && preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
}

function verifyUserPassword($plainPassword, $storedHash){
    if (!is_string($storedHash) || $storedHash === '') {
        return false;
    }
    if (password_verify($plainPassword, $storedHash)) {
        return true;
    }
    if (isLegacyMd5Hash($storedHash) && hash_equals(strtolower($storedHash), md5($plainPassword))) {
        return true;
    }
    return false;
}

function shouldUpgradePasswordHash($storedHash){
    if (isLegacyMd5Hash($storedHash)) {
        return true;
    }
    return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

// escape string
function e($val){
    global $db;
    return mysqli_real_escape_string($db, trim($val));
}

function display_error() {
    global $errors;

    if (count($errors) > 0){
        echo '<div class="error">';
        foreach ($errors as $error){
            echo $error .'<br>';
        }
        echo '</div>';
    }
}

function isLoggedIn()
{
    if (isset($_SESSION['user'])) {
        return true;
    }else{
        return false;
    }
}

// log user out if logout button clicked
if (isset($_GET['logout'])) {
    // Get the user ID of the logged-out user
    $user_id = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;

    // Delete the record from the logged_in_users table
    if ($user_id > 0) {
        $deleteStmt = mysqli_prepare($db, "DELETE FROM logged_in_users WHERE user_id = ?");
        if ($deleteStmt) {
            mysqli_stmt_bind_param($deleteStmt, "i", $user_id);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        }
    }

    session_destroy();
    unset($_SESSION['user']);
    header("location: index.php");
}

// call the login() function if register_btn is clicked
if (isset($_POST['login_btn'])) {
    login();
}

// LOGIN USER
function login(){
    global $db, $username, $errors;

    // grap form values
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    // make sure form is filled properly
    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($password)) {
        array_push($errors, "Password is required");
    }

    // attempt login if no errors on form
    if (count($errors) == 0) {
        $stmt = mysqli_prepare($db, "SELECT * FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            array_push($errors, "Login currently unavailable.");
            return;
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $results = mysqli_stmt_get_result($stmt);
        $logged_in_user = mysqli_fetch_assoc($results);
        mysqli_stmt_close($stmt);

        if ($logged_in_user && verifyUserPassword($password, $logged_in_user['password'])) { // user found
            session_regenerate_id(true);

            // Insert record into logged_in_users table
            $user_id = (int) $logged_in_user['id'];
            $session_id = session_id();
            $login_time = date("Y-m-d H:i:s");

            $insertStmt = mysqli_prepare($db, "INSERT INTO logged_in_users (user_id, session_id, login_time) VALUES (?, ?, ?)");
            if ($insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "iss", $user_id, $session_id, $login_time);
                mysqli_stmt_execute($insertStmt);
                mysqli_stmt_close($insertStmt);
            }

            if (shouldUpgradePasswordHash($logged_in_user['password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgradeStmt = mysqli_prepare($db, "UPDATE users SET password = ? WHERE id = ?");
                if ($upgradeStmt) {
                    mysqli_stmt_bind_param($upgradeStmt, "si", $newHash, $user_id);
                    mysqli_stmt_execute($upgradeStmt);
                    mysqli_stmt_close($upgradeStmt);
                    $logged_in_user['password'] = $newHash;
                }
            }

            // Set session variables after user is found
            $_SESSION['username'] = $logged_in_user['username'];
            $_SESSION['user_type'] = $logged_in_user['user_type'];

            // Redirect based on user type
            if ($logged_in_user['user_type'] == 'typist') {
                $_SESSION['user'] = $logged_in_user;
                $_SESSION['success']  = "You are now logged in";
                header('location: dashboards/typist/index.php');
            }
            elseif ($logged_in_user['user_type'] == 'radiologist') {
                $_SESSION['user'] = $logged_in_user;
                $_SESSION['success']  = "You are now logged in";
                header('location: dashboards/radiologist/index.php');
            }
            else {
                $_SESSION['user'] = $logged_in_user;
                $_SESSION['success_message'] = "Logged in successfully.";
                header('location: dashboards/radiologist/index.php');
            }
        } else {
            array_push($errors, "Wrong username/password combination");
        }
    }
}

// ...
function isAdmin()
{
    if (isset($_SESSION['user']) && $_SESSION['user']['user_type'] == 'admin' ) {
        return true;
    }else{
        return false;
    }
}
