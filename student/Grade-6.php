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
                { sentence: "Wear your clean shoes to school", phonemes: ["w", "e", "r", "y", "o", "r", "sh", "oo", "z"], example: "Wear your clean shoes to the party." },
                { sentence: "The moon is round and bright", phonemes: ["th", "e", "m", "oo", "n", "i", "z", "r", "ow", "n", "d"], example: "The moon is round and very bright." },
                { sentence: "Sit on the wood chair please", phonemes: ["s", "i", "t", "o", "n", "th", "e", "ch", "e", "r"], example: "Sit on the wood chair in the hall." },
                { sentence: "The rain falls on the grass", phonemes: ["th", "e", "r", "ay", "n", "f", "o", "l", "z"], example: "The rain falls down on the grass." },
                { sentence: "Drink warm milk before you bed", phonemes: ["d", "r", "i", "ng", "k", "w", "o", "r", "m"], example: "Drink warm milk tonight before bed." },
                { sentence: "The school bell rings at noon", phonemes: ["th", "e", "b", "e", "l", "r", "i", "ng", "z"], example: "The school bell rings loud at noon." },
                { sentence: "Sweep the floor with a broom", phonemes: ["s", "w", "ee", "p", "th", "e", "f", "l", "o", "r"], example: "Sweep the dusty floor with a broom." },
                { sentence: "The yellow bus is on the road", phonemes: ["th", "e", "b", "a", "s", "i", "z", "k", "a", "m", "i", "ng"], example: "The yellow bus is coming down the road." },
                { sentence: "Hold the baby hand carefully", phonemes: ["h", "o", "l", "d", "th", "e", "b", "ay", "b", "ee"], example: "Hold the baby's hand very carefully." },
                { sentence: "The yellow mango is sweet", phonemes: ["th", "e", "f", "r", "oo", "t", "i", "z", "s", "w", "ee", "t"], example: "The yellow mango fruit is sweet." },
                { sentence: "Close the white gate now", phonemes: ["k", "l", "o", "z", "th", "e", "w", "ay", "t"], example: "Close the white gate to be safe." },
                { sentence: "The lamp is on the desk", phonemes: ["th", "e", "l", "a", "m", "p", "i", "z"], example: "The lamp is bright on the desk." },
                { sentence: "A big blue ship on the sea", phonemes: ["a", "b", "i", "g", "b", "l", "oo", "sh", "i", "p"], example: "A big blue ship is on the sea." },
                { sentence: "The grass is green in park", phonemes: ["th", "e", "g", "r", "a", "s", "i", "z"], example: "The grass is green in the park." },
                { sentence: "Pick up the pen from floor", phonemes: ["p", "i", "k", "a", "p", "th", "e", "p", "e", "n"], example: "Pick up the pen from the floor." },
                { sentence: "The boy runs to the gate", phonemes: ["th", "e", "b", "oy", "r", "a", "n", "z"], example: "The boy runs fast to the gate." },
                { sentence: "A tall tree is in the yard", phonemes: ["a", "t", "o", "l", "g", "r", "ee", "n"], example: "A tall green tree is in the yard." },
                { sentence: "The fish swims in the water", phonemes: ["th", "e", "f", "i", "sh", "k", "a", "n"], example: "The fish can swim in the water." },
                { sentence: "Use a spoon to eat the soup", phonemes: ["y", "oo", "z", "a", "s", "i", "l", "v", "e", "r"], example: "Use a silver spoon to eat soup." },
                { sentence: "The pink cake is very sweet", phonemes: ["th", "e", "k", "ay", "k", "i", "z"], example: "The cake is pink and very sweet." },
                { sentence: "Draw a house on the paper", phonemes: ["d", "r", "o", "a", "s", "m", "o", "l"], example: "Draw a small house on the paper." },
                { sentence: "The sky has many white clouds", phonemes: ["th", "e", "s", "k", "ay", "h", "a", "z"], example: "The sky has many white clouds." },
                { sentence: "Walk to the park with dad", phonemes: ["w", "o", "k", "t", "oo", "th", "e"], example: "Walk to the park with your dad." },
                { sentence: "The bed is soft and clean", phonemes: ["th", "e", "b", "e", "d", "i", "z"], example: "The bed is soft and very clean." },
                { sentence: "The box is empty and light", phonemes: ["th", "e", "b", "o", "ks", "i", "z"], example: "The box is empty and very light." },
                { sentence: "I have a new blue bag", phonemes: ["a", "n", "y", "oo", "b", "l", "oo"], example: "I have a new blue bag today." },
                { sentence: "The gate is locked with key", phonemes: ["th", "e", "g", "ay", "t", "i", "z"], example: "The gate is locked with a key." },
                { sentence: "The jar is full of jam", phonemes: ["th", "e", "j", "a", "r", "i", "z"], example: "The jar is full of sweet jam." },
                { sentence: "Sit on the mat and read", phonemes: ["s", "i", "t", "o", "n", "th", "e", "m", "a", "t"], example: "Sit on the mat and read now." },
                { sentence: "The yellow bus is here now", phonemes: ["th", "e", "b", "a", "s", "i", "z"], example: "The yellow bus is here now." },
                { sentence: "The ice is cold in the cup", phonemes: ["th", "e", "ay", "s", "i", "z"], example: "The ice is cold in the cup." },
                { sentence: "Look at the frog on the log", phonemes: ["l", "u", "k", "a", "t", "th", "e"], example: "Look at the frog on the log." },
                { sentence: "The girl is nice to her mom", phonemes: ["th", "e", "g", "e", "r", "l", "i", "z"], example: "The girl is nice to her mom." },
                { sentence: "She has a big gold ring", phonemes: ["a", "b", "i", "g", "g", "o", "l", "d"], example: "She has a big gold ring now." },
                { sentence: "The sea is blue and deep", phonemes: ["th", "e", "s", "ee", "i", "z"], example: "The sea is blue and very deep." },
                { sentence: "Write your name on the paper", phonemes: ["r", "ay", "t", "y", "o", "r", "n", "ay", "m"], example: "Write your name on the paper." },
                { sentence: "The leaf is green and small", phonemes: ["th", "e", "l", "ee", "f", "i", "z"], example: "The leaf is green and small." },
                { sentence: "I drink a hot cup of tea", phonemes: ["a", "h", "o", "t", "k", "a", "p"], example: "I drink a hot cup of tea." },
                { sentence: "The brown door is wide open", phonemes: ["th", "e", "d", "o", "r", "i", "z"], example: "The brown door is wide open." },
                { sentence: "A star is bright at night", phonemes: ["th", "e", "s", "t", "a", "r", "i", "z"], example: "The star is bright at night." }
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
                { sentence: "We must respect all human rights", phonemes: ["r", "ee", "s", "p", "e", "k", "t"], example: "We must respect all human rights for a fair society." },
                { sentence: "Celebrate Filipino culture with food", phonemes: ["s", "e", "l", "e", "b", "r", "ay", "t"], example: "We celebrate Filipino culture with food and dancing." },
                { sentence: "Oxygen is vital for all humans", phonemes: ["o", "k", "s", "i", "j", "e", "n"], example: "Oxygen is vital for life for both humans and animals." },
                { sentence: "The heart pumps blood to the body", phonemes: ["h", "a", "r", "t"], example: "The human heart pumps the blood to all parts of the body." },
                { sentence: "Gravity pulls things to the center", phonemes: ["g", "r", "a", "v", "i", "t", "ee"], example: "Gravity pulls things down toward the center of the earth." },
                { sentence: "Photosynthesis makes food for plants", phonemes: ["f", "o", "t", "o", "s", "i", "n", "th", "e", "s", "i", "s"], example: "Photosynthesis makes food for the plants using sunlight." },
                { sentence: "Computers are fast at solving math", phonemes: ["k", "a", "m", "p", "y", "oo", "t", "e", "r", "z"], example: "Modern computers are very fast at solving math problems." },
                { sentence: "The Pacific ocean is deep blue", phonemes: ["o", "sh", "u", "n"], example: "The Pacific ocean is deep blue and has many fish." },
                { sentence: "Laws are made to protect animals", phonemes: ["p", "r", "u", "t", "e", "k", "t"], example: "Laws are made to protect the wild animals from hunters." },
                { sentence: "We should eat a balanced meal", phonemes: ["b", "a", "l", "a", "n", "s", "t"], example: "We should eat a balanced meal of rice and vegetables." },
                { sentence: "Learning history is very exciting", phonemes: ["h", "i", "s", "t", "o", "r", "ee"], example: "Learning about history is very exciting for students." },
                { sentence: "Cleanliness is the rule in school", phonemes: ["k", "l", "e", "n", "l", "i", "n", "e", "s"], example: "Cleanliness is the rule in our school and classroom." },
                { sentence: "Honesty is the way to gain trust", phonemes: ["o", "n", "e", "s", "t", "ee"], example: "Honesty is the best way to gain the trust of friends." },
                { sentence: "Kindness helps the world be better", phonemes: ["k", "ay", "n", "d", "n", "e", "s"], example: "Small acts of kindness helps the world become better." },
                { sentence: "We read books for new knowledge", phonemes: ["n", "o", "l", "e", "j"], example: "We read books for knowledge and to learn new facts." },
                { sentence: "The internet connects people daily", phonemes: ["i", "n", "t", "e", "r", "n", "e", "t"], example: "The internet connects all people around the globe." },
                { sentence: "Practice makes us perfect in sports", phonemes: ["p", "r", "a", "k", "t", "i", "s"], example: "Constant practice makes us perfect in playing sports." },
                { sentence: "Trust your fellow team to win", phonemes: ["t", "ee", "m"], example: "You must trust your fellow team members to win." },
                { sentence: "The study of science is everywhere", phonemes: ["s", "ay", "e", "n", "s"], example: "The study of science is everywhere in the world today." },
                { sentence: "Volcanoes are very dangerous places", phonemes: ["v", "o", "l", "k", "ay", "n", "o", "z"], example: "Erupting volcanoes are very hot and dangerous places." },
                { sentence: "Fossils are remains from long ago", phonemes: ["f", "o", "s", "e", "l", "z"], example: "Fossils are old remains of creatures from long ago." },
                { sentence: "The desert has very little rain", phonemes: ["d", "e", "z", "e", "r", "t"], example: "The desert is sandy and has very little rainfall." },
                { sentence: "Mars is the red planet near us", phonemes: ["m", "a", "r", "z"], example: "Mars is the red planet and it is close to earth." },
                { sentence: "Bridges are made of strong steel", phonemes: ["b", "r", "i", "j", "e", "z"], example: "Modern bridges are made of steel and strong concrete." },
                { sentence: "Helicopters fly high above city", phonemes: ["h", "e", "l", "i", "k", "o", "p", "t", "e", "r", "z"], example: "Helicopters fly high above the city buildings today." },
                { sentence: "Scientists use microscopes for cells", phonemes: ["m", "ay", "k", "r", "o", "s", "k", "o", "p", "s"], example: "Scientists use microscopes to see the human cells." },
                { sentence: "Lighthouses guide the ships home", phonemes: ["l", "ay", "t", "h", "ow", "s", "e", "z"], example: "Lighthouses guide ships away from the rocky shores." },
                { sentence: "Rainforests provide fresh air daily", phonemes: ["r", "ay", "n", "f", "o", "r", "e", "s", "t", "s"], example: "Rainforests are green and provide fresh air for us." },
                { sentence: "Bees do the task of pollination", phonemes: ["p", "u", "l", "i", "n", "ay", "sh", "u", "n"], example: "Pollination is a task done by bees and butterflies." },
                { sentence: "Gravity holds us on the ground", phonemes: ["g", "r", "a", "v", "i", "t", "ee"], example: "The force of gravity holds us down on the ground." },
                { sentence: "A compass shows the way to go", phonemes: ["k", "a", "m", "p", "u", "s"], example: "The magnetic compass shows the way to the travelers." },
                { sentence: "Thermal energy comes from heat", phonemes: ["e", "n", "e", "r", "j", "ee"], example: "Thermal energy comes from the heat of the earth." },
                { sentence: "Stars are suns in outer space", phonemes: ["s", "t", "a", "r", "z"], example: "Stars are far away suns in the dark outer space." },
                { sentence: "Comets have tails made of ice", phonemes: ["k", "o", "m", "e", "t", "s"], example: "Comets have icy tails that glow when near the sun." },
                { sentence: "The moon has craters on surface", phonemes: ["m", "oo", "n"], example: "The moon has craters made by falling space rocks." },
                { sentence: "Dinosaurs ruled the earth long ago", phonemes: ["d", "ay", "n", "u", "s", "o", "r", "z"], example: "The dinosaurs are giants that ruled the earth long ago." },
                { sentence: "Recycle paper to make new sheets", phonemes: ["r", "ee", "s", "ay", "k", "e", "l"], example: "We can recycle the old paper to make new sheets." },
                { sentence: "Everyone must help save the earth", phonemes: ["s", "ay", "v"], example: "Everyone must help to save our precious earth now." },
                { sentence: "Watch the seed grow into a tree", phonemes: ["p", "l", "a", "n", "t"], example: "Plant a tiny seed and watch it grow into a tree." },
                { sentence: "Clean water is life for everyone", phonemes: ["w", "o", "t", "e", "r"], example: "Clean water is life for all living things on earth." }
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
                { sentence: "Energy can be transformed to heat", phonemes: ["e", "n", "e", "r", "j", "ee"], example: "Energy can be transformed from one form to another." },
                { sentence: "Deforestation destroys wild habitats", phonemes: ["d", "ee", "f", "o", "r", "e", "s", "t", "ay", "sh", "u", "n"], example: "Deforestation destroys habitats of many wild animals." },
                { sentence: "Water conservation is a vital duty", phonemes: ["k", "a", "n", "s", "e", "r", "v", "ay", "sh", "u", "n"], example: "Water conservation is a duty of every citizen today." },
                { sentence: "Wind power produces clean energy", phonemes: ["w", "i", "n", "d", "p", "ow", "e", "r"], example: "Wind power produces electricity without any pollution." },
                { sentence: "Gravity keeps us on the ground now", phonemes: ["g", "r", "a", "v", "i", "t", "ee"], example: "Gravity keeps us on the ground and prevents floating." },
                { sentence: "The solar system is very vast now", phonemes: ["s", "o", "l", "e", "r", "s", "i", "s", "t", "e", "m"], example: "The solar system is very vast with many planets." },
                { sentence: "Stars are born in huge nebulae gas", phonemes: ["n", "e", "b", "y", "u", "l", "ee"], example: "Stars are born in huge nebulae of gas and dust." },
                { sentence: "Black holes have strong gravity pull", phonemes: ["b", "l", "a", "k", "h", "o", "l", "z"], example: "Black holes have strong gravity that traps the light." },
                { sentence: "Robots will help us in the future", phonemes: ["r", "o", "b", "o", "t", "s"], example: "Robots will help us in the future with many tasks." },
                { sentence: "Digital literacy is a necessary skill", phonemes: ["d", "i", "j", "i", "t", "e", "l"], example: "Digital literacy is a necessary skill in the modern world today." },
                { sentence: "Leadership requires great responsibility", phonemes: ["l", "ee", "d", "e", "r", "sh", "i", "p"], example: "Leadership requires great responsibility and a clear vision." },
                { sentence: "Resilience helps overcome challenges", phonemes: ["r", "ee", "z", "i", "l", "y", "e", "n", "s"], example: "Resilience helps overcome challenges during difficult times." },
                { sentence: "Integrity is doing the right thing", phonemes: ["i", "n", "t", "e", "g", "r", "i", "t", "ee"], example: "Integrity is doing the right thing even when no one sees." },
                { sentence: "Creativity knows no boundaries now", phonemes: ["k", "r", "ee", "ay", "t", "i", "v", "i", "t", "ee"], example: "Creativity knows no boundaries for an inspired artist." },
                { sentence: "Perseverance pays off in the end", phonemes: ["p", "e", "r", "s", "e", "v", "i", "r", "a", "n", "s"], example: "Perseverance pays off in the end after a hard work." },
                { sentence: "Discipline is the key to success", phonemes: ["d", "i", "s", "i", "p", "l", "i", "n"], example: "Discipline is the key to success in any career path." },
                { sentence: "The universe is expanding slowly", phonemes: ["y", "oo", "n", "i", "v", "e", "r", "s"], example: "The universe is expanding now since the big bang." },
                { sentence: "Cyber security is very important", phonemes: ["s", "ay", "b", "e", "r"], example: "Cyber security is important to protect our privacy." },
                { sentence: "Artificial intelligence is here now", phonemes: ["a", "r", "t", "i", "f", "i", "sh", "e", "l"], example: "Artificial intelligence is here to change the world." },
                { sentence: "A pulley makes lifting very easy", phonemes: ["p", "u", "l", "ee"], example: "A pulley is a simple machine that makes lifting heavy objects easy." },
                { sentence: "Levers help us move heavy rocks", phonemes: ["l", "e", "v", "e", "r", "z"], example: "Levers are used to lift or move heavy weights with less effort." },
                { sentence: "The inclined plane is a ramp", phonemes: ["i", "n", "k", "l", "ay", "n", "d"], example: "An inclined plane is a flat surface tilted at an angle like a ramp." },
                { sentence: "Wheels and axles reduce friction", phonemes: ["f", "r", "i", "k", "sh", "u", "n"], example: "Wheels and axles help moving objects by reducing friction." },
                { sentence: "Wedges are used to split wood", phonemes: ["w", "e", "j", "e", "z"], example: "Wedges like axes are simple machines used to split things apart." },
                { sentence: "The respiratory system helps breathing", phonemes: ["r", "e", "s", "p", "i", "r", "a", "t", "o", "r", "ee"], example: "The respiratory system helps us breathe by taking in oxygen." },
                { sentence: "The skeletal system provides support", phonemes: ["s", "k", "e", "l", "e", "t", "e", "l"], example: "The skeletal system provides the framework and support for our body." },
                { sentence: "Muscles allow the body to move", phonemes: ["m", "a", "s", "e", "l", "z"], example: "Muscles work with bones to allow the human body to move." },
                { sentence: "Excretory system removes body waste", phonemes: ["e", "k", "s", "k", "r", "e", "t", "o", "r", "ee"], example: "The excretory system is responsible for removing waste from the body." },
                { sentence: "Digestive system breaks down food", phonemes: ["d", "ay", "j", "e", "s", "t", "i", "v"], example: "The digestive system breaks down food into nutrients for energy." },
                { sentence: "Earthquakes happen along fault lines", phonemes: ["e", "r", "th", "k", "w", "ay", "k", "s"], example: "Earthquakes occur when rocks break and move along fault lines." },
                { sentence: "Tsunamis are giant ocean waves", phonemes: ["ts", "oo", "n", "a", "m", "ee", "z"], example: "Tsunamis are giant waves caused by underwater earthquakes." },
                { sentence: "Asteroids orbit the hot sun", phonemes: ["a", "s", "t", "e", "r", "oy", "d", "z"], example: "Asteroids are rocky objects that orbit the sun in space." },
                { sentence: "Galaxies contain billions of stars", phonemes: ["g", "a", "l", "a", "k", "s", "ee", "z"], example: "Galaxies are massive systems containing billions of stars." },
                { sentence: "Satellites transmit signals to earth", phonemes: ["s", "a", "t", "e", "l", "ay", "t", "s"], example: "Satellites orbit the earth to transmit television and phone signals." },
                { sentence: "Astronauts explore the outer space", phonemes: ["a", "s", "t", "r", "o", "n", "o", "t", "s"], example: "Astronauts travel in spacecraft to explore the outer space." },
                { sentence: "The Milky Way is our home", phonemes: ["m", "i", "l", "k", "ee"], example: "The Milky Way is the galaxy that contains our solar system." },
                { sentence: "Comets have glowing dust tails", phonemes: ["k", "o", "m", "e", "t", "s"], example: "Comets are made of ice and dust and have glowing tails." },
                { sentence: "Space probes collect space data", phonemes: ["s", "p", "ay", "s"], example: "Space probes are sent to other planets to collect scientific data." },
                { sentence: "The sun is a medium star", phonemes: ["s", "a", "n"], example: "The sun is a medium-sized star at the center of our system." }
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

        // --- SPEECH & AUDIO ---

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
            const perfect = ["Excellent! You got five stars!", "Amazing!", "Wow! Pro!", "Perfect!", "Incredible!"];
            const great = ["Great job!", "Good effort!", "Nice work!"];
            const tryAgain = ["Try again!", "Keep practicing!", "You can do it!"];
            let message = score === 5 ? perfect[Math.floor(Math.random() * perfect.length)] : (score >= 4 ? great[Math.floor(Math.random() * great.length)] : tryAgain[Math.floor(Math.random() * tryAgain.length)]);
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

        // --- GAMEPLAY FLOW ---

        function loadNextWord() {
            imageBox.style.visibility = "hidden";
            imageBox.classList.add("hidden");
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

            // GUMAMIT NG .sentence dahil iyon ang structure ng wordBank mo
            const targetText = currentWord.sentence || currentWord.word;
            wordDisplay.textContent = targetText;
            phonemeDisplay.textContent = currentWord.phonemes.join(" · ");

            const imgKey = targetText.toLowerCase();
            if (imageLibrary[imgKey]) {
                const imgPath = imageLibrary[imgKey];
                document.getElementById("wordImage").src = imgPath;
                imageBox.style.backgroundImage = `url('${imgPath}')`;
            } else {
                imageBox.classList.add("hidden");
            }

            wordAttemptsHistory = [];
            renderWordHistory();
            updateUIProgress();
        }

        function checkPronunciation(spoken) {
            const targetText = currentWord.sentence || currentWord.word;
            const score = ratePronunciation(spoken, targetText);
            speakRating(score);

            if (score === 5) {
                const imgKey = targetText.toLowerCase();
                if (imageLibrary[imgKey]) {
                    imageBox.style.visibility = "visible";
                    imageBox.classList.remove("hidden");
                }
                feedbackMessage.style.visibility = "visible";

                if (!wordAttemptsHistory.includes(5)) {
                    allProgress[currentDifficulty].words_correct++;

                    // Milestone confetti check
                    if ((currentDifficulty === 'beginner' && begCountFromDB + 1 === 40) ||
                        (currentDifficulty === 'intermediate' && intCountFromDB + 1 === 40)) {
                        triggerConfetti();
                        setTimeout(() => location.reload(), 2000);
                        return;
                    }
                    triggerConfetti();
                }
                nextBtn.disabled = false;
                feedbackMessage.textContent = "⭐ " + currentWord.example;
                feedbackMessage.className = "feedback-message bg-success-feedback";
                playFeedbackBtn.style.display = "inline-block";
            } else {
                nextBtn.disabled = true;
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

        function renderWordHistory() {
            wordHistoryEl.innerHTML = wordAttemptsHistory.length === 0 ? "No attempts yet." : "";
            [...wordAttemptsHistory].reverse().forEach((s, i) => {
                const div = document.createElement("div"); div.className = "history-item";
                div.innerHTML = `<span>Attempt ${wordAttemptsHistory.length - i}:</span> `;
                for (let j = 1; j <= 5; j++) { div.innerHTML += `<i class="fa-star fa-solid ${j <= s ? 'filled-star' : 'empty-star'}"></i>`; }
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

        // --- DATABASE SYNC ---

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

        // --- MIC CONTROLS ---

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

        // --- INITIALIZATION ---

        function init() {
            difficultySelect.addEventListener("change", () => { currentDifficulty = difficultySelect.value; loadProgressFromDB(); });

            nextBtn.addEventListener("click", () => {
                let p = allProgress[currentDifficulty];
                if (p.words_correct > 0 && p.words_correct % 5 === 0) triggerMysteryGame("bonus");
                else { p.word_index++; saveProgressToDB(); loadNextWord(); }
            });

            // FIX PARA SA LISTEN BUTTONS: Gumamit ng .sentence
            playWordBtn.addEventListener("click", () => {
                const textToSpeak = currentWord.sentence || currentWord.word;
                speak(textToSpeak);
            });

            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));

            micBtn.addEventListener("click", toggleMic);

            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = true;

                recognition.onresult = (e) => {
                    let interimTranscript = '';
                    for (let i = e.resultIndex; i < e.results.length; ++i) {
                        if (e.results[i].isFinal) {
                            const finalTxt = e.results[i][0].transcript;
                            transcriptEl.textContent = finalTxt;
                            checkPronunciation(finalTxt);
                            stopMicLogic();
                        } else {
                            interimTranscript = e.results[i][0].transcript;
                            transcriptEl.textContent = interimTranscript;

                            let spoken = interimTranscript.toLowerCase().trim();
                            // Property Sync
                            let target = (currentWord.sentence || currentWord.word).toLowerCase().trim();

                            if (spoken.includes(target)) {
                                checkPronunciation(spoken);
                                stopMicLogic();
                            }
                        }
                    }
                };
                recognition.onspeechend = () => { stopMicLogic(); };
                recognition.onerror = () => { stopMicLogic(); };
            }

            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = getUsEnglishVoice;
            }

            if (SHOW_PARENTAL_NOTE) showParentalGate(); else loadProgressFromDB();
            setTimeout(() => { if (PLAY_WELCOME_VOICE) introduceSystem(); }, 1500);
        }

        window.onload = init;
    </script>
</main>
</body>
</html>