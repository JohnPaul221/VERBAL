<?php
global $pdo;
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || $_SESSION['grade'] != '4') {
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
            /* Grade 4 Friendly - Bright & Playful Colors */
            --primary: #FF6B6B;        /* Soft Coral Red */
            --success: #20BF6B;        /* Grass Green */
            --kids-blue: #3867D6;      /* Royal Blue */
            --kids-yellow: #F7B731;    /* Sunny Orange-Yellow */
            --bg-gradient: linear-gradient(135deg, #D1F2FF 0%, #FFFFFF 100%);
        }

        /* --- Full Screen Fit --- */
        body {
            height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            padding: 0 15px;
            color: #2D3436; /* Dark text for light bg */
            margin: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- Header --- */
        .page-header { height: 60px; position: relative; flex-shrink: 0; }
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #D4AF37; z-index: 100; color: #43380D; font-weight: bold; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #2549A8; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #B33939; border-radius: 10px; z-index: 10; cursor: pointer; }

        /* --- Main Layout --- */
        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #FFFFFF; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #BDC3C7; border: 5px solid var(--kids-blue);
            box-sizing: border-box;
        }

        /* --- Sections --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F8F9FA;
            display: flex; flex-direction: column; border: 2px solid #DCDDE1;
            align-items: center; text-align: center;
            overflow: visible;
            justify-content: space-between;
        }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F1F2F6;
            display: flex; flex-direction: column; border: 2px solid #CED6E0;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-blue); opacity: 0.2; border-radius: 10px; }

        /* --- IMAGE BOX + FULL SCREEN POPUP --- */
        .word-image-box {
            width: 120px;
            height: 120px;
            border: 4px solid var(--success);
            border-radius: 15px;
            background: #FFFFFF;
            position: relative;
            background-size: cover;
            background-position: center;
            cursor: zoom-in;
            flex-shrink: 0;
            visibility: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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
            background-color: #FFFFFF;
            z-index: 9999;
            box-shadow: 0 0 0 100vmax rgba(255,255,255,0.8), 0 20px 50px rgba(0,0,0,0.2);
            border: 8px solid var(--kids-blue);
            border-radius: 25px;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes popIn {
            from { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        /* --- UI ELEMENTS --- */
        h2, h3 { font-size: 1.1rem; margin: 5px 0; color: #2D3436; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; }
        #difficulty { width: 90%; padding: 8px; border-radius: 12px; border: 3px solid var(--kids-blue); font-family: inherit; font-weight: bold; margin-bottom: 5px; background: white; color: var(--kids-blue); }
        .mic-icon { width: 65px; height: 65px; background: var(--primary); border: 5px solid #FF8E8E; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; margin: 5px auto; box-shadow: 0 4px 0px #B33939; }
        .reading-material { background: #FFFFFF; border-radius: 20px; border: 3px dashed var(--success); padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }
        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: var(--kids-blue); text-transform: uppercase; line-height: 1.1; text-shadow: 2px 2px #E1F5FE; }
        .transcript { font-size: 1.1rem; font-weight: bold; color: var(--success); background: #F0FFF4; border: 2px solid var(--success); border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        /* --- PRO SCOREBOARD --- */
        .stats-container { display: flex; justify-content: space-around; align-items: center; background: #FFFFFF; padding: 15px 10px; border-radius: 20px; border: 5px solid var(--kids-yellow); margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #D4AF37; }
        .stat-item span { display: block; font-size: 0.8rem; color: #636E72; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: #2D3436; line-height: 1; text-shadow: 1px 1px 0px #DFE6E9; }

        /* --- PROGRESS BAR --- */
        .progress-bar { width: 100%; height: 25px; background: #DFE6E9 !important; border-radius: 15px; border: 4px solid #BDC3C7; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: var(--success) !important; transition: width 0.6s ease-in-out; }

        /* --- PRO SENTENCE --- */
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid var(--kids-yellow); color: #2D3436; min-height: 60px; display: flex; align-items: center; justify-content: center; visibility: hidden; }
        .bg-success-feedback { background: #D1FAE5 !important; color: #065F46 !important; border: 3px solid #10B981 !important; }

        /* --- PRO STARS --- */
        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #DFE6E9; transition: color 0.3s ease; }
        .filled-star { color: var(--kids-yellow) !important; text-shadow: 0 0 5px rgba(247, 183, 49, 0.5); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; transform-origin: center; }
        @keyframes starPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.4); } 100% { transform: scale(1); opacity: 1; } }

        /* --- PRO UI MYSTERY MATCHING --- */
        #memoryGrid { background: rgba(209, 242, 255, 0.5); padding: 20px; border-radius: 20px; border: 3px solid var(--kids-blue); }
        .memory-card {
            width: 80px; height: 80px;
            background: linear-gradient(145deg, var(--kids-blue), #4B7BEC);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 2.8rem;
            border-radius: 18px; cursor: pointer; box-shadow: 0 6px 0 #2549A8, 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.3); position: relative;
        }
        .memory-card:hover { transform: translateY(-5px) scale(1.05); filter: brightness(1.1); }
        .memory-card::before { content: "?"; font-weight: 900; opacity: 0.5; color: white; }
        .memory-card.flipped { background: #FFFFFF; color: var(--kids-blue); border: 4px solid var(--kids-yellow); box-shadow: 0 6px 0 #D4AF37; animation: cardAppear 0.4s ease-out forwards; }
        .memory-card.flipped::before { content: ""; }
        .memory-card.matched { background: var(--success); border-color: #1B8D52; animation: matchedSuccess 0.5s ease-out forwards; pointer-events: none; }
        @keyframes cardAppear { 0% { transform: scale(0.5) rotateY(0deg); opacity: 0; } 100% { transform: scale(1) rotateY(180deg); opacity: 1; } }
        @keyframes matchedSuccess { 0% { transform: scale(1); } 50% { transform: scale(1.2); filter: brightness(1.1); } 100% { transform: scale(0); opacity: 0; visibility: hidden; } }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid var(--kids-blue); background: #FFFFFF; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }
        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--kids-yellow); color: #43380D; box-shadow: 0 4px 0 #D4AF37; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 4px 0 #2549A8; width: 90%; }
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
                { word: "The heavy rain flooded the street", phonemes: ["th", "e", "h", "e", "v", "ee", "r", "ay", "n", "f", "l", "a", "d", "e", "d", "th", "e", "s", "t", "r", "ee", "t"], example: "We couldn't go outside because the heavy rain flooded the street." },
                { word: "She wears a necklace made of shells", phonemes: ["sh", "ee", "w", "e", "r", "z", "a", "n", "e", "k", "l", "a", "s", "m", "ay", "d", "o", "v", "sh", "e", "l", "z"], example: "During her beach trip, she wears a necklace made of shells." },
                { word: "Our teacher wrote on the blackboard", phonemes: ["ow", "r", "t", "ee", "ch", "e", "r", "r", "o", "t", "o", "n", "th", "e", "b", "l", "a", "k", "b", "o", "r", "d"], example: "The lesson started when our teacher wrote on the blackboard." },
                { word: "The eagle flies above the clouds", phonemes: ["th", "e", "ee", "g", "e", "l", "f", "l", "ay", "z", "a", "b", "a", "v", "th", "e", "k", "l", "ow", "d", "z"], example: "It was a beautiful sight as the eagle flies above the clouds." },
                { word: "The market is crowded every Sunday", phonemes: ["th", "e", "m", "a", "r", "k", "e", "t", "i", "z", "k", "r", "ow", "d", "e", "d", "e", "v", "r", "ee", "s", "a", "n", "d", "ay"], example: "We go early because the market is crowded every Sunday." },
                { word: "We use a compass for directions", phonemes: ["w", "ee", "y", "oo", "z", "a", "k", "a", "m", "p", "a", "s", "f", "o", "r", "d", "i", "r", "e", "k", "sh", "u", "n", "z"], example: "When we go hiking in the woods, we use a compass for directions." },
                { word: "The farmer harvested the ripe corn", phonemes: ["th", "e", "f", "a", "r", "m", "e", "r", "h", "a", "r", "v", "e", "s", "t", "e", "d", "th", "e", "r", "ay", "p", "k", "o", "r", "n"], example: "After months of waiting, the farmer harvested the ripe corn." },
                { word: "A forest provides oxygen for us", phonemes: ["a", "f", "o", "r", "e", "s", "t", "p", "r", "o", "v", "ay", "d", "z", "o", "k", "s", "i", "j", "e", "n", "f", "o", "r", "a", "s"], example: "We must plant trees because a forest provides oxygen for us." },
                { word: "The moon reflects light from sun", phonemes: ["th", "e", "m", "oo", "n", "r", "e", "f", "l", "e", "k", "t", "s", "l", "ay", "t", "f", "r", "o", "m", "s", "a", "n"], example: "At night, the moon reflects light from sun to brighten the earth." },
                { word: "He bought a new pair of shoes", phonemes: ["h", "ee", "b", "o", "t", "a", "n", "y", "oo", "p", "e", "r", "o", "v", "sh", "oo", "z"], example: "For the upcoming party, he bought a new pair of shoes." }
            ],
            intermediate: [
                { word: "The caterpillar turned into a beautiful butterfly", phonemes: ["th", "e", "k", "a", "t", "e", "r", "p", "i", "l", "e", "r", "t", "e", "r", "n", "d", "i", "n", "t", "oo", "a", "b", "y", "oo", "t", "i", "f", "u", "l", "b", "a", "t", "e", "r", "f", "l", "ay"], example: "We watched as the caterpillar turned into a beautiful butterfly." },
                { word: "The Philippines is an archipelago with many islands", phonemes: ["th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "z", "a", "n", "a", "r", "k", "i", "p", "e", "l", "a", "g", "o", "w", "i", "th", "m", "e", "n", "ee", "ay", "l", "a", "n", "d", "z"], example: "Travelers love that the Philippines is an archipelago with many islands." },
                { word: "We should throw our trash in the bin", phonemes: ["w", "ee", "sh", "u", "d", "th", "r", "o", "ow", "r", "t", "r", "a", "sh", "i", "n", "th", "e", "b", "i", "n"], example: "To keep our classroom clean, we should throw our trash in the bin." },
                { word: "Magellan arrived in the Philippines in fifteen twenty-one", phonemes: ["m", "a", "g", "e", "l", "a", "n", "a", "r", "ay", "v", "d", "i", "n", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "n", "f", "i", "f", "t", "ee", "n", "t", "w", "e", "n", "t", "ee", "w", "a", "n"], example: "Our history book says Magellan arrived in the Philippines in fifteen twenty-one." },
                { word: "The heart pumps blood to our whole body", phonemes: ["th", "e", "h", "a", "r", "t", "p", "a", "m", "p", "s", "b", "l", "a", "d", "t", "oo", "ow", "r", "h", "o", "l", "b", "o", "d", "ee"], example: "To keep us alive, the heart pumps blood to our whole body." },
                { word: "Solid liquid and gas are states of matter", phonemes: ["s", "o", "l", "i", "d", "l", "i", "k", "w", "i", "d", "a", "n", "d", "g", "a", "s", "a", "r", "s", "t", "ay", "t", "s", "o", "v", "m", "a", "t", "e", "r"], example: "In chemistry, we learn that solid liquid and gas are states of matter." },
                { word: "The teacher explained the lesson very clearly today", phonemes: ["th", "e", "t", "ee", "ch", "e", "r", "i", "k", "s", "p", "l", "ay", "n", "d", "th", "e", "l", "e", "s", "o", "n", "v", "e", "r", "ee", "k", "l", "e", "r", "l", "ee", "t", "u", "d", "ay"], example: "I understood everything because the teacher explained the lesson very clearly today." },
                { word: "Proper nutrition makes our bones and muscles strong", phonemes: ["p", "r", "o", "p", "e", "r", "n", "y", "oo", "t", "r", "i", "sh", "u", "n", "m", "ay", "k", "s", "ow", "r", "b", "o", "n", "z", "a", "n", "d", "m", "a", "s", "e", "l", "z", "s", "t", "r", "o", "ng"], example: "Eating vegetables and proper nutrition makes our bones and muscles strong." },
                { word: "We use a thermometer to measure body heat", phonemes: ["w", "ee", "y", "oo", "z", "a", "th", "e", "r", "m", "o", "m", "e", "t", "e", "r", "t", "oo", "m", "e", "zh", "e", "r", "b", "o", "d", "ee", "h", "ee", "t"], example: "When I have a fever, we use a thermometer to measure body heat." },
                { word: "The local government helps the people in community", phonemes: ["th", "e", "l", "o", "k", "a", "l", "g", "a", "v", "e", "r", "n", "m", "e", "n", "t", "h", "e", "l", "p", "s", "th", "e", "p", "ee", "p", "e", "l", "i", "n", "k", "o", "m", "y", "oo", "n", "i", "t", "ee"], example: "During the flood, the local government helps the people in community." }
            ],
            advanced: [
                { word: "The water cycle includes evaporation and condensation and precipitation", phonemes: ["th", "e", "w", "o", "t", "e", "r", "s", "ay", "k", "e", "l", "i", "n", "k", "l", "oo", "d", "z", "i", "v", "a", "p", "o", "r", "ay", "sh", "u", "n", "a", "n", "d", "k", "o", "n", "d", "e", "n", "s", "ay", "sh", "u", "n", "a", "n", "d", "p", "r", "ee", "s", "i", "p", "i", "t", "ay", "sh", "u", "n"], example: "To understand why it rains, we learned how the water cycle includes evaporation and condensation and precipitation." },
                { word: "Our community helpers include the doctors and nurses and teachers", phonemes: ["ow", "r", "k", "o", "m", "y", "oo", "n", "i", "t", "ee", "h", "e", "l", "p", "e", "r", "z", "i", "n", "k", "l", "oo", "d", "th", "e", "d", "o", "k", "t", "o", "r", "z", "a", "n", "d", "n", "e", "r", "s", "e", "z", "a", "n", "d", "t", "ee", "ch", "e", "r", "z"], example: "We are thankful because our community helpers include the doctors and nurses and teachers." },
                { word: "We use our five senses to explore the world around", phonemes: ["w", "ee", "y", "oo", "z", "ow", "r", "f", "ay", "v", "s", "e", "n", "s", "e", "z", "t", "oo", "i", "k", "s", "p", "l", "o", "r", "th", "e", "w", "e", "r", "l", "d", "a", "r", "ow", "n", "d"], example: "From tasting food to seeing colors, we use our five senses to explore the world around." },
                { word: "Trees are important because they give us fresh clean air", phonemes: ["t", "r", "ee", "z", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "b", "i", "k", "o", "z", "th", "ay", "g", "i", "v", "a", "s", "f", "r", "e", "sh", "k", "l", "ee", "n", "e", "r"], example: "We should stop cutting forests since trees are important because they give us fresh clean air." },
                { word: "The heart and lungs work together to give us oxygen", phonemes: ["th", "e", "h", "a", "r", "t", "a", "n", "d", "l", "a", "ng", "z", "w", "e", "r", "k", "t", "oo", "g", "e", "th", "e", "r", "t", "oo", "g", "i", "v", "a", "s", "o", "k", "s", "i", "j", "e", "n"], example: "When we exercise, the heart and lungs work together to give us oxygen." },
                { word: "Computers allow us to send emails to people far away", phonemes: ["k", "o", "m", "p", "y", "oo", "t", "e", "r", "z", "a", "l", "ow", "a", "s", "t", "oo", "s", "e", "n", "d", "ee", "m", "ay", "l", "z", "t", "oo", "p", "ee", "p", "e", "l", "f", "a", "r", "a", "w", "ay"], example: "Communication is faster because computers allow us to send emails to people far away." },
                { word: "The Philippines is rich in natural beauty and many resources", phonemes: ["th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "z", "r", "i", "ch", "i", "n", "n", "a", "ch", "u", "r", "a", "l", "b", "y", "oo", "t", "ee", "a", "n", "d", "m", "e", "n", "ee", "r", "ee", "s", "o", "r", "s", "e", "z"], example: "Tourists visit often because the Philippines is rich in natural beauty and many resources." },
                { word: "Honesty and respect are important values for every young child", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "a", "n", "d", "r", "e", "s", "p", "e", "k", "t", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "v", "a", "l", "y", "oo", "z", "f", "o", "r", "e", "v", "r", "ee", "y", "a", "ng", "ch", "ay", "l", "d"], example: "In school, we are taught that honesty and respect are important values for every young child." },
                { word: "The butterfly went through four stages of very slow metamorphosis", phonemes: ["th", "e", "b", "a", "t", "e", "r", "f", "l", "ay", "w", "e", "n", "t", "th", "r", "oo", "f", "o", "r", "s", "t", "ay", "j", "e", "z", "o", "v", "v", "e", "r", "ee", "s", "l", "o", "m", "e", "t", "a", "m", "o", "r", "f", "o", "s", "i", "s"], example: "We observed in class how the butterfly went through four stages of very slow metamorphosis." },
                { word: "Always listen carefully when the teacher is explaining the lesson", phonemes: ["o", "l", "w", "ay", "z", "l", "i", "s", "e", "n", "k", "e", "r", "f", "u", "l", "ee", "w", "e", "n", "th", "e", "t", "ee", "ch", "e", "r", "i", "z", "i", "k", "s", "p", "l", "ay", "n", "i", "ng", "th", "e", "l", "e", "s", "o", "n"], example: "To get a high score, always listen carefully when the teacher is explaining the lesson." },
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