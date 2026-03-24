<?php
global $pdo;
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || $_SESSION['grade'] != '6') {
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
            /* New Creative Nature Colors */
            --primary: #FF7F50;        /* Coral Orange */
            --success: #2ECC71;        /* Emerald Green */
            --kids-blue: #00B894;      /* Minty Teal */
            --kids-yellow: #FDCB6E;    /* Soft Sunshine Yellow */
            --bg-gradient: linear-gradient(135deg, #74B9FF 0%, #FFFFFF 100%);
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
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #E1B12C; z-index: 100; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #009473; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #D35400; border-radius: 10px; z-index: 10; cursor: pointer; }

        /* --- Main Layout --- */
        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #FFFFFF; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #B2BEC3; border: 4px solid var(--kids-blue);
            box-sizing: border-box;
        }

        /* --- Sections --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F9F9F9;
            display: flex; flex-direction: column; border: 2px solid #DFE6E9;
            align-items: center; text-align: center;
            overflow: visible;
            justify-content: space-between;
        }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F9F9F9;
            display: flex; flex-direction: column; border: 2px solid #DFE6E9;
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
        .reading-material { background: #F0FFF4; border-radius: 20px; border: 3px dashed var(--kids-blue); padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }
        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: #2D3436; text-transform: uppercase; line-height: 1.1; }
        .transcript { font-size: 1.1rem; font-weight: bold; color: #009432; background: #E8F5E9; border: 2px solid #2ECC71; border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        /* --- PRO SCOREBOARD --- */
        .stats-container { display: flex; justify-content: space-around; align-items: center; background: white; padding: 15px 10px; border-radius: 20px; border: 5px solid var(--kids-blue); margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #009473; }
        .stat-item span { display: block; font-size: 0.8rem; color: #636E72; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: var(--kids-blue); line-height: 1; text-shadow: 1px 1px 0px #F5F6FA; }

        /* --- PROGRESS BAR --- */
        .progress-bar { width: 100%; height: 25px; background: #FFFFFF !important; border-radius: 15px; border: 4px solid #DFE6E9; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: var(--success) !important; transition: width 0.6s ease-in-out; }

        /* --- PRO SENTENCE --- */
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid #DFE6E9; color: #2D3436; min-height: 60px; display: flex; align-items: center; justify-content: center; visibility: hidden; }
        .bg-success-feedback { background: #E8F5E9 !important; color: #2E7D32 !important; border: 3px solid #4CAF50 !important; }

        /* --- PRO STARS --- */
        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #DCDDE1; transition: color 0.3s ease; }
        .filled-star { color: #FDCB6E !important; text-shadow: 0 0 15px rgba(253, 203, 110, 0.6); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, starShine 2s infinite linear; transform-origin: center; }
        @keyframes starPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.4); } 100% { transform: scale(1); opacity: 1; } }
        @keyframes starShine { 0% { text-shadow: 0 0 10px rgba(253, 203, 110, 0.5); } 50% { text-shadow: 0 0 20px rgba(253, 203, 110, 0.8), 0 0 30px rgba(255, 255, 255, 0.5); } 100% { text-shadow: 0 0 10px rgba(253, 203, 110, 0.5); } }

        /* --- PRO UI MYSTERY MATCHING --- */
        #memoryGrid { background: rgba(255, 255, 255, 0.5); padding: 20px; border-radius: 20px; border: 2px solid #F0FFF4; }
        .memory-card {
            width: 80px; height: 80px;
            background: linear-gradient(145deg, var(--kids-blue), #009473);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 2.8rem;
            border-radius: 18px; cursor: pointer; box-shadow: 0 6px 0 #006266, 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(255, 255, 255, 0.3); position: relative;
        }
        .memory-card:hover { transform: translateY(-5px) scale(1.05); filter: brightness(1.1); }
        .memory-card::before { content: "?"; font-weight: 900; opacity: 0.5; }
        .memory-card.flipped { background: white; color: #2D3436; border: 4px solid var(--kids-yellow); box-shadow: 0 6px 0 #E1B12C; animation: cardAppear 0.4s ease-out forwards; }
        .memory-card.flipped::before { content: ""; }
        .memory-card.matched { background: #00D2D3; border-color: #01A3A4; animation: matchedSuccess 0.5s ease-out forwards; pointer-events: none; }
        @keyframes cardAppear { 0% { transform: scale(0.5) rotateY(0deg); opacity: 0; } 100% { transform: scale(1) rotateY(180deg); opacity: 1; } }
        @keyframes matchedSuccess { 0% { transform: scale(1); } 50% { transform: scale(1.2); filter: brightness(1.5); } 100% { transform: scale(0); opacity: 0; visibility: hidden; } }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid var(--kids-blue); background: #F0FFF4; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }
        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--kids-yellow); color: #574B15; box-shadow: 0 4px 0 #E1B12C; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 4px 0 #009473; width: 90%; }
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
                { sentence: "Keep the surroundings clean", phonemes: ["k", "ee", "p", "s", "a", "r", "ow", "n", "d", "i", "ng", "z"], example: "We should keep our surroundings clean and green." },
                { sentence: "The plant grows tall in the yard", phonemes: ["th", "e", "p", "l", "a", "n", "t", "g", "r", "o", "z"], example: "The plant grows tall in the garden." },
                { sentence: "Birds fly high in the blue sky", phonemes: ["b", "e", "r", "d", "z", "f", "l", "ay"], example: "Many birds fly in the blue sky today." },
                { sentence: "Eat your lunch in the room now", phonemes: ["ee", "t", "y", "o", "r", "l", "a", "n", "ch"], example: "Go and eat your lunch in the room." },
                { sentence: "Wash your face to stay fresh", phonemes: ["w", "o", "sh", "y", "o", "r", "f", "ay", "s"], example: "Wash your face daily to stay fresh." },
                { sentence: "The water in the cup is cold", phonemes: ["th", "e", "w", "o", "t", "e", "r"], example: "The water in the cup is very cold." },
                { sentence: "Read a new story book today", phonemes: ["r", "ee", "d", "a", "n", "y", "oo", "s", "t", "o", "r", "ee"], example: "I like to read a new story book." },
                { sentence: "The red car moves very fast", phonemes: ["th", "e", "k", "a", "r", "m", "oo", "v", "z"], example: "The red car moves very fast now." },
                { sentence: "Open the heavy door for me", phonemes: ["o", "p", "e", "n", "th", "e", "h", "e", "v", "ee"], example: "Please open the heavy door for me." },
                { sentence: "The stars are bright tonight", phonemes: ["th", "e", "s", "t", "a", "r", "z"], example: "The stars are bright in the sky." },
                { sentence: "Wear your clean shoes to school", phonemes: ["w", "e", "r", "y", "o", "r", "sh", "oo", "z"], example: "Wear your clean shoes to the party." }
            ],
            intermediate: [
                { sentence: "Understand the natural water cycle", phonemes: ["a", "n", "d", "e", "r", "s", "t", "a", "n", "d"], example: "The sun's heat causes water to evaporate during the cycle." },
                { sentence: "The Philippine flag is high above", phonemes: ["f", "l", "a", "g"], example: "The Philippine flag is high above the school grounds." },
                { sentence: "Practice safe and healthy habits", phonemes: ["p", "r", "a", "k", "t", "i", "s"], example: "We practice safe health habits to prevent the spread of germs." },
                { sentence: "Study how local government helps", phonemes: ["g", "a", "v", "e", "r", "n", "m", "e", "n", "t"], example: "We study how our local government helps the community." },
                { sentence: "Maintain a peaceful garden to relax", phonemes: ["m", "ay", "n", "t", "ay", "n"], example: "A peaceful garden is a great place to relax and read books." },
                { sentence: "Planting trees reduces air pollution", phonemes: ["p", "u", "l", "oo", "sh", "u", "n"], example: "Planting more trees helps to reduce air pollution in cities." },
                { sentence: "A balanced ecosystem helps animals", phonemes: ["ee", "k", "o", "s", "i", "s", "t", "e", "m"], example: "A balanced ecosystem allows animals and plants to survive." },
                { sentence: "Drivers must follow traffic signals", phonemes: ["s", "i", "g", "n", "e", "l", "z"], example: "Drivers must follow the traffic signals to avoid accidents." },
                { sentence: "Clean energy comes from the sun", phonemes: ["e", "n", "e", "r", "j", "ee"], example: "Clean energy comes from the sun using solar panels." },
                { sentence: "The giant telescope sees planets", phonemes: ["t", "e", "l", "e", "s", "k", "o", "p"], example: "The giant telescope sees far away planets in the galaxy." },
                { sentence: "We must respect all human rights", phonemes: ["r", "ee", "s", "p", "e", "k", "t"], example: "We must respect all human rights for a fair society." }
            ],
            advanced: [
                { sentence: "The circulatory system moves blood", phonemes: ["s", "e", "r", "k", "y", "u", "l", "a", "t", "o", "r", "ee"], example: "The circulatory system moves blood and nutrients to all your cells." },
                { sentence: "The nervous system controls the body", phonemes: ["n", "e", "r", "v", "u", "s"], example: "The nervous system controls the body by sending fast electrical signals." },
                { sentence: "Biodiversity is essential for nature", phonemes: ["b", "ay", "o", "d", "ay", "v", "e", "r", "s", "i", "t", "ee"], example: "Biodiversity is essential for nature to maintain a healthy environment." },
                { sentence: "Climate change causes global warming", phonemes: ["k", "l", "ay", "m", "e", "t"], example: "Climate change causes global warming which melts the polar ice." },
                { sentence: "Sustainability ensures a better future", phonemes: ["s", "a", "s", "t", "ay", "n", "a", "b", "i", "l", "i", "t", "ee"], example: "Sustainability ensures a better future for the next generations." },
                { sentence: "Renewable energy reduces pollution", phonemes: ["r", "ee", "n", "y", "oo", "a", "b", "e", "l"], example: "Renewable energy reduces pollution and helps the ozone layer." },
                { sentence: "Freedom of speech is a basic right", phonemes: ["f", "r", "ee", "d", "u", "m"], example: "Freedom of speech is a fundamental right in any democracy." },
                { sentence: "Scientific research drives innovation", phonemes: ["s", "ay", "e", "n", "t", "i", "f", "i", "k"], example: "Scientific research drives innovation in medicine and technology." },
                { sentence: "The atmosphere has many layers now", phonemes: ["a", "t", "m", "u", "s", "f", "i", "r"], example: "The atmosphere has many layers that protect our planet." },
                { sentence: "Plate tectonics move the continents", phonemes: ["p", "l", "ay", "t", "t", "e", "k", "t", "o", "n", "i", "k", "s"], example: "Plate tectonics move the continents over millions of years." },
                { sentence: "The human brain is very complex", phonemes: ["h", "y", "oo", "m", "e", "n", "b", "r", "ay", "n"], example: "The human brain is very complex and controls the body." },
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
            const sentenceWords = (currentWord.sentence || "").replace(/[.,!]/g, "").split(' ');
            let targetWord = "";

            // FIXED LOGIC & SYNTAX
            if (currentDifficulty === 'beginner') {
                allProgress.beginner.words_correct = begCountFromDB;
                targetWord = sentenceWords[0];
            } else if (currentDifficulty === 'intermediate') {
                allProgress.intermediate.words_correct = intCountFromDB;
                let medWords = sentenceWords.filter(w => w.length >= 5 && w.length <= 7);
                targetWord = medWords.length > 0 ? medWords[Math.floor(Math.random() * medWords.length)] : sentenceWords[sentenceWords.length - 1];
            } else {
                targetWord = sentenceWords.reduce((a, b) => a.length > b.length ? a : b);
            }

            targetWord = targetWord.toUpperCase().replace(/[.,!?;:]/g, "");
            const scrambled = targetWord.split('').sort(() => 0.5 - Math.random()).join(' ');

            Swal.fire({
                title: title,
                html: `
            <div style="background: #F1F2F6; padding: 20px; border-radius: 15px; border: 2px solid #0984E3;">
                <p style="color: #2F3542; font-weight: bold; margin-bottom: 15px;">Level: ${currentDifficulty.toUpperCase()}<br>Unscramble this word:</p>
                <h1 style="letter-spacing: 8px; font-size: 2.5rem; color: #D63031; margin: 20px 0;">${scrambled}</h1>
                <input type="text" id="scrambleInput" class="swal2-input" placeholder="Type answer here..." style="text-align:center; text-transform: uppercase;">
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
            const targetText = currentWord.sentence || currentWord.word;
            const score = ratePronunciation(spoken, targetText);

            if (score === 5) {
                // FIX: Tinanggal ang .replace logic para MAISAMA ang tuldok (.) sa dulo ng sentence
                // Ginawa lang nating lowercase at trim para malinis.
                const imgKey = currentWord.example.toLowerCase().trim();

                // Debugging: Makita natin sa Console (F12) kung ano ang hinahanap na name
                console.log("Looking for image key:", imgKey);

                if (imageLibrary[imgKey]) {
                    const imgPath = imageLibrary[imgKey];
                    const imgElement = document.getElementById("wordImage");

                    if (imgElement) {
                        imgElement.src = imgPath;
                    }

                    imageBox.style.backgroundImage = `url('${imgPath}')`;
                    imageBox.style.visibility = "visible";
                    imageBox.classList.remove("hidden");

                    console.log("Success! Image path is:", imgPath);
                } else {
                    // Kung ayaw pa rin lumabas, i-check ang console para makita kung ano ang mismatch
                    console.log("Failed to find key in library. Library keys are:", Object.keys(imageLibrary));
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
            let score = p.words_correct;
            let displayScore = score > 40 ? 40 : score;
            document.getElementById("progressText").textContent = displayScore + " / 40";
            document.getElementById("progressBarFill").style.width = (displayScore / 40 * 100) + "%";

            if (score >= 40 && !p.isResetting) setTimeout(() => triggerLevelCompleteCelebration(), 600);

            document.getElementById("attemptedCount").textContent = p.words_attempted;
            const accuracy = p.words_attempted > 0 ? Math.round((p.words_correct / p.words_attempted) * 100) : 0;
            document.getElementById("accuracyRate").textContent = accuracy + "%";
        }

        // --- GAMEPLAY FLOW ---

        function loadNextWord() {
            // Itago muna ang image box tuwing bagong salita
            imageBox.style.visibility = "hidden";
            imageBox.classList.add("hidden");
            imageBox.style.backgroundImage = "none";
            document.getElementById("wordImage").src = "";

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

            const targetText = currentWord.sentence || currentWord.word;
            wordDisplay.textContent = targetText;
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

        // --- DB SYNC ---

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
            formData.append('words_correct', p.words_correct);
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

        // --- MIC LOGIC ---

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
                if (p.words_correct > 0 && p.words_correct % 5 === 0) {
                    triggerMysteryGame("bonus");
                } else {
                    p.word_index++;
                    saveProgressToDB();
                    loadNextWord();
                }
            });

            playWordBtn.addEventListener("click", () => speak(currentWord.sentence || currentWord.word));
            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));
            micBtn.addEventListener("click", toggleMic);

            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = true;
                recognition.onresult = (e) => {
                    let interim = '';
                    for (let i = e.resultIndex; i < e.results.length; ++i) {
                        if (e.results[i].isFinal) {
                            transcriptEl.textContent = e.results[i][0].transcript;
                            checkPronunciation(e.results[i][0].transcript);
                            stopMicLogic();
                        } else {
                            interim = e.results[i][0].transcript;
                            transcriptEl.textContent = interim;
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