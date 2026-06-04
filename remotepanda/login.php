<?php
if (isset($_POST['login'])) {
    session_start();
    include('includes/dbconnection.php');
    include('hitman/config.php');
    include('hitman/project-security.php');

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    $dbConn = null;
    if (isset($conn) && $conn instanceof mysqli) {
        $dbConn = $conn;
    } elseif (isset($con) && $con instanceof mysqli) {
        $dbConn = $con;
    }

    if (!$dbConn) {
        $_SESSION['message'] = "Login service unavailable.";
        header('location:u.php');
        exit;
    }

    $stmt = mysqli_prepare($dbConn, "SELECT userid, username, password FROM `user` WHERE username = ? LIMIT 1");
    if (!$stmt) {
        $_SESSION['message'] = "Login service unavailable.";
        header('location:u.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $valid = false;
    if ($row) {
        $stored = (string) $row['password'];
        if (password_verify($password, $stored)) {
            $valid = true;
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $stored) && hash_equals(strtolower($stored), md5($password))) {
            $valid = true;
        } elseif (hash_equals($stored, $password)) {
            // Backward compatibility for legacy plaintext rows.
            $valid = true;
        }
    }

    if (!$valid) {
        $_SESSION['message'] = "Login Failed. User not Found!";
        header('location:u.php');
        exit;
    }

    session_regenerate_id(true);

    if (isset($_POST['remember'])) {
        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie("user", $row['username'], [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    $_SESSION['id'] = $row['userid'];
    header('location:success.php');
    exit;
} else {
    session_start();
    $_SESSION['message'] = "Please Login!";
    header('location:index.php');
    exit;
}
?>
