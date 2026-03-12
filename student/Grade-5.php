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

// --- ETO YUNG BINAGO PARA HINDI PAULIT-ULIT ANG WELCOME ---
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
            /* Grade 2 Colors */
            --primary: #FF4757;
            --success: #1E90FF;
            --kids-blue: #1E90FF;
            --kids-yellow: #FFC312;
            --bg-gradient: linear-gradient(135deg, #A29BFE 0%, #FFFFFF 100%);
        }

        /* --- Full Screen Fit --- */
        body {
            height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            padding: 0 15px;
            color: #2F3542;
            margin: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- Header --- */
        .page-header { height: 60px; position: relative; flex-shrink: 0; }
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #E1B12C; z-index: 100; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #0984E3; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #B33939; border-radius: 10px; z-index: 10; cursor: pointer; }

        /* --- Main Layout --- */
        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #FFFFFF; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #CED6E0; border: 4px solid var(--kids-blue);
            box-sizing: border-box;
        }

        /* --- Sections --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F1F2F6;
            display: flex; flex-direction: column; border: 2px solid #DFE4EA;
            align-items: center; text-align: center;
            overflow: visible;
            justify-content: space-between;
        }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F1F2F6;
            display: flex; flex-direction: column; border: 2px solid #DFE4EA;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-blue); opacity: 0.2; border-radius: 10px; }

        /* --- IMAGE BOX + FULL SCREEN POPUP --- */
        .word-image-box {
            width: 120px;
            height: 120px;
            border: 4px solid var(--kids-yellow);
            border-radius: 15px;
            background: white;
            position: relative;
            background-size: cover;
            background-position: center;
            cursor: zoom-in;
            flex-shrink: 0;
            visibility: hidden; /* NAG-ADD NG VISIBILITY HIDDEN */
        }

        /* PAG WALA NAMAN IMAGE, DISPLAY NONE PARA HINDI PANGIT SA LAYOUT */
        .word-image-box.hidden {
            display: none !important;
        }

        #wordImage { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

        .word-image-box:hover::after {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            width: 350px;
            height: 350px;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            background-color: white;
            z-index: 9999;
            box-shadow: 0 0 0 100vmax rgba(0,0,0,0.6), 0 20px 50px rgba(0,0,0,0.5);
            border: 8px solid white;
            border-radius: 25px;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes popIn {
            from { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        /* --- UI ELEMENTS --- */
        h2, h3 { font-size: 1.1rem; margin: 5px 0; color: #2F3542; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        #difficulty { width: 90%; padding: 8px; border-radius: 12px; border: 3px solid var(--kids-blue); font-family: inherit; font-weight: bold; margin-bottom: 5px; }
        .mic-icon { width: 65px; height: 65px; background: var(--primary); border: 5px solid #FF7F50; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; margin: 5px auto; box-shadow: 0 4px 0px #B33939; }
        .reading-material { background: #EBF7FF; border-radius: 20px; border: 3px dashed var(--kids-blue); padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }
        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: #2F3542; text-transform: uppercase; line-height: 1.1; }
        .transcript { font-size: 1.1rem; font-weight: bold; color: #218C74; background: #E3FCEF; border: 2px solid #2ED573; border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        /* --- PRO SCOREBOARD --- */
        .stats-container { display: flex; justify-content: space-around; align-items: center; background: white; padding: 15px 10px; border-radius: 20px; border: 5px solid var(--kids-blue); margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #0984E3; }
        .stat-item span { display: block; font-size: 0.8rem; color: #57606F; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: var(--kids-blue); line-height: 1; text-shadow: 1px 1px 0px #f1f2f6; }

        /* --- PROGRESS BAR --- */
        .progress-bar { width: 100%; height: 25px; background: #FFFFFF !important; border-radius: 15px; border: 4px solid #DFE4EA; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: #1E90FF !important; transition: width 0.6s ease-in-out; }

        /* --- PRO SENTENCE --- */
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid #DFE4EA; color: #2F3542; min-height: 60px; display: flex; align-items: center; justify-content: center; visibility: hidden; }
        .bg-success-feedback { background: #E3F2FD !important; color: #1976D2 !important; border: 3px solid #2196F3 !important; }

        /* --- PRO STARS --- */
        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #CED6E0; transition: color 0.3s ease; }
        .filled-star { color: #FFC312 !important; text-shadow: 0 0 15px rgba(255, 195, 0, 0.6); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, starShine 2s infinite linear; transform-origin: center; }
        @keyframes starPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.4); } 100% { transform: scale(1); opacity: 1; } }
        @keyframes starShine { 0% { text-shadow: 0 0 10px rgba(255, 195, 0, 0.5); } 50% { text-shadow: 0 0 20px rgba(255, 195, 0, 0.8), 0 0 30px rgba(255, 255, 255, 0.5); } 100% { text-shadow: 0 0 10px rgba(255, 195, 0, 0.5); } }

        /* --- PRO UI MYSTERY MATCHING --- */
        #memoryGrid { background: rgba(255, 255, 255, 0.5); padding: 20px; border-radius: 20px; border: 2px solid #EBF7FF; }
        .memory-card {
            width: 80px; height: 80px;
            background: linear-gradient(145deg, var(--kids-blue), #0984E3);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 2.8rem;
            border-radius: 18px; cursor: pointer; box-shadow: 0 6px 0 #0652DD, 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.3); position: relative;
        }
        .memory-card:hover { transform: translateY(-5px) scale(1.05); filter: brightness(1.1); }
        .memory-card::before { content: "?"; font-weight: 900; opacity: 0.5; }
        .memory-card.flipped { background: white; color: #2F3542; border: 4px solid var(--kids-yellow); box-shadow: 0 6px 0 #E1B12C; animation: cardAppear 0.4s ease-out forwards; }
        .memory-card.flipped::before { content: ""; }
        .memory-card.matched { background: #1DD1A1; border-color: #10ac84; animation: matchedSuccess 0.5s ease-out forwards; pointer-events: none; }
        @keyframes cardAppear { 0% { transform: scale(0.5) rotateY(0deg); opacity: 0; } 100% { transform: scale(1) rotateY(180deg); opacity: 1; } }
        @keyframes matchedSuccess { 0% { transform: scale(1); } 50% { transform: scale(1.2); filter: brightness(1.5); } 100% { transform: scale(0); opacity: 0; visibility: hidden; } }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid var(--kids-blue); background: #EBF7FF; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }
        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--kids-yellow); color: #574B15; box-shadow: 0 4px 0 #E1B12C; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 4px 0 #0984E3; width: 90%; }
        button:active { transform: translateY(2px); box-shadow: none; }
    </style>
</head>
<body>
<header class="page-header">
    <span id="usernameDisplay">Hello, <?php echo htmlspecialchars($username); ?>!</span>
    <button id="logoutBtn" onclick="window.location.href='../logout.php';">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </button>
</header>
<main>
    <div class="main-container">
        <div class="section">
            <h2>Your Turn to Talk! 🎤</h2>
            <div class="flex-space-between mb-4">
                <div>Level: <span id="levelDisplay" style="font-weight: bold; color: #10b981;">Beginner</span></div>
                <div>Progress: <span id="progressText" style="font-weight: bold; color: #10b981;">0 / 10</span></div>
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
                        <div class="stat-item"><span>Words Attempted:</span><span id="attemptedCount" class="value">0</span></div>
                        <div class="stat-item"><span>Accuracy:</span><span id="accuracyRate" class="value">0%</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="section">
            <h2>The Word to Read 📖</h2>
            <div class="reading-material" style="display: flex; align-items: center; gap: 15px; padding: 15px;">
                <div class="word-image-box" id="imageBox">
                    <div id="imageSpinner" class="loading-spinner"></div>
                    <img id="wordImage" src="https://via.placeholder.com/120" alt="Word Image" fetchpriority="high" loading="lazy">
                </div>
                <div class="word-info" style="flex: 1;">
                    <div id="wordDisplay" style="text-align: left; padding-left: 10px;">Ready to begin</div>
                    <div id="phonemeDisplay" style="text-align: left; padding-left: 10px;"></div>
                    <div style="display: flex; justify-content: flex-start; margin-top: 5px; padding-left: 10px;">
                        <button id="playWordBtn" class="btn-primary" style="font-size: 0.8rem; padding: 5px 12px;"><i class="fa-solid fa-volume-high"></i> Listen</button>
                    </div>
                </div>
            </div>
            <h3>You Said:</h3>
            <div class="transcript" id="transcript">...</div>
            <div class="rating-container"><div class="rating" id="rating">0</div><div id="stars"></div></div>
            <div class="feedback-scoreboard-container">
                <div class="feedback-container">
                    <h3>Example Sentence! 📝</h3>
                    <div id="feedbackMessage" class="feedback-message bg-initial-feedback">Practice sentence will appear here when you start.</div>
                    <div class="flex-center mt-4"><button id="playFeedbackBtn" class="btn-primary"><i class="fa-solid fa-volume-high"></i> Listen to Sentence</button></div>
                </div>
            </div>
        </div>
        <div class="divider"></div>
        <div class="history-section">
            <h2>Attempts History</h2>
            <div id="wordHistory">No attempts yet.</div>
            <div id="historyMessage" class="history-message"></div>
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

        const wordBankGrade5 = {
            beginner: [
                { word: "The heart pumps blood through our veins", phonemes: ["th", "e", "h", "a", "r", "t", "p", "a", "m", "p", "s", "b", "l", "a", "d", "th", "r", "oo", "ow", "r", "v", "ay", "n", "z"] },
                { word: "Our lungs help us breathe fresh clean air", phonemes: ["ow", "r", "l", "a", "ng", "z", "h", "e", "l", "p", "a", "s", "b", "r", "ee", "th", "f", "r", "e", "sh", "k", "l", "ee", "n", "e", "r"] },
                { word: "Plants make food using the bright yellow sun", phonemes: ["p", "l", "a", "n", "t", "s", "m", "ay", "k", "f", "oo", "d", "y", "oo", "z", "i", "ng", "th", "e", "b", "r", "ay", "t", "y", "e", "l", "o", "s", "a", "n"] },
                { word: "Ancient Filipinos lived in many small tribal groups", phonemes: ["ay", "n", "sh", "u", "n", "t", "f", "i", "l", "i", "p", "ee", "n", "o", "z", "l", "i", "v", "d", "i", "n", "m", "e", "n", "ee", "s", "m", "o", "l", "t", "r", "ay", "b", "a", "l", "g", "r", "oo", "p", "s"] },
                { word: "Spain ruled the Philippines for three hundred years", phonemes: ["s", "p", "ay", "n", "r", "oo", "l", "d", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "f", "o", "r", "th", "r", "ee", "h", "a", "n", "d", "r", "e", "d", "y", "e", "r", "z"] },
                { word: "Estuaries are where the river meets the sea", phonemes: ["e", "s", "ch", "oo", "e", "r", "ee", "z", "a", "r", "w", "e", "r", "th", "e", "r", "i", "v", "e", "r", "m", "ee", "t", "s", "th", "e", "s", "ee"] },
                { word: "Mangrove trees protect the coast from big waves", phonemes: ["m", "a", "ng", "g", "r", "o", "v", "t", "r", "ee", "z", "p", "r", "o", "t", "e", "k", "t", "th", "e", "k", "o", "s", "t", "f", "r", "o", "m", "b", "i", "g", "w", "ay", "v", "z"] },
                { word: "A solid object has a very definite shape", phonemes: ["a", "s", "o", "l", "i", "d", "o", "b", "j", "e", "k", "t", "h", "a", "z", "a", "v", "e", "r", "ee", "d", "e", "f", "i", "n", "i", "t", "sh", "ay", "p"] },
                { word: "Gravity is a force that pulls objects down", phonemes: ["g", "r", "a", "v", "i", "t", "ee", "i", "z", "a", "f", "o", "r", "s", "th", "a", "t", "p", "u", "l", "z", "o", "b", "j", "e", "k", "t", "s", "d", "ow", "n"] },
                { word: "Proper waste management helps keep the environment clean", phonemes: ["p", "r", "o", "p", "e", "r", "w", "ay", "s", "t", "m", "a", "n", "i", "j", "m", "e", "n", "t", "h", "e", "l", "p", "s", "k", "ee", "p", "th", "e", "e", "n", "v", "ay", "r", "o", "n", "m", "e", "n", "t", "k", "l", "ee", "n"] },
                { word: "The brain controls everything we do and think", phonemes: ["th", "e", "b", "r", "ay", "n", "k", "o", "n", "t", "r", "o", "l", "z", "e", "v", "r", "ee", "th", "ee", "ng", "w", "ee", "d", "oo", "a", "n", "d", "th", "i", "ng", "k"] },
                { word: "We should respect our national heroes and flags", phonemes: ["w", "ee", "sh", "u", "d", "r", "e", "s", "p", "e", "k", "t", "ow", "r", "n", "a", "sh", "u", "n", "a", "l", "h", "ee", "r", "o", "z", "a", "n", "d", "f", "l", "a", "g", "z"] },
                { word: "The equator is a line around the Earth", phonemes: ["th", "e", "ee", "k", "w", "ay", "t", "e", "r", "i", "z", "a", "l", "ay", "n", "a", "r", "ow", "n", "d", "th", "e", "e", "r", "th"] },
                { word: "Moisture in the air can form into clouds", phonemes: ["m", "oy", "s", "ch", "e", "r", "i", "n", "th", "e", "e", "r", "k", "a", "n", "f", "o", "r", "m", "i", "n", "t", "oo", "k", "l", "ow", "d", "z"] },
                { word: "Always wash your hands with soap and water", phonemes: ["o", "l", "w", "ay", "z", "w", "o", "sh", "y", "o", "r", "h", "a", "n", "d", "z", "w", "i", "th", "s", "o", "p", "a", "n", "d", "w", "o", "t", "e", "r"] },
                { word: "An ecosystem has both living and nonliving things", phonemes: ["a", "n", "ee", "k", "o", "s", "i", "s", "t", "e", "m", "h", "a", "z", "b", "o", "th", "l", "i", "v", "i", "ng", "a", "n", "d", "n", "o", "n", "l", "i", "v", "i", "ng", "th", "ee", "ng", "z"] },
                { word: "The sun is at the center of system", phonemes: ["th", "e", "s", "a", "n", "i", "z", "a", "t", "th", "e", "s", "e", "n", "t", "e", "r", "o", "v", "s", "i", "s", "t", "e", "m"] },
                { word: "He bought a new dictionary for English class", phonemes: ["h", "ee", "b", "o", "t", "a", "n", "y", "oo", "d", "i", "k", "sh", "u", "n", "e", "r", "ee", "f", "o", "r", "i", "ng", "g", "l", "i", "sh", "k", "l", "a", "s"] },
                { word: "Sound travels faster through solid objects and liquids", phonemes: ["s", "ow", "n", "d", "t", "r", "a", "v", "e", "l", "z", "f", "a", "s", "t", "e", "r", "th", "r", "oo", "s", "o", "l", "i", "d", "o", "b", "j", "e", "k", "t", "s", "a", "n", "d", "l", "i", "k", "w", "i", "d", "z"] },
                { word: "The moon goes around our planet every month", phonemes: ["th", "e", "m", "oo", "n", "g", "o", "z", "a", "r", "ow", "n", "ow", "r", "p", "l", "a", "n", "e", "t", "e", "v", "r", "ee", "m", "a", "n", "th"] },
                { word: "Honesty is a very important value in life", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "i", "z", "a", "v", "e", "r", "ee", "i", "m", "p", "o", "r", "t", "a", "n", "t", "v", "a", "l", "y", "oo", "i", "n", "l", "ay", "f"] },
                { word: "Friction makes it hard to move heavy things", phonemes: ["f", "r", "i", "k", "sh", "u", "n", "m", "ay", "k", "s", "i", "t", "h", "a", "r", "d", "t", "oo", "m", "oo", "v", "h", "e", "v", "ee", "th", "ee", "ng", "z"] },
                { word: "We use our eyes to see beautiful colors", phonemes: ["w", "ee", "y", "oo", "z", "ow", "r", "ay", "z", "t", "oo", "s", "ee", "b", "y", "oo", "t", "i", "f", "u", "l", "k", "a", "l", "e", "r", "z"] },
                { word: "Computers help us finish our work very fast", phonemes: ["k", "o", "m", "p", "y", "oo", "t", "e", "r", "z", "h", "e", "l", "p", "a", "s", "f", "i", "n", "i", "sh", "ow", "r", "w", "e", "r", "k", "v", "e", "r", "ee", "f", "a", "s", "t"] },
                { word: "A sentence begins with a big capital letter", phonemes: ["a", "s", "e", "n", "t", "e", "n", "s", "b", "e", "g", "i", "n", "z", "w", "i", "th", "a", "b", "i", "g", "k", "a", "p", "i", "t", "a", "l", "l", "e", "t", "e", "r"] },
                { word: "The teacher explained the lesson very well today", phonemes: ["th", "e", "t", "ee", "ch", "e", "r", "i", "k", "s", "p", "l", "ay", "n", "d", "th", "e", "l", "e", "s", "o", "n", "v", "e", "r", "ee", "w", "e", "l", "t", "u", "d", "ay"] },
                { word: "I will study hard to get good grades", phonemes: ["ay", "w", "i", "l", "s", "t", "a", "d", "ee", "h", "a", "r", "d", "t", "oo", "g", "e", "t", "g", "u", "d", "g", "r", "ay", "d", "z"] },
                { word: "Always respect the opinions of other people now", phonemes: ["o", "l", "w", "ay", "z", "r", "e", "s", "p", "e", "k", "t", "th", "e", "o", "p", "i", "n", "y", "u", "n", "z", "o", "v", "a", "th", "e", "r", "p", "ee", "p", "e", "l", "n", "ow"] },
                { word: "We should save water at home and school", phonemes: ["w", "ee", "sh", "u", "d", "s", "ay", "v", "w", "o", "t", "e", "r", "a", "t", "h", "o", "m", "a", "n", "d", "s", "k", "oo", "l"] },
                { word: "Plants grow towards the direction of the light", phonemes: ["p", "l", "a", "n", "t", "s", "g", "r", "o", "t", "o", "w", "e", "r", "d", "z", "th", "e", "d", "i", "r", "e", "k", "sh", "u", "n", "o", "v", "th", "e", "l", "ay", "t"] },
                { word: "The weather is very hot during summer time", phonemes: ["th", "e", "w", "e", "th", "e", "r", "i", "z", "v", "e", "r", "ee", "h", "o", "t", "d", "u", "r", "i", "ng", "s", "a", "m", "e", "r", "t", "ay", "m"] },
                { word: "A butterfly has four stages in its life", phonemes: ["a", "b", "a", "t", "e", "r", "f", "l", "ay", "h", "a", "z", "f", "o", "r", "s", "t", "ay", "j", "e", "z", "i", "n", "i", "t", "s", "l", "ay", "f"] },
                { word: "Read books to learn more about the world", phonemes: ["r", "ee", "d", "b", "u", "k", "s", "t", "oo", "l", "e", "r", "n", "m", "o", "r", "a", "b", "ow", "t", "th", "e", "w", "e", "r", "l", "d"] },
                { word: "Magnets can pull some objects that have iron", phonemes: ["m", "a", "g", "n", "e", "t", "s", "k", "a", "n", "p", "u", "l", "s", "a", "m", "o", "b", "j", "e", "k", "t", "s", "th", "a", "t", "h", "a", "v", "ay", "e", "r", "n"] },
                { word: "Light travels in a very straight path today", phonemes: ["l", "ay", "t", "t", "r", "a", "v", "e", "l", "z", "i", "n", "a", "v", "e", "r", "ee", "s", "t", "r", "ay", "t", "p", "a", "th", "t", "u", "d", "ay"] },
                { word: "Heat can make some solid things melt fast", phonemes: ["h", "ee", "t", "k", "a", "n", "m", "ay", "k", "s", "a", "m", "s", "o", "l", "i", "d", "th", "ee", "ng", "z", "m", "e", "l", "t", "f", "a", "s", "t"] },
                { word: "The ocean is full of many salt water", phonemes: ["th", "e", "o", "sh", "u", "n", "i", "z", "f", "u", "l", "o", "v", "m", "e", "n", "ee", "s", "o", "l", "t", "w", "o", "t", "e", "r"] },
                { word: "Fungi and bacteria are very small living things", phonemes: ["f", "a", "n", "j", "ay", "a", "n", "d", "b", "a", "k", "t", "e", "r", "ee", "a", "a", "r", "v", "e", "r", "ee", "s", "m", "o", "l", "l", "i", "v", "i", "ng", "th", "ee", "ng", "z"] },
                { word: "Rainwater flows into the rivers and the seas", phonemes: ["r", "ay", "n", "w", "o", "t", "e", "r", "f", "l", "o", "z", "i", "n", "t", "oo", "th", "e", "r", "i", "v", "e", "r", "z", "a", "n", "d", "th", "e", "s", "ee", "z"] },
                { word: "We breathe out gas called carbon dioxide now", phonemes: ["w", "ee", "b", "r", "ee", "th", "ow", "t", "g", "a", "s", "k", "o", "l", "d", "k", "a", "r", "b", "u", "n", "d", "ay", "o", "k", "s", "ay", "d", "n", "ow"] },
                { word: "The liver helps clean the blood every day", phonemes: ["th", "e", "l", "i", "v", "e", "r", "h", "e", "l", "p", "s", "k", "l", "ee", "n", "th", "e", "b", "l", "a", "d", "e", "v", "r", "ee", "d", "ay"] },
                { word: "The stomach digests the food that we eat", phonemes: ["th", "e", "s", "t", "a", "m", "u", "k", "d", "ay", "j", "e", "s", "t", "s", "th", "e", "f", "oo", "d", "th", "a", "t", "w", "ee", "ee", "t"] },
                { word: "Always wear a clean shirt for school today", phonemes: ["o", "l", "w", "ay", "z", "w", "e", "r", "a", "k", "l", "ee", "n", "sh", "e", "r", "t", "f", "o", "r", "s", "k", "oo", "l", "t", "u", "d", "ay"] },
                { word: "Recycle paper and plastic to help the Earth", phonemes: ["r", "ee", "s", "ay", "k", "e", "l", "p", "ay", "p", "e", "r", "a", "n", "d", "p", "l", "a", "s", "t", "i", "k", "t", "oo", "h", "e", "l", "p", "th", "e", "e", "r", "th"] },
                { word: "A group of stars is called a constellation", phonemes: ["a", "g", "r", "oo", "p", "o", "v", "s", "t", "a", "r", "z", "i", "z", "k", "o", "l", "d", "a", "k", "o", "n", "s", "t", "e", "l", "ay", "sh", "u", "n"] },
                { word: "The compass needle points to the north pole", phonemes: ["th", "e", "k", "a", "m", "p", "a", "s", "n", "ee", "d", "e", "l", "p", "oy", "n", "t", "s", "t", "oo", "th", "e", "n", "o", "r", "th", "p", "o", "l"] },
                { word: "Our ancestors used boats to travel between islands", phonemes: ["ow", "r", "a", "n", "s", "e", "s", "t", "e", "r", "z", "y", "oo", "z", "d", "b", "o", "t", "s", "t", "oo", "t", "r", "a", "v", "e", "l", "b", "i", "t", "w", "ee", "n", "ay", "l", "a", "n", "d", "z"] },
                { word: "The Philippines has many beautiful and green forests", phonemes: ["th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "h", "a", "z", "m", "e", "n", "ee", "b", "y", "oo", "t", "i", "f", "u", "l", "a", "n", "d", "g", "r", "ee", "n", "f", "o", "r", "e", "s", "t", "s"] },
                { word: "We use a map to find many places", phonemes: ["w", "ee", "y", "oo", "z", "a", "m", "a", "p", "t", "oo", "f", "ay", "n", "d", "m", "e", "n", "ee", "p", "l", "ay", "s", "e", "z"] },
                { word: "The sun is the source of all energy", phonemes: ["th", "e", "s", "a", "n", "i", "z", "th", "e", "s", "o", "r", "s", "o", "v", "o", "l", "e", "n", "e", "r", "j", "ee"] }
            ],
            intermediate: [
                { word: "The human reproductive system is essential for continuing life", phonemes: ["th", "e", "h", "y", "oo", "m", "e", "n", "r", "ee", "p", "r", "o", "d", "a", "k", "t", "i", "v", "s", "i", "s", "t", "e", "m", "i", "z", "i", "s", "e", "n", "sh", "a", "l", "f", "o", "r", "k", "o", "n", "t", "i", "n", "y", "oo", "i", "ng", "l", "ay", "f"] },
                { word: "Conductors allow heat and electricity to pass through them", phonemes: ["k", "o", "n", "d", "a", "k", "t", "e", "r", "z", "a", "l", "ow", "h", "ee", "t", "a", "n", "d", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "t", "oo", "p", "a", "s", "th", "r", "oo", "th", "e", "m"] },
                { word: "Insulators like rubber and plastic block the flow of heat", phonemes: ["i", "n", "s", "y", "oo", "l", "ay", "t", "e", "r", "z", "l", "ay", "k", "r", "a", "b", "e", "r", "a", "n", "d", "p", "l", "a", "s", "t", "i", "k", "b", "l", "o", "k", "th", "e", "f", "l", "o", "o", "v", "h", "ee", "t"] },
                { word: "Global warming is causing the Earth temperature to rise slowly", phonemes: ["g", "l", "o", "b", "a", "l", "w", "o", "r", "m", "i", "ng", "i", "z", "k", "o", "z", "i", "ng", "th", "e", "e", "r", "th", "t", "e", "m", "p", "e", "r", "a", "ch", "e", "r", "t", "oo", "r", "ay", "z", "s", "l", "o", "l", "ee"] },
                { word: "The Spanish friars introduced the Catholic religion to the Filipinos", phonemes: ["th", "e", "s", "p", "a", "n", "i", "sh", "f", "r", "ay", "e", "r", "z", "i", "n", "t", "r", "o", "d", "y", "oo", "s", "t", "th", "e", "k", "a", "th", "l", "i", "k", "r", "i", "l", "i", "j", "u", "n", "t", "oo", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "o", "z"] },
                { word: "Physical changes do not create new substances in the matter", phonemes: ["f", "i", "z", "i", "k", "a", "l", "ch", "ay", "n", "j", "e", "z", "d", "oo", "n", "o", "t", "k", "r", "ee", "ay", "t", "n", "y", "oo", "s", "a", "b", "s", "t", "a", "n", "s", "e", "z", "i", "n", "th", "e", "m", "a", "t", "e", "r"] },
                { word: "Chemical changes result in the formation of entirely new substances", phonemes: ["k", "e", "m", "i", "k", "a", "l", "ch", "ay", "n", "j", "e", "z", "r", "e", "z", "a", "l", "t", "i", "n", "th", "e", "f", "o", "r", "m", "ay", "sh", "u", "n", "o", "v", "e", "n", "t", "ay", "r", "l", "ee", "n", "y", "oo", "s", "a", "b", "s", "t", "a", "n", "s", "e", "z"] },
                { word: "We should preserve estuaries because they are nurseries for fish", phonemes: ["w", "ee", "sh", "u", "d", "p", "r", "e", "z", "e", "r", "v", "e", "s", "ch", "oo", "e", "r", "ee", "z", "b", "i", "k", "o", "z", "th", "ay", "a", "r", "n", "e", "r", "s", "e", "r", "ee", "z", "f", "o", "r", "f", "i", "sh"] },
                { word: "The encomienda system was used to collect taxes during Spanish era", phonemes: ["th", "e", "e", "n", "k", "o", "m", "y", "e", "n", "d", "a", "s", "i", "s", "t", "e", "m", "w", "o", "z", "y", "oo", "z", "d", "t", "oo", "k", "o", "l", "e", "k", "t", "t", "a", "k", "s", "e", "z", "d", "u", "r", "i", "ng", "s", "p", "a", "n", "i", "sh", "e", "r", "a"] },
                { word: "Proper posture while sitting helps prevent back and neck pain", phonemes: ["p", "r", "o", "p", "e", "r", "p", "o", "s", "ch", "e", "r", "w", "ay", "l", "s", "i", "t", "i", "ng", "h", "e", "l", "p", "s", "p", "r", "ee", "v", "e", "n", "t", "b", "a", "k", "a", "n", "d", "n", "e", "k", "p", "ay", "n"] },
                { word: "The skeletal system provides support and protection for our body", phonemes: ["th", "e", "s", "k", "e", "l", "e", "t", "a", "l", "s", "i", "s", "t", "e", "m", "p", "r", "o", "v", "ay", "d", "z", "s", "a", "p", "o", "r", "t", "a", "n", "d", "p", "r", "o", "t", "e", "k", "sh", "u", "n", "f", "o", "r", "ow", "r", "b", "o", "d", "ee"] },
                { word: "Muscles contract and relax to allow movement of our limbs", phonemes: ["m", "a", "s", "e", "l", "z", "k", "o", "n", "t", "r", "a", "k", "t", "a", "n", "d", "r", "ee", "l", "a", "k", "s", "t", "oo", "a", "l", "ow", "m", "oo", "v", "m", "e", "n", "t", "o", "v", "ow", "r", "l", "i", "m", "z"] },
                { word: "Intertidal zones are covered by water during the high tide", phonemes: ["i", "n", "t", "e", "r", "t", "ay", "d", "a", "l", "z", "o", "n", "z", "a", "r", "k", "a", "v", "e", "r", "d", "b", "ay", "w", "o", "t", "e", "r", "d", "u", "r", "i", "ng", "th", "e", "h", "ay", "t", "ay", "d"] },
                { word: "Coral reefs provide a home for thousands of marine species", phonemes: ["k", "o", "r", "a", "l", "r", "ee", "f", "s", "p", "r", "o", "v", "ay", "d", "a", "h", "o", "m", "f", "o", "r", "th", "ow", "z", "a", "n", "d", "z", "o", "v", "m", "a", "r", "ee", "n", "s", "p", "ee", "sh", "ee", "z"] },
                { word: "The digestive system breaks down food into very tiny pieces", phonemes: ["th", "e", "d", "ay", "j", "e", "s", "t", "i", "v", "s", "i", "s", "t", "e", "m", "b", "r", "ay", "k", "s", "d", "ow", "n", "f", "oo", "d", "i", "n", "t", "oo", "v", "e", "r", "ee", "t", "ay", "n", "ee", "p", "ee", "s", "e", "z"] },
                { word: "Vitamins and minerals are essential for a very healthy body", phonemes: ["v", "ay", "t", "a", "m", "i", "n", "z", "a", "n", "d", "m", "i", "n", "e", "r", "a", "l", "z", "a", "r", "i", "s", "e", "n", "sh", "a", "l", "f", "o", "r", "a", "v", "e", "r", "ee", "h", "e", "l", "th", "ee", "b", "o", "d", "ee"] },
                { word: "Light can be reflected or absorbed by different various objects", phonemes: ["l", "ay", "t", "k", "a", "n", "b", "ee", "r", "e", "f", "l", "e", "k", "t", "e", "d", "o", "r", "a", "b", "s", "o", "r", "b", "d", "b", "ay", "d", "i", "f", "e", "r", "e", "n", "t", "v", "e", "r", "ee", "u", "s", "o", "b", "j", "e", "k", "t", "s"] },
                { word: "Refraction is the bending of light as it passes through", phonemes: ["r", "ee", "f", "r", "a", "k", "sh", "u", "n", "i", "z", "th", "e", "b", "e", "n", "d", "i", "ng", "o", "v", "l", "ay", "t", "a", "z", "i", "t", "p", "a", "s", "e", "z", "th", "r", "oo"] },
                { word: "The static electricity can cause your hair to stand up", phonemes: ["th", "e", "s", "t", "a", "t", "i", "k", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "k", "a", "n", "k", "o", "z", "y", "o", "r", "h", "e", "r", "t", "oo", "s", "t", "a", "n", "d", "a", "p"] },
                { word: "A circuit is a path through which the electricity flows", phonemes: ["a", "s", "e", "r", "k", "i", "t", "i", "z", "a", "p", "a", "th", "th", "r", "oo", "w", "i", "ch", "th", "e", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "f", "l", "o", "z"] },
                { word: "Series circuits have only one path for the electric current", phonemes: ["s", "ee", "r", "ee", "z", "s", "e", "r", "k", "i", "t", "s", "h", "a", "v", "o", "n", "l", "ee", "w", "a", "n", "p", "a", "th", "f", "o", "r", "th", "e", "i", "l", "e", "k", "t", "r", "i", "k", "k", "e", "r", "e", "n", "t"] },
                { word: "Parallel circuits have multiple paths for the electricity to flow", phonemes: ["p", "a", "r", "a", "l", "e", "l", "s", "e", "r", "k", "i", "t", "s", "h", "a", "v", "m", "a", "l", "t", "i", "p", "e", "l", "p", "a", "th", "s", "f", "o", "r", "th", "e", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "t", "oo", "f", "l", "o"] },
                { word: "Always handle electrical appliances with dry hands to stay safe", phonemes: ["o", "l", "w", "ay", "z", "h", "a", "n", "d", "e", "l", "i", "l", "e", "k", "t", "r", "i", "k", "a", "l", "a", "p", "l", "ay", "a", "n", "s", "e", "z", "w", "i", "th", "d", "r", "ay", "h", "a", "n", "d", "z", "t", "oo", "s", "t", "ay", "s", "ay", "f"] },
                { word: "The moon does not have its own light to shine", phonemes: ["th", "e", "m", "oo", "n", "d", "a", "z", "n", "o", "t", "h", "a", "v", "i", "t", "s", "o", "n", "l", "ay", "t", "t", "oo", "sh", "ay", "n"] },
                { word: "Astronauts wear space suits to breathe in the outer space", phonemes: ["a", "s", "t", "r", "o", "n", "o", "t", "s", "w", "e", "r", "s", "p", "ay", "s", "s", "oo", "t", "s", "t", "oo", "b", "r", "ee", "th", "i", "n", "th", "e", "ow", "t", "e", "r", "s", "p", "ay", "s"] },
                { word: "The force of gravity is weaker on the small moon", phonemes: ["th", "e", "f", "o", "r", "s", "o", "v", "g", "r", "a", "v", "i", "t", "ee", "i", "z", "w", "ee", "k", "e", "r", "o", "n", "th", "e", "s", "m", "o", "l", "m", "oo", "n"] },
                { word: "Stars are massive balls of burning gas in the universe", phonemes: ["s", "t", "a", "r", "z", "a", "r", "m", "a", "s", "i", "v", "b", "o", "l", "z", "o", "v", "b", "e", "r", "n", "i", "ng", "g", "a", "s", "i", "n", "th", "e", "y", "oo", "n", "i", "v", "e", "r", "s"] },
                { word: "Our solar system is part of the Milky Way galaxy", phonemes: ["ow", "r", "s", "o", "l", "e", "r", "s", "i", "s", "t", "e", "m", "i", "z", "p", "a", "r", "t", "o", "v", "th", "e", "m", "i", "l", "k", "ee", "w", "ay", "g", "a", "l", "a", "k", "s", "ee"] },
                { word: "We use telescopes to see distant stars and other planets", phonemes: ["w", "ee", "y", "oo", "z", "t", "e", "l", "e", "s", "k", "o", "p", "s", "t", "oo", "s", "ee", "d", "i", "s", "t", "a", "n", "t", "s", "t", "a", "r", "z", "a", "n", "d", "a", "th", "e", "r", "p", "l", "a", "n", "e", "t", "s"] },
                { word: "The phases of the moon change throughout the whole month", phonemes: ["th", "e", "f", "ay", "z", "e", "z", "o", "v", "th", "e", "m", "oo", "n", "ch", "ay", "n", "j", "th", "r", "oo", "ow", "t", "th", "e", "h", "o", "l", "m", "a", "n", "th"] },
                { word: "An eclipse occurs when a celestial body blocks the light", phonemes: ["a", "n", "i", "k", "l", "i", "p", "s", "o", "k", "e", "r", "z", "w", "e", "n", "a", "s", "e", "l", "e", "s", "ch", "a", "l", "b", "o", "d", "ee", "b", "l", "o", "k", "s", "th", "e", "l", "ay", "t"] },
                { word: "Proper waste disposal is necessary to prevent the water pollution", phonemes: ["p", "r", "o", "p", "e", "r", "w", "ay", "s", "t", "d", "i", "s", "p", "o", "z", "a", "l", "i", "z", "n", "e", "s", "e", "s", "e", "r", "ee", "t", "oo", "p", "r", "ee", "v", "e", "n", "t", "th", "e", "w", "o", "t", "e", "r", "p", "o", "l", "oo", "sh", "u", "n"] },
                { word: "Biodegradable materials can be broken down by many small fungi", phonemes: ["b", "ay", "o", "d", "i", "g", "r", "ay", "d", "a", "b", "e", "l", "m", "a", "t", "ee", "r", "ee", "a", "l", "z", "k", "a", "n", "b", "ee", "b", "r", "o", "k", "e", "n", "d", "ow", "n", "b", "ay", "m", "e", "n", "ee", "s", "m", "o", "l", "f", "a", "n", "j", "ay"] },
                { word: "Non-biodegradable waste should be recycled or disposed of properly", phonemes: ["n", "o", "n", "b", "ay", "o", "d", "i", "g", "r", "ay", "d", "a", "b", "e", "l", "w", "ay", "s", "t", "sh", "u", "d", "b", "ee", "r", "ee", "s", "ay", "k", "e", "l", "d", "o", "r", "d", "i", "s", "p", "o", "z", "d", "o", "v", "p", "r", "o", "p", "e", "r", "l", "ee"] },
                { word: "Global warming results from too much greenhouse gases in air", phonemes: ["g", "l", "o", "b", "a", "l", "w", "o", "r", "m", "i", "ng", "r", "e", "z", "a", "l", "t", "s", "f", "r", "o", "m", "t", "oo", "m", "a", "ch", "g", "r", "ee", "n", "h", "ow", "s", "g", "a", "s", "e", "z", "i", "n", "e", "r"] },
                { word: "The ozone layer protects our world from harmful solar radiation", phonemes: ["th", "e", "o", "z", "o", "n", "l", "ay", "e", "r", "p", "r", "o", "t", "e", "k", "t", "s", "ow", "r", "w", "e", "r", "l", "d", "f", "r", "o", "m", "h", "a", "r", "m", "f", "u", "l", "s", "o", "l", "e", "r", "r", "ay", "d", "ee", "ay", "sh", "u", "n"] },
                { word: "We should use reusable bags to reduce the plastic waste", phonemes: ["w", "ee", "sh", "u", "d", "y", "oo", "z", "r", "ee", "y", "oo", "z", "a", "b", "e", "l", "b", "a", "g", "z", "t", "oo", "r", "ee", "d", "y", "oo", "s", "th", "e", "p", "l", "a", "s", "t", "i", "k", "w", "ay", "s", "t"] },
                { word: "Always turn off the lights when leaving the school room", phonemes: ["o", "l", "w", "ay", "z", "t", "e", "r", "n", "o", "f", "th", "e", "l", "ay", "t", "s", "w", "e", "n", "l", "ee", "v", "i", "ng", "th", "e", "s", "k", "oo", "l", "r", "oo", "m"] },
                { word: "Planting trees helps to absorb the excess of carbon dioxide", phonemes: ["p", "l", "a", "n", "t", "i", "ng", "t", "r", "ee", "z", "h", "e", "l", "p", "s", "t", "oo", "a", "b", "s", "o", "r", "b", "th", "e", "i", "k", "s", "e", "s", "o", "v", "k", "a", "r", "b", "u", "n", "d", "ay", "o", "k", "s", "ay", "d"] },
                { word: "Conservation of energy is important for our planet very future", phonemes: ["k", "o", "n", "s", "e", "r", "v", "ay", "sh", "u", "n", "o", "v", "e", "n", "e", "r", "j", "ee", "i", "z", "i", "m", "p", "o", "r", "t", "a", "n", "t", "f", "o", "r", "ow", "r", "p", "l", "a", "n", "e", "t", "v", "e", "r", "ee", "f", "y", "oo", "ch", "e", "r"] },
                { word: "Fresh water is a very limited resource on our world", phonemes: ["f", "r", "e", "sh", "w", "o", "t", "e", "r", "i", "z", "a", "v", "e", "r", "ee", "l", "i", "m", "i", "t", "e", "d", "r", "ee", "s", "o", "r", "s", "f", "o", "r", "ow", "r", "w", "e", "r", "l", "d"] },
                { word: "The human brain is the center of the nervous system", phonemes: ["th", "e", "h", "y", "oo", "m", "e", "n", "b", "r", "ay", "n", "i", "z", "th", "e", "s", "e", "n", "t", "e", "r", "o", "v", "th", "e", "n", "e", "r", "v", "u", "s", "s", "i", "s", "t", "e", "m"] },
                { word: "Nerves transmit signals from the body to the human brain", phonemes: ["n", "e", "r", "v", "z", "t", "r", "a", "n", "s", "m", "i", "t", "s", "i", "g", "n", "a", "l", "z", "f", "r", "o", "m", "th", "e", "b", "o", "d", "ee", "t", "oo", "th", "e", "h", "y", "oo", "m", "e", "n", "b", "r", "ay", "n"] },
                { word: "A healthy diet is crucial for maintaining our strong body", phonemes: ["a", "h", "e", "l", "th", "ee", "d", "ay", "e", "t", "i", "z", "k", "r", "oo", "sh", "a", "l", "f", "o", "r", "m", "ay", "n", "t", "ay", "n", "i", "ng", "ow", "r", "s", "t", "r", "o", "ng", "b", "o", "d", "ee"] },
                { word: "Regular exercise strengthens our muscles and also our human heart", phonemes: ["r", "e", "g", "y", "oo", "l", "e", "r", "e", "k", "s", "e", "r", "s", "ay", "z", "s", "t", "r", "e", "ng", "th", "e", "n", "z", "ow", "r", "m", "a", "s", "e", "l", "z", "a", "n", "d", "o", "l", "s", "o", "ow", "r", "h", "y", "oo", "m", "e", "n", "h", "a", "r", "t"] },
                { word: "Get enough sleep to help your body recover and grow", phonemes: ["g", "e", "t", "i", "n", "a", "f", "s", "l", "ee", "p", "t", "oo", "h", "e", "l", "p", "y", "o", "r", "b", "o", "d", "ee", "r", "ee", "k", "a", "v", "e", "r", "a", "n", "d", "g", "r", "o"] },
                { word: "Proper hygiene prevents the spread of many harmful various germs", phonemes: ["p", "r", "o", "p", "e", "r", "h", "ay", "j", "ee", "n", "p", "r", "ee", "v", "e", "n", "t", "s", "th", "e", "s", "p", "r", "e", "d", "o", "v", "m", "e", "n", "ee", "h", "a", "r", "m", "f", "u", "l", "v", "e", "r", "ee", "u", "s", "j", "e", "r", "m", "z"] },
                { word: "Honesty and respect are core values for every single person", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "a", "n", "d", "r", "e", "s", "p", "e", "k", "t", "a", "r", "k", "o", "r", "v", "a", "l", "y", "oo", "z", "f", "o", "r", "e", "v", "r", "ee", "s", "i", "ng", "g", "e", "l", "p", "e", "r", "s", "u", "n"] },
                { word: "We celebrate our rich culture through festivals and many songs", phonemes: ["w", "ee", "s", "e", "l", "e", "b", "r", "ay", "t", "ow", "r", "r", "i", "ch", "k", "a", "l", "ch", "e", "r", "th", "r", "oo", "f", "e", "s", "t", "i", "v", "a", "l", "z", "a", "n", "d", "m", "e", "n", "ee", "s", "o", "ng", "z"] },
                { word: "Unity in the community makes our country very much stronger", phonemes: ["y", "oo", "n", "i", "t", "ee", "i", "n", "th", "e", "k", "o", "m", "y", "oo", "n", "i", "t", "ee", "m", "ay", "k", "s", "ow", "r", "k", "a", "n", "t", "r", "ee", "v", "e", "r", "ee", "m", "a", "ch", "s", "t", "r", "o", "ng", "g", "e", "r"] }
            ],
            advanced: [
                { word: "The reproductive system allows humans to produce offspring and continue generations", phonemes: ["th", "e", "r", "ee", "p", "r", "o", "d", "a", "k", "t", "i", "v", "s", "i", "s", "t", "e", "m", "a", "l", "ow", "z", "h", "y", "oo", "m", "e", "n", "z", "t", "oo", "p", "r", "o", "d", "y", "oo", "s", "o", "f", "s", "p", "r", "i", "ng", "a", "n", "d", "k", "o", "n", "t", "i", "n", "y", "oo", "j", "e", "n", "e", "r", "ay", "sh", "u", "n", "z"] },
                { word: "Puberty is a period of rapid physical and emotional changes in children", phonemes: ["p", "y", "oo", "b", "e", "r", "t", "ee", "i", "z", "a", "p", "ee", "r", "ee", "u", "d", "o", "v", "r", "a", "p", "i", "d", "f", "i", "z", "i", "k", "a", "l", "a", "n", "d", "ee", "m", "o", "sh", "u", "n", "a", "l", "ch", "ay", "n", "j", "e", "z", "i", "n", "ch", "i", "l", "d", "r", "e", "n"] },
                { word: "Menstrual cycle is a monthly series of changes in the female body", phonemes: ["m", "e", "n", "s", "t", "r", "oo", "a", "l", "s", "ay", "k", "e", "l", "i", "n", "a", "m", "a", "n", "th", "l", "ee", "s", "ee", "r", "ee", "z", "o", "v", "ch", "ay", "n", "j", "e", "z", "i", "n", "th", "e", "f", "ee", "m", "ay", "l", "b", "o", "d", "ee"] },
                { word: "Convection is the transfer of heat through the movement of many fluids", phonemes: ["k", "o", "n", "v", "e", "k", "sh", "u", "n", "i", "z", "th", "e", "t", "r", "a", "n", "s", "f", "e", "r", "o", "v", "h", "ee", "t", "th", "r", "oo", "th", "e", "m", "oo", "v", "m", "e", "n", "t", "o", "v", "m", "e", "n", "ee", "f", "l", "oo", "i", "d", "z"] },
                { word: "Thermal energy always moves from a warmer object to a cooler object", phonemes: ["th", "e", "r", "m", "a", "l", "e", "n", "e", "r", "j", "ee", "o", "l", "w", "ay", "z", "m", "oo", "v", "z", "f", "r", "o", "m", "a", "w", "o", "r", "m", "e", "r", "o", "b", "j", "e", "k", "t", "t", "oo", "a", "k", "oo", "l", "e", "r", "o", "b", "j", "e", "k", "t"] },
                { word: "Materials that do not conduct electricity are called insulators or very nonconductors", phonemes: ["m", "a", "t", "ee", "r", "ee", "a", "l", "z", "th", "a", "t", "d", "oo", "n", "o", "t", "k", "o", "n", "d", "a", "k", "t", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "a", "r", "k", "o", "l", "d", "i", "n", "s", "y", "oo", "l", "ay", "t", "e", "r", "z", "o", "r", "v", "e", "r", "ee", "n", "o", "n", "k", "o", "n", "d", "a", "k", "t", "e", "r", "z"] },
                { word: "Electromagnetism is the interaction between the electric currents and the magnetic fields", phonemes: ["i", "l", "e", "k", "t", "r", "o", "m", "a", "g", "n", "e", "t", "i", "z", "m", "i", "z", "th", "e", "i", "n", "t", "e", "r", "a", "k", "sh", "u", "n", "b", "i", "t", "w", "ee", "n", "th", "e", "i", "l", "e", "k", "t", "r", "i", "k", "k", "e", "r", "e", "n", "t", "s", "a", "n", "d", "th", "e", "m", "a", "g", "n", "e", "t", "i", "k", "f", "ee", "l", "d", "z"] },
                { word: "The Spanish colonized the Philippines to spread Christianity and to find wealth", phonemes: ["th", "e", "s", "p", "a", "n", "i", "sh", "k", "o", "l", "o", "n", "ay", "z", "d", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "t", "oo", "s", "p", "r", "e", "d", "k", "r", "i", "s", "ch", "i", "a", "n", "i", "t", "ee", "a", "n", "d", "t", "oo", "f", "ay", "n", "d", "w", "e", "l", "th"] },
                { word: "Ferdinand Magellan was a Portuguese explorer who led the first Spanish expedition", phonemes: ["f", "e", "r", "d", "i", "n", "a", "n", "d", "m", "a", "g", "e", "l", "a", "n", "w", "o", "z", "a", "p", "o", "r", "ch", "u", "g", "ee", "z", "i", "k", "s", "p", "l", "o", "r", "e", "r", "h", "oo", "l", "e", "d", "th", "e", "f", "e", "r", "s", "t", "s", "p", "a", "n", "i", "sh", "e", "k", "s", "p", "e", "d", "i", "sh", "u", "n"] },
                { word: "The battle of Mactan showed the bravery of Lapu-Lapu against the foreign invaders", phonemes: ["th", "e", "b", "a", "t", "e", "l", "o", "v", "m", "a", "k", "t", "a", "n", "sh", "o", "d", "th", "e", "b", "r", "ay", "v", "e", "r", "ee", "o", "v", "l", "a", "p", "oo", "l", "a", "p", "oo", "a", "g", "e", "n", "s", "t", "th", "e", "f", "o", "r", "i", "n", "i", "n", "v", "ay", "d", "e", "r", "z"] },
                { word: "Miguel Lopez de Legazpi established the first Spanish settlement in the Cebu island", phonemes: ["m", "i", "g", "e", "l", "l", "o", "p", "e", "z", "d", "e", "l", "e", "g", "a", "z", "p", "ee", "e", "s", "t", "a", "b", "l", "i", "sh", "t", "th", "e", "f", "e", "r", "s", "t", "s", "p", "a", "n", "i", "sh", "s", "e", "t", "e", "l", "m", "e", "n", "t", "i", "n", "th", "e", "s", "e", "b", "oo", "ay", "l", "a", "n", "d"] },
                { word: "The Galleon Trade connected the Philippines to Mexico for over two hundred years", phonemes: ["th", "e", "g", "a", "l", "y", "u", "n", "t", "r", "ay", "d", "k", "o", "n", "e", "k", "t", "e", "d", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "t", "oo", "m", "e", "k", "s", "i", "k", "o", "f", "o", "r", "o", "v", "e", "r", "t", "oo", "h", "a", "n", "d", "r", "e", "d", "y", "e", "r", "z"] },
                { word: "Physical fitness includes cardiovascular endurance and muscular strength and also great flexibility", phonemes: ["f", "i", "z", "i", "k", "a", "l", "f", "i", "t", "n", "e", "s", "i", "n", "k", "l", "oo", "d", "z", "k", "a", "r", "d", "ee", "o", "v", "a", "s", "k", "y", "oo", "l", "e", "r", "e", "n", "d", "y", "oo", "r", "a", "n", "s", "a", "n", "d", "m", "a", "s", "k", "y", "oo", "l", "e", "r", "s", "t", "r", "e", "ng", "th", "a", "n", "d", "o", "l", "s", "o", "g", "r", "ay", "t", "f", "l", "e", "k", "s", "i", "b", "i", "l", "i", "t", "ee"] },
                { word: "Proper waste management like composting can reduce the amount of household trash", phonemes: ["p", "r", "o", "p", "e", "r", "w", "ay", "s", "t", "m", "a", "n", "i", "j", "m", "e", "n", "t", "l", "ay", "k", "k", "o", "m", "p", "o", "s", "t", "i", "ng", "k", "a", "n", "r", "ee", "d", "y", "oo", "s", "th", "e", "a", "m", "ow", "n", "t", "o", "v", "h", "ow", "s", "h", "o", "l", "d", "t", "r", "a", "sh"] },
                { word: "Pollution in estuaries affects the health of many marine organisms and local ecosystems", phonemes: ["p", "o", "l", "oo", "sh", "u", "n", "i", "n", "e", "s", "ch", "oo", "e", "r", "ee", "z", "a", "f", "e", "k", "t", "s", "th", "e", "h", "e", "l", "th", "o", "v", "m", "e", "n", "ee", "m", "a", "r", "ee", "n", "o", "r", "g", "a", "n", "i", "z", "m", "z", "a", "n", "d", "l", "o", "k", "a", "l", "ee", "k", "o", "s", "i", "s", "t", "e", "m", "z"] },
                { word: "Global warming occurs when greenhouse gases trap heat in the atmosphere of Earth", phonemes: ["g", "l", "o", "b", "a", "l", "w", "o", "r", "m", "i", "ng", "o", "k", "e", "r", "z", "w", "e", "n", "g", "r", "ee", "n", "h", "ow", "s", "g", "a", "s", "e", "z", "t", "r", "a", "p", "h", "ee", "t", "i", "n", "th", "e", "a", "t", "m", "o", "s", "f", "ee", "r", "o", "v", "e", "r", "th"] },
                { word: "Soil erosion can be prevented by planting trees and building many strong stone terraces", phonemes: ["s", "oy", "l", "i", "r", "o", "zh", "u", "n", "k", "a", "n", "b", "ee", "p", "r", "ee", "v", "e", "n", "t", "e", "d", "b", "ay", "p", "l", "a", "n", "t", "i", "ng", "t", "r", "ee", "z", "a", "n", "d", "b", "i", "l", "d", "i", "ng", "m", "e", "n", "ee", "s", "t", "r", "o", "ng", "s", "t", "o", "n", "t", "e", "r", "a", "s", "e", "z"] },
                { word: "The human heart has four chambers that work together to circulate the oxygenated blood", phonemes: ["th", "e", "h", "y", "oo", "m", "e", "n", "h", "a", "r", "t", "h", "a", "z", "f", "o", "r", "ch", "ay", "m", "b", "e", "r", "z", "th", "a", "t", "w", "e", "r", "k", "t", "oo", "g", "e", "th", "e", "r", "t", "oo", "s", "e", "r", "k", "y", "oo", "l", "ay", "t", "th", "e", "o", "k", "s", "i", "j", "e", "n", "ay", "t", "e", "d", "b", "l", "a", "d"] },
                { word: "Large intestine absorbs water from the remaining undigested food before it leaves the body", phonemes: ["l", "a", "r", "j", "i", "n", "t", "e", "s", "t", "i", "n", "a", "b", "s", "o", "r", "b", "z", "w", "o", "t", "e", "r", "f", "r", "o", "m", "th", "e", "r", "ee", "m", "ay", "n", "i", "ng", "a", "n", "d", "ay", "j", "e", "s", "t", "e", "d", "f", "oo", "d", "b", "e", "f", "o", "r", "i", "t", "l", "ee", "v", "z", "th", "e", "b", "o", "d", "ee"] },
                { word: "Our nerves send signals to the brain which processes information and also decides the actions", phonemes: ["ow", "r", "n", "e", "r", "v", "z", "s", "e", "n", "d", "s", "i", "g", "n", "a", "l", "z", "t", "oo", "th", "e", "b", "r", "ay", "n", "w", "i", "ch", "p", "r", "o", "s", "e", "s", "e", "z", "i", "n", "f", "o", "r", "m", "ay", "sh", "u", "n", "a", "n", "d", "o", "l", "s", "o", "d", "ee", "s", "ay", "d", "z", "th", "e", "a", "k", "sh", "u", "n", "z"] },
                { word: "Regular exercise and a balanced diet are necessary for the growth of every healthy child", phonemes: ["r", "e", "g", "y", "oo", "l", "e", "r", "e", "k", "s", "e", "r", "s", "ay", "z", "a", "n", "d", "a", "b", "a", "l", "a", "n", "s", "t", "d", "ay", "e", "t", "a", "r", "n", "e", "s", "e", "s", "e", "r", "ee", "f", "o", "r", "th", "e", "g", "r", "o", "th", "o", "v", "e", "v", "r", "ee", "h", "e", "l", "th", "ee", "ch", "ay", "l", "d"] },
                { word: "Refraction of light can make a straw look broken when it is in a glass", phonemes: ["r", "ee", "f", "r", "a", "k", "sh", "u", "n", "o", "v", "l", "ay", "t", "k", "a", "n", "m", "ay", "k", "a", "s", "t", "r", "o", "l", "u", "k", "b", "r", "o", "k", "e", "n", "w", "e", "n", "i", "t", "i", "z", "i", "n", "a", "g", "l", "a", "s"] },
                { word: "Light and sound travel as waves but light is much faster than the sound in air", phonemes: ["l", "ay", "t", "a", "n", "d", "s", "ow", "n", "d", "t", "r", "a", "v", "e", "l", "a", "z", "w", "ay", "v", "z", "b", "a", "t", "l", "ay", "t", "i", "z", "m", "a", "ch", "f", "a", "s", "t", "e", "r", "th", "a", "n", "th", "e", "s", "ow", "n", "d", "i", "n", "e", "r"] },
                { word: "A complete electric circuit allows electricity to flow and power up various devices and lights", phonemes: ["a", "k", "o", "m", "p", "l", "ee", "t", "i", "l", "e", "k", "t", "r", "i", "k", "s", "e", "r", "k", "i", "t", "a", "l", "ow", "z", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "t", "oo", "f", "l", "o", "a", "n", "d", "p", "ow", "e", "r", "a", "p", "v", "e", "r", "ee", "u", "s", "d", "i", "v", "ay", "s", "e", "z", "a", "n", "d", "l", "ay", "t", "s"] },
                { word: "Static electricity can be produced by rubbing two different materials against each other very fast", phonemes: ["s", "t", "a", "t", "i", "k", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "k", "a", "n", "b", "ee", "p", "r", "o", "d", "y", "oo", "s", "t", "b", "ay", "r", "a", "b", "i", "ng", "t", "oo", "d", "i", "f", "e", "r", "e", "n", "t", "m", "a", "t", "ee", "r", "ee", "a", "l", "z", "a", "g", "e", "n", "s", "t", "ee", "ch", "a", "th", "e", "r", "v", "e", "r", "ee", "f", "a", "s", "t"] },
                { word: "Conduction is the transfer of heat through a solid material from one molecule to another", phonemes: ["k", "o", "n", "d", "a", "k", "sh", "u", "n", "i", "z", "th", "e", "t", "r", "a", "n", "s", "f", "e", "r", "o", "v", "h", "ee", "t", "th", "r", "oo", "a", "s", "o", "l", "i", "d", "m", "a", "t", "ee", "r", "ee", "a", "l", "f", "r", "o", "m", "w", "a", "n", "m", "o", "l", "e", "k", "y", "oo", "l", "t", "oo", "a", "n", "a", "th", "e", "r"] },
                { word: "The moon goes through many phases as it revolves around the planet Earth every single month", phonemes: ["th", "e", "m", "oo", "n", "g", "o", "z", "th", "r", "oo", "m", "e", "n", "ee", "f", "ay", "z", "e", "z", "a", "z", "i", "t", "r", "i", "v", "o", "l", "v", "z", "a", "r", "ow", "n", "th", "e", "p", "l", "a", "n", "e", "t", "e", "r", "th", "e", "v", "r", "ee", "s", "i", "ng", "g", "e", "l", "m", "a", "n", "th"] },
                { word: "Constellations are groups of stars that form a recognizable pattern in the dark night sky", phonemes: ["k", "o", "n", "s", "t", "e", "l", "ay", "sh", "u", "n", "z", "a", "r", "g", "r", "oo", "p", "s", "o", "v", "s", "t", "a", "r", "z", "th", "a", "t", "f", "o", "r", "m", "a", "r", "e", "k", "o", "g", "n", "ay", "z", "a", "b", "e", "l", "p", "a", "t", "e", "r", "n", "i", "n", "th", "e", "d", "a", "r", "k", "n", "ay", "t", "s", "k", "ay"] },
                { word: "A solar eclipse happens when the moon passes between the bright sun and the planet Earth", phonemes: ["a", "s", "o", "l", "e", "r", "i", "k", "l", "i", "p", "s", "h", "a", "p", "e", "n", "z", "w", "e", "n", "th", "e", "m", "oo", "n", "p", "a", "s", "e", "z", "b", "i", "t", "w", "ee", "n", "th", "e", "b", "r", "ay", "t", "s", "a", "n", "a", "n", "d", "th", "e", "p", "l", "a", "n", "e", "t", "e", "r", "th"] },
                { word: "A lunar eclipse occurs when the Earth passes between the sun and the full round moon", phonemes: ["a", "l", "y", "oo", "n", "e", "r", "i", "k", "l", "i", "p", "s", "o", "k", "e", "r", "z", "w", "e", "n", "th", "e", "e", "r", "th", "p", "a", "s", "e", "z", "b", "i", "t", "w", "ee", "n", "th", "e", "s", "a", "n", "a", "n", "d", "th", "e", "f", "u", "l", "r", "ow", "n", "d", "m", "oo", "n"] },
                { word: "Biodiversity is very important for the stability and health of any local environment and ecosystem", phonemes: ["b", "ay", "o", "d", "ay", "v", "e", "r", "s", "i", "t", "ee", "i", "z", "v", "e", "r", "ee", "i", "m", "p", "o", "r", "t", "a", "n", "t", "f", "o", "r", "th", "e", "s", "t", "a", "b", "i", "l", "i", "t", "ee", "a", "n", "d", "h", "e", "l", "th", "o", "v", "e", "n", "ee", "l", "o", "k", "a", "l", "e", "n", "v", "ay", "r", "o", "n", "m", "e", "n", "t", "a", "n", "d", "ee", "k", "o", "s", "i", "s", "t", "e", "m"] },
                { word: "Recycling reduces the need for raw materials and saves a lot of energy for our world", phonemes: ["r", "ee", "s", "ay", "k", "l", "i", "ng", "r", "ee", "d", "y", "oo", "s", "e", "z", "th", "e", "n", "ee", "d", "f", "o", "r", "r", "o", "m", "a", "t", "ee", "r", "ee", "a", "l", "z", "a", "n", "d", "s", "ay", "v", "z", "a", "l", "o", "t", "o", "v", "e", "n", "e", "r", "j", "ee", "f", "o", "r", "ow", "r", "w", "e", "r", "l", "d"] },
                { word: "The ozone layer acts like a shield that blocks many harmful ultraviolet rays from the sun", phonemes: ["th", "e", "o", "z", "o", "n", "l", "ay", "e", "r", "a", "k", "t", "s", "l", "ay", "k", "a", "sh", "ee", "l", "d", "th", "a", "t", "b", "l", "o", "k", "s", "m", "e", "n", "ee", "h", "a", "r", "m", "f", "u", "l", "a", "l", "t", "r", "a", "v", "ay", "o", "l", "e", "t", "r", "ay", "z", "f", "r", "o", "m", "th", "e", "s", "a", "n"] },
                { word: "Always dispose of your household waste properly to keep our beautiful surroundings clean and green", phonemes: ["o", "l", "w", "ay", "z", "d", "i", "s", "p", "o", "z", "o", "v", "y", "o", "r", "h", "ow", "s", "h", "o", "l", "d", "w", "ay", "s", "t", "p", "r", "o", "p", "e", "r", "l", "ee", "t", "oo", "k", "ee", "p", "ow", "r", "b", "y", "oo", "t", "i", "f", "u", "l", "s", "a", "r", "ow", "n", "d", "i", "ng", "z", "k", "l", "ee", "n", "a", "n", "d", "g", "r", "ee", "n"] },
                { word: "Conserving the natural resources means using them wisely and not wasting any of them at all", phonemes: ["k", "o", "n", "s", "e", "r", "v", "i", "ng", "th", "e", "n", "a", "ch", "u", "r", "a", "l", "r", "ee", "s", "o", "r", "s", "e", "z", "m", "ee", "n", "z", "y", "oo", "z", "i", "ng", "th", "e", "m", "w", "ay", "z", "l", "ee", "a", "n", "d", "n", "o", "t", "w", "ay", "s", "t", "i", "ng", "e", "n", "ee", "o", "v", "th", "e", "m", "a", "t", "o", "l"] },
                { word: "Deforestation can lead to many landslides and floods during the heavy rainy seasons in our country", phonemes: ["d", "ee", "f", "o", "r", "e", "s", "t", "ay", "sh", "u", "n", "k", "a", "n", "l", "ee", "d", "t", "oo", "m", "e", "n", "ee", "l", "a", "n", "d", "s", "l", "ay", "d", "z", "a", "n", "d", "f", "l", "a", "d", "z", "d", "u", "r", "i", "ng", "th", "e", "h", "e", "v", "ee", "r", "ay", "n", "ee", "s", "ee", "z", "u", "n", "z", "i", "n", "ow", "r", "k", "a", "n", "t", "r", "ee"] },
                { word: "Our national hero Doctor Jose Rizal was a very brilliant man who fought for our freedom", phonemes: ["ow", "r", "n", "a", "sh", "u", "n", "a", "l", "h", "ee", "r", "o", "d", "o", "k", "t", "o", "r", "h", "o", "z", "ay", "r", "i", "z", "a", "l", "w", "o", "z", "a", "v", "e", "r", "ee", "b", "r", "i", "l", "y", "u", "n", "t", "m", "a", "n", "h", "oo", "f", "o", "t", "f", "o", "r", "ow", "r", "f", "r", "ee", "d", "u", "m"] },
                { word: "Spanish culture has a deep influence on the traditions and the language of the Filipino people", phonemes: ["s", "p", "a", "n", "i", "sh", "k", "a", "l", "ch", "e", "r", "h", "a", "z", "a", "d", "ee", "p", "i", "n", "f", "l", "oo", "e", "n", "s", "o", "n", "th", "e", "t", "r", "a", "d", "i", "sh", "u", "n", "z", "a", "n", "d", "th", "e", "l", "a", "ng", "g", "w", "i", "j", "o", "v", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "o", "p", "ee", "p", "e", "l"] },
                { word: "Always respect your parents and elders as part of our very rich and beautiful Filipino values", phonemes: ["o", "l", "w", "ay", "z", "r", "e", "s", "p", "e", "k", "t", "y", "o", "r", "p", "e", "r", "e", "n", "t", "s", "a", "n", "d", "e", "l", "d", "e", "r", "z", "a", "z", "p", "a", "r", "t", "o", "v", "ow", "r", "v", "e", "r", "ee", "r", "i", "ch", "a", "n", "d", "b", "y", "oo", "t", "i", "f", "u", "l", "f", "i", "l", "i", "p", "ee", "n", "o", "v", "a", "l", "y", "oo", "z"] },
                { word: "Education is the key to a better future and success for every young and hardworking Filipino child", phonemes: ["e", "j", "u", "k", "ay", "sh", "u", "n", "i", "z", "th", "e", "k", "ee", "t", "oo", "a", "b", "e", "t", "e", "r", "f", "y", "oo", "ch", "e", "r", "a", "n", "d", "s", "a", "k", "s", "e", "s", "f", "o", "r", "e", "v", "r", "ee", "y", "a", "ng", "a", "n", "d", "h", "a", "r", "d", "w", "e", "r", "k", "i", "ng", "f", "i", "l", "i", "p", "ee", "n", "o", "ch", "ay", "l", "d"] }
            ]
        };


        const emojiPool = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🐦', '🐤', '🦆', '🚗', '🚕', '🍎', '🍌'];

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
        let flippedCards = [], matchedPairs = 0;

        // --- Binabalik ang mga Core Functions ---

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

        function introduceSystem() {
            const introText = `Welcome back! To start, look at the word under "The Word to Read". Click the big red microphone button to start speaking. Have fun learning!`;
            speak(introText);
        }

        function triggerMysteryGame(reason = "bonus") {
            matchedPairs = 0; flippedCards = [];
            const title = reason === "struggle" ? "Time for a Break! 🧩" : "Sentence Master! 🧠";
            const text = reason === "struggle" ? "Medyo mahirap ba? Mag-relax muna at laruin ito!" : "Naka 5-stars ka sa mahirap na word! Hanapin ang pares!";
            const gameEmojis = [...emojiPool].sort(() => 0.5 - Math.random()).slice(0, 3);
            const cardValues = [...gameEmojis, ...gameEmojis].sort(() => 0.5 - Math.random());
            Swal.fire({
                title: title,
                html: `<p style="margin-bottom: 10px; font-weight: bold;">${text}</p><div id="memoryGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; justify-items: center;">${cardValues.map((emoji, index) => `<div class="memory-card" id="card-${index}" onclick="flipMemoryCard(${index}, '${emoji}')">?</div>`).join('')}</div>`,
                showConfirmButton: false, allowOutsideClick: false, background: '#FFFFFF'
            });
        }

        window.flipMemoryCard = function(index, emoji) {
            const card = document.getElementById(`card-${index}`);
            if (flippedCards.length < 2 && !card.classList.contains('flipped') && !card.classList.contains('matched')) {
                card.textContent = emoji; card.classList.add('flipped');
                flippedCards.push({ index, emoji });
                if (flippedCards.length === 2) setTimeout(checkMemoryMatch, 700);
            }
        };

        function checkMemoryMatch() {
            const [c1, c2] = flippedCards;
            if (c1.emoji === c2.emoji) {
                document.getElementById(`card-${c1.index}`).classList.add('matched');
                document.getElementById(`card-${c2.index}`).classList.add('matched');
                matchedPairs++;
                if (matchedPairs === 3) {
                    Swal.fire({ icon: 'success', title: 'Magaling! ⭐', showConfirmButton: false, timer: 1500 }).then(() => {
                        let p = allProgress[currentDifficulty];
                        p.word_index++;
                        saveProgressToDB();
                        loadNextWord();
                    });
                }
            } else {
                [c1, c2].forEach(c => {
                    const card = document.getElementById(`card-${c.index}`);
                    card.textContent = '?'; card.classList.remove('flipped');
                });
            }
            flippedCards = [];
        }

        function shuffleArray(array) {
            let shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }

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

        function showParentalGate() {
            Swal.fire({
                title: 'Parental Guidance 👨‍👩‍👧',
                html: `<div style="text-align: left; font-family: sans-serif; line-height: 1.6; padding: 10px;"><p style="color: #2D3436; font-weight: bold;">Dear Parent,</p><p>Ang goal po natin ay matuto ang inyong anak. <b>Hayaan silang magkamali</b> para malaman ni teacher kung saan sila dapat tulungan.</p><hr style="margin: 15px 0;"><label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; background: #f0f9ff; padding: 10px; border-radius: 10px; border: 1px solid #bae6fd;"><input type="checkbox" id="honestyCheck" style="width: 22px; height: 22px; margin-top: 3px;"><span style="font-size: 0.9rem; color: #0369a1;">Naintindihan ko at hahayaan ang anak ko na mag-practice mag-isa.</span></label></div>`,
                confirmButtonText: 'Start Learning! 🚀', confirmButtonColor: '#48DBFB', allowOutsideClick: false,
                preConfirm: () => { if (!document.getElementById('honestyCheck').checked) { Swal.showValidationMessage('Pakicheck po ang box para magpatuloy.'); } }
            }).then(() => { loadProgressFromDB(); });
        }

        function getUsEnglishVoice() {
            if (usEnglishVoice) return usEnglishVoice;
            const voices = speechSynthesis.getVoices();
            let selected = voices.find(v => v.name === 'Google US English') || voices.find(v => v.lang === 'en-US');
            usEnglishVoice = selected; return usEnglishVoice;
        }

        function speak(text) {
            speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            const v = getUsEnglishVoice();
            if (v) { utter.voice = v; utter.lang = v.lang; } else { utter.lang = "en-US"; }
            speechSynthesis.speak(utter);
        }

        function speakRating(score) {
            const perfect = [
                "Excellent! You got five stars!", "Amazing! You are a superstar!",
                "Wow! You are a reading pro!", "Perfect! Your voice is so clear!",
                "Incredible! I am so proud of you!", "Fantastic! You nailed it!",
                "Outstanding! Keep it up!", "You're a natural! Five stars for you!",
                "Brilliant! That was a perfect score!", "Superb! Your pronunciation is spot on!",
                "Marvelous! You are reading like a champ!", "Unbelievable! You are so smart!",
                "Way to go! You are the best!", "You are a reading wizard!",
                "Five stars! You are doing amazing!", "That was music to my ears!",
                "You make reading look so easy!", "Gold star for you! Perfect!",
                "I love how you said that!", "You are a total rockstar!"
            ];

            const great = [
                "Great job! Almost perfect!", "Good effort! You're doing well!",
                "Nice work! Keep going!", "Very good! You're getting better!",
                "Well done! Just a little more practice!", "Awesome! You're nearly there!",
                "That was good! Try to say it even clearer next time!", "Great! You are a fast learner!",
                "So close! You almost got five stars!", "You're doing a wonderful job!",
                "I like how you're trying!", "Keep it up, you're doing great!",
                "Your reading is getting stronger!", "Nice and clear! Good job!",
                "You are working so hard, well done!"
            ];

            const good = [
                "Nice try! You can do it!", "Good start! Let's try one more time.",
                "Not bad! Keep practicing the sounds.", "I like your effort! Try again!",
                "You're learning! Let's repeat that word.", "Keep practicing, you'll get it!",
                "You're on the right track!", "Good job! Try to say every letter.",
                "Practice makes perfect! Try again!", "Don't stop now, you're doing okay!"
            ];

            const tryAgain = [
                "Don't give up! Try again!", "Keep practicing! You can do it!",
                "You're getting closer! Give it another go!", "Let's try that one more time, slowly.",
                "It's okay to make mistakes! Try again!", "I know you can do it! One more try!",
                "Listen to the sound and try once more.", "Let's try to say it together next time!",
                "You can do this! Just keep trying!", "Take a deep breath and try again!",
                "Believe in yourself! Give it another shot!", "Don't worry, just try again!",
                "Every try makes you better! Go again!", "One more time for me, please!"
            ];

            let message = "";
            if (score === 5) {
                message = perfect[Math.floor(Math.random() * perfect.length)];
            } else if (score === 4) {
                message = great[Math.floor(Math.random() * great.length)];
            } else if (score === 3) {
                message = good[Math.floor(Math.random() * good.length)];
            } else {
                message = tryAgain[Math.floor(Math.random() * tryAgain.length)];
            }

            speak(message);
        }

        async function startWaveform() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                analyser = audioContext.createAnalyser();
                source = audioContext.createMediaStreamSource(stream);
                source.connect(analyser);
                analyser.fftSize = 256; bufferLength = analyser.frequencyBinCount;
                dataArray = new Uint8Array(bufferLength); drawWaveform();
            } catch (err) { console.error("Mic error:", err); }
        }

        function drawWaveform() {
            if (!listening) return;
            animationId = requestAnimationFrame(drawWaveform);
            analyser.getByteFrequencyData(dataArray);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let x = 0; let barWidth = (canvas.width / bufferLength) * 2;
            for (let i = 0; i < bufferLength; i++) {
                let barHeight = (dataArray[i] / 255) * canvas.height;
                ctx.fillStyle = `hsl(${200 + (dataArray[i]/255)*100}, 90%, 60%)`;
                ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight); x += barWidth + 1;
            }
        }

        function ratePronunciation(spoken, target) {
            spoken = spoken.toLowerCase().trim().replace(/[.,!]/g, "");
            target = target.toLowerCase().trim().replace(/[.,!]/g, "");
            if (!spoken || spoken === "...") return 0;
            if (spoken === target) return 5;
            const spokenWords = spoken.split(/\s+/); const targetWords = target.split(/\s+/);
            let matches = 0; targetWords.forEach(word => { if (spokenWords.includes(word)) matches++; });
            const ratio = matches / targetWords.length;
            if (ratio >= 0.8) return 4; if (ratio >= 0.5) return 3; if (ratio >= 0.3) return 2; if (ratio > 0) return 1;
            return 0;
        }

        function loadNextWord() {
            imageBox.style.visibility = "hidden";
            imageBox.classList.add("hidden");
            feedbackMessage.style.visibility = "hidden";
            feedbackMessage.textContent = "Practice the word to see the sentence!";
            feedbackMessage.className = "feedback-message bg-initial-feedback";
            playFeedbackBtn.style.display = "none";
            transcriptEl.textContent = "..."; ratingEl.textContent = "0";
            renderStars(0); nextBtn.disabled = true;
            document.getElementById("runningTimer").style.visibility = "hidden";
            document.getElementById("seconds").textContent = "0.0";

            let p = allProgress[currentDifficulty];
            if (!p.shuffledList || p.shuffledList.length === 0 || p.word_index >= p.shuffledList.length) {
                p.shuffledList = shuffleArray(wordBank[currentDifficulty]); p.word_index = 0;
            }
            currentWord = p.shuffledList[p.word_index];

            wordDisplay.textContent = currentWord.word;
            phonemeDisplay.textContent = currentWord.phonemes.join(" · ");
            const imgKey = (currentWord.originalWord || currentWord.word).toLowerCase();

            if (imageLibrary[imgKey]) {
                const imgPath = imageLibrary[imgKey];
                document.getElementById("wordImage").src = imgPath;
                imageBox.style.backgroundImage = `url('${imgPath}')`;
                imageBox.classList.remove("hidden");
            } else {
                imageBox.classList.add("hidden");
            }

            wordAttemptsHistory = []; renderWordHistory(); updateUIProgress();
        }

        // --- Binagong checkPronunciation para ma-fix ang Duplicate ---

        function checkPronunciation(spoken) {
            // Guard: Stop kung naka-5 stars na para hindi mag-doble sa history
            if (wordAttemptsHistory.includes(5)) return;

            const score = ratePronunciation(spoken, currentWord.word);
            speakRating(score);

            if (score === 5) {
                const imgKey = (currentWord.originalWord || currentWord.word).toLowerCase();
                if (imageLibrary[imgKey]) {
                    imageBox.style.visibility = "visible";
                    imageBox.classList.remove("hidden");
                }
                feedbackMessage.style.visibility = "visible";

                // Only count as correct if this is the FIRST 5-star for this word
                allProgress[currentDifficulty].words_correct++;

                // Milestone counters
                if (currentDifficulty === 'beginner') {
                    begCountFromDB++;
                    if(begCountFromDB === 40) {
                        triggerConfetti();
                        setTimeout(() => location.reload(), 2000);
                        return;
                    }
                }
                if (currentDifficulty === 'intermediate') {
                    intCountFromDB++;
                    if(intCountFromDB === 40) {
                        triggerConfetti();
                        setTimeout(() => location.reload(), 2000);
                        return;
                    }
                }
                triggerConfetti();

                nextBtn.disabled = false;
                feedbackMessage.textContent = "⭐ " + currentWord.example;
                feedbackMessage.className = "feedback-message bg-success-feedback";
                playFeedbackBtn.style.display = "inline-block";
            } else {
                nextBtn.disabled = true;
                feedbackMessage.textContent = "Practice the word to see the sentence!";
                imageBox.style.visibility = "hidden";
                feedbackMessage.style.visibility = "hidden";
            }

            allProgress[currentDifficulty].words_attempted++;
            ratingEl.textContent = score; renderStars(score);

            // Push and Render History
            wordAttemptsHistory.push(score);
            renderWordHistory();

            if (wordAttemptsHistory.length >= 5 && !wordAttemptsHistory.includes(5)) {
                setTimeout(() => triggerMysteryGame("struggle"), 1000);
            }

            updateUIProgress();
            saveProgressToDB();
            saveRatingToDB(currentWord.word, score);
            checkLevelUnlock();
        }

        function updateUIProgress() {
            let p = allProgress[currentDifficulty];
            let score = p.words_correct;
            let displayScore = score > 40 ? 40 : score;
            document.getElementById("progressText").textContent = displayScore + " / 40";
            document.getElementById("progressBarFill").style.width = (displayScore / 40 * 100) + "%";
            if (score >= 40 && !p.isResetting) setTimeout(() => triggerLevelCompleteCelebration(), 600);
            document.getElementById("attemptedCount").textContent = p.words_attempted;
            const accuracy = p.words_attempted > 0 ? Math.round((p.words_correct / p.words_attempted) * 100) : 0;
            document.getElementById("accuracyRate").textContent = accuracy + "%";
        }

        function triggerLevelCompleteCelebration() {
            let p = allProgress[currentDifficulty]; p.isResetting = true;
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const playNote = (freq, start, duration) => {
                const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
                osc.type = 'triangle'; osc.frequency.setValueAtTime(freq, start);
                gain.gain.setValueAtTime(0.1, start); gain.gain.exponentialRampToValueAtTime(0.01, start + duration);
                osc.connect(gain); gain.connect(audioCtx.destination);
                osc.start(start); osc.stop(start + duration);
            };
            [523.25, 659.25, 783.99, 1046.50].forEach((f, i) => playNote(f, audioCtx.currentTime + (i * 0.12), 0.6));
            speak("Level Complete! Amazing job!");
            Swal.fire({
                title: '<div style="color: #FFC312; font-size: 2.5rem; font-weight: 900; text-shadow: 0 0 20px gold; animation: glowPulse 1s infinite alternate;">LEVEL COMPLETE</div>',
                html: `<div style="text-align: center; padding: 20px; position: relative;"><div id="starContainer" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 40px;"><i class="fa-star fa-solid pro-star" style="font-size: 3rem; color: #CED6E0;"></i><i class="fa-star fa-solid pro-star" style="font-size: 3.5rem; color: #CED6E0;"></i><i class="fa-star fa-solid pro-star" style="font-size: 4.5rem; color: #CED6E0;"></i><i class="fa-star fa-solid pro-star" style="font-size: 3.5rem; color: #CED6E0;"></i><i class="fa-star fa-solid pro-star" style="font-size: 3rem; color: #CED6E0;"></i></div><div style="animation: fadeInUp 1s ease-out forwards;"><h2 style="color: #FFFFFF;">PERFECT!</h2><p style="color: #FFFFFF;">Naka-40 correct words ka na!</p></div></div>`,
                confirmButtonText: 'GO! 🚀', background: 'transparent', backdrop: `rgba(0,0,0,0.85)`, allowOutsideClick: false,
                didOpen: () => {
                    const stars = document.querySelectorAll('.pro-star');
                    stars.forEach((star, i) => {
                        setTimeout(() => {
                            star.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
                            star.style.color = '#FFC312'; star.style.transform = 'scale(1.2)';
                            for(let j=0; j<10; j++) triggerConfetti();
                        }, (i + 1) * 400);
                    });
                }
            }).then(() => {
                p.word_index = 0; p.words_correct = 0; p.words_attempted = 0;
                p.shuffledList = shuffleArray(wordBank[currentDifficulty]);
                p.isResetting = false; saveProgressToDB(); updateUIProgress(); loadNextWord();
            });
        }

        function renderStars(score) {
            starsEl.innerHTML = "";
            for (let i = 1; i <= 5; i++) {
                const s = document.createElement("i");
                s.className = `fa-star fa-solid star ${i <= score ? 'filled-star' : 'empty-star'}`;
                starsEl.appendChild(s);
            }
        }

        // --- Reverse History Render ---
        function renderWordHistory() {
            wordHistoryEl.innerHTML = wordAttemptsHistory.length === 0 ? "No attempts yet." : "";
            [...wordAttemptsHistory].reverse().forEach((s, i) => {
                const div = document.createElement("div"); div.className = "history-item";
                div.style.padding = "5px"; div.style.borderBottom = "1px solid #ddd";
                div.innerHTML = `<span>Attempt ${wordAttemptsHistory.length - i}:</span> `;
                for (let j = 1; j <= 5; j++) { div.innerHTML += `<i class="fa-star fa-solid ${j <= s ? 'filled-star' : 'empty-star'}" style="font-size: 0.9rem;"></i>`; }
                wordHistoryEl.appendChild(div);
            });
        }

        function triggerConfetti() {
            for (let i = 0; i < 15; i++) {
                const c = document.createElement('div'); c.className = 'confetti';
                c.style.left = Math.random() * 100 + 'vw';
                c.style.backgroundColor = ['#FF6B6B','#48DBFB','#FECA57','#1DD1A1','#A29BFE'][Math.floor(Math.random()*5)];
                c.style.width = '8px'; c.style.height = '8px'; c.style.position = 'fixed'; c.style.top = '-10px';
                c.style.animation = `fall ${Math.random()*3+2}s linear forwards`;
                document.body.appendChild(c); setTimeout(() => c.remove(), 5000);
            }
        }

        async function loadProgressFromDB() {
            if (!STUDENT_ID) { loadNextWord(); return; }
            try {
                const response = await fetch(`load_progress.php?student_id=${STUDENT_ID}&difficulty=${currentDifficulty}&t=${Date.now()}`);
                const data = await response.json();
                if (data.success) {
                    if (data.progress) {
                        allProgress[currentDifficulty].word_index = parseInt(data.progress.word_index) || 0;
                        allProgress[currentDifficulty].words_attempted = parseInt(data.progress.words_attempted) || 0;
                        allProgress[currentDifficulty].words_correct = parseInt(data.progress.words_correct) || 0;
                    }
                }
                allProgress.beginner.shuffledList = shuffleArray(wordBank.beginner);
                allProgress.intermediate.shuffledList = shuffleArray(wordBank.intermediate);
                allProgress.advanced.shuffledList = shuffleArray(wordBank.advanced);
                updateUIProgress(); loadNextWord(); checkLevelUnlock();
            } catch (e) { loadNextWord(); }
        }

        function saveProgressToDB() {
            if (!STUDENT_ID) return;
            const p = allProgress[currentDifficulty];
            const formData = new FormData();
            formData.append('student_id', STUDENT_ID); formData.append('difficulty', currentDifficulty);
            formData.append('word_index', p.word_index); formData.append('words_attempted', p.words_attempted); formData.append('words_correct', p.words_correct);
            fetch('save_progress.php', { method: 'POST', body: formData });
        }

        function saveRatingToDB(word, score) {
            if (!STUDENT_ID) return;
            const formData = new FormData();
            formData.append('student_id', STUDENT_ID); formData.append('username', USERNAME);
            formData.append('word', word); formData.append('score', score);
            formData.append('difficulty', currentDifficulty);
            formData.append('duration', document.getElementById("seconds").textContent);
            fetch('save_rating.php', { method: 'POST', body: formData });
        }

        function toggleMic() {
            if (!recognition) return;
            if (!listening) {
                listening = true; micBtn.classList.add("listening"); statusEl.textContent = "Listening...";
                document.getElementById("runningTimer").style.visibility = "visible";
                startTime = Date.now();
                timerInterval = setInterval(() => { document.getElementById("seconds").textContent = ((Date.now() - startTime) / 1000).toFixed(1); }, 100);
                startWaveform(); recognition.start();
            } else { stopMicLogic(); }
        }

        function stopMicLogic() {
            listening = false; micBtn.classList.remove("listening");
            clearInterval(timerInterval); if (recognition) recognition.stop();
            if (animationId) cancelAnimationFrame(animationId);
        }

        function init() {
            difficultySelect.addEventListener("change", () => { currentDifficulty = difficultySelect.value; loadProgressFromDB(); });
            nextBtn.addEventListener("click", () => {
                let p = allProgress[currentDifficulty];
                if (p.words_correct > 0 && p.words_correct % 5 === 0 && !wordAttemptsHistory.includes(5)) {
                    triggerMysteryGame("bonus");
                } else { p.word_index++; saveProgressToDB(); loadNextWord(); }
            });
            playWordBtn.addEventListener("click", () => speak(currentWord.word));
            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));
            micBtn.addEventListener("click", toggleMic);

            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = true;

                recognition.onresult = (e) => {
                    let isFinal = false;
                    let finalTxt = '';
                    for (let i = e.resultIndex; i < e.results.length; ++i) {
                        const transcript = e.results[i][0].transcript;
                        transcriptEl.textContent = transcript;
                        if (e.results[i].isFinal) {
                            isFinal = true;
                            finalTxt = transcript;
                        } else {
                            if (transcript.toLowerCase().trim() === currentWord.word.toLowerCase().trim()) {
                                checkPronunciation(transcript);
                                stopMicLogic();
                                return;
                            }
                        }
                    }
                    if (isFinal) {
                        checkPronunciation(finalTxt);
                        stopMicLogic();
                    }
                };
                recognition.onspeechend = () => { stopMicLogic(); };
                recognition.onerror = () => { stopMicLogic(); };
            }
            if (speechSynthesis.onvoiceschanged !== undefined) { speechSynthesis.onvoiceschanged = getUsEnglishVoice; }
            if (SHOW_PARENTAL_NOTE) showParentalGate(); else loadProgressFromDB();
            setTimeout(() => { if (PLAY_WELCOME_VOICE) introduceSystem(); }, 1500);
        }
        window.onload = init;
    </script>
</main>
</body>
</html>