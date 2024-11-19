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
function addStudent($studentId, $firstName, $lastName) {
    $conn = connectDatabase(); // Create a database connection

    // Check for duplicate Student ID
    $checkStmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $checkStmt->bind_param("s", $studentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Student ID already exists. Please use a unique ID.'
        ];
    }

    // Insert new student
    $stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $studentId, $firstName, $lastName);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return [
            'success' => true,
            'message' => 'Student added successfully.'
        ];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Error adding student: ' . $error
        ];
    }
}

function getAllStudents() {
    $conn = connectDatabase(); // Create a database connection
    $query = "SELECT * FROM students ORDER BY id DESC";
    $result = $conn->query($query);

    $students = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    $conn->close();
    return $students;
}




?>

