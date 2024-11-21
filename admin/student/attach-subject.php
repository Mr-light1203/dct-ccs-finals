<?php
$Pagetitle = "Attach Subject";
require_once '../partials/header.php';
require_once '../partials/side-bar.php';
require_once '../../functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize state variables
$message_error = '';
$message_success = '';
$subjects_available = [];
$subjects_linked = [];

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $student_info = getStudentDetails($student_id);

    if (!$student_info) {
        $message_error = "Student record not found.";
    } else {
        $db = connectDatabase();

        if (!$db || $db->connect_error) {
            $message_error = "Database connection error: " . $db->connect_error;
        } else {
            // Retrieve subjects not yet linked to the student
            $query = "SELECT * FROM subjects WHERE id NOT IN (SELECT subject_id FROM students_subjects WHERE student_id = ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $subjects_available = $stmt->get_result();

            // Handle form submission for linking subjects
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_subjects'])) {
                if (!empty($_POST['subjects']) && is_array($_POST['subjects'])) {
                    $selected_subjects = $_POST['subjects'];
                    foreach ($selected_subjects as $subject_id) {
                        $subject_id = intval($subject_id);
                        $link_query = "INSERT INTO students_subjects (student_id, subject_id, grade) VALUES (?, ?, ?)";
                        $link_stmt = $db->prepare($link_query);
                        if ($link_stmt) {
                            $default_grade = 0.00;
                            $link_stmt->bind_param('iid', $student_id, $subject_id, $default_grade);
                            if (!$link_stmt->execute()) {
                                $message_error = "Error linking subject: " . $link_stmt->error;
                            }
                        } else {
                            $message_error = "Statement preparation failed: " . $db->error;
                        }
                    }

                    // Refresh the list of available subjects
                    $stmt->execute();
                    $subjects_available = $stmt->get_result();

                   // $message_success = "Subjects successfully linked to the student.";
                } else {
                    $message_error = "Please select at least one subject to link.";
                }
            }

            // Retrieve subjects already linked to the student
            $linked_query = "SELECT subjects.subject_code, subjects.subject_name, students_subjects.grade, students_subjects.id 
                             FROM subjects 
                             JOIN students_subjects ON subjects.id = students_subjects.subject_id 
                             WHERE students_subjects.student_id = ?";
            $linked_stmt = $db->prepare($linked_query);
            $linked_stmt->bind_param('i', $student_id);
            $linked_stmt->execute();
            $subjects_linked = $linked_stmt->get_result();
        }
    }
} else {
    $message_error = "Student ID not provided.";
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Attach Subject to Student</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../student/register.php">Register Student</a></li>
            <li class="breadcrumb-item active" aria-current="page">Attach Subject to Student</li>
        </ol>
    </nav>

    <!-- Display Notifications -->
    <?php echo showNotification($message_error, 'danger'); ?>
    <?php echo showNotification($message_success, 'success'); ?>

    <?php if (isset($student_info)): ?>
        <div class="card">
            <div class="card-body">
                <p><strong>Student Information:</strong></p>
                <ul>
                    <li><strong>ID:</strong> <?php echo htmlspecialchars($student_info['student_id']); ?></li>
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></li>
                </ul>

                <!-- Form to Link Subjects -->
                <form method="post" action="">
                    <hr>
                    <?php if ($subjects_available->num_rows > 0): ?>
                        <?php while ($subject = $subjects_available->fetch_assoc()): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subjects[]" value="<?php echo $subject['id']; ?>" id="subject_<?php echo $subject['id']; ?>">
                                <label class="form-check-label" for="subject_<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                        <button type="submit" name="link_subjects" class="btn btn-primary mt-3">Attach Subjects</button>
                    <?php else: ?>
                        <p>No available subjects to link.</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Linked Subjects Table -->
        <hr>
        <div class = "card">
        <div class = "card-body">
        <h3>Subject List</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subjects_linked->num_rows > 0): ?>
                    <?php while ($row = $subjects_linked->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo $row['grade'] > 0 ? number_format($row['grade'], 2) : '--.--'; ?></td>
                            <td>
                                <form method="get" action="unlink-subject.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Detach</button>
                                </form>
                                <form method="post" action="update-grade.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Assign Grade</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No attached subjects found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
    <?php endif; ?>
</main>

<?php require_once '../partials/footer.php'; ?>
