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
                // 50 Items - Grade 4 DepEd Vocabulary (Nouns & Basic Concepts)
                { word: "Bamboo", phonemes: ["b", "a", "m", "b", "oo"], example: "The bahay kubo is made of bamboo." },
                { word: "Friend", phonemes: ["f", "r", "e", "n", "d"], example: "My friend and I play in the yard." },
                { word: "Family", phonemes: ["f", "a", "m", "i", "l", "ee"], example: "I love my big and happy family." },
                { word: "Helper", phonemes: ["h", "e", "l", "p", "e", "r"], example: "Be a good helper at home today." },
                { word: "Lesson", phonemes: ["l", "e", "s", "o", "n"], example: "Listen to the lesson of our teacher." },
                { word: "Nature", phonemes: ["n", "ay", "ch", "u", "r"], example: "We must take good care of nature." },
                { word: "Person", phonemes: ["p", "e", "r", "s", "o", "n"], example: "Rizal is a very famous person." },
                { word: "Street", phonemes: ["s", "t", "r", "ee", "t"], example: "Keep the street clean and tidy." },
                { word: "Health", phonemes: ["h", "e", "l", "th"], example: "Good health is a great wealth." },
                { word: "Doctor", phonemes: ["d", "o", "k", "t", "o", "r"], example: "The doctor checks the sick child." },
                { word: "Farmer", phonemes: ["f", "a", "r", "m", "e", "r"], example: "The farmer plants rice in the field." },
                { word: "Parent", phonemes: ["p", "a", "r", "e", "n", "t"], example: "Respect your parent at all times." },
                { word: "Animal", phonemes: ["a", "n", "i", "m", "e", "l"], example: "The dog is a very loyal animal." },
                { word: "Flower", phonemes: ["f", "l", "ow", "e", "r"], example: "The gumamela is a red flower." },
                { word: "Market", phonemes: ["m", "a", "r", "k", "e", "t"], example: "Mother buys fish at the market." },
                { word: "School", phonemes: ["s", "k", "oo", "l"], example: "Our school is near the big plaza." },
                { word: "Island", phonemes: ["ay", "l", "a", "n", "d"], example: "We live on a very beautiful island." },
                { word: "Garden", phonemes: ["g", "a", "r", "d", "e", "n"], example: "The garden is full of green plants." },
                { word: "Leader", phonemes: ["l", "ee", "d", "e", "r"], example: "He is the leader of our group." },
                { word: "Player", phonemes: ["p", "l", "ay", "e", "r"], example: "She is a fast player in the game." },
                { word: "Bottle", phonemes: ["b", "o", "t", "e", "l"], example: "Put the water in the glass bottle." },
                { word: "Bridge", phonemes: ["b", "r", "i", "j"], example: "The bridge connects the two towns." },
                { word: "Church", phonemes: ["ch", "e", "r", "ch"], example: "We go to church every Sunday." },
                { word: "Ground", phonemes: ["g", "r", "ow", "n", "d"], example: "Do not throw trash on the ground." },
                { word: "Forest", phonemes: ["f", "o", "r", "e", "s", "t"], example: "Wild birds live in the deep forest." },
                { word: "Jungle", phonemes: ["j", "a", "ng", "g", "e", "l"], example: "The jungle is home to many snakes." },
                { word: "Orange", phonemes: ["o", "r", "a", "n", "j"], example: "Eat an orange for Vitamin C." },
                { word: "Pencil", phonemes: ["p", "e", "n", "s", "i", "l"], example: "Sharpen your pencil before writing." },
                { word: "Silver", phonemes: ["s", "i", "l", "v", "e", "r"], example: "She won a shiny silver medal." },
                { word: "Ticket", phonemes: ["t", "i", "k", "e", "t"], example: "Buy a ticket for the bus ride." },
                { word: "Village", phonemes: ["v", "i", "l", "i", "j"], example: "Peace lives in our small village." },
                { word: "Window", phonemes: ["w", "i", "n", "d", "o"], example: "The window lets the fresh air in." },
                { word: "Winter", phonemes: ["w", "i", "n", "t", "e", "r"], example: "It is cold during the winter." },
                { word: "Banana", phonemes: ["b", "a", "n", "a", "n", "a"], example: "The banana is a healthy snack." },
                { word: "Dinner", phonemes: ["d", "i", "n", "e", "r"], example: "The family eats dinner together." },
                { word: "Eraser", phonemes: ["ee", "r", "ay", "s", "e", "r"], example: "Use an eraser to fix the error." },
                { word: "Hammer", phonemes: ["h", "a", "m", "e", "r"], example: "Use a hammer to fix the fence." },
                { word: "Insect", phonemes: ["i", "n", "s", "e", "k", "t"], example: "The ant is a very busy insect." },
                { word: "Jacket", phonemes: ["j", "a", "k", "e", "t"], example: "Wear a jacket if you feel cold." },
                { word: "Kettle", phonemes: ["k", "e", "t", "e", "l"], example: "The kettle is whistling on the stove." },
                { word: "Lizard", phonemes: ["l", "i", "z", "e", "r", "d"], example: "The lizard stays on the ceiling." },
                { word: "Mirror", phonemes: ["m", "i", "r", "o", "r"], example: "Look at the mirror and smile." },
                { word: "Number", phonemes: ["n", "u", "m", "b", "e", "r"], example: "Identify the missing number here." },
                { word: "Parrot", phonemes: ["p", "a", "r", "o", "t"], example: "The parrot can copy what you say." },
                { word: "Rabbit", phonemes: ["r", "a", "b", "i", "t"], example: "The rabbit likes to eat carrots." },
                { word: "Saddle", phonemes: ["s", "a", "d", "e", "l"], example: "Place the saddle on the horse." },
                { word: "Tunnel", phonemes: ["t", "u", "n", "e", "l"], example: "The car went through the tunnel." },
                { word: "Valley", phonemes: ["v", "a", "l", "ee"], example: "The flowers bloom in the valley." },
                { word: "Wallet", phonemes: ["w", "o", "l", "e", "t"], example: "Keep your money inside your wallet." },
                { word: "Basket", phonemes: ["b", "a", "s", "k", "e", "t"], example: "Put the mangoes in the basket." }
            ],
            intermediate: [
                // 50 Items - Grade 4 DepEd Vocabulary (More complex words)
                { word: "Honest", phonemes: ["o", "n", "e", "s", "t"], example: "An honest child is loved by all." },
                { word: "Protect", phonemes: ["p", "r", "o", "t", "e", "k", "t"], example: "We must protect our environment." },
                { word: "Country", phonemes: ["k", "a", "n", "t", "r", "ee"], example: "The Philippines is our country." },
                { word: "Respect", phonemes: ["r", "e", "s", "p", "e", "k", "t"], example: "Show respect to the elderly." },
                { word: "Community", phonemes: ["k", "o", "m", "y", "u", "n", "i", "t", "ee"], example: "Our community is clean and safe." },
                { word: "Bravery", phonemes: ["b", "r", "ay", "v", "e", "r", "ee"], example: "The soldier showed great bravery." },
                { word: "Citizen", phonemes: ["s", "i", "t", "i", "z", "e", "n"], example: "I am a proud Filipino citizen." },
                { word: "Culture", phonemes: ["k", "a", "l", "ch", "u", "r"], example: "Our culture is rich and unique." },
                { word: "History", phonemes: ["h", "i", "s", "t", "o", "r", "ee"], example: "We study history in our class." },
                { word: "Justice", phonemes: ["j", "a", "s", "t", "i", "s"], example: "The judge gives justice to all." },
                { word: "Library", phonemes: ["l", "ay", "b", "r", "a", "r", "ee"], example: "Silence is observed in the library." },
                { word: "Morning", phonemes: ["m", "o", "r", "n", "i", "ng"], example: "The sun shines bright this morning." },
                { word: "Ocean", phonemes: ["o", "sh", "u", "n"], example: "The ocean is home to many fish." },
                { word: "Officer", phonemes: ["o", "f", "i", "s", "e", "r"], example: "The police officer helps us." },
                { word: "Pantry", phonemes: ["p", "a", "n", "t", "r", "ee"], example: "Store the canned goods in the pantry." },
                { word: "Quality", phonemes: ["k", "w", "o", "l", "i", "t", "ee"], example: "This book has very good quality." },
                { word: "Recipe", phonemes: ["r", "e", "s", "i", "p", "ee"], example: "Follow the recipe to cook adobo." },
                { word: "Science", phonemes: ["s", "ay", "e", "n", "s"], example: "We do experiments in science." },
                { word: "Traffic", phonemes: ["t", "r", "a", "f", "i", "k"], example: "There is heavy traffic in the city." },
                { word: "Weather", phonemes: ["w", "e", "th", "e", "r"], example: "The weather is sunny and warm." },
                { word: "Uniform", phonemes: ["y", "u", "n", "i", "f", "o", "r", "m"], example: "Wear your clean school uniform." },
                { word: "Victory", phonemes: ["v", "i", "k", "t", "o", "r", "ee"], example: "The team celebrated their victory." },
                { word: "Wisdom", phonemes: ["w", "i", "z", "d", "u", "m"], example: "Lolo has a lot of wisdom to share." },
                { word: "Journey", phonemes: ["j", "e", "r", "n", "ee"], example: "Life is a long and happy journey." },
                { word: "Freedom", phonemes: ["f", "r", "ee", "d", "u", "m"], example: "We enjoy the freedom of speech." },
                { word: "Shelter", phonemes: ["sh", "e", "l", "t", "e", "r"], example: "The dog found shelter under the tree." },
                { word: "Dolphin", phonemes: ["d", "o", "l", "f", "i", "n"], example: "The dolphin is a smart mammal." },
                { word: "Anchor", phonemes: ["a", "ng", "k", "o", "r"], example: "The ship dropped its heavy anchor." },
                { word: "Button", phonemes: ["b", "a", "t", "u", "n"], example: "Press the button to start the fan." },
                { word: "Candle", phonemes: ["k", "a", "n", "d", "e", "l"], example: "Light a candle when it is dark." },
                { word: "Circle", phonemes: ["s", "e", "r", "k", "e", "l"], example: "Draw a perfect circle on paper." },
                { word: "Feather", phonemes: ["f", "e", "th", "e", "r"], example: "The bird dropped a soft feather." },
                { word: "Guitar", phonemes: ["g", "i", "t", "a", "r"], example: "He can play the guitar well." },
                { word: "Helmet", phonemes: ["h", "e", "l", "m", "e", "t"], example: "Always wear a helmet on a bike." },
                { word: "Island", phonemes: ["ay", "l", "a", "n", "d"], example: "Boracay is a very famous island." },
                { word: "Jelly", phonemes: ["j", "e", "l", "ee"], example: "I like strawberry jelly on bread." },
                { word: "Kitten", phonemes: ["k", "i", "t", "e", "n"], example: "The kitten is playing with yarn." },
                { word: "Magnet", phonemes: ["m", "a", "g", "n", "e", "t"], example: "The magnet pulls the iron nail." },
                { word: "Nickel", phonemes: ["n", "i", "k", "e", "l"], example: "A nickel is a small metal coin." },
                { word: "Otter", phonemes: ["o", "t", "e", "r"], example: "The otter lives near the water." },
                { word: "Panda", phonemes: ["p", "a", "n", "d", "a"], example: "The panda eats green bamboo." },
                { word: "Quartz", phonemes: ["k", "w", "o", "r", "t", "s"], example: "Quartz is a very hard mineral." },
                { word: "Rocket", phonemes: ["r", "o", "k", "e", "t"], example: "The rocket flies to outer space." },
                { word: "Shadow", phonemes: ["sh", "a", "d", "o"], example: "Look at your shadow on the ground." },
                { word: "Tiger", phonemes: ["t", "ay", "g", "e", "r"], example: "The tiger has black stripes." },
                { word: "Uncle", phonemes: ["a", "ng", "k", "e", "l"], example: "My uncle lives in the province." },
                { word: "Violin", phonemes: ["v", "ay", "o", "l", "i", "n"], example: "She plays the violin beautifully." },
                { word: "Walnut", phonemes: ["w", "o", "l", "n", "a", "t"], example: "The walnut is a healthy nut." },
                { word: "Yogurt", phonemes: ["y", "o", "g", "e", "r", "t"], example: "I love to eat frozen yogurt." },
                { word: "Zebra", phonemes: ["z", "ee", "b", "r", "a"], example: "The zebra is a wild animal." }
            ],
            advanced: [
                // 50 Items - Grade 4 DepEd Sentences (Compound & Complex)
                { sentence: "Clean the yard because it is very dirty", phonemes: ["k", "l", "ee", "n", "th", "e", "y", "a", "r", "d"], example: "Clean the yard because it is very dirty and full of leaves." },
                { sentence: "The sun rises in the east every morning", phonemes: ["th", "e", "s", "a", "n", "r", "ay", "z", "e", "s"], example: "The sun rises in the east every morning to give us light." },
                { sentence: "Brush your teeth to keep them white", phonemes: ["b", "r", "a", "sh", "y", "o", "r", "t", "ee", "th"], example: "Brush your teeth to keep them white and healthy." },
                { sentence: "Eat vegetables to have a strong body", phonemes: ["ee", "t", "v", "e", "j", "t", "u", "b", "e", "l", "z"], example: "Eat vegetables to have a strong body and sharp mind." },
                { sentence: "The teacher talks and the pupils listen", phonemes: ["th", "e", "t", "ee", "ch", "e", "r"], example: "The teacher talks and the pupils listen to the lesson." },
                { sentence: "Wash your hands before and after eating", phonemes: ["w", "o", "sh", "h", "a", "n", "d", "z"], example: "Wash your hands before and after eating your food." },
                { sentence: "Plant a tree today for a better future", phonemes: ["p", "l", "a", "n", "t", "a", "t", "r", "ee"], example: "Plant a tree today for a better and greener future." },
                { sentence: "Help the elderly cross the busy street", phonemes: ["h", "e", "l", "p", "th", "e", "e", "l", "d", "e", "r", "l", "ee"], example: "Help the elderly cross the busy street with care." },
                { sentence: "Honesty is the best policy for everyone", phonemes: ["o", "n", "e", "s", "t", "ee"], example: "Honesty is the best policy for everyone in the world." },
                { sentence: "Keep our school clean for a better study", phonemes: ["k", "ee", "p", "a", "u", "r", "s", "k", "oo", "l"], example: "Keep our school clean for a better study environment." },
                { sentence: "Read books to discover new things", phonemes: ["r", "ee", "d", "b", "u", "k", "s"], example: "Read many books to discover new things every day." },
                { sentence: "Water the plants to help them grow tall", phonemes: ["w", "o", "t", "e", "r", "th", "e", "p", "l", "a", "n", "t", "s"], example: "Water the plants to help them grow tall and healthy." },
                { sentence: "Listen carefully to follow the directions", phonemes: ["l", "i", "s", "e", "n", "k", "e", "r", "f", "u", "l", "ee"], example: "Listen carefully to follow the directions of the test." },
                { sentence: "Always tell the truth no matter what", phonemes: ["o", "l", "w", "ay", "z", "t", "e", "l"], example: "Always tell the truth no matter what happens." },
                { sentence: "We must segregate our trash at home", phonemes: ["w", "ee", "m", "a", "s", "t"], example: "We must segregate our trash at home and in school." },
                { sentence: "The Philippine flag has four colors", phonemes: ["th", "e", "f", "l", "a", "g"], example: "The Philippine flag has four colors: red, white, blue, and yellow." },
                { sentence: "Filipinos are known for being hospitable", phonemes: ["f", "i", "l", "i", "p", "ee", "n", "o", "z"], example: "Filipinos are known for being hospitable to guests." },
                { sentence: "The National Anthem is Lupang Hinirang", phonemes: ["th", "e", "n", "a", "sh", "u", "n", "a", "l"], example: "The National Anthem is Lupang Hinirang, our song of pride." },
                { sentence: "Rizal is our National Hero of the land", phonemes: ["r", "i", "z", "a", "l"], example: "Jose Rizal is our National Hero of the land of the brave." },
                { sentence: "Say please and thank you to be polite", phonemes: ["s", "ay", "p", "l", "ee", "z"], example: "Say please and thank you to be polite to everyone." },
                { sentence: "Go to bed early to wake up strong", phonemes: ["g", "o", "t", "u", "b", "e", "d"], example: "Go to bed early to wake up strong and happy tomorrow." },
                { sentence: "The rainbow appears after the heavy rain", phonemes: ["th", "e", "r", "ay", "n", "b", "o"], example: "The rainbow appears after the heavy rain has stopped." },
                { sentence: "Dogs are man's best friend for life", phonemes: ["d", "o", "g", "z", "a", "r"], example: "Dogs are man's best friend for life and for always." },
                { sentence: "Study hard to reach your big dreams", phonemes: ["s", "t", "a", "d", "ee", "h", "a", "r", "d"], example: "Study hard to reach your big dreams in the future." },
                { sentence: "Respect the national symbols of our land", phonemes: ["r", "e", "s", "p", "e", "k", "t"], example: "Respect the national symbols of our land and our people." },
                { sentence: "A clean body is a healthy body", phonemes: ["a", "k", "l", "ee", "n", "b", "o", "d", "ee"], example: "A clean body is a healthy body for everyone." },
                { sentence: "Unity brings peace to our big nation", phonemes: ["y", "u", "n", "i", "t", "ee"], example: "Unity brings peace to our big nation and our world." },
                { sentence: "Be kind to animals and all living things", phonemes: ["b", "ee", "k", "ay", "n", "d"], example: "Be kind to animals and all living things on earth." },
                { sentence: "The moon reflects the light of the sun", phonemes: ["th", "e", "m", "oo", "n"], example: "The moon reflects the light of the sun during the night." },
                { sentence: "Birds build their nests on the tree", phonemes: ["b", "e", "r", "d", "z"], example: "Birds build their nests on the tall tree in our yard." },
                { sentence: "Exercise daily to stay fit and active", phonemes: ["e", "k", "s", "e", "r", "s", "ay", "z"], example: "Exercise daily to stay fit and active for school." },
                { sentence: "Save water by turning off the faucet", phonemes: ["s", "ay", "v", "w", "o", "t", "e", "r"], example: "Save water by turning off the faucet when not in use." },
                { sentence: "The carabaos help the farmers in the field", phonemes: ["th", "e", "k", "a", "r", "a", "b", "ow"], example: "The carabaos help the farmers in the rice field." },
                { sentence: "Sampaguita is the national flower of us", phonemes: ["s", "a", "m", "p", "a", "g", "ee", "t", "a"], example: "Sampaguita is the national flower of us Filipinos." },
                { sentence: "Do your homework before you play games", phonemes: ["d", "oo", "y", "o", "r"], example: "Do your homework before you play games with friends." },
                { sentence: "Listen to the advice of your parents", phonemes: ["l", "i", "s", "e", "n"], example: "Listen to the advice of your parents for a better life." },
                { sentence: "The library is a place for reading", phonemes: ["th", "e", "l", "ay", "b", "r", "a", "r", "ee"], example: "The library is a place for reading and learning more." },
                { sentence: "Write neatly on your school notebook", phonemes: ["r", "ay", "t", "n", "ee", "t", "l", "ee"], example: "Write neatly on your school notebook during the class." },
                { sentence: "The ocean waves are high and strong", phonemes: ["th", "e", "o", "sh", "u", "n"], example: "The ocean waves are high and strong during the storm." },
                { sentence: "Share your toys with your little brother", phonemes: ["sh", "e", "r", "y", "o", "r"], example: "Share your toys with your little brother and sister." },
                { sentence: "The firemen put out the big fire", phonemes: ["th", "e", "f", "ay", "r", "m", "e", "n"], example: "The firemen put out the big fire in the city." },
                { sentence: "A healthy breakfast starts the day right", phonemes: ["a", "h", "e", "l", "th", "ee"], example: "A healthy breakfast starts the day right for all." },
                { sentence: "Always wear your seatbelt for safety", phonemes: ["o", "l", "w", "ay", "z"], example: "Always wear your seatbelt for safety while in the car." },
                { sentence: "The ants work together to find food", phonemes: ["th", "e", "a", "n", "t", "s"], example: "The ants work together to find food for the winter." },
                { sentence: "The computer helps us in our research", phonemes: ["th", "e", "k", "o", "m", "p", "y", "u", "t", "e", "r"], example: "The computer helps us in our research for school." },
                { sentence: "Follow the rules of the road at all times", phonemes: ["f", "o", "l", "o", "th", "e"], example: "Follow the rules of the road at all times for safety." },
                { sentence: "The stars twinkle in the dark night sky", phonemes: ["th", "e", "s", "t", "a", "r", "z"], example: "The stars twinkle in the dark night sky so bright." },
                { sentence: "Be proud of your own local products", phonemes: ["b", "ee", "p", "r", "ow", "d"], example: "Be proud of your own local products from the province." },
                { sentence: "Recycle old papers to save the trees", phonemes: ["r", "ee", "s", "ay", "k", "e", "l"], example: "Recycle old papers to save the trees and the earth." },
                { sentence: "The community is happy when it is clean", phonemes: ["th", "e", "k", "o", "m", "y", "u", "n", "i", "t", "ee"], example: "The community is happy when it is clean and green." }
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