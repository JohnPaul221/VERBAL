<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Extract necessary session data for AJAX call
$student_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Assuming you store username in session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awesome Word Reader! 🗣️</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- GENERAL CHILD-FRIENDLY STYLES (SLIGHTLY REDUCED) --- */
        body {
            min-height: 100vh;
            /* MODIFIED BACKGROUND FOR SKY BLUE */
            background: linear-gradient(135deg, #87CEEB 0%, #E0F2F7 100%); /* Sky Blue to Very Light Blue */
            font-family: "Comic Sans MS", "Poppins", sans-serif;
            /* Reduced padding */
            padding: 10px;
            color: #1e3a8a; /* Deep blue text */
            /* 🔥 FIX: Prevent vertical scrolling 🔥 */
            overflow-y: hidden;
        }
        h2 {
            color: #1e40af; /* Darker Blue Heading */
            /* Reduced size */
            font-size: 1.8rem;
            margin-bottom: 8px; /* Reduced margin */
            border-bottom: 3px solid #63b3ed; /* Adjusted underline color for blue scheme */
            padding-bottom: 3px;
        }

        h3 {
            color: #1e40af;
            /* Reduced size */
            font-size: 1.3rem; /* Further reduced */
            margin-top: 5px; /* Reduced margin */
            margin-bottom: 3px; /* Reduced margin */
        }

        /* --- MAIN LAYOUT (TIGHTER AND NARROWER) --- */
        .page-header {
            text-align: center;
        }

        .main-container {
            /* Width set to 75% of viewport width */
            max-width: 75vw;
            /* 20px top margin to move it down, auto for horizontal centering */
            margin: 20px auto 0 auto;

            /* ADJUSTED HEIGHT: Set min-height to 82vh */
            min-height: 82vh;

            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background: #ffffff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            border: 3px solid #60a5fa;
        }

        .section {
            flex: 1;
            /* Min width set for proportional scaling */
            min-width: 45%;
            padding: 10px; /* Base section padding */
            border-radius: 10px;
            background-color: #f7fbff;

            /* ADJUSTED MAX-HEIGHT: Calculate height based on new 82vh container height minus padding */
            max-height: calc(82vh - 30px); /* 82vh minus main-container top/bottom padding (15px * 2) */

            overflow-y: auto;
        }

        /* TIGHTENING THE WORD READING SECTION */
        .section:nth-child(3) {
            padding: 8px;
        }

        .divider {
            width: 3px;
            background: #60a5fa;
            border-radius: 3px;
        }

        /* --- ELEMENTS --- */

        /* Progress Bar (Smaller) */
        .progress-bar {
            height: 15px;
            background: #e0f7ff;
            border-radius: 9999px;
            margin-bottom: 10px; /* Reduced margin */
            overflow: hidden;
            border: 1px solid #3b82f6;
        }
        .progress-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 9999px;
            transition: width 0.5s ease-in-out;
        }

        /* Dropdown/Select */
        #difficulty {
            width: 100%;
            padding: 8px; /* Reduced padding */
            margin-bottom: 8px; /* Reduced margin */
            border: 2px solid #60a5fa;
            border-radius: 10px;
            font-size: 1rem;
            background-color: #fff;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2069.4L146.4%20209.7%205.1%2069.4c-3.1-3.1-3.1-8.2%200-11.3l11.3-11.3c3.1-3.1%208.2-3.1%2011.3%200l118.8%20118.8%20118.8-118.8c3.1-3.1%208.2-3.1%2011.3%200l11.3%2011.3c3.2%203.1%203.2%208.2%200%2011.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }

        /* --- WORD DISPLAY (MADE SMALLER) --- */
        .reading-material {
            /* Adjusted color from yellow to a light blue/green for better contrast */
            background: #b2ebf2;
            padding: 8px;
            border-radius: 10px;
            margin-bottom: 6px;
            /* Adjusted border color */
            border: 2px dashed #00bcd4;
        }

        #wordDisplay {
            font-size: 2.2rem;
            text-align: center;
            font-weight: 900;
            /* Adjusted color */
            color: #00838f;
            padding: 3px 0;
            text-shadow: 1px 1px 0px rgba(255, 255, 255, 0.9);
        }

        #phonemeDisplay {
            font-size: 1.0rem;
            color: #6b7280;
            text-align: center;
            margin-top: 3px;
        }

        /* Target the button specifically inside the reading material for smaller size */
        .reading-material button {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 20px;
        }

        /* --- BUTTONS (SMALLER GENERAL) --- */
        button {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.2s ease-out;
            box-shadow: 0 3px #4b5563;
            margin: 3px;
        }
        /* Override for mic/next/leaderboard buttons */
        #nextBtn, #playFeedbackBtn, #leaderboardBtn {
            padding: 8px 16px;
            font-size: 1rem;
        }

        button:active {
            box-shadow: 0 1px #4b5563;
            transform: translateY(2px);
        }

        .btn-primary {
            background: #ef4444;
            color: #fff;
            box-shadow: 0 3px #b91c1c;
        }
        .btn-primary:hover { background: #f87171; }

        .btn-secondary {
            background: #3b82f6;
            color: #fff;
            box-shadow: 0 3px #1d4ed8;
        }
        .btn-secondary:hover { background: #60a5fa; }

        /* --- MIC & WAVEFORM (SMALLER) --- */
        .mic-container { width: 90px; height: 90px; margin: 10px auto; }
        .mic-icon {
            width: 100%; height: 100%;
            background-color: #3b82f6;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            color: white;
            font-size: 36px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            /* Adjusted border color for blue scheme */
            border: 4px solid #63b3ed;
        }
        .mic-icon.listening {
            background-color: #ef4444;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 25px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .status {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #1e3a8a;
            text-align: center;
            font-weight: bold;
        }

        .waveform {
            height: 80px;
            border-radius: 10px;
            background: linear-gradient(to right, #e0f2fe, #dbeafe);
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
            margin-bottom: 8px;
            position: relative;
        }

        #waveformCanvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* --- RESULTS & FEEDBACK (SMALLER) --- */
        .transcript {
            font-size: 1.2rem;
            font-weight: bold;
            color: #166534;
            margin: 5px auto;
            text-align: center;
            padding: 8px;
            border-radius: 8px;
            background-color: #ecfdf5;
            border: 2px solid #34d399;
        }

        .rating-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 8px 0;
            gap: 8px;
        }
        .rating {
            font-size: 2rem;
            font-weight: 900;
            /* Adjusted color from orange to yellow/gold */
            color: #FFC300;
        }
        .star { color: #fcc43d; font-size: 1.8rem; margin: 0 3px; }

        /* Specific feedback styles */
        .feedback-message {
            padding: 10px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: bold;
            min-height: 50px;
            margin-top: 5px;
        }
        .bg-green-100 { background: #d1fae5; } .text-green-800 { color: #065f46; }
        .bg-blue-100 { background: #dbeafe; } .text-blue-800 { color: #1e40af; }
        .bg-red-100 { background: #fee2e2; } .text-red-800 { color: #991b1b; }
        .bg-initial-feedback { background: #f3f4f6; color: #6b7280; border: 2px dashed #d1d5db; }

        /* --- SIDE-BY-SIDE LAYOUT --- */
        .feedback-scoreboard-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .feedback-container, .scoreboard-container {
            flex: 1;
            /* Min width slightly reduced for tighter fit */
            min-width: 40%;
            display: flex;
            flex-direction: column;
        }

        .feedback-container h3, .scoreboard-container h3 {
            margin-top: 5px;
            margin-bottom: 3px;
        }

        /* Stats (Smaller) */
        .stats-container {
            background: #e0f2fe;
            padding: 10px;
            border-radius: 10px;
            border: 2px solid #93c5fd;
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .stat-item span {
            display: block;
            font-size: 0.9rem;
            color: #1e40af;
        }
        .stat-item .value {
            font-size: 1.8rem;
            font-weight: 900;
            /* Adjusted color from orange to gold */
            color: #FFC300;
        }

        /* Utility classes (using simple CSS) - Adjusted margins */
        .flex-center { display: flex; justify-content: center; align-items: center; }
        .flex-space-between { display: flex; justify-content: space-between; align-items: center; }
        .mt-4 { margin-top: 8px; }
        .mb-4 { margin-bottom: 8px; }
        .text-center { text-align: center; }


        /* Responsive */
        @media (max-width: 900px) {
            /* Ensure it scales back up when the screen is very small */
            .main-container {
                max-width: 100%;
                flex-direction: column;
                padding: 10px;
                /* Restore height flexibility on small screens */
                min-height: calc(100vh - 20px);
            }
            .section {
                /* Restore max-height flexibility on small screens */
                max-height: none;
                overflow-y: visible;
            }
            .divider { display: none; }
            /* Adjusted mobile font sizes to match new small desktop sizes */
            h1 { font-size: 2.5rem; }
            .rating { font-size: 2rem; }
            #wordDisplay { font-size: 3rem; }

            /* Full width for side-by-side containers on small screens */
            .feedback-container, .scoreboard-container {
                min-width: 100%;
            }
            .feedback-scoreboard-container {
                flex-direction: column; /* Stack vertically on small screens */
            }
            .stats-container {
                grid-template-columns: 1fr 1fr; /* Revert to 2 columns for stats inside on mobile */
            }
        }
    </style>
</head>

<body>
<header class="page-header">
</header>
<main>
    <div class="main-container">

        <div class="section">
            <h2>Your Turn to Talk! 🎤</h2>

            <div class="flex-space-between mb-4">
                <div>Level: <span id="levelDisplay" style="font-weight: bold; color: #10b981;">Beginner</span></div>
                <div>Progress: <span id="progressText" style="font-weight: bold; color: #10b981;">0</span>%</div>
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
                <button id="leaderboardBtn" class="btn-primary" onclick="window.location.href='../save_rating.php';">
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
                    <h3>Example Sentence! 📝</h3> <div id="feedbackMessage" class="feedback-message bg-initial-feedback">
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
    </div>
</main>
</body>
</html>
<script>
    // PHP variables made available to JavaScript
    const STUDENT_ID = <?php echo json_encode($student_id); ?>;
    const USERNAME = <?php echo json_encode($username); ?>;

    const wordBank = {
        // ... (wordBank content is unchanged) ...
        beginner: [
            { word: "cat", phonemes: ["k","æ","t"], example:"The cat sat on the mat." },
            { word: "dog", phonemes: ["d","ɔ","g"], example:"The dog barked loudly." },
            { word: "sun", phonemes: ["s","ʌ","n"], example:"The sun is bright." },
            { word: "book", phonemes: ["b","ʊ","k"], example:"I read a book." },
            { word: "fish", phonemes: ["f","ɪ","ʃ"], example:"The fish swims fast." },
            { word: "pen", phonemes: ["p","ɛ","n"], example:"She writes with a pen." },
            { word: "hat", phonemes: ["h","æ","t"], example:"He wears a hat." },
            { word: "tree", phonemes: ["t","ɹ","iː"], example:"The tree is tall." },
            { word: "cup", phonemes: ["k","ʌ","p"], example:"The cup is full." },
            { word: "ball", phonemes: ["b","ɔ","l"], example:"The ball rolled away." }
        ],
        intermediate: [
            { word: "orange", phonemes: ["ɔ","ɹ","ɪ","n","dʒ"], example:"I ate an orange for breakfast." },
            { word: "window", phonemes: ["w","ɪ","n","d","oʊ"], example:"She opened the window." },
            { word: "school", phonemes: ["s","k","uː","l"], example:"He goes to school." },
            { word: "pencil", phonemes: ["p","ɛ","n","s","ɪ","l"], example:"She dropped her pencil." },
            { word: "garden", phonemes: ["g","ɑ","ɹ","d","ən"], example:"The garden has flowers." },
            { word: "market", phonemes: ["m","ɑ","ɹ","k","ɪ","t"], example:"They went to the market." },
            { word: "family", phonemes: ["f","æ","m","l","i"], example:"My family is big." },
            { word: "doctor", phonemes: ["d","ɑ","k","t","ɚ"], example:"The doctor is kind." },
            { word: "animal", phonemes: ["æ","n","ɪ","m","əl"], example:"The zoo has many animals." },
            { word: "happy", phonemes: ["h","æ","p","i"], example:"She feels happy today." }
        ],
        advanced: [
            { word: "extraordinary", phonemes: ["ɪ","k","s","t","ɹ","ɔ","ɹ","d","ɪ","n","ɛ","ɹ","i"], example:"Her talent was extraordinary." },
            { word: "architecture", phonemes: ["ɑ","ɹ","k","ɪ","t","ɛ","k","tʃ","ɚ"], example:"The architecture is beautiful." },
            { word: "environment", phonemes: ["ɪ","n","v","aɪ","ɹ","ən","m","ə","n","t"], example:"We must protect the environment." },
            { word: "philosophy", phonemes: ["f","ɪ","l","ɑ","s","ə","f","i"], example:"He studied philosophy." },
            { word: "responsibility", phonemes: ["ɹ","ɪ","s","p","ɑ","n","s","ə","b","ɪ","l","ɪ","t","i"], example:"It is your responsibility." },
            { word: "communication", phonemes: ["k","ə","m","j","uː","n","ɪ","k","eɪ","ʃ","ən"], example:"Good communication is important." },
            { word: "achievement", phonemes: ["ə","tʃ","iː","v","mə","n","t"], example:"This is his greatest achievement." },
            { word: "opportunity", phonemes: ["ɑ","p","ɚ","t","uː","n","ɪ","t","i"], example:"She got a job opportunity." },
            { word: "determination", phonemes: ["d","ɪ","t","ɜː","m","ɪ","n","eɪ","ʃ","ən"], example:"His determination is strong." },
            { word: "consequence", phonemes: ["k","ɑ","n","s","ɪ","k","w","ɛ","n","s"], example:"Every action has a consequence." }
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

    // waveform canvas
    const canvas = document.getElementById("waveformCanvas");
    const ctx = canvas.getContext("2d");

    let currentWord = null;
    let wordsAttempted = 0, wordsCorrect = 0;
    let recognition, audioContext, analyser, dataArray, bufferLength, source;
    let animationId = null;
    let listening = false;

    function init() {
        difficultySelect.addEventListener("change", updateDifficulty);
        nextBtn.addEventListener("click", loadNextWord);
        playWordBtn.addEventListener("click", () => { if (currentWord) speakWord(currentWord.word); });
        // Updated to use the new heading name if desired, though the text content remains the same.
        playFeedbackBtn.addEventListener("click", () => speakFeedback(feedbackMessage.textContent));
        micBtn.addEventListener("click", toggleMic);

        if ("webkitSpeechRecognition" in window) {
            recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = "en-US";

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript.trim().toLowerCase();
                const confidence = event.results[0][0].confidence || 0;
                transcriptEl.textContent = `${transcript} (conf: ${Math.round(confidence * 100)}%)`;
                checkPronunciation(transcript, confidence);
            };
            recognition.onerror = (event) => {
                statusEl.textContent = "Error: " + event.error;
                micBtn.classList.remove("listening");
                stopWaveform();
            };
            recognition.onend = () => {
                micBtn.classList.remove("listening");
                stopWaveform();
            };
        } else {
            statusEl.textContent = "Speech Recognition not supported.";
        }

        loadNextWord();
    }

    function toggleMic() {
        if (!currentWord) return alert("Pick a word first!");
        if (!recognition) return;

        listening = !listening;
        micBtn.classList.toggle("listening", listening);

        if (listening) {
            statusEl.textContent = "Listening...";
            recognition.start();
            // REVERTED: Start the frequency bar graph function
            startWaveform();
        } else {
            recognition.stop();
            // We still call stopWaveform here to stop the drawing loop and free resources
            stopWaveform();
        }
    }

    // --- Waveform REVERTED to Bar Graph Style ---
    async function startWaveform() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            // Store the stream reference to stop tracks later
            source = audioContext.createMediaStreamSource(stream);
            // Also store the stream object itself to access tracks
            source.mediaStream = stream;
            source.connect(analyser);

            // REVERTED: Use frequency data for a bar graph look
            analyser.fftSize = 256;
            bufferLength = analyser.frequencyBinCount;
            dataArray = new Uint8Array(bufferLength);

            // Set canvas size to match CSS container
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;

            drawLandscapeWave();
        } catch (err) {
            console.error('Error accessing microphone for waveform:', err);
            statusEl.textContent = "Error: Cannot access microphone for soundwave.";
        }
    }

    // REVERTED: Function to draw the frequency bar graph (original style)
    function drawLandscapeWave() {
        animationId = requestAnimationFrame(drawLandscapeWave);

        // REVERTED: Use frequency data
        analyser.getByteFrequencyData(dataArray);

        const WIDTH = canvas.width;
        const HEIGHT = canvas.height;
        // The original code used a fixed bar width calculation that favored a specific look
        const barWidth = (WIDTH / bufferLength) * 1.5;

        ctx.clearRect(0, 0, WIDTH, HEIGHT);
        let x = 0;

        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * HEIGHT;
            // Retained original color logic for visual style
            const hue = 200 + (dataArray[i] / 255) * 100;
            ctx.fillStyle = `hsl(${hue}, 90%, 60%)`;

            // Draw bar from the bottom up
            ctx.fillRect(x, HEIGHT - barHeight, barWidth, barHeight);
            x += barWidth + 1;
        }
    }

    function stopWaveform() {
        if (animationId) cancelAnimationFrame(animationId);
        // CRITICAL: Stop the stream tracks and close the audio context to release the mic
        if (source && source.mediaStream) {
            source.mediaStream.getTracks().forEach(track => track.stop());
        }
        if (audioContext && audioContext.state !== 'closed') audioContext.close();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        // Reset state after stopping
        listening = false;
    }

    // --- Core functions ---
    function updateDifficulty() {
        const diff = difficultySelect.value;
        levelDisplay.textContent = diff.charAt(0).toUpperCase() + diff.slice(1);
        loadNextWord();
    }

    function loadNextWord() {
        const difficulty = difficultySelect.value;
        const wordList = wordBank[difficulty];
        currentWord = wordList[Math.floor(Math.random() * wordList.length)];
        wordDisplay.textContent = currentWord.word;
        phonemeDisplay.textContent = currentWord.phonemes.join("·");
        feedbackMessage.textContent = "Read the word aloud when ready.";
        // Reset feedback styling to initial state
        feedbackMessage.className = "feedback-message bg-initial-feedback";
        transcriptEl.textContent = "...";
        ratingEl.textContent = "0";
        renderStars(0);
        // nextBtn.disabled = true; // FIX: Removing this line ensures the button is clickable after the first rating
    }

    function checkPronunciation(spoken, confidence = 1) {
        wordsAttempted++;
        attemptedCount.textContent = wordsAttempted;

        const { score, feedback } = ratePronunciation(spoken, currentWord.word, currentWord.phonemes);

        ratingEl.textContent = score;
        renderStars(score);

        // Reset class list first
        feedbackMessage.className = "feedback-message";

        if (score >= 4) {
            // Perfect score: just show the example sentence
            feedbackMessage.textContent = `${currentWord.example}`;
            feedbackMessage.classList.add("bg-green-100", "text-green-800");
            wordsCorrect++;
        } else if (score === 3) {
            // Good try: give a positive comment and show the example sentence
            feedbackMessage.textContent = `👌 Good try! Try saying it in this sentence: ${currentWord.example}`;
            feedbackMessage.classList.add("bg-blue-100", "text-blue-800");
        } else {
            // Low score: focus on phonetic practice
            feedbackMessage.textContent = `❌ Try again. Focus on the sounds: ${currentWord.phonemes.join(" - ")}`;
            feedbackMessage.classList.add("bg-red-100", "text-red-800");
        }

        const accuracy = Math.round((wordsCorrect / wordsAttempted) * 100);
        accuracyRate.textContent = accuracy + "%";
        progressText.textContent = accuracy;
        progressBar.style.width = accuracy + "%";

        // This line ensures the button is enabled after a rating is processed
        nextBtn.disabled = false;

        // **Save the rating to the database**
        saveRatingToDB(currentWord.word, score);
    }

    /**
     * Sends the rating data to a PHP script for database insertion using AJAX.
     * @param {string} word The word the student attempted.
     * @param {number} score The score received (0-5).
     */
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

        // **FIXED PATH:** '../save_rating.php' navigates up one level from /student/ to /verbal/
        fetch('../save_rating.php', {
            method: 'POST',
            body: data
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log("Rating saved successfully: " + result.message);
                } else {
                    console.error("Failed to save rating: " + result.message);
                    // Log the error message to the console
                    console.error("PHP Error Details:", result.message);
                }
            })
            .catch(error => {
                console.error('AJAX Network Error:', error);
            });
    }


    // --- Advanced Pronunciation Evaluation (No change needed here) ---
    function ratePronunciation(spoken, target, targetPhonemes) {
        spoken = spoken.toLowerCase().trim();
        target = target.toLowerCase().trim();

        const phonemeMap = {
            // Vowels
            a: ["æ", "ɑ", "ə", "eɪ", "ʌ"],
            e: ["ɛ", "i", "ɪ", "eɪ", "ə"],
            i: ["ɪ", "aɪ", "iː", "ɜː"],
            o: ["ɒ", "oʊ", "ɔ", "ɑ"],
            u: ["ʌ", "uː", "juː", "ʊ"],
            y: ["j", "aɪ", "ɪ"],

            // Consonants
            b: ["b"],
            c: ["k", "s"],
            d: ["d"],
            f: ["f"],
            g: ["g", "dʒ"],
            h: ["h"],
            j: ["dʒ"],
            k: ["k"],
            l: ["l"],
            m: ["m"],
            n: ["n"],
            p: ["p"],
            q: ["k", "kw"],
            r: ["ɹ", "r"],
            s: ["s", "ʃ", "z"],
            t: ["t", "θ"],
            v: ["v"],
            w: ["w"],
            x: ["ks", "gz"],
            z: ["z", "ʒ"],

            // Common digraphs
            ch: ["tʃ"],
            sh: ["ʃ"],
            th: ["θ", "ð"],
            ph: ["f"],
            ng: ["ŋ"],
            wh: ["w"],

            // Suffixes
            tion: ["ʃən"],
            sion: ["ʒən"],
            ture: ["tʃɚ"],
            sure: ["ʃɚ"],
            age: ["ɪdʒ"],

            // Common vowel groups
            ai: ["eɪ"],
            ea: ["iː", "ɛ"],
            ee: ["iː"],
            oo: ["uː", "ʊ"],
            ou: ["aʊ", "oʊ"],
            ow: ["aʊ", "oʊ"],
            au: ["ɔː"],
            oi: ["ɔɪ"],
            er: ["ɜː", "ɚ"],
            ar: ["ɑɹ"],
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

        // Levenshtein distance (strict — counts even small changes)
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
                            matrix[i - 1][j - 1] + 1.3 // stricter substitution cost
                        );
                }
            }
            return matrix[a.length][b.length];
        }

        const dist = levenshtein(guess.join(""), targetPhonemes.join(""));
        const sim = 1 - dist / Math.max(targetPhonemes.length, guess.length);

        // Detect which phonemes mismatched
        const errors = [];
        const len = Math.min(guess.length, targetPhonemes.length);
        for (let j = 0; j < len; j++) {
            if (guess[j] !== targetPhonemes[j]) {
                errors.push({ expected: targetPhonemes[j], said: guess[j] || "—" });
            }
        }

        // Convert similarity to score (stricter scale)
        let score;
        if (sim > 0.96) score = 5;
        else if (sim > 0.85) score = 4;
        else if (sim > 0.70) score = 3;
        else if (sim > 0.50) score = 2;
        else if (sim > 0.25) score = 1;
        else score = 0;

        // Smart feedback messages
        let feedback = "";
        if (score >= 5) {
            feedback = "✅ Perfect! Excellent pronunciation.";
        } else if (score === 4) {
            feedback = errors.length
                ? `👍 Almost perfect — small miss on "${errors[0].expected}" sound.`
                : "👍 Very clear pronunciation!";
        } else if (score === 3) {
            feedback = errors.length
                ? `👌 Good try — but you pronounced "${errors[0].said}" instead of "${errors[0].expected}".`
                : "👌 Good attempt! Try focusing on vowel clarity.";
        } else if (score === 2) {
            feedback = `😕 Some sounds off — check: ${errors.map(e => `${e.said}→${e.expected}`).join(", ")}`;
        } else {
            feedback = `❌ Try again. Focus on: ${targetPhonemes.join(" · ")}`;
        }

        return { score, feedback };
    }

    function renderStars(score) {
        starsEl.innerHTML = "";
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement("i");
            star.className = "fa-star fa " + (i <= score ? "star filled" : "star");
            starsEl.appendChild(star);
        }
    }

    function speakWord(word) {
        if ("speechSynthesis" in window) {
            const utter = new SpeechSynthesisUtterance(word);
            utter.lang = "en-US";
            const voices = speechSynthesis.getVoices();
            const usVoice = voices.find(v => v.lang === "en-US" && v.name.includes("Google")) || voices.find(v => v.lang === "en-US");
            if (usVoice) utter.voice = usVoice;
            utter.rate = 0.9;
            speechSynthesis.speak(utter);
        } else alert("Speech synthesis not supported.");
    }

    function speakFeedback(text) {
        if ("speechSynthesis" in window) {
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = "en-US";
            utter.rate = 0.95;
            speechSynthesis.speak(utter);
        }
    }

    if (speechSynthesis.onvoiceschanged !== undefined)
        speechSynthesis.onvoiceschanged = () => {};

    window.onload = init;

</script>