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
//Logout function 
function logoutUser() {
    session_destroy();
    header("Location:/index.php");
}

function getStudentIdPrefix($id) {
    return substr($id, 0, 4);
}

function getStudentDetails($id) {
    $db = connectDatabase();
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();

    $stmt->close();
    $db->close();

    return $details;
}

function showNotification($message, $type = 'danger') {
    if (!$message) {
        return '';
    }

    $message = (array)$message;
    $alertHTML = '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
    $alertHTML .= '<ul>';
    foreach ($message as $msg) {
        $alertHTML .= '<li>' . htmlspecialchars($msg) . '</li>';
    }
    $alertHTML .= '</ul>';
    $alertHTML .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $alertHTML .= '</div>';

    return $alertHTML;
}
function verifyStudentData($data) {
    $validation_errors = [];
    if (empty($data['id_number'])) {
        $validation_errors[] = "Student ID is required.";
    }
    if (empty($data['first_name'])) {
        $validation_errors[] = "First Name is required.";
    }
    if (empty($data['last_name'])) {
        $validation_errors[] = "Last Name is required.";
    }

    return $validation_errors;
}
function isStudentIdDuplicate($data) {
    $db = connectDatabase();
    $sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $data['id_number']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        return "This Student ID is already taken.";
    }

    return '';
}
function createUniqueStudentId() {
    $db = connectDatabase();
    $query = "SELECT MAX(id) AS current_max FROM students";
    $result = $db->query($query);
    $data = $result->fetch_assoc();
    $db->close();

    return ($data['current_max'] ?? 0) + 1;
}

function sanitizeStudentId($id) {
    return substr($id, 0, 4);
}
function displayAlert($messages, $alertType = 'danger') {
    // Return an empty string if there are no messages
    if (!$messages) {
        return '';
    }

    // Convert single message to an array for consistent handling
    $messages = (array) $messages;

    // Build the alert box HTML
    $alertHTML = '<div class="alert alert-' . htmlspecialchars($alertType) . ' alert-dismissible fade show" role="alert">';
    $alertHTML .= '<ul>';
    foreach ($messages as $message) {
        $alertHTML .= '<li>' . htmlspecialchars($message) . '</li>';
    }
    $alertHTML .= '</ul>';
    $alertHTML .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $alertHTML .= '</div>';

    return $alertHTML;
}
/**
 * Get the count of students who passed based on their average grade (>= 75).
 * 
 * @param mysqli $connection
 * @return int The count of students who passed.
 */
function getPassedStudentsCount($connection)
{
    $query = "
        SELECT COUNT(*) AS passed_count
        FROM (
            SELECT student_id, AVG(grade) AS avg_grade
            FROM students_subjects
            WHERE grade IS NOT NULL
            GROUP BY student_id
            HAVING avg_grade >= 75
        ) AS passed_students";
    $result = $connection->query($query);
    return $result->fetch_assoc()['passed_count'] ?? 0;
}

/**
 * Get the count of students who failed based on their average grade (< 75).
 * 
 * @param mysqli $connection
 * @return int The count of students who failed.
 */
function getFailedStudentsCount($connection)
{
    $query = "
        SELECT COUNT(*) AS failed_count
        FROM (
            SELECT student_id, AVG(grade) AS avg_grade
            FROM students_subjects
            WHERE grade IS NOT NULL
            GROUP BY student_id
            HAVING avg_grade < 75
        ) AS failed_students";
    $result = $connection->query($query);
    return $result->fetch_assoc()['failed_count'] ?? 0;
}
/**
 * Fetch the total number of registered students.
 * 
 * @param object $db The database connection.
 * @return int The total number of students.
 */
function getTotalStudents($db) {
    $query = "SELECT COUNT(*) AS total_students FROM students";
    $result = $db->query($query);

    if ($result) {
        $row = $result->fetch_assoc();
        return (int) $row['total_students'];
    } else {
        return 0; // Return 0 if the query fails
    }
}


/**
 * Fetch the total number of subjects from the database.
 * 
 * @param object $db The database connection.
 * @return int The total number of subjects.
 */
function getTotalSubjects($db) {
    $query = "SELECT COUNT(*) AS total_subjects FROM subjects";
    $result = $db->query($query);

    if ($result) {
        $row = $result->fetch_assoc();
        return (int) $row['total_subjects'];
    } else {
        return 0; // Return 0 if the query fails
    }
}

?>

