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

            /* --- Mid Section (NO SCROLLBAR & CENTERED) --- */
            .section {
                flex: 1.2;
                padding: 10px; border-radius: 15px; background-color: #FFF9EB;
                display: flex; flex-direction: column; border: 2px solid #FFEAA7;
                align-items: center; text-align: center;
                overflow: visible; /* Mahalaga para sa Pop-up */
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

            /* --- FIXED CENTER POP-UP EFFECT --- */
            .word-image-box {
                width: 100px;
                height: 100px;
                border: 3px solid var(--kids-yellow);
                border-radius: 12px;
                background: white;
                position: relative;
                background-size: cover;
                background-position: center;
                cursor: zoom-in;
            }

            #wordImage { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

            .word-image-box:hover::after {
                content: "";
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(1);
                width: 250px;
                height: 250px;
                background-image: inherit;
                background-size: cover;
                background-position: center;
                background-color: white;
                z-index: 9999;
                box-shadow: 0 0 0 100vmax rgba(0,0,0,0.6), 0 15px 40px rgba(0,0,0,0.5);
                border: 6px solid white;
                border-radius: 20px;
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

            /* --- Rating & Stars (Same Line) --- */
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

            /* --- Waveform (Aligned) --- */
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

            /* --- Stats & Progress (HABA ADJUSTED TO 98%) --- */
            .stats-container {
                display: flex;
                justify-content: space-around;
                background: white;
                padding: 15px 5px;
                border-radius: 15px;
                border: 2px solid #FFEAA7;
                margin: 10px auto;
                width: 98%; /* Lakihan ang haba */
            }
            .stat-item { text-align: center; flex: 1; }
            .stat-item span {
                display: block;
                font-size: 0.9rem;
                color: #0984E3;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .stat-item .value {
                font-size: 2.2rem;
                font-weight: 900;
                color: #F1C40F;
                line-height: 1;
            }

            .progress-bar {
                width: 98%; /* Pantay sa stats container */
                height: 15px;
                background: #eee;
                border-radius: 10px;
                margin: 10px 0;
                overflow: hidden;
                border: 1px solid #ddd;
            }
            .progress-bar-inner { height: 100%; background: var(--success); transition: width 0.3s ease; }

            /* --- Others & Logic --- */
            button { padding: 8px 15px; border-radius: 30px; border: none; font-weight: 800; cursor: pointer; font-size: 0.85rem; }
            .btn-primary { background: var(--kids-yellow); color: #856404; box-shadow: 0 3px 0 #E1B12C; }
            .btn-secondary { background: var(--kids-blue); color: white; box-shadow: 0 3px 0 #0A82C5; }

            .filled-star { color: #FFC300 !important; text-shadow: 0 0 5px rgba(255, 195, 0, 0.5); }
            .empty-star { color: #ccc !important; }
            .history-item { display: block; margin: 5px 0; font-size: 0.9rem; border-bottom: 1px solid #eee; padding-bottom: 3px; }
            .attempt-label { font-weight: bold; margin-right: 5px; color: #636e72; }

            .confetti { position: fixed; top: -10px; z-index: 9999; pointer-events: none; border-radius: 2px; }
            @keyframes fall { to { transform: translateY(100vh) rotate(360deg); opacity: 0; } }

            @keyframes pulse { 0% { transform: scale(1); } 70% { transform: scale(1.1); box-shadow: 0 0 20px rgba(214, 48, 49, 0.5); } 100% { transform: scale(1); } }
            @keyframes starPop {
                0% { transform: scale(0); opacity: 0; }
                60% { transform: scale(1.3); }
                100% { transform: scale(1); opacity: 1; }
            }
            .perfect-score-star { animation: perfect-score-star 0.6s ease-in-out; }

            body.dark-mode { background: #2d3436; color: white; }
            body.dark-mode .main-container { background: #353b48; border-color: #2f3640; box-shadow: 0 8px 0px #2f3640; }
            body.dark-mode .section { background-color: #2f3640; border-color: #1e272e; color: #f5f6fa; }
        </style>
    </head>
    <body>
    <header class="page-header">
        <button id="themeToggle" title="Toggle Light/Dark Mode">
            <i class="fa-solid fa-moon"></i>
        </button>
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
                "sun", "moon", "star", "rain", "tree",
                "bird", "fish", "cat", "dog", "cow",
                "pig", "ant", "bee", "frog", "duck",
                "apple", "cake", "milk", "egg", "cup",
                "hat", "bag", "pen", "book", "box",
                "ball", "toy", "car", "bus", "bike",
                "bed", "door", "home", "farm", "park",
                "hand", "foot", "eye", "nose", "ear",
                "red", "blue", "pink", "leaf", "rock",
                "boat", "kite", "key", "ring", "bell",
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
            // --- 1. Constants and Data ---
            const STUDENT_ID = <?php echo json_encode($student_id); ?>;
            const USERNAME = <?php echo json_encode($username); ?>;
            const PLAY_WELCOME_VOICE = <?php echo json_encode($play_welcome_voice); ?>;
            const imageLibrary = <?php echo json_encode($imageLibrary); ?>;

            const wordBank = {
                beginner: [
                    { word: "sun", phonemes: ["s","ʌ","n"], example: "The sun is bright." },
                    { word: "moon", phonemes: ["m","uː","n"], example: "The moon is up." },
                    { word: "star", phonemes: ["s","t","ɑː","r"], example: "I see a star." },
                    { word: "rain", phonemes: ["r","eɪ","n"], example: "The rain falls." },
                    { word: "tree", phonemes: ["t","r","iː"], example: "The tree is tall." },
                ],
                intermediate: [],
                advanced: []
            };

            // --- 2. UI Elements ---
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            const icon = themeToggle.querySelector('i');
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

            // --- 3. State Management ---
            let allProgress = {
                beginner: { word_index: 0, words_attempted: 0, words_correct: 0 },
                intermediate: { word_index: 0, words_attempted: 0, words_correct: 0 },
                advanced: { word_index: 0, words_attempted: 0, words_correct: 0 }
            };
            let currentDifficulty = 'beginner';
            let currentWord = null;
            const DISPLAY_CYCLE_LENGTH = 10;
            let wordAttemptsHistory = [];
            let recognition, audioContext, analyser, dataArray, bufferLength, source;
            let animationId = null;
            let listening = false;
            let voicesLoaded = false;
            let usEnglishVoice = null;

            // --- 4. Theme Logic ---
            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
                icon.classList.replace('fa-moon', 'fa-sun');
            }

            themeToggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    icon.classList.replace('fa-moon', 'fa-sun');
                    localStorage.setItem('theme', 'dark');
                } else {
                    icon.classList.replace('fa-sun', 'fa-moon');
                    localStorage.setItem('theme', 'light');
                }
            });

            // --- 5. Voice/Speech Synthesis Logic ---
            function getUsEnglishVoice() {
                if (usEnglishVoice) return usEnglishVoice;
                const voices = speechSynthesis.getVoices();
                usEnglishVoice = voices.find(v => v.lang === "en-US" && v.name.includes("Google")) || voices.find(v => v.lang === "en-US");
                return usEnglishVoice;
            }

            function speakWelcome() {
                if (!("speechSynthesis" in window)) return;
                const welcomeMessage = `Hello, ${USERNAME}! Welcome to Verbal Practice. Select a level and click 'Next Word' to begin.`;
                const utter = new SpeechSynthesisUtterance(welcomeMessage);
                utter.lang = "en-US";
                utter.rate = 1.0;
                const usVoice = getUsEnglishVoice();
                if (usVoice) utter.voice = usVoice;
                speechSynthesis.speak(utter);
            }

            if ("speechSynthesis" in window) {
                speechSynthesis.onvoiceschanged = () => { if (!voicesLoaded) { getUsEnglishVoice(); voicesLoaded = true; if (PLAY_WELCOME_VOICE) speakWelcome(); } };
                if (speechSynthesis.getVoices().length > 0) { if (!voicesLoaded) { getUsEnglishVoice(); voicesLoaded = true; if (PLAY_WELCOME_VOICE) speakWelcome(); } }
            }

            // --- 6. Effects & UI Updates ---
            function triggerPerfectScoreEffect() {
                const starElements = document.querySelectorAll('#stars .fa-star');
                starElements.forEach((star, index) => { setTimeout(() => { star.classList.add('perfect-score-star'); }, index * 100); });
                for (let i = 0; i < 100; i++) { createConfettiPiece(); }
                setTimeout(() => { starElements.forEach(star => star.classList.remove('perfect-score-star')); }, 4000);
            }

            function createConfettiPiece() {
                const colors = ['#3b82f6', '#10b981', '#fbbf24', '#ef4444', '#f472b6', '#ffffff'];
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = (Math.random() * 8 + 5) + 'px';
                confetti.style.height = (Math.random() * 8 + 5) + 'px';
                const duration = Math.random() * 3 + 2;
                confetti.style.animation = `fall ${duration}s linear forwards`;
                document.body.appendChild(confetti);
                setTimeout(() => confetti.remove(), duration * 1000);
            }

            function renderWordHistory() {
                if (!currentWord) return;
                if (wordAttemptsHistory.length === 0) {
                    wordHistoryEl.innerHTML = `No attempts yet for <b>${currentWord.word.toUpperCase()}</b>.`;
                    historyMessageEl.textContent = "";
                    return;
                }
                wordHistoryEl.innerHTML = "";
                const reversedHistory = [...wordAttemptsHistory].reverse();
                const totalAttempts = wordAttemptsHistory.length;
                reversedHistory.forEach((score, index) => {
                    const item = document.createElement("span");
                    item.className = `history-item`;
                    const attemptNumber = totalAttempts - index;
                    const attemptLabel = document.createElement("span");
                    attemptLabel.className = "attempt-label";
                    attemptLabel.textContent = `Attempt ${attemptNumber}:`;
                    item.appendChild(attemptLabel);
                    for (let i = 1; i <= 5; i++) {
                        const star = document.createElement("i");
                        star.className = "fa-star fa-solid star";
                        i <= score ? star.classList.add("filled-star") : star.classList.add("empty-star");
                        star.style.animationDelay = `${(i - 1) * 0.1}s`;
                        item.appendChild(star);
                    }
                    wordHistoryEl.appendChild(item);
                });
                historyMessageEl.textContent = wordAttemptsHistory.includes(5) ? `✅ Great! You achieved 5 stars for "${currentWord.word.toUpperCase()}"!` : `Keep going! Aim for a 5-star score.`;
                historyMessageEl.style.color = wordAttemptsHistory.includes(5) ? "#10b981" : "#1e40af";
            }

            function updateUIFromProgress() {
                const progress = allProgress[currentDifficulty];
                attemptedCount.textContent = progress.words_attempted;
                const accuracy = progress.words_attempted > 0 ? Math.round((progress.words_correct / progress.words_attempted) * 100) : 0;
                accuracyRate.textContent = accuracy + "%";
                levelDisplay.textContent = currentDifficulty.charAt(0).toUpperCase() + currentDifficulty.slice(1);
                const currentProgress = progress.words_correct % DISPLAY_CYCLE_LENGTH;
                const displayCount = currentProgress === 0 && progress.words_correct > 0 ? DISPLAY_CYCLE_LENGTH : currentProgress;
                const progressPercent = (displayCount / DISPLAY_CYCLE_LENGTH) * 100;
                progressText.textContent = `${displayCount} / ${DISPLAY_CYCLE_LENGTH}`;
                progressBar.style.width = progressPercent + "%";
            }

            // --- 7. Data Persistence ---
            function loadProgressFromDB() {
                if (!STUDENT_ID) { updateUIFromProgress(); loadNextWord(); return; }
                fetch(`../load_all_progress.php?student_id=${STUDENT_ID}`).then(response => response.json()).then(data => {
                    if (data.success && data.progress) {
                        data.progress.forEach(p => {
                            if (allProgress[p.difficulty]) {
                                allProgress[p.difficulty].word_index = parseInt(p.word_index) || 0;
                                allProgress[p.difficulty].words_attempted = parseInt(p.words_attempted) || 0;
                                allProgress[p.difficulty].words_correct = parseInt(p.words_correct) || 0;
                            }
                        });
                        const lastDifficulty = data.last_difficulty || 'beginner';
                        if (wordBank[lastDifficulty]) { difficultySelect.value = lastDifficulty; currentDifficulty = lastDifficulty; }
                    }
                    updateUIFromProgress(); loadNextWord();
                }).catch(e => { updateUIFromProgress(); loadNextWord(); });
            }

            function saveProgressToDB() {
                if (!STUDENT_ID) return;
                const progress = allProgress[currentDifficulty];
                const data = new URLSearchParams();
                data.append('student_id', STUDENT_ID);
                data.append('difficulty', currentDifficulty);
                data.append('word_index', progress.word_index);
                data.append('words_attempted', progress.words_attempted);
                data.append('words_correct', progress.words_correct);
                fetch('../save_progress.php', { method: 'POST', body: data }).then(r => r.json()).then(res => console.log(res.message)).catch(e => console.error(e));
            }

            function saveRatingToDB(word, score) {
                if (!STUDENT_ID || !USERNAME) return;
                const data = new URLSearchParams();
                data.append('student_id', STUDENT_ID);
                data.append('username', USERNAME);
                data.append('word', word);
                data.append('score', score);
                fetch('../save_rating.php', { method: 'POST', body: data }).then(r => r.json()).then(res => console.log(res.message)).catch(e => console.error(e));
            }

            // --- 8. Core Functionality (Mic, Word Loading, Pronunciation) ---
            function updateDifficulty() { saveProgressToDB(); currentDifficulty = difficultySelect.value; updateUIFromProgress(); loadNextWord(); }

            function toggleMic() {
                if (!currentWord || wordDisplay.textContent === "Ready to begin") return alert("Please click 'Next Word!' to start practicing.");
                if (!recognition) return;
                if ("speechSynthesis" in window) speechSynthesis.cancel();
                if (wordAttemptsHistory.includes(5)) return alert("You have already achieved 5 stars for this word.");
                listening = !listening;
                micBtn.classList.toggle("listening", listening);
                if (listening) {
                    statusEl.textContent = "Listening...";
                    transcriptEl.textContent = "Listening...";
                    nextBtn.disabled = true;
                    recognition.start();
                    startWaveform();
                } else {
                    statusEl.textContent = "Processing...";
                    recognition.stop();
                }
            }

            function loadNextWord() {
                const wordList = wordBank[currentDifficulty];
                if (!wordList || wordList.length === 0) return;

                let progress = allProgress[currentDifficulty];
                if (progress.word_index >= wordList.length) { progress.word_index = 0; saveProgressToDB(); }

                currentWord = wordList[Math.floor(Math.random() * wordList.length)];

                const imgElement = document.getElementById("wordImage");
                const imageBox = document.getElementById("imageBox");
                const spinner = document.getElementById("imageSpinner");

                if (imgElement) {
                    const targetWord = currentWord.word.toLowerCase();
                    const newSrc = imageLibrary[targetWord] || "https://via.placeholder.com/120?text=No+Image";

                    // --- IN-ADD PARA SA FIXED POP-UP EFFECT ---
                    if (imageBox) imageBox.style.backgroundImage = `url('${newSrc}')`;
                    // ------------------------------------------

                    if (spinner) spinner.style.display = "block";
                    imgElement.style.opacity = "0.3";
                    imgElement.onload = () => {
                        if (spinner) spinner.style.display = "none";
                        imgElement.style.opacity = "1";
                        imgElement.classList.remove("img-error");
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

            function ratePronunciation(spoken, target, targetPhonemes) {
                spoken = spoken.toLowerCase().trim();
                target = target.toLowerCase().trim();
                const phonemeMap = { a: ["æ"], b: ["b"], c: ["k"], d: ["d"], e: ["ɛ"], f: ["f"], g: ["g"], h: ["h"], i: ["ɪ"], j: ["dʒ"], k: ["k"], l: ["l"], m: ["m"], n: ["n"], o: ["ɒ"], p: ["p"], q: ["k"], r: ["ɹ"], s: ["s"], t: ["t"], u: ["ʌ"], v: ["v"], w: ["w"], x: ["ks"], y: ["j"], z: ["z"] };
                const guess = [];
                for (let i=0; i<spoken.length; i++) { if (phonemeMap[spoken[i]]) guess.push(phonemeMap[spoken[i]][0]); }
                function levenshtein(a, b) {
                    const matrix = Array.from({ length: a.length + 1 }, () => []);
                    for (let i = 0; i <= a.length; i++) matrix[i][0] = i;
                    for (let j = 0; j <= b.length; j++) matrix[0][j] = j;
                    for (let i = 1; i <= a.length; i++) {
                        for (let j = 1; j <= b.length; j++) {
                            if (a[i - 1] === b[j - 1]) matrix[i][j] = matrix[i - 1][j - 1];
                            else matrix[i][j] = Math.min(matrix[i - 1][j] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j - 1] + 1.3);
                        }
                    } return matrix[a.length][b.length];
                }
                const dist = levenshtein(guess.join(""), targetPhonemes.join(""));
                const sim = 1 - dist / Math.max(targetPhonemes.length, guess.length);
                let score = sim > 0.96 ? 5 : sim > 0.85 ? 4 : sim > 0.70 ? 3 : sim > 0.50 ? 2 : sim > 0.25 ? 1 : 0;
                return { score, feedback: "" };
            }

            // --- 9. Audio Visualizer & Speakers ---
            async function startWaveform() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    analyser = audioContext.createAnalyser();
                    source = audioContext.createMediaStreamSource(stream);
                    source.mediaStream = stream;
                    source.connect(analyser);
                    analyser.fftSize = 256;
                    bufferLength = analyser.frequencyBinCount;
                    dataArray = new Uint8Array(bufferLength);
                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                    drawLandscapeWave();
                } catch (err) { console.error(err); }
            }

            function drawLandscapeWave() {
                if (!listening) { stopWaveform(); return; }
                animationId = requestAnimationFrame(drawLandscapeWave);
                analyser.getByteFrequencyData(dataArray);
                const WIDTH = canvas.width, HEIGHT = canvas.height, barWidth = (WIDTH / bufferLength) * 1.5;
                ctx.clearRect(0, 0, WIDTH, HEIGHT);
                let x = 0;
                for (let i = 0; i < bufferLength; i++) {
                    const barHeight = (dataArray[i] / 255) * HEIGHT;
                    ctx.fillStyle = `hsl(${200 + (dataArray[i]/255)*100}, 90%, 60%)`;
                    ctx.fillRect(x, HEIGHT - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }
            }

            function stopWaveform() {
                if (animationId) cancelAnimationFrame(animationId);
                if (source && source.mediaStream) source.mediaStream.getTracks().forEach(t => t.stop());
                if (audioContext && audioContext.state !== 'closed') audioContext.close();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }

            function speakWord(word) { if (!("speechSynthesis" in window)) return; speechSynthesis.cancel(); const utter = new SpeechSynthesisUtterance(word); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }
            function speakFeedback(text) { if (!("speechSynthesis" in window)) return; speechSynthesis.cancel(); const utter = new SpeechSynthesisUtterance(text); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }
            function speakRating(score, callback) { if (!("speechSynthesis" in window)) { if (callback) callback(); return; } speechSynthesis.cancel(); let message = score === 5 ? "Excellent! You got 5 stars!" : score >= 4 ? `Great job! You achieved ${score} stars.` : score >= 2 ? `You got ${score} stars. Try again.` : `Your score is ${score} stars. Keep practicing!`; const utter = new SpeechSynthesisUtterance(message); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } if (callback) { utter.onend = callback; utter.onerror = callback; } speechSynthesis.speak(utter); }
            function speakCompletionMessage() { if (!("speechSynthesis" in window)) return; const message = `Congratulations, you completed ${DISPLAY_CYCLE_LENGTH} words!`; const utter = new SpeechSynthesisUtterance(message); utter.lang = "en-US"; if (voicesLoaded) { const usVoice = getUsEnglishVoice(); if (usVoice) utter.voice = usVoice; } speechSynthesis.speak(utter); }

            function renderStars(score) { starsEl.innerHTML = ""; for (let i = 1; i <= 5; i++) { const star = document.createElement("i"); star.className = i <= score ? "fa-star fa-solid star filled-star" : "fa-star fa-solid star empty-star"; starsEl.appendChild(star); } }

            // --- 10. Initialization ---
            function init() {
                difficultySelect.addEventListener("change", updateDifficulty);
                nextBtn.addEventListener("click", loadNextWord);
                playWordBtn.addEventListener("click", () => { if (currentWord) speakWord(currentWord.word); });
                playFeedbackBtn.addEventListener("click", () => {
                    const textToSpeak = feedbackMessage.textContent.startsWith("👌 Good try!") ? currentWord.example : feedbackMessage.textContent.startsWith("❌ Try again.") ? "Please listen to the word." : feedbackMessage.textContent;
                    speakFeedback(textToSpeak);
                });
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

            window.onload = init;
        </script>
</main>
</body>
</html>