<?php
ob_start(); // Start output buffering
include("../../functions.php");
$Pagetitle = "Delete Student";
include("../partials/header.php");
include("../partials/side-bar.php");

$errorMessage = null;
$successMessage = null;

// Check if ID is passed in the URL
if (isset($_GET['id'])) {
    $studentId = $_GET['id'];

    $conn = connectDatabase();
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $errorMessage = "Student not found!";
    }

    $stmt->close();
    $conn->close();
}

// Handle form submission to delete the student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $studentIdToDelete = $_POST['id'];

        $conn = connectDatabase();
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $studentIdToDelete);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: register.php?message=deleted"); // Redirect after successful deletion
            exit();
        } else {
            $errorMessage = "Failed to delete the student record!";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <div>
        <!-- Content here -->
        <h2>Delete a Student</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="register.php">Register Student</a></li>
                <li class="breadcrumb-item active" aria-current="page">Delete Student</li>
            </ol>
        </nav>

        <!-- Alerts -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Confirmation Form -->
        <?php if (isset($student)): ?>
            <div class = "card">
                <div class = "card-body">
                    <p>Are you sure you want to delete the following student record?</p>
                    <ul>
                        <li><strong>Student ID:</strong> <?php echo $student['student_id']; ?></li>
                        <li><strong>First Name:</strong> <?php echo $student['first_name']; ?></li>
                        <li><strong>Last Name:</strong> <?php echo $student['last_name']; ?></li>
                    </ul>
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Delete Student Record</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
include("../partials/footer.php");
ob_end_flush(); // End and flush the buffer
?>
