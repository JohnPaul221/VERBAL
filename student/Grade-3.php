<?php
global $pdo;
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || $_SESSION['grade'] != '3') {
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
            /* Grade 3 Colors - Deep Teal & Sunset Theme */
            --primary: #FF7F50;       /* Coral/Orange */
            --success: #20BF6B;       /* Jade Green */
            --kids-blue: #0FB9B1;      /* Deep Teal */
            --kids-yellow: #A55EEA;    /* Royal Purple */
            --bg-gradient: linear-gradient(135deg, #4568DC 0%, #B06AB3 100%);
        }

        /* --- Full Screen Fit --- */
        body {
            height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            padding: 0 15px;
            color: #2D3436;
            margin: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- Header --- */
        .page-header { height: 60px; position: relative; flex-shrink: 0; }
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: #FED330; border: 2px solid #F7B731; z-index: 100; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #0097A7; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #E67E22; border-radius: 10px; z-index: 10; cursor: pointer; }

        /* --- Main Layout --- */
        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #FFFFFF; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #A5B1C2; border: 4px solid var(--kids-blue);
            box-sizing: border-box;
        }

        /* --- Sections --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F1F5F9;
            display: flex; flex-direction: column; border: 2px solid #D1D8E0;
            align-items: center; text-align: center;
            overflow: visible;
            justify-content: space-between;
        }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F1F5F9;
            display: flex; flex-direction: column; border: 2px solid #D1D8E0;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-blue); opacity: 0.2; border-radius: 10px; }

        /* --- IMAGE BOX + FULL SCREEN POPUP --- */
        .word-image-box {
            width: 120px;
            height: 120px;
            border: 4px solid var(--primary);
            border-radius: 15px;
            background: white;
            position: relative;
            background-size: cover;
            background-position: center;
            cursor: zoom-in;
            flex-shrink: 0;
            visibility: hidden;
        }

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
        h2, h3 { font-size: 1.1rem; margin: 5px 0; color: #2D3436; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        #difficulty { width: 90%; padding: 8px; border-radius: 12px; border: 3px solid var(--kids-blue); font-family: inherit; font-weight: bold; margin-bottom: 5px; }
        .mic-icon { width: 65px; height: 65px; background: var(--primary); border: 5px solid #FF9F43; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; margin: 5px auto; box-shadow: 0 4px 0px #D35400; }
        .reading-material { background: #E0F7FA; border-radius: 20px; border: 3px dashed var(--kids-blue); padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }
        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: #2D3436; text-transform: uppercase; line-height: 1.1; }
        .transcript { font-size: 1.1rem; font-weight: bold; color: #019031; background: #DFF9FB; border: 2px solid #20BF6B; border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        /* --- PRO SCOREBOARD --- */
        .stats-container { display: flex; justify-content: space-around; align-items: center; background: white; padding: 15px 10px; border-radius: 20px; border: 5px solid var(--kids-blue); margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #0097A7; }
        .stat-item span { display: block; font-size: 0.8rem; color: #7F8C8D; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: var(--kids-blue); line-height: 1; text-shadow: 1px 1px 0px #f1f2f6; }

        /* --- PROGRESS BAR --- */
        .progress-bar { width: 100%; height: 25px; background: #FFFFFF !important; border-radius: 15px; border: 4px solid #D1D8E0; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: var(--success) !important; transition: width 0.6s ease-in-out; }

        /* --- PRO SENTENCE --- */
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid #D1D8E0; color: #2D3436; min-height: 60px; display: flex; align-items: center; justify-content: center; visibility: hidden; }
        .bg-success-feedback { background: #E0F2F1 !important; color: #00695C !important; border: 3px solid #26A69A !important; }

        /* --- PRO STARS --- */
        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #D1D8E0; transition: color 0.3s ease; }
        .filled-star { color: #F7B731 !important; text-shadow: 0 0 15px rgba(247, 183, 49, 0.6); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, starShine 2s infinite linear; transform-origin: center; }
        @keyframes starPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.4); } 100% { transform: scale(1); opacity: 1; } }
        @keyframes starShine { 0% { text-shadow: 0 0 10px rgba(247, 183, 49, 0.5); } 50% { text-shadow: 0 0 20px rgba(247, 183, 49, 0.8); } 100% { text-shadow: 0 0 10px rgba(247, 183, 49, 0.5); } }

        /* --- PRO UI MYSTERY MATCHING --- */
        #memoryGrid { background: rgba(255, 255, 255, 0.5); padding: 20px; border-radius: 20px; border: 2px solid #D1D8E0; }
        .memory-card {
            width: 80px; height: 80px;
            background: linear-gradient(145deg, var(--kids-blue), #0097A7);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 2.8rem;
            border-radius: 18px; cursor: pointer; box-shadow: 0 6px 0 #006064, 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.3); position: relative;
        }
        .memory-card:hover { transform: translateY(-5px) scale(1.05); filter: brightness(1.1); }
        .memory-card::before { content: "?"; font-weight: 900; opacity: 0.5; }
        .memory-card.flipped { background: white; color: #2D3436; border: 4px solid var(--primary); box-shadow: 0 6px 0 #E67E22; animation: cardAppear 0.4s ease-out forwards; }
        .memory-card.flipped::before { content: ""; }
        .memory-card.matched { background: var(--success); border-color: #1B8E50; animation: matchedSuccess 0.5s ease-out forwards; pointer-events: none; }
        @keyframes cardAppear { 0% { transform: scale(0.5) rotateY(0deg); opacity: 0; } 100% { transform: scale(1) rotateY(180deg); opacity: 1; } }
        @keyframes matchedSuccess { 0% { transform: scale(1); } 50% { transform: scale(1.2); filter: brightness(1.5); } 100% { transform: scale(0); opacity: 0; visibility: hidden; } }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid var(--kids-blue); background: #E0F2F1; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }
        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: #FED330; color: #574B15; box-shadow: 0 4px 0 #F7B731; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 4px 0 #0097A7; width: 90%; }
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

        const wordBank = {
            beginner: [
                { word: "The car crosses the bridge", phonemes: ["th", "e", "k", "a", "r", "k", "r", "o", "s", "e", "z", "th", "e", "b", "r", "i", "j"] },
                { word: "The stars are very bright", phonemes: ["th", "e", "s", "t", "a", "r", "z", "a", "r", "v", "e", "r", "ee", "b", "r", "ay", "t"] },
                { word: "My shirt has a button", phonemes: ["m", "ay", "sh", "e", "r", "t", "h", "a", "z", "a", "b", "a", "t", "o", "n"] },
                { word: "Smile for the camera now", phonemes: ["s", "m", "ay", "l", "f", "o", "r", "th", "e", "k", "a", "m", "e", "r", "a", "n", "ow"] },
                { word: "I use a red crayon", phonemes: ["ay", "y", "oo", "z", "a", "r", "e", "d", "k", "r", "ay", "o", "n"] },
                { word: "The doctor helps the sick", phonemes: ["th", "e", "d", "o", "k", "t", "o", "r", "h", "e", "l", "p", "s", "th", "e", "s", "i", "k"] },
                { word: "The dragon breathes hot fire", phonemes: ["th", "e", "d", "r", "a", "g", "o", "n", "b", "r", "ee", "th", "z", "h", "o", "t", "f", "ay", "r"] },
                { word: "We live on planet Earth", phonemes: ["w", "ee", "l", "i", "v", "o", "n", "p", "l", "a", "n", "e", "t", "e", "r", "th"] },
                { word: "I love my whole family", phonemes: ["ay", "l", "a", "v", "m", "ay", "h", "o", "l", "f", "a", "m", "i", "l", "ee"] },
                { word: "The farmer plants the rice", phonemes: ["th", "e", "f", "a", "r", "m", "e", "r", "p", "l", "a", "n", "t", "s", "th", "e", "r", "ay", "s"] },
                { word: "Trees grow in the forest", phonemes: ["t", "r", "ee", "z", "g", "r", "o", "i", "n", "th", "e", "f", "o", "r", "e", "s", "t"] },
                { word: "Roses grow in the garden", phonemes: ["r", "o", "z", "e", "z", "g", "r", "o", "i", "n", "th", "e", "g", "a", "r", "d", "e", "n"] },
                { word: "Dad uses a heavy hammer", phonemes: ["d", "a", "d", "y", "oo", "z", "e", "z", "a", "h", "e", "v", "ee", "h", "a", "m", "e", "r"] },
                { word: "The island is far away", phonemes: ["th", "e", "ay", "l", "a", "n", "d", "i", "z", "f", "a", "r", "a", "w", "ay"] },
                { word: "The lion lives in jungle", phonemes: ["th", "e", "l", "ay", "o", "n", "l", "i", "v", "z", "i", "n", "j", "a", "ng", "g", "e", "l"] },
                { word: "Mom cooks in the kitchen", phonemes: ["m", "o", "m", "k", "u", "k", "s", "i", "n", "th", "e", "k", "i", "ch", "e", "n"] },
                { word: "Climb up the wooden ladder", phonemes: ["k", "l", "ay", "m", "a", "p", "th", "e", "w", "u", "d", "e", "n", "l", "a", "d", "e", "r"] },
                { word: "Our lesson today is easy", phonemes: ["ow", "r", "l", "e", "s", "o", "n", "t", "u", "d", "ay", "i", "z", "ee", "z", "ee"] },
                { word: "We buy fish at market", phonemes: ["w", "ee", "b", "ay", "f", "i", "sh", "a", "t", "m", "a", "r", "k", "e", "t"] },
                { word: "I look in the mirror", phonemes: ["ay", "l", "u", "k", "i", "n", "th", "e", "m", "i", "r", "o", "r"] },
                { word: "Good morning to you all", phonemes: ["g", "u", "d", "m", "o", "r", "n", "i", "ng", "t", "oo", "y", "oo", "o", "l"] },
                { word: "Tell me your lucky number", phonemes: ["t", "e", "l", "m", "ee", "y", "o", "r", "l", "a", "k", "ee", "n", "a", "m", "b", "e", "r"] },
                { word: "The orange is very juicy", phonemes: ["th", "e", "o", "r", "a", "n", "j", "i", "z", "v", "e", "r", "ee", "j", "oo", "s", "ee"] },
                { word: "Always obey your kind parent", phonemes: ["o", "l", "w", "ay", "z", "o", "b", "ay", "y", "o", "r", "k", "ay", "n", "d", "p", "e", "r", "e", "n", "t"] },
                { word: "My pencil is long yellow", phonemes: ["m", "ay", "p", "e", "n", "s", "i", "l", "i", "z", "l", "o", "ng", "y", "e", "l", "o"] },
                { word: "Mars is a red planet", phonemes: ["m", "a", "r", "z", "i", "z", "a", "r", "e", "d", "p", "l", "a", "n", "e", "t"] },
                { word: "The coin is in pocket", phonemes: ["th", "e", "k", "oy", "n", "i", "z", "i", "n", "p", "o", "k", "e", "t"] },
                { word: "This puzzle is hard work", phonemes: ["th", "i", "s", "p", "a", "z", "e", "l", "i", "z", "h", "a", "r", "d", "w", "e", "r", "k"] },
                { word: "The rabbit has long ears", phonemes: ["th", "e", "r", "a", "b", "i", "t", "h", "a", "z", "l", "o", "ng", "e", "r", "z"] },
                { word: "I go to school now", phonemes: ["ay", "g", "o", "t", "oo", "s", "k", "oo", "l", "n", "ow"] },
                { word: "My shadow follows me today", phonemes: ["m", "ay", "sh", "a", "d", "o", "f", "o", "l", "o", "z", "m", "ee", "t", "u", "d", "ay"] },
                { word: "The silver ring is shiny", phonemes: ["th", "e", "s", "i", "l", "v", "e", "r", "r", "i", "ng", "i", "z", "sh", "ay", "n", "ee"] },
                { word: "My sister is very kind", phonemes: ["m", "ay", "s", "i", "s", "t", "e", "r", "i", "z", "v", "e", "r", "ee", "k", "ay", "n", "d"] },
                { word: "The spider spins a web", phonemes: ["th", "e", "s", "p", "ay", "d", "e", "r", "s", "p", "i", "n", "z", "a", "w", "e", "b"] },
                { word: "Go swimming in the summer", phonemes: ["g", "o", "s", "w", "i", "m", "i", "ng", "i", "n", "th", "e", "s", "a", "m", "e", "r"] },
                { word: "Buy a ticket for bus", phonemes: ["b", "ay", "a", "t", "i", "k", "e", "t", "f", "o", "r", "b", "a", "s"] },
                { word: "The red tomato is round", phonemes: ["th", "e", "r", "e", "d", "t", "o", "m", "ay", "t", "o", "i", "z", "r", "ow", "n", "d"] },
                { word: "We travel to the farm", phonemes: ["w", "ee", "t", "r", "a", "v", "e", "l", "t", "oo", "th", "e", "f", "a", "r", "m"] },
                { word: "The turtle is very slow", phonemes: ["th", "e", "t", "e", "r", "t", "e", "l", "i", "z", "v", "e", "r", "ee", "s", "l", "o"] },
                { word: "Look out the open window", phonemes: ["l", "u", "k", "ow", "t", "th", "e", "o", "p", "e", "n", "w", "i", "n", "d", "o"] },
                { word: "Snow falls during the winter", phonemes: ["s", "n", "o", "f", "o", "l", "z", "d", "u", "r", "i", "ng", "th", "e", "w", "i", "n", "t", "e", "r"] },
                { word: "The sun is bright yellow", phonemes: ["th", "e", "s", "a", "n", "i", "z", "b", "r", "ay", "t", "y", "e", "l", "o"] },
                { word: "The plane is at airport", phonemes: ["th", "e", "p", "l", "ay", "n", "i", "z", "a", "t", "e", "r", "p", "o", "r", "t"] },
                { word: "The bottle is full water", phonemes: ["th", "e", "b", "o", "t", "e", "l", "i", "z", "f", "u", "l", "w", "o", "t", "e", "r"] },
                { word: "The candy is very sweet", phonemes: ["th", "e", "k", "a", "n", "d", "ee", "i", "z", "v", "e", "r", "ee", "s", "w", "ee", "t"] },
                { word: "The moon is a circle", phonemes: ["th", "e", "m", "oo", "n", "i", "z", "a", "s", "e", "r", "k", "e", "l"] },
                { word: "We eat rice for dinner", phonemes: ["w", "ee", "ee", "t", "r", "ay", "s", "f", "o", "r", "d", "i", "n", "e", "r"] },
                { word: "The red flower is pretty", phonemes: ["th", "e", "r", "e", "d", "f", "l", "ow", "e", "r", "i", "z", "p", "r", "i", "t", "ee"] },
                { word: "He plays the guitar well", phonemes: ["h", "ee", "p", "l", "ay", "z", "th", "e", "g", "i", "t", "a", "r", "w", "e", "l"] },
                { word: "Wash your face with water", phonemes: ["w", "o", "sh", "y", "o", "r", "f", "ay", "s", "w", "i", "th", "w", "o", "t", "e", "r"] }
            ],
            intermediate: [
                { word: "Learn the ABC alphabet at school", phonemes: ["l", "e", "r", "n", "th", "e", "a", "l", "f", "a", "b", "e", "t", "a", "t", "s", "k", "oo", "l"] },
                { word: "The city building is very tall", phonemes: ["th", "e", "s", "i", "t", "ee", "b", "i", "l", "d", "i", "ng", "i", "z", "v", "e", "r", "ee", "t", "o", "l"] },
                { word: "Check the date on the calendar", phonemes: ["ch", "e", "k", "th", "e", "d", "ay", "t", "o", "n", "th", "e", "k", "a", "l", "e", "n", "d", "e", "r"] },
                { word: "I play games on my computer", phonemes: ["ay", "p", "l", "ay", "g", "ay", "m", "z", "o", "n", "m", "ay", "k", "o", "m", "p", "y", "oo", "t", "e", "r"] },
                { word: "The dinosaur is large and scary", phonemes: ["th", "e", "d", "ay", "n", "o", "s", "o", "r", "i", "z", "l", "a", "r", "j", "a", "n", "d", "s", "k", "e", "r", "ee"] },
                { word: "The elephant is a very big animal", phonemes: ["th", "e", "e", "l", "e", "f", "a", "n", "t", "i", "z", "a", "v", "e", "r", "ee", "b", "i", "g", "a", "n", "i", "m", "a", "l"] },
                { word: "The firefly shines in the night", phonemes: ["th", "e", "f", "ay", "r", "f", "l", "ay", "sh", "ay", "n", "z", "i", "n", "th", "e", "n", "ay", "t"] },
                { word: "The goldfish is in the water tank", phonemes: ["th", "e", "g", "o", "l", "d", "f", "i", "sh", "i", "z", "i", "n", "th", "e", "w", "o", "t", "e", "r", "t", "a", "ng", "k"] },
                { word: "The sick boy is in the hospital", phonemes: ["th", "e", "s", "i", "k", "b", "oy", "i", "z", "i", "n", "th", "e", "h", "o", "s", "p", "i", "t", "a", "l"] },
                { word: "I love to eat cold ice cream", phonemes: ["ay", "l", "a", "v", "t", "oo", "ee", "t", "k", "o", "l", "d", "ay", "s", "k", "r", "ee", "m"] },
                { word: "The kangaroo has a deep pocket", phonemes: ["th", "e", "k", "a", "ng", "g", "a", "r", "oo", "h", "a", "z", "a", "d", "ee", "p", "p", "o", "k", "e", "t"] },
                { word: "Read many books in the library", phonemes: ["r", "ee", "d", "m", "e", "n", "ee", "b", "u", "k", "s", "i", "n", "th", "e", "l", "ay", "b", "r", "e", "r", "ee"] },
                { word: "The mountain is very tall and high", phonemes: ["th", "e", "m", "ow", "n", "t", "i", "n", "i", "z", "v", "e", "r", "ee", "t", "o", "l", "a", "n", "d", "h", "ay"] },
                { word: "I write in my new notebook", phonemes: ["ay", "r", "ay", "t", "i", "n", "m", "ay", "n", "y", "oo", "n", "o", "t", "b", "u", "k"] },
                { word: "The octopus has eight long arms", phonemes: ["th", "e", "o", "k", "t", "o", "p", "u", "s", "h", "a", "z", "ay", "t", "l", "o", "ng", "a", "r", "m", "z"] },
                { word: "The hot pancake is very yum", phonemes: ["th", "e", "h", "o", "t", "p", "a", "n", "k", "ay", "k", "i", "z", "v", "e", "r", "ee", "y", "a", "m"] },
                { word: "Wear your yellow raincoat for rain", phonemes: ["w", "e", "r", "y", "o", "r", "y", "e", "l", "o", "r", "ay", "n", "k", "o", "t", "f", "o", "r", "r", "ay", "n"] },
                { word: "The egg sandwich is very yum", phonemes: ["th", "e", "e", "g", "s", "a", "n", "d", "w", "i", "ch", "i", "z", "v", "e", "r", "ee", "y", "a", "m"] },
                { word: "Call your dad on the telephone", phonemes: ["k", "o", "l", "y", "o", "r", "d", "a", "d", "o", "n", "th", "e", "t", "e", "l", "e", "f", "o", "n"] },
                { word: "The red umbrella is for the rain", phonemes: ["th", "e", "r", "e", "d", "a", "m", "b", "r", "e", "l", "a", "i", "z", "f", "o", "r", "th", "e", "r", "ay", "n"] },
                { word: "Eat your green vegetable every day", phonemes: ["ee", "t", "y", "o", "r", "g", "r", "ee", "n", "v", "e", "j", "e", "t", "a", "b", "e", "l", "e", "v", "r", "ee", "d", "ay"] },
                { word: "The big watermelon is very heavy", phonemes: ["th", "e", "b", "i", "g", "w", "o", "t", "e", "r", "m", "e", "l", "o", "n", "i", "z", "v", "e", "r", "ee", "h", "e", "v", "ee"] },
                { word: "It was very sunny day yesterday", phonemes: ["i", "t", "w", "o", "z", "v", "e", "r", "ee", "s", "a", "n", "ee", "d", "ay", "y", "e", "s", "t", "e", "r", "d", "ay"] },
                { word: "The zookeeper feeds the big lions", phonemes: ["th", "e", "z", "oo", "k", "ee", "p", "e", "r", "f", "ee", "d", "z", "th", "e", "b", "i", "g", "l", "ay", "o", "n", "z"] },
                { word: "Eat a good breakfast every morning", phonemes: ["ee", "t", "a", "g", "u", "d", "b", "r", "e", "k", "f", "a", "s", "t", "e", "v", "r", "ee", "m", "o", "r", "n", "i", "ng"] },
                { word: "The chocolate cake is very sweet", phonemes: ["th", "e", "ch", "o", "k", "o", "l", "e", "t", "k", "ay", "k", "i", "z", "v", "e", "r", "ee", "s", "w", "ee", "t"] },
                { word: "The detective finds many new clues", phonemes: ["th", "e", "d", "e", "t", "e", "k", "t", "i", "v", "f", "ay", "n", "d", "z", "m", "e", "n", "ee", "n", "y", "oo", "k", "l", "oo", "z"] },
                { word: "He has everything he needs today", phonemes: ["h", "ee", "h", "a", "z", "e", "v", "r", "ee", "th", "ee", "ng", "h", "ee", "n", "ee", "d", "z", "t", "u", "d", "ay"] },
                { word: "The fireworks are bright and loud", phonemes: ["th", "e", "f", "ay", "r", "w", "e", "r", "k", "s", "a", "r", "b", "r", "ay", "t", "a", "n", "d", "l", "ow", "d"] },
                { word: "The green grasshopper jumps very far", phonemes: ["th", "e", "g", "r", "ee", "n", "g", "r", "a", "s", "h", "o", "p", "e", "r", "j", "a", "m", "p", "s", "v", "e", "r", "ee", "f", "a", "r"] },
                { word: "The helicopter flies high in sky", phonemes: ["th", "e", "h", "e", "l", "i", "k", "o", "p", "t", "e", "r", "f", "l", "ay", "z", "h", "ay", "i", "n", "s", "k", "ay"] },
                { word: "The piano is a large instrument", phonemes: ["th", "e", "p", "ee", "a", "n", "o", "i", "z", "a", "l", "a", "r", "j", "i", "n", "s", "t", "r", "u", "m", "e", "n", "t"] },
                { word: "The jellyfish swims in the sea", phonemes: ["th", "e", "j", "e", "l", "ee", "f", "i", "sh", "s", "w", "i", "m", "z", "i", "n", "th", "e", "s", "ee"] },
                { word: "I type on the computer keyboard", phonemes: ["ay", "t", "ay", "p", "o", "n", "th", "e", "k", "o", "m", "p", "y", "oo", "t", "e", "r", "k", "ee", "b", "o", "r", "d"] },
                { word: "The lighthouse is by the sea", phonemes: ["th", "e", "l", "ay", "t", "h", "ow", "s", "i", "z", "b", "ay", "th", "e", "s", "ee"] },
                { word: "Sing into the loud silver microphone", phonemes: ["s", "i", "ng", "i", "n", "t", "oo", "th", "e", "l", "ow", "d", "s", "i", "l", "v", "e", "r", "m", "ay", "k", "r", "o", "f", "o", "n"] },
                { word: "Dad reads the newspaper every morning", phonemes: ["d", "a", "d", "r", "ee", "d", "z", "th", "e", "n", "y", "oo", "s", "p", "ay", "p", "e", "r", "e", "v", "r", "ee", "m", "o", "r", "n", "i", "ng"] },
                { word: "Wear your thick overcoat in winter", phonemes: ["w", "e", "r", "y", "o", "r", "th", "i", "k", "o", "v", "e", "r", "k", "o", "t", "i", "n", "w", "i", "n", "t", "e", "r"] },
                { word: "The yellow pineapple is very sweet", phonemes: ["th", "e", "y", "e", "l", "o", "p", "ay", "n", "a", "p", "e", "l", "i", "z", "v", "e", "r", "ee", "s", "w", "ee", "t"] },
                { word: "They dance a quadrille at party", phonemes: ["th", "ay", "d", "a", "n", "s", "a", "k", "w", "o", "d", "r", "i", "l", "a", "t", "p", "a", "r", "t", "ee"] },
                { word: "The door is a brown rectangle", phonemes: ["th", "e", "d", "o", "r", "i", "z", "a", "b", "r", "ow", "n", "r", "e", "k", "t", "a", "ng", "g", "e", "l"] },
                { word: "I ride my skateboard in park", phonemes: ["ay", "r", "ay", "d", "m", "ay", "s", "k", "ay", "t", "b", "o", "r", "d", "i", "n", "p", "a", "r", "k"] },
                { word: "Jump high on the blue trampoline", phonemes: ["j", "a", "m", "p", "h", "ay", "o", "n", "th", "e", "b", "l", "oo", "t", "r", "a", "m", "p", "o", "l", "ee", "n"] },
                { word: "The small worms live deep underground", phonemes: ["th", "e", "s", "m", "o", "l", "w", "e", "r", "m", "z", "l", "i", "v", "d", "ee", "p", "a", "n", "d", "e", "r", "g", "r", "ow", "n", "d"] },
                { word: "The volcano has a lot smoke", phonemes: ["th", "e", "v", "o", "l", "k", "ay", "n", "o", "h", "a", "z", "a", "l", "o", "t", "s", "m", "o", "k"] },
                { word: "He uses a wheelchair to move", phonemes: ["h", "ee", "y", "oo", "z", "e", "z", "a", "w", "ee", "l", "ch", "e", "r", "t", "oo", "m", "oo", "v"] },
                { word: "Play a tune on the xylophone", phonemes: ["p", "l", "ay", "a", "t", "y", "oo", "n", "o", "n", "th", "e", "z", "ay", "l", "o", "f", "o", "n"] },
                { word: "Measure with a long wooden yardstick", phonemes: ["m", "e", "zh", "e", "r", "w", "i", "th", "a", "l", "o", "ng", "w", "u", "d", "e", "n", "y", "a", "r", "d", "s", "t", "i", "k"] },
                { word: "The green zucchini is a vegetable", phonemes: ["th", "e", "g", "r", "ee", "n", "z", "oo", "k", "ee", "n", "ee", "i", "z", "a", "v", "e", "j", "e", "t", "a", "b", "e", "l"] },
                { word: "The word blue is an adjective", phonemes: ["th", "e", "w", "e", "r", "d", "b", "l", "oo", "i", "z", "a", "n", "a", "j", "e", "k", "t", "i", "v"] }
            ],
            advanced: [
                { word: "The blue bird sings a song on the tree", phonemes: ["th", "e", "b", "l", "oo", "b", "e", "r", "d", "s", "i", "ng", "z", "a", "s", "o", "ng", "o", "n", "th", "e", "t", "r", "ee"] },
                { word: "I like to eat fresh fruit in the morning", phonemes: ["ay", "l", "ay", "k", "t", "oo", "ee", "t", "f", "r", "e", "sh", "f", "r", "oo", "t", "i", "n", "th", "e", "m", "o", "r", "n", "i", "ng"] },
                { word: "Wash your hands with soap before you eat food", phonemes: ["w", "o", "sh", "y", "o", "r", "h", "a", "n", "d", "z", "w", "i", "th", "s", "o", "p", "b", "e", "f", "o", "r", "y", "oo", "ee", "t", "f", "oo", "d"] },
                { word: "The sky is blue and clear during the day", phonemes: ["th", "e", "s", "k", "ay", "i", "z", "b", "l", "oo", "a", "n", "d", "k", "l", "e", "r", "d", "u", "r", "i", "ng", "th", "e", "d", "ay"] },
                { word: "I read a story book every night in bed", phonemes: ["ay", "r", "ee", "d", "a", "s", "t", "o", "r", "ee", "b", "u", "k", "e", "v", "r", "ee", "n", "ay", "t", "i", "n", "b", "e", "d"] },
                { word: "We play in the green park with our friends", phonemes: ["w", "ee", "p", "l", "ay", "i", "n", "th", "e", "g", "r", "ee", "n", "p", "a", "r", "k", "w", "i", "th", "ow", "r", "f", "r", "e", "n", "d", "z"] },
                { word: "Drink cold milk for breakfast to grow very strong", phonemes: ["d", "r", "i", "ng", "k", "k", "o", "l", "d", "m", "i", "l", "k", "f", "o", "r", "b", "r", "e", "k", "f", "a", "s", "t", "t", "oo", "g", "r", "o", "v", "e", "r", "ee", "s", "t", "r", "o", "ng"] },
                { word: "The sun shines on the trees in the morning", phonemes: ["th", "e", "s", "a", "n", "sh", "ay", "n", "z", "o", "n", "th", "e", "t", "r", "ee", "z", "i", "n", "th", "e", "m", "o", "r", "n", "i", "ng"] },
                { word: "Open the door for me when I come home", phonemes: ["o", "p", "e", "n", "th", "e", "d", "o", "r", "f", "o", "r", "m", "ee", "w", "e", "n", "ay", "k", "a", "m", "h", "o", "m"] },
                { word: "The small boy can run very fast in race", phonemes: ["th", "e", "s", "m", "o", "l", "b", "oy", "k", "a", "n", "r", "a", "n", "v", "e", "r", "ee", "f", "a", "s", "t", "i", "n", "r", "ay", "s"] },
                { word: "The cat naps on the mat in the room", phonemes: ["th", "e", "k", "a", "t", "n", "a", "p", "s", "o", "n", "th", "e", "m", "a", "t", "i", "n", "th", "e", "r", "oo", "m"] },
                { word: "Please sit on the chair and listen to me", phonemes: ["p", "l", "ee", "z", "s", "i", "t", "o", "n", "th", "e", "ch", "e", "r", "a", "n", "d", "l", "i", "s", "e", "n", "t", "oo", "m", "ee"] },
                { word: "I have a big red apple in my bag", phonemes: ["ay", "h", "a", "v", "a", "b", "i", "g", "r", "e", "d", "a", "p", "e", "l", "i", "n", "m", "ay", "b", "a", "g"] },
                { word: "Look at the stars tonight when the sky clear", phonemes: ["l", "u", "k", "a", "t", "th", "e", "s", "t", "a", "r", "z", "t", "u", "n", "ay", "t", "w", "e", "n", "th", "e", "s", "k", "ay", "k", "l", "e", "r"] },
                { word: "We go to school to learn how to read", phonemes: ["w", "ee", "g", "o", "t", "oo", "s", "k", "oo", "l", "t", "oo", "l", "e", "r", "n", "h", "ow", "t", "oo", "r", "ee", "d"] },
                { word: "The brown dog barks loud when it sees cat", phonemes: ["th", "e", "b", "r", "ow", "n", "d", "o", "g", "b", "a", "r", "k", "s", "l", "ow", "d", "w", "e", "n", "i", "t", "s", "ee", "z", "k", "a", "t"] },
                { word: "Water the plant in the pot every single day", phonemes: ["w", "o", "t", "e", "r", "th", "e", "p", "l", "a", "n", "t", "i", "n", "th", "e", "p", "o", "t", "e", "v", "r", "ee", "s", "i", "ng", "g", "e", "l", "d", "ay"] },
                { word: "Clean the room every day to keep it neat", phonemes: ["k", "l", "ee", "n", "th", "e", "r", "oo", "m", "e", "v", "r", "ee", "d", "ay", "t", "oo", "k", "ee", "p", "i", "t", "n", "ee", "t"] },
                { word: "The big boat is on the blue deep sea", phonemes: ["th", "e", "b", "i", "g", "b", "o", "t", "i", "z", "o", "n", "th", "e", "b", "l", "oo", "d", "ee", "p", "s", "ee"] },
                { word: "The rain falls on the roof of my house", phonemes: ["th", "e", "r", "ay", "n", "f", "o", "l", "z", "o", "n", "th", "e", "r", "oo", "f", "o", "v", "m", "ay", "h", "ow", "s"] },
                { word: "Eat your rice and fish to stay very healthy", phonemes: ["ee", "t", "y", "o", "r", "r", "ay", "s", "a", "n", "d", "f", "i", "sh", "t", "oo", "s", "t", "ay", "v", "e", "r", "ee", "h", "e", "l", "th", "ee"] },
                { word: "Brush your teeth every morning after you eat breakfast", phonemes: ["b", "r", "a", "sh", "y", "o", "r", "t", "ee", "th", "e", "v", "r", "ee", "m", "o", "r", "n", "i", "ng", "a", "f", "t", "e", "r", "y", "oo", "ee", "t", "b", "r", "e", "k", "f", "a", "s", "t"] },
                { word: "Draw a big house on the clean white paper", phonemes: ["d", "r", "o", "a", "b", "i", "g", "h", "ow", "s", "o", "n", "th", "e", "k", "l", "ee", "n", "w", "ay", "t", "p", "ay", "p", "e", "r"] },
                { word: "The moon glows at night when we are asleep", phonemes: ["th", "e", "m", "oo", "n", "g", "l", "o", "z", "a", "t", "n", "ay", "t", "w", "e", "n", "w", "ee", "a", "r", "a", "s", "l", "ee", "p"] },
                { word: "The green frog can jump very high and far", phonemes: ["th", "e", "g", "r", "ee", "n", "f", "r", "o", "g", "k", "a", "n", "j", "a", "m", "p", "v", "e", "r", "ee", "h", "ay", "a", "n", "d", "f", "a", "r"] },
                { word: "Do not pick the flower in the public park", phonemes: ["d", "oo", "n", "o", "t", "p", "i", "k", "th", "e", "f", "l", "ow", "e", "r", "i", "n", "th", "e", "p", "a", "b", "l", "i", "k", "p", "a", "r", "k"] },
                { word: "The school bell rings now for the next class", phonemes: ["th", "e", "s", "k", "oo", "l", "b", "e", "l", "r", "i", "ng", "z", "n", "ow", "f", "o", "r", "th", "e", "n", "e", "k", "s", "t", "k", "l", "a", "s"] },
                { word: "Wait for the yellow bus at the bus stop", phonemes: ["w", "ay", "t", "f", "o", "r", "th", "e", "y", "e", "l", "o", "b", "a", "s", "a", "t", "th", "e", "b", "a", "s", "s", "t", "o", "p"] },
                { word: "Ride the blue bike now in the front yard", phonemes: ["r", "ay", "d", "th", "e", "b", "l", "oo", "b", "ay", "k", "n", "ow", "i", "n", "th", "e", "f", "r", "a", "n", "t", "y", "a", "r", "d"] },
                { word: "The cold wind blows the leaves off the tree", phonemes: ["th", "e", "k", "o", "l", "d", "w", "i", "n", "d", "b", "l", "o", "z", "th", "e", "l", "ee", "v", "z", "o", "f", "th", "e", "t", "r", "ee"] },
                { word: "Buy some fresh bread at the store near us", phonemes: ["b", "ay", "s", "a", "m", "f", "r", "e", "sh", "b", "r", "e", "d", "a", "t", "th", "e", "s", "t", "o", "r", "n", "e", "r", "a", "s"] },
                { word: "Help your mom at home with the house work", phonemes: ["h", "e", "l", "p", "y", "o", "r", "m", "o", "m", "a", "t", "h", "o", "m", "w", "i", "th", "th", "e", "h", "ow", "s", "w", "e", "r", "k"] },
                { word: "She has a pretty doll with a pink dress", phonemes: ["sh", "ee", "h", "a", "z", "a", "p", "r", "i", "t", "ee", "d", "o", "l", "w", "i", "th", "a", "p", "i", "ng", "k", "d", "r", "e", "s"] },
                { word: "The green frog hops far into the deep pond", phonemes: ["th", "e", "g", "r", "ee", "n", "f", "r", "o", "g", "h", "o", "p", "s", "f", "a", "r", "i", "n", "t", "oo", "th", "e", "d", "ee", "p", "p", "o", "n", "d"] },
                { word: "Wear a big hat for the hot bright sun", phonemes: ["w", "e", "r", "a", "b", "i", "g", "h", "a", "t", "f", "o", "r", "th", "e", "h", "o", "t", "b", "r", "ay", "t", "s", "a", "n"] },
                { word: "The desk lamp is on in the dark room", phonemes: ["th", "e", "d", "e", "s", "k", "l", "a", "m", "p", "i", "z", "o", "n", "i", "n", "th", "e", "d", "a", "r", "k", "r", "oo", "m"] },
                { word: "The tall tree has fruit for us to eat", phonemes: ["th", "e", "t", "o", "l", "t", "r", "ee", "h", "a", "z", "f", "r", "oo", "t", "f", "o", "r", "a", "s", "t", "oo", "ee", "t"] },
                { word: "I see the blue ocean from the high mountain", phonemes: ["ay", "s", "ee", "th", "e", "b", "l", "oo", "o", "sh", "u", "n", "f", "r", "o", "m", "th", "e", "h", "ay", "m", "ow", "n", "t", "i", "n"] },
                { word: "The car is very red and fast on road", phonemes: ["th", "e", "k", "a", "r", "i", "z", "v", "e", "r", "ee", "r", "e", "d", "a", "n", "d", "f", "a", "s", "t", "o", "n", "r", "o", "d"] },
                { word: "Sit and rest on the bench in the garden", phonemes: ["s", "i", "t", "a", "n", "d", "r", "e", "s", "t", "o", "n", "th", "e", "b", "e", "n", "ch", "i", "n", "th", "e", "g", "a", "r", "d", "e", "n"] },
                { word: "The bird flies so high in the blue sky", phonemes: ["th", "e", "b", "e", "r", "d", "f", "l", "ay", "z", "s", "o", "h", "ay", "i", "n", "th", "e", "b", "l", "oo", "s", "k", "ay"] },
                { word: "Find the key in your bag or the pocket", phonemes: ["f", "ay", "n", "d", "th", "e", "k", "ee", "i", "n", "y", "o", "r", "b", "a", "g", "o", "r", "th", "e", "p", "o", "k", "e", "t"] },
                { word: "The milk is very cold and good for you", phonemes: ["th", "e", "m", "i", "l", "k", "i", "z", "v", "e", "r", "ee", "k", "o", "l", "d", "a", "n", "d", "g", "u", "d", "f", "o", "r", "y", "oo"] },
                { word: "Play the drum for me at the music room", phonemes: ["p", "l", "ay", "th", "e", "d", "r", "a", "m", "f", "o", "r", "m", "ee", "a", "t", "th", "e", "m", "y", "oo", "z", "i", "k", "r", "oo", "m"] },
                { word: "The cake is very sweet and yum to eat", phonemes: ["th", "e", "k", "ay", "k", "i", "z", "v", "e", "r", "ee", "s", "w", "ee", "t", "a", "n", "d", "y", "a", "m", "t", "oo", "ee", "t"] },
                { word: "The truck is very fast and loud on road", phonemes: ["th", "e", "t", "r", "a", "k", "i", "z", "v", "e", "r", "ee", "f", "a", "s", "t", "a", "n", "d", "l", "ow", "d", "o", "n", "r", "o", "d"] },
                { word: "Wash your face now with some cold clean water", phonemes: ["w", "o", "sh", "y", "o", "r", "f", "ay", "s", "n", "ow", "w", "i", "th", "s", "a", "m", "k", "o", "l", "d", "k", "l", "ee", "n", "w", "o", "t", "e", "r"] },
                { word: "The sky glows at sunset over the blue sea", phonemes: ["th", "e", "s", "k", "ay", "g", "l", "o", "z", "a", "t", "s", "a", "n", "s", "e", "t", "o", "v", "e", "r", "th", "e", "b", "l", "oo", "s", "ee"] },
                { word: "Eat a sweet yellow banana for your morning snack", phonemes: ["ee", "t", "a", "s", "w", "ee", "t", "y", "e", "l", "o", "b", "a", "n", "a", "n", "a", "f", "o", "r", "y", "o", "r", "m", "o", "r", "n", "i", "ng", "s", "n", "a", "k"] },
                { word: "The white gate is open for the big car", phonemes: ["th", "e", "w", "ay", "t", "g", "ay", "t", "i", "z", "o", "p", "e", "n", "f", "o", "r", "th", "e", "b", "i", "g", "k", "a", "r"] }
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