<?php
session_start(); // Start the session

// Include the necessary functions
include 'functions.php'; // All database and reusable functions are here


// Initialize variables
$email = '';
$password = '';
$errors = [];

$result = null;

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input fields
    if (empty($email)) {
        $errors[] = "Email Address is required!";
    }
    if (empty($password)) {
        $errors[] = "Password is required!";
    }

    if (empty($errors)) {
        // Call the login function
        $result = loginUser($email, $password);

        if ($result['success']) {
            // Successful login
            $_SESSION['loggedin'] = true;
            $_SESSION['users'] = $result['users']; // Store user info in session
            header("Location: admin/dashboard.php");
            exit();
        } else {
            // Handle errors safely
            if (isset($result['message'])) {
                $errors[] = $result['message'];
            } elseif (isset($result['errors']) && !empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
            } else {
                $errors[] = "An unknown error occurred. Please try again.";
            }
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title></title>
</head>

<body class="bg-secondary-subtle">
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="col-3">
            <!-- Server-Side Validation Messages should be placed here -->
            <?php if (!empty($errors)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>System Errors:</strong> Please correct the following errors:
                    <hr>
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-4 fw-normal">Login</h1>
                    <form method="post" action="">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="email" name="email" placeholder="user1@example.com"  value="<?= htmlspecialchars($email) ?>">
                            <label for="email">Email address</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="<?= htmlspecialchars($password) ?>">
                            <label for="password">Password</label>
                        </div>
                        <div class="form-floating mb-3">
                            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>