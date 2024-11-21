<?php
session_start();
ob_start(); // Start output buffering

$Pagetitle = "Detach a Subject";
require_once '../partials/header.php';
require_once '../partials/side-bar.php';
require_once '../../functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$error_message = '';
$success_message = '';
$record = null;

// Check if a valid record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid request. No valid ID provided.";
    redirectToAttachPage();
}

$record_id = intval($_GET['id']);
$connection = connectDatabase();

if ($connection && !$connection->connect_error) {
    // Fetch the student-subject record based on the provided ID
    $record = fetchRecord($connection, $record_id);

    if ($record) {
        // Handle form submission for detaching the subject
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['detach_subject'])) {
            if (detachSubject($connection, $record_id)) {
                $success_message = "Subject successfully detached.";
                redirectToAttachPage($record['student_id']);
            } else {
                $error_message = "Failed to detach the subject. Please try again.";
            }
        }
    } else {
        $error_message = "Record not found.";
        redirectToAttachPage();
    }
} else {
    $error_message = "Database connection failed: " . ($connection->connect_error ?? 'Unknown error');
}

// Close the database connection
if ($connection) $connection->close();

// Helper function to fetch the record details
function fetchRecord($connection, $record_id)
{
    $query = "SELECT 
                students.id AS student_id, 
                students.first_name, 
                students.last_name, 
                subjects.subject_code, 
                subjects.subject_name 
              FROM students_subjects 
              JOIN students ON students_subjects.student_id = students.id 
              JOIN subjects ON students_subjects.subject_id = subjects.id 
              WHERE students_subjects.id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $record_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?? null;
}

// Helper function to detach the subject
function detachSubject($connection, $record_id)
{
    $query = "DELETE FROM students_subjects WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $record_id);
    return $stmt->execute();
}

// Helper function to redirect to the attach-subject page
function redirectToAttachPage($student_id = null)
{
    $location = "attach-subject.php";
    if ($student_id) {
        $location .= "?id=" . htmlspecialchars($student_id);
    }
    header("Location: $location");
    exit();
}

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Detach a Subject</h1>

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../student/register.php">Register Student</a></li>
            <?php if ($record): ?>
                <li class="breadcrumb-item">
                    <a href="attach-subject.php?id=<?php echo htmlspecialchars($record['student_id']); ?>">Attach Subject to Student</a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">Detach Subject from Student</li>
        </ol>
    </nav>

    <!-- Display Messages -->
    <?php echo displayAlert($error_message, 'danger'); ?>
    <?php echo displayAlert($success_message, 'success'); ?>

    <?php if ($record): ?>
        <div class="card">
            <div class="card-body">
                <p><strong>Confirm Detachment:</strong></p>
                <ul>
                    <li><strong>Student ID:</strong> <?php echo htmlspecialchars($record['student_id']); ?></li>
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></li>
                    <li><strong>Subject Code:</strong> <?php echo htmlspecialchars($record['subject_code']); ?></li>
                    <li><strong>Subject Name:</strong> <?php echo htmlspecialchars($record['subject_name']); ?></li>
                </ul>

                <!-- Detach Form -->
                <form method="post">
                    <a href="attach-subject.php?id=<?php echo htmlspecialchars($record['student_id']); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="detach_subject" class="btn btn-danger">Detach Subject</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php 
require_once '../partials/footer.php'; 
ob_end_flush();
?>
