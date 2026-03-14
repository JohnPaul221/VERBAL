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
            /* Vibrant Grade 4 Palette */
            --primary: #FF4757;        /* Coral Red */
            --success: #2ED573;        /* Bright Mint */
            --kids-blue: #54A0FF;      /* Sky Blue */
            --kids-yellow: #FECA57;    /* Sunflower */
            --bg-gradient: linear-gradient(135deg, #FF9FF3 0%, #54A0FF 100%); /* Pink to Blue */
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
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #F19066; z-index: 100; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: #5F27CD; padding: 5px 12px; border-radius: 10px; border: 2px solid #341F97; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: var(--primary); color: white; border: 2px solid #EE5253; border-radius: 10px; z-index: 10; cursor: pointer; }

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
            padding: 10px; border-radius: 15px; background-color: #F7F1E3;
            display: flex; flex-direction: column; border: 2px solid #D1CCC0;
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

        .divider { width: 3px; background: #FF9F43; opacity: 0.3; border-radius: 10px; }

        /* --- IMAGE BOX + FULL SCREEN POPUP --- */
        .word-image-box {
            width: 120px;
            height: 120px;
            border: 4px solid #FF9F43;
            border-radius: 15px;
            background: white;
            position: relative;
            background-size: cover;
            background-position: center;
            cursor: zoom-in;
            flex-shrink: 0;
            visibility: hidden;
        }

        .word-image-box.hidden { display: none !important; }

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
        #difficulty { width: 90%; padding: 8px; border-radius: 12px; border: 3px solid #1DD1A1; font-family: inherit; font-weight: bold; margin-bottom: 5px; background: white; }

        .mic-icon {
            width: 65px; height: 65px;
            background: #5F27CD;
            border: 5px solid #A29BFE;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            color: white; font-size: 26px;
            cursor: pointer; margin: 5px auto;
            box-shadow: 0 4px 0px #341F97;
        }

        .reading-material { background: #FFF9E6; border-radius: 20px; border: 3px dashed #FF9F43; padding: 10px; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 15px; width: 95%; margin: 5px auto; }
        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: #5758BB; text-transform: uppercase; line-height: 1.1; }
        .transcript { font-size: 1.1rem; font-weight: bold; color: #10AC84; background: #E3FCEF; border: 2px solid #1DD1A1; border-radius: 15px; padding: 10px; width: 90%; min-height: 30px; }

        /* --- PRO SCOREBOARD --- */
        .stats-container { display: flex; justify-content: space-around; align-items: center; background: white; padding: 15px 10px; border-radius: 20px; border: 5px solid #48DBFB; margin: 5px auto; width: 100%; box-sizing: border-box; box-shadow: 0 6px 0px #0ABDE3; }
        .stat-item span { display: block; font-size: 0.8rem; color: #57606F; font-weight: 800; text-transform: uppercase; }
        .stat-item .value { font-size: 3rem; font-weight: 900; color: #2E86DE; line-height: 1; text-shadow: 1px 1px 0px #f1f2f6; }

        /* --- PROGRESS BAR --- */
        .progress-bar { width: 100%; height: 25px; background: #FFFFFF !important; border-radius: 15px; border: 4px solid #DFE4EA; overflow: hidden; position: relative; box-sizing: border-box; }
        .progress-bar-inner { height: 100%; width: 0%; background: #1DD1A1 !important; transition: width 0.6s ease-in-out; }

        /* --- PRO SENTENCE --- */
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid #DFE4EA; color: #2F3542; min-height: 60px; display: flex; align-items: center; justify-content: center; visibility: hidden; }
        .bg-success-feedback { background: #F0FFF4 !important; color: #2ED573 !important; border: 3px solid #2ED573 !important; }

        /* --- PRO STARS --- */
        #stars { font-size: 2.5rem; display: flex; gap: 5px; }
        .fa-star { color: #CED6E0; transition: color 0.3s ease; }
        .filled-star { color: #FF9F43 !important; text-shadow: 0 0 15px rgba(255, 159, 67, 0.6); animation: starPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, starShine 2s infinite linear; transform-origin: center; }

        /* --- PRO UI MYSTERY MATCHING --- */
        #memoryGrid { background: rgba(255, 255, 255, 0.5); padding: 20px; border-radius: 20px; border: 2px solid #EBF7FF; }
        .memory-card {
            width: 80px; height: 80px;
            background: linear-gradient(145deg, #FF6B6B, #EE5253);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 2.8rem;
            border-radius: 18px; cursor: pointer; box-shadow: 0 6px 0 #B33939;
            transition: all 0.3s;
            border: 4px solid rgba(255, 255, 255, 0.3); position: relative;
        }
        .memory-card.flipped { background: white; color: #2F3542; border: 4px solid #FF9F43; box-shadow: 0 6px 0 #EE9500; }
        .memory-card.matched { background: #1DD1A1; border-color: #10AC84; }

        .waveform { width: 95%; height: 40px; border-radius: 12px; border: 2px solid #54A0FF; background: #EBF7FF; margin: 5px auto; }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 10px; }

        button { padding: 10px 20px; border-radius: 20px; border: none; font-weight: 800; cursor: pointer; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: #FF9F43; color: white; box-shadow: 0 4px 0 #EE9500; }
        .btn-secondary { background: #54A0FF; color: white; box-shadow: 0 4px 0 #2E86DE; width: 90%; }
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

        const wordBank   = {
            beginner: [
                { word: "The heavy rain flooded the street", phonemes: ["th", "e", "h", "e", "v", "ee", "r", "ay", "n", "f", "l", "a", "d", "e", "d", "th", "e", "s", "t", "r", "ee", "t"] },
                { word: "She wears a necklace made of shells", phonemes: ["sh", "ee", "w", "e", "r", "z", "a", "n", "e", "k", "l", "a", "s", "m", "ay", "d", "o", "v", "sh", "e", "l", "z"] },
                { word: "Our teacher wrote on the blackboard", phonemes: ["ow", "r", "t", "ee", "ch", "e", "r", "r", "o", "t", "o", "n", "th", "e", "b", "l", "a", "k", "b", "o", "r", "d"] },
                { word: "The eagle flies above the clouds", phonemes: ["th", "e", "ee", "g", "e", "l", "f", "l", "ay", "z", "a", "b", "a", "v", "th", "e", "k", "l", "ow", "d", "z"] },
                { word: "The market is crowded every Sunday", phonemes: ["th", "e", "m", "a", "r", "k", "e", "t", "i", "z", "k", "r", "ow", "d", "e", "d", "e", "v", "r", "ee", "s", "a", "n", "d", "ay"] },
                { word: "We use a compass for directions", phonemes: ["w", "ee", "y", "oo", "z", "a", "k", "a", "m", "p", "a", "s", "f", "o", "r", "d", "i", "r", "e", "k", "sh", "u", "n", "z"] },
                { word: "The farmer harvested the ripe corn", phonemes: ["th", "e", "f", "a", "r", "m", "e", "r", "h", "a", "r", "v", "e", "s", "t", "e", "d", "th", "e", "r", "ay", "p", "k", "o", "r", "n"] },
                { word: "A forest provides oxygen for us", phonemes: ["a", "f", "o", "r", "e", "s", "t", "p", "r", "o", "v", "ay", "d", "z", "o", "k", "s", "i", "j", "e", "n", "f", "o", "r", "a", "s"] },
                { word: "The moon reflects light from sun", phonemes: ["th", "e", "m", "oo", "n", "r", "e", "f", "l", "e", "k", "t", "s", "l", "ay", "t", "f", "r", "o", "m", "s", "a", "n"] },
                { word: "He bought a new pair of shoes", phonemes: ["h", "ee", "b", "o", "t", "a", "n", "y", "oo", "p", "e", "r", "o", "v", "sh", "oo", "z"] },
                { word: "The police officer caught the thief", phonemes: ["th", "e", "p", "o", "l", "ee", "s", "o", "f", "i", "s", "e", "r", "k", "o", "t", "th", "e", "th", "ee", "f"] },
                { word: "Please arrange the chairs in line", phonemes: ["p", "l", "ee", "z", "a", "r", "ay", "n", "j", "th", "e", "ch", "e", "r", "z", "i", "n", "l", "ay", "n"] },
                { word: "The library is a quiet place", phonemes: ["th", "e", "l", "ay", "b", "r", "e", "r", "ee", "i", "z", "a", "k", "w", "ay", "e", "t", "p", "l", "ay", "s"] },
                { word: "Gravity pulls things to the ground", phonemes: ["g", "r", "a", "v", "i", "t", "ee", "p", "u", "l", "z", "th", "ee", "ng", "z", "t", "oo", "th", "e", "g", "r", "ow", "n", "d"] },
                { word: "The athletes run on the track", phonemes: ["th", "e", "a", "th", "l", "ee", "t", "s", "r", "a", "n", "o", "n", "th", "e", "t", "r", "a", "k"] },
                { word: "A spider has eight hairy legs", phonemes: ["a", "s", "p", "ay", "d", "e", "r", "h", "a", "z", "ay", "t", "h", "e", "r", "ee", "l", "e", "g", "z"] },
                { word: "We study about different animal habitats", phonemes: ["w", "ee", "s", "t", "a", "d", "ee", "a", "b", "ow", "t", "d", "i", "f", "e", "r", "e", "n", "t", "a", "n", "i", "m", "a", "l", "h", "a", "b", "i", "t", "a", "t", "s"] },
                { word: "The river flows to the ocean", phonemes: ["th", "e", "r", "i", "v", "e", "r", "f", "l", "o", "z", "t", "oo", "th", "e", "o", "sh", "u", "n"] },
                { word: "Be careful when using sharp tools", phonemes: ["b", "ee", "k", "e", "r", "f", "u", "l", "w", "e", "n", "y", "oo", "z", "i", "ng", "sh", "a", "r", "p", "t", "oo", "l", "z"] },
                { word: "Plants need sunlight to make food", phonemes: ["p", "l", "a", "n", "t", "s", "n", "ee", "d", "s", "a", "n", "l", "ay", "t", "t", "oo", "m", "ay", "k", "f", "oo", "d"] },
                { word: "The carpenter fixed the broken table", phonemes: ["th", "e", "k", "a", "r", "p", "e", "n", "t", "e", "r", "f", "i", "k", "s", "t", "th", "e", "b", "r", "o", "k", "e", "n", "t", "ay", "b", "e", "l"] },
                { word: "The scientist studied the tiny cells", phonemes: ["th", "e", "s", "ay", "e", "n", "t", "i", "s", "t", "s", "t", "a", "d", "ee", "d", "th", "e", "t", "ay", "n", "ee", "s", "e", "l", "z"] },
                { word: "I saw a beautiful rainbow today", phonemes: ["ay", "s", "o", "a", "b", "y", "oo", "t", "i", "f", "u", "l", "r", "ay", "n", "b", "o", "t", "u", "d", "ay"] },
                { word: "Computers are useful for our work", phonemes: ["k", "o", "m", "p", "y", "oo", "t", "e", "r", "z", "a", "r", "y", "oo", "s", "f", "u", "l", "f", "o", "r", "ow", "r", "w", "e", "r", "k"] },
                { word: "The children played in the yard", phonemes: ["th", "e", "ch", "i", "l", "d", "r", "e", "n", "p", "l", "ay", "d", "i", "n", "th", "e", "y", "a", "r", "d"] },
                { word: "She bought fresh milk from dairy", phonemes: ["sh", "ee", "b", "o", "t", "f", "r", "e", "sh", "m", "i", "l", "k", "f", "r", "o", "m", "d", "e", "r", "ee"] },
                { word: "The pilot landed the plane safely", phonemes: ["th", "e", "p", "ay", "l", "o", "t", "l", "a", "n", "d", "e", "d", "th", "e", "p", "l", "ay", "n", "s", "ay", "f", "l", "ee"] },
                { word: "Always wash your hands before meals", phonemes: ["o", "l", "w", "ay", "z", "w", "o", "sh", "y", "o", "r", "h", "a", "n", "d", "z", "b", "e", "f", "o", "r", "m", "ee", "l", "z"] },
                { word: "The mountains are covered with trees", phonemes: ["th", "e", "m", "ow", "n", "t", "i", "n", "z", "a", "r", "k", "a", "v", "e", "r", "d", "w", "i", "th", "t", "r", "ee", "z"] },
                { word: "We celebrated my birthday at home", phonemes: ["w", "ee", "s", "e", "l", "e", "b", "r", "ay", "t", "e", "d", "m", "ay", "b", "e", "r", "th", "d", "ay", "a", "t", "h", "o", "m"] },
                { word: "The baker made delicious sweet bread", phonemes: ["th", "e", "b", "ay", "k", "e", "r", "m", "ay", "d", "d", "e", "l", "i", "sh", "u", "s", "s", "w", "ee", "t", "b", "r", "e", "d"] },
                { word: "The students listened to the story", phonemes: ["th", "e", "s", "t", "oo", "d", "e", "n", "t", "s", "l", "i", "s", "e", "n", "d", "t", "oo", "th", "e", "s", "t", "o", "r", "ee"] },
                { word: "Electricity gives power to the lamp", phonemes: ["i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "g", "i", "v", "z", "p", "ow", "e", "r", "t", "oo", "th", "e", "l", "a", "m", "p"] },
                { word: "An umbrella protects us from rain", phonemes: ["a", "n", "a", "m", "b", "r", "e", "l", "a", "p", "r", "o", "t", "e", "k", "t", "s", "a", "s", "f", "r", "o", "m", "r", "ay", "n"] },
                { word: "The ocean has many salt water", phonemes: ["th", "e", "o", "sh", "u", "n", "h", "a", "z", "m", "e", "n", "ee", "s", "o", "l", "t", "w", "o", "t", "e", "r"] },
                { word: "The postman delivered the long letter", phonemes: ["th", "e", "p", "o", "s", "t", "m", "a", "n", "d", "e", "l", "i", "v", "e", "r", "d", "th", "e", "l", "o", "ng", "l", "e", "t", "e", "r"] },
                { word: "The soldiers marched during the parade", phonemes: ["th", "e", "s", "o", "l", "j", "e", "r", "z", "m", "a", "r", "ch", "t", "d", "u", "r", "i", "ng", "th", "e", "p", "a", "r", "ay", "d"] },
                { word: "Lions are known as brave animals", phonemes: ["l", "ay", "o", "n", "z", "a", "r", "n", "o", "n", "a", "z", "b", "r", "ay", "v", "a", "n", "i", "m", "a", "l", "z"] },
                { word: "The volcano erupted with thick lava", phonemes: ["th", "e", "v", "o", "l", "k", "ay", "n", "o", "i", "r", "a", "p", "t", "e", "d", "w", "i", "th", "th", "i", "k", "l", "a", "v", "a"] },
                { word: "Fruits are good for our health", phonemes: ["f", "r", "oo", "t", "s", "a", "r", "g", "u", "d", "f", "o", "r", "ow", "r", "h", "e", "l", "th"] },
                { word: "The dancer moved with great grace", phonemes: ["th", "e", "d", "a", "n", "s", "e", "r", "m", "oo", "v", "d", "w", "i", "th", "g", "r", "ay", "t", "g", "r", "ay", "s"] },
                { word: "Our country is found in Asia", phonemes: ["ow", "r", "k", "a", "n", "t", "r", "ee", "i", "z", "f", "ow", "n", "d", "i", "n", "ay", "zh", "a"] },
                { word: "He fixed the bicycle in garage", phonemes: ["h", "ee", "f", "i", "k", "s", "t", "th", "e", "b", "ay", "s", "i", "k", "e", "l", "i", "n", "g", "a", "r", "a", "zh"] },
                { word: "The moon shines bright at night", phonemes: ["th", "e", "m", "oo", "n", "sh", "ay", "n", "z", "b", "r", "ay", "t", "a", "t", "n", "ay", "t"] },
                { word: "Always tell the truth to everyone", phonemes: ["o", "l", "w", "ay", "z", "t", "e", "l", "th", "e", "t", "r", "oo", "th", "t", "oo", "e", "v", "r", "ee", "w", "a", "n"] },
                { word: "The chef cooked a tasty meal", phonemes: ["th", "e", "sh", "e", "f", "k", "u", "k", "t", "a", "t", "ay", "s", "t", "ee", "m", "ee", "l"] },
                { word: "Water is important for all life", phonemes: ["w", "o", "t", "e", "r", "i", "z", "i", "m", "p", "o", "r", "t", "a", "n", "t", "f", "o", "r", "o", "l", "l", "ay", "f"] },
                { word: "Birds build nests on the branches", phonemes: ["b", "e", "r", "d", "z", "b", "i", "l", "d", "n", "e", "s", "t", "s", "o", "n", "th", "e", "b", "r", "a", "n", "ch", "e", "z"] },
                { word: "The sun rises in the east", phonemes: ["th", "e", "s", "a", "n", "r", "ay", "z", "e", "z", "i", "n", "th", "e", "ee", "s", "t"] },
                { word: "We must protect our natural resources", phonemes: ["w", "ee", "m", "a", "s", "t", "p", "r", "o", "t", "e", "k", "t", "ow", "r", "n", "a", "ch", "u", "r", "a", "l", "r", "ee", "s", "o", "r", "s", "e", "z"] }
            ],
            intermediate: [
                { word: "The caterpillar turned into a beautiful butterfly", phonemes: ["th", "e", "k", "a", "t", "e", "r", "p", "i", "l", "e", "r", "t", "e", "r", "n", "d", "i", "n", "t", "oo", "a", "b", "y", "oo", "t", "i", "f", "u", "l", "b", "a", "t", "e", "r", "f", "l", "ay"] },
                { word: "The Philippines is an archipelago with many islands", phonemes: ["th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "z", "a", "n", "a", "r", "k", "i", "p", "e", "l", "a", "g", "o", "w", "i", "th", "m", "e", "n", "ee", "ay", "l", "a", "n", "d", "z"] },
                { word: "We should throw our trash in the bin", phonemes: ["w", "ee", "sh", "u", "d", "th", "r", "o", "ow", "r", "t", "r", "a", "sh", "i", "n", "th", "e", "b", "i", "n"] },
                { word: "Magellan arrived in the Philippines in fifteen twenty-one", phonemes: ["m", "a", "g", "e", "l", "a", "n", "a", "r", "ay", "v", "d", "i", "n", "th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "n", "f", "i", "f", "t", "ee", "n", "t", "w", "e", "n", "t", "ee", "w", "a", "n"] },
                { word: "The heart pumps blood to our whole body", phonemes: ["th", "e", "h", "a", "r", "t", "p", "a", "m", "p", "s", "b", "l", "a", "d", "t", "oo", "ow", "r", "h", "o", "l", "b", "o", "d", "ee"] },
                { word: "Solid liquid and gas are states of matter", phonemes: ["s", "o", "l", "i", "d", "l", "i", "k", "w", "i", "d", "a", "n", "d", "g", "a", "s", "a", "r", "s", "t", "ay", "t", "s", "o", "v", "m", "a", "t", "e", "r"] },
                { word: "The teacher explained the lesson very clearly today", phonemes: ["th", "e", "t", "ee", "ch", "e", "r", "i", "k", "s", "p", "l", "ay", "n", "d", "th", "e", "l", "e", "s", "o", "n", "v", "e", "r", "ee", "k", "l", "e", "r", "l", "ee", "t", "u", "d", "ay"] },
                { word: "Proper nutrition makes our bones and muscles strong", phonemes: ["p", "r", "o", "p", "e", "r", "n", "y", "oo", "t", "r", "i", "sh", "u", "n", "m", "ay", "k", "s", "ow", "r", "b", "o", "n", "z", "a", "n", "d", "m", "a", "s", "e", "l", "z", "s", "t", "r", "o", "ng"] },
                { word: "We use a thermometer to measure body heat", phonemes: ["w", "ee", "y", "oo", "z", "a", "th", "e", "r", "m", "o", "m", "e", "t", "e", "r", "t", "oo", "m", "e", "zh", "e", "r", "b", "o", "d", "ee", "h", "ee", "t"] },
                { word: "The local government helps the people in community", phonemes: ["th", "e", "l", "o", "k", "a", "l", "g", "a", "v", "e", "r", "n", "m", "e", "n", "t", "h", "e", "l", "p", "s", "th", "e", "p", "ee", "p", "e", "l", "i", "n", "k", "o", "m", "y", "oo", "n", "i", "t", "ee"] },
                { word: "Respect the national flag during the morning ceremony", phonemes: ["r", "e", "s", "p", "e", "k", "t", "th", "e", "n", "a", "sh", "u", "n", "a", "l", "f", "l", "a", "g", "d", "u", "r", "i", "ng", "th", "e", "m", "o", "r", "n", "i", "ng", "s", "e", "r", "e", "m", "o", "n", "ee"] },
                { word: "A healthy diet includes plenty of green vegetables", phonemes: ["a", "h", "e", "l", "th", "ee", "d", "ay", "e", "t", "i", "n", "k", "l", "oo", "d", "z", "p", "l", "e", "n", "t", "ee", "o", "v", "g", "r", "ee", "n", "v", "e", "j", "e", "t", "a", "b", "e", "l", "z"] },
                { word: "The sun provides light and heat to Earth", phonemes: ["th", "e", "s", "a", "n", "p", "r", "o", "v", "ay", "d", "z", "l", "ay", "t", "a", "n", "d", "h", "ee", "t", "t", "oo", "e", "r", "th"] },
                { word: "Water evaporation is part of the great cycle", phonemes: ["w", "o", "t", "e", "r", "i", "v", "a", "p", "o", "r", "ay", "sh", "u", "n", "i", "z", "p", "a", "r", "t", "o", "v", "th", "e", "g", "r", "ay", "t", "s", "ay", "k", "e", "l"] },
                { word: "Computers help us search for information very quickly", phonemes: ["k", "o", "m", "p", "y", "oo", "t", "e", "r", "z", "h", "e", "l", "p", "a", "s", "s", "e", "r", "ch", "f", "o", "r", "i", "n", "f", "o", "r", "m", "ay", "sh", "u", "n", "v", "e", "r", "ee", "k", "w", "i", "k", "l", "ee"] },
                { word: "The electricity flows through the long copper wires", phonemes: ["th", "e", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "f", "l", "o", "z", "th", "r", "oo", "th", "e", "l", "o", "ng", "k", "o", "p", "e", "r", "w", "ay", "r", "z"] },
                { word: "We should always recycle plastic and glass bottles", phonemes: ["w", "ee", "sh", "u", "d", "o", "l", "w", "ay", "z", "r", "ee", "s", "ay", "k", "e", "l", "p", "l", "a", "s", "t", "i", "k", "a", "n", "d", "g", "l", "a", "s", "b", "o", "t", "e", "l", "z"] },
                { word: "Honesty is the best policy in our life", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "i", "z", "th", "e", "b", "e", "s", "t", "p", "o", "l", "i", "s", "ee", "i", "n", "ow", "r", "l", "ay", "f"] },
                { word: "The heavy box was carried by the man", phonemes: ["th", "e", "h", "e", "v", "ee", "b", "o", "k", "s", "w", "o", "z", "k", "a", "r", "ee", "d", "b", "ay", "th", "e", "m", "a", "n"] },
                { word: "A sentence must start with a capital letter", phonemes: ["a", "s", "e", "n", "t", "e", "n", "s", "m", "a", "s", "t", "s", "t", "a", "r", "t", "w", "i", "th", "a", "k", "a", "p", "i", "t", "a", "l", "l", "e", "t", "e", "r"] },
                { word: "He writes his homework neatly in the notebook", phonemes: ["h", "ee", "r", "ay", "t", "s", "h", "i", "z", "h", "o", "m", "w", "e", "r", "k", "n", "ee", "t", "l", "ee", "i", "n", "th", "e", "n", "o", "t", "b", "u", "k"] },
                { word: "The moon moves around the planet Earth slowly", phonemes: ["th", "e", "m", "oo", "n", "m", "oo", "v", "z", "a", "r", "ow", "n", "d", "th", "e", "p", "l", "a", "n", "e", "t", "e", "r", "th", "s", "l", "o", "l", "ee"] },
                { word: "Eating fruits and vegetables is good for body", phonemes: ["ee", "t", "i", "ng", "f", "r", "oo", "t", "s", "a", "n", "d", "v", "e", "j", "e", "t", "a", "b", "e", "l", "z", "i", "z", "g", "u", "d", "f", "o", "r", "b", "o", "d", "ee"] },
                { word: "I will visit my grandparents in the province", phonemes: ["ay", "w", "i", "l", "v", "i", "z", "i", "t", "m", "ay", "g", "r", "a", "n", "d", "p", "e", "r", "e", "n", "t", "s", "i", "n", "th", "e", "p", "r", "o", "v", "i", "n", "s"] },
                { word: "The library contains many interesting story books now", phonemes: ["th", "e", "l", "ay", "b", "r", "e", "r", "ee", "k", "o", "n", "t", "ay", "n", "z", "m", "e", "n", "ee", "i", "n", "t", "r", "e", "s", "t", "i", "ng", "s", "t", "o", "r", "ee", "b", "u", "k", "s", "n", "ow"] },
                { word: "We should use water wisely at home today", phonemes: ["w", "ee", "sh", "u", "d", "y", "oo", "z", "w", "o", "t", "e", "r", "w", "ay", "z", "l", "ee", "a", "t", "h", "o", "m", "t", "u", "d", "ay"] },
                { word: "The solar system has eight different planets inside", phonemes: ["th", "e", "s", "o", "l", "e", "r", "s", "i", "s", "t", "e", "m", "h", "a", "z", "ay", "t", "d", "i", "f", "e", "r", "e", "n", "t", "p", "l", "a", "n", "e", "t", "s", "ay", "n", "s", "ay", "d"] },
                { word: "A map shows the location of various places", phonemes: ["a", "m", "a", "p", "sh", "o", "z", "th", "e", "l", "o", "k", "ay", "sh", "u", "n", "o", "v", "v", "e", "r", "ee", "u", "s", "p", "l", "ay", "s", "e", "z"] },
                { word: "Please turn off the lights when leaving room", phonemes: ["p", "l", "ee", "z", "t", "e", "r", "n", "o", "f", "th", "e", "l", "ay", "t", "s", "w", "e", "n", "l", "ee", "v", "i", "ng", "r", "oo", "m"] },
                { word: "The students are preparing for their final exams", phonemes: ["th", "e", "s", "t", "oo", "d", "e", "n", "t", "s", "a", "r", "p", "r", "ee", "p", "e", "r", "i", "ng", "f", "o", "r", "th", "e", "r", "f", "ay", "n", "a", "l", "i", "g", "z", "a", "m", "z"] },
                { word: "Reading books helps us to learn new words", phonemes: ["r", "ee", "d", "i", "ng", "b", "u", "k", "s", "h", "e", "l", "p", "s", "a", "s", "t", "oo", "l", "e", "r", "n", "n", "y", "oo", "w", "e", "r", "d", "z"] },
                { word: "The weather today is warm and very sunny", phonemes: ["th", "e", "w", "e", "th", "e", "r", "t", "u", "d", "ay", "i", "z", "w", "o", "r", "m", "a", "n", "d", "v", "e", "r", "ee", "s", "a", "n", "ee"] },
                { word: "She sang a beautiful song for the crowd", phonemes: ["sh", "ee", "s", "a", "ng", "a", "b", "y", "oo", "t", "i", "f", "u", "l", "s", "o", "ng", "f", "o", "r", "th", "e", "k", "r", "ow", "d"] },
                { word: "The mountain peak is very high and cold", phonemes: ["th", "e", "m", "ow", "n", "t", "i", "n", "p", "ee", "k", "i", "z", "v", "e", "r", "ee", "h", "ay", "a", "n", "d", "k", "o", "l", "d"] },
                { word: "Always be kind to animals and small insects", phonemes: ["o", "l", "w", "ay", "z", "b", "ee", "k", "ay", "n", "d", "t", "oo", "a", "n", "i", "m", "a", "l", "z", "a", "n", "d", "s", "m", "o", "l", "i", "n", "s", "e", "k", "t", "s"] },
                { word: "Our ancestors lived in small caves long ago", phonemes: ["ow", "r", "a", "n", "s", "e", "s", "t", "e", "r", "z", "l", "i", "v", "d", "i", "n", "s", "m", "o", "l", "k", "ay", "v", "z", "l", "o", "ng", "a", "g", "o"] },
                { word: "The firemen arrived quickly to help the family", phonemes: ["th", "e", "f", "ay", "r", "m", "e", "n", "a", "r", "ay", "v", "d", "k", "w", "i", "k", "l", "ee", "t", "oo", "h", "e", "l", "p", "th", "e", "f", "a", "m", "i", "l", "ee"] },
                { word: "Music makes people feel very happy and relaxed", phonemes: ["m", "y", "oo", "z", "i", "k", "m", "ay", "k", "s", "p", "ee", "p", "e", "l", "f", "ee", "l", "v", "e", "r", "ee", "h", "a", "p", "ee", "a", "n", "d", "r", "ee", "l", "a", "k", "s", "t"] },
                { word: "The butterfly has thin wings with bright colors", phonemes: ["th", "e", "b", "a", "t", "e", "r", "f", "l", "ay", "h", "a", "z", "th", "i", "n", "w", "i", "ng", "z", "w", "i", "th", "b", "r", "ay", "t", "k", "a", "l", "e", "r", "z"] },
                { word: "Always cover your mouth when you cough loudly", phonemes: ["o", "l", "w", "ay", "z", "k", "a", "v", "e", "r", "y", "o", "r", "m", "ow", "th", "w", "e", "n", "y", "oo", "k", "o", "f", "l", "ow", "d", "l", "ee"] },
                { word: "The moon reflects sunlight during the dark night", phonemes: ["th", "e", "m", "oo", "n", "r", "e", "f", "l", "e", "k", "t", "s", "s", "a", "n", "l", "ay", "t", "d", "u", "r", "i", "ng", "th", "e", "d", "a", "r", "k", "n", "ay", "t"] },
                { word: "The airplane flew over the high snowy mountains", phonemes: ["th", "e", "e", "r", "p", "l", "ay", "n", "f", "l", "oo", "o", "v", "e", "r", "th", "e", "h", "ay", "s", "n", "o", "ee", "m", "ow", "n", "t", "i", "n", "z"] },
                { word: "We should keep our surroundings clean and green", phonemes: ["w", "ee", "sh", "u", "d", "k", "ee", "p", "ow", "r", "s", "a", "r", "ow", "n", "d", "i", "ng", "z", "k", "l", "ee", "n", "a", "n", "d", "g", "r", "ee", "n"] },
                { word: "Magnets can pull some objects that have iron", phonemes: ["m", "a", "g", "n", "e", "t", "s", "k", "a", "n", "p", "u", "l", "s", "a", "m", "o", "b", "j", "e", "k", "t", "s", "th", "a", "t", "h", "a", "v", "ay", "e", "r", "n"] },
                { word: "Sound travels through air and water and solids", phonemes: ["s", "ow", "n", "d", "t", "r", "a", "v", "e", "l", "z", "th", "r", "oo", "e", "r", "a", "n", "d", "w", "o", "t", "e", "r", "a", "n", "d", "s", "o", "l", "i", "d", "z"] },
                { word: "Gravity pulls all things down toward the center", phonemes: ["g", "r", "a", "v", "i", "t", "ee", "p", "u", "l", "z", "o", "l", "th", "ee", "ng", "z", "d", "ow", "n", "t", "o", "w", "e", "r", "d", "th", "e", "s", "e", "n", "t", "e", "r"] },
                { word: "The forest is home to many wild animals", phonemes: ["th", "e", "f", "o", "r", "e", "s", "t", "i", "z", "h", "o", "m", "t", "oo", "m", "e", "n", "ee", "w", "ay", "l", "d", "a", "n", "i", "m", "a", "l", "z"] },
                { word: "Light helps us see everything around our world", phonemes: ["l", "ay", "t", "h", "e", "l", "p", "s", "a", "s", "s", "ee", "e", "v", "r", "ee", "th", "ee", "ng", "a", "r", "ow", "n", "d", "ow", "r", "w", "e", "r", "l", "d"] },
                { word: "Friction makes it hard to push heavy things", phonemes: ["f", "r", "i", "k", "sh", "u", "n", "m", "ay", "k", "s", "i", "t", "h", "a", "r", "d", "t", "oo", "p", "u", "sh", "h", "e", "v", "ee", "th", "ee", "ng", "z"] },
                { word: "The compass helps the sailor find the way", phonemes: ["th", "e", "k", "a", "m", "p", "a", "s", "h", "e", "l", "p", "s", "th", "e", "s", "ay", "l", "e", "r", "f", "ay", "n", "d", "th", "e", "w", "ay"] }
            ],
            advanced: [
                { word: "The water cycle includes evaporation and condensation and precipitation", phonemes: ["th", "e", "w", "o", "t", "e", "r", "s", "ay", "k", "e", "l", "i", "n", "k", "l", "oo", "d", "z", "i", "v", "a", "p", "o", "r", "ay", "sh", "u", "n", "a", "n", "d", "k", "o", "n", "d", "e", "n", "s", "ay", "sh", "u", "n", "a", "n", "d", "p", "r", "ee", "s", "i", "p", "i", "t", "ay", "sh", "u", "n"] },
                { word: "Our community helpers include the doctors and nurses and teachers", phonemes: ["ow", "r", "k", "o", "m", "y", "oo", "n", "i", "t", "ee", "h", "e", "l", "p", "e", "r", "z", "i", "n", "k", "l", "oo", "d", "th", "e", "d", "o", "k", "t", "o", "r", "z", "a", "n", "d", "n", "e", "r", "s", "e", "z", "a", "n", "d", "t", "ee", "ch", "e", "r", "z"] },
                { word: "We use our five senses to explore the world around", phonemes: ["w", "ee", "y", "oo", "z", "ow", "r", "f", "ay", "v", "s", "e", "n", "s", "e", "z", "t", "oo", "i", "k", "s", "p", "l", "o", "r", "th", "e", "w", "e", "r", "l", "d", "a", "r", "ow", "n", "d"] },
                { word: "Trees are important because they give us fresh clean air", phonemes: ["t", "r", "ee", "z", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "b", "i", "k", "o", "z", "th", "ay", "g", "i", "v", "a", "s", "f", "r", "e", "sh", "k", "l", "ee", "n", "e", "r"] },
                { word: "The heart and lungs work together to give us oxygen", phonemes: ["th", "e", "h", "a", "r", "t", "a", "n", "d", "l", "a", "ng", "z", "w", "e", "r", "k", "t", "oo", "g", "e", "th", "e", "r", "t", "oo", "g", "i", "v", "a", "s", "o", "k", "s", "i", "j", "e", "n"] },
                { word: "Computers allow us to send emails to people far away", phonemes: ["k", "o", "m", "p", "y", "oo", "t", "e", "r", "z", "a", "l", "ow", "a", "s", "t", "oo", "s", "e", "n", "d", "ee", "m", "ay", "l", "z", "t", "oo", "p", "ee", "p", "e", "l", "f", "a", "r", "a", "w", "ay"] },
                { word: "The Philippines is rich in natural beauty and many resources", phonemes: ["th", "e", "f", "i", "l", "i", "p", "ee", "n", "z", "i", "z", "r", "i", "ch", "i", "n", "n", "a", "ch", "u", "r", "a", "l", "b", "y", "oo", "t", "ee", "a", "n", "d", "m", "e", "n", "ee", "r", "ee", "s", "o", "r", "s", "e", "z"] },
                { word: "Honesty and respect are important values for every young child", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "a", "n", "d", "r", "e", "s", "p", "e", "k", "t", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "v", "a", "l", "y", "oo", "z", "f", "o", "r", "e", "v", "r", "ee", "y", "a", "ng", "ch", "ay", "l", "d"] },
                { word: "The butterfly went through four stages of very slow metamorphosis", phonemes: ["th", "e", "b", "a", "t", "e", "r", "f", "l", "ay", "w", "e", "n", "t", "th", "r", "oo", "f", "o", "r", "s", "t", "ay", "j", "e", "z", "o", "v", "v", "e", "r", "ee", "s", "l", "o", "m", "e", "t", "a", "m", "o", "r", "f", "o", "s", "i", "s"] },
                { word: "Always listen carefully when the teacher is explaining the lesson", phonemes: ["o", "l", "w", "ay", "z", "l", "i", "s", "e", "n", "k", "e", "r", "f", "u", "l", "ee", "w", "e", "n", "th", "e", "t", "ee", "ch", "e", "r", "i", "z", "i", "k", "s", "p", "l", "ay", "n", "i", "ng", "th", "e", "l", "e", "s", "o", "n"] },
                { word: "Eating a balanced diet helps prevent many kinds of sickness", phonemes: ["ee", "t", "i", "ng", "a", "b", "a", "l", "a", "n", "s", "t", "d", "ay", "e", "t", "h", "e", "l", "p", "s", "p", "r", "ee", "v", "e", "n", "t", "m", "e", "n", "ee", "k", "ay", "n", "d", "z", "o", "v", "s", "i", "k", "n", "e", "s"] },
                { word: "The electricity provides energy to run our modern home appliances", phonemes: ["th", "e", "i", "l", "e", "k", "t", "r", "i", "s", "i", "t", "ee", "p", "r", "o", "v", "ay", "d", "z", "e", "n", "e", "r", "j", "ee", "t", "oo", "r", "a", "n", "ow", "r", "m", "o", "d", "e", "r", "n", "h", "o", "m", "a", "p", "l", "ay", "a", "n", "s", "e", "z"] },
                { word: "We should conserve water by turning off the faucet tightly", phonemes: ["w", "ee", "sh", "u", "d", "k", "o", "n", "s", "e", "r", "v", "w", "o", "t", "e", "r", "b", "ay", "t", "e", "r", "n", "i", "ng", "o", "f", "th", "e", "f", "o", "s", "e", "t", "t", "ay", "t", "l", "ee"] },
                { word: "Volcanoes can be dangerous when they erupt with hot lava", phonemes: ["v", "o", "l", "k", "ay", "n", "o", "z", "k", "a", "n", "b", "ee", "d", "ay", "n", "j", "e", "r", "u", "s", "w", "e", "n", "th", "ay", "i", "r", "a", "p", "t", "w", "i", "th", "h", "o", "t", "l", "a", "v", "a"] },
                { word: "Plants use their green leaves to capture energy from sunlight", phonemes: ["p", "l", "a", "n", "t", "s", "y", "oo", "z", "th", "e", "r", "g", "r", "ee", "n", "l", "ee", "v", "z", "t", "oo", "k", "a", "p", "ch", "e", "r", "e", "n", "e", "r", "j", "ee", "f", "r", "o", "m", "s", "a", "n", "l", "ay", "t"] },
                { word: "The national hero Jose Rizal wrote books to inspire Filipinos", phonemes: ["th", "e", "n", "a", "sh", "u", "n", "a", "l", "h", "ee", "r", "o", "h", "o", "z", "ay", "r", "i", "z", "a", "l", "r", "o", "t", "b", "u", "k", "s", "t", "oo", "i", "n", "s", "p", "ay", "r", "f", "i", "l", "i", "p", "ee", "n", "o", "z"] },
                { word: "Recycling helps to reduce the amount of waste in landfills", phonemes: ["r", "ee", "s", "ay", "k", "l", "i", "ng", "h", "e", "l", "p", "s", "t", "oo", "r", "ee", "d", "y", "oo", "s", "th", "e", "a", "m", "ow", "n", "t", "o", "v", "w", "ay", "s", "t", "i", "n", "l", "a", "n", "d", "f", "i", "l", "z"] },
                { word: "Exercise and enough sleep are important for a growing child", phonemes: ["e", "k", "s", "e", "r", "s", "ay", "z", "a", "n", "d", "i", "n", "a", "f", "s", "l", "ee", "p", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "f", "o", "r", "a", "g", "r", "o", "i", "ng", "ch", "ay", "l", "d"] },
                { word: "Our teeth need regular brushing to prevent cavities and decay", phonemes: ["ow", "r", "t", "ee", "th", "n", "ee", "d", "r", "e", "g", "y", "oo", "l", "e", "r", "b", "r", "a", "sh", "i", "ng", "t", "oo", "p", "r", "ee", "v", "e", "n", "t", "k", "a", "v", "i", "t", "ee", "z", "a", "n", "d", "d", "i", "k", "ay"] },
                { word: "Clouds are made of millions of tiny drops of water", phonemes: ["k", "l", "ow", "d", "z", "a", "r", "m", "ay", "d", "o", "v", "m", "i", "l", "y", "u", "n", "z", "o", "v", "t", "ay", "n", "ee", "d", "r", "o", "p", "s", "o", "v", "w", "o", "t", "e", "r"] },
                { word: "Shadows are formed when an object blocks the path of light", phonemes: ["sh", "a", "d", "o", "z", "a", "r", "f", "o", "r", "m", "d", "w", "e", "n", "a", "n", "o", "b", "j", "e", "k", "t", "b", "l", "o", "k", "s", "th", "e", "p", "a", "th", "o", "v", "l", "ay", "t"] },
                { word: "The force of gravity keeps us from floating into outer space", phonemes: ["th", "e", "f", "o", "r", "s", "o", "v", "g", "r", "a", "v", "i", "t", "ee", "k", "ee", "p", "s", "a", "s", "f", "r", "o", "m", "f", "l", "o", "t", "i", "ng", "i", "n", "t", "oo", "ow", "t", "e", "r", "s", "p", "ay", "s"] },
                { word: "Loud noises can damage our ears so we must be careful", phonemes: ["l", "ow", "d", "n", "oy", "z", "e", "z", "k", "a", "n", "d", "a", "m", "i", "j", "ow", "r", "e", "r", "z", "s", "o", "w", "ee", "m", "a", "s", "t", "b", "ee", "k", "e", "r", "f", "u", "l"] },
                { word: "Oxygen is the gas that humans need to breathe to live", phonemes: ["o", "k", "s", "i", "j", "e", "n", "i", "z", "th", "e", "g", "a", "s", "th", "a", "t", "h", "y", "oo", "m", "e", "n", "z", "n", "ee", "d", "t", "oo", "b", "r", "ee", "th", "t", "oo", "l", "i", "v"] },
                { word: "Fossils are remains of plants and animals from a long time", phonemes: ["f", "o", "s", "e", "l", "z", "a", "r", "r", "ee", "m", "ay", "n", "z", "o", "v", "p", "l", "a", "n", "t", "s", "a", "n", "d", "a", "n", "i", "m", "a", "l", "z", "f", "r", "o", "m", "a", "l", "o", "ng", "t", "ay", "m"] },
                { word: "The equator is an imaginary line around the middle of Earth", phonemes: ["th", "e", "ee", "k", "w", "ay", "t", "e", "r", "i", "z", "a", "n", "i", "m", "a", "j", "i", "n", "e", "r", "ee", "l", "ay", "n", "a", "r", "ow", "n", "d", "th", "e", "m", "i", "d", "e", "l", "o", "v", "e", "r", "th"] },
                { word: "Respecting our elders is a beautiful part of our Filipino culture", phonemes: ["r", "e", "s", "p", "e", "k", "t", "i", "ng", "ow", "r", "e", "l", "d", "e", "r", "z", "i", "z", "a", "b", "y", "oo", "t", "i", "f", "u", "l", "p", "a", "r", "t", "o", "v", "ow", "r", "f", "i", "l", "i", "p", "ee", "n", "o", "k", "a", "l", "ch", "e", "r"] },
                { word: "The teacher told us to prepare for our group project presentation", phonemes: ["th", "e", "t", "ee", "ch", "e", "r", "t", "o", "l", "d", "a", "s", "t", "oo", "p", "r", "ee", "p", "e", "r", "f", "o", "r", "ow", "r", "g", "r", "oo", "p", "p", "r", "o", "j", "e", "k", "t", "p", "r", "e", "z", "e", "n", "t", "ay", "sh", "u", "n"] },
                { word: "Magnets have a north pole and a south pole on each", phonemes: ["m", "a", "g", "n", "e", "t", "s", "h", "a", "v", "a", "n", "o", "r", "th", "p", "o", "l", "a", "n", "d", "a", "s", "ow", "th", "p", "o", "l", "o", "n", "ee", "ch"] },
                { word: "Using reusable bags helps reduce the waste in our beautiful environment", phonemes: ["y", "oo", "z", "i", "ng", "r", "ee", "y", "oo", "z", "a", "b", "e", "l", "b", "a", "g", "z", "h", "e", "l", "p", "s", "r", "ee", "d", "y", "oo", "s", "th", "e", "w", "ay", "s", "t", "i", "n", "ow", "r", "b", "y", "oo", "t", "i", "f", "u", "l", "e", "n", "v", "ay", "r", "o", "n", "m", "e", "n", "t"] },
                { word: "The nervous system controls all the actions of our human body", phonemes: ["th", "e", "n", "e", "r", "v", "u", "s", "s", "i", "s", "t", "e", "m", "k", "o", "n", "t", "r", "o", "l", "z", "o", "l", "th", "e", "a", "k", "sh", "u", "n", "z", "o", "v", "ow", "r", "h", "y", "oo", "m", "e", "n", "b", "o", "d", "ee"] },
                { word: "Proper waste disposal is very important to prevent the spread disease", phonemes: ["p", "r", "o", "p", "e", "r", "w", "ay", "s", "t", "d", "i", "s", "p", "o", "z", "a", "l", "i", "z", "v", "e", "r", "ee", "i", "m", "p", "o", "r", "t", "a", "n", "t", "t", "oo", "p", "r", "ee", "v", "e", "n", "t", "th", "e", "s", "p", "r", "e", "d", "d", "i", "z", "ee", "z"] },
                { word: "The internet helps us learn many new and very exciting things", phonemes: ["th", "e", "i", "n", "t", "e", "r", "n", "e", "t", "h", "e", "l", "p", "s", "a", "s", "l", "e", "r", "n", "m", "e", "n", "ee", "n", "y", "oo", "a", "n", "d", "v", "e", "r", "ee", "i", "k", "s", "ay", "t", "i", "ng", "th", "ee", "ng", "z"] },
                { word: "The farmer used a large tractor to plow the muddy field", phonemes: ["th", "e", "f", "a", "r", "m", "e", "r", "y", "oo", "z", "d", "a", "l", "a", "r", "j", "t", "r", "a", "k", "t", "e", "r", "t", "oo", "p", "l", "ow", "th", "e", "m", "a", "d", "ee", "f", "ee", "l", "d"] },
                { word: "We should eat a variety of foods to get all nutrients", phonemes: ["w", "ee", "sh", "u", "d", "ee", "t", "a", "v", "e", "r", "ay", "e", "t", "ee", "o", "v", "f", "oo", "d", "z", "t", "oo", "g", "e", "t", "o", "l", "n", "y", "oo", "t", "r", "ee", "e", "n", "t", "s"] },
                { word: "The scientist used a microscope to see the tiny living organisms", phonemes: ["th", "e", "s", "ay", "e", "n", "t", "i", "s", "t", "y", "oo", "z", "d", "a", "m", "ay", "k", "r", "o", "s", "k", "o", "p", "t", "oo", "s", "ee", "th", "e", "t", "ay", "n", "ee", "l", "i", "v", "i", "ng", "o", "r", "g", "a", "n", "i", "z", "m", "z"] },
                { word: "Honesty is telling the truth even when it is very difficult", phonemes: ["h", "o", "n", "e", "s", "t", "ee", "i", "z", "t", "e", "l", "i", "ng", "th", "e", "t", "r", "oo", "th", "ee", "v", "e", "n", "w", "e", "n", "i", "t", "i", "z", "v", "e", "r", "ee", "d", "i", "f", "i", "k", "a", "l", "t"] },
                { word: "The national anthem is sung with great pride by all Filipinos", phonemes: ["th", "e", "n", "a", "sh", "u", "n", "a", "l", "a", "n", "th", "e", "m", "i", "z", "s", "a", "ng", "w", "i", "th", "g", "r", "ay", "t", "p", "r", "ay", "d", "b", "ay", "o", "l", "f", "i", "l", "i", "p", "ee", "n", "o", "z"] },
                { word: "Proper hygiene includes bathing every day and washing your messy hair", phonemes: ["p", "r", "o", "p", "e", "r", "h", "ay", "j", "ee", "n", "i", "n", "k", "l", "oo", "d", "z", "b", "ay", "th", "i", "ng", "e", "v", "r", "ee", "d", "ay", "a", "n", "d", "w", "o", "sh", "i", "ng", "y", "o", "r", "m", "e", "s", "ee", "h", "e", "r"] },
                { word: "The computer keyboard is used to type letters and many numbers", phonemes: ["th", "e", "k", "o", "m", "p", "y", "oo", "t", "e", "r", "k", "ee", "b", "o", "r", "d", "i", "z", "y", "oo", "z", "d", "t", "oo", "t", "ay", "p", "l", "e", "t", "e", "r", "z", "a", "n", "d", "m", "e", "n", "ee", "n", "a", "m", "b", "e", "r", "z"] },
                { word: "A healthy community has clean water and safe parks for children", phonemes: ["a", "h", "e", "l", "th", "ee", "k", "o", "m", "y", "oo", "n", "i", "t", "ee", "h", "a", "z", "k", "l", "ee", "n", "w", "o", "t", "e", "r", "a", "n", "d", "s", "ay", "f", "p", "a", "r", "k", "s", "f", "o", "r", "ch", "i", "l", "d", "r", "e", "n"] },
                { word: "Always say thank you when someone gives you a nice gift", phonemes: ["o", "l", "w", "ay", "z", "s", "ay", "th", "a", "ng", "k", "y", "oo", "w", "e", "n", "s", "a", "m", "w", "a", "n", "g", "i", "v", "z", "y", "oo", "a", "n", "ay", "s", "g", "i", "f", "t"] },
                { word: "The forest fire was put out by the brave local firemen", phonemes: ["th", "e", "f", "o", "r", "e", "s", "t", "f", "ay", "r", "w", "o", "z", "p", "u", "t", "ow", "t", "b", "ay", "th", "e", "b", "r", "ay", "v", "l", "o", "k", "a", "l", "f", "ay", "r", "m", "e", "n"] },
                { word: "Reading aloud helps us practice our pronunciation of new English words", phonemes: ["r", "ee", "d", "i", "ng", "a", "l", "ow", "d", "h", "e", "l", "p", "s", "a", "s", "p", "r", "a", "k", "t", "i", "s", "ow", "r", "p", "r", "o", "n", "a", "n", "s", "ee", "ay", "sh", "u", "n", "o", "v", "n", "y", "oo", "i", "ng", "g", "l", "i", "sh", "w", "e", "r", "d", "z"] },
                { word: "We should plant more trees to make our world much greener", phonemes: ["w", "ee", "sh", "u", "d", "p", "l", "a", "n", "t", "m", "o", "r", "t", "r", "ee", "z", "t", "oo", "m", "ay", "k", "ow", "r", "w", "e", "r", "l", "d", "m", "a", "ch", "g", "r", "ee", "n", "e", "r"] },
                { word: "The sun is a star that gives light to the planets", phonemes: ["th", "e", "s", "a", "n", "i", "z", "a", "s", "t", "a", "r", "th", "a", "t", "g", "i", "v", "z", "l", "ay", "t", "t", "oo", "th", "e", "p", "l", "a", "n", "e", "t", "s"] },
                { word: "Bees are important insects because they help the flowers to grow", phonemes: ["b", "ee", "z", "a", "r", "i", "m", "p", "o", "r", "t", "a", "n", "t", "i", "n", "s", "e", "k", "t", "s", "b", "i", "k", "o", "z", "th", "ay", "h", "e", "l", "p", "th", "e", "f", "l", "ow", "e", "r", "z", "t", "oo", "g", "r", "o"] },
                { word: "Our parents work very hard to provide for our family needs", phonemes: ["ow", "r", "p", "e", "r", "e", "n", "t", "s", "w", "e", "r", "k", "v", "e", "r", "ee", "h", "a", "r", "d", "t", "oo", "p", "r", "o", "v", "ay", "d", "f", "o", "r", "ow", "r", "f", "a", "m", "i", "l", "ee", "n", "ee", "d", "z"] },
                { word: "Always brush your teeth before going to sleep at the night", phonemes: ["o", "l", "w", "ay", "z", "b", "r", "a", "sh", "y", "o", "r", "t", "ee", "th", "b", "e", "f", "o", "r", "g", "o", "i", "ng", "t", "oo", "s", "l", "ee", "p", "a", "t", "th", "e", "n", "ay", "t"] },
                { word: "The ocean is home to many different kinds of colorful fish", phonemes: ["th", "e", "o", "sh", "u", "n", "i", "z", "h", "o", "m", "t", "oo", "m", "e", "n", "ee", "d", "i", "f", "e", "r", "e", "n", "t", "k", "ay", "n", "d", "z", "o", "v", "k", "a", "l", "e", "r", "f", "u", "l", "f", "i", "sh"] }
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