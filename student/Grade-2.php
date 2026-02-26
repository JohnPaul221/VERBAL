<?php
session_start();
require_once('../config/database.php');

// Security Check - Dapat Student at dapat Grade 2
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || $_SESSION['grade'] != '2') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
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
            /* Grade 2 Colors: Mas matingkad at basic colors */
            --primary: #FF4757;    /* Bright Red */
            --success: #1E90FF;    /* Kids Blue Fill */
            --kids-blue: #1E90FF;   /* Sky Blue */
            --kids-yellow: #FFC312; /* Sunflower Yellow */
            --bg-gradient: linear-gradient(135deg, #A29BFE 0%, #FFFFFF 100%); /* Soft Purple to White */
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

        /* --- Mid Section --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #F1F2F6;
            display: flex; flex-direction: column; border: 2px solid #DFE4EA;
            align-items: center; text-align: center;
            overflow: visible;
            scrollbar-width: none;
        }
        .section::-webkit-scrollbar { display: none; }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #F1F2F6;
            display: flex; flex-direction: column; border: 2px solid #DFE4EA;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-blue); opacity: 0.2; border-radius: 10px; }

        /* --- IMAGE BOX ADJUSTMENTS --- */
        .word-image-box {
            width: 150px;
            height: 150px;
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

        /* --- HOVER POP-UP ADJUSTMENTS --- */
        .word-image-box:hover::after {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            width: 400px;
            height: 400px;
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

        /* --- UI Elements --- */
        h2, h3 {
            font-size: 1.2rem; margin: 8px 0; color: #2F3542;
            display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        }

        #difficulty {
            width: 90%;
            padding: 8px 12px;
            border-radius: 15px;
            border: 3px solid var(--kids-blue);
            font-family: inherit;
            font-weight: bold;
            color: #2D3436;
            background: white;
            cursor: pointer;
            outline: none;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        #difficulty:hover { border-color: var(--primary); transform: scale(1.02); }

        .mic-icon {
            width: 80px; height: 80px; background: var(--primary); border: 5px solid #FF7F50;
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            color: white; font-size: 30px; cursor: pointer; margin: 10px auto;
            box-shadow: 0 4px 0px #B33939;
            transition: transform 0.2s;
        }
        .mic-icon:active { transform: scale(0.9); }
        .mic-icon.listening { animation: pulse 1s infinite; background: #FF4757; }

        .reading-material {
            background: #EBF7FF; border-radius: 20px; border: 3px dashed var(--kids-blue);
            padding: 12px; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 8px;
            width: 90%; margin: 10px auto;
        }

        #wordDisplay { font-size: 2.2rem; font-weight: 900; color: #2F3542; text-transform: uppercase; line-height: 1.2; }

        .transcript {
            font-size: 1.1rem; font-weight: bold; color: #218C74; background: #E3FCEF;
            border: 2px solid #2ED573; border-radius: 12px; padding: 8px;
            text-align: center; margin: 5px auto; width: 85%; min-height: 30px;
        }

        /* --- EXAMPLE SENTENCE ADJUSTMENTS --- */
        #feedbackMessage {
            font-size: 1.8rem;
            font-weight: 800;
            padding: 15px;
            border-radius: 15px;
            line-height: 1.4;
            text-align: center;
            width: 95%;
            margin: 10px auto;
        }
        .bg-initial-feedback { background: #F1F2F6; color: #747D8C !important; }
        .bg-success-feedback { background: #DFF9FB; color: #0984E3 !important; border: 3px solid #0984E3; }

        .rating-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 10px 0;
        }
        .rating {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--kids-yellow);
            text-shadow: 2px 2px #E1B12C;
            line-height: 1;
        }
        #stars {
            display: flex;
            gap: 4px;
            font-size: 1.6rem;
            color: #CED6E0;
        }
        .star-active { color: #FFC312; animation: starPop 0.4s ease-out forwards; }

        .waveform {
            width: 98%;
            max-width: 450px;
            height: 45px;
            border-radius: 15px;
            border: 2px solid var(--kids-blue);
            background: #EBF7FF;
            margin: 8px auto;
        }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 13px; }

        /* --- PINALAKING STATS CONTAINER --- */
        .stats-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            background: white;
            padding: 20px 10px;
            border-radius: 30px;
            border: 6px solid var(--kids-blue);
            margin: 15px auto;
            width: 95%;
            box-sizing: border-box;
            box-shadow: 0 6px 0px #0984E3;
        }

        .stat-item {
            text-align: center;
            flex: 1;
            padding: 10px;
        }

        .stat-item span {
            display: block;
            font-size: 1.3rem;
            color: #57606F;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-item .value {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--kids-blue);
            line-height: 1;
            text-shadow: 2px 2px 0px #f1f2f6;
        }

        /* --- ADJUSTED PROGRESS BAR (CLEAR-TO-BLUE EFFECT) --- */
        /* Container ng Progress Bar */
        .progress-bar {
            width: 100%;
            height: 50px;
            background: #FFFFFF; /* Clear/White sa simula */
            border-radius: 20px;
            border: 5px solid #DFE4EA; /* Gray border sa simula */
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
            transition: border-color 0.8s ease; /* Smooth transition ng border */
        }

        /* Kapag nagsimula na ang progress, magiging asul ang border */
        .progress-bar.active-progress {
            border-color: #1E90FF;
        }

        /* Ang mismong laman na gumagalaw */
        .progress-bar-inner {
            height: 100%;
            width: 0%; /* Dito magsisimula sa 0 */
            background: #1E90FF; /* Kids Blue */
            /* Transition para dahan-dahan ang pag-haba */
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes moveStripes {
            from { background-position: 0 0; }
            to { background-position: 40px 0; }
        }

        button { padding: 8px 15px; border-radius: 30px; border: none; font-weight: 800; cursor: pointer; font-size: 0.85rem; }
        .btn-primary { background: var(--kids-yellow); color: #574B15; box-shadow: 0 3px 0 #E1B12C; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 3px 0 #0984E3; }

        .filled-star { color: #FFC312 !important; text-shadow: 0 0 5px rgba(255, 195, 0, 0.5); }
        .empty-star { color: #DFE4EA !important; }
        .history-item { display: block; margin: 5px 0; font-size: 0.9rem; border-bottom: 1px solid #DFE4EA; padding-bottom: 3px; }

        .confetti { position: fixed; top: -10px; z-index: 9999; pointer-events: none; border-radius: 2px; }
        @keyframes fall { to { transform: translateY(100vh) rotate(360deg); opacity: 0; } }

        @keyframes pulse { 0% { transform: scale(1); } 70% { transform: scale(1.1); box-shadow: 0 0 20px rgba(255, 71, 87, 0.5); } 100% { transform: scale(1); } }
        @keyframes starPop {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.3); }
            100% { transform: scale(1); opacity: 1; }
        }

        body.dark-mode { background: #2F3542; color: white; }
        body.dark-mode .main-container { background: #57606F; border-color: #2F3542; box-shadow: 0 8px 0px #2F3542; }
        body.dark-mode .section { background-color: #2F3542; border-color: #57606F; color: #F1F2F6; }

        .emoji-btn {
            font-size: 3rem; background: white; border: 3px solid var(--kids-yellow);
            border-radius: 20px; padding: 10px; cursor: pointer; transition: 0.2s;
        }
        .emoji-btn:hover { background: #F1F2F6; transform: scale(1.1); }
    </style>
</head>
<body>
<header class="page-header">
    <button id="themeToggle" title="Toggle Light/Dark Mode"><i class="fa-solid fa-moon"></i></button>
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
            <div class="mic-container"><div id="micBtn" class="mic-icon"><i class="fa-solid fa-microphone"></i></div></div>
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
                { word: "fan", phonemes: ["f", "a", "n"], example: "Turn on the fan." },
                { word: "mat", phonemes: ["m", "a", "t"], example: "Wipe your feet on the mat." },
                { word: "hen", phonemes: ["h", "e", "n"], example: "The hen has eggs." },
                { word: "pen", phonemes: ["p", "e", "n"], example: "I use a black pen." },
                { word: "bin", phonemes: ["b", "i", "n"], example: "Throw it in the bin." },
                { word: "tin", phonemes: ["t", "i", "n"], example: "The tin can is recycled." },
                { word: "pot", phonemes: ["p", "o", "t"], example: "Mom cooks in a pot." },
                { word: "mop", phonemes: ["m", "o", "p"], example: "Get the mop for the floor." },
                { word: "sun", phonemes: ["s", "u", "n"], example: "The sun is hot today." },
                { word: "cup", phonemes: ["k", "u", "p"], example: "Drink from your cup." },
                { word: "bag", phonemes: ["b", "a", "g"], example: "My bag is on the chair." },
                { word: "jam", phonemes: ["j", "a", "m"], example: "I like bread and jam." },
                { word: "bed", phonemes: ["b", "e", "d"], example: "Fix your bed." },
                { word: "dig", phonemes: ["d", "i", "g"], example: "Dig a hole for the seed." },
                { word: "hop", phonemes: ["h", "o", "p"], example: "The rabbits hop." },
                { word: "gum", phonemes: ["g", "u", "m"], example: "Do not stick gum here." },
                { word: "wet", phonemes: ["w", "e", "t"], example: "The dog is wet." },
                { word: "six", phonemes: ["s", "i", "ks"], example: "I am six years old." },
                { word: "box", phonemes: ["b", "o", "ks"], example: "The box is empty." },
                { word: "run", phonemes: ["r", "u", "n"], example: "We can run fast." },
                { word: "cat", phonemes: ["k", "a", "t"], example: "The cat says meow." },
                { word: "dog", phonemes: ["d", "o", "g"], example: "The dog wags its tail." },
                { word: "hat", phonemes: ["h", "a", "t"], example: "Wear your hat outside." },
                { word: "rat", phonemes: ["r", "a", "t"], example: "The rat is hiding." },
                { word: "bad", phonemes: ["b", "a", "d"], example: "Do not be a bad boy." },
                { word: "sad", phonemes: ["s", "a", "d"], example: "She is sad today." },
                { word: "mad", phonemes: ["m", "a", "d"], example: "Don't be mad at me." },
                { word: "dad", phonemes: ["d", "a", "d"], example: "My dad is tall." },
                { word: "red", phonemes: ["r", "e", "d"], example: "The apple is red." },
                { word: "ten", phonemes: ["t", "e", "n"], example: "I have ten fingers." },
                { word: "net", phonemes: ["n", "e", "t"], example: "The net is for fish." },
                { word: "jet", phonemes: ["j", "e", "t"], example: "The jet flies high." },
                { word: "bib", phonemes: ["b", "i", "b"], example: "The baby wears a bib." },
                { word: "lid", phonemes: ["l", "i", "d"], example: "Close the lid tight." },
                { word: "pig", phonemes: ["p", "i", "g"], example: "The pig is fat." },
                { word: "wig", phonemes: ["w", "i", "g"], example: "The clown has a wig." },
                { word: "lip", phonemes: ["l", "i", "p"], example: "My lip is dry." },
                { word: "zip", phonemes: ["z", "i", "p"], example: "Zip up your jacket." },
                { word: "log", phonemes: ["l", "o", "g"], example: "The log is brown." },
                { word: "top", phonemes: ["t", "o", "p"], example: "The toy top spins." },
                { word: "fog", phonemes: ["f", "o", "g"], example: "I see white fog." },
                { word: "not", phonemes: ["n", "o", "t"], example: "That is not mine." },
                { word: "bud", phonemes: ["b", "u", "d"], example: "The flower has a bud." },
                { word: "mud", phonemes: ["m", "u", "d"], example: "Don't play in the mud." },
                { word: "hug", phonemes: ["h", "u", "g"], example: "Give Mom a hug." },
                { word: "jug", phonemes: ["j", "u", "g"], example: "The jug is full of water." },
                { word: "bug", phonemes: ["b", "u", "g"], example: "The bug is crawling." },
                { word: "bus", phonemes: ["b", "u", "s"], example: "We ride the bus." },
                { word: "cut", phonemes: ["k", "u", "t"], example: "Cut the paper." },
                { word: "nut", phonemes: ["n", "u", "t"], example: "I eat a cashew nut." },
                { word: "pan", phonemes: ["p", "a", "n"], example: "The frying pan is hot." },
                { word: "cap", phonemes: ["k", "a", "p"], example: "My cap is blue." },
                { word: "map", phonemes: ["m", "a", "p"], example: "Look at the map." },
                { word: "tap", phonemes: ["t", "a", "p"], example: "Tap the table." },
                { word: "van", phonemes: ["v", "a", "n"], example: "The van is fast." },
                { word: "win", phonemes: ["w", "i", "n"], example: "We want to win." },
                { word: "sit", phonemes: ["s", "i", "t"], example: "Sit down properly." },
                { word: "hit", phonemes: ["h", "i", "t"], example: "Hit the ball." },
                { word: "big", phonemes: ["b", "i", "g"], example: "The elephant is big." },
                { word: "kit", phonemes: ["k", "i", "t"], example: "A first aid kit." },
                { word: "cab", phonemes: ["k", "a", "b"], example: "Ride the taxi cab." },
                { word: "ham", phonemes: ["h", "a", "m"], example: "I like ham sandwich." },
                { word: "nod", phonemes: ["n", "o", "d"], example: "Nod your head yes." },
                { word: "rod", phonemes: ["r", "o", "d"], example: "The fishing rod is long." },
                { word: "tub", phonemes: ["t", "u", "b"], example: "The tub has bubbles." },
                { word: "wax", phonemes: ["w", "a", "ks"], example: "The candle wax is hot." },
                { word: "mix", phonemes: ["m", "i", "ks"], example: "Mix the batter." },
                { word: "yes", phonemes: ["y", "e", "s"], example: "Say yes to help." },
                { word: "toy", phonemes: ["t", "oy"], example: "This is my favorite toy." },
                { word: "boy", phonemes: ["b", "oy"], example: "The boy is playing." },
                { word: "leg", phonemes: ["l", "e", "g"], example: "My leg is sore." },
                { word: "pet", phonemes: ["p", "e", "t"], example: "I love my pet dog." },
                { word: "sob", phonemes: ["s", "o", "b"], example: "She began to sob." },
                { word: "rob", phonemes: ["r", "o", "b"], example: "Do not rob anyone." },
                { word: "bun", phonemes: ["b", "u", "n"], example: "The bun is soft." },
                { word: "fun", phonemes: ["f", "u", "n"], example: "Playing is fun." },
                { word: "gap", phonemes: ["g", "a", "p"], example: "Mind the gap." },
                { word: "lap", phonemes: ["l", "a", "p"], example: "Sit on my lap." },
                { word: "nap", phonemes: ["n", "a", "p"], example: "Take a short nap." },
                { word: "sap", phonemes: ["s", "a", "p"], example: "The tree has sap." },
                { word: "man", phonemes: ["m", "a", "n"], example: "The man is kind." },
                { word: "fin", phonemes: ["f", "i", "n"], example: "The fish has a fin." },
                { word: "pin", phonemes: ["p", "i", "n"], example: "Use a safety pin." },
                { word: "sin", phonemes: ["s", "i", "n"], example: "Be good and don't sin." },
                { word: "dot", phonemes: ["d", "o", "t"], example: "Connect the dot." },
                { word: "hot", phonemes: ["h", "o", "t"], example: "The soup is hot." },
                { word: "lot", phonemes: ["l", "o", "t"], example: "Thanks a lot." },
                { word: "rot", phonemes: ["r", "o", "t"], example: "The fruits will rot." },
                { word: "fox", phonemes: ["f", "o", "ks"], example: "The fox is orange." },
                { word: "tax", phonemes: ["t", "a", "ks"], example: "Pay the right tax." },
                { word: "yak", phonemes: ["y", "a", "k"], example: "The yak is big." },
                { word: "pug", phonemes: ["p", "u", "g"], example: "A pug is a dog." },
                { word: "rug", phonemes: ["r", "u", "g"], example: "The rug is soft." },
                { word: "tug", phonemes: ["t", "u", "g"], example: "Tug the rope." },
                { word: "pup", phonemes: ["p", "u", "p"], example: "The pup is cute." },
                { word: "hum", phonemes: ["h", "u", "m"], example: "Hum a happy song." },
                { word: "sum", phonemes: ["s", "u", "m"], example: "Find the total sum." },
                { word: "pad", phonemes: ["p", "a", "d"], example: "Write on the pad." },
                { word: "tag", phonemes: ["t", "a", "g"], example: "Play a game of tag." },
                { word: "wag", phonemes: ["w", "a", "g"], example: "The dog can wag." }
            ],
            intermediate: [
                { word: "plant", phonemes: ["p", "l", "a", "n", "t"], example: "The plant is green." },
                { word: "brush", phonemes: ["b", "r", "u", "sh"], example: "Brush your hair." },
                { word: "clock", phonemes: ["k", "l", "o", "k"], example: "The clock is ticking." },
                { word: "dress", phonemes: ["d", "r", "e", "s"], example: "She wears a dress." },
                { word: "school", phonemes: ["s", "k", "oo", "l"], example: "Go to school early." },
                { word: "friend", phonemes: ["f", "r", "e", "n", "d"], example: "You are my friend." },
                { word: "bright", phonemes: ["b", "r", "ai", "t"], example: "The stars are bright." },
                { word: "market", phonemes: ["m", "ar", "k", "e", "t"], example: "Buy fish at the market." },
                { word: "teacher", phonemes: ["t", "ee", "ch", "er"], example: "The teacher is nice." },
                { word: "family", phonemes: ["f", "a", "m", "i", "l", "y"], example: "I love my family." },
                { word: "garden", phonemes: ["g", "ar", "d", "e", "n"], example: "The garden has flowers." },
                { word: "church", phonemes: ["ch", "ur", "ch"], example: "Pray in the church." },
                { word: "snack", phonemes: ["s", "n", "a", "k"], example: "I have a healthy snack." },
                { word: "clean", phonemes: ["k", "l", "ee", "n"], example: "Keep your room clean." },
                { word: "street", phonemes: ["s", "t", "r", "ee", "t"], example: "Cross the street." },
                { word: "bread", phonemes: ["b", "r", "e", "d"], example: "I eat bread." },
                { word: "shout", phonemes: ["sh", "ow", "t"], example: "Do not shout." },
                { word: "ground", phonemes: ["g", "r", "ow", "n", "d"], example: "Sit on the ground." },
                { word: "thanks", phonemes: ["th", "a", "ng", "k", "s"], example: "Give thanks always." },
                { word: "bottle", phonemes: ["b", "o", "t", "l"], example: "Drink from the bottle." },
                { word: "morning", phonemes: ["m", "or", "n", "i", "ng"], example: "Good morning to you." },
                { word: "evening", phonemes: ["ee", "v", "n", "i", "ng"], example: "Good evening, Dad." },
                { word: "mother", phonemes: ["m", "u", "th", "er"], example: "My mother is kind." },
                { word: "father", phonemes: ["f", "a", "th", "er"], example: "My father is strong." },
                { word: "brother", phonemes: ["b", "r", "u", "th", "er"], example: "My brother plays ball." },
                { word: "sister", phonemes: ["s", "i", "s", "t", "er"], example: "My sister is cute." },
                { word: "window", phonemes: ["w", "i", "n", "d", "ow"], example: "Open the window." },
                { word: "pencil", phonemes: ["p", "e", "n", "s", "l"], example: "Sharpen your pencil." },
                { word: "paper", phonemes: ["p", "ay", "p", "er"], example: "Write on the paper." },
                { word: "crayons", phonemes: ["k", "r", "ay", "o", "n", "z"], example: "Color with crayons." },
                { word: "doctor", phonemes: ["d", "o", "k", "t", "er"], example: "The doctor helps us." },
                { word: "nurse", phonemes: ["n", "er", "s"], example: "The nurse is kind." },
                { word: "farmer", phonemes: ["f", "ar", "m", "er"], example: "The farmer plants rice." },
                { word: "police", phonemes: ["p", "o", "l", "ee", "s"], example: "The police help us." },
                { word: "kitchen", phonemes: ["k", "i", "ch", "e", "n"], example: "Cook in the kitchen." },
                { word: "table", phonemes: ["t", "ay", "b", "l"], example: "The table is round." },
                { word: "chair", phonemes: ["ch", "air"], example: "Sit on the chair." },
                { word: "shampoo", phonemes: ["sh", "a", "m", "p", "oo"], example: "Use shampoo for hair." },
                { word: "slipper", phonemes: ["s", "l", "i", "p", "er"], example: "Wear your slipper." },
                { word: "flower", phonemes: ["f", "l", "ow", "er"], example: "The flower is red." },
                { word: "orange", phonemes: ["o", "r", "a", "n", "j"], example: "The orange is sweet." },
                { word: "banana", phonemes: ["b", "a", "n", "a", "n", "a"], example: "Eat a yellow banana." },
                { word: "mango", phonemes: ["m", "a", "ng", "g", "o"], example: "Sweet mango is good." },
                { word: "animal", phonemes: ["a", "n", "i", "m", "l"], example: "The lion is an animal." },
                { word: "strong", phonemes: ["s", "t", "r", "o", "ng"], example: "I am big and strong." },
                { word: "pretty", phonemes: ["p", "r", "i", "t", "ee"], example: "She has a pretty face." },
                { word: "happy", phonemes: ["h", "a", "p", "ee"], example: "I am so happy." },
                { word: "hungry", phonemes: ["h", "u", "ng", "g", "r", "ee"], example: "I am hungry for rice." },
                { word: "thirsty", phonemes: ["th", "er", "s", "t", "ee"], example: "I am thirsty for water." },
                { word: "sleepy", phonemes: ["s", "l", "ee", "p", "ee"], example: "The baby is sleepy." },
                { word: "market", phonemes: ["m", "ar", "k", "e", "t"], example: "Go to the market." },
                { word: "canteen", phonemes: ["k", "a", "n", "t", "ee", "n"], example: "Eat in the canteen." },
                { word: "village", phonemes: ["v", "i", "l", "i", "j"], example: "I live in a village." },
                { word: "bridge", phonemes: ["b", "r", "i", "j"], example: "Cross the long bridge." },
                { word: "tricycle", phonemes: ["t", "r", "ay", "s", "i", "k", "l"], example: "Ride the tricycle." },
                { word: "jeepney", phonemes: ["j", "ee", "p", "n", "ee"], example: "The jeepney is colorful." },
                { word: "picture", phonemes: ["p", "i", "k", "ch", "er"], example: "Draw a nice picture." },
                { word: "mountain", phonemes: ["m", "ow", "n", "t", "i", "n"], example: "The mountain is high." },
                { word: "island", phonemes: ["ay", "l", "a", "n", "d"], example: "The island is far." },
                { word: "river", phonemes: ["r", "i", "v", "er"], example: "The river has fish." },
                { word: "summer", phonemes: ["s", "u", "m", "er"], example: "It is hot in summer." },
                { word: "winter", phonemes: ["w", "i", "n", "t", "er"], example: "It is cold in winter." },
                { word: "weather", phonemes: ["w", "e", "th", "er"], example: "The weather is cloudy." },
                { word: "umbrella", phonemes: ["u", "m", "b", "r", "e", "l", "a"], example: "Use your umbrella." },
                { word: "rainbow", phonemes: ["r", "ay", "n", "b", "ow"], example: "I see a rainbow." },
                { word: "thunder", phonemes: ["th", "u", "n", "d", "er"], example: "The thunder is loud." },
                { word: "clouds", phonemes: ["k", "l", "ow", "d", "z"], example: "The clouds are white." },
                { word: "butterfly", phonemes: ["b", "u", "t", "er", "f", "l", "ay"], example: "The butterfly is pretty." },
                { word: "spider", phonemes: ["s", "p", "ay", "d", "er"], example: "The spider has 8 legs." },
                { word: "cricket", phonemes: ["k", "r", "i", "k", "e", "t"], example: "The cricket chirps." },
                { word: "elephant", phonemes: ["e", "l", "e", "f", "n", "t"], example: "The elephant is big." },
                { word: "monkey", phonemes: ["m", "u", "ng", "k", "ee"], example: "The monkey eats banner." },
                { word: "rabbit", phonemes: ["r", "a", "b", "i", "t"], example: "The rabbit is fast." },
                { word: "turtle", phonemes: ["t", "er", "t", "l"], example: "The turtle is slow." },
                { word: "plastic", phonemes: ["p", "l", "a", "s", "t", "i", "k"], example: "Do not use plastic." },
                { word: "recycle", phonemes: ["r", "ee", "s", "ay", "k", "l"], example: "Recycle the bottles." },
                { word: "helper", phonemes: ["h", "e", "l", "p", "er"], example: "Be a little helper." },
                { word: "honest", phonemes: ["o", "n", "e", "s", "t"], example: "Be an honest child." },
                { word: "polite", phonemes: ["p", "o", "l", "ay", "t"], example: "Be polite to elders." },
                { word: "quiet", phonemes: ["k", "w", "ay", "e", "t"], example: "Please be quiet." },
                { word: "country", phonemes: ["k", "u", "n", "t", "r", "ee"], example: "I love my country." },
                { word: "people", phonemes: ["p", "ee", "p", "l"], example: "Many people are here." },
                { word: "Sunday", phonemes: ["s", "u", "n", "d", "ay"], example: "We go to church on Sunday." },
                { word: "Monday", phonemes: ["m", "u", "n", "d", "ay"], example: "Classes start on Monday." },
                { word: "Tuesday", phonemes: ["t", "oo", "z", "d", "ay"], example: "Tuesday is my birthday." },
                { word: "Wednesday", phonemes: ["w", "e", "n", "z", "d", "ay"], example: "We play on Wednesday." },
                { word: "Thursday", phonemes: ["th", "er", "z", "d", "ay"], example: "Thursday is a holiday." },
                { word: "Friday", phonemes: ["f", "r", "ay", "d", "ay"], example: "Friday is fish day." },
                { word: "Saturday", phonemes: ["s", "a", "t", "er", "d", "ay"], example: "Rest on Saturday." },
                { word: "January", phonemes: ["j", "a", "n", "y", "u", "e", "r", "ee"], example: "It is January now." },
                { word: "birthday", phonemes: ["b", "er", "th", "d", "ay"], example: "Happy birthday to you!" },
                { word: "numbers", phonemes: ["n", "u", "m", "b", "er", "z"], example: "Count the numbers." },
                { word: "letters", phonemes: ["l", "e", "t", "er", "z"], example: "Write the letters." },
                { word: "reading", phonemes: ["r", "ee", "d", "i", "ng"], example: "I love reading books." },
                { word: "writing", phonemes: ["r", "ay", "t", "i", "ng"], example: "My writing is neat." },
                { word: "drawing", phonemes: ["d", "r", "o", "i", "ng"], example: "The drawing is colorful." },
                { word: "singing", phonemes: ["s", "i", "ng", "i", "ng"], example: "She is good at singing." },
                { word: "dancing", phonemes: ["d", "a", "n", "s", "i", "ng"], example: "Join the dancing group." },
                { word: "playground", phonemes: ["p", "l", "ay", "g", "r", "ow", "n", "d"], example: "Play at the playground." },
                { word: "together", phonemes: ["t", "u", "g", "e", "th", "er"], example: "We play together." }
            ]
        };

        const emojiPool = [
            { char: '🍎', name: 'Apple' }, { char: '🐶', name: 'Dog' }, { char: '🚗', name: 'Car' },
            { char: '🐘', name: 'Elephant' }, { char: '🌞', name: 'Sun' }, { char: '🍕', name: 'Pizza' },
            { char: '🎈', name: 'Balloon' }, { char: '🐱', name: 'Cat' }, { char: '🍌', name: 'Banana' },
            { char: '🍦', name: 'Ice Cream' }, { char: '🏠', name: 'House' }, { char: '🐸', name: 'Frog' },
            { char: '🚀', name: 'Rocket' }, { char: '🎁', name: 'Gift' }, { char: '🦋', name: 'Butterfly' },
            { char: '🧸', name: 'Teddy Bear' }, { char: '🌈', name: 'Rainbow' }, { char: '⚽', name: 'Ball' }
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
                html: `
    <div style="text-align: left; font-family: sans-serif; line-height: 1.6; padding: 10px;">
        <p style="color: #2D3436; font-weight: bold;">Dear Parent,</p>
        <p>Ang goal po natin ay matuto ang inyong anak. <b>Hayaan silang magkamali</b> para malaman ni teacher kung saan sila dapat tulungan.</p>
        <hr style="margin: 15px 0;">
        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; background: #f0f9ff; padding: 10px; border-radius: 10px; border: 1px solid #bae6fd;">
            <input type="checkbox" id="honestyCheck" style="width: 22px; height: 22px; margin-top: 3px;">
            <span style="font-size: 0.9rem; color: #0369a1;">Naintindihan ko at hahayaan ang anak ko na mag-practice mag-isa.</span>
        </label>
    </div>
`,
                confirmButtonText: 'Start Learning! 🚀',
                confirmButtonColor: '#48DBFB',
                allowOutsideClick: false,
                preConfirm: () => {
                    if (!document.getElementById('honestyCheck').checked) {
                        Swal.showValidationMessage('Pakicheck po ang box para magpatuloy.');
                    }
                }
            }).then(() => {
                loadProgressFromDB();
            });
        }

        // --- Emoji Verification Function ---
        function triggerEmojiCheck() {
            const shuffled = [...emojiPool].sort(() => 0.5 - Math.random());
            const selection = shuffled.slice(0, 4);
            const targetEmoji = selection[Math.floor(Math.random() * selection.length)];

            Swal.fire({
                title: 'Laro Muna Tayo! 🎮',
                html: `
    <p style="font-size: 1.3rem; font-weight: 800; color: #FF6B6B;">Nasaan ang ${targetEmoji.name}?</p>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-width: 320px; margin: 20px auto 0 auto;">
        ${selection.map(e => `
            <button class="emoji-btn" onclick="checkEmojiChoice('${e.char}', '${targetEmoji.char}')">
                ${e.char}
            </button>
        `).join('')}
    </div>
`,
                showConfirmButton: false,
                allowOutsideClick: false,
                background: '#FFF9EB'
            });
        }

        window.checkEmojiChoice = function(chosen, target) {
            if (chosen === target) {
                Swal.fire({ icon: 'success', title: 'Magaling! ⭐', showConfirmButton: false, timer: 1500 }).then(() => {
                    let p = allProgress[currentDifficulty];
                    p.word_index++;
                    loadNextWord();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Mali! Try muna ulit!', confirmButtonText: 'Sige po!' }).then(() => triggerEmojiCheck());
            }
        };

        // --- VOICE FIX ---
        function getUsEnglishVoice() {
            if (usEnglishVoice) return usEnglishVoice;
            const voices = speechSynthesis.getVoices();
            let selected = voices.find(v => v.name === 'Google US English') ||
                voices.find(v => v.name.includes('Female')) ||
                voices.find(v => v.name.includes('Zira')) ||
                voices.find(v => v.lang === 'en-US');
            usEnglishVoice = selected;
            return usEnglishVoice;
        }

        function speak(text) {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
            speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            const v = getUsEnglishVoice();
            if (v) {
                utter.voice = v;
                utter.lang = v.lang;
            } else {
                utter.lang = "en-US";
            }
            speechSynthesis.speak(utter);
        }

        function speakRating(score) {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
            let message = (score >= 4) ? (score === 5 ? "Excellent!" : "Great job!") : "Try again!";
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
                analyser.fftSize = 256;
                bufferLength = analyser.frequencyBinCount;
                dataArray = new Uint8Array(bufferLength);
                drawWaveform();
            } catch (err) { console.error("Mic error:", err); }
        }

        function drawWaveform() {
            if (!listening) return;
            animationId = requestAnimationFrame(drawWaveform);
            analyser.getByteFrequencyData(dataArray);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let x = 0;
            let barWidth = (canvas.width / bufferLength) * 2;
            for (let i = 0; i < bufferLength; i++) {
                let barHeight = (dataArray[i] / 255) * canvas.height;
                ctx.fillStyle = `hsl(${200 + (dataArray[i]/255)*100}, 90%, 60%)`;
                ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                x += barWidth + 1;
            }
        }

        // --- Scoring Logic ---
        function ratePronunciation(spoken, target) {
            spoken = spoken.toLowerCase().trim().replace(/[.,!]/g, "");
            target = target.toLowerCase().trim().replace(/[.,!]/g, "");
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

        // --- Game Logic ---
        function loadNextWord() {
            feedbackMessage.textContent = "Practice the word to see the sentence!";
            feedbackMessage.className = "feedback-message bg-initial-feedback";
            playFeedbackBtn.style.display = "none";
            transcriptEl.textContent = "...";
            ratingEl.textContent = "0";
            renderStars(0);
            nextBtn.disabled = true;

            if (currentDifficulty === 'advanced') {
                if (!isHardModeUnlocked) {
                    wordDisplay.textContent = "LOCKED";
                    phonemeDisplay.textContent = "Ask your teacher to unlock Hard Mode!";
                    micBtn.style.opacity = "0.5";
                    micBtn.style.pointerEvents = "none";
                    playWordBtn.disabled = true;
                    return;
                }
                micBtn.style.opacity = "1";
                micBtn.style.pointerEvents = "auto";
                playWordBtn.disabled = false;
                let p = allProgress[currentDifficulty];
                if (p.word_index >= masteredWords.length) p.word_index = 0;
                const targetKey = masteredWords[p.word_index];
                const allPossible = [...wordBank.beginner, ...wordBank.intermediate];
                let raw = allPossible.find(item => item.word.toLowerCase() === targetKey);
                currentWord = raw ? { word: raw.example, phonemes: ["Sentence Mastery"], example: raw.example, originalWord: raw.word } : allPossible[0];
            } else {
                micBtn.style.opacity = "1";
                micBtn.style.pointerEvents = "auto";
                playWordBtn.disabled = false;
                let p = allProgress[currentDifficulty];
                if (!p.shuffledList || p.shuffledList.length === 0 || p.word_index >= p.shuffledList.length) {
                    p.shuffledList = shuffleArray(wordBank[currentDifficulty]);
                    p.word_index = 0;
                }
                currentWord = p.shuffledList[p.word_index];
            }

            wordDisplay.textContent = currentWord.word;
            phonemeDisplay.textContent = currentWord.phonemes.join(" · ");
            const imgKey = (currentWord.originalWord || currentWord.word).toLowerCase();
            document.getElementById("wordImage").src = imageLibrary[imgKey] || "https://via.placeholder.com/120";

            wordAttemptsHistory = [];
            renderWordHistory();
            updateUIProgress();
        }

        function checkPronunciation(spoken) {
            const score = ratePronunciation(spoken, currentWord.word);
            speakRating(score);

            if (score === 5) {
                const key = (currentWord.originalWord || currentWord.word).toLowerCase();
                if (currentDifficulty !== 'advanced' && !masteredWords.includes(key)) {
                    masteredWords.push(key);
                }
                if (!wordAttemptsHistory.includes(5)) {
                    allProgress[currentDifficulty].words_correct++;
                    triggerConfetti();
                }
            }

            allProgress[currentDifficulty].words_attempted++;
            ratingEl.textContent = score;
            renderStars(score);
            wordAttemptsHistory.push(score);
            renderWordHistory();
            updateUIProgress();
            saveProgressToDB();
            saveRatingToDB(currentWord.word, score);

            if (score >= 4) {
                nextBtn.disabled = false;
                feedbackMessage.textContent = "⭐ " + currentWord.example;
                feedbackMessage.className = "feedback-message bg-success-feedback";
                playFeedbackBtn.style.display = "inline-block";
            } else {
                nextBtn.disabled = true;
            }
        }

        function updateUIProgress() {
            let p = allProgress[currentDifficulty];

            // Dito natin kukunin ang score (halimbawa: 1)
            let score = p.words_correct;
            if (score > 10) score = 10;

            document.getElementById("progressText").textContent = score + " / 10";

            const fill = document.getElementById("progressBarFill");

            // DITO MO ILALAGAY:
            // Palitan mo yung dati mong code ng line na 'to:
            // (score / 10) * 100 = Ito ang magbibigay ng 10% shade sa bawat puntos
            fill.style.width = (score / 10) * 100 + "%";

            // Para sa border effect
            const mainBar = document.getElementById("progressBarMain");
            if (score > 0) {
                mainBar.classList.add("active-progress");
            } else {
                mainBar.classList.remove("active-progress");
            }


            // Update ng iba pang stats sa scoreboard
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
                const div = document.createElement("div");
                div.className = "history-item";
                div.innerHTML = `<span>Attempt ${wordAttemptsHistory.length - i}:</span> `;
                for (let j = 1; j <= 5; j++) {
                    div.innerHTML += `<i class="fa-star fa-solid ${j <= s ? 'filled-star' : 'empty-star'}"></i>`;
                }
                wordHistoryEl.appendChild(div);
            });
        }

        function triggerConfetti() {
            for (let i = 0; i < 30; i++) {
                const c = document.createElement('div');
                c.className = 'confetti';
                c.style.left = Math.random() * 100 + 'vw';
                c.style.backgroundColor = ['#FF6B6B','#48DBFB','#FECA57','#1DD1A1'][Math.floor(Math.random()*4)];
                c.style.width = '8px'; c.style.height = '8px'; c.style.position = 'fixed'; c.style.top = '-10px';
                c.style.animation = `fall ${Math.random()*3+2}s linear forwards`;
                document.body.appendChild(c);
                setTimeout(() => c.remove(), 5000);
            }
        }

        async function loadProgressFromDB() {
            if (!STUDENT_ID) { loadNextWord(); return; }
            try {
                const response = await fetch(`load_progress.php?student_id=${STUDENT_ID}&difficulty=${currentDifficulty}&t=${Date.now()}`);
                const data = await response.json();
                if (data.success) {
                    isHardModeUnlocked = (data.mastery_unlocked == 1);
                    if (data.progress) {
                        allProgress[currentDifficulty].word_index = parseInt(data.progress.word_index) || 0;
                        allProgress[currentDifficulty].words_attempted = parseInt(data.progress.words_attempted) || 0;
                        allProgress[currentDifficulty].words_correct = parseInt(data.progress.words_correct) || 0;
                    }
                }
                allProgress.beginner.shuffledList = shuffleArray(wordBank.beginner);
                allProgress.intermediate.shuffledList = shuffleArray(wordBank.intermediate);
                updateUIProgress();
                loadNextWord();
            } catch (e) { console.error("Load error", e); loadNextWord(); }
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
            if (!STUDENT_ID || !USERNAME) return;
            const formData = new FormData();
            formData.append('student_id', STUDENT_ID);
            formData.append('username', USERNAME);
            formData.append('word', word);
            formData.append('score', score);
            fetch('save_rating.php', { method: 'POST', body: formData });
        }

        function toggleMic() {
            if (currentDifficulty === 'advanced' && !isHardModeUnlocked) return;
            if (!recognition) return;
            if (!listening) {
                listening = true;
                micBtn.classList.add("listening");
                statusEl.textContent = "Listening...";
                startWaveform();
                recognition.start();
            } else {
                listening = false;
                micBtn.classList.remove("listening");
                recognition.stop();
                if (animationId) cancelAnimationFrame(animationId);
            }
        }

        function init() {
            difficultySelect.addEventListener("change", () => {
                currentDifficulty = difficultySelect.value;
                loadProgressFromDB();
            });
            nextBtn.addEventListener("click", () => {
                let p = allProgress[currentDifficulty];
                if (p.words_correct > 0 && p.words_correct % 5 === 0 && currentDifficulty !== 'advanced') {
                    triggerEmojiCheck();
                } else {
                    p.word_index++;
                    loadNextWord();
                }
            });
            playWordBtn.addEventListener("click", () => speak(currentWord.word));
            playFeedbackBtn.addEventListener("click", () => speak(currentWord.example));
            micBtn.addEventListener("click", toggleMic);
            speechSynthesis.onvoiceschanged = getUsEnglishVoice;
            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition();
                recognition.onresult = (e) => {
                    const txt = e.results[e.results.length - 1][0].transcript;
                    transcriptEl.textContent = txt;
                    checkPronunciation(txt);
                    listening = false;
                    micBtn.classList.remove("listening");
                };
            }
            if (SHOW_PARENTAL_NOTE) {
                showParentalGate();
            } else {
                loadProgressFromDB();
            }
        }
        window.onload = init;
    </script>
</main>
</body>
</html>