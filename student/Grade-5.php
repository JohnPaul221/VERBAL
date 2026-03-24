<?php
global $pdo;
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || $_SESSION['grade'] != '5') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$username = $_SESSION['fullname'];

$play_welcome_voice = false;
if (!isset($_SESSION['welcome_voiced_grade1'])) {
    $play_welcome_voice = true;
    $_SESSION['welcome_voiced_grade1'] = true;
}

try {
    $sqlBeg = "SELECT COUNT(DISTINCT word) as beg_perfect 
                   FROM student_ratings 
                   WHERE student_id = :sid AND score = 5 
                   AND (difficulty = 'beginner' OR difficulty IS NULL)";
    $stmtBeg = $pdo->prepare($sqlBeg);
    $stmtBeg->execute(['sid' => $student_id]);
    $beg_count = $stmtBeg->fetch()['beg_perfect'] ?? 0;

    $sqlInt = "SELECT COUNT(DISTINCT word) as int_perfect 
                   FROM student_ratings 
                   WHERE student_id = :sid AND score = 5 
                   AND difficulty = 'intermediate'";
    $stmtInt = $pdo->prepare($sqlInt);
    $stmtInt->execute(['sid' => $student_id]);
    $int_count = $stmtInt->fetch()['int_perfect'] ?? 0;

    $total_perfect_words = $beg_count;
} catch (PDOException $e) {
    error_log("Unlock Logic Error: " . $e->getMessage());
    $beg_count = 0; $int_count = 0;
}

$intermediate_unlocked = ($beg_count >= 40);
$advanced_unlocked     = ($int_count >= 40);

$show_parental_note = false;
if (!isset($_SESSION['note_shown_grade1'])) {
    $show_parental_note = true;
    $_SESSION['note_shown_grade1'] = true;
}

$imageLibrary = [];
$uploadDir = "../upload/";
if (is_dir($uploadDir)) {
    $files = glob($uploadDir . "*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
    foreach ($files as $file) {
        $filename = strtolower(pathinfo($file, PATHINFO_FILENAME));
        $imageLibrary[$filename] = $file . "?v=" . filemtime($file);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VERBAL PRACTICE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4361EE;
            --success: #06D6A0;
            --kids-blue: #3A0CA3;
            --kids-yellow: #FFD166;
            --bg-gradient: linear-gradient(135deg, #E0E7FF 0%, #F8FAFC 100%);
        }

        body {
            height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            padding: 0 15px;
            color: #1E293B;
            margin: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .page-header { height: 60px; position: relative; flex-shrink: 0; }
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #EAB308; z-index: 10; color: #43380D; font-weight: bold; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #2D0A82; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #3046C5; border-radius: 10px; z-index: 10; cursor: pointer; }

        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #FFFFFF; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #CBD5E1; border: 5px solid var(--kids-blue);
            box-sizing: border-box;
        }

        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F8FAFC;
            display: flex; flex-direction: column; border: 2px solid #E2E8F0;
            align-items: center; text-align: center;
            overflow: visible;
            justify-content: space-between;
        }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F1F5F9;
            display: flex; flex-direction: column; border: 2px solid #CBD5E1;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-blue); opacity: 0.2; border-radius: 10px; }

        .word-image-box {
            width: 120px; height: 120px; border: 4px solid var(--success);
            border-radius: 15px; background: #FFFFFF; position: relative;
            background-size: cover; background-position: center; cursor: zoom-in;
            flex-shrink: 0; visibility: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .word-image-box.hidden { display: none !important; }
        #wordImage { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

        .word-image-box:hover::after {
            content: ""; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(1);
            width: 350px; height: 350px; background-image: inherit; background-size: cover;
            background-position: center; background-color: #FFFFFF; z-index: 9999;
            box-shadow: 0 0 0 100vmax rgba(255,255,255,0.8), 0 20px 50px rgba(0,0,0,0.2);
            border: 8px solid var(--kids-blue); border-radius: 25px;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes popIn {
            from { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        h2, h3 { font-size: 1.1rem; margin: 5px 0; color: #1E293B; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        #difficulty { width: 90%; padding: 8px; border-radius: 12px; border: 3px solid var(--kids-blue); font-family: inherit; font-weight: bold; margin-bottom: 5px; background: white; color: var(--kids-blue); }
        .mic-icon { width: 65px; height: 65px; background: var(--primary); border: 5px solid #7B91FB; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; margin: 5px auto; box-shadow: 0 4px 0px #3046C5; }
        .reading-material { background: #FFFFFF; border-radius: 20px; border: 3px dashed var(--success); padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }

        /* BINAGO: Liitan ang Word Display at gawing Sentence Case */
        #wordDisplay { font-size: 1.6rem; font-weight: 900; color: var(--kids-blue); text-transform: none; line-height: 1.1; text-shadow: 2px 2px #E0E7FF; }

        .transcript { font-size: 1.1rem; font-weight: bold; color: #059669; background: #ECFDF5; border: 2px solid var(--success); border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        .stats-container { display: flex; justify-content: space-around; align-items: center; background: #FFFFFF; padding: 15px 10px; border-radius: 20px; border: 5px solid var(--kids-yellow); margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #EAB308; }
        .stat-item span { display: block; font-size: 0.8rem; color: #64748B; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: #1E293B; line-height: 1; text-shadow: 1px 1px 0px #F1F5F9; }

        .progress-bar { width: 100%; height: 25px; background: #E2E8F0 !important; border-radius: 15px; border: 4px solid #CBD5E1; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: var(--success) !important; transition: width 0.6s ease-in-out; }

        /* BINAGO: Liitan ang Feedback Message at gawing Sentence Case */
        #feedbackMessage { font-size: 1.2rem; font-weight: 700; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid var(--kids-yellow); color: #1E293B; min-height: 50px; display: flex; align-items: center; justify-content: center; visibility: hidden; text-transform: none; }

        .bg-success-feedback { background: #D1FAE5 !important; color: #065F46 !important; border: 3px solid #10B981 !important; }

        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #E2E8F0; transition: color 0.3s ease; }
        .filled-star { color: var(--kids-yellow) !important; text-shadow: 0 0 5px rgba(255, 209, 102, 0.5); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; transform-origin: center; }
        @keyframes starPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.4); } 100% { transform: scale(1); opacity: 1; } }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid var(--kids-blue); background: #FFFFFF; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }
        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--kids-yellow); color: #43380D; box-shadow: 0 4px 0 #EAB308; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 4px 0 #2D0A82; width: 90%; }
        button:active { transform: translateY(2px); box-shadow: none; }
    </style>
</head>
<body>
<header class="page-header">
    <span id="usernameDisplay">Hello, <?php echo htmlspecialchars($username); ?>!</span>
    <button id="logoutBtn" onclick="window.location.href='../logout.php';">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </button>
    <button id="reportBtn" onclick="window.location.href='student_report.php';" style="position: absolute; top: 15px; right: 300px; padding: 5px 15px; font-size: 0.8rem; background: #4318FF; color: white; border: 2px solid #2F11B0; border-radius: 10px; z-index: 10; cursor: pointer; font-weight: 800;">
        <i class="fa-solid fa-chart-line"></i> View My Report
    </button>
</header>
<main>
    <div class="main-container">
        <div class="section">
            <h2>Your Turn to Talk! 🎤</h2>
            <div class="flex-space-between mb-4">
                <div>Level: <span id="levelDisplay" style="font-weight: bold; color: #10b981;">Beginner</span></div>
                <div>Progress: <span id="progressText" style="font-weight: bold; color: #10b981;">0 / 40</span></div>
            </div>
            <div id="progressBarMain" class="progress-bar">
                <div id="progressBarFill" class="progress-bar-inner"></div>
            </div>
            <label for="difficulty" style="display: block; font-size: 1.2rem; font-weight: bold; margin-bottom: 8px;">Pick a Level:</label>
            <select id="difficulty">
                <option value="beginner">🌟 Beginner (Easy Words)</option>
                <option value="intermediate" <?php echo !$intermediate_unlocked ? 'disabled' : ''; ?>>
                    👍 Intermediate <?php echo !$intermediate_unlocked ? '🔒 (Need 40 Beginner Stars)' : '🔓 Unlocked!'; ?>
                </option>
                <option value="advanced" <?php echo !$advanced_unlocked ? 'disabled' : ''; ?>>
                    🧠 Advanced <?php echo !$advanced_unlocked ? '🔒 (Need 40 Intermediate Stars)' : '🔓 Unlocked!'; ?>
                </option>
            </select>
            <div class="mic-container">
                <div id="runningTimer" style="font-size: 1.2rem; font-weight: 800; color: var(--kids-blue); margin-bottom: 5px; visibility: hidden;">
                    <i class="fa-solid fa-stopwatch"></i> Time: <span id="seconds">0.0</span>s
                </div>
                <div id="micBtn" class="mic-icon"><i class="fa-solid fa-microphone"></i></div>
            </div>
            <div id="status" class="status">Click the big circle to start!</div>
            <div id="waveformContainer" class="waveform"><canvas id="waveformCanvas"></canvas></div>
            <div class="flex-center mt-4">
                <button id="nextBtn" class="btn-secondary" disabled>👉 Next Word!</button>
                <div class="scoreboard-container">
                    <hr style="border: 1px dashed #60a5fa; margin: 8px 0;">
                    <h3>My Scoreboard 🏆</h3>
                    <div class="stats-container">
                        <div class="stat-item"><span>Attempts:</span><span id="attemptedCount" class="value">0</span></div>
                        <div class="stat-item"><span>Accuracy:</span><span id="accuracyRate" class="value">0%</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h2>The Word to Read 📖</h2>
            <div class="reading-material">
                <div class="word-image-box" id="imageBox">
                    <img id="wordImage" src="" alt="Word Image">
                </div>
                <div class="word-info" style="flex: 1;">
                    <div id="wordDisplay">Ready to begin</div>
                    <div id="phonemeDisplay" style="text-align: left; padding-left: 10px; font-size: 0.9rem; color: #64748B;"></div>
                    <div style="display: flex; justify-content: flex-start; margin-top: 5px; padding-left: 10px;">
                        <button id="playWordBtn" class="btn-primary" style="font-size: 0.8rem; padding: 5px 12px;"><i class="fa-solid fa-volume-high"></i> Listen</button>
                    </div>
                </div>
            </div>
            <h3>You Said:</h3>
            <div class="transcript" id="transcript">...</div>
            <div class="rating-container"><div id="stars"></div><div class="rating" id="rating" style="display:none;">0</div></div>
            <div class="feedback-scoreboard-container">
                <div class="feedback-container">
                    <h3>Example Sentence! 📝</h3>
                    <div id="feedbackMessage">Practice the sentence above!</div>
                    <div class="flex-center mt-4"><button id="playFeedbackBtn" class="btn-primary" style="display:none;"><i class="fa-solid fa-volume-high"></i> Listen to Sentence</button></div>
                </div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="history-section">
            <h2>Attempts History</h2>
            <div id="wordHistory">No attempts yet.</div>
        </div>
    </div>
    <script>
        // --- Constant Data from PHP ---
        const STUDENT_ID = <?php echo json_encode($student_id); ?>;
        const USERNAME = <?php echo json_encode($username); ?>;
        const PLAY_WELCOME_VOICE = <?php echo json_encode($play_welcome_voice); ?>;
        const SHOW_PARENTAL_NOTE = <?php echo json_encode($show_parental_note); ?>;
        const imageLibrary = <?php echo json_encode($imageLibrary); ?>;

        let begCountFromDB = <?php echo json_encode($beg_count ?? 0); ?>;
        let intCountFromDB = <?php echo json_encode($int_count ?? 0); ?>;

        const wordBank = {
            beginner: [
                { word: "The heart pumps blood through our veins", phonemes: ["th", "e", "h", "a", "r", "t", "p", "a", "m", "p", "s", "b", "l", "a", "d", "th", "r", "oo", "ow", "r", "v", "ay", "n", "z"], example: "The heart pumps blood through our veins to keep us alive." },
                { word: "Our lungs help us breathe fresh clean air", phonemes: ["ow", "r", "l", "a", "ng", "z", "h", "e", "l", "p", "a", "s", "b", "r", "ee", "th", "f", "r", "e", "sh", "k", "l", "ee", "n", "e", "r"], example: "Our lungs help us breathe fresh clean air every single day." },
                { word: "Plants make food using the bright yellow sun", phonemes: ["p", "l", "a", "n", "t", "s", "m", "ay", "k", "f", "oo", "d", "y", "oo", "z", "i", "ng", "th", "e", "b", "r", "ay", "t", "y", "e", "l", "o", "s", "a", "n"], example: "Plants make food using the bright yellow sun in the garden." },
                { word: "Ancient Filipinos lived in many small tribal groups", phonemes: ["ay", "n", "sh", "u", "n", "t", "f", "i", "l", "i", "p", "ee", "n", "o", "z", "l", "i", "v", "d", "i", "n", "m", "e", "n", "ee", "s", "m", "o", "l", "t", "r", "ay", "b", "a", "l", "g", "r", "oo", "p", "s"], example: "Ancient Filipinos lived in many small tribal groups called barangays." },
                { word: "Spain ruled the Philippines for three hundred years", phonemes: ["s", "p", "ay", "n", "r", "oo", "l", "d", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "f", "o", "r", "th", "r", "ee", "h", "a", "n", "d", "r", "e", "d", "y", "e", "r", "z"], example: "Spain ruled the Philippines for three hundred years in the past." },
                { word: "Estuaries are where the river meets the sea", phonemes: ["e", "s", "ch", "oo", "e", "r", "ee", "z", "a", "r", "w", "e", "r", "th", "e", "r", "i", "v", "e", "r", "m", "ee", "t", "s", "th", "e", "s", "ee"], example: "Estuaries are where the river meets the sea and fish live." },
                { word: "Mangrove trees protect the coast from big waves", phonemes: ["m", "a", "ng", "g", "r", "o", "v", "t", "r", "ee", "z", "p", "r", "o", "t", "e", "k", "t", "th", "e", "k", "o", "s", "t", "f", "r", "o", "m", "b", "i", "g", "w", "ay", "v", "z"], example: "Mangrove trees protect the coast from big waves during storms." },
                { word: "A solid object has a very definite shape", phonemes: ["a", "s", "o", "l", "i", "d", "o", "b", "j", "e", "k", "t", "h", "a", "z", "a", "v", "e", "r", "ee", "d", "e", "f", "i", "n", "i", "t", "sh", "ay", "p"], example: "A solid object has a very definite shape like a hard rock." },
                { word: "Gravity is a force that pulls objects down", phonemes: ["g", "r", "a", "v", "i", "t", "ee", "i", "z", "a", "f", "o", "r", "s", "th", "a", "t", "p", "u", "l", "z", "o", "b", "j", "e", "k", "t", "s", "d", "ow", "n"], example: "Gravity is a force that pulls objects down to the ground." },
                { word: "Proper waste management helps keep the environment clean", phonemes: ["p", "r", "o", "p", "e", "r", "w", "ay", "s", "t", "m", "a", "n", "i", "j", "m", "e", "n", "t", "h", "e", "l", "p", "s", "k", "ee", "p", "th", "e", "e", "n", "v", "ay", "r", "o", "n", "m", "e", "n", "t", "k", "l", "ee", "n"], example: "Proper waste management helps keep the environment clean for all." },
                { word: "The brain controls everything we do and think", phonemes: ["th", "e", "b", "r", "ay", "n", "k", "o", "n", "t", "r", "o", "l", "z", "e", "v", "r", "ee", "th", "ee", "ng", "w", "ee", "d", "oo", "a", "n", "d", "th", "i", "ng", "k"], example: "The brain controls everything we do and think every day." }
            ],
            intermediate: [
                { word: "The human reproductive system is essential for continuing life", phonemes: ["th", "e", "h", "y", "oo", "m", "e", "n", "r", "ee", "p", "r", "o", "d", "a", "k", "t", "i", "v", "s", "i", "s", "t", "e", "m", "i", "z", "i", "s", "e", "n", "sh", "a", "l", "f", "o", "r", "k", "o", "n", "t", "i", "n", "y", "oo", "i", "ng", "l", "ay", "f"], example: "The human reproductive system is essential for continuing life on Earth." },
                { word: "Conductors allow heat and electricity to pass through them", phonemes: ["k", "o", "n", "d", "a", "k", "t", "e", "r", "z", "a", "l", "ow", "h", "ee", "t", "a", "n", "d", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "t", "oo", "p", "a", "s", "th", "r", "oo", "th", "e", "m"], example: "Conductors allow heat and electricity to pass through them efficiently." },
                { word: "Insulators like rubber and plastic block the flow of heat", phonemes: ["i", "n", "s", "y", "oo", "l", "ay", "t", "e", "r", "z", "l", "ay", "k", "r", "a", "b", "e", "r", "a", "n", "d", "p", "l", "a", "s", "t", "i", "k", "b", "l", "o", "k", "th", "e", "f", "l", "o", "o", "v", "h", "ee", "t"], example: "Insulators like rubber and plastic block the flow of heat easily." },
                { word: "Global warming is causing the Earth temperature to rise slowly", phonemes: ["g", "l", "o", "b", "a", "l", "w", "o", "r", "m", "i", "ng", "i", "z", "k", "o", "z", "i", "ng", "th", "e", "e", "r", "th", "t", "e", "m", "p", "e", "r", "a", "ch", "e", "r", "t", "oo", "r", "ay", "z", "s", "l", "o", "l", "ee"], example: "Global warming is causing the Earth temperature to rise slowly each year." },
                { word: "The Spanish friars introduced the Catholic religion to the Filipinos", phonemes: ["th", "e", "s", "p", "a", "n", "i", "sh", "f", "r", "ay", "e", "r", "z", "i", "n", "t", "r", "o", "d", "y", "oo", "s", "t", "th", "e", "k", "a", "th", "l", "i", "k", "r", "i", "l", "i", "j", "u", "n", "t", "oo", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "o", "z"], example: "The Spanish friars introduced the Catholic religion to the Filipinos long ago." },
                { word: "Physical changes do not create new substances in the matter", phonemes: ["f", "i", "z", "i", "k", "a", "l", "ch", "ay", "n", "j", "e", "z", "d", "oo", "n", "o", "t", "k", "r", "ee", "ay", "t", "n", "y", "oo", "s", "a", "b", "s", "t", "a", "n", "s", "e", "z", "i", "n", "th", "e", "m", "a", "t", "e", "r"], example: "Physical changes do not create new substances in the matter during science experiments." },
                { word: "Chemical changes result in the formation of entirely new substances", phonemes: ["k", "e", "m", "i", "k", "a", "l", "ch", "ay", "n", "j", "e", "z", "r", "e", "z", "a", "l", "t", "i", "n", "th", "e", "f", "o", "r", "m", "ay", "sh", "u", "n", "o", "v", "e", "n", "t", "ay", "r", "l", "ee", "n", "y", "oo", "s", "a", "b", "s", "t", "a", "n", "s", "e", "z"], example: "Chemical changes result in the formation of entirely new substances like rust." },
                { word: "We should preserve estuaries because they are nurseries for fish", phonemes: ["w", "ee", "sh", "u", "d", "p", "r", "e", "z", "e", "r", "v", "e", "s", "ch", "oo", "e", "r", "ee", "z", "b", "i", "k", "o", "z", "th", "ay", "a", "r", "n", "e", "r", "s", "e", "r", "ee", "z", "f", "o", "r", "f", "i", "sh"], example: "We should preserve estuaries because they are nurseries for fish in the wild." },
                { word: "The encomienda system was used to collect taxes during Spanish era", phonemes: ["th", "e", "e", "n", "k", "o", "m", "y", "e", "n", "d", "a", "s", "i", "s", "t", "e", "m", "w", "o", "z", "y", "oo", "z", "d", "t", "oo", "k", "o", "l", "e", "k", "t", "t", "a", "k", "s", "e", "z", "d", "u", "r", "i", "ng", "s", "p", "a", "n", "i", "sh", "e", "r", "a"], example: "The encomienda system was used to collect taxes during Spanish era in history." },
                { word: "Proper posture while sitting helps prevent back and neck pain", phonemes: ["p", "r", "o", "p", "e", "r", "p", "o", "s", "ch", "e", "r", "w", "ay", "l", "s", "i", "t", "i", "ng", "h", "e", "l", "p", "s", "p", "r", "ee", "v", "e", "n", "t", "b", "a", "k", "a", "n", "d", "n", "e", "k", "p", "ay", "n"], example: "Proper posture while sitting helps prevent back and neck pain after studying." },
                { word: "The skeletal system provides support and protection for our body", phonemes: ["th", "e", "s", "k", "e", "l", "e", "t", "a", "l", "s", "i", "s", "t", "e", "m", "p", "r", "o", "v", "ay", "d", "z", "s", "a", "p", "o", "r", "t", "a", "n", "d", "p", "r", "o", "t", "e", "k", "sh", "u", "n", "f", "o", "r", "ow", "r", "b", "o", "d", "ee"], example: "The skeletal system provides support and protection for our body at all times." }
            ],
            advanced: [
                { word: "The reproductive system allows humans to produce offspring and continue generations", phonemes: ["th", "e", "r", "ee", "p", "r", "o", "d", "a", "k", "t", "i", "v", "s", "i", "s", "t", "e", "m", "a", "l", "ow", "z", "h", "y", "oo", "m", "e", "n", "z", "t", "oo", "p", "r", "o", "d", "y", "oo", "s", "o", "f", "s", "p", "r", "i", "ng", "a", "n", "d", "k", "o", "n", "t", "i", "n", "y", "oo", "j", "e", "n", "e", "r", "ay", "sh", "u", "n", "z"], example: "The reproductive system allows humans to produce offspring and continue generations on Earth." },
                { word: "Puberty is a period of rapid physical and emotional changes in children", phonemes: ["p", "y", "oo", "b", "e", "r", "t", "ee", "i", "z", "a", "p", "ee", "r", "ee", "u", "d", "o", "v", "r", "a", "p", "i", "d", "f", "i", "z", "i", "k", "a", "l", "a", "n", "d", "ee", "m", "o", "sh", "u", "n", "a", "l", "ch", "ay", "n", "j", "e", "z", "i", "n", "ch", "i", "l", "d", "r", "e", "n"], example: "Puberty is a period of rapid physical and emotional changes in children during growth." },
                { word: "Menstrual cycle is a monthly series of changes in the female body", phonemes: ["m", "e", "n", "s", "t", "r", "oo", "a", "l", "s", "ay", "k", "e", "l", "i", "n", "a", "m", "a", "n", "th", "l", "ee", "s", "ee", "r", "ee", "z", "o", "v", "ch", "ay", "n", "j", "e", "z", "i", "n", "th", "e", "f", "ee", "m", "ay", "l", "b", "o", "d", "ee"], example: "Menstrual cycle is a monthly series of changes in the female body starting at puberty." },
                { word: "Convection is the transfer of heat through the movement of many fluids", phonemes: ["k", "o", "n", "v", "e", "k", "sh", "u", "n", "i", "z", "th", "e", "t", "r", "a", "n", "s", "f", "e", "r", "o", "v", "h", "ee", "t", "th", "r", "oo", "th", "e", "m", "oo", "v", "m", "e", "n", "t", "o", "v", "m", "e", "n", "ee", "f", "l", "oo", "i", "d", "z"], example: "Convection is the transfer of heat through the movement of many fluids like boiling water." },
                { word: "Thermal energy always moves from a warmer object to a cooler object", phonemes: ["th", "e", "r", "m", "a", "l", "e", "n", "e", "r", "j", "ee", "o", "l", "w", "ay", "z", "m", "oo", "v", "z", "f", "r", "o", "m", "a", "w", "o", "r", "m", "e", "r", "o", "b", "j", "e", "k", "t", "t", "oo", "a", "k", "oo", "l", "e", "r", "o", "b", "j", "e", "k", "t"], example: "Thermal energy always moves from a warmer object to a cooler object until balanced." },
                { word: "Materials that do not conduct electricity are called insulators or very nonconductors", phonemes: ["m", "a", "t", "ee", "r", "ee", "a", "l", "z", "th", "a", "t", "d", "oo", "n", "o", "t", "k", "o", "n", "d", "a", "k", "t", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "a", "r", "k", "o", "l", "d", "i", "n", "s", "y", "oo", "l", "ay", "t", "e", "r", "z", "o", "r", "v", "e", "r", "ee", "n", "o", "n", "k", "o", "n", "d", "a", "k", "t", "e", "r", "z"], example: "Materials that do not conduct electricity are called insulators or very nonconductors in circuits." },
                { word: "Electromagnetism is the interaction between the electric currents and the magnetic fields", phonemes: ["i", "l", "e", "k", "t", "r", "o", "m", "a", "g", "n", "e", "t", "i", "z", "m", "i", "z", "th", "e", "i", "n", "t", "e", "r", "a", "k", "sh", "u", "n", "b", "i", "t", "w", "ee", "n", "th", "e", "i", "l", "e", "k", "t", "r", "i", "k", "k", "e", "r", "e", "n", "t", "s", "a", "n", "d", "th", "e", "m", "a", "g", "n", "e", "t", "i", "k", "f", "ee", "l", "d", "z"], example: "Electromagnetism is the interaction between the electric currents and the magnetic fields in motors." },
                { word: "The Spanish colonized the Philippines to spread Christianity and to find wealth", phonemes: ["th", "e", "s", "p", "a", "n", "i", "sh", "k", "o", "l", "o", "n", "ay", "z", "d", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "t", "oo", "s", "p", "r", "e", "d", "k", "r", "i", "s", "ch", "i", "a", "n", "i", "t", "ee", "a", "n", "d", "t", "oo", "f", "ay", "n", "d", "w", "e", "l", "th"], example: "The Spanish colonized the Philippines to spread Christianity and to find wealth during the exploration." },
                { word: "Ferdinand Magellan was a Portuguese explorer who led the first Spanish expedition", phonemes: ["f", "e", "r", "d", "i", "n", "a", "n", "d", "m", "a", "g", "e", "l", "a", "n", "w", "o", "z", "a", "p", "o", "r", "ch", "u", "g", "ee", "z", "i", "k", "s", "p", "l", "o", "r", "e", "r", "h", "oo", "l", "e", "d", "th", "e", "f", "e", "r", "s", "t", "s", "p", "a", "n", "i", "sh", "e", "k", "s", "p", "e", "d", "i", "sh", "u", "n"], example: "Ferdinand Magellan was a Portuguese explorer who led the first Spanish expedition to our shores." },
                { word: "The battle of Mactan showed the bravery of Lapu-Lapu against the foreign invaders", phonemes: ["th", "e", "b", "a", "t", "e", "l", "o", "v", "m", "a", "k", "t", "a", "n", "sh", "o", "d", "th", "e", "b", "r", "ay", "v", "e", "r", "ee", "o", "v", "l", "a", "p", "oo", "l", "a", "p", "oo", "a", "g", "e", "n", "s", "t", "th", "e", "f", "o", "r", "i", "n", "i", "n", "v", "ay", "d", "e", "r", "z"], example: "The battle of Mactan showed the bravery of Lapu-Lapu against the foreign invaders in history class." },
                { word: "Miguel Lopez de Legazpi established the first Spanish settlement in the Cebu island", phonemes: ["m", "i", "g", "e", "l", "l", "o", "p", "e", "z", "d", "e", "l", "e", "g", "a", "z", "p", "ee", "e", "s", "t", "a", "b", "l", "i", "sh", "t", "th", "e", "f", "e", "r", "s", "t", "s", "p", "a", "n", "i", "sh", "s", "e", "t", "e", "l", "m", "e", "n", "t", "i", "n", "th", "e", "s", "e", "b", "oo", "ay", "l", "a", "n", "d"], example: "Miguel Lopez de Legazpi established the first Spanish settlement in the Cebu island long ago." },
            ]
        };

        let masteredWords = [];
        let allProgress = {
            beginner: { word_index: 0, words_attempted: 0, words_correct: 0, shuffledList: [], isResetting: false },
            intermediate: { word_index: 0, words_attempted: 0, words_correct: 0, shuffledList: [], isResetting: false },
            advanced: { word_index: 0, words_attempted: 0, words_correct: 0, shuffledList: [], isResetting: false }
        };
        let currentDifficulty = 'beginner';
        let currentWord = null;
        let wordAttemptsHistory = [];
        let recognition, audioContext, analyser, dataArray, bufferLength, source;
        let animationId = null;
        let listening = false;
        let usEnglishVoice = null;
        let timerInterval, startTime;

        // --- UI REFERENCES ---
        const wordDisplay = document.getElementById("wordDisplay");
        const phonemeDisplay = document.getElementById("phonemeDisplay");
        const feedbackMessage = document.getElementById("feedbackMessage");
        const nextBtn = document.getElementById("nextBtn");
        const difficultySelect = document.getElementById("difficulty");
        const micBtn = document.getElementById("micBtn");
        const statusEl = document.getElementById("status");
        const transcriptEl = document.getElementById("transcript");
        const ratingEl = document.getElementById("rating");
        const starsEl = document.getElementById("stars");
        const playWordBtn = document.getElementById("playWordBtn");
        const playFeedbackBtn = document.getElementById("playFeedbackBtn");
        const wordHistoryEl = document.getElementById("wordHistory");
        const canvas = document.getElementById("waveformCanvas");
        const ctx = canvas.getContext("2d");
        const progressBarFill = document.getElementById("progressBarFill");
        const imageBox = document.getElementById("imageBox");

        // --- HELPER FUNCTIONS ---

        function checkLevelUnlock() {
            const interOption = document.querySelector('option[value="intermediate"]');
            const advOption = document.querySelector('option[value="advanced"]');

            if (begCountFromDB >= 40) {
                if (interOption) {
                    interOption.disabled = false;
                    interOption.textContent = "👍 Intermediate (UNLOCKED! 🔓)";
                }
            }
            if (intCountFromDB >= 40) {
                if (advOption) {
                    advOption.disabled = false;
                    advOption.textContent = "🧠 Advanced (UNLOCKED! 🔓)";
                }
            }
        }

        function speak(text) {
            speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            const voices = speechSynthesis.getVoices();
            let selected = voices.find(v => v.name === 'Google US English') || voices.find(v => v.lang === 'en-US');
            if (selected) { utter.voice = selected; utter.lang = selected.lang; } else { utter.lang = "en-US"; }
            speechSynthesis.speak(utter);
        }

        function triggerMysteryGame(reason = "bonus") {
            const title = reason === "struggle" ? "Quick Brain Exercise! 🧠" : "Word Master Challenge! 🏆";

            // --- LOGIC FIX: Kumuha ng salita mula sa example sentence para sa scramble ---
            const cleanSentence = currentWord.example.replace(/[.,!?;:]/g, "");
            const sentenceWords = cleanSentence.split(' ');
            let targetWord = "";

            if (currentDifficulty === 'beginner') {
                // Beginner: Kunin ang unang salita sa sentence
                targetWord = sentenceWords[0];
            } else if (currentDifficulty === 'intermediate') {
                // Intermediate: Kunin ang huling salita
                targetWord = sentenceWords[sentenceWords.length - 1];
            } else {
                // Advanced: Kunin ang pinakamahabang salita
                targetWord = sentenceWords.reduce((a, b) => a.length > b.length ? a : b);
            }

            targetWord = targetWord.toUpperCase();
            const scrambled = targetWord.split('').sort(() => 0.5 - Math.random()).join(' ');

            Swal.fire({
                title: title,
                html: `
        <div style="background: #F1F2F6; padding: 20px; border-radius: 15px; border: 2px solid #0984E3;">
            <p style="color: #2F3542; font-weight: bold; margin-bottom: 15px;">Unscramble the word from the sentence:</p>
            <h1 style="letter-spacing: 8px; font-size: 2.5rem; color: #D63031; margin: 20px 0;">${scrambled}</h1>
            <input type="text" id="scrambleInput" class="swal2-input" placeholder="TYPE ANSWER HERE" style="text-align:center; text-transform: uppercase;">
        </div>
    `,
                confirmButtonText: 'Check Answer ✅',
                confirmButtonColor: '#00B894',
                allowOutsideClick: false,
                preConfirm: () => {
                    const input = document.getElementById('scrambleInput').value.toUpperCase().trim();
                    if (input !== targetWord) {
                        Swal.showValidationMessage(`Mali! Hint: Nagsisimula ito sa letter "${targetWord[0]}"`);
                    }
                    return input === targetWord;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ icon: 'success', title: 'Excellent Logic! ⭐', showConfirmButton: false, timer: 1500 }).then(() => {
                        let p = allProgress[currentDifficulty];
                        p.word_index++;
                        saveProgressToDB();
                        loadNextWord();
                    });
                }
            });
        }

        function checkPronunciation(spoken) {
            const targetText = currentWord.word;
            const score = ratePronunciation(spoken, targetText);

            if (score === 5) {
                // FIX: Ginamit ang clean key para sa Image Library
                const imgKey = currentWord.example.toLowerCase().trim();

                if (imageLibrary[imgKey]) {
                    const imgPath = imageLibrary[imgKey];
                    const imgElement = document.getElementById("wordImage");

                    if (imgElement) imgElement.src = imgPath;
                    imageBox.style.backgroundImage = `url('${imgPath}')`;
                    imageBox.style.visibility = "visible";
                    imageBox.classList.remove("hidden");
                }

                feedbackMessage.style.visibility = "visible";

                if (!wordAttemptsHistory.includes(5)) {
                    if (currentDifficulty === 'beginner') {
                        allProgress.beginner.words_correct++;
                        begCountFromDB++;
                    } else if (currentDifficulty === 'intermediate') {
                        allProgress.intermediate.words_correct++;
                        intCountFromDB++;
                    }
                    checkLevelUnlock();
                    triggerConfetti();
                    speakRating(5);
                }

                nextBtn.disabled = false;
                feedbackMessage.textContent = "⭐ " + currentWord.example;
                feedbackMessage.className = "feedback-message bg-success-feedback";
                playFeedbackBtn.style.display = "inline-block";

            } else {
                nextBtn.disabled = true;
                speakRating(score);
            }

            allProgress[currentDifficulty].words_attempted++;
            ratingEl.textContent = score;
            renderStars(score);
            wordAttemptsHistory.push(score);
            renderWordHistory();

            if (wordAttemptsHistory.length >= 5 && !wordAttemptsHistory.includes(5)) {
                setTimeout(() => triggerMysteryGame("struggle"), 1000);
            }

            updateUIProgress();
            saveProgressToDB();
            saveRatingToDB(targetText, score);
        }

        function updateUIProgress() {
            let p = allProgress[currentDifficulty];
            let score = (currentDifficulty === 'beginner') ? begCountFromDB : (currentDifficulty === 'intermediate' ? intCountFromDB : p.words_correct);
            let displayScore = score > 40 ? 40 : score;
            document.getElementById("progressText").textContent = displayScore + " / 40";
            document.getElementById("progressBarFill").style.width = (displayScore / 40 * 100) + "%";

            document.getElementById("attemptedCount").textContent = p.words_attempted;
            const accuracy = p.words_attempted > 0 ? Math.round((score / p.words_attempted) * 100) : 0;
            document.getElementById("accuracyRate").textContent = accuracy + "%";
        }

        function loadNextWord() {
            imageBox.style.visibility = "hidden";
            imageBox.classList.add("hidden");
            const placeholderImg = document.getElementById("wordImage");
            if(placeholderImg) placeholderImg.src = "";

            feedbackMessage.style.visibility = "hidden";
            feedbackMessage.textContent = "Practice the word to see the sentence!";
            feedbackMessage.className = "feedback-message bg-initial-feedback";
            playFeedbackBtn.style.display = "none";
            transcriptEl.textContent = "...";
            ratingEl.textContent = "0";
            renderStars(0);
            nextBtn.disabled = true;
            document.getElementById("runningTimer").style.visibility = "hidden";
            document.getElementById("seconds").textContent = "0.0";

            let p = allProgress[currentDifficulty];

            if (!p.shuffledList || p.shuffledList.length === 0 || p.word_index >= p.shuffledList.length) {
                p.shuffledList = shuffleArray(wordBank[currentDifficulty]);
                p.word_index = 0;
            }
            currentWord = p.shuffledList[p.word_index];

            wordDisplay.textContent = currentWord.word;
            phonemeDisplay.textContent = currentWord.phonemes.join(" · ");

            wordAttemptsHistory = [];
            renderWordHistory();
            updateUIProgress();
        }

        function ratePronunciation(spoken, target) {
            spoken = spoken.toLowerCase().trim().replace(/[.,!?;:]/g, "");
            target = target.toLowerCase().trim().replace(/[.,!?;:]/g, "");
            if (!spoken || spoken === "...") return 0;
            if (spoken === target) return 5;

            const spokenWords = spoken.split(/\s+/);
            const targetWords = target.split(/\s+/);
            let matches = 0;
            targetWords.forEach(word => { if (spokenWords.includes(word)) matches++; });

            const ratio = matches / targetWords.length;
            if (ratio >= 0.8) return 4;
            if (ratio >= 0.5) return 3;
            if (ratio >= 0.3) return 2;
            if (ratio > 0) return 1;
            return 0;
        }

        function renderStars(score) {
            starsEl.innerHTML = "";
            for (let i = 1; i <= 5; i++) {
                const s = document.createElement("i");
                s.className = `fa-star fa-solid star ${i <= score ? 'filled-star' : 'empty-star'}`;
                starsEl.appendChild(s);
            }
        }

        function renderWordHistory() {
            wordHistoryEl.innerHTML = wordAttemptsHistory.length === 0 ? "No attempts yet." : "";
            [...wordAttemptsHistory].reverse().forEach((s, i) => {
                const div = document.createElement("div");
                div.className = "history-item";
                div.innerHTML = `<span>Attempt ${wordAttemptsHistory.length - i}:</span> `;
                for (let j = 1; j <= 5; j++) {
                    div.innerHTML += `<i class="fa-star fa-solid ${j <= s ? 'filled-star' : 'empty-star'}"></i>`;
                }
                wordHistoryEl.appendChild(div);
            });
        }

        function shuffleArray(array) {
            let shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }

        function triggerConfetti() {
            for (let i = 0; i < 15; i++) {
                const c = document.createElement('div');
                c.className = 'confetti';
                c.style.left = Math.random() * 100 + 'vw';
                c.style.backgroundColor = ['#FF6B6B', '#48DBFB', '#FECA57', '#1DD1A1', '#A29BFE'][Math.floor(Math.random() * 5)];
                c.style.width = '8px'; c.style.height = '8px'; c.style.position = 'fixed'; c.style.top = '-10px';
                c.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                document.body.appendChild(c);
                setTimeout(() => c.remove(), 5000);
            }
        }

        function speakRating(score) {
            const perfect = ["Excellent!", "Amazing!", "Wow! Pro!", "Perfect!", "Incredible!"];
            const great = ["Great job!", "Good effort!", "Nice work!"];
            const tryAgain = ["Try again!", "Keep practicing!", "You can do it!"];
            let message = score === 5 ? perfect[Math.floor(Math.random() * perfect.length)] : (score >= 4 ? great[Math.floor(Math.random() * great.length)] : tryAgain[Math.floor(Math.random() * tryAgain.length)]);
            speak(message);
        }

        async function loadProgressFromDB() {
            if (!STUDENT_ID) { loadNextWord(); return; }
            try {
                const response = await fetch(`load_progress.php?student_id=${STUDENT_ID}&difficulty=${currentDifficulty}&t=${Date.now()}`);
                const data = await response.json();
                if (data.success && data.progress) {
                    allProgress[currentDifficulty].word_index = parseInt(data.progress.word_index) || 0;
                    allProgress[currentDifficulty].words_attempted = parseInt(data.progress.words_attempted) || 0;
                    allProgress[currentDifficulty].words_correct = parseInt(data.progress.words_correct) || 0;
                }
                allProgress.beginner.shuffledList = shuffleArray(wordBank.beginner);
                allProgress.intermediate.shuffledList = shuffleArray(wordBank.intermediate);
                allProgress.advanced.shuffledList = shuffleArray(wordBank.advanced);
                updateUIProgress();
                loadNextWord();
                checkLevelUnlock();
            } catch (e) { loadNextWord(); }
        }

        function saveProgressToDB() {
            if (!STUDENT_ID) return;
            const p = allProgress[currentDifficulty];
            const formData = new FormData();
            formData.append('student_id', STUDENT_ID);
            formData.append('difficulty', currentDifficulty);
            formData.append('word_index', p.word_index);
            formData.append('words_attempted', p.words_attempted);
            formData.append('words_correct', (currentDifficulty === 'beginner') ? begCountFromDB : (currentDifficulty === 'intermediate' ? intCountFromDB : p.words_correct));
            fetch('save_progress.php', { method: 'POST', body: formData });
        }

        function saveRatingToDB(word, score) {
            if (!STUDENT_ID) return;
            const formData = new FormData();
            formData.append('student_id', STUDENT_ID);
            formData.append('username', USERNAME);
            formData.append('word', word);
            formData.append('score', score);
            formData.append('difficulty', currentDifficulty);
            formData.append('duration', document.getElementById("seconds").textContent);
            fetch('save_rating.php', { method: 'POST', body: formData });
        }

        function toggleMic() {
            if (!recognition) return;
            if (!listening) {
                listening = true;
                micBtn.classList.add("listening");
                statusEl.textContent = "Listening...";
                document.getElementById("runningTimer").style.visibility = "visible";
                startTime = Date.now();
                timerInterval = setInterval(() => {
                    document.getElementById("seconds").textContent = ((Date.now() - startTime) / 1000).toFixed(1);
                }, 100);
                recognition.start();
            } else {
                stopMicLogic();
            }
        }

        function stopMicLogic() {
            listening = false;
            micBtn.classList.remove("listening");
            clearInterval(timerInterval);
            if (recognition) recognition.stop();
        }

        function init() {
            difficultySelect.addEventListener("change", () => {
                currentDifficulty = difficultySelect.value;
                loadProgressFromDB();
            });

            nextBtn.addEventListener("click", () => {
                let p = allProgress[currentDifficulty];
                let score = (currentDifficulty === 'beginner') ? begCountFromDB : (currentDifficulty === 'intermediate' ? intCountFromDB : p.words_correct);
                if (score > 0 && score % 5 === 0) {
                    triggerMysteryGame("bonus");
                } else {
                    p.word_index++;
                    saveProgressToDB();
                    loadNextWord();
                }
            });

            playWordBtn.addEventListener("click", () => speak(currentWord.word));
            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));
            micBtn.addEventListener("click", toggleMic);

            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = true;
                recognition.onresult = (e) => {
                    for (let i = e.resultIndex; i < e.results.length; ++i) {
                        if (e.results[i].isFinal) {
                            transcriptEl.textContent = e.results[i][0].transcript;
                            checkPronunciation(e.results[i][0].transcript);
                            stopMicLogic();
                        } else {
                            transcriptEl.textContent = e.results[i][0].transcript;
                        }
                    }
                };
                recognition.onspeechend = stopMicLogic;
                recognition.onerror = stopMicLogic;
            }

            if (SHOW_PARENTAL_NOTE) {
                Swal.fire({
                    title: 'Parental Guidance 👨‍👩‍👧',
                    html: `<p>Hayaan ang bata na mag-practice mag-isa para matuto.</p>`,
                    confirmButtonText: 'Start! 🚀'
                }).then(loadProgressFromDB);
            } else {
                loadProgressFromDB();
            }
        }

        window.onload = init;
    </script>
</main>
</body>
</html>