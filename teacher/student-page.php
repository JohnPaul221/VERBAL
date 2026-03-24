<?php
session_start();
require_once '../config/database.php';
global $pdo;


if (!isset($_SESSION['teacher_logged_in']) || $_SESSION['teacher_logged_in'] !== true || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$status = "";


if (isset($_POST['reset_progress'])) {
    $s_id = $_POST['student_id'];
    try {
        $del = $pdo->prepare("DELETE FROM student_ratings WHERE student_id = ?");
        $del->execute([$s_id]);
        $reset = $pdo->prepare("UPDATE student_progress_main SET word_index = 0, words_attempted = 0, words_correct = 0 WHERE student_id = ?");
        $reset->execute([$s_id]);
        echo "success";
    } catch (PDOException $e) {
        echo "error";
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_student'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']); // LRN
    $password = trim($_POST['password']);
    $birthday = $_POST['birthday'];
    $sex      = $_POST['sex'];

    try {
        $check = $pdo->prepare("SELECT id FROM students WHERE username = ?");
        $check->execute([$username]);

        if ($check->rowCount() > 0) {
            $status = "exists";
        } else {
            $stmt_t = $pdo->prepare("SELECT grade_handle, section FROM teachers WHERE id = ?");
            $stmt_t->execute([$teacher_id]);
            $t_data = $stmt_t->fetch();

            $insert = $pdo->prepare("INSERT INTO students (fullname, username, password, grade, section, birthday, sex) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$fullname, $username, $password, $t_data['grade_handle'], $t_data['section'], $birthday, $sex]);
            $status = "success";
        }
    } catch (PDOException $e) {
        $status = "error";
    }
}


if (isset($_GET['delete_id'])) {
    try {
        $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $del->execute([$_GET['delete_id']]);
        header("Location: student-page.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        $status = "error";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql_students = "SELECT *, (CASE WHEN last_login > NOW() - INTERVAL 5 MINUTE THEN 1 ELSE 0 END) as is_online FROM students WHERE grade = ? AND section = ? ORDER BY fullname ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute([$teacher['grade_handle'], $teacher['section']]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Verbal Pro</title>
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
            height: 100vh; width: 100vw; margin: 0; padding: 0; overflow: hidden;
            background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex;
        }

        * { box-sizing: border-box; -ms-overflow-style: none; scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }

        .sidebar {
            width: 280px; background: var(--sidebar-dark); padding: 40px 25px;
            display: flex; flex-direction: column; color: white; flex-shrink: 0;
            transition: 0.4s; height: 100vh;
        }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link {
            display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray);
            text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s; margin-bottom: 8px;
        }
        .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: white; transform: translateX(8px); }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .logout-btn {
            margin-top: auto; color: #ff5f5f; border: 1px solid rgba(255, 95, 95, 0.2);
            text-align: center; cursor: pointer; padding: 12px; border-radius: 15px; font-weight: 700; transition: 0.3s;
        }
        .logout-btn:hover { background: #ff5f5f; color: white; }

        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; gap: 15px; }

        .controls-right { display: flex; align-items: center; gap: 15px; }
        .search-wrapper input {
            width: 250px; padding: 10px 20px; border-radius: 50px; border: none;
            background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.03); outline: none; font-weight: 600;
        }
        .filter-select {
            padding: 10px 15px; border-radius: 50px; border: none; background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); outline: none; font-weight: 700; color: var(--text-dark); cursor: pointer;
        }

        .section-header {
            background: linear-gradient(135deg, #4318FF 0%, #991BFF 100%);
            padding: 30px; border-radius: 24px; color: white; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0px 20px 40px rgba(67, 24, 255, 0.2); flex-shrink: 0;
        }

        .list-container-parent { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .student-row-header {
            display: grid; grid-template-columns: 60px 2fr 1.2fr 120px 80px 100px 100px;
            padding: 10px 25px; color: var(--text-gray); font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
        }
        .student-list-scroll { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .student-card-row {
            display: grid; grid-template-columns: 60px 2fr 1.2fr 120px 80px 100px 100px;
            align-items: center; background: var(--white); padding: 12px 25px; border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: 0.3s;
        }
        .student-card-row:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }

        .clickable-name { font-weight: 800; cursor: pointer; transition: 0.2s; padding: 5px 8px; border-radius: 8px; color: var(--text-dark); }
        .clickable-name:hover { color: var(--accent); background: rgba(67, 24, 255, 0.08); }

        .avatar-box {
            width: 40px; height: 40px; border-radius: 10px; background: #E9EDF7; color: var(--accent);
            display: flex; align-items: center; justify-content: center; font-weight: 800; position: relative;
        }
        .status-dot { position: absolute; bottom: -2px; right: -2px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid white; }
        .online { background: #05CD99; } .offline { background: #CBD5E0; }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(17, 28, 68, 0.5);
            backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-box { background: white; padding: 40px; border-radius: 25px; width: 100%; max-width: 750px; position: relative; animation: zoomIn 0.3s; }
        .form-input { width: 100%; padding: 12px; margin-top: 5px; margin-bottom: 15px; border-radius: 10px; border: 2px solid #E0E5F2; outline: none; }

        .swal2-popup.swal2-toast {
            box-shadow: 0 10px 30px rgba(67, 24, 255, 0.1) !important;
            border-radius: 15px !important;
            padding: 1.2rem !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(5px);
        }

        @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
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
    <div class="logout-btn" onclick="confirmLogout()">Logout Account</div>
</aside>

<main class="main-wrapper">
    <header class="top-bar">
        <div class="controls-right">
            <div class="search-wrapper">
                <input type="text" id="studentSearch" placeholder="Search student name..." onkeyup="applyFilters()">
            </div>
            <select id="genderFilter" class="filter-select" onchange="applyFilters()">
                <option value="all">All Genders</option>
                <option value="Male">Male Only</option>
                <option value="Female">Female Only</option>
            </select>
        </div>
        <div style="background: white; padding: 10px 20px; border-radius: 50px; font-weight:800; font-size:0.85rem; box-shadow: var(--shadow);"><?= htmlspecialchars($teacher['fullname']) ?></div>
    </header>

    <div class="section-header">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800;">Section <?= htmlspecialchars($teacher['section']) ?></h2>
            <p style="opacity: 0.9; font-weight: 600;">Grade <?= htmlspecialchars($teacher['grade_handle']) ?> • Monitoring <?= count($students) ?> students</p>
        </div>
        <button onclick="openAddModal()" style="background: white; color: var(--accent); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer;">+ Add Student</button>
    </div>

    <div class="list-container-parent">
        <div class="student-row-header"><div>Icon</div><div>Full Name</div><div>LRN</div><div>Birthday</div><div>Gender</div><div>Status</div><div>Action</div></div>
        <div class="student-list-scroll" id="studentList">
            <?php foreach ($students as $row): ?>
                <div class="student-card-row animate__animated animate__fadeInUp"
                     data-name="<?= strtolower(htmlspecialchars($row['fullname'])) ?>"
                     data-gender="<?= $row['sex'] ?>">

                    <div class="avatar-box">
                        <?= strtoupper(substr($row['fullname'], 0, 1)) ?>
                        <div class="status-dot <?= $row['is_online'] ? 'online' : 'offline' ?>"></div>
                    </div>

                    <div>
                        <span class="clickable-name" onclick="openProgressReport(<?= $row['id'] ?>, '<?= htmlspecialchars($row['fullname']) ?>')">
                            <?= htmlspecialchars($row['fullname']) ?>
                        </span>
                    </div>

                    <div style="color: var(--text-gray); font-weight: 800; font-size: 0.85rem;"><?= htmlspecialchars($row['username']) ?></div>
                    <div style="font-weight: 700; font-size: 0.85rem;"><?= $row['birthday'] ? date('M d, Y', strtotime($row['birthday'])) : 'N/A' ?></div>

                    <div style="text-align: center;">
                        <?php if ($row['sex'] === 'Male'): ?>
                            <i class="fa-solid fa-mars" style="color: #007bff; font-size: 1.1rem;" title="Male"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-venus" style="color: #ff69b4; font-size: 1.1rem;" title="Female"></i>
                        <?php endif; ?>
                    </div>

                    <div><span style="font-weight:800; font-size:0.7rem;"><?= $row['is_online']?'ONLINE':'OFFLINE' ?></span></div>

                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <a href="edit_student.php?id=<?= $row['id'] ?>" style="color: var(--accent);"><i class="fas fa-edit"></i></a>
                        <a href="javascript:void(0);" onclick="confirmDelete(<?= $row['id'] ?>)" style="color: #ff5f5f;"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <i class="fas fa-times close-btn" onclick="toggleModal('addModal', false)" style="position:absolute; top:20px; right:25px; cursor:pointer; color: var(--text-gray);"></i>
        <h2 style="font-weight: 800; color: var(--accent); margin-bottom: 20px;">Register Student</h2>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">FULL NAME</label>
                    <input type="text" name="fullname" class="form-input" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">LRN (12 NUMBERS ONLY)</label>
                    <input type="text" id="lrnInput" name="username" class="form-input" maxlength="12" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncPassword();" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">GENDER</label>
                    <select name="sex" class="form-input"><option value="Male">Male</option><option value="Female">Female</option></select>
                </div>
                <div>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">BIRTHDAY</label>
                    <input type="date" name="birthday" class="form-input" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">PASSWORD (AUTO-SJES)</label>
                    <input type="text" id="autoPassField" name="password" class="form-input" readonly required>
                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <button type="submit" name="submit_student" style="flex:1; padding:15px; border-radius:12px; border:none; background:var(--accent); color:white; font-weight:800; cursor:pointer;">Register Student</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const ProToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        showClass: { popup: 'animate__animated animate__fadeInRight' },
        hideClass: { popup: 'animate__animated animate__fadeOutRight' },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function toggleModal(id, show) {
        document.getElementById(id).style.display = show ? 'flex' : 'none';
    }

    function syncPassword() {
        const lrn = document.getElementById('lrnInput').value;
        const passField = document.getElementById('autoPassField');
        passField.value = lrn.trim() === "" ? "" : "sjes" + lrn;
    }

    function openAddModal() {
        document.getElementById('lrnInput').value = "";
        document.getElementById('autoPassField').value = "";
        toggleModal('addModal', true);
    }

    function applyFilters() {
        const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
        const genderTerm = document.getElementById('genderFilter').value;

        document.querySelectorAll('.student-card-row').forEach(row => {
            const nameMatch = row.getAttribute('data-name').includes(searchTerm);
            const genderMatch = (genderTerm === 'all' || row.getAttribute('data-gender') === genderTerm);

            if (nameMatch && genderMatch) {
                row.style.display = "grid";
            } else {
                row.style.display = "none";
            }
        });
    }

    function openProgressReport(studentId, fullName) {
        window.location.href = `student-report.php?student_id=${studentId}&name=${encodeURIComponent(fullName)}&range=all`;
    }

    <?php if($status == "success"): ?>
    ProToast.fire({ icon: 'success', title: 'Registration Successful', text: 'New student added to the section.' });
    <?php endif; ?>

    <?php if($status == "exists"): ?>
    ProToast.fire({ icon: 'error', title: 'Registration Failed', text: 'LRN/Username na ito ay nagamit na.', showClass: { popup: 'animate__animated animate__shakeX' } });
    <?php endif; ?>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
    ProToast.fire({ icon: 'success', title: 'Student Removed', text: 'Account has been successfully deleted.' });
    <?php endif; ?>

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Student?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5f5f',
            cancelButtonColor: '#A3AED0',
            confirmButtonText: 'Yes, Delete',
            background: '#ffffff',
            customClass: { popup: 'animate__animated animate__zoomIn' }
        }).then(res => {
            if(res.isConfirmed) window.location.href = 'student-page.php?delete_id=' + id;
        });
    }

    function confirmLogout() {
        Swal.fire({
            title: 'Ready to Leave?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4318FF',
            confirmButtonText: 'Logout',
            customClass: { popup: 'animate__animated animate__zoomIn' }
        }).then(res => {
            if(res.isConfirmed) window.location.href = '../login.php';
        });
    }
</script>
</body>
</html>