<?php
require 'config.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (
            $user &&
            password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            redirect('index.php');
        }

        $error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Sign In | CampusCare
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<div class="login-page">


    <!-- LEFT VISUAL PANEL -->

    <section class="login-visual">

        <div class="login-brand">

            <div class="login-brand-logo">
                CC
            </div>

            <div>

                <h2>
                    CampusCare
                </h2>

                <p>
                    Student Support Desk
                </p>

            </div>

        </div>


        <div class="login-hero">

            <span class="login-eyebrow">
                Campus Support Platform
            </span>

            <h1>
                Manage student support requests from one workspace.
            </h1>

            <p>
                Review service issues, assign ownership, track progress,
                document resolutions, and maintain a clear ticket history.
            </p>

        </div>


        <div class="login-feature-list">

            <div class="login-feature">
                <span>01</span>
                <div>
                    <strong>Ticket Management</strong>
                    <p>Track requests from creation through resolution.</p>
                </div>
            </div>

            <div class="login-feature">
                <span>02</span>
                <div>
                    <strong>Support Workflow</strong>
                    <p>Assign requests and update support status.</p>
                </div>
            </div>

            <div class="login-feature">
                <span>03</span>
                <div>
                    <strong>Activity History</strong>
                    <p>Keep a clear record of important ticket changes.</p>
                </div>
            </div>

        </div>

    </section>


    <!-- LOGIN PANEL -->

    <section class="login-panel">

        <div class="login-card">

            <div class="login-card-heading">

                <span class="login-mobile-logo">
                    CC
                </span>

                <h2>
                    Welcome back
                </h2>

                <p>
                    Sign in to access the CampusCare support dashboard.
                </p>

            </div>


            <?php if ($error): ?>

                <div class="flash error">

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@campuscare.local"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary login-submit"
                >
                    Sign In
                </button>


            </form>


            <div class="demo-account">

                <span>
                    Demo administrator
                </span>

                <strong>
                    admin@campuscare.local
                </strong>

                <small>
                    Use the configured local demo password.
                </small>

            </div>


        </div>

    </section>


</div>


</body>

</html>