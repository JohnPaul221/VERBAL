<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$play_welcome_voice = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VERBAL PRACTICE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #3b82f6; --primary-dark: #1e40af; --success: #10b981; --danger: #ef4444; --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); --glass: rgba(255, 255, 255, 0.95); }
        body { height: 100vh; background: var(--bg-gradient); font-family: "Poppins", sans-serif; padding: 10px 20px; color: #334155; margin: 0; display: flex; flex-direction: column; overflow: hidden; }
        h2 { color: var(--primary-dark); font-size: 1.5rem; margin-bottom: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        h3 { color: #475569; font-size: 1.1rem; margin: 15px 0 10px 0; font-weight: 600; }
        .page-header { padding: 5px 0; margin: 0; min-height: 0; }
        #usernameDisplay { position: absolute; top: 25px; left: 35px; font-size: 0.95rem; font-weight: 700; color: #1e40af; background: rgba(224, 242, 254, 0.8); padding: 6px 15px; border-radius: 12px; border: 1px solid #bae6fd; z-index: 10; backdrop-filter: blur(4px); }
        #logoutBtn { position: absolute; top: 25px; right: 35px; padding: 8px 15px; font-size: 0.85rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 10px; z-index: 10; cursor: pointer; transition: all 0.2s; box-shadow: none; }
        #logoutBtn:hover { background: #fca5a5; }
        #logoutBtn:active { box-shadow: 0 1px #b91c1c; transform: translateY(2px); }
        .main-container { max-width: 95vw; margin: 0px auto; min-height: 85vh; display: flex; flex-wrap: nowrap; gap: 20px; background: #ffffff; border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); border: 3px solid #60a5fa; }
        .section h2 { margin-top: 10px; }
        .section { flex: 1; padding: 15px; border-radius: 10px; background-color: #f7fbff; max-height: calc(85vh - 40px); overflow-y: auto; display: flex; flex-direction: column; }
        .divider { width: 3px; background: #60a5fa; border-radius: 3px; }
        .progress-bar { height: 15px; background: #e0f7ff; border-radius: 9999px; margin-bottom: 10px; overflow: hidden; border: 1px solid #3b82f6; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 9999px; transition: width 0.5s ease-in-out; }
        #difficulty { width: 100%; padding: 8px; margin-bottom: 8px; border: 2px solid #60a5fa; border-radius: 10px; font-size: 1rem; background-color: #fff; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2069.4L146.4%20209.7%205.1%2069.4c-3.1-3.1-3.1-8.2%200-11.3l11.3-11.3c3.1-3.1%208.2-3.1%2011.3%200l118.8%20118.8%20118.8-118.8c3.1-3.1%208.2-3.1%2011.3%200l11.3%2011.3c3.2%203.1%203.2%208.2%200%2011.4z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px center; background-size: 12px; }
        .reading-material { background: #b2ebf2; padding: 8px; border-radius: 10px; margin-bottom: 6px; border: 2px dashed #00bcd4; }
        #wordDisplay { font-size: 2.2rem; text-align: center; font-weight: 900; color: #00838f; padding: 3px 0; text-shadow: 1px 1px 0px rgba(255, 255, 255, 0.9); }
        #phonemeDisplay { font-size: 1.0rem; color: #6b7280; text-align: center; margin-top: 3px; }
        .reading-material button { padding: 6px 12px; font-size: 0.9rem; border-radius: 20px; }
        button { padding: 8px 16px; border-radius: 20px; border: none; cursor: pointer; font-size: 1rem; font-weight: bold; transition: all 0.2s ease-out; box-shadow: 0 3px #4b5563; margin: 3px; }
        #nextBtn, #playFeedbackBtn, #leaderboardBtn { padding: 8px 16px; font-size: 1rem; }
        button:active { box-shadow: 0 1px #4b5563; transform: translateY(2px); }
        .btn-primary { background: #ef4444; color: #fff; box-shadow: 0 3px #b91c1c; }
        .btn-primary:hover { background: #f87171; }
        .btn-secondary { background: #3b82f6; color: #fff; box-shadow: 0 3px #1d4ed8; }
        .btn-secondary:hover { background: #60a5fa; }
        .mic-container { width: 90px; height: 90px; margin: 10px auto; }
        .mic-icon { width: 100%; height: 100%; background-color: #3b82f6; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 36px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0,0,0,0.2); border: 4px solid #63b3ed; }
        .mic-icon.listening { background-color: #ef4444; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { transform: scale(1.1); box-shadow: 0 0 0 25px rgba(239, 68, 68, 0); } 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .status { font-size: 1.1rem; margin-bottom: 8px; color: #1e3a8a; text-align: center; font-weight: bold; }
        .waveform { height: 80px; border-radius: 10px; background: linear-gradient(to right, #e0f2fe, #dbeafe); box-shadow: inset 0 0 5px rgba(0,0,0,0.05); margin-bottom: 8px; position: relative; }
        #waveformCanvas { width: 100%; height: 100%; display: block; }
        .transcript { font-size: 1.2rem; font-weight: bold; color: #166534; margin: 5px auto; text-align: center; padding: 8px; border-radius: 8px; background-color: #ecfdf5; border: 2px solid #34d399; }
        .rating-container { display: flex; justify-content: center; align-items: center; margin: 8px 0; gap: 8px; }
        .rating { font-size: 2rem; font-weight: 900; color: #FFC300; }
        .star { font-size: 1.2rem; margin: 0 3px; transition: color 0.3s ease; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3); }
        .filled-star { color: #FFC300; }
        .empty-star { color: #ccc; text-shadow: none; }
        .feedback-message { padding: 10px; border-radius: 8px; font-size: 0.95rem; font-weight: bold; min-height: 50px; margin-top: 5px; }
        .bg-green-100 { background: #d1fae5; } .text-green-800 { color: #065f46; }
        .bg-blue-100 { background: #dbeafe; } .text-blue-800 { color: #1e40af; }
        .bg-red-100 { background: #fee2e2; } .text-red-800 { color: #991b1b; }
        .bg-initial-feedback { background: #f3f4f6; color: #6b7280; border: 2px dashed #d1d5db; }
        .feedback-scoreboard-container { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .feedback-container, .scoreboard-container { flex: 1 1 45%; min-width: 45%; display: flex; flex-direction: column; }
        .feedback-container h3, .scoreboard-container h3 { margin-top: 5px; margin-bottom: 3px; }
        .stats-container { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .stat-item span { display: block; font-size: 0.9rem; color: #1e40af; }
        .stat-item .value { font-size: 1.8rem; font-weight: 900; color: #FFC300; }
        .flex-center { display: flex; justify-content: center; align-items: center; }
        .flex-space-between { display: flex; justify-content: space-between; align-items: center; }
        .mt-2 { margin-top: 4px; } .mt-4 { margin-top: 8px; } .mb-4 { margin-bottom: 8px; }
        .text-center { text-align: center; }
        .history-section h2 { font-size: 1.5rem; color: #10b981; border-bottom: 2px solid #10b981; margin-bottom: 15px; padding-bottom: 5px; }
        @keyframes starPop { 0% { transform: scale(0.5) translateY(10px); opacity: 0; } 50% { transform: scale(1.2) translateY(-5px); opacity: 1; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
        #wordHistory { display: flex; flex-direction: column-reverse; align-items: flex-start; gap: 5px; padding: 0; }
        .history-item { display: flex; justify-content: flex-start; align-items: center; width: 100%; margin: 0; padding: 3px 0; border-bottom: 1px dashed #e0f2fe; font-weight: 600; font-size: 0.9rem; color: #1e40af; }
        .history-item:last-child { border-bottom: none; }
        .history-star { animation: starPop 0.5s ease-out; display: inline-block; margin-right: 2px; font-size: 1.1rem; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3); }
        .attempt-label { font-weight: bold; color: #00838f; margin-right: 5px; min-width: 75px; }
        .history-message { font-size: 0.9rem; color: #10b981; font-weight: bold; margin-top: 10px; }

        /* SPINNER & FALLBACK */
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-spinner {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(59, 130, 246, 0.2);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
            z-index: 2;
        }
        .img-error { opacity: 0.5; filter: grayscale(1); }

        /* FIXED CENTERED ZOOM STYLES */
        .word-image-box {
            width: 110px;
            height: 110px;
            background: #ffffff;
            border: 2px solid #00838f;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            position: relative;
        }

        #wordImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            transition: opacity 0.2s;
        }

        .word-image-box:hover::after {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(2.5);
            width: 180px;
            height: 180px;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            background-color: white;
            z-index: 9999;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            border: 4px solid white;
            border-radius: 10px;
            animation: popIn 0.2s ease-out forwards;
        }

        @keyframes popIn {
            from { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(2.5); opacity: 1; }
        }

        @media (max-width: 600px) { .word-image-box:hover::after { width: 140px; height: 140px; transform: translate(-50%, -50%) scale(2); } }
        @media (max-width: 1200px) { body { overflow-y: auto; } .main-container { max-width: 98vw; margin: 10px auto; height: 85vh; display: flex; flex-wrap: nowrap; gap: 15px; background: #ffffff; border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); border: 3px solid #60a5fa; box-sizing: border-box; } .section, .history-section { flex: 1 1 100%; max-height: none; overflow-y: visible; min-width: 100%; } .section:nth-child(1), .section:nth-child(2) { min-width: 100%; max-width: 100%; flex: 1 1 100%; } .divider { display: none; } .history-section { order: 4; } .section:nth-child(1) { order: 1; } .section:nth-child(2) { order: 2; } .feedback-scoreboard-container { flex-direction: column; } .feedback-container, .scoreboard-container { flex: 1 1 100%; min-width: 100%; } .stats-container { grid-template-columns: 1fr 1fr; } #logoutBtn { position: static; margin: 10px auto; display: block; width: 90%; } #usernameDisplay { position: static; margin: 10px auto; display: block; width: 90%; text-align: center; } .page-header { flex-direction: column; } }
    </style>
</head>
<body>
<header class="page-header">
    <?php echo '<span id="usernameDisplay">Hello, ' . htmlspecialchars($username) . '!</span>'; ?>
    <button id="logoutBtn" onclick="window.location.href='../login.php';"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
</header>
<main>
    <div class="main-container">
        <div class="section">
            <h2>Your Turn to Talk! 🎤</h2>
            <div class="flex-space-between mb-4">
                <div>Level: <span id="levelDisplay" style="font-weight: bold; color: #10b981;">Beginner</span></div>
                <div>Word: <span id="progressText" style="font-weight: bold; color: #10b981;">0 / 5</span></div>
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
                <button id="leaderboardBtn" class="btn-primary" onclick="window.location.href='../leaderboard.php';">🏆 Leaderboard</button>
                <button id="nextBtn" class="btn-secondary" disabled>👉 Next Word!</button>
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
                        <button id="playWordBtn" class="btn-primary" style="font-size: 0.8rem; padding: 5px 12px;">
                            <i class="fa-solid fa-volume-high"></i> Listen
                        </button>
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
        <div class="history-section">
            <h2>Attempts History</h2>
            <div id="wordHistory">No attempts yet.</div>
            <div id="historyMessage" class="history-message"></div>
        </div>
    </div>
    <?php
    $manualWords = [
        // Beginner (1-100)
            "sun", "moon", "star", "cloud", "rain", "tree", "bird", "fish", "apple", "book",
            "cat", "dog", "ball", "cup", "hat", "pen", "egg", "fan", "box", "toy",
            "milk", "door", "bed", "frog", "duck", "cake", "boat", "car", "bus", "key",
            "leaf", "ant", "bee", "cow", "pig", "bat", "bag", "net", "ice", "fire",
            "lamp", "ring", "shoe", "soap", "soup", "kite", "gift", "flag", "drum", "bell",
            "crab", "deer", "goat", "lamb", "lion", "bear", "wolf", "seed", "corn", "pear",
            "desk", "fork", "bowl", "wall", "roof", "gate", "road", "hill", "lake", "rock",
            "hand", "foot", "nose", "eyes", "ears", "hair", "neck", "back", "knee", "toes",
            "pink", "blue", "red", "gold", "gray", "ship", "bike", "card", "baby", "home",
            "farm", "park", "nest", "zero", "five", "nine", "king", "lamp", "duck", "bell",

        // Intermediate (101-200)
            "mountain", "garden", "balloon", "camera", "bottle", "window", "kitchen", "market", "school", "pencil",
            "village", "forest", "bridge", "island", "planet", "rocket", "doctor", "farmer", "worker", "singer",
            "picture", "blanket", "bicycle", "chicken", "dolphin", "elephant", "giraffe", "hamster", "octopus", "penguin",
            "rainbow", "thunder", "weather", "clothes", "glasses", "jewelry", "pockets", "sneakers", "watches", "whistle",
            "breakfast", "sandwich", "vegetable", "chocolate", "pancake", "cucumber", "eggplant", "mushroom", "potatoes", "tomatoes",
            "butterfly", "squirrel", "mosquito", "scorpion", "alligator", "kangaroo", "ostrich", "reindeer", "dinosaur", "tortoise",
            "airplane", "ambulance", "firetruck", "helicopter", "spaceship", "submarine", "traincar", "sailboat", "motorcycle", "tractor",
            "building", "hospital", "library", "museum", "restaurant", "stadium", "theater", "workshop", "apartment", "castle",
            "keyboard", "monitor", "notebook", "speaker", "telephone", "television", "flashlight", "microscope", "telescope", "compass",
            "calendar", "dictionary", "envelope", "magazine", "newspaper", "postcard", "scissors", "umbrella", "backpack", "suitcase",

        // Advanced (201-300)
            "architecture", "environment", "technology", "literature", "philosophy", "psychology", "astronomy", "government", "university", "experience",
            "adventure", "celebration", "communication", "competition", "description", "education", "imagination", "information", "organization", "population",
            "discovery", "electricity", "foundation", "generation", "instrument", "investment", "management", "permission", "production", "reflection",
            "vocabulary", "transportation", "satisfaction", "reputation", "personality", "opportunity", "negotiation", "membership", "leadership", "friendship",
            "championship", "relationship", "partnership", "scholarship", "internship", "citizenship", "stewardship", "fellowship", "craftsmanship", "agriculture",
            "biotechnology", "cryptography", "dermatology", "engineering", "genealogy", "hieroglyphics", "illustration", "journalism", "kinematics", "landscape",
            "mathematics", "nanotechnology", "oceanography", "photography", "quarantine", "radioactivity", "spectroscopy", "topography", "ultraviolet", "vulnerability",
            "wilderness", "xenophobia", "yesteryear", "zoology", "abundance", "benevolent", "consequence", "determination", "enthusiasm", "fluctuation",
            "gravitation", "hospitality", "independence", "jurisdiction", "knowledgeable", "limitation", "magnificent", "notification", "observation", "perspective",
            "qualitative", "resilience", "significant", "terminology", "unification", "verification", "wavelength", "xenon", "yield", "zenith"
    ];

    $imageLibrary = [];
    foreach ($manualWords as $word) {
        $wordLower = strtolower($word);
        $localPath = "../upload/" . $wordLower . ".jpg";
        if (file_exists($localPath)) {
            // filemtime para laging updated ang image kahit palitan mo sa folder
            $imageLibrary[$wordLower] = $localPath . "?v=" . filemtime($localPath);
        } else {
            // Fallback image kung hindi mo pa na-uupload
            $imageLibrary[$wordLower] = "https://via.placeholder.com/400?text=" . $wordLower;
        }
    }
    ?>
    <script>
        const STUDENT_ID = <?php echo json_encode($student_id); ?>;
        const USERNAME = <?php echo json_encode($username); ?>;
        const PLAY_WELCOME_VOICE = <?php echo json_encode($play_welcome_voice); ?>;
        const imageLibrary = <?php echo json_encode($imageLibrary); ?>;

        const wordBank = {
            beginner: [
                { word: "sun", phonemes: ["s","ʌ","n"], example: "The sun is very bright today." },
                { word: "moon", phonemes: ["m","uː","n"], example: "The moon shines at night." },
                { word: "star", phonemes: ["s","t","ɑː","r"], example: "Look at the shining star." },
            ]
        };

        const wordDisplay = document.getElementById("wordDisplay");
        const phonemeDisplay = document.getElementById("phonemeDisplay");
        const feedbackMessage = document.getElementById("feedbackMessage");
        const nextBtn = document.getElementById("nextBtn");
        const difficultySelect = document.getElementById("difficulty");
        const levelDisplay = document.getElementById("levelDisplay");
        const progressText = document.getElementById("progressText");
        const progressBar = document.querySelector(".progress-bar-inner");
        const attemptedCount = document.getElementById("attemptedCount");
        const accuracyRate = document.getElementById("accuracyRate");
        const micBtn = document.getElementById("micBtn");
        const statusEl = document.getElementById("status");
        const transcriptEl = document.getElementById("transcript");
        const ratingEl = document.getElementById("rating");
        const starsEl = document.getElementById("stars");
        const playWordBtn = document.getElementById("playWordBtn");
        const playFeedbackBtn = document.getElementById("playFeedbackBtn");
        const wordHistoryEl = document.getElementById("wordHistory");
        const historyMessageEl = document.getElementById("historyMessage");
        const canvas = document.getElementById("waveformCanvas");
        const ctx = canvas.getContext("2d");

        let allProgress = { beginner: { word_index: 0, words_attempted: 0, words_correct: 0 }, intermediate: { word_index: 0, words_attempted: 0, words_correct: 0 }, advanced: { word_index: 0, words_attempted: 0, words_correct: 0 } };
        let currentDifficulty = 'beginner';
        let currentWord = null;
        const DISPLAY_CYCLE_LENGTH = 10;
        let wordAttemptsHistory = [];
        let recognition, audioContext, analyser, dataArray, bufferLength, source;
        let animationId = null;
        let listening = false;
        let voicesLoaded = false;
        let usEnglishVoice = null;

        function getUsEnglishVoice() { if (usEnglishVoice) return usEnglishVoice; const voices = speechSynthesis.getVoices(); usEnglishVoice = voices.find(v => v.lang === "en-US" && v.name.includes("Google")) || voices.find(v => v.lang === "en-US"); return usEnglishVoice; }
        function speakWelcome() { if (!("speechSynthesis" in window)) return; const welcomeMessage = `Hello, ${USERNAME}! Welcome to Verbal Practice. Select a level and click 'Next Word' to begin.`; const utter = new SpeechSynthesisUtterance(welcomeMessage); utter.lang = "en-US"; utter.rate = 1.0; const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; speechSynthesis.speak(utter); }

        if ("speechSynthesis" in window) {
            speechSynthesis.onvoiceschanged = () => { if (!voicesLoaded) { getUsEnglishVoice(); voicesLoaded = true; if (PLAY_WELCOME_VOICE) speakWelcome(); } };
            if (speechSynthesis.getVoices().length > 0) { if (!voicesLoaded) { getUsEnglishVoice(); voicesLoaded = true; if (PLAY_WELCOME_VOICE) speakWelcome(); } }
        }

        function triggerPerfectScoreEffect() { const starElements = document.querySelectorAll('#stars .fa-star'); starElements.forEach((star, index) => { setTimeout(() => { star.classList.add('perfect-score-star'); }, index * 100); }); for (let i = 0; i < 100; i++) { createConfettiPiece(); } setTimeout(() => { starElements.forEach(star => star.classList.remove('perfect-score-star')); }, 4000); }
        function createConfettiPiece() { const colors = ['#3b82f6', '#10b981', '#fbbf24', '#ef4444', '#f472b6', '#ffffff']; const confetti = document.createElement('div'); confetti.className = 'confetti'; confetti.style.left = Math.random() * 100 + 'vw'; confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)]; confetti.style.width = (Math.random() * 8 + 5) + 'px'; confetti.style.height = (Math.random() * 8 + 5) + 'px'; const duration = Math.random() * 3 + 2; confetti.style.animation = `fall ${duration}s linear forwards`; document.body.appendChild(confetti); setTimeout(() => confetti.remove(), duration * 1000); }

        function updateRating(score) { const ratingText = document.getElementById('rating'); const starsContainer = document.getElementById('stars'); ratingText.innerText = score; starsContainer.innerHTML = ''; for (let i = 1; i <= 5; i++) { const starIcon = document.createElement('i'); starIcon.className = i <= score ? 'fa-solid fa-star star filled-star' : 'fa-regular fa-star star empty-star'; starsContainer.appendChild(starIcon); } if (score === 5) triggerPerfectScoreEffect(); }

        function renderWordHistory() { if (!currentWord) return; if (wordAttemptsHistory.length === 0) { wordHistoryEl.innerHTML = `No attempts yet for <b>${currentWord.word.toUpperCase()}</b>.`; historyMessageEl.textContent = ""; return; } wordHistoryEl.innerHTML = ""; const reversedHistory = [...wordAttemptsHistory].reverse(); const totalAttempts = wordAttemptsHistory.length; reversedHistory.forEach((score, index) => { const item = document.createElement("span"); item.className = `history-item`; const attemptNumber = totalAttempts - index; const attemptLabel = document.createElement("span"); attemptLabel.className = "attempt-label"; attemptLabel.textContent = `Attempt ${attemptNumber}:`; item.appendChild(attemptLabel); for (let i = 1; i <= 5; i++) { const star = document.createElement("i"); star.className = "fa-star fa-solid star"; i <= score ? star.classList.add("filled-star") : star.classList.add("empty-star"); star.style.animationDelay = `${(i - 1) * 0.1}s`; item.appendChild(star); } wordHistoryEl.appendChild(item); }); historyMessageEl.textContent = wordAttemptsHistory.includes(5) ? `✅ Great! You achieved 5 stars for "${currentWord.word.toUpperCase()}"!` : `Keep going! Aim for a 5-star score.`; historyMessageEl.style.color = wordAttemptsHistory.includes(5) ? "#10b981" : "#1e40af"; }

        function updateUIFromProgress() { const progress = allProgress[currentDifficulty]; attemptedCount.textContent = progress.words_attempted; const accuracy = progress.words_attempted > 0 ? Math.round((progress.words_correct / progress.words_attempted) * 100) : 0; accuracyRate.textContent = accuracy + "%"; levelDisplay.textContent = currentDifficulty.charAt(0).toUpperCase() + currentDifficulty.slice(1); const currentProgress = progress.words_correct % DISPLAY_CYCLE_LENGTH; const displayCount = currentProgress === 0 && progress.words_correct > 0 ? DISPLAY_CYCLE_LENGTH : currentProgress; const progressPercent = (displayCount / DISPLAY_CYCLE_LENGTH) * 100; progressText.textContent = `${displayCount} / ${DISPLAY_CYCLE_LENGTH}`; progressBar.style.width = progressPercent + "%"; }

        function loadProgressFromDB() { if (!STUDENT_ID) { updateUIFromProgress(); loadNextWord(); return; } fetch(`../load_all_progress.php?student_id=${STUDENT_ID}`).then(response => response.json()).then(data => { if (data.success && data.progress) { data.progress.forEach(p => { if (allProgress[p.difficulty]) { allProgress[p.difficulty].word_index = parseInt(p.word_index) || 0; allProgress[p.difficulty].words_attempted = parseInt(p.words_attempted) || 0; allProgress[p.difficulty].words_correct = parseInt(p.words_correct) || 0; } }); const lastDifficulty = data.last_difficulty || 'beginner'; if (wordBank[lastDifficulty]) { difficultySelect.value = lastDifficulty; currentDifficulty = lastDifficulty; } } updateUIFromProgress(); loadNextWord(); }).catch(e => { updateUIFromProgress(); loadNextWord(); }); }

        function saveProgressToDB() { if (!STUDENT_ID) return; const progress = allProgress[currentDifficulty]; const data = new URLSearchParams(); data.append('student_id', STUDENT_ID); data.append('difficulty', currentDifficulty); data.append('word_index', progress.word_index); data.append('words_attempted', progress.words_attempted); data.append('words_correct', progress.words_correct); fetch('../save_progress.php', { method: 'POST', body: data }).then(r => r.json()).then(res => console.log(res.message)).catch(e => console.error(e)); }

        function updateDifficulty() { saveProgressToDB(); currentDifficulty = difficultySelect.value; updateUIFromProgress(); loadNextWord(); }

        function init() {
            difficultySelect.addEventListener("change", updateDifficulty);
            nextBtn.addEventListener("click", loadNextWord);
            playWordBtn.addEventListener("click", () => { if (currentWord) speakWord(currentWord.word); });
            playFeedbackBtn.addEventListener("click", () => { const textToSpeak = feedbackMessage.textContent.startsWith("👌 Good try!") ? currentWord.example : feedbackMessage.textContent.startsWith("❌ Try again.") ? "Please listen to the word." : feedbackMessage.textContent; speakFeedback(textToSpeak); });
            micBtn.addEventListener("click", toggleMic);
            if ("webkitSpeechRecognition" in window) {
                recognition = new webkitSpeechRecognition(); recognition.continuous = false; recognition.interimResults = true; recognition.lang = "en-US";
                recognition.onresult = (event) => {
                    let interimTranscript = '', finalTranscript = '', confidence = 0, hasTargetWord = false;
                    const targetWordLower = currentWord.word.toLowerCase();
                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        const transcript = event.results[i][0].transcript.trim().toLowerCase();
                        if (event.results[i].isFinal) { finalTranscript += (finalTranscript.length > 0 ? ' ' : '') + transcript; confidence = event.results[i][0].confidence || confidence; if (finalTranscript.includes(targetWordLower)) hasTargetWord = true; }
                        else { interimTranscript += (interimTranscript.length > 0 ? ' ' : '') + transcript; }
                    }
                    transcriptEl.textContent = `${finalTranscript || interimTranscript} ...`;
                    if (hasTargetWord || event.results[event.results.length - 1].isFinal) {
                        if (finalTranscript) checkPronunciation(finalTranscript, confidence);
                        else statusEl.textContent = "Did not catch a full word. Try again.";
                        if (listening) { recognition.stop(); listening = false; micBtn.classList.remove("listening"); stopWaveform(); }
                    }
                };
                recognition.onerror = (e) => { statusEl.textContent = "Error: " + e.error; micBtn.classList.remove("listening"); listening = false; stopWaveform(); nextBtn.disabled = false; };
                recognition.onend = () => { if (statusEl.textContent === "Processing...") statusEl.textContent = "Click the big circle to start!"; micBtn.classList.remove("listening"); listening = false; };
            } else { statusEl.textContent = "Speech Recognition not supported."; }
            loadProgressFromDB();
        }

        function toggleMic() { if (!currentWord || wordDisplay.textContent === "Ready to begin") return alert("Please click 'Next Word!' to start practicing."); if (!recognition) return; if ("speechSynthesis" in window) speechSynthesis.cancel(); if (wordAttemptsHistory.includes(5)) return alert("You have already achieved 5 stars for this word."); listening = !listening; micBtn.classList.toggle("listening", listening); if (listening) { statusEl.textContent = "Listening..."; transcriptEl.textContent = "Listening..."; nextBtn.disabled = true; recognition.start(); startWaveform(); } else { statusEl.textContent = "Processing..."; recognition.stop(); } }

        function loadNextWord() {
            const wordList = wordBank[currentDifficulty];
            let progress = allProgress[currentDifficulty];
            if (progress.word_index >= wordList.length) { progress.word_index = 0; saveProgressToDB(); }
            currentWord = wordList[Math.floor(Math.random() * wordList.length)];

            const imgElement = document.getElementById("wordImage");
            const imageBox = document.getElementById("imageBox");
            const spinner = document.getElementById("imageSpinner");

            if (imgElement) {
                const targetWord = currentWord.word.toLowerCase();
                const newSrc = imageLibrary[targetWord] || "https://via.placeholder.com/120?text=No+Image";
                if (spinner) spinner.style.display = "block";
                imgElement.style.opacity = "0.3";
                imgElement.onload = () => {
                    if (spinner) spinner.style.display = "none";
                    imgElement.style.opacity = "1";
                    imgElement.classList.remove("img-error");
                    if (imageBox) imageBox.style.backgroundImage = `url('${imgElement.src}')`;
                };
                imgElement.onerror = () => {
                    if (spinner) spinner.style.display = "none";
                    imgElement.src = "https://cdn-icons-png.flaticon.com/512/107/107817.png";
                    imgElement.classList.add("img-error");
                    if (imageBox) imageBox.style.backgroundImage = `url('${imgElement.src}')`;
                };
                imgElement.src = newSrc;
            }

            wordAttemptsHistory = [];
            renderWordHistory();
            wordDisplay.textContent = currentWord.word;
            phonemeDisplay.textContent = currentWord.phonemes.join("·");
            feedbackMessage.textContent = "Read the word aloud when ready.";
            feedbackMessage.className = "feedback-message bg-initial-feedback";
            transcriptEl.textContent = "...";
            ratingEl.textContent = "0";
            renderStars(0);
            nextBtn.disabled = true;
            updateUIFromProgress();
        }

        const wordImageEl = document.getElementById('wordImage');
        const imageBoxEl = document.getElementById('imageBox');
        const observer = new MutationObserver(() => { imageBoxEl.style.backgroundImage = `url('${wordImageEl.src}')`; });
        observer.observe(wordImageEl, { attributes: true, attributeFilter: ['src'] });

        function speakRating(score, callback) { if (!("speechSynthesis" in window)) { if (callback) callback(); return; } speechSynthesis.cancel(); let message = score === 5 ? "Excellent! You got 5 stars!" : score >= 4 ? `Great job! You achieved ${score} stars.` : score >= 2 ? `You got ${score} stars. Try again.` : `Your score is ${score} stars. Keep practicing!`; const utter = new SpeechSynthesisUtterance(message); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } if (callback) { utter.onend = callback; utter.onerror = callback; } speechSynthesis.speak(utter); }
        function speakCompletionMessage() { if (!("speechSynthesis" in window)) return; const message = `Congratulations, you completed ${DISPLAY_CYCLE_LENGTH} words!`; const utter = new SpeechSynthesisUtterance(message); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }

        function checkPronunciation(spoken, confidence = 1) {
            let progress = allProgress[currentDifficulty];
            const { score } = ratePronunciation(spoken, currentWord.word, currentWord.phonemes);
            let isNewPass = false;
            if (!wordAttemptsHistory.includes(5) || score < 5) { wordAttemptsHistory.push(score); renderWordHistory(); }
            if (score >= 4) {
                if (!wordAttemptsHistory.slice(0, -1).some(s => s >= 4)) { isNewPass = true; progress.words_correct++; progress.words_attempted++; if (progress.word_index < wordBank[currentDifficulty].length) progress.word_index++; }
                else if (wordAttemptsHistory.filter(s => s === score).length === 1) { progress.words_attempted++; }
            } else if (wordAttemptsHistory.filter(s => s === score).length === 1) { progress.words_attempted++; }
            saveProgressToDB();
            ratingEl.textContent = score; renderStars(score);
            feedbackMessage.className = "feedback-message";
            const isCycleComplete = (progress.words_correct % DISPLAY_CYCLE_LENGTH) === 0 && progress.words_correct > 0 && isNewPass;
            if (score >= 4) { isCycleComplete ? speakRating(score, speakCompletionMessage) : speakRating(score); feedbackMessage.textContent = `${currentWord.example}`; feedbackMessage.classList.add("bg-green-100", "text-green-800"); }
            else if (score === 3) { speakRating(score); feedbackMessage.textContent = `👌 Good try! Try saying it in this sentence: ${currentWord.example}`; feedbackMessage.classList.add("bg-blue-100", "text-blue-800"); }
            else { speakRating(score); feedbackMessage.textContent = `❌ Try again. Focus on the sounds: ${currentWord.phonemes.join(" - ")}`; feedbackMessage.classList.add("bg-red-100", "text-red-800"); }
            updateUIFromProgress();
            saveRatingToDB(currentWord.word, score);
            nextBtn.disabled = false;
        }

        function saveRatingToDB(word, score) { if (!STUDENT_ID || !USERNAME) return; const data = new URLSearchParams(); data.append('student_id', STUDENT_ID); data.append('username', USERNAME); data.append('word', word); data.append('score', score); fetch('../save_rating.php', { method: 'POST', body: data }).then(r => r.json()).then(res => console.log(res.message)).catch(e => console.error(e)); }

        async function startWaveform() { try { const stream = await navigator.mediaDevices.getUserMedia({ audio: true }); audioContext = new (window.AudioContext || window.webkitAudioContext)(); analyser = audioContext.createAnalyser(); source = audioContext.createMediaStreamSource(stream); source.mediaStream = stream; source.connect(analyser); analyser.fftSize = 256; bufferLength = analyser.frequencyBinCount; dataArray = new Uint8Array(bufferLength); canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; drawLandscapeWave(); } catch (err) { console.error(err); } }

        function drawLandscapeWave() { if (!listening) { stopWaveform(); return; } animationId = requestAnimationFrame(drawLandscapeWave); analyser.getByteFrequencyData(dataArray); const WIDTH = canvas.width, HEIGHT = canvas.height, barWidth = (WIDTH / bufferLength) * 1.5; ctx.clearRect(0, 0, WIDTH, HEIGHT); let x = 0; for (let i = 0; i < bufferLength; i++) { const barHeight = (dataArray[i] / 255) * HEIGHT; ctx.fillStyle = `hsl(${200 + (dataArray[i]/255)*100}, 90%, 60%)`; ctx.fillRect(x, HEIGHT - barHeight, barWidth, barHeight); x += barWidth + 1; } }

        function stopWaveform() { if (animationId) cancelAnimationFrame(animationId); if (source && source.mediaStream) source.mediaStream.getTracks().forEach(t => t.stop()); if (audioContext && audioContext.state !== 'closed') audioContext.close(); ctx.clearRect(0, 0, canvas.width, canvas.height); }
        function speakWord(word) { if (!("speechSynthesis" in window)) return; speechSynthesis.cancel(); const utter = new SpeechSynthesisUtterance(word); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }
        function speakFeedback(text) { if (!("speechSynthesis" in window)) return; speechSynthesis.cancel(); const utter = new SpeechSynthesisUtterance(text); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }
        function renderStars(score) { starsEl.innerHTML = ""; for (let i = 1; i <= 5; i++) { const star = document.createElement("i"); star.className = i <= score ? "fa-star fa-solid star filled-star" : "fa-star fa-solid star empty-star"; starsEl.appendChild(star); } }

        function ratePronunciation(spoken, target, targetPhonemes) { spoken = spoken.toLowerCase().trim(); target = target.toLowerCase().trim(); const phonemeMap = { a: ["æ"], b: ["b"], c: ["k"], d: ["d"], e: ["ɛ"], f: ["f"], g: ["g"], h: ["h"], i: ["ɪ"], j: ["dʒ"], k: ["k"], l: ["l"], m: ["m"], n: ["n"], o: ["ɒ"], p: ["p"], q: ["k"], r: ["ɹ"], s: ["s"], t: ["t"], u: ["ʌ"], v: ["v"], w: ["w"], x: ["ks"], y: ["j"], z: ["z"] }; const guess = []; for (let i=0; i<spoken.length; i++) { if (phonemeMap[spoken[i]]) guess.push(phonemeMap[spoken[i]][0]); } function levenshtein(a, b) { const matrix = Array.from({ length: a.length + 1 }, () => []); for (let i = 0; i <= a.length; i++) matrix[i][0] = i; for (let j = 0; j <= b.length; j++) matrix[0][j] = j; for (let i = 1; i <= a.length; i++) { for (let j = 1; j <= b.length; j++) { if (a[i - 1] === b[j - 1]) matrix[i][j] = matrix[i - 1][j - 1]; else matrix[i][j] = Math.min(matrix[i - 1][j] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j - 1] + 1.3); } } return matrix[a.length][b.length]; } const dist = levenshtein(guess.join(""), targetPhonemes.join("")); const sim = 1 - dist / Math.max(targetPhonemes.length, guess.length); let score = sim > 0.96 ? 5 : sim > 0.85 ? 4 : sim > 0.70 ? 3 : sim > 0.50 ? 2 : sim > 0.25 ? 1 : 0; return { score, feedback: "" }; }

        window.onload = init;
    </script>
</main>
</body>
</html>