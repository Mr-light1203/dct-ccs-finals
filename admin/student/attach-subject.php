<?php
include("../../functions.php");
$Pagetitle = "Attach Subject";
include("../partials/header.php");
include("../partials/side-bar.php");

// Connect to the database
$conn = connectDatabase();

// Process form submission to attach subjects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $selectedSubjects = $_POST['subjects'] ?? []; // Get selected subjects or an empty array

    foreach ($selectedSubjects as $subjectId) {
        // Check if the subject is already assigned to prevent duplicates
        $checkQuery = $conn->prepare("SELECT * FROM students_subjects WHERE student_id = ? AND subject_id = ?");
        $checkQuery->bind_param("ii", $studentId, $subjectId);
        $checkQuery->execute();
        $result = $checkQuery->get_result();

        if ($result->num_rows === 0) {
            // Attach subject if not already assigned
            $insertQuery = $conn->prepare("INSERT INTO students_subjects (student_id, subject_id) VALUES (?, ?)");
            $insertQuery->bind_param("ii", $studentId, $subjectId);
            $insertQuery->execute();
        }
    }

    // Redirect to the same page with a success message
    header("Location: attach.php?student_id=" . $studentId . "&success=1");
    exit;
}

// Fetch the student details
$studentId = $_GET['student_id'] ?? null;
if (!$studentId) {
    echo "No student selected.";
    exit;
}

$studentQuery = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$studentQuery->bind_param("s", $studentId);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch unassigned subjects
$unassignedQuery = $conn->prepare("
    SELECT * FROM subjects 
    WHERE id NOT IN (SELECT subject_id FROM students_subjects WHERE student_id = ?)
");
$unassignedQuery->bind_param("s", $studentId);
$unassignedQuery->execute();
$unassignedSubjects = $unassignedQuery->get_result();

// Fetch assigned subjects
$assignedQuery = $conn->prepare("
    SELECT subjects.* FROM subjects 
    JOIN students_subjects ON subjects.id = students_subjects.subject_id 
    WHERE students_subjects.student_id = ?
");
$assignedQuery->bind_param("s", $studentId);
$assignedQuery->execute();
$assignedSubjects = $assignedQuery->get_result();
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <div class="container">
        <h2>Attach Subject to Student</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="register.php">Register Student</a></li>
                <li class="breadcrumb-item active" aria-current="page">Attach Subject</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-body">
                <!-- Success message -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Subjects successfully attached to the student!
                    </div>
                <?php endif; ?>

                <!-- Student information -->
                <h5>Selected Student Information</h5>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></p>

                <hr>

                <!-- Unassigned subjects form -->
                <form action="attach.php" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>">
                    <h6>Select Subjects to Attach</h6>
                    <?php if ($unassignedSubjects->num_rows > 0): ?>
                        <ul>
                            <?php while ($row = $unassignedSubjects->fetch_assoc()): ?>
                                <li>
                                    <label>
                                        <input type="checkbox" name="subjects[]" value="<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['code'] . " - " . $row['name']); ?>
                                    </label>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <button type="submit" class="btn btn-primary">Attach Subjects</button>
                    <?php else: ?>
                        <p>All subjects are already attached to this student.</p>
                    <?php endif; ?>
                </form>

                <hr>

                <!-- Assigned subjects -->
                <h6>Assigned Subjects</h6>
                <?php if ($assignedSubjects->num_rows > 0): ?>
                    <ul>
                        <?php while ($row = $assignedSubjects->fetch_assoc()): ?>
                            <li><?php echo htmlspecialchars($row['code'] . " - " . $row['name']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No subjects assigned yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php 
include("../partials/footer.php");
?>
