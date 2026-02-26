<?php
session_start();
require_once '../config/database.php';
global $pdo;

// 1. SECURITY CHECK - Teacher only
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$status = "";

// --- AJAX HANDLER FOR MASTERY UNLOCK ---
if (isset($_POST['toggle_mastery'])) {
    $s_id = $_POST['student_id'];
    $val = $_POST['val'];
    try {
        $update = $pdo->prepare("UPDATE students SET mastery_unlocked = ? WHERE id = ?");
        $update->execute([$val, $s_id]);
        echo "success";
    } catch (PDOException $e) {
        echo "error";
    }
    exit();
}

// --- AJAX HANDLER FOR RESET STUDENT PROGRESS ---
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

// --- AJAX HANDLER PARA SA INDIVIDUAL PROGRESS REPORT ---
if (isset($_POST['fetch_individual_report'])) {
    $s_id = $_POST['student_id'];
    try {
        // Kunin ang history ng ratings mula sa student_ratings table
        $stmt = $pdo->prepare("SELECT word, score, created_at FROM student_ratings WHERE student_id = ? ORDER BY created_at DESC");
        $stmt->execute([$s_id]);
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kunin ang XP at Accuracy para sa summary
        $stmt_stats = $pdo->prepare("SELECT 
                                    COALESCE(SUM(score), 0) as total_xp, 
                                    COUNT(*) as total_words,
                                    AVG(score) as avg_rating 
                                    FROM student_ratings WHERE student_id = ?");
        $stmt_stats->execute([$s_id]);
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'ratings' => $ratings,
            'xp'      => number_format($stats['total_xp']),
            'avg'     => round($stats['avg_rating'], 1),
            'count'   => $stats['total_words']
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => true]);
    }
    exit();
}

// --- ADD STUDENT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_student'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
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

// --- DELETE STUDENT LOGIC ---
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

// --- FETCH TEACHER AND STUDENTS DATA ---
try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql_students = "SELECT *, 
                    (CASE WHEN last_login > NOW() - INTERVAL 5 MINUTE THEN 1 ELSE 0 END) as is_online 
                    FROM students 
                    WHERE grade = ? 
                    AND section = ? 
                    ORDER BY fullname ASC";
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

        /* FIT TO SCREEN LOGIC */
        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; -ms-overflow-style: none; scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }

        body { display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR WITH PRO EFFECTS */
        .sidebar {
            width: 280px;
            background: var(--sidebar-dark);
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            color: white;
            flex-shrink: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .logo { font-size: 1.6rem; font-weight: 800; margin-bottom: 50px; }
        .nav-link { display: flex; align-items: center; padding: 16px 20px; color: var(--text-gray); text-decoration: none; border-radius: 20px; font-weight: 700; transition: 0.3s ease; margin-bottom: 8px; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: white; transform: translateX(8px); }
        .nav-link.active { background: var(--accent); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.3); }

        .logout-btn { margin-top: auto; color: #ff5f5f; border: 1px solid rgba(255, 95, 95, 0.2); text-align: center; cursor: pointer; padding: 12px; border-radius: 15px; font-weight: 700; transition: 0.3s; }
        .logout-btn:hover { background: #ff5f5f; color: white; }

        /* MAIN WRAPPER */
        .main-wrapper { flex: 1; padding: 25px 35px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; }
        .search-wrapper { position: relative; width: 300px; }
        .search-wrapper input { width: 100%; padding: 10px 20px; border-radius: 50px; border: none; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.03); outline: none; transition: 0.3s; }
        .search-wrapper input:focus { width: 330px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        .filter-container { display: flex; gap: 8px; margin-left: 20px; }
        .filter-btn { padding: 8px 16px; border-radius: 50px; border: 2px solid #E0E5F2; background: white; color: var(--text-dark); font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 0.85rem; }
        .filter-btn.active { background: var(--accent); color: white; border-color: var(--accent); transform: scale(1.05); }

        .section-header {
            background: linear-gradient(135deg, #4318FF 0%, #991BFF 100%);
            padding: 30px; border-radius: 24px; color: white; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0px 20px 40px rgba(67, 24, 255, 0.2); flex-shrink: 0;
        }

        /* LIST AREA */
        .list-container-parent { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .student-row-header { display: grid; grid-template-columns: 60px 2fr 1.2fr 120px 80px 100px 100px 100px; padding: 10px 25px; color: var(--text-gray); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; flex-shrink: 0; }
        .student-list-scroll { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-bottom: 20px; }
        .student-card-row { display: grid; grid-template-columns: 60px 2fr 1.2fr 120px 80px 100px 100px 100px; align-items: center; background: var(--white); padding: 12px 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); margin: 0 2px; }
        .student-card-row:hover { transform: translateY(-5px); border: 1.5px solid var(--accent); }

        .clickable-name { font-weight: 800; cursor: pointer; transition: 0.2s; padding: 5px 8px; border-radius: 8px; color: var(--text-dark); text-decoration: underline; text-decoration-color: transparent; }
        .clickable-name:hover { color: var(--accent); background: rgba(67, 24, 255, 0.08); text-decoration-color: var(--accent); }

        .avatar-box { width: 40px; height: 40px; border-radius: 10px; background: #E9EDF7; color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; position: relative; }
        .status-dot { position: absolute; bottom: -2px; right: -2px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid white; }
        .online { background: #05CD99; box-shadow: 0 0 8px #05CD99; } .offline { background: #CBD5E0; }

        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #CBD5E0; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #05CD99; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* MODAL STYLE */
        .modal-overlay { position: fixed; inset: 0; background: rgba(17, 28, 68, 0.5); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: white; padding: 40px; border-radius: 25px; width: 100%; max-width: 750px; animation: zoomIn 0.3s; position: relative; }
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.5rem; cursor: pointer; color: var(--text-gray); }

        .report-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-item { background: #F8FAFC; padding: 15px; border-radius: 15px; text-align: center; }
        .stat-item span { display: block; font-size: 0.65rem; font-weight: 800; color: var(--text-gray); text-transform: uppercase; }
        .stat-item h3 { font-size: 1.4rem; font-weight: 800; color: var(--accent); margin-top: 5px; }

        .report-table-wrapper { max-height: 280px; overflow-y: auto; border-radius: 15px; border: 1px solid #E2E8F0; }
        .report-table { width: 100%; border-collapse: collapse; text-align: left; }
        .report-table th { background: #F8FAFC; padding: 12px 15px; font-size: 0.7rem; text-transform: uppercase; color: var(--text-gray); position: sticky; top: 0; }
        .report-table td { padding: 12px 15px; border-bottom: 1px solid #F1F5F9; font-weight: 700; font-size: 0.85rem; }

        .form-input { width: 100%; padding: 12px; margin-top: 5px; margin-bottom: 15px; border-radius: 10px; border: 2px solid #E0E5F2; outline: none; }
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
        <div style="display: flex; align-items: center;">
            <div class="search-wrapper">
                <input type="text" id="studentSearch" placeholder="Search student name..." onkeyup="applyFilters()">
            </div>
            <div class="filter-container">
                <button onclick="setFilter('all')" class="filter-btn active" id="btn-all">All</button>
                <button onclick="setFilter('Male')" class="filter-btn" id="btn-male">Boys</button>
                <button onclick="setFilter('Female')" class="filter-btn" id="btn-female">Girls</button>
            </div>
        </div>
        <div style="background: white; padding: 8px 15px; border-radius: 50px; font-weight:800; font-size:0.85rem; box-shadow: var(--shadow);">
            <?= htmlspecialchars($teacher['fullname']) ?>
        </div>
    </header>

    <div class="section-header">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800;">Section <?= htmlspecialchars($teacher['section']) ?></h2>
            <p style="opacity: 0.9; font-weight: 600;">Grade <?= htmlspecialchars($teacher['grade_handle']) ?> • Monitoring <?= count($students) ?> students</p>
        </div>
        <button onclick="toggleModal('addModal', true)" style="background: white; color: var(--accent); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer;">+ Add Student</button>
    </div>

    <div class="list-container-parent">
        <div class="student-row-header">
            <div>Icon</div><div>Full Name</div><div>Username / LRN</div><div>Birthday</div><div>Gender</div><div>Status</div><div>Mastery</div><div>Action</div>
        </div>

        <div class="student-list-scroll">
            <?php foreach ($students as $row): ?>
                <div class="student-card-row animate__animated animate__fadeInUp"
                     data-name="<?= strtolower(htmlspecialchars($row['fullname'])) ?>"
                     data-gender="<?= htmlspecialchars($row['sex']) ?>">
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
                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-dark);"><?= $row['birthday'] ? date('M d, Y', strtotime($row['birthday'])) : 'N/A' ?></div>
                    <div style="text-align: center;"><i class="fa-solid <?= $row['sex'] === 'Male' ? 'fa-mars' : 'fa-venus' ?>" style="color: <?= $row['sex'] === 'Male' ? '#4facfe' : '#f093fb' ?>;"></i></div>
                    <div><span style="font-weight:800; font-size:0.7rem; color:<?= $row['is_online']?'var(--success)':'var(--text-gray)' ?>;"><?= $row['is_online']?'ONLINE':'OFFLINE' ?></span></div>
                    <div style="text-align: center;"><label class="switch"><input type="checkbox" class="mastery-toggle" data-id="<?= $row['id'] ?>" <?= ($row['mastery_unlocked'] ?? 0) ? 'checked' : '' ?>><span class="slider"></span></label></div>
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
        <i class="fas fa-times close-btn" onclick="toggleModal('addModal', false)"></i>
        <h2 style="font-weight: 800; color: var(--accent); margin-bottom: 20px;">Register Student</h2>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">FULL NAME</label>
                    <input type="text" name="fullname" class="form-input" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">LRN / USERNAME</label>
                    <input type="text" name="username" class="form-input" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">GENDER</label>
                    <select name="sex" class="form-input"><option value="Male">Male</option><option value="Female">Female</option></select>
                </div>
                <div>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">BIRTHDAY</label>
                    <input type="date" name="birthday" class="form-input" required>
                    <label style="font-weight:700; font-size:0.75rem; color:var(--text-gray);">PASSWORD</label>
                    <input type="text" name="password" class="form-input" required>
                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <button type="submit" name="submit_student" style="flex:1; padding:12px; border-radius:12px; border:none; background:var(--accent); color:white; font-weight:700; cursor:pointer;">Register Student</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="reportModal">
    <div class="modal-box">
        <i class="fas fa-times close-btn" onclick="toggleModal('reportModal', false)"></i>
        <h2 id="modalStudentName" style="font-weight: 800; color: var(--text-dark); margin-bottom: 25px;">Progress Report</h2>

        <div class="report-stats">
            <div class="stat-item"><span>Total XP</span><h3 id="statXP">0</h3></div>
            <div class="stat-item"><span>Average ⭐</span><h3 id="statAvg">0</h3></div>
            <div class="stat-item"><span>Attempts</span><h3 id="statCount">0</h3></div>
        </div>

        <div class="report-table-wrapper">
            <table class="report-table">
                <thead>
                <tr><th>Word</th><th>Rating</th><th>Date</th></tr>
                </thead>
                <tbody id="reportTableBody"></tbody>
            </table>
        </div>
    </div>
</div>



<script>
    let activeFilter = 'all';

    function setFilter(gender) {
        activeFilter = gender;
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('btn-' + (gender === 'all' ? 'all' : gender.toLowerCase())).classList.add('active');
        applyFilters();
    }

    function applyFilters() {
        const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
        document.querySelectorAll('.student-card-row').forEach(row => {
            const matchesSearch = row.getAttribute('data-name').includes(searchTerm);
            const matchesGender = (activeFilter === 'all' || row.getAttribute('data-gender') === activeFilter);
            row.style.display = (matchesSearch && matchesGender) ? "grid" : "none";
        });
    }

    function toggleModal(id, show) { document.getElementById(id).style.display = show ? 'flex' : 'none'; }

    function openProgressReport(studentId, fullName) {
        document.getElementById('modalStudentName').innerText = fullName + "'s Progress Report";
        toggleModal('reportModal', true);
        document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading...</td></tr>';

        let fd = new FormData();
        fd.append('fetch_individual_report', '1');
        fd.append('student_id', studentId);

        fetch('student-page.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                document.getElementById('statXP').innerText = data.xp;
                document.getElementById('statAvg').innerText = data.avg;
                document.getElementById('statCount').innerText = data.count;

                let html = '';
                if (data.ratings.length > 0) {
                    data.ratings.forEach(r => {
                        html += `<tr><td>${r.word}</td><td style="color:#FFB547;">${'⭐'.repeat(r.score)}</td><td style="font-size:0.75rem; color:var(--text-gray);">${r.created_at}</td></tr>`;
                    });
                } else {
                    html = '<tr><td colspan="3" style="text-align:center; padding:20px;">No records yet.</td></tr>';
                }
                document.getElementById('reportTableBody').innerHTML = html;
            });
    }

    document.querySelectorAll('.mastery-toggle').forEach(chk => {
        chk.onchange = function() {
            let fd = new FormData();
            fd.append('toggle_mastery', '1');
            fd.append('student_id', this.dataset.id);
            fd.append('val', this.checked ? 1 : 0);
            fetch('student-page.php', { method: 'POST', body: fd });
        }
    });

    function confirmDelete(id) {
        Swal.fire({ title: 'Delete Student?', text: "Hindi na ito maibabalik!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff5f5f' }).then(res => {
            if(res.isConfirmed) window.location.href = 'student-page.php?delete_id=' + id;
        });
    }

    function confirmLogout() {
        Swal.fire({ title: 'Logout?', icon: 'question', showCancelButton: true }).then(res => {
            if(res.isConfirmed) window.location.href = '../login.php';
        });
    }
</script>
</body>
</html>