<?php    
 function connectDatabase(): mysqli {
    $servername = 'localhost';
    $username = 'root';        // Change if needed
    $password = "";            // Change if needed
    $dbname = 'dct-ccs-finals'; // Replace with your actual database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
function loginUser($email, $password) {
    $errors = [];

    // Validation logic
    if (empty($email)) {
        $errors['email'] = 'Email Address is required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email Address is invalid!';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required!';
    }

    // If validation errors exist, return them
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Proceed to check login credentials in the database
    $conn = connectDatabase();

    // Hash the password using MD5
    $hashedPassword = md5($password);

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found
        $user = $result->fetch_assoc();
        return ['success' => true, 'user' => $user];
    } else {
        // User not found
        return ['success' => false, 'errors' => ['credentials' => 'Invalid email or password.']];
    }
}

function guard() {  
    if (empty($_SESSION["email"])){
        header("Location:/index.php");
    }
}
function returnPage(){
    if (!empty($_SESSION["email"])) {
        if (!empty($_SESSION['page'])) { 
            header("Location:". $_SESSION['page']);
            exit();
        } else {
            header("Location: /admin/dashboard.php");
            exit();
        }
    }
}



?>

