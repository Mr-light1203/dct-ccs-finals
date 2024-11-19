<?php
include("../../functions.php");
$Pagetitle = "Edit Student";
include("../partials/header.php");
include("../partials/side-bar.php");

$errorMessage = null;
$successMessage = null;

// Get student ID from query parameter
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['id'];
    $updatedFirstName = trim($_POST['firstName']);
    $updatedLastName = trim($_POST['lastName']);

    if (empty($updatedFirstName) || empty($updatedLastName)) {
        $errorMessage = "All fields are required!";
    } else {
        $conn = connectDatabase();
        $stmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $updatedFirstName, $updatedLastName, $studentId);

        if ($stmt->execute()) {
            $successMessage = "Student details updated successfully!";
        } else {
            $errorMessage = "Failed to update student details!";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <div class="container">
        <h2>Edit Student</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="register.php">Register Student</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Student</li>
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

        <!-- Edit Form -->
        <?php if (isset($student)): ?>
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                        <div class="mb-3">
    <label for="studentId" class="form-label">Student ID</label>
    <input type="text" class="form-control" id="studentId" value="<?php echo $student['student_id']; ?>" readonly>
</div>
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo $student['first_name']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo $student['last_name']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Student</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
include("../partials/footer.php");
?>
