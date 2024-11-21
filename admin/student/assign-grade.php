<?php
session_start();
ob_start();

$Pagetitle = "Assign Grade";
require_once '../partials/header.php';
require_once '../partials/side-bar.php';
require_once '../../functions.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize messages
$errorMessage = '';
$successMessage = '';

// Fetch record ID from GET or POST
$subjectMappingId = $_GET['id'] ?? $_POST['id'] ?? null;

if (!empty($subjectMappingId) && is_numeric($subjectMappingId)) {
    $subjectMappingId = intval($subjectMappingId);
    $databaseConnection = connectDatabase();

    if ($databaseConnection && !$databaseConnection->connect_error) {
        // Fetch the student-subject data for the given record ID
        $subjectRecord = fetchSubjectMapping($databaseConnection, $subjectMappingId);

        if ($subjectRecord) {
            // Handle form submission for assigning the grade
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_grade'])) {
                $gradeInput = $_POST['grade'] ?? '';

                // Validate the grade
                if (!validateGrade($gradeInput)) {
                    $errorMessage = "Grade must be a numeric value between 0 and 100.";
                } else {
                    // Update the grade in the database
                    $grade = floatval($gradeInput);
                    if (updateGrade($databaseConnection, $grade, $subjectMappingId)) {
                        $successMessage = "Grade successfully assigned.";
                        redirectToAttachSubjectPage($subjectRecord['student_id']);
                    } else {
                        $errorMessage = "Failed to assign the grade. Please try again.";
                    }
                }
            }
        } else {
            $errorMessage = "Record not found.";
            redirectToAttachSubjectPage();
        }
    } else {
        $errorMessage = "Database connection failed: " . ($databaseConnection->connect_error ?? 'Unknown error');
    }
} else {
    $errorMessage = "Invalid request. No valid ID provided.";
    redirectToAttachSubjectPage();
}

// Helper function to fetch the student-subject record
function fetchSubjectMapping($databaseConnection, $subjectMappingId)
{
    $query = "SELECT students.id AS student_id, students.first_name, students.last_name, 
                     subjects.subject_code, subjects.subject_name, students_subjects.grade 
              FROM students_subjects 
              JOIN students ON students_subjects.student_id = students.id 
              JOIN subjects ON students_subjects.subject_id = subjects.id 
              WHERE students_subjects.id = ?";
    $stmt = $databaseConnection->prepare($query);
    $stmt->bind_param('i', $subjectMappingId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?? null;
}

// Helper function to update the grade in the database
function updateGrade($databaseConnection, $grade, $subjectMappingId)
{
    $query = "UPDATE students_subjects SET grade = ? WHERE id = ?";
    $stmt = $databaseConnection->prepare($query);
    $stmt->bind_param('di', $grade, $subjectMappingId);
    return $stmt->execute();
}

// Helper function to validate grade input
function validateGrade($gradeInput)
{
    return is_numeric($gradeInput) && $gradeInput >= 0 && $gradeInput <= 100;
}

// Helper function to redirect to the attach-subject page
function redirectToAttachSubjectPage($studentId = null)
{
    $location = "attach-subject.php";
    if ($studentId) {
        $location .= "?id=" . htmlspecialchars($studentId);
    }
    header("Location: $location");
    exit();
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Assign Grade to Subject</h1>

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../student/register.php">Register Student</a></li>
            <?php if (isset($subjectRecord)): ?>
                <li class="breadcrumb-item">
                    <a href="attach-subject.php?id=<?php echo htmlspecialchars($subjectRecord['student_id']); ?>">Attach Subject to Student</a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">Assign Grade to Subject</li>
        </ol>
    </nav>

    <!-- Display Messages -->
    <?php echo displayAlert($errorMessage, 'danger'); ?>
    <?php echo displayAlert($successMessage, 'success'); ?>

    <?php if (isset($subjectRecord)): ?>
        <div class="card">
            <div class="card-body">
                <h5>Selected Student and Subject Information</h5>
                <ul>
                    <li><strong>Student ID:</strong> <?php echo htmlspecialchars($subjectRecord['student_id']); ?></li>
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($subjectRecord['first_name'] . ' ' . $subjectRecord['last_name']); ?></li>
                    <li><strong>Subject Code:</strong> <?php echo htmlspecialchars($subjectRecord['subject_code']); ?></li>
                    <li><strong>Subject Name:</strong> <?php echo htmlspecialchars($subjectRecord['subject_name']); ?></li>
                    <li><strong>Current Grade:</strong> 
                        <?php echo $subjectRecord['grade'] > 0 ? number_format($subjectRecord['grade'], 2) : '--.--'; ?>
                    </li>
                </ul>

                <form method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($subjectMappingId); ?>">
                    <div class="mb-3">
                        <label for="grade" class="form-label">Grade</label>
                        <input type="number" step="0.01" class="form-control" id="grade" name="grade" 
                               value="<?php echo htmlspecialchars($subjectRecord['grade'] ?? ''); ?>" required>
                    </div>
                    <a href="attach-subject.php?id=<?php echo htmlspecialchars($subjectRecord['student_id']); ?>" 
                       class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="assign_grade" class="btn btn-primary">Assign Grade to Subject</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php 
require_once '../partials/footer.php'; 
ob_end_flush();
?>
