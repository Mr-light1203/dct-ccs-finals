<?php
include("../../functions.php");
$Pagetitle = "Register Student";
include("../partials/header.php");
include("../partials/side-bar.php");
$message = ''; // To hold success or error messages
$messageType = ''; // To hold the type of message ('success' or 'error')

// Handle form submission
$errorMessage = null; // Variable to store error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId']);
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);

    // Check for empty fields
    if (empty($studentId) || empty($firstName) || empty($lastName)) {
        $errorMessage = "All fields are required!";
    } else {
        $conn = connectDatabase();

        // Check for duplicate student ID
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "A student with this ID already exists!";
        } else {
            // Add student to the database
            $stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $studentId, $firstName, $lastName);
            $stmt->execute();
        }

        $stmt->close();
        $conn->close();
    }
}


?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">    
<div class="container">
    <h2>Register a New Student</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Register Student</li>
        </ol>
    </nav>
    
    <!-- Dismissable Alert -->
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <div class="card">
        <div class="card-body">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="studentId" class="form-label">Student ID</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="studentId" 
                        name="studentId" 
                        placeholder="Enter Student ID" 
                        value="<?php echo isset($_POST['studentId']) ? htmlspecialchars($_POST['studentId']) : ''; ?>"
                    >
                </div>
                <div class="mb-3">
                    <label for="firstName" class="form-label">First Name</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="firstName" 
                        name="firstName" 
                        placeholder="Enter First Name" 
                        value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>"
                    >
                </div>
                <div class="mb-3">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="lastName" 
                        name="lastName" 
                        placeholder="Enter Last Name" 
                        value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>"
                    >
                </div>
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary w-100">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student List -->
    <div class="card mt-4">
        <div class="card-header">Student List</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Student ID</th>
                        <th scope="col">First Name</th>
                        <th scope="col">Last Name</th>
                        <th scope="col">Options</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $conn = connectDatabase();
                $result = $conn->query("SELECT * FROM students ORDER BY id ASC");

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['student_id']}</td>
                            <td>{$row['first_name']}</td>
                            <td>{$row['last_name']}</td>
                            <td>
                                <a href='edit.php?id={$row['id']}' class='btn btn-sm btn-info'>Edit</a>
                                <a href='delete.php?id={$row['id']}' class='btn btn-sm btn-danger'>Delete</a>
                                <a href='attach-subject.php?id={$row['id']}' class='btn btn-sm btn-warning'>Attach Subject</a>
                            </td>
                        </tr>";
                }

                $conn->close();
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
<?php 
include("../partials/footer.php");
?>














?>