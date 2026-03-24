<?php
session_start();
require_once '../config/database.php';
global $pdo;


if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$status = "";

if (!isset($_GET['id'])) {
    header("Location: student-page.php");
    exit();
}

$student_id = $_GET['id'];

try {
    $stmt_t = $pdo->prepare("SELECT grade_handle, section, fullname FROM teachers WHERE id = ?");
    $stmt_t->execute([$teacher_id]);
    $teacher = $stmt_t->fetch(PDO::FETCH_ASSOC);

    $stmt_s = $pdo->prepare("SELECT * FROM students WHERE id = ? AND grade = ? AND section = ?");
    $stmt_s->execute([$student_id, $teacher['grade_handle'], $teacher['section']]);
    $student = $stmt_s->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: student-page.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $birthday = $_POST['birthday'];
    $sex      = $_POST['sex'];

    try {
        $update = $pdo->prepare("UPDATE students SET fullname = ?, username = ?, password = ?, birthday = ?, sex = ? WHERE id = ?");
        $update->execute([$fullname, $username, $password, $birthday, $sex, $student_id]);
        $status = "success";
        $stmt_s->execute([$student_id, $teacher['grade_handle'], $teacher['section']]);
        $student = $stmt_s->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | Verbal Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent: #4318FF;
            --bg-body: #F4F7FE;
            --text-dark: #1B2559;
            --text-gray: #A3AED0;
            --sidebar-dark: #111C44;
            --success: #05CD99;
            --white: #ffffff;
            --shadow: 14px 17px 40px 4px rgba(112, 144, 176, 0.08);
        }

        html, body {
            height: 100vh; width: 100vw; margin: 0; padding: 0;
            overflow: hidden; background: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body { display: flex; }

        .sidebar {
            width: 280px; background: var(--sidebar-dark); padding: 40px 25px;
            display: flex; flex-direction: column; color: white; flex-shrink: 0;
        }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link {
            display: flex; align-items: center; padding: 16px 20px;
            color: var(--text-gray); text-decoration: none; border-radius: 20px;
            font-weight: 700; transition: 0.3s ease; margin-bottom: 8px;
        }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }

        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--accent); text-decoration: none; font-weight: 700;
            margin-bottom: 20px; transition: 0.3s;
        }
        .back-btn:hover { transform: translateX(-5px); }

        .edit-card {
            background: white; padding: 40px; border-radius: 25px;
            box-shadow: var(--shadow); max-width: 800px; margin: 0 auto; width: 100%;
        }

        .form-label { font-weight: 800; font-size: 0.75rem; color: var(--text-gray); text-transform: uppercase; display: block; margin-bottom: 8px; }
        .form-input {
            width: 100%; padding: 15px; margin-bottom: 25px; border-radius: 12px;
            border: 2px solid #E0E5F2; outline: none; font-family: inherit; font-weight: 600;
            transition: 0.3s;
        }
        .form-input:focus { border-color: var(--accent); }

        .btn-update {
            background: var(--accent); color: white; border: none;
            padding: 15px 30px; border-radius: 12px; font-weight: 800;
            cursor: pointer; width: 100%; font-size: 1rem; transition: 0.3s;
        }
        .btn-update:hover { box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); transform: translateY(-2px); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">🟣 <span>Verbal.</span></div>
    <nav style="display:flex; flex-direction:column; gap:5px;">
        <a href="teacher_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> &nbsp; Dashboard</a>
        <a href="student-page.php" class="nav-link active"><i class="fas fa-user-graduate"></i> &nbsp; Students</a>
        <a href="class-reports.php" class="nav-link"><i class="fas fa-chart-pie"></i> &nbsp; Reports</a>
    </nav>
</aside>

<main class="main-wrapper">
    <a href="student-page.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Student List</a>

    <div class="edit-card animate__animated animate__fadeIn">
        <h2 style="font-weight: 800; color: var(--text-dark); margin-bottom: 10px;">Edit Student Profile</h2>
        <p style="color: var(--text-gray); font-weight: 600; margin-bottom: 30px;">Update information for <?= htmlspecialchars($student['fullname']) ?></p>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-input" value="<?= htmlspecialchars($student['fullname']) ?>" required>

                    <label class="form-label">Username / LRN</label>
                    <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($student['username']) ?>" required>

                    <label class="form-label">Gender</label>
                    <select name="sex" class="form-input">
                        <option value="Male" <?= $student['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $student['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Birthday</label>
                    <input type="date" name="birthday" class="form-input" value="<?= $student['birthday'] ?>" required>

                    <label class="form-label">Password</label>
                    <input type="text" name="password" class="form-input" value="<?= htmlspecialchars($student['password']) ?>" required>

                    <div style="margin-top: 10px;">
                        <button type="submit" name="update_student" class="btn-update">Save Changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<?php if ($status === "success"): ?>
    <script>
        Swal.fire({
            title: 'Updated!',
            text: 'Student profile has been updated successfully.',
            icon: 'success',
            confirmButtonColor: '#4318FF'
        });
    </script>
<?php elseif ($status === "error"): ?>
    <script>
        Swal.fire({
            title: 'Error!',
            text: 'Something went wrong while updating.',
            icon: 'error'
        });
    </script>
<?php endif; ?>

</body>
</html>