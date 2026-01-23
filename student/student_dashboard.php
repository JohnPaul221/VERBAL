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
            @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap');

            :root {
                /* Adventure Palette */
                --sky-blue: #70d6ff;       /* Bright sky */
                --cloud-white: #ffffff;
                --grass-green: #6bcb77;    /* Adventure green */
                --sun-yellow: #ffd93d;     /* Highlighting */
                --street-blue: #4d96ff;    /* Primary buttons */
                --text-main: #2b2d42;      /* Clear readability */
                --accent-orange: #ff6b6b;  /* Warning/Logout */
            }

            body {
                height: 100vh;
                background: linear-gradient(180deg, var(--sky-blue) 0%, #e0f2fe 100%);
                font-family: "Nunito", sans-serif;
                padding: 10px 20px;
                color: var(--text-main);
                margin: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                box-sizing: border-box;
            }

            /* Decorative clouds effect */
            body::after {
                content: "☁️";
                position: absolute;
                font-size: 100px;
                top: 50px;
                right: 10%;
                opacity: 0.2;
                z-index: -1;
            }

            .page-header {
                height: 50px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 5px;
            }

            #usernameDisplay {
                background: var(--cloud-white);
                padding: 6px 15px;
                border-radius: 20px;
                border: 3px solid var(--street-blue);
                color: var(--street-blue);
                font-weight: 900;
                box-shadow: 0 4px 0px rgba(77, 150, 255, 0.2);
            }

            #logoutBtn {
                background: var(--accent-orange);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 8px 15px;
                cursor: pointer;
                font-weight: 700;
                box-shadow: 0 4px 0px #d64545;
            }

            /* Main Container */
            .main-container {
                max-width: 98vw;
                margin: 0 auto;
                height: calc(100vh - 80px);
                display: flex;
                gap: 15px;
                background: rgba(255, 255, 255, 0.4);
                border-radius: 30px;
                padding: 15px;
                backdrop-filter: blur(8px);
                border: 6px solid var(--cloud-white);
                box-sizing: border-box;
            }

            /* Adventure Cards */
            .section, .history-section {
                flex: 1;
                padding: 15px;
                border-radius: 25px;
                background-color: var(--cloud-white);
                max-height: 100%;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                border: 2px solid #e2e8f0;
                box-shadow: 0 8px 15px rgba(0,0,0,0.05);
                box-sizing: border-box;
            }


            /* Remove scroll from the "Read the Word" section specifically */
            .section:nth-of-type(2) {
                overflow-y: hidden;
            }

            .divider {
                width: 4px;
                background: var(--cloud-white);
                border-radius: 10px;
                opacity: 0.5;
            }

            h2 {
                color: var(--street-blue);
                font-size: 1.3rem;
                margin-bottom: 12px;
                font-weight: 900;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            /* Word Box */
            .reading-material {
                background: #f0f9ff;
                padding: 12px; /* Slightly reduced padding */
                border-radius: 20px;
                border: 4px solid var(--sky-blue);
                margin-bottom: 10px;
                position: relative;
            }

            #wordDisplay {
                font-size: 2.3rem; /* Slightly reduced to save vertical space */
                color: var(--street-blue);
                text-align: center;
                font-weight: 900;
                text-shadow: 2px 2px 0px var(--cloud-white);
            }

            /* Playful Buttons */
            button {
                padding: 10px 20px;
                border-radius: 15px;
                border: none;
                font-weight: 900;
                cursor: pointer;
                transition: 0.2s;
            }

            .btn-primary {
                background: var(--accent-orange);
                color: white;
                box-shadow: 0 5px 0px #d64545;
            }

            .btn-secondary {
                background: var(--street-blue);
                color: white;
                box-shadow: 0 5px 0px #2a6fdb;
            }

            button:active {
                transform: translateY(4px);
                box-shadow: none;
            }

            /* Mic - Central Hub */
            .mic-container { width: 70px; height: 70px; margin: 10px auto; }
            .mic-icon {
                width: 100%; height: 100%;
                background-color: var(--street-blue);
                border: 5px solid var(--cloud-white);
                border-radius: 50%;
                display: flex; justify-content: center; align-items: center;
                color: white; font-size: 25px;
                box-shadow: 0 5px 15px rgba(77, 150, 255, 0.4);
            }

            .mic-icon.listening {
                background-color: var(--accent-orange);
                animation: pulse 1s infinite;
            }

            /* Stats & Progress */
            .progress-bar { height: 12px; background: #edf2f7; border-radius: 20px; margin-bottom: 10px; overflow: hidden; }
            .progress-bar-inner { background: var(--grass-green); height: 100%; transition: width 0.4s; }

            .stat-item .value { color: var(--sun-yellow); font-size: 1.6rem; font-weight: 900; text-shadow: 1px 1px 1px rgba(0,0,0,0.1); }

            /* Custom Scrollbar for other sections */
            .section::-webkit-scrollbar { width: 8px; }
            .section::-webkit-scrollbar-thumb { background: var(--sky-blue); border-radius: 10px; }

            @keyframes pulse {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
                70% { transform: scale(1.1); box-shadow: 0 0 0 15px rgba(255, 107, 107, 0); }
                100% { transform: scale(1); }
            }

            /* Scoreboard adjustments for fit */
            .stats-container {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .stat-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
        </style>
    </head>

    <body>
    <header class="page-header">
        <?php echo '<span id="usernameDisplay">Hello, ' . htmlspecialchars($username) . '!</span>'; ?>
        <button id="logoutBtn" onclick="window.location.href='../login.php';">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </button>
    </header>
    <main>
        <div class="main-container">

            <div class="section">
                <h2>Your Turn to Talk! 🎤</h2>

                <div class="flex-space-between mb-4">
                    <div>Level: <span id="levelDisplay" style="font-weight: bold; color: #10b981;">Beginner</span></div>
                    <div>Word: <span id="progressText" style="font-weight: bold; color: #10b981;">0 / 5</span></div>
                </div>

                <div class="progress-bar">
                    <div class="progress-bar-inner" style="width: 0%"></div>
                </div>

                <label for="difficulty" style="display: block; font-size: 1.2rem; font-weight: bold; margin-bottom: 8px;">Pick a Level:</label>
                <select id="difficulty">
                    <option value="beginner">🌟 Beginner (Easy Words)</option>
                    <option value="intermediate">👍 Intermediate (Medium Words)</option>
                    <option value="advanced">🧠 Advanced (Hard Words)</option>
                </select>

                <div class="mic-container">
                    <div id="micBtn" class="mic-icon"><i class="fa-solid fa-microphone"></i></div>
                </div>

                <div id="status" class="status">Click the big circle to start!</div>

                <div id="waveformContainer" class="waveform">
                    <canvas id="waveformCanvas"></canvas>
                </div>

                <div class="flex-center mt-4">
                    <button id="leaderboardBtn" class="btn-primary" onclick="window.location.href='../leaderboard.php';">
                        🏆 Leaderboard
                    </button>
                    <button id="nextBtn" class="btn-secondary" disabled>👉 Next Word!</button>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section">
                <h2>The Word to Read 📖</h2>

                <div class="reading-material">
                    <div id="wordDisplay">
                        Ready to begin
                    </div>
                    <div class="flex-center mt-4">
                        <button id="playWordBtn" class="btn-primary">
                            <i class="fa-solid fa-volume-high"></i> Listen to the Word
                        </button>
                    </div>
                    <div id="phonemeDisplay"></div>
                </div>

                <h3>You Said:</h3>
                <div class="transcript" id="transcript">...</div>

                <div class="rating-container">
                    <div class="rating" id="rating">0</div>
                    <div id="stars"></div>
                </div>

                <div class="feedback-scoreboard-container">
                    <div class="feedback-container">
                        <h3>Example Sentence! 📝</h3>
                        <div id="feedbackMessage" class="feedback-message bg-initial-feedback">
                            Practice sentence will appear here when you start.
                        </div>
                        <div class="flex-center mt-4">
                            <button id="playFeedbackBtn" class="btn-primary">
                                <i class="fa-solid fa-volume-high"></i> Listen to Sentence
                            </button>
                        </div>
                    </div>

                    <div class="scoreboard-container">
                        <hr style="border: 1px dashed #60a5fa; margin: 8px 0;">
                        <h3>My Scoreboard 🏆</h3>
                        <div class="stats-container">
                            <div class="stat-item">
                                <span>Words Attempted:</span>
                                <span id="attemptedCount" class="value">0</span>
                            </div>
                            <div class="stat-item">
                                <span>Accuracy:</span>
                                <span id="accuracyRate" class="value">0%</span>
                            </div>
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
        <script>
            const STUDENT_ID = <?php echo json_encode($student_id); ?>;
            const USERNAME = <?php echo json_encode($username); ?>;
            const PLAY_WELCOME_VOICE = <?php echo json_encode($play_welcome_voice); ?>;

            const wordBank = {
                beginner: [
                    { word: "cat", phonemes: ["k","æ","t"], example: "The cat is on the mat." },
                    { word: "dog", phonemes: ["d","ɒ","g"], example: "The dog can run fast." },
                    { word: "sun", phonemes: ["s","ʌ","n"], example: "The sun is hot today." },
                    { word: "pig", phonemes: ["p","ɪ","g"], example: "The pink pig is in the mud." },
                    { word: "bus", phonemes: ["b","ʌ","s"], example: "I ride the yellow bus to school." }
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

            function getUsEnglishVoice() {
                if (usEnglishVoice) return usEnglishVoice;

                const voices = speechSynthesis.getVoices();
                usEnglishVoice = voices.find(v => v.lang === "en-US" && v.name.includes("Google")) ||
                    voices.find(v => v.lang === "en-US");

                return usEnglishVoice;
            }

            function speakWelcome() {
                if (!("speechSynthesis" in window)) {
                    console.warn("Speech Synthesis not supported.");
                    return;
                }

                const welcomeMessage = `Hello, ${USERNAME}! Welcome to Verbal Practice. Select a level and click 'Next Word' to begin.`;
                const utter = new SpeechSynthesisUtterance(welcomeMessage);
                utter.lang = "en-US";
                utter.rate = 1.0;

                const usVoice = getUsEnglishVoice();
                if (usVoice) utter.voice = usVoice;

                speechSynthesis.speak(utter);
            }

            if ("speechSynthesis" in window) {
                speechSynthesis.onvoiceschanged = () => {
                    if (!voicesLoaded) {
                        getUsEnglishVoice();
                        voicesLoaded = true;
                        console.log("SpeechSynthesis voices loaded and ready (from onvoiceschanged).");
                        if (PLAY_WELCOME_VOICE) speakWelcome();
                    }
                };

                if (speechSynthesis.getVoices().length > 0) {
                    if (!voicesLoaded) {
                        getUsEnglishVoice();
                        voicesLoaded = true;
                        console.log("SpeechSynthesis voices loaded and ready (from initial check).");
                        if (PLAY_WELCOME_VOICE) speakWelcome();
                    }
                }
            }
            // Function para sa 5-star Celebration
            function triggerPerfectScoreEffect() {
                const starElements = document.querySelectorAll('#stars .fa-star');

                // 1. Gawing animated ang bawat bituin
                starElements.forEach((star, index) => {
                    setTimeout(() => {
                        star.classList.add('perfect-score-star');
                    }, index * 100); // Staggered entrance
                });

                // 2. Magpaulan ng Confetti
                for (let i = 0; i < 100; i++) {
                    createConfettiPiece();
                }

                // 3. Opsyonal: Alisin ang animation pagkatapos ng 4 na segundo
                setTimeout(() => {
                    starElements.forEach(star => star.classList.remove('perfect-score-star'));
                }, 4000);
            }

            // Helper function para sa confetti
            function createConfettiPiece() {
                const colors = ['#3b82f6', '#10b981', '#fbbf24', '#ef4444', '#f472b6', '#ffffff'];
                const confetti = document.createElement('div');
                confetti.className = 'confetti';

                // Random styling
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = (Math.random() * 8 + 5) + 'px';
                confetti.style.height = (Math.random() * 8 + 5) + 'px';

                // Random animation duration
                const duration = Math.random() * 3 + 2;
                confetti.style.animation = `fall ${duration}s linear forwards`;

                document.body.appendChild(confetti);

                // Linisin ang DOM pagkatapos ng animation
                setTimeout(() => confetti.remove(), duration * 1000);
            }

            /**
             * SAMPLE INTEGRATION:
             * Ito ang logic na dapat tumatakbo kapag natapos ang Speech Recognition
             */
            function updateRating(score) {
                const ratingText = document.getElementById('rating');
                const starsContainer = document.getElementById('stars');

                ratingText.innerText = score;
                starsContainer.innerHTML = '';

                for (let i = 1; i <= 5; i++) {
                    const starIcon = document.createElement('i');
                    if (i <= score) {
                        starIcon.className = 'fa-solid fa-star star filled-star';
                    } else {
                        starIcon.className = 'fa-regular fa-star star empty-star';
                    }
                    starsContainer.appendChild(starIcon);
                }

                // TRIGGER KAPAG 5 STARS!
                if (score === 5) {
                    triggerPerfectScoreEffect();
                }
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

                        if (i <= score) {
                            star.classList.add("filled-star");
                        } else {
                            star.classList.add("empty-star");
                        }

                        star.style.animationDelay = `${(i - 1) * 0.1}s`;

                        item.appendChild(star);
                    }

                    wordHistoryEl.appendChild(item);
                });

                if (wordAttemptsHistory.includes(5)) {
                    historyMessageEl.textContent = `✅ Great! You achieved 5 stars for "${currentWord.word.toUpperCase()}"!`;
                    historyMessageEl.style.color = "#10b981";
                } else {
                    historyMessageEl.textContent = `Keep going! Aim for a 5-star score.`;
                    historyMessageEl.style.color = "#1e40af";
                }
            }

            function updateUIFromProgress() {
                const progress = allProgress[currentDifficulty];
                const wordListLength = wordBank[currentDifficulty].length;

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

            function loadProgressFromDB() {
                if (!STUDENT_ID) {
                    console.error("Student ID is missing. Cannot load progress.");
                    updateUIFromProgress();
                    loadNextWord();
                    return;
                }
                fetch(`../load_all_progress.php?student_id=${STUDENT_ID}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.progress) {
                            data.progress.forEach(p => {
                                if (allProgress[p.difficulty]) {
                                    allProgress[p.difficulty].word_index = parseInt(p.word_index) || 0;
                                    allProgress[p.difficulty].words_attempted = parseInt(p.words_attempted) || 0;
                                    allProgress[p.difficulty].words_correct = parseInt(p.words_correct) || 0;
                                }
                            });

                            const lastDifficulty = data.last_difficulty || 'beginner';
                            if (wordBank[lastDifficulty]) {
                                difficultySelect.value = lastDifficulty;
                                currentDifficulty = lastDifficulty;
                            }
                        } else {
                            console.log("No existing progress found or error loading.");
                        }

                        updateUIFromProgress();
                        loadNextWord();
                    })
                    .catch(error => {
                        console.error('AJAX Network Error (Load Progress):', error);
                        updateUIFromProgress();
                        loadNextWord();
                    });
            }

            function saveProgressToDB() {
                if (!STUDENT_ID) {
                    console.error("Student ID is missing. Cannot save progress.");
                    return;
                }

                const progress = allProgress[currentDifficulty];

                const data = new URLSearchParams();
                data.append('student_id', STUDENT_ID);
                data.append('difficulty', currentDifficulty);
                data.append('word_index', progress.word_index);
                data.append('words_attempted', progress.words_attempted);
                data.append('words_correct', progress.words_correct);

                fetch('../save_progress.php', {
                    method: 'POST',
                    body: data
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            console.log("Progress saved successfully: " + result.message);
                        } else {
                            console.error("Failed to save progress: " + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('AJAX Network Error (Save Progress):', error);
                    });
            }

            function updateDifficulty() {
                saveProgressToDB();

                const newDiff = difficultySelect.value;
                currentDifficulty = newDiff;

                updateUIFromProgress();

                loadNextWord();
            }

            function init() {
                difficultySelect.addEventListener("change", updateDifficulty);
                nextBtn.addEventListener("click", loadNextWord);
                playWordBtn.addEventListener("click", () => { if (currentWord) speakWord(currentWord.word); });
                playFeedbackBtn.addEventListener("click", () => {
                    const textToSpeak = feedbackMessage.textContent.startsWith("👌 Good try! Try saying it in this sentence: ")
                        ? currentWord.example
                        : feedbackMessage.textContent.startsWith("❌ Try again. Focus on the sounds: ")
                            ? "Please listen to the word."
                            : feedbackMessage.textContent;

                    speakFeedback(textToSpeak);
                });
                micBtn.addEventListener("click", toggleMic);

                if ("webkitSpeechRecognition" in window) {
                    recognition = new webkitSpeechRecognition();
                    recognition.continuous = false;
                    recognition.interimResults = true;
                    recognition.lang = "en-US";

                    recognition.onresult = (event) => {
                        let interimTranscript = '';
                        let finalTranscript = '';
                        let confidence = 0;
                        let hasTargetWord = false;

                        const targetWordLower = currentWord.word.toLowerCase();

                        for (let i = event.resultIndex; i < event.results.length; ++i) {
                            const transcript = event.results[i][0].transcript.trim().toLowerCase();

                            if (event.results[i].isFinal) {
                                finalTranscript += (finalTranscript.length > 0 ? ' ' : '') + transcript;
                                confidence = event.results[i][0].confidence || confidence;

                                if (finalTranscript.includes(targetWordLower)) {
                                    hasTargetWord = true;
                                }

                            } else {
                                interimTranscript += (interimTranscript.length > 0 ? ' ' : '') + transcript;
                            }
                        }

                        transcriptEl.textContent = `${finalTranscript || interimTranscript} ...`;

                        if (hasTargetWord || event.results[event.results.length - 1].isFinal) {
                            if (finalTranscript) {
                                checkPronunciation(finalTranscript, confidence);
                            } else {
                                statusEl.textContent = "Did not catch a full word. Try again.";
                                nextBtn.disabled = false;
                            }
                            if (listening) {
                                recognition.stop();
                                listening = false;
                                micBtn.classList.remove("listening");
                                stopWaveform();
                            }
                            return;
                        }
                    };

                    recognition.onerror = (event) => {
                        statusEl.textContent = "Error: " + event.error;
                        micBtn.classList.remove("listening");
                        listening = false;
                        stopWaveform();
                        nextBtn.disabled = false;
                    };
                    recognition.onend = () => {
                        if (statusEl.textContent === "Processing...") {
                            statusEl.textContent = "Click the big circle to start!";
                        }
                        micBtn.classList.remove("listening");
                        listening = false;
                    };
                } else {
                    statusEl.textContent = "Speech Recognition not supported in this browser.";
                }

                loadProgressFromDB();
            }


            function toggleMic() {
                if (!currentWord || wordDisplay.textContent === "Ready to begin") return alert("Please click 'Next Word!' to start practicing.");
                if (!recognition) return;

                if ("speechSynthesis" in window) speechSynthesis.cancel();

                if (wordAttemptsHistory.includes(5)) {
                    alert("You have already achieved 5 stars for this word. Please click 'Next Word!' to continue.");
                    return;
                }

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
                let progress = allProgress[currentDifficulty];
                const displayListLength = DISPLAY_CYCLE_LENGTH;

                if (progress.word_index >= wordList.length) {
                    alert(`You have successfully attempted all ${wordList.length} words in the ${currentDifficulty} level! Resetting unique word tracking to start a new cycle.`);
                    progress.word_index = 0;
                    saveProgressToDB();
                }

                currentWord = wordList[Math.floor(Math.random() * wordList.length)];

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

            function speakRating(score, callback) {
                if (!("speechSynthesis" in window)) {
                    if (callback) callback();
                    return;
                }

                speechSynthesis.cancel();

                let message;
                if (score === 5) {
                    message = "Excellent! You got 5 stars!";
                } else if (score >= 4) {
                    message = `Great job! You achieved ${score} stars.`;
                } else if (score >= 2) {
                    message = `You got ${score} stars. Try again for a higher score.`;
                } else {
                    message = `Your score is ${score} stars. Keep practicing!`;
                }

                const utter = new SpeechSynthesisUtterance(message);
                utter.lang = "en-US";
                utter.rate = 1.0;

                if (voicesLoaded) {
                    const usVoice = getUsEnglishVoice();
                    if (usVoice) utter.voice = usVoice;
                }

                if (callback) {
                    utter.onend = callback;
                    utter.onerror = callback;
                }

                speechSynthesis.speak(utter);
            }

            function speakCompletionMessage() {
                if (!("speechSynthesis" in window)) {
                    return;
                }

                const message = `Congratulations, you completed ${DISPLAY_CYCLE_LENGTH} words! Click next word to start a new cycle.`;

                const utter = new SpeechSynthesisUtterance(message);
                utter.lang = "en-US";
                utter.rate = 1.0;

                if (voicesLoaded) {
                    const usVoice = getUsEnglishVoice();
                    if (usVoice) utter.voice = usVoice;
                }

                speechSynthesis.speak(utter);
            }

            function checkPronunciation(spoken, confidence = 1) {
                let progress = allProgress[currentDifficulty];

                const { score, feedback } = ratePronunciation(spoken, currentWord.word, currentWord.phonemes);

                let isNewPass = false;

                if (!wordAttemptsHistory.includes(5) || score < 5) {
                    wordAttemptsHistory.push(score);
                    renderWordHistory();
                }

                if (score >= 4) {
                    if (!wordAttemptsHistory.slice(0, -1).some(s => s >= 4)) {
                        isNewPass = true;
                        progress.words_correct++;
                        progress.words_attempted++;
                        const wordListLength = wordBank[currentDifficulty].length;
                        if (progress.word_index < wordListLength) {
                            progress.word_index++;
                        }
                    } else if (wordAttemptsHistory.filter(s => s === score).length === 1) {
                        progress.words_attempted++;
                    }
                } else {
                    if (wordAttemptsHistory.filter(s => s === score).length === 1) {
                        progress.words_attempted++;
                    }
                }

                saveProgressToDB();

                ratingEl.textContent = score;
                renderStars(score);

                feedbackMessage.className = "feedback-message";
                const isCycleComplete = (progress.words_correct % DISPLAY_CYCLE_LENGTH) === 0 && progress.words_correct > 0 && isNewPass;

                if (score >= 4) {
                    if (isCycleComplete) {
                        speakRating(score, speakCompletionMessage);
                    } else {
                        speakRating(score);
                    }

                    feedbackMessage.textContent = `${currentWord.example}`;
                    feedbackMessage.classList.add("bg-green-100", "text-green-800");

                } else if (score === 3) {
                    speakRating(score);
                    feedbackMessage.textContent = `👌 Good try! Try saying it in this sentence: ${currentWord.example}`;
                    feedbackMessage.classList.add("bg-blue-100", "text-blue-800");
                } else {
                    speakRating(score);
                    feedbackMessage.textContent = `❌ Try again. Focus on the sounds: ${currentWord.phonemes.join(" - ")}`;
                    feedbackMessage.classList.add("bg-red-100", "text-red-800");
                }

                updateUIFromProgress();

                saveRatingToDB(currentWord.word, score);

                nextBtn.disabled = false;
            }

            function saveRatingToDB(word, score) {
                if (!STUDENT_ID || !USERNAME) {
                    console.error("Student ID or Username is missing. Cannot save rating.");
                    return;
                }

                const data = new URLSearchParams();
                data.append('student_id', STUDENT_ID);
                data.append('username', USERNAME);
                data.append('word', word);
                data.append('score', score);
                fetch('../save_rating.php', {
                    method: 'POST',
                    body: data
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.success) {
                            console.log("Rating saved successfully: " + result.message);
                        } else {
                            console.error("Failed to save rating: " + result.message);
                            console.error("PHP Error Details:", result.message);
                        }
                    })
                    .catch(error => {
                        console.error('AJAX Network Error or PHP Execution Error:', error);
                    });
            }

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
                } catch (err) {
                    console.error('Error accessing microphone for waveform:', err);
                    statusEl.textContent = "Error: Cannot access microphone for soundwave.";
                }
            }
            function drawLandscapeWave() {
                if (!listening) {
                    stopWaveform();
                    return;
                }
                animationId = requestAnimationFrame(drawLandscapeWave);
                analyser.getByteFrequencyData(dataArray);
                const WIDTH = canvas.width;
                const HEIGHT = canvas.height;
                const barWidth = (WIDTH / bufferLength) * 1.5;
                ctx.clearRect(0, 0, WIDTH, HEIGHT);
                let x = 0;
                for (let i = 0; i < bufferLength; i++) {
                    const barHeight = (dataArray[i] / 255) * HEIGHT;
                    const hue = 200 + (dataArray[i] / 255) * 100;
                    ctx.fillStyle = `hsl(${hue}, 90%, 60%)`;

                    ctx.fillRect(x, HEIGHT - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }
            }
            function stopWaveform() {
                if (animationId) cancelAnimationFrame(animationId);
                if (source && source.mediaStream) {
                    source.mediaStream.getTracks().forEach(track => track.stop());
                }
                if (audioContext && audioContext.state !== 'closed') {
                    audioContext.close().catch(e => console.error("Error closing AudioContext:", e));
                }
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            function speakWord(word) {
                if (!("speechSynthesis" in window)) {
                    alert("Speech synthesis not supported.");
                    return;
                }
                speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(word);
                utter.lang = "en-US";
                utter.rate = 0.9;

                if (voicesLoaded) {
                    const usVoice = getUsEnglishVoice();
                    if (usVoice) utter.voice = usVoice;
                }
                speechSynthesis.speak(utter);
            }
            function speakFeedback(text) {
                if (!("speechSynthesis" in window)) {
                    alert("Speech synthesis not supported.");
                    return;
                }
                speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance(text);
                utter.lang = "en-US";
                utter.rate = 0.95;

                if (voicesLoaded) {
                    const usVoice = getUsEnglishVoice();
                    if (usVoice) utter.voice = usVoice;
                }

                speechSynthesis.speak(utter);
            }
            function renderStars(score) {
                starsEl.innerHTML = "";
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement("i");
                    star.className = "fa-star fa-solid star";

                    if (i <= score) {
                        star.classList.add("filled-star");
                    } else {
                        star.classList.add("empty-star");
                    }

                    starsEl.appendChild(star);
                }
            }
            function ratePronunciation(spoken, target, targetPhonemes) {
                spoken = spoken.toLowerCase().trim();
                target = target.toLowerCase().trim();

                const phonemeMap = {
                    a: ["æ", "ɑ", "ə", "eɪ", "ʌ"], e: ["ɛ", "i", "ɪ", "eɪ", "ə"],
                    i: ["ɪ", "aɪ", "iː", "ɜː"], o: ["ɒ", "oʊ", "ɔ", "ɑ"],
                    u: ["ʌ", "uː", "juː", "ʊ"], y: ["j", "aɪ", "ɪ"],
                    b: ["b"], c: ["k", "s"], d: ["d"], f: ["f"], g: ["g", "dʒ"],
                    h: ["h"], j: ["dʒ"], k: ["k"], l: ["l"], m: ["m"], n: ["n"],
                    p: ["p"], q: ["k", "kw"], r: ["ɹ", "r"], s: ["s", "ʃ", "z"],
                    t: ["t", "θ"], v: ["v"], w: ["w"], x: ["ks", "gz"], z: ["z", "ʒ"],
                    ch: ["tʃ"], sh: ["ʃ"], th: ["θ", "ð"], ph: ["f"], ng: ["ŋ"], wh: ["w"],
                    tion: ["ʃən"], sion: ["ʒən"], ture: ["tʃɚ"], sure: ["ʃɚ"], age: ["ɪdʒ"],
                    ai: ["eɪ"], ea: ["iː", "ɛ"], ee: ["iː"], oo: ["uː", "ʊ"], ou: ["aʊ", "oʊ"],
                    ow: ["aʊ", "oʊ"], au: ["ɔː"], oi: ["ɔɪ"], er: ["ɜː", "ɚ"], ar: ["ɑɹ"],
                    or: ["ɔɹ", "ɝ"],
                };
                const guess = [];
                let i = 0;
                while (i < spoken.length) {
                    let two = spoken.slice(i, i + 2);
                    if (phonemeMap[two]) {
                        guess.push(phonemeMap[two][0]);
                        i += 2;
                    } else if (phonemeMap[spoken[i]]) {
                        guess.push(phonemeMap[spoken[i]][0]);
                        i++;
                    } else i++;
                }
                function levenshtein(a, b) {
                    const matrix = Array.from({ length: a.length + 1 }, () => []);
                    for (let i = 0; i <= a.length; i++) matrix[i][0] = i;
                    for (let j = 0; j <= b.length; j++) matrix[0][j] = j;
                    for (let i = 1; i <= a.length; i++) {
                        for (let j = 1; j <= b.length; j++) {
                            if (a[i - 1] === b[j - 1]) matrix[i][j] = matrix[i - 1][j - 1];
                            else
                                matrix[i][j] = Math.min(
                                    matrix[i - 1][j] + 1,
                                    matrix[i][j - 1] + 1,
                                    matrix[i - 1][j - 1] + 1.3
                                );
                        }
                    }
                    return matrix[a.length][b.length];
                }

                const dist = levenshtein(guess.join(""), targetPhonemes.join(""));
                const sim = 1 - dist / Math.max(targetPhonemes.length, guess.length);

                let score;
                if (sim > 0.96) score = 5;
                else if (sim > 0.85) score = 4;
                else if (sim > 0.70) score = 3;
                else if (sim > 0.50) score = 2;
                else if (sim > 0.25) score = 1;
                else score = 0;

                let feedback = "";

                return { score, feedback };


            }



            window.onload = init;

        </script>
    </main>
    </body>
    </html>
