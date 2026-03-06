<?php
session_start();
require_once('../config/database.php');

// --- FIXED LOGIC: Multi-Role Session Security ---
// Gagamit tayo ng 'student_logged_in' para kahit i-refresh, hindi madi-disturbo ng teacher session
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true || $_SESSION['grade'] != '2') {
    header("Location: ../login.php");
    exit();
}

// Siguraduhin na ang variables ay tumutugma sa login.php
$student_id = $_SESSION['student_id'];
$username = $_SESSION['fullname'];
// ----------------------------------------------

$play_welcome_voice = true;

// Logic para sa One-time Pop-up kada log-in
$show_parental_note = false;
if (!isset($_SESSION['note_shown_grade1'])) {
    $show_parental_note = true;
    $_SESSION['note_shown_grade1'] = true;
}

// Fetch Word Library for Images
$manualWords = ["sun", "moon", "star", "rain", "tree", "bird", "fish", "cat", "dog", "cow"];
$imageLibrary = [];
foreach ($manualWords as $word) {
    $wordLower = strtolower($word);
    $localPath = "../upload/" . $wordLower . ".jpg";
    $imageLibrary[$wordLower] = file_exists($localPath) ? $localPath . "?v=" . filemtime($localPath) : "https://via.placeholder.com/400?text=" . $wordLower;
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
        #feedbackMessage { font-size: 1.8rem; font-weight: 800; padding: 15px; border-radius: 20px; line-height: 1.3; text-align: center; width: 95%; margin: 8px auto; background: #FFFFFF; border: 3px solid #DFE4EA; color: #2F3542; min-height: 60px; display: flex; align-items: center; justify-content: center; }
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
        body.dark-mode { background: #2F3542; color: white; }
        body.dark-mode .main-container { background: #57606F; border-color: #2F3542; }
        body.dark-mode .fa-star { color: #57606F; }
    </style>
</head>
<body>
<header class="page-header">
    <span id="usernameDisplay">Hello, <?php echo htmlspecialchars($username); ?>!</span>
    <button id="logoutBtn" onclick="window.location.href='../login.php';"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
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
                <option value="intermediate">👍 Intermediate (Medium Words)</option>
                <option value="advanced">🧠 Advanced (Hard Words)</option>
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

        const wordBank = {
            beginner: [
                { word: "cat", phonemes: ["k", "a", "t"], example: "The cat is on the mat." },
                { word: "dog", phonemes: ["d", "o", "g"], example: "My dog can bark loud." },
                { word: "sun", phonemes: ["s", "u", "n"], example: "The sun is very bright." },
                { word: "pig", phonemes: ["p", "i", "g"], example: "The pig lives on the farm." },
                { word: "ten", phonemes: ["t", "e", "n"], example: "I have ten colorful pens." },
                { word: "bat", phonemes: ["b", "a", "t"], example: "He hits the ball with a bat." },
                { word: "cup", phonemes: ["k", "u", "p"], example: "I drink water from a cup." },
                { word: "net", phonemes: ["n", "e", "t"], example: "The butterfly is in the net." },
                { word: "bin", phonemes: ["b", "i", "n"], example: "Put the paper in the bin." },
                { word: "hop", phonemes: ["h", "o", "p"], example: "I can hop like a rabbit." },
                { word: "map", phonemes: ["m", "a", "p"], example: "Follow the treasure map." },
                { word: "jet", phonemes: ["j", "e", "t"], example: "The jet flies in the sky." },
                { word: "lid", phonemes: ["l", "i", "d"], example: "Put the lid on the jar." },
                { word: "mop", phonemes: ["m", "o", "p"], example: "Help Mom mop the floor." },
                { word: "bug", phonemes: ["b", "u", "g"], example: "A tiny bug is on the leaf." },
                { word: "van", phonemes: ["v", "a", "n"], example: "We go to school in a van." },
                { word: "wet", phonemes: ["w", "e", "t"], example: "My hair is wet from the rain." },
                { word: "dig", phonemes: ["d", "i", "g"], example: "I dig a hole for the seed." },
                { word: "box", phonemes: ["b", "o", "ks"], example: "The toys are in the box." },
                { word: "hut", phonemes: ["h", "u", "t"], example: "They live in a small hut." },
                { word: "fan", phonemes: ["f", "a", "n"], example: "Turn on the electric fan." },
                { word: "leg", phonemes: ["l", "e", "g"], example: "An insect has six legs." },
                { word: "pin", phonemes: ["p", "i", "n"], example: "The pin is very sharp." },
                { word: "hot", phonemes: ["h", "o", "t"], example: "The soup is too hot." },
                { word: "run", phonemes: ["r", "u", "n"], example: "Run to the finish line!" },
                { word: "jam", phonemes: ["j", "a", "m"], example: "I like bread with jam." },
                { word: "bed", phonemes: ["b", "e", "d"], example: "Make your bed every morning." },
                { word: "zip", phonemes: ["z", "i", "p"], example: "Zip up your jacket." },
                { word: "pot", phonemes: ["p", "o", "t"], example: "Mom cooks in a big pot." },
                { word: "tub", phonemes: ["t", "u", "b"], example: "The baby is in the tub." },
                { word: "bag", phonemes: ["b", "a", "g"], example: "Carry your school bag." },
                { word: "hen", phonemes: ["h", "e", "n"], example: "The hen laid an egg." },
                { word: "sit", phonemes: ["s", "i", "t"], example: "Sit down on the chair." },
                { word: "log", phonemes: ["l", "o", "g"], example: "The turtle sits on a log." },
                { word: "gum", phonemes: ["g", "u", "m"], example: "Do not swallow your gum." },
                { word: "cap", phonemes: ["k", "a", "p"], example: "The boy wore a blue cap." },
                { word: "red", phonemes: ["r", "e", "d"], example: "Apples are usually red." },
                { word: "six", phonemes: ["s", "i", "ks"], example: "A cube has six sides." },
                { word: "top", phonemes: ["t", "o", "p"], example: "The bird is on top of the tree." },
                { word: "mud", phonemes: ["m", "u", "d"], example: "The boots are covered in mud." },
                { word: "rat", phonemes: ["r", "a", "t"], example: "The rat ran into the hole." },
                { word: "pen", phonemes: ["p", "e", "n"], example: "Use a pen to write." },
                { word: "wig", phonemes: ["w", "i", "g"], example: "The clown wears a wig." },
                { word: "rod", phonemes: ["r", "o", "d"], example: "He uses a fishing rod." },
                { word: "nut", phonemes: ["n", "u", "t"], example: "The squirrel eats a nut." },
                { word: "sad", phonemes: ["s", "a", "d"], example: "She is sad because she lost her toy." },
                { word: "fit", phonemes: ["f", "i", "t"], example: "These shoes fit me well." },
                { word: "fox", phonemes: ["f", "o", "ks"], example: "The fox has a bushy tail." },
                { word: "bus", phonemes: ["b", "u", "s"], example: "I ride the yellow bus." },
                { word: "hat", phonemes: ["h", "a", "t"], example: "Put on your hat." },
                { word: "ant", phonemes: ["a", "n", "t"], example: "The ant is carrying food." },
                { word: "egg", phonemes: ["e", "gg"], example: "Birds hatch from an egg." },
                { word: "ink", phonemes: ["i", "n", "k"], example: "The pen ran out of ink." },
                { word: "owl", phonemes: ["ow", "l"], example: "An owl wakes up at night." },
                { word: "up", phonemes: ["u", "p"], example: "Look up at the sky." },
                { word: "yak", phonemes: ["y", "a", "k"], example: "The yak has long hair." },
                { word: "dad", phonemes: ["d", "a", "d"], example: "My dad is a hero." },
                { word: "mom", phonemes: ["m", "o", "m"], example: "I love my mom." },
                { word: "win", phonemes: ["w", "i", "n"], example: "I want to win the game." },
                { word: "tax", phonemes: ["t", "a", "ks"], example: "Adults pay a tax." },
                { word: "rob", phonemes: ["r", "o", "b"], example: "The thief tried to rob the store." },
                { word: "mad", phonemes: ["m", "a", "d"], example: "Don't be mad at me." },
                { word: "get", phonemes: ["g", "e", "t"], example: "Go get your umbrella." },
                { word: "hit", phonemes: ["h", "i", "t"], example: "Hit the ball hard." },
                { word: "job", phonemes: ["j", "o", "b"], example: "He has a busy job." },
                { word: "cut", phonemes: ["k", "u", "t"], example: "Cut the paper with scissors." },
                { word: "fog", phonemes: ["f", "o", "g"], example: "It is hard to see in the fog." },
                { word: "bad", phonemes: ["b", "a", "d"], example: "Eating too much candy is bad." },
                { word: "let", phonemes: ["l", "e", "t"], example: "Let the bird fly away." },
                { word: "mix", phonemes: ["m", "i", "ks"], example: "Mix the colors together." },
                { word: "nod", phonemes: ["n", "o", "d"], example: "Nod your head if you agree." },
                { word: "rug", phonemes: ["r", "u", "g"], example: "The cat is on the rug." },
                { word: "set", phonemes: ["s", "e", "t"], example: "Set the table for dinner." },
                { word: "tip", phonemes: ["t", "i", "p"], example: "The tip of my pencil broke." },
                { word: "wet", phonemes: ["w", "e", "t"], example: "I got wet in the rain." },
                { word: "yam", phonemes: ["y", "a", "m"], example: "A yam is a sweet potato." },
                { word: "box", phonemes: ["b", "o", "ks"], example: "A big cardboard box." },
                { word: "zip", phonemes: ["z", "i", "p"], example: "Can you zip your bag?" },
                { word: "gas", phonemes: ["g", "a", "s"], example: "Cars need gas to run." },
                { word: "hen", phonemes: ["h", "e", "n"], example: "The hen clucks." },
                { word: "kid", phonemes: ["k", "i", "d"], example: "The kid is playing." },
                { word: "lot", phonemes: ["l", "o", "t"], example: "I have a lot of toys." },
                { word: "pan", phonemes: ["p", "a", "n"], example: "Fry the egg in the pan." },
                { word: "rim", phonemes: ["r", "i", "m"], example: "The rim of the glass." },
                { word: "sub", phonemes: ["s", "u", "b"], example: "A sub dives deep." },
                { word: "tag", phonemes: ["t", "a", "g"], example: "Let's play a game of tag." },
                { word: "wax", phonemes: ["w", "a", "ks"], example: "Candles are made of wax." },
                { word: "yes", phonemes: ["y", "e", "s"], example: "Say yes to your friends." },
                { word: "cob", phonemes: ["c", "o", "b"], example: "Corn on the cob." },
                { word: "den", phonemes: ["d", "e", "n"], example: "The lion is in its den." },
                { word: "fin", phonemes: ["f", "i", "n"], example: "A shark has a big fin." },
                { word: "hug", phonemes: ["h", "u", "g"], example: "Give your mom a hug." },
                { word: "lab", phonemes: ["l", "a", "b"], example: "Scientists work in a lab." },
                { word: "men", phonemes: ["m", "e", "n"], example: "Three men are talking." },
                { word: "nap", phonemes: ["n", "a", "p"], example: "Take a nap in the afternoon." },
                { word: "pad", phonemes: ["p", "a", "d"], example: "Write on the paper pad." },
                { word: "rub", phonemes: ["r", "u", "b"], example: "Rub your hands together." },
                { word: "tan", phonemes: ["t", "a", "n"], example: "Her skin is tan." },
                { word: "vet", phonemes: ["v", "e", "t"], example: "The vet helps sick animals." },
                { word: "win", phonemes: ["w", "i", "n"], example: "You can win a prize." }
            ],
            intermediate: [
                { word: "frog", phonemes: ["f", "r", "o", "g"], example: "The frog is green and wet." },
                { word: "star", phonemes: ["s", "t", "a", "r"], example: "The star shines at night." },
                { word: "ship", phonemes: ["sh", "i", "p"], example: "A ship sails on the sea." },
                { word: "tree", phonemes: ["t", "r", "ee"], example: "The mango tree is tall." },
                { word: "fish", phonemes: ["f", "i", "sh"], example: "A fish can breathe underwater." },
                { word: "clock", phonemes: ["k", "l", "o", "k"], example: "Check the clock for the time." },
                { word: "brush", phonemes: ["b", "r", "u", "sh"], example: "Brush your teeth daily." },
                { word: "plant", phonemes: ["p", "l", "a", "n", "t"], example: "The plant grows in the soil." },
                { word: "chair", phonemes: ["ch", "ai", "r"], example: "Pull up a chair." },
                { word: "bread", phonemes: ["b", "r", "ea", "d"], example: "I like toasted bread." },
                { word: "smile", phonemes: ["s", "m", "i", "l", "e"], example: "You have a pretty smile." },
                { word: "grass", phonemes: ["g", "r", "a", "ss"], example: "Do not walk on the grass." },
                { word: "shell", phonemes: ["sh", "e", "ll"], example: "I found a shell on the beach." },
                { word: "truck", phonemes: ["t", "r", "u", "k"], example: "The truck is full of sand." },
                { word: "spoon", phonemes: ["s", "p", "oo", "n"], example: "Eat your soup with a spoon." },
                { word: "flag", phonemes: ["f", "l", "a", "g"], example: "Our flag has three stars." },
                { word: "cloud", phonemes: ["k", "l", "ou", "d"], example: "The cloud looks like a sheep." },
                { word: "shoes", phonemes: ["sh", "oe", "s"], example: "Tie your shoes tightly." },
                { word: "dress", phonemes: ["d", "r", "e", "ss"], example: "She wore a new dress." },
                { word: "light", phonemes: ["l", "igh", "t"], example: "Turn off the light." },
                { word: "black", phonemes: ["b", "l", "a", "k"], example: "The chalkboard is black." },
                { word: "sweet", phonemes: ["s", "w", "ee", "t"], example: "Candy is very sweet." },
                { word: "train", phonemes: ["t", "r", "ai", "n"], example: "The train moves on tracks." },
                { word: "bench", phonemes: ["b", "e", "n", "ch"], example: "Sit on the park bench." },
                { word: "grape", phonemes: ["g", "r", "a", "p", "e"], example: "I want a bunch of grapes." },
                { word: "phone", phonemes: ["ph", "o", "n", "e"], example: "The phone is ringing." },
                { word: "snake", phonemes: ["s", "n", "a", "k", "e"], example: "The snake is very long." },
                { word: "green", phonemes: ["g", "r", "ee", "n"], example: "Leafy vegetables are green." },
                { word: "plate", phonemes: ["p", "l", "a", "t", "e"], example: "Put the cake on the plate." },
                { word: "swing", phonemes: ["s", "w", "i", "ng"], example: "Push me on the swing." },
                { word: "brick", phonemes: ["b", "r", "i", "k"], example: "The wall is made of brick." },
                { word: "whale", phonemes: ["wh", "a", "l", "e"], example: "A whale is a huge animal." },
                { word: "lunch", phonemes: ["l", "u", "n", "ch"], example: "What is for lunch?" },
                { word: "slide", phonemes: ["s", "l", "i", "d", "e"], example: "The slide is fast." },
                { word: "flute", phonemes: ["f", "l", "u", "t", "e"], example: "He plays a silver flute." },
                { word: "shirt", phonemes: ["sh", "ir", "t"], example: "My shirt has buttons." },
                { word: "drink", phonemes: ["d", "r", "i", "n", "k"], example: "Drink your milk." },
                { word: "block", phonemes: ["b", "l", "o", "k"], example: "I built a block tower." },
                { word: "thumb", phonemes: ["th", "u", "m", "b"], example: "I have a sore thumb." },
                { word: "sheep", phonemes: ["sh", "ee", "p"], example: "The sheep has soft wool." },
                { word: "bring", phonemes: ["b", "r", "i", "ng"], example: "Bring your books to class." },
                { word: "crane", phonemes: ["k", "r", "a", "n", "e"], example: "The crane is very tall." },
                { word: "chest", phonemes: ["ch", "e", "s", "t"], example: "The treasure is in the chest." },
                { word: "sleep", phonemes: ["s", "l", "ee", "p"], example: "Go to sleep early." },
                { word: "clown", phonemes: ["k", "l", "ow", "n"], example: "The clown has a red nose." },
                { word: "dream", phonemes: ["d", "r", "ea", "m"], example: "I had a dream about flying." },
                { word: "small", phonemes: ["s", "m", "a", "ll"], example: "The ant is very small." },
                { word: "white", phonemes: ["wh", "i", "t", "e"], example: "Snow is pure white." },
                { word: "stair", phonemes: ["s", "t", "ai", "r"], example: "Walk up the stair carefully." },
                { word: "thump", phonemes: ["th", "u", "m", "p"], example: "The ball fell with a thump." },
                { word: "bread", phonemes: ["b", "r", "ea", "d"], example: "Eat bread for breakfast." },
                { word: "brush", phonemes: ["b", "r", "u", "sh"], example: "Brush your hair." },
                { word: "plane", phonemes: ["p", "l", "a", "n", "e"], example: "The plane is high." },
                { word: "skate", phonemes: ["s", "k", "a", "t", "e"], example: "I like to skate." },
                { word: "store", phonemes: ["s", "t", "o", "r", "e"], example: "Buy milk at the store." },
                { word: "school", phonemes: ["s", "ch", "oo", "l"], example: "I go to school." },
                { word: "spoon", phonemes: ["s", "p", "oo", "n"], example: "Use a silver spoon." },
                { word: "slide", phonemes: ["s", "l", "i", "d", "e"], example: "Go down the slide." },
                { word: "snake", phonemes: ["s", "n", "a", "k", "e"], example: "The snake is green." },
                { word: "sleep", phonemes: ["s", "l", "ee", "p"], example: "Time to sleep now." },
                { word: "smoke", phonemes: ["s", "m", "o", "k", "e"], example: "Smoke from the fire." },
                { word: "snack", phonemes: ["s", "n", "a", "k"], example: "Eat a healthy snack." },
                { word: "space", phonemes: ["s", "p", "a", "c", "e"], example: "Stars are in space." },
                { word: "speak", phonemes: ["s", "p", "ea", "k"], example: "Please speak loudly." },
                { word: "sport", phonemes: ["s", "p", "o", "r", "t"], example: "Tennis is a sport." },
                { word: "spray", phonemes: ["s", "p", "r", "ay"], example: "Spray the water." },
                { word: "stamp", phonemes: ["s", "t", "a", "m", "p"], example: "Put a stamp on it." },
                { word: "stand", phonemes: ["s", "t", "a", "n", "d"], example: "Stand in a line." },
                { word: "steam", phonemes: ["s", "t", "ea", "m"], example: "Steam is very hot." },
                { word: "stick", phonemes: ["s", "t", "i", "k"], example: "Pick up the stick." },
                { word: "stone", phonemes: ["s", "t", "o", "n", "e"], example: "Throw a small stone." },
                { word: "storm", phonemes: ["s", "t", "o", "r", "m"], example: "A storm is coming." },
                { word: "story", phonemes: ["s", "t", "o", "r", "y"], example: "Read me a story." },
                { word: "stove", phonemes: ["s", "t", "o", "v", "e"], example: "Cook on the stove." },
                { word: "sweet", phonemes: ["s", "w", "ee", "t"], example: "Sugar is sweet." },
                { word: "swing", phonemes: ["s", "w", "i", "ng"], example: "I love the swing." },
                { word: "table", phonemes: ["t", "a", "b", "l", "e"], example: "Sit at the table." },
                { word: "thank", phonemes: ["th", "a", "n", "k"], example: "Say thank you." },
                { word: "think", phonemes: ["th", "i", "n", "k"], example: "Think before you act." },
                { word: "three", phonemes: ["th", "r", "ee"], example: "One, two, three." },
                { word: "throw", phonemes: ["th", "r", "ow"], example: "Throw the ball." },
                { word: "tiger", phonemes: ["t", "i", "g", "er"], example: "The tiger is fast." },
                { word: "toast", phonemes: ["t", "oa", "s", "t"], example: "I want some toast." },
                { word: "today", phonemes: ["t", "o", "d", "ay"], example: "Today is Monday." },
                { word: "tooth", phonemes: ["t", "oo", "th"], example: "I lost a tooth." },
                { word: "torch", phonemes: ["t", "o", "r", "ch"], example: "Use a torch light." },
                { word: "train", phonemes: ["t", "r", "ai", "n"], example: "Ride on a train." },
                { word: "trash", phonemes: ["t", "r", "a", "sh"], example: "Empty the trash." },
                { word: "treat", phonemes: ["t", "r", "ea", "t"], example: "Trick or treat." },
                { word: "truck", phonemes: ["t", "r", "u", "k"], example: "The truck is big." },
                { word: "trust", phonemes: ["t", "r", "u", "s", "t"], example: "Trust your friend." },
                { word: "under", phonemes: ["u", "n", "d", "er"], example: "Look under the bed." },
                { word: "until", phonemes: ["u", "n", "t", "il"], example: "Wait until noon." },
                { word: "voice", phonemes: ["v", "oi", "c", "e"], example: "Use a soft voice." },
                { word: "watch", phonemes: ["w", "a", "t", "ch"], example: "Watch the movie." },
                { word: "water", phonemes: ["w", "a", "t", "er"], example: "Drink cold water." },
                { word: "whale", phonemes: ["wh", "a", "l", "e"], example: "A whale is big." },
                { word: "wheel", phonemes: ["wh", "ee", "l"], example: "The wheel is round." },
                { word: "where", phonemes: ["wh", "e", "r", "e"], example: "Where are you?" },
                { word: "world", phonemes: ["w", "o", "r", "l", "d"], example: "The world is large." }
            ]
        };

        const emojiPool = [
            '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯',
            '🦁', '🐮', '🐷', '🐸', '🐵', '🐔', '🐧', '🐦', '🐤', '🦆',
            '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋',
            '🐌', '🐞', '🐜', '🦟', '🐢', '🐍', '🐙', '🦑', '🦞', '🦀',
            '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', ' leopards',
            '🌵', '🎄', '🌲', '🌳', '🌴', '🌱', '🌿', '☘️', '🍀', '🎍',
            '🎋', '🍃', '🍂', '🍁', '🍄', '🌾', '💐', '🌷', '🌹', '🥀',
            '🌺', '🌸', '🌼', '🌻', '🌞', '🌝', '🌛', '🌙', '🌚', '🌟',
            '⭐️', '✨', '☄️', '💥', '🔥', '🌪', '🌈', '☀️', '🌤', '⛅️',
            '🍎', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈',
            '🍒', '🍑', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🌽',
            '🥕', '🥔', '🍠', '🥐', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳',
            '🥓', '🥩', '🍗', '🍖', '🌭', '🍔', '🍟', '🍕', '🥪', '🥙',
            '🌮', '🌯', '🥗', '🥘', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱',
            '🥟', '🍤', '🍙', '🍚', '🍘', '🍥', '🍢', '🍡', '🍧', '🍨',
            '🍦', '🥧', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩',
            '🍪', '🌰', '🥜', '🍯', '🥛', '☕️', '🍵', '🥤', '🍶', '🍺',
            '🚗', '🚕', '🚙', '🚌', '🚎', '🏎', '🚓', '🚑', '🚒', '🚐',
            '🚚', '🚛', '🚜', '🛴', '🚲', '🛵', '🏍', '🚨', '🚔', '🚍',
            '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄',
            '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬',
            '🚁', '🛶', '⛵️', '🚤', '🛳', '⛴', '🚢', '🚀', '🛸', '🛰'
        ];

        // --- State Variables ---
        let masteredWords = [];
        let isHardModeUnlocked = false;
        let allProgress = {
            beginner: { word_index: 0, words_attempted: 0, words_correct: 0, shuffledList: [] },
            intermediate: { word_index: 0, words_attempted: 0, words_correct: 0, shuffledList: [] },
            advanced: { word_index: 0, words_attempted: 0, words_correct: 0 }
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

        // --- WELCOME VOICE FUNCTION ---
        function introduceSystem() {
            const introText = `Welcome back to Verbal Practice! To start, look at the word under "The Word to Read". Click the big red microphone button to start speaking. Have fun learning!`;
            speak(introText);
        }

        // --- MEMORY MATCH GAME ---
        function triggerEmojiCheck() {
            matchedPairs = 0; flippedCards = [];
            const gameEmojis = [...emojiPool].sort(() => 0.5 - Math.random()).slice(0, 3);
            const cardValues = [...gameEmojis, ...gameEmojis].sort(() => 0.5 - Math.random());
            Swal.fire({
                title: 'Sentence Master! 🧠',
                html: `<p style="margin-bottom: 10px; font-weight: bold;">Naka 5-stars ka sa mahirap na word! Hanapin ang pares!</p>
<div id="memoryGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; justify-items: center;">
${cardValues.map((emoji, index) => `<div class="memory-card" id="card-${index}" onclick="flipMemoryCard(${index}, '${emoji}')">?</div>`).join('')}</div>`,
                showConfirmButton: false, allowOutsideClick: false, background: '#FFF9EB'
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
                    card.textContent = '?';
                    card.classList.remove('flipped');
                });
            }
            flippedCards = [];
        }

        // --- Helper: Shuffle Function ---
        function shuffleArray(array) {
            let shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }

        // --- UI Element Selectors ---
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
        const progressBarMain = document.getElementById("progressBarMain");

        // --- Parental Gate Function ---
        function showParentalGate() {
            Swal.fire({
                title: 'Parental Guidance 👨‍👩‍👧',
                html: `<div style="text-align: left; font-family: sans-serif; line-height: 1.6; padding: 10px;"><p style="color: #2D3436; font-weight: bold;">Dear Parent,</p><p>Ang goal po natin ay matuto ang inyong anak. <b>Hayaan silang magkamali</b> para malaman ni teacher kung saan sila dapat tulungan.</p><hr style="margin: 15px 0;"><label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; background: #f0f9ff; padding: 10px; border-radius: 10px; border: 1px solid #bae6fd;"><input type="checkbox" id="honestyCheck" style="width: 22px; height: 22px; margin-top: 3px;"><span style="font-size: 0.9rem; color: #0369a1;">Naintindihan ko at hahayaan ang anak ko na mag-practice mag-isa.</span></label></div>`,
                confirmButtonText: 'Start Learning! 🚀',
                confirmButtonColor: '#48DBFB',
                allowOutsideClick: false,
                preConfirm: () => { if (!document.getElementById('honestyCheck').checked) { Swal.showValidationMessage('Pakicheck po ang box para magpatuloy.'); } }
            }).then(() => { loadProgressFromDB(); });
        }

        // --- VOICE LOGIC ---
        function getUsEnglishVoice() {
            if (usEnglishVoice) return usEnglishVoice;
            const voices = speechSynthesis.getVoices();
            let selected = voices.find(v => v.name === 'Google US English') || voices.find(v => v.lang === 'en-US');
            usEnglishVoice = selected;
            return usEnglishVoice;
        }

        function speak(text) {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
            speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            const v = getUsEnglishVoice();
            if (v) { utter.voice = v; utter.lang = v.lang; } else { utter.lang = "en-US"; }
            speechSynthesis.speak(utter);
        }

        // --- MULTIPLE FEEDBACK LOGIC ---
        function speakRating(score) {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;

            const perfect = ["Excellent! You got five stars!", "Amazing! Perfect pronunciation!", "Wow! You sound like a pro!", "Perfect! Keep it up!", "Incredible job! Five stars for you!"];
            const great = ["Great job!", "Good effort!", "You're doing well!", "Nice work!", "Almost perfect, keep going!"];
            const tryAgain = ["Try again!", "Keep practicing!", "You can do it, try once more!", "Don't give up, try again!", "Let's give it another shot!"];

            let message = "";
            if (score === 5) {
                message = perfect[Math.floor(Math.random() * perfect.length)];
            } else if (score >= 4) {
                message = great[Math.floor(Math.random() * great.length)];
            } else {
                message = tryAgain[Math.floor(Math.random() * tryAgain.length)];
            }
            speak(message);
        }

        // --- Visualizer ---
        async function startWaveform() {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
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

        // --- Scoring Logic ---
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

        // --- Game Logic ---
        function loadNextWord() {
            feedbackMessage.textContent = "Practice the word to see the sentence!";
            feedbackMessage.className = "feedback-message bg-initial-feedback";
            playFeedbackBtn.style.display = "none";
            transcriptEl.textContent = "..."; ratingEl.textContent = "0";
            renderStars(0); nextBtn.disabled = true;
            document.getElementById("runningTimer").style.visibility = "hidden";
            document.getElementById("seconds").textContent = "0.0";

            if (currentDifficulty === 'advanced') {
                if (!isHardModeUnlocked) {
                    wordDisplay.textContent = "LOCKED";
                    micBtn.style.opacity = "0.5"; micBtn.style.pointerEvents = "none";
                    playWordBtn.disabled = true; return;
                }
                let p = allProgress[currentDifficulty];
                if (p.word_index >= masteredWords.length) p.word_index = 0;
                const targetKey = masteredWords[p.word_index];
                const allPossible = [...wordBank.beginner, ...wordBank.intermediate];
                let raw = allPossible.find(item => item.word.toLowerCase() === targetKey);
                currentWord = raw ? { word: raw.example, phonemes: ["Sentence Mastery"], example: raw.example, originalWord: raw.word } : allPossible[0];
            } else {
                micBtn.style.opacity = "1"; micBtn.style.pointerEvents = "auto";
                playWordBtn.disabled = false;
                let p = allProgress[currentDifficulty];
                if (!p.shuffledList || p.shuffledList.length === 0 || p.word_index >= p.shuffledList.length) {
                    p.shuffledList = shuffleArray(wordBank[currentDifficulty]); p.word_index = 0;
                }
                currentWord = p.shuffledList[p.word_index];
            }
            wordDisplay.textContent = currentWord.word;
            phonemeDisplay.textContent = currentWord.phonemes.join(" · ");
            const imgKey = (currentWord.originalWord || currentWord.word).toLowerCase();
            document.getElementById("wordImage").src = imageLibrary[imgKey] || "https://via.placeholder.com/120";
            wordAttemptsHistory = []; renderWordHistory(); updateUIProgress();
        }

        function checkPronunciation(spoken) {
            const score = ratePronunciation(spoken, currentWord.word);
            speakRating(score);

            if (currentDifficulty === 'advanced') {
                if (score === 5) {
                    if (!wordAttemptsHistory.includes(5)) {
                        allProgress[currentDifficulty].words_correct++;
                        triggerConfetti();
                    }
                    nextBtn.disabled = false;
                    feedbackMessage.textContent = "⭐ Excellent! " + currentWord.example;
                    feedbackMessage.className = "feedback-message bg-success-feedback";
                    playFeedbackBtn.style.display = "inline-block";
                    setTimeout(() => { triggerEmojiCheck(); }, 2000);
                } else {
                    nextBtn.disabled = true;
                    feedbackMessage.textContent = "Practice more! You need 5 stars to see the sentence.";
                    feedbackMessage.className = "feedback-message bg-initial-feedback";
                    playFeedbackBtn.style.display = "none";
                }
            }
            else {
                if (score === 5) {
                    const key = (currentWord.originalWord || currentWord.word).toLowerCase();
                    if (!masteredWords.includes(key)) masteredWords.push(key);
                    if (!wordAttemptsHistory.includes(5)) {
                        allProgress[currentDifficulty].words_correct++;
                        triggerConfetti();
                    }
                }
                if (score >= 4) {
                    nextBtn.disabled = false;
                    feedbackMessage.textContent = "⭐ " + currentWord.example;
                    feedbackMessage.className = "feedback-message bg-success-feedback";
                    playFeedbackBtn.style.display = "inline-block";
                } else {
                    nextBtn.disabled = true;
                    feedbackMessage.textContent = "Practice the word to see the sentence!";
                    feedbackMessage.className = "feedback-message bg-initial-feedback";
                    playFeedbackBtn.style.display = "none";
                }
            }

            allProgress[currentDifficulty].words_attempted++;
            ratingEl.textContent = score; renderStars(score);
            wordAttemptsHistory.push(score); renderWordHistory();
            updateUIProgress(); saveProgressToDB();
            saveRatingToDB(currentWord.word, score);
        }

        function updateUIProgress() {
            let p = allProgress[currentDifficulty];
            let score = p.words_correct; if (score > 10) score = 10;
            document.getElementById("progressText").textContent = score + " / 10";
            document.getElementById("progressBarFill").style.width = (score * 10) + "%";
            document.getElementById("attemptedCount").textContent = p.words_attempted;
            const accuracy = p.words_attempted > 0 ? Math.round((p.words_correct / p.words_attempted) * 100) : 0;
            document.getElementById("accuracyRate").textContent = accuracy + "%";
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
                for (let j = 1; j <= 5; j++) {
                    div.innerHTML += `<i class="fa-star fa-solid ${j <= s ? 'filled-star' : 'empty-star'}"></i>`;
                }
                wordHistoryEl.appendChild(div);
            });
        }

        function triggerConfetti() {
            for (let i = 0; i < 30; i++) {
                const c = document.createElement('div'); c.className = 'confetti';
                c.style.left = Math.random() * 100 + 'vw';
                c.style.backgroundColor = ['#FF6B6B','#48DBFB','#FECA57','#1DD1A1'][Math.floor(Math.random()*4)];
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
                    isHardModeUnlocked = (data.mastery_unlocked == 1);
                    if (data.mastered_list) masteredWords = data.mastered_list;
                    if (data.progress) {
                        allProgress[currentDifficulty].word_index = parseInt(data.progress.word_index) || 0;
                        allProgress[currentDifficulty].words_attempted = parseInt(data.progress.words_attempted) || 0;
                        allProgress[currentDifficulty].words_correct = parseInt(data.progress.words_correct) || 0;
                    }
                }
                allProgress.beginner.shuffledList = shuffleArray(wordBank.beginner);
                allProgress.intermediate.shuffledList = shuffleArray(wordBank.intermediate);
                updateUIProgress(); loadNextWord();
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
            if (!STUDENT_ID || !USERNAME) return;

            // Kunin ang value ng timer (halimbawa: "2.5") mula sa screen
            const durationValue = document.getElementById("seconds").textContent;

            const formData = new FormData();
            formData.append('student_id', STUDENT_ID);
            formData.append('username', USERNAME);
            formData.append('word', word);
            formData.append('score', score);

            // Ito ang idadagdag mo para ma-save ang oras
            formData.append('duration', durationValue);

            fetch('save_rating.php', { method: 'POST', body: formData });
        }


        function toggleMic() {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
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
                if (p.words_correct > 0 && p.words_correct % 5 === 0 && currentDifficulty !== 'advanced') {
                    triggerEmojiCheck();
                } else {
                    p.word_index++; saveProgressToDB(); loadNextWord();
                }
            });
            playWordBtn.addEventListener("click", () => speak(currentWord.word));
            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));
            micBtn.addEventListener("click", toggleMic);

            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.onresult = (e) => {
                    const txt = e.results[e.results.length - 1][0].transcript;
                    transcriptEl.textContent = txt; checkPronunciation(txt); stopMicLogic();
                };
            }

            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = getUsEnglishVoice;
            }

            if (SHOW_PARENTAL_NOTE) showParentalGate(); else loadProgressFromDB();

            setTimeout(() => {
                if (PLAY_WELCOME_VOICE) introduceSystem();
            }, 1500);
        }
        window.onload = init;
    </script>
</main>
</body>
</html>