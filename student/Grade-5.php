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

        const wordBank = {
            beginner: [
                // 50 Items - Grade 5 Level Vocabulary (Nouns & Basic Concepts)
                { word: "Globe", phonemes: ["g", "l", "o", "b"], example: "The globe shows all the countries." },
                { word: "Torch", phonemes: ["t", "o", "r", "ch"], example: "The torch gives light in the cave." },
                { word: "Storm", phonemes: ["s", "t", "o", "r", "m"], example: "The storm destroyed the small huts." },
                { word: "Coast", phonemes: ["k", "o", "s", "t"], example: "The boat sailed along the coast." },
                { word: "Flute", phonemes: ["f", "l", "oo", "t"], example: "She plays a tune on her flute." },
                { word: "Grape", phonemes: ["g", "r", "ay", "p"], example: "The purple grape is sweet and sour." },
                { word: "Bridge", phonemes: ["b", "r", "i", "j"], example: "The stone bridge is very strong." },
                { word: "Crane", phonemes: ["k", "r", "ay", "n"], example: "The tall crane lifts heavy steel." },
                { word: "Flame", phonemes: ["f", "l", "ay", "m"], example: "The blue flame is very hot." },
                { word: "Grain", phonemes: ["g", "r", "ay", "n"], example: "The farmer harvests the rice grain." },
                { word: "Scale", phonemes: ["s", "k", "ay", "l"], example: "Use the scale to weigh the fish." },
                { word: "Snail", phonemes: ["s", "n", "ay", "l"], example: "The snail crawls on the leaf." },
                { word: "Steam", phonemes: ["s", "t", "ee", "m"], example: "Steam rises from the hot coffee." },
                { word: "Thorn", phonemes: ["th", "o", "r", "n"], example: "The rose has a sharp thorn." },
                { word: "Wheat", phonemes: ["w", "ee", "t"], example: "Bread is made from ground wheat." },
                { word: "Brick", phonemes: ["b", "r", "i", "k"], example: "The house is made of red brick." },
                { word: "Clerk", phonemes: ["k", "l", "e", "r", "k"], example: "The clerk works in the office." },
                { word: "Dwarf", phonemes: ["d", "w", "o", "r", "f"], example: "The dwarf lives in the forest." },
                { word: "Frost", phonemes: ["f", "r", "o", "s", "t"], example: "Frost covered the cold grass." },
                { word: "Glove", phonemes: ["g", "l", "a", "v"], example: "Wear a glove on your hand." },
                { word: "Scarf", phonemes: ["s", "k", "a", "r", "f"], example: "The red scarf is around her neck." },
                { word: "Stove", phonemes: ["s", "t", "o", "v"], example: "Cook the rice on the stove." },
                { word: "Track", phonemes: ["t", "r", "a", "k"], example: "The train runs on the track." },
                { word: "Whale", phonemes: ["w", "ay", "l"], example: "The blue whale is a mammal." },
                { word: "Brush", phonemes: ["b", "r", "a", "sh"], example: "Clean the floor with a brush." },
                { word: "Cloth", phonemes: ["k", "l", "o", "th"], example: "The table cloth is white." },
                { word: "Fruit", phonemes: ["f", "r", "oo", "t"], example: "Pineapple is a tropical fruit." },
                { word: "Grill", phonemes: ["g", "r", "i", "l"], example: "Grill the fish over charcoal." },
                { word: "Plaza", phonemes: ["p", "l", "a", "z", "a"], example: "The plaza is full of people." },
                { word: "Shelf", phonemes: ["sh", "e", "l", "f"], example: "The books are on the shelf." },
                { word: "Swing", phonemes: ["s", "w", "i", "ng"], example: "The children play on the swing." },
                { word: "Wheel", phonemes: ["w", "ee", "l"], example: "The wheel of the cart broke." },
                { word: "Board", phonemes: ["b", "o", "r", "d"], example: "Write the lesson on the board." },
                { word: "Coach", phonemes: ["k", "o", "ch"], example: "The coach trains the team." },
                { word: "Floor", phonemes: ["f", "l", "o", "r"], example: "Sweep the dust off the floor." },
                { word: "Glass", phonemes: ["g", "l", "a", "s"], example: "The glass is half full." },
                { word: "Hatch", phonemes: ["h", "a", "ch"], example: "The eggs will hatch soon." },
                { word: "Pound", phonemes: ["p", "ow", "n", "d"], example: "Pound the garlic into paste." },
                { word: "Shirt", phonemes: ["sh", "e", "r", "t"], example: "The button of my shirt fell." },
                { word: "Sword", phonemes: ["s", "o", "r", "d"], example: "The hero has a sharp sword." },
                { word: "Truck", phonemes: ["t", "r", "a", "k"], example: "The truck carries the gravel." },
                { word: "Wreck", phonemes: ["r", "e", "k"], example: "They found the ship wreck." },
                { word: "Creek", phonemes: ["k", "r", "ee", "k"], example: "The creek flows into the sea." },
                { word: "Frame", phonemes: ["f", "r", "ay", "m"], example: "Put the picture in a frame." },
                { word: "Grand", phonemes: ["g", "r", "a", "n", "d"], example: "The hotel has a grand lobby." },
                { word: "Knife", phonemes: ["n", "ay", "f"], example: "The knife is for the meat." },
                { word: "Price", phonemes: ["p", "r", "ay", "s"], example: "The price of rice is high." },
                { word: "Table", phonemes: ["t", "ay", "b", "e", "l"], example: "Set the food on the table." },
                { word: "Twist", phonemes: ["t", "w", "i", "s", "t"], example: "Twist the cap to open it." },
                { word: "Couch", phonemes: ["k", "ow", "ch"], example: "Sit on the soft couch." }
            ],
            intermediate: [
                // 50 Items - Grade 5 Sentences (Focus on Science and Society)
                { sentence: "Recycle the plastic waste today", phonemes: ["r", "ee", "s", "ay", "k", "e", "l"], example: "We must recycle the plastic waste to save the earth." },
                { sentence: "Conserve our daily water supply", phonemes: ["k", "a", "n", "s", "e", "r", "v"], example: "It is important to conserve our water supply every day." },
                { sentence: "Protect the fragile coral reefs", phonemes: ["p", "r", "u", "t", "e", "k", "t"], example: "Fishermen should protect the coral reefs in the ocean." },
                { sentence: "Observe the tiny bacteria cells", phonemes: ["u", "b", "z", "e", "r", "v"], example: "Use a microscope to observe the tiny bacteria cells." },
                { sentence: "Explain the dark solar eclipse", phonemes: ["e", "k", "s", "p", "l", "ay", "n"], example: "The teacher will explain the solar eclipse to the class." },
                { sentence: "Analyze the science project data", phonemes: ["a", "n", "a", "l", "ay", "z"], example: "The students analyze the science data from the experiment." },
                { sentence: "Measure the liquid volume carefully", phonemes: ["m", "e", "zh", "e", "r"], example: "Measure the liquid volume using a glass cylinder." },
                { sentence: "Respect the village elders always", phonemes: ["r", "ee", "s", "p", "e", "k", "t"], example: "We should always respect the village elders in our town." },
                { sentence: "Organize the school sports fest", phonemes: ["o", "r", "g", "a", "n", "ay", "z"], example: "Let us organize the school event for the sports fest." },
                { sentence: "Calculate the total grocery cost", phonemes: ["k", "a", "l", "k", "y", "u", "l", "ay", "t"], example: "Calculate the total cost of the groceries at the store." },
                { sentence: "Discuss the local town history", phonemes: ["d", "i", "s", "k", "a", "s"], example: "The family will discuss the local history of our province." },
                { sentence: "Prepare for the incoming typhoon", phonemes: ["p", "r", "ee", "p", "e", "r"], example: "We must prepare for the typhoon by fixing our house roof." },
                { sentence: "Identify the plant species now", phonemes: ["ay", "d", "e", "n", "t", "i", "f", "ay"], example: "Try to identify the plant species in the school garden." },
                { sentence: "Construct a sturdy wooden bridge", phonemes: ["k", "a", "n", "s", "t", "r", "a", "k", "t"], example: "Engineers construct a sturdy bridge across the wide river." },
                { sentence: "Describe the forest animals well", phonemes: ["d", "ee", "s", "k", "r", "ay", "b"], example: "Please describe the forest animals you saw during the trip." },
                { sentence: "Monitor the patient blood pressure", phonemes: ["m", "o", "n", "i", "t", "e", "r"], example: "The nurse will monitor the blood pressure of the patient." },
                { sentence: "Promote the local island tourism", phonemes: ["p", "r", "u", "m", "o", "t"], example: "We should promote the local tourism of our beautiful islands." },
                { sentence: "Maintain a healthy food lifestyle", phonemes: ["m", "ay", "n", "t", "ay", "n"], example: "Eating well and exercising help maintain a healthy lifestyle." },
                { sentence: "Reduce your carbon footprint here", phonemes: ["r", "ee", "d", "y", "oo", "s"], example: "Planting trees helps to reduce the carbon footprint on earth." },
                { sentence: "Discover the hidden cave treasure", phonemes: ["d", "i", "s", "k", "a", "v", "e", "r"], example: "The explorers hope to discover the hidden treasure in the cave." },
                { sentence: "Communicate with your whole team", phonemes: ["k", "a", "m", "y", "oo", "n", "i", "k", "ay", "t"], example: "You must communicate with your team to win the game." },
                { sentence: "Accomplish the daily home tasks", phonemes: ["a", "k", "o", "m", "p", "l", "i", "sh"], example: "I need to accomplish the daily tasks before I go to bed." },
                { sentence: "Demonstrate the science experiment", phonemes: ["d", "e", "m", "u", "n", "s", "t", "r", "ay", "t"], example: "The scientist will demonstrate the experiment to the kids." },
                { sentence: "Cooperate with all your classmates", phonemes: ["k", "o", "o", "p", "e", "r", "ay", "t"], example: "You should cooperate with classmates for the group work." },
                { sentence: "Participate in the math contest", phonemes: ["p", "a", "r", "t", "i", "s", "i", "p", "ay", "t"], example: "Many students participate in the contest during the fiesta." },
                { sentence: "Celebrate our Independence Day now", phonemes: ["s", "e", "l", "e", "b", "r", "ay", "t"], example: "We celebrate our independence day every twelfth of June." },
                { sentence: "Appreciate the local museum arts", phonemes: ["a", "p", "r", "ee", "sh", "ee", "ay", "t"], example: "Take time to appreciate the fine arts in the local museum." },
                { sentence: "Recognize the Philippine national flag", phonemes: ["r", "e", "k", "u", "g", "n", "ay", "z"], example: "Children should recognize the national flag of the country." },
                { sentence: "Summarize the short story plot", phonemes: ["s", "a", "m", "e", "r", "ay", "z"], example: "Try to summarize the short story using only five sentences." },
                { sentence: "Illustrate the natural water cycle", phonemes: ["i", "l", "a", "s", "t", "r", "ay", "t"], example: "Can you illustrate the water cycle on your white board?" },
                { sentence: "Distribute the emergency relief goods", phonemes: ["d", "i", "s", "t", "r", "i", "b", "y", "oo", "t"], example: "The volunteers distribute the relief goods to the families." },
                { sentence: "Advocate for basic child rights", phonemes: ["a", "d", "v", "u", "k", "ay", "t"], example: "We should advocate for child rights in our local community." },
                { sentence: "Translate the short English text", phonemes: ["t", "r", "a", "n", "s", "l", "ay", "t"], example: "Use a dictionary to translate the English text to Filipino." },
                { sentence: "Renovate the old school building", phonemes: ["r", "e", "n", "u", "v", "ay", "t"], example: "The city will renovate the old building into a library." },
                { sentence: "Minimize the loud noise level", phonemes: ["m", "i", "n", "i", "m", "ay", "z"], example: "Please minimize the noise level inside the school clinic." },
                { sentence: "Negotiate for a very fair price", phonemes: ["n", "ee", "g", "o", "sh", "ee", "ay", "t"], example: "Try to negotiate the fair price of the goods at the market." },
                { sentence: "Calculate the exact floor area", phonemes: ["k", "a", "l", "k", "y", "u", "l", "ay", "t"], example: "Calculate the exact area of the floor for the new tiles." },
                { sentence: "Regulate the busy traffic flow", phonemes: ["r", "e", "g", "y", "u", "l", "ay", "t"], example: "Traffic lights help to regulate the traffic flow in the city." },
                { sentence: "Stimulate your child brain power", phonemes: ["s", "t", "i", "m", "y", "u", "l", "ay", "t"], example: "Reading books helps stimulate the brain power of children." },
                { sentence: "Generate the town electricity now", phonemes: ["j", "e", "n", "e", "r", "ay", "t"], example: "The big dam can generate the electricity for the whole town." },
                { sentence: "Integrate the new school lessons", phonemes: ["i", "n", "t", "e", "g", "r", "ay", "t"], example: "Teachers integrate the new lessons into the curriculum." },
                { sentence: "Participate in local elections now", phonemes: ["p", "a", "r", "t", "i", "s", "i", "p", "ay", "t"], example: "Grown-ups participate in elections to choose their leaders." },
                { sentence: "Formulate the correct study plan", phonemes: ["f", "o", "r", "m", "y", "u", "l", "ay", "t"], example: "We need to formulate the correct plan to finish the project." },
                { sentence: "Moderate the indoor temperature now", phonemes: ["m", "o", "d", "e", "r", "ay", "t"], example: "Use a fan to moderate the temperature inside the room." },
                { sentence: "Decorate the whole town plaza", phonemes: ["d", "e", "k", "e", "r", "ay", "t"], example: "They will decorate the town plaza for the upcoming fiesta." },
                { sentence: "Migrate during the cold winter", phonemes: ["m", "ay", "g", "r", "ay", "t"], example: "Many birds migrate during the winter to find warmer lands." },
                { sentence: "Eliminate the standing waste water", phonemes: ["ee", "l", "i", "m", "i", "n", "ay", "t"], example: "We need a drain to eliminate the waste water from the sink." },
                { sentence: "Synthesize the report information", phonemes: ["s", "i", "n", "th", "e", "s", "ay", "z"], example: "The writer will synthesize the information for the report." },
                { sentence: "Facilitate the library group study", phonemes: ["f", "a", "s", "i", "l", "i", "t", "ay", "t"], example: "The teacher will facilitate the group study in the library." },
                { sentence: "Investigate the local crime scene", phonemes: ["i", "n", "v", "e", "s", "t", "i", "g", "ay", "t"], example: "The police investigate the crime scene for some evidence." }
            ],
            advanced: [
                // 50 Items - Grade 5 Complex Sentences (Curriculum Based)
                { sentence: "Trees give us oxygen to breathe", phonemes: ["t", "r", "ee", "z"], example: "Trees give us oxygen to breathe and they keep the air clean." },
                { sentence: "The volcano has hot red lava", phonemes: ["v", "o", "l", "k", "ay", "n", "o"], example: "The volcano has hot red lava flowing down its steep slope." },
                { sentence: "The earth rotates on its axis", phonemes: ["e", "r", "th"], example: "The earth rotates on its axis and it revolves around the sun." },
                { sentence: "Solar panels can trap the sunlight", phonemes: ["s", "o", "l", "e", "r"], example: "Solar panels can trap sunlight to generate clean electricity." },
                { sentence: "Rivers flow from the tall mountains", phonemes: ["r", "i", "v", "e", "r", "z"], example: "Rivers flow from the mountains and carry water to the sea." },
                { sentence: "Bacteria are very tiny organisms now", phonemes: ["b", "a", "k", "t", "i", "r", "ee", "a"], example: "Bacteria are very tiny organisms that we cannot see with eyes." },
                { sentence: "The telescope sees the far planets", phonemes: ["t", "e", "l", "e", "s", "k", "o", "p"], example: "The telescope sees the planets and stars in the night sky." },
                { sentence: "Insects have a hard exoskeleton now", phonemes: ["i", "n", "s", "e", "k", "t", "s"], example: "Insects have a hard exoskeleton to protect their soft bodies." },
                { sentence: "Photosynthesis makes food for plants", phonemes: ["f", "o", "t", "o", "s", "i", "n", "th", "e", "s", "i", "s"], example: "Photosynthesis makes food for plants using light and water." },
                { sentence: "The internet connects the whole world", phonemes: ["i", "n", "t", "e", "r", "n", "e", "t"], example: "The internet connects the world and helps us learn fast." },
                { sentence: "Bees pollinate the many garden flowers", phonemes: ["b", "ee", "z"], example: "Bees pollinate the many flowers while they collect nectar." },
                { sentence: "The atmosphere protects our big earth", phonemes: ["a", "t", "m", "u", "s", "f", "i", "r"], example: "The atmosphere protects our earth from the heat of the sun." },
                { sentence: "Computers process data very fast now", phonemes: ["k", "a", "m", "p", "y", "oo", "t", "e", "r", "z"], example: "Computers process data very fast to help us with our work." },
                { sentence: "History tells us about the past life", phonemes: ["h", "i", "s", "t", "o", "r", "ee"], example: "History tells us about the past and how people lived before." },
                { sentence: "Exercise keeps the human heart healthy", phonemes: ["e", "k", "s", "e", "r", "s", "ay", "z"], example: "Exercise keeps the heart healthy and makes our bones strong." },
                { sentence: "Magnetism pulls the iron metal objects", phonemes: ["m", "a", "g", "n", "e", "t", "i", "z", "u", "m"], example: "Magnetism pulls the iron objects towards the metal magnet." },
                { sentence: "Fossils are remains of dead animals", phonemes: ["f", "o", "s", "e", "l", "z"], example: "Fossils are remains of animals that died thousands of years ago." },
                { sentence: "Rainforests are always full of life", phonemes: ["r", "ay", "n", "f", "o", "r", "e", "s", "t", "s"], example: "Rainforests are full of life with many birds and green trees." },
                { sentence: "Nutrition is very good for the body", phonemes: ["n", "oo", "t", "r", "i", "sh", "u", "n"], example: "Good nutrition is good for the body to prevent many diseases." },
                { sentence: "Energy comes from the hot yellow sun", phonemes: ["e", "n", "e", "r", "j", "ee"], example: "Energy comes from the hot sun and it helps the plants grow." },
                { sentence: "The heart pumps blood to the body", phonemes: ["h", "a", "r", "t"], example: "The heart pumps blood to every part of the human body." },
                { sentence: "Pollution makes the city air dirty", phonemes: ["p", "u", "l", "oo", "sh", "u", "n"], example: "Pollution makes the air dirty and it is bad for our lungs." },
                { sentence: "Communication is very important now", phonemes: ["k", "a", "m", "y", "oo", "n", "i", "k", "ay", "sh", "u", "n"], example: "Communication is very important to understand each other well." },
                { sentence: "The lighthouse guides the big ships", phonemes: ["l", "ay", "t", "h", "ow", "s"], example: "The lighthouse guides the ships away from the sharp rocks." },
                { sentence: "Erosion washes the farm soil away", phonemes: ["ee", "r", "o", "zh", "u", "n"], example: "Erosion washes the soil away during a very heavy rainstorm." },
                { sentence: "Gravity pulls things to the ground", phonemes: ["g", "r", "a", "v", "i", "t", "ee"], example: "Gravity pulls things to the ground when we drop them down." },
                { sentence: "Oxygen is vital for all human life", phonemes: ["o", "k", "s", "i", "j", "e", "n"], example: "Oxygen is vital for human life and all living things on earth." },
                { sentence: "Climate change affects the whole world", phonemes: ["k", "l", "ay", "m", "e", "t"], example: "Climate change affects the world and causes the ice to melt." },
                { sentence: "Agriculture provides our daily food", phonemes: ["a", "g", "r", "i", "k", "a", "l", "ch", "e", "r"], example: "Agriculture provides our daily food like rice and vegetables." },
                { sentence: "Transportation by land and deep sea", phonemes: ["t", "r", "a", "n", "s", "p", "o", "r", "t", "ay", "sh", "u", "n"], example: "Transportation by land and sea helps us travel very far away." },
                { sentence: "Electricity powers our many homes", phonemes: ["ee", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee"], example: "Electricity powers our many homes and all our gadgets too." },
                { sentence: "The desert is always hot and sandy", phonemes: ["d", "e", "z", "e", "r", "t"], example: "The desert is hot and sandy with very little water to drink." },
                { sentence: "The arctic is very cold and icy", phonemes: ["a", "r", "k", "t", "i", "k"], example: "The arctic is cold and icy where the polar bears live." },
                { sentence: "Education is the key to success now", phonemes: ["e", "j", "u", "k", "ay", "sh", "u", "n"], example: "Education is the key to success and a bright future for all." },
                { sentence: "Cleanliness is next to godliness", phonemes: ["k", "l", "e", "n", "l", "i", "n", "e", "s"], example: "Cleanliness is next to godliness so keep your room tidy." },
                { sentence: "Kindness makes the world better today", phonemes: ["k", "ay", "n", "d", "n", "e", "s"], example: "Kindness makes the world better for everyone to live in." },
                { sentence: "Honesty is always the best policy", phonemes: ["o", "n", "e", "s", "t", "ee"], example: "Honesty is the best policy so always tell the true story." },
                { sentence: "Patience is a very great virtue", phonemes: ["p", "ay", "sh", "e", "n", "s"], example: "Patience is a great virtue when waiting for your turn." },
                { sentence: "Practice makes a person perfect", phonemes: ["p", "r", "a", "k", "t", "i", "s"], example: "Practice makes a person perfect in playing the piano well." },
                { sentence: "Knowledge is power for everyone", phonemes: ["n", "o", "l", "e", "j"], example: "Knowledge is power for everyone who wants to learn more." },
                { sentence: "Teamwork makes the whole dream work", phonemes: ["t", "ee", "m", "w", "e", "r", "k"], example: "Teamwork makes the dream work when playing basketball now." },
                { sentence: "Respect the basic rights of others", phonemes: ["r", "ee", "s", "p", "e", "k", "t"], example: "Always respect the rights of others to live in peace today." },
                { sentence: "Protect the wild life animals now", phonemes: ["p", "r", "u", "t", "e", "k", "t"], example: "We must protect the wild life animals in the deep forest." },
                { sentence: "The bright stars are giant suns", phonemes: ["s", "t", "a", "r", "z"], example: "The stars are giant suns that are very far away from us." },
                { sentence: "Comets have long icy blue tails", phonemes: ["k", "o", "m", "e", "t", "s"], example: "Comets have long icy tails that glow in the dark space." },
                { sentence: "The white moon has many craters", phonemes: ["m", "oo", "n"], example: "The moon has many craters because rocks hit its surface." },
                { sentence: "Magnets have north and south poles", phonemes: ["m", "a", "g", "n", "e", "t", "s"], example: "Magnets have north and south poles that attract metal." },
                { sentence: "The lungs help us breathe fresh air", phonemes: ["l", "a", "ng", "z"], example: "The lungs help us breathe in oxygen and breathe out air." },
                { sentence: "Bones protect our soft inner organs", phonemes: ["b", "o", "n", "z"], example: "Bones protect our soft inner organs and help us stand." },
                { sentence: "Skin is the largest organ of body", phonemes: ["s", "k", "i", "n"], example: "Skin is the largest organ of the body and it protects us." }
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