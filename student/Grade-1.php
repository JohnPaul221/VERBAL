<?php
session_start();
require_once('../config/database.php');

// Security Check - Dapat Student at dapat Grade 1
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || $_SESSION['grade'] != '1') {
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
            --primary: #FF6B6B;
            --success: #1DD1A1;
            --kids-blue: #48DBFB;
            --kids-yellow: #FECA57;
            --bg-gradient: linear-gradient(135deg, #FFEFBA 0%, #FFFFFF 100%);
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
        #themeToggle { position: absolute; top: 15px; right: 130px; padding: 5px 10px; border-radius: 10px; cursor: pointer; background: var(--kids-yellow); border: 2px solid #F39C12; z-index: 100; }
        #usernameDisplay { position: absolute; top: 15px; left: 10px; font-size: 0.9rem; font-weight: 700; color: white; background: var(--kids-blue); padding: 5px 12px; border-radius: 10px; border: 2px solid #0ABDE3; z-index: 10; }
        #logoutBtn { position: absolute; top: 15px; right: 10px; padding: 5px 12px; font-size: 0.8rem; background: #FF7675; color: white; border: 2px solid #D63031; border-radius: 10px; z-index: 10; cursor: pointer; }

        /* --- Main Layout --- */
        .main-container {
            height: calc(100vh - 80px);
            max-width: 98vw; margin: 0 auto 10px auto;
            display: flex; flex-wrap: nowrap; gap: 15px;
            background: #fff; border-radius: 25px; padding: 15px;
            box-shadow: 0 8px 0px #FAB1A0; border: 4px solid var(--kids-yellow);
            box-sizing: border-box;
        }

        /* --- Mid Section --- */
        .section {
            flex: 1.2;
            padding: 10px; border-radius: 15px; background-color: #FFF9EB;
            display: flex; flex-direction: column; border: 2px solid #FFEAA7;
            align-items: center; text-align: center;
            overflow: visible;
            scrollbar-width: none;
        }
        .section::-webkit-scrollbar { display: none; }

        .history-section {
            flex: 0.6;
            padding: 10px; border-radius: 15px; background-color: #FFF9EB;
            display: flex; flex-direction: column; border: 2px solid #FFEAA7;
            overflow-y: auto; align-items: center; text-align: center;
            min-width: 200px;
        }

        .divider { width: 3px; background: var(--kids-yellow); opacity: 0.2; border-radius: 10px; }

        /* --- IMAGE BOX ADJUSTMENTS --- */
        .word-image-box {
            width: 150px;  /* Pinalaki mula 100px */
            height: 150px; /* Pinalaki mula 100px */
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
            width: 400px;  /* Pinalaki mula 250px */
            height: 400px; /* Pinalaki mula 250px */
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
            font-size: 1.2rem; margin: 8px 0; color: #EE5253;
            display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        }

        #difficulty {
            width: 90%;
            padding: 8px 12px;
            border-radius: 15px;
            border: 3px solid var(--kids-yellow);
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
            width: 80px; height: 80px; background: var(--primary); border: 5px solid #FFADAD;
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            color: white; font-size: 30px; cursor: pointer; margin: 10px auto;
            box-shadow: 0 4px 0px #D63031;
            transition: transform 0.2s;
        }
        .mic-icon:active { transform: scale(0.9); }
        .mic-icon.listening { animation: pulse 1s infinite; background: #D63031; }

        .reading-material {
            background: #DFF9FB; border-radius: 20px; border: 3px dashed var(--kids-blue);
            padding: 12px; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 8px;
            width: 90%; margin: 10px auto;
        }

        #wordDisplay { font-size: 2rem; font-weight: 900; color: #2D3436; text-transform: uppercase; line-height: 1.2; }

        .transcript {
            font-size: 1.1rem; font-weight: bold; color: #00B894; background: #E3FCEF;
            border: 2px solid #00B894; border-radius: 12px; padding: 8px;
            text-align: center; margin: 5px auto; width: 85%; min-height: 30px;
        }

        /* --- EXAMPLE SENTENCE ADJUSTMENTS --- */
        #feedbackMessage {
            font-size: 1.8rem; /* Pinalaki ang text ng sentence */
            font-weight: 800;
            padding: 15px;
            border-radius: 15px;
            line-height: 1.4;
            text-align: center;
            width: 95%;
            margin: 10px auto;
        }
        .bg-initial-feedback { background: #f1f2f6; color: #7f8c8d !important; }
        .bg-success-feedback { background: #e3fcef; color: #00b894 !important; border: 3px solid #00b894; }

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
            color: #dfe6e9;
        }
        .star-active { color: #FFC300; animation: starPop 0.4s ease-out forwards; }

        .waveform {
            width: 98%;
            max-width: 450px;
            height: 45px;
            border-radius: 15px;
            border: 2px solid #A29BFE;
            background: #F0EDFF;
            margin: 8px auto;
        }
        #waveformCanvas { width: 100%; height: 100%; border-radius: 13px; }

        /* --- STATS CONTAINER ADJUSTMENTS --- */
        .stats-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            background: white;
            padding: 20px 10px;
            border-radius: 20px;
            border: 3px solid #FFEAA7;
            margin: 10px auto;
            width: 100%;
            box-sizing: border-box;
        }
        .stat-item { text-align: center; flex: 1; }
        .stat-item span {
            display: block;
            font-size: 1rem;
            color: #0984E3;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-item .value {
            font-size: 2.8rem; /* Pinalaki ang numero sa stats */
            font-weight: 900;
            color: #F1C40F;
            line-height: 1;
        }

        .progress-bar {
            width: 98%;
            height: 15px;
            background: #eee;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .progress-bar-inner { height: 100%; background: var(--success); transition: width 0.3s ease; }

        button { padding: 8px 15px; border-radius: 30px; border: none; font-weight: 800; cursor: pointer; font-size: 0.85rem; }
        .btn-primary { background: var(--kids-yellow); color: #856404; box-shadow: 0 3px 0 #E1B12C; }
        .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 3px 0 #0A82C5; }

        .filled-star { color: #FFC300 !important; text-shadow: 0 0 5px rgba(255, 195, 0, 0.5); }
        .empty-star { color: #ccc !important; }
        .history-item { display: block; margin: 5px 0; font-size: 0.9rem; border-bottom: 1px solid #eee; padding-bottom: 3px; }

        .confetti { position: fixed; top: -10px; z-index: 9999; pointer-events: none; border-radius: 2px; }
        @keyframes fall { to { transform: translateY(100vh) rotate(360deg); opacity: 0; } }

        @keyframes pulse { 0% { transform: scale(1); } 70% { transform: scale(1.1); box-shadow: 0 0 20px rgba(214, 48, 49, 0.5); } 100% { transform: scale(1); } }
        @keyframes starPop {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.3); }
            100% { transform: scale(1); opacity: 1; }
        }

        body.dark-mode { background: #2d3436; color: white; }
        body.dark-mode .main-container { background: #353b48; border-color: #2f3640; box-shadow: 0 8px 0px #2f3640; }
        body.dark-mode .section { background-color: #2f3640; border-color: #1e272e; color: #f5f6fa; }

        .emoji-btn {
            font-size: 3rem; background: white; border: 3px solid #FECA57;
            border-radius: 20px; padding: 10px; cursor: pointer; transition: 0.2s;
        }
        .emoji-btn:hover { background: #FFF9EB; transform: scale(1.1); }
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
                <div>Word: <span id="progressText" style="font-weight: bold; color: #10b981;">0 / 10</span></div>
            </div>
            <div class="progress-bar"><div class="progress-bar-inner" style="width: 0%"></div></div>

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
                { word: "cat", phonemes: ["k","a","t"], example: "The cat is big." },
                { word: "dog", phonemes: ["d","o","g"], example: "The dog can run." },
                { word: "sun", phonemes: ["s","u","n"], example: "The sun is hot." },
                { word: "hat", phonemes: ["h","a","t"], example: "The hat is red." },
                { word: "pen", phonemes: ["p","e","n"], example: "I have a pen." },
                { word: "bag", phonemes: ["b","a","g"], example: "My bag is new." },
                { word: "cup", phonemes: ["k","u","p"], example: "The cup is blue." },
                { word: "bed", phonemes: ["b","e","d"], example: "The bed is soft." },
                { word: "box", phonemes: ["b","o","ks"], example: "It is a big box." },
                { word: "pig", phonemes: ["p","i","g"], example: "The pig is fat." },
                { word: "map", phonemes: ["m","a","p"], example: "I see a map." },
                { word: "top", phonemes: ["t","o","p"], example: "The top can spin." },
                { word: "net", phonemes: ["n","e","t"], example: "The net is wide." },
                { word: "lip", phonemes: ["l","i","p"], example: "My lip is dry." },
                { word: "bug", phonemes: ["b","u","g"], example: "The bug is small." },
                { word: "rat", phonemes: ["r","a","t"], example: "The rat can run." },
                { word: "fan", phonemes: ["f","a","n"], example: "The fan is on." },
                { word: "sit", phonemes: ["s","i","t"], example: "Sit on the mat." },
                { word: "run", phonemes: ["r","u","n"], example: "I can run fast." },
                { word: "red", phonemes: ["r","e","d"], example: "The ball is red." },
                { word: "big", phonemes: ["b","i","g"], example: "It is big." },
                { word: "hot", phonemes: ["h","o","t"], example: "The sun is hot." },
                { word: "man", phonemes: ["m","a","n"], example: "The man is kind." },
                { word: "hen", phonemes: ["h","e","n"], example: "The hen can run." },
                { word: "jam", phonemes: ["j","a","m"], example: "I like jam." },
                { word: "leg", phonemes: ["l","e","g"], example: "My leg can hop." },
                { word: "mud", phonemes: ["m","u","d"], example: "The mud is wet." },
                { word: "cap", phonemes: ["k","a","p"], example: "The cap is blue." },
                { word: "toy", phonemes: ["t","oy"], example: "The toy is fun." },
                { word: "key", phonemes: ["k","ee"], example: "I have a key." },
                { word: "bus", phonemes: ["b","u","s"], example: "The bus is big." },
                { word: "car", phonemes: ["k","ar"], example: "The car is fast." },
                { word: "cow", phonemes: ["k","ow"], example: "The cow is big." },
                { word: "bee", phonemes: ["b","ee"], example: "The bee can fly." },
                { word: "egg", phonemes: ["e","g"], example: "I eat an egg." },
                { word: "ice", phonemes: ["i","s"], example: "The ice is cold." },
                { word: "ant", phonemes: ["a","n","t"], example: "The ant is small." },
                { word: "bat", phonemes: ["b","a","t"], example: "The bat can fly." },
                { word: "fox", phonemes: ["f","o","ks"], example: "The fox can run." },
                { word: "yak", phonemes: ["y","a","k"], example: "The yak is big." },
                { word: "zip", phonemes: ["z","i","p"], example: "Zip the bag." },
                { word: "van", phonemes: ["v","a","n"], example: "The van is red." },
                { word: "web", phonemes: ["w","e","b"], example: "The web is thin." },
                { word: "wig", phonemes: ["w","i","g"], example: "The wig is funny." },
                { word: "log", phonemes: ["l","o","g"], example: "The log is big." },
                { word: "fin", phonemes: ["f","i","n"], example: "The fish has a fin." },
                { word: "kid", phonemes: ["k","i","d"], example: "The kid can hop." },
                { word: "mix", phonemes: ["m","i","ks"], example: "Mix the jam." },
                { word: "pan", phonemes: ["p","a","n"], example: "The pan is hot." },
                { word: "tap", phonemes: ["t","a","p"], example: "Tap the box." },
                { word: "dad", phonemes: ["d","a","d"], example: "I love my dad." },
                { word: "mom", phonemes: ["m","o","m"], example: "My mom is kind." },
                { word: "sad", phonemes: ["s","a","d"], example: "The boy is sad." },
                { word: "mad", phonemes: ["m","a","d"], example: "Do not be mad." },
                { word: "lid", phonemes: ["l","i","d"], example: "Open the lid." },
                { word: "rod", phonemes: ["r","o","d"], example: "The rod is long." },
                { word: "bib", phonemes: ["b","i","b"], example: "The bib is red." },
                { word: "cub", phonemes: ["k","u","b"], example: "The cub is small." },
                { word: "gum", phonemes: ["g","u","m"], example: "I chew gum." },
                { word: "hut", phonemes: ["h","u","t"], example: "The hut is small." },
                { word: "jet", phonemes: ["j","e","t"], example: "The jet is fast." },
                { word: "kit", phonemes: ["k","i","t"], example: "This is my kit." },
                { word: "lap", phonemes: ["l","a","p"], example: "Sit on my lap." },
                { word: "mat", phonemes: ["m","a","t"], example: "The mat is clean." },
                { word: "nap", phonemes: ["n","a","p"], example: "I take a nap." },
                { word: "owl", phonemes: ["ow","l"], example: "The owl can fly." },
                { word: "pot", phonemes: ["p","o","t"], example: "The pot is hot." },
                { word: "ram", phonemes: ["r","a","m"], example: "The ram can run." },
                { word: "sip", phonemes: ["s","i","p"], example: "Sip the milk." },
                { word: "tub", phonemes: ["t","u","b"], example: "The tub is full." },
                { word: "vet", phonemes: ["v","e","t"], example: "The vet is kind." },
                { word: "wet", phonemes: ["w","e","t"], example: "The dog is wet." },
                { word: "yak", phonemes: ["y","a","k"], example: "The yak is big." },
                { word: "zen", phonemes: ["z","e","n"], example: "He is calm and zen." },
                { word: "dig", phonemes: ["d","i","g"], example: "Dig the sand." },
                { word: "fig", phonemes: ["f","i","g"], example: "The fig is sweet." },
                { word: "hog", phonemes: ["h","o","g"], example: "The hog is big." },
                { word: "jog", phonemes: ["j","o","g"], example: "I jog in the park." },
                { word: "log", phonemes: ["l","o","g"], example: "The log is brown." },
                { word: "mug", phonemes: ["m","u","g"], example: "The mug is blue." },
                { word: "nod", phonemes: ["n","o","d"], example: "I nod my head." },
                { word: "pad", phonemes: ["p","a","d"], example: "The pad is soft." },
                { word: "rag", phonemes: ["r","a","g"], example: "Use the rag." },
                { word: "tag", phonemes: ["t","a","g"], example: "Tag the bag." },
                { word: "wag", phonemes: ["w","a","g"], example: "The dog can wag." },
                { word: "yam", phonemes: ["y","a","m"], example: "I eat yam." },
                { word: "zap", phonemes: ["z","a","p"], example: "Zap the bug." },
                { word: "bid", phonemes: ["b","i","d"], example: "I bid goodbye." },
                { word: "cod", phonemes: ["k","o","d"], example: "The cod can swim." },
                { word: "fed", phonemes: ["f","e","d"], example: "The cat is fed." },
                { word: "gap", phonemes: ["g","a","p"], example: "Jump the gap." },
                { word: "ham", phonemes: ["h","a","m"], example: "I eat ham." },
                { word: "jam", phonemes: ["j","a","m"], example: "The jam is sweet." },
                { word: "lab", phonemes: ["l","a","b"], example: "This is a lab." },
                { word: "mob", phonemes: ["m","o","b"], example: "The mob is loud." },
                { word: "rib", phonemes: ["r","i","b"], example: "The rib is small." },
                { word: "sob", phonemes: ["s","o","b"], example: "Do not sob." },
                { word: "tab", phonemes: ["t","a","b"], example: "Close the tab." },
                { word: "van", phonemes: ["v","a","n"], example: "The van is big." },
                { word: "web", phonemes: ["w","e","b"], example: "The web is thin." }
            ],
            intermediate: [
                { word: "tree", phonemes: ["t","r","ee"], example: "The tree is tall." },
                { word: "milk", phonemes: ["m","i","l","k"], example: "I drink milk." },
                { word: "fish", phonemes: ["f","i","sh"], example: "The fish can swim." },
                { word: "rain", phonemes: ["r","ai","n"], example: "The rain is wet." },
                { word: "ball", phonemes: ["b","a","ll"], example: "The ball is blue." },
                { word: "frog", phonemes: ["f","r","o","g"], example: "The frog can hop." },
                { word: "star", phonemes: ["s","t","ar"], example: "The star is bright." },
                { word: "bird", phonemes: ["b","ir","d"], example: "The bird can fly." },
                { word: "book", phonemes: ["b","oo","k"], example: "I read a book." },
                { word: "cake", phonemes: ["k","ay","k"], example: "The cake is sweet." },
                { word: "play", phonemes: ["p","l","ay"], example: "I want to play." },
                { word: "jump", phonemes: ["j","u","m","p"], example: "The boy can jump." },
                { word: "farm", phonemes: ["f","ar","m"], example: "The farm is big." },
                { word: "moon", phonemes: ["m","oo","n"], example: "The moon is bright." },
                { word: "leaf", phonemes: ["l","ee","f"], example: "The leaf is green." },
                { word: "boat", phonemes: ["b","oa","t"], example: "The boat can sail." },
                { word: "road", phonemes: ["r","oa","d"], example: "The road is long." },
                { word: "snow", phonemes: ["s","n","ow"], example: "The snow is cold." },
                { word: "ship", phonemes: ["sh","i","p"], example: "The ship is big." },
                { word: "ring", phonemes: ["r","i","ng"], example: "The ring is gold." },
                { word: "king", phonemes: ["k","i","ng"], example: "The king is kind." },
                { word: "lamp", phonemes: ["l","a","m","p"], example: "The lamp is on." },
                { word: "sand", phonemes: ["s","a","n","d"], example: "The sand is hot." },
                { word: "wind", phonemes: ["w","i","n","d"], example: "The wind is strong." },
                { word: "hand", phonemes: ["h","a","n","d"], example: "Raise your hand." },
                { word: "hill", phonemes: ["h","i","ll"], example: "The hill is high." },
                { word: "seed", phonemes: ["s","ee","d"], example: "Plant a seed." },
                { word: "corn", phonemes: ["k","or","n"], example: "The corn is sweet." },
                { word: "duck", phonemes: ["d","u","k"], example: "The duck can swim." },
                { word: "rock", phonemes: ["r","o","k"], example: "The rock is hard." },
                { word: "chair", phonemes: ["ch","air"], example: "Sit on the chair." },
                { word: "table", phonemes: ["t","ay","b","l"], example: "The table is round." },
                { word: "bread", phonemes: ["b","r","ea","d"], example: "I eat bread." },
                { word: "plant", phonemes: ["p","l","a","n","t"], example: "Plant a tree." },
                { word: "cloud", phonemes: ["k","l","ow","d"], example: "The cloud is white." },
                { word: "smile", phonemes: ["s","m","ay","l"], example: "She has a smile." },
                { word: "train", phonemes: ["t","r","ai","n"], example: "The train is long." },
                { word: "light", phonemes: ["l","ai","t"], example: "Turn on the light." },
                { word: "night", phonemes: ["n","ai","t"], example: "The night is dark." },
                { word: "sweet", phonemes: ["s","w","ee","t"], example: "The cake is sweet." },
                { word: "bring", phonemes: ["b","r","i","ng"], example: "Bring your bag." },
                { word: "clean", phonemes: ["k","l","ee","n"], example: "Keep it clean." },
                { word: "drink", phonemes: ["d","r","i","ng","k"], example: "Drink your milk." },
                { word: "grass", phonemes: ["g","r","a","s"], example: "The grass is green." },
                { word: "black", phonemes: ["b","l","a","k"], example: "The cat is black." },
                { word: "white", phonemes: ["w","ai","t"], example: "The dog is white." },
                { word: "brown", phonemes: ["b","r","ow","n"], example: "The bear is brown." },
                { word: "stone", phonemes: ["s","t","oa","n"], example: "The stone is hard." },
                { word: "heart", phonemes: ["h","ar","t"], example: "My heart is happy." },
                { word: "laugh", phonemes: ["l","a","f"], example: "The kids laugh." },
                { word: "happy", phonemes: ["h","a","p","ee"], example: "I am happy." },
                { word: "angry", phonemes: ["a","ng","g","r","ee"], example: "He is angry." },
                { word: "quiet", phonemes: ["kw","ai","e","t"], example: "Be quiet." },
                { word: "early", phonemes: ["er","l","ee"], example: "Wake up early." },
                { word: "later", phonemes: ["l","ay","t","er"], example: "See you later." },
                { word: "after", phonemes: ["a","f","t","er"], example: "Come after lunch." },
                { word: "under", phonemes: ["u","n","d","er"], example: "The cat is under." },
                { word: "over", phonemes: ["o","v","er"], example: "Jump over it." },
                { word: "river", phonemes: ["r","i","v","er"], example: "The river is long." },
                { word: "market", phonemes: ["m","ar","k","e","t"], example: "Go to the market." },
                { word: "teacher", phonemes: ["t","ee","ch","er"], example: "My teacher is kind." },
                { word: "student", phonemes: ["s","t","oo","d","e","n","t"], example: "The student can read." },
                { word: "pencil", phonemes: ["p","e","n","s","l"], example: "I have a pencil." },
                { word: "paper", phonemes: ["p","ay","p","er"], example: "Write on the paper." },
                { word: "color", phonemes: ["k","o","l","er"], example: "I like the color blue." },
                { word: "family", phonemes: ["f","a","m","i","l","ee"], example: "I love my family." },
                { word: "window", phonemes: ["w","i","n","d","ow"], example: "Open the window." },
                { word: "garden", phonemes: ["g","ar","d","e","n"], example: "The garden is big." },
                { word: "kitchen", phonemes: ["k","i","ch","e","n"], example: "Mom is in the kitchen." },
                { word: "bathroom", phonemes: ["b","a","th","r","oo","m"], example: "Go to the bathroom." }
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
        const progressBar = document.querySelector(".progress-bar-inner");
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

        // --- VOICE FIX: Para hindi maging boses lalaki ---
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

                if (masteredWords.length === 0) {
                    wordDisplay.textContent = "NO WORDS";
                    phonemeDisplay.textContent = "Get 5 stars in Easy/Medium first!";
                    micBtn.style.opacity = "0.5";
                    micBtn.style.pointerEvents = "none";
                    return;
                }

                micBtn.style.opacity = "1";
                micBtn.style.pointerEvents = "auto";
                playWordBtn.disabled = false;

                let p = allProgress[currentDifficulty];
                if (p.word_index >= masteredWords.length) p.word_index = 0;

                const targetKey = masteredWords[p.word_index]; // FIFO Priority
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

        // --- UI Updates ---
        function updateUIProgress() {
            const p = allProgress[currentDifficulty];
            document.getElementById("attemptedCount").textContent = p.words_attempted;
            const acc = p.words_attempted > 0 ? Math.round((p.words_correct / p.words_attempted) * 100) : 0;
            document.getElementById("accuracyRate").textContent = acc + "%";
            let cycle = (currentDifficulty === 'advanced') ? masteredWords.length : p.words_correct % 10;
            document.getElementById("progressText").textContent = `${cycle} / 10`;
            progressBar.style.width = (cycle * 10) + "%";
            if (document.getElementById("levelDisplay")) {
                document.getElementById("levelDisplay").textContent = currentDifficulty.charAt(0).toUpperCase() + currentDifficulty.slice(1);
            }
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

        // --- Database Calls ---
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