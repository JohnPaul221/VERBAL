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
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #87CEEB 0%, #E0F2F7 100%);
            font-family: "Comic Sans MS", "Poppins", sans-serif;
            padding: 10px;
            color: #1e3a8a;
            overflow-y: hidden;
        }
        h2 {
            color: #1e40af;
            font-size: 1.8rem;
            margin-bottom: 8px;
            border-bottom: 3px solid #63b3ed;
            padding-bottom: 3px;
        }
        h3 {
            color: #1e40af;
            font-size: 1.3rem;
            margin-top: 5px;
            margin-bottom: 3px;
        }
        .page-header {
            text-align: center;
            position: relative;
            padding: 10px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #usernameDisplay {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 1.1rem;
            font-weight: 800;
            color: #1e40af;
            background: #e0f2fe;
            padding: 5px 10px;
            border-radius: 8px;
            border: 2px solid #60a5fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        #logoutBtn {
            position: absolute;
            top: 5px;
            right: 15px;
            padding: 6px 12px;
            font-size: 0.9rem;
            font-weight: 600;
            background: #fca5a5;
            color: #7f1d1d;
            border: 2px solid #ef4444;
            box-shadow: 0 3px #b91c1c;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease-out;
        }
        #logoutBtn:hover {
            background: #fecaca;
        }
        #logoutBtn:active {
            box-shadow: 0 1px #b91c1c;
            transform: translateY(2px);
        }
        .main-container {
            max-width: 75vw;
            margin: 0px auto 0 auto;
            min-height: 82vh;
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
            background: #ffffff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            border: 3px solid #60a5fa;
        }
        .section {
            padding: 10px;
            border-radius: 10px;
            background-color: #f7fbff;
            max-height: calc(82vh - 30px);
            overflow-y: auto;
        }
        .section:nth-child(1) {
            flex: 0 0 30%;
            min-width: 280px;
            max-width: 400px;
        }
        .section:nth-child(2) {
            flex: 1 1 40%;
            min-width: 350px;
        }
        .history-section {
            flex: 0 0 20%;
            min-width: 140px;
            max-width: 220px;
            padding: 12px 8px;
            border-radius: 10px;
            background-color: #f7fbff;
            max-height: calc(82vh - 30px);
            overflow-y: auto;
            border: 2px solid #63b3ed;
        }
        .divider {
            width: 3px;
            background: #60a5fa;
            border-radius: 3px;
        }
        .progress-bar {
            height: 15px;
            background: #e0f7ff;
            border-radius: 9999px;
            margin-bottom: 10px;
            overflow: hidden;
            border: 1px solid #3b82f6;
        }
        .progress-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 9999px;
            transition: width 0.5s ease-in-out;
        }
        #difficulty {
            width: 100%;
            padding: 8px;
            margin-bottom: 8px;
            border: 2px solid #60a5fa;
            border-radius: 10px;
            font-size: 1rem;
            background-color: #fff;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2069.4L146.4%20209.7%205.1%2069.4c-3.1-3.1-3.1-8.2%200-11.3l11.3-11.3c3.1-3.1%208.2-3.1%2011.3%200l118.8%20118.8%20118.8-118.8c3.1-3.1%208.2-3.1%2011.3%200l11.3%2011.3c3.2%203.1%203.2%208.2%200%2011.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        .reading-material {
            background: #b2ebf2;
            padding: 8px;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 2px dashed #00bcd4;
        }
        #wordDisplay {
            font-size: 2.2rem;
            text-align: center;
            font-weight: 900;
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
        .reading-material button {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 20px;
        }
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
            color: #FFC300;
        }
        .star {
            font-size: 1.2rem;
            margin: 0 3px;
            transition: color 0.3s ease;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        .filled-star {
            color: #FFC300;
        }
        .empty-star {
            color: #ccc;
            text-shadow: none;
        }
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
        .feedback-scoreboard-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .feedback-container, .scoreboard-container {
            flex: 1 1 45%;
            min-width: 45%;
            display: flex;
            flex-direction: column;
        }
        .feedback-container h3, .scoreboard-container h3 {
            margin-top: 5px;
            margin-bottom: 3px;
        }
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
            color: #FFC300;
        }
        .flex-center { display: flex; justify-content: center; align-items: center; }
        .flex-space-between { display: flex; justify-content: space-between; align-items: center; }
        .mt-2 { margin-top: 4px; }
        .mt-4 { margin-top: 8px; }
        .mb-4 { margin-bottom: 8px; }
        .text-center { text-align: center; }

        .history-section h2 {
            font-size: 1.5rem;
            color: #10b981;
            border-bottom: 2px solid #10b981;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }

        @keyframes starPop {
            0% { transform: scale(0.5) translateY(10px); opacity: 0; }
            50% { transform: scale(1.2) translateY(-5px); opacity: 1; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }

        #wordHistory {
            display: flex;
            flex-direction: column-reverse;
            align-items: flex-start;
            gap: 5px;
            padding: 0;
        }

        .history-item {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            width: 100%;
            margin: 0;
            padding: 3px 0;
            border-bottom: 1px dashed #e0f2fe;
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e40af;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-star {
            animation: starPop 0.5s ease-out;
            display: inline-block;
            margin-right: 2px;
            font-size: 1.1rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .attempt-label {
            font-weight: bold;
            color: #00838f;
            margin-right: 5px;
            min-width: 75px;
        }

        .history-message {
            font-size: 0.9rem;
            color: #10b981;
            font-weight: bold;
            margin-top: 10px;
        }
        @media (max-width: 1200px) {
            body {
                overflow-y: auto;
            }
            .main-container {
                max-width: 98vw;
                flex-wrap: wrap;
                flex-direction: column;
                padding: 10px;
                min-height: calc(100vh - 20px);
            }
            .section, .history-section {
                flex: 1 1 100%;
                max-height: none;
                overflow-y: visible;
                min-width: 100%;
            }
            .section:nth-child(1), .section:nth-child(2) {
                min-width: 100%;
                max-width: 100%;
                flex: 1 1 100%;
            }

            .divider { display: none; }
            .history-section { order: 4; }
            .section:nth-child(1) { order: 1; }
            .section:nth-child(2) { order: 2; }

            .feedback-scoreboard-container { flex-direction: column; }
            .feedback-container, .scoreboard-container {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .stats-container { grid-template-columns: 1fr 1fr; }
            #logoutBtn {
                position: static;
                margin: 10px auto;
                display: block;
                width: 90%;
            }
            #usernameDisplay {
                position: static;
                margin: 10px auto;
                display: block;
                width: 90%;
                text-align: center;
            }
            .page-header {
                flex-direction: column;
            }
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
</main>
</body>
</html>
<script>
    const STUDENT_ID = <?php echo json_encode($student_id); ?>;
    const USERNAME = <?php echo json_encode($username); ?>;
    const PLAY_WELCOME_VOICE = <?php echo json_encode($play_welcome_voice); ?>;

    const wordBank = {
        beginner: [
            { word: "cat", phonemes: ["k","æ","t"], example: "The cat is on the mat." },
            { word: "bat", phonemes: ["b","æ","t"], example: "I have a baseball bat." },
            { word: "rat", phonemes: ["ɹ","æ","t"], example: "The rat ate the cheese." },
            { word: "hat", phonemes: ["h","æ","t"], example: "Put on your sun hat." },
            { word: "mat", phonemes: ["m","æ","t"], example: "Sit on the floor mat." },
            { word: "van", phonemes: ["v","æ","n"], example: "The white van is fast." },
            { word: "pan", phonemes: ["p","æ","n"], example: "The frying pan is hot." },
            { word: "fan", phonemes: ["f","æ","n"], example: "Turn on the electric fan." },
            { word: "map", phonemes: ["m","æ","p"], example: "Look at the world map." },
            { word: "tap", phonemes: ["t","æ","p"], example: "Water flows from the tap." },
            { word: "cap", phonemes: ["k","æ","p"], example: "I wear a blue baseball cap." },
            { word: "bag", phonemes: ["b","æ","g"], example: "My school bag is heavy." },
            { word: "tag", phonemes: ["t","æ","g"], example: "Check the price tag." },
            { word: "bed", phonemes: ["b","ɛ","d"], example: "Lie down on the soft bed." },
            { word: "net", phonemes: ["n","ɛ","t"], example: "The fish is in the net." },
            { word: "pet", phonemes: ["p","ɛ","t"], example: "The dog is a good pet." },
            { word: "jet", phonemes: ["dʒ","ɛ","t"], example: "The jet plane is fast." },
            { word: "hen", phonemes: ["h","ɛ","n"], example: "The hen laid an egg." },
            { word: "pen", phonemes: ["p","ɛ","n"], example: "Write with a black pen." },
            { word: "ten", phonemes: ["t","ɛ","n"], example: "I have ten fingers." },
            { word: "pig", phonemes: ["p","ɪ","g"], example: "The pink pig is eating." },
            { word: "wig", phonemes: ["w","ɪ","g"], example: "She wears a curly wig." },
            { word: "dig", phonemes: ["d","ɪ","g"], example: "Dig a hole in the sand." },
            { word: "fig", phonemes: ["f","ɪ","g"], example: "A fig is a sweet fruit." },
            { word: "kit", phonemes: ["k","ɪ","t"], example: "I have a first aid kit." },
            { word: "zip", phonemes: ["z","ɪ","p"], example: "Zip up your warm jacket." },
            { word: "lip", phonemes: ["l","ɪ","p"], example: "Put balm on your lip." },
            { word: "bin", phonemes: ["b","ɪ","n"], example: "Put trash in the bin." },
            { word: "pin", phonemes: ["p","ɪ","n"], example: "Use a safety pin." },
            { word: "fin", phonemes: ["f","ɪ","n"], example: "The shark has a tall fin." },
            { word: "tin", phonemes: ["t","ɪ","n"], example: "The tuna is in a tin." },
            { word: "six", phonemes: ["s","ɪ","k","s"], example: "I see six little birds." },
            { word: "dog", phonemes: ["d","ɔ","g"], example: "The dog is barking." },
            { word: "log", phonemes: ["l","ɔ","g"], example: "The turtle is on the log." },
            { word: "fog", phonemes: ["f","ɔ","g"], example: "The fog is very thick." },
            { word: "mop", phonemes: ["m","ɑ","p"], example: "Clean the floor with a mop." },
            { word: "top", phonemes: ["t","ɑ","p"], example: "The spinning top is blue." },
            { word: "pot", phonemes: ["p","ɑ","t"], example: "The flower is in the pot." },
            { word: "dot", phonemes: ["d","ɑ","t"], example: "The ladybug has a dot." },
            { word: "cot", phonemes: ["c","ɑ","t"], example: "The baby sleeps in a cot." },
            { word: "box", phonemes: ["b","ɑ","k","s"], example: "The toy is in the box." },
            { word: "fox", phonemes: ["f","ɑ","k","s"], example: "The red fox is clever." },
            { word: "sun", phonemes: ["s","ʌ","n"], example: "The sun is yellow." },
            { word: "bun", phonemes: ["b","ʌ","n"], example: "I ate a sweet bun." },
            { word: "gum", phonemes: ["g","ʌ","m"], example: "Do not chew gum in class." },
            { word: "mud", phonemes: ["m","ʌ","d"], example: "The pig is in the mud." },
            { word: "bud", phonemes: ["b","ʌ","d"], example: "The flower has a bud." },
            { word: "bug", phonemes: ["b","ʌ","g"], example: "Look at the little bug." },
            { word: "hug", phonemes: ["h","ʌ","g"], example: "Give me a big hug." },
            { word: "rug", phonemes: ["ɹ","ʌ","g"], example: "Sit on the red rug." },
            { word: "mug", phonemes: ["m","ʌ","g"], example: "The hot cocoa is in a mug." },
            { word: "jug", phonemes: ["dʒ","ʌ","g"], example: "The milk is in the jug." },
            { word: "tub", phonemes: ["t","ʌ","b"], example: "Wash in the bath tub." },
            { word: "cup", phonemes: ["k","ʌ","p"], example: "Drink from the blue cup." },
            { word: "bus", phonemes: ["b","ʌ","s"], example: "We go on the school bus." },
            { word: "nut", phonemes: ["n","ʌ","t"], example: "The squirrel has a nut." },
            { word: "hut", phonemes: ["h","ʌ","t"], example: "They live in a small hut." },
            { word: "web", phonemes: ["w","ɛ","b"], example: "The spider made a web." },
            { word: "box", phonemes: ["b","ɑ","k","s"], example: "Put the books in the box." },
            { word: "ham", phonemes: ["h","æ","m"], example: "I like ham in my bread." },
            { word: "egg", phonemes: ["ɛ","g"], example: "The bird laid an egg." },
            { word: "jam", phonemes: ["dʒ","æ","m"], example: "Put strawberry jam on toast." },
            { word: "ram", phonemes: ["ɹ","æ","m"], example: "The ram has big horns." },
            { word: "yam", phonemes: ["j","æ","m"], example: "The sweet yam is orange." },
            { word: "dam", phonemes: ["d","æ","m"], example: "The beaver built a dam." },
            { word: "cab", phonemes: ["k","æ","b"], example: "We took a yellow cab." },
            { word: "bib", phonemes: ["b","ɪ","b"], example: "The baby wears a bib." },
            { word: "rib", phonemes: ["ɹ","ɪ","b"], example: "I can feel my rib." },
            { word: "cub", phonemes: ["k","ʌ","b"], example: "The bear cub is cute." },
            { word: "sub", phonemes: ["s","ʌ","b"], example: "The sub is under water." },
            { word: "leg", phonemes: ["l","ɛ","g"], example: "The table has a leg." },
            { word: "peg", phonemes: ["p","ɛ","g"], example: "Hang your coat on the peg." },
            { word: "beg", phonemes: ["b","ɛ","g"], example: "The dog will beg for food." },
            { word: "mix", phonemes: ["m","ɪ","k","s"], example: "Mix the cake batter." },
            { word: "fix", phonemes: ["f","ɪ","k","s"], example: "Dad can fix the car." },
            { word: "tax", phonemes: ["t","æ","k","s"], example: "Pay the sales tax." },
            { word: "gas", phonemes: ["g","æ","s"], example: "The car needs gas." },
            { word: "pad", phonemes: ["p","æ","d"], example: "Write on the ink pad." },
            { word: "dad", phonemes: ["d","æ","d"], example: "My dad is very tall." },
            { word: "bad", phonemes: ["b","æ","d"], example: "The rotten apple is bad." },
            { word: "mad", phonemes: ["m","æ","d"], example: "The angry man is mad." },
            { word: "lad", phonemes: ["l","æ","d"], example: "The young lad is happy." },
            { word: "sad", phonemes: ["s","æ","d"], example: "Do not be sad today." },
            { word: "lid", phonemes: ["l","ɪ","d"], example: "Put the lid on the pot." },
            { word: "kid", phonemes: ["k","ɪ","d"], example: "The little kid is playing." },
            { word: "hid", phonemes: ["h","ɪ","d"], example: "The cat hid in the box." },
            { word: "rod", phonemes: ["ɹ","ɑ","d"], example: "Use the fishing rod." },
            { word: "pod", phonemes: ["p","ɑ","d"], example: "The peas are in a pod." },
            { word: "cod", phonemes: ["k","ɑ","d"], example: "The cod is a big fish." },
            { word: "bud", phonemes: ["b","ʌ","d"], example: "The rose bud is pink." },
            { word: "mud", phonemes: ["m","ʌ","d"], example: "Wipe the mud off shoes." },
            { word: "sud", phonemes: ["s","ʌ","d"], example: "The soap has a lot of sud." },
            { word: "wax", phonemes: ["w","æ","k","s"], example: "The candle is made of wax." },
            { word: "dip", phonemes: ["d","ɪ","p"], example: "Dip the chip in sauce." },
            { word: "pip", phonemes: ["p","ɪ","p"], example: "The apple has a pip." },
            { word: "hip", phonemes: ["h","ɪ","p"], example: "Put your hand on your hip." },
            { word: "sip", phonemes: ["s","ɪ","p"], example: "Take a sip of water." },
            { word: "tip", phonemes: ["t","ɪ","p"], example: "The tip of the pencil." },
            { word: "rip", phonemes: ["ɹ","ɪ","p"], example: "There is a rip in my shirt." },
            { word: "gap", phonemes: ["g","æ","p"], example: "The gap in the fence." }
        ],
        intermediate: [
            { word: "ship", phonemes: ["ʃ","ɪ","p"], example: "The big ship is on the sea." },
            { word: "shop", phonemes: ["ʃ","ɑ","p"], example: "Go to the toy shop." },
            { word: "fish", phonemes: ["f","ɪ","ʃ"], example: "The goldfish is swimming." },
            { word: "dish", phonemes: ["d","ɪ","ʃ"], example: "Put food in the dish." },
            { word: "shell", phonemes: ["ʃ","ɛ","l"], example: "I found a seashell." },
            { word: "shed", phonemes: ["ʃ","ɛ","d"], example: "Tools are in the shed." },
            { word: "shoe", phonemes: ["ʃ","uː"], example: "Tie your left shoe." },
            { word: "duck", phonemes: ["d","ʌ","k"], example: "The yellow duck swims." },
            { word: "rock", phonemes: ["ɹ","ɑ","k"], example: "The gray rock is heavy." },
            { word: "sock", phonemes: ["s","ɑ","k"], example: "I lost my blue sock." },
            { word: "back", phonemes: ["b","æ","k"], example: "My back is straight." },
            { word: "neck", phonemes: ["n","ɛ","k"], example: "The giraffe has a long neck." },
            { word: "lock", phonemes: ["l","ɑ","k"], example: "Lock the front door." },
            { word: "bell", phonemes: ["b","ɛ","l"], example: "The school bell rang." },
            { word: "frog", phonemes: ["f","ɹ","ɑ","g"], example: "The green frog jumped." },
            { word: "flag", phonemes: ["f","l","æ","g"], example: "The flag is waving." },
            { word: "star", phonemes: ["s","t","ɑː","ɹ"], example: "The star is in the sky." },
            { word: "tree", phonemes: ["t","ɹ","iː"], example: "The apple tree is tall." },
            { word: "drum", phonemes: ["d","ɹ","ʌ","m"], example: "Bang on the loud drum." },
            { word: "grass", phonemes: ["g","ɹ","æ","s"], example: "The green grass is soft." },
            { word: "brush", phonemes: ["b","ɹ","ʌ","ʃ"], example: "Brush your messy hair." },
            { word: "bread", phonemes: ["b","ɹ","ɛ","d"], example: "I like toasted bread." },
            { word: "clock", phonemes: ["k","l","ɑ","k"], example: "The clock on the wall." },
            { word: "spoon", phonemes: ["s","p","uː","n"], example: "Eat with a silver spoon." },
            { word: "snail", phonemes: ["s","n","eɪ","l"], example: "The snail is very slow." },
            { word: "snake", phonemes: ["s","n","eɪ","k"], example: "The snake is in the grass." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "Sit on the park bench." },
            { word: "lamp", phonemes: ["l","æ","m","p"], example: "Turn on the desk lamp." },
            { word: "tent", phonemes: ["t","ɛ","n","t"], example: "We sleep in a blue tent." },
            { word: "crab", phonemes: ["k","ɹ","æ","b"], example: "The crab has sharp claws." },
            { word: "truck", phonemes: ["t","ɹ","ʌ","k"], example: "The red truck is big." },
            { word: "plate", phonemes: ["p","l","eɪ","t"], example: "Put food on the plate." },
            { word: "plant", phonemes: ["p","l","æ","n","t"], example: "Water the green plant." },
            { word: "swing", phonemes: ["s","w","ɪ","ŋ"], example: "Play on the tire swing." },
            { word: "brick", phonemes: ["b","ɹ","ɪ","k"], example: "The house is made of brick." },
            { word: "block", phonemes: ["b","l","ɑ","k"], example: "Build with a wood block." },
            { word: "glass", phonemes: ["g","l","æ","s"], example: "Drink from a clear glass." },
            { word: "dress", phonemes: ["d","ɹ","ɛ","s"], example: "She has a pink dress." },
            { word: "stick", phonemes: ["s","t","ɪ","k"], example: "The dog found a stick." },
            { word: "stone", phonemes: ["s","t","oʊ","n"], example: "Throw a small stone." },
            { word: "cloud", phonemes: ["k","l","aʊ","d"], example: "Look at the white cloud." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "The wood bench is hard." },
            { word: "swing", phonemes: ["s","w","ɪ","ŋ"], example: "I can go high on the swing." },
            { word: "clown", phonemes: ["k","l","aʊ","n"], example: "The clown has a red nose." },
            { word: "skirt", phonemes: ["s","k","ɜː","ɹ","t"], example: "She wears a blue skirt." },
            { word: "shirt", phonemes: ["ʃ","ɜː","ɹ","t"], example: "Put on your clean shirt." },
            { word: "spoon", phonemes: ["s","p","uː","n"], example: "I need a soup spoon." },
            { word: "stork", phonemes: ["s","t","ɔː","ɹ","k"], example: "The stork has long legs." },
            { word: "shark", phonemes: ["ʃ","ɑː","ɹ","k"], example: "The shark is in the ocean." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "Wait on the park bench." },
            { word: "globe", phonemes: ["g","l","oʊ","b"], example: "Find our country on the globe." },
            { word: "flute", phonemes: ["f","l","uː","t"], example: "She plays the silver flute." },
            { word: "grape", phonemes: ["g","ɹ","eɪ","p"], example: "The purple grape is sweet." },
            { word: "sheep", phonemes: ["ʃ","iː","p"], example: "The white sheep is soft." },
            { word: "brush", phonemes: ["b","ɹ","ʌ","ʃ"], example: "Use a hair brush." },
            { word: "watch", phonemes: ["w","ɑ","tʃ"], example: "Check the wrist watch." },
            { word: "whale", phonemes: ["w","eɪ","l"], example: "The blue whale is huge." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "The garden bench is green." },
            { word: "stair", phonemes: ["s","t","ɛ","ɹ"], example: "Walk up the wood stair." },
            { word: "brick", phonemes: ["b","ɹ","ɪ","k"], example: "A red brick wall." },
            { word: "plums", phonemes: ["p","l","ʌ","m","z"], example: "A bowl of purple plums." },
            { word: "stems", phonemes: ["s","t","ɛ","m","z"], example: "Flowers have green stems." },
            { word: "skunk", phonemes: ["s","k","ʌ","ŋ","k"], example: "The skunk is black and white." },
            { word: "swan", phonemes: ["s","w","ɑ","n"], example: "The white swan swims." },
            { word: "stool", phonemes: ["s","t","uː","l"], example: "Sit on the tall stool." },
            { word: "scarf", phonemes: ["s","k","ɑː","ɹ","f"], example: "Wear a warm red scarf." },
            { word: "twins", phonemes: ["t","w","ɪ","n","z"], example: "The twins look the same." },
            { word: "crane", phonemes: ["k","ɹ","eɪ","n"], example: "The tall crane is at work." },
            { word: "grill", phonemes: ["g","ɹ","ɪ","l"], example: "Cook corn on the grill." },
            { word: "shelf", phonemes: ["ʃ","ɛ","l","f"], example: "Put books on the shelf." },
            { word: "trunk", phonemes: ["t","ɹ","ʌ","ŋ","k"], example: "The elephant has a trunk." },
            { word: "quill", phonemes: ["k","w","ɪ","l"], example: "An old ink quill." },
            { word: "quilt", phonemes: ["k","w","ɪ","l","t"], example: "A warm patch quilt." },
            { word: "drill", phonemes: ["d","ɹ","ɪ","l"], example: "Use the power drill." },
            { word: "chest", phonemes: ["tʃ","ɛ","s","t"], example: "A wooden toy chest." },
            { word: "chain", phonemes: ["tʃ","eɪ","n"], example: "A metal dog chain." },
            { word: "check", phonemes: ["tʃ","ɛ","k"], example: "A black and white check." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "The piano bench is low." },
            { word: "beach", phonemes: ["b","iː","tʃ"], example: "Play on the sandy beach." },
            { word: "peach", phonemes: ["p","iː","tʃ"], example: "A soft fuzzy peach." },
            { word: "torch", phonemes: ["t","ɔː","ɹ","tʃ"], example: "Carry a bright torch." },
            { word: "teeth", phonemes: ["t","iː","θ"], example: "Brush your white teeth." },
            { word: "mouth", phonemes: ["m","aʊ","θ"], example: "Open your mouth wide." },
            { word: "path", phonemes: ["p","æ","θ"], example: "The forest walking path." },
            { word: "cloth", phonemes: ["k","l","ɑ","θ"], example: "Wipe it with a cloth." },
            { word: "thumb", phonemes: ["θ","ʌ","m"], example: "Show a thumbs up." },
            { word: "three", phonemes: ["θ","ɹ","iː"], example: "One, two, three apples." },
            { word: "wheat", phonemes: ["w","iː","t"], example: "A field of gold wheat." },
            { word: "wheel", phonemes: ["w","iː","l"], example: "The car has a round wheel." },
            { word: "whale", phonemes: ["w","eɪ","l"], example: "The whale swims deep." },
            { word: "whip", phonemes: ["w","ɪ","p"], example: "A long leather whip." },
            { word: "whistle", phonemes: ["w","ɪ","s","əl"], example: "Blow the silver whistle." },
            { word: "white", phonemes: ["w","aɪ","t"], example: "A sheet of white paper." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "Sit on the wood bench." },
            { word: "stump", phonemes: ["s","t","ʌ","m","p"], example: "A brown tree stump." },
            { word: "skirt", phonemes: ["s","k","ɜː","ɹ","t"], example: "A pink flared skirt." },
            { word: "swing", phonemes: ["s","w","ɪ","ŋ"], example: "The swing is in the yard." },
            { word: "slide", phonemes: ["s","l","aɪ","d"], example: "Go down the blue slide." },
            { word: "spoon", phonemes: ["s","p","uː","n"], example: "A silver soup spoon." },
            { word: "star", phonemes: ["s","t","ɑː","ɹ"], example: "The star is yellow." }
        ],
        advanced: [
            { word: "cake", phonemes: ["k","eɪ","k"], example: "A chocolate birthday cake." },
            { word: "kite", phonemes: ["k","aɪ","t"], example: "Fly a red kite." },
            { word: "bike", phonemes: ["b","aɪ","k"], example: "I ride my blue bike." },
            { word: "rope", phonemes: ["ɹ","oʊ","p"], example: "A long jump rope." },
            { word: "bone", phonemes: ["b","oʊ","n"], example: "The dog has a bone." },
            { word: "cone", phonemes: ["k","oʊ","n"], example: "An ice cream cone." },
            { word: "nose", phonemes: ["n","oʊ","z"], example: "Touch your small nose." },
            { word: "rose", phonemes: ["ɹ","oʊ","z"], example: "A red garden rose." },
            { word: "gate", phonemes: ["g","eɪ","t"], example: "Close the garden gate." },
            { word: "plane", phonemes: ["p","l","eɪ","n"], example: "The white plane flies." },
            { word: "whale", phonemes: ["w","eɪ","l"], example: "The whale is huge." },
            { word: "house", phonemes: ["h","aʊ","s"], example: "A house with a roof." },
            { word: "mouse", phonemes: ["m","aʊ","s"], example: "The mouse ate cheese." },
            { word: "leaf", phonemes: ["l","iː","f"], example: "A green tree leaf." },
            { word: "boat", phonemes: ["b","oʊ","t"], example: "A sailboat on water." },
            { word: "coat", phonemes: ["k","oʊ","t"], example: "Wear a winter coat." },
            { word: "goat", phonemes: ["g","oʊ","t"], example: "A goat with horns." },
            { word: "train", phonemes: ["t","ɹ","eɪ","n"], example: "A long steam train." },
            { word: "cloud", phonemes: ["k","l","aʊ","d"], example: "A fluffy white cloud." },
            { word: "globe", phonemes: ["g","l","oʊ","b"], example: "A world map globe." },
            { word: "flute", phonemes: ["f","l","uː","t"], example: "A silver music flute." },
            { word: "grape", phonemes: ["g","ɹ","eɪ","p"], example: "A bunch of grapes." },
            { word: "sheep", phonemes: ["ʃ","iː","p"], example: "A white wool sheep." },
            { word: "watch", phonemes: ["w","ɑ","tʃ"], example: "Check the wrist watch." },
            { word: "bridge", phonemes: ["b","ɹ","ɪ","dʒ"], example: "A stone river bridge." },
            { word: "phone", phonemes: ["f","oʊ","n"], example: "Answer the black phone." },
            { word: "stove", phonemes: ["s","t","oʊ","v"], example: "Cook on the gas stove." },
            { word: "cube", phonemes: ["k","j","uː","b"], example: "An ice cube tray." },
            { word: "dice", phonemes: ["d","aɪ","s"], example: "Roll the white dice." },
            { word: "slide", phonemes: ["s","l","aɪ","d"], example: "The playground slide." },
            { word: "frame", phonemes: ["f","ɹ","eɪ","m"], example: "A wooden photo frame." },
            { word: "smile", phonemes: ["s","m","aɪ","l"], example: "See the happy smile." },
            { word: "fruit", phonemes: ["f","ɹ","uː","t"], example: "A bowl of fresh fruit." },
            { word: "juice", phonemes: ["dʒ","uː","s"], example: "A glass of orange juice." },
            { word: "moon", phonemes: ["m","uː","n"], example: "The moon is round." },
            { word: "spoon", phonemes: ["s","p","uː","n"], example: "A silver soup spoon." },
            { word: "book", phonemes: ["b","ʊ","k"], example: "Read a story book." },
            { word: "hook", phonemes: ["h","ʊ","k"], example: "Hang it on a hook." },
            { word: "foot", phonemes: ["f","ʊ","t"], example: "Put a shoe on your foot." },
            { word: "boot", phonemes: ["b","uː","t"], example: "A black rubber boot." },
            { word: "star", phonemes: ["s","t","ɑː","ɹ"], example: "A bright yellow star." },
            { word: "car", phonemes: ["k","ɑː","ɹ"], example: "A fast red car." },
            { word: "jar", phonemes: ["dʒ","ɑː","ɹ"], example: "A glass jam jar." },
            { word: "park", phonemes: ["p","ɑː","ɹ","k"], example: "Play in the green park." },
            { word: "fork", phonemes: ["f","ɔː","ɹ","k"], example: "Eat with a silver fork." },
            { word: "corn", phonemes: ["k","ɔː","ɹ","n"], example: "Yellow corn on the cob." },
            { word: "horn", phonemes: ["h","ɔː","ɹ","n"], example: "The car blew its horn." },
            { word: "bird", phonemes: ["b","ɜː","ɹ","d"], example: "A small blue bird." },
            { word: "shirt", phonemes: ["ʃ","ɜː","ɹ","t"], example: "A clean white shirt." },
            { word: "skirt", phonemes: ["s","k","ɜː","ɹ","t"], example: "A pink pleated skirt." },
            { word: "purse", phonemes: ["p","ɜː","ɹ","s"], example: "A leather coin purse." },
            { word: "turtle", phonemes: ["t","ɜː","ɹ","t","əl"], example: "A slow green turtle." },
            { word: "crown", phonemes: ["k","ɹ","aʊ","n"], example: "The king wears a crown." },
            { word: "clown", phonemes: ["k","l","aʊ","n"], example: "The funny circus clown." },
            { word: "owl", phonemes: ["aʊ","l"], example: "The owl is in the tree." },
            { word: "flower", phonemes: ["f","l","aʊ","ɚ"], example: "A red garden flower." },
            { word: "mouse", phonemes: ["m","aʊ","s"], example: "A small brown mouse." },
            { word: "house", phonemes: ["h","aʊ","s"], example: "A red brick house." },
            { word: "couch", phonemes: ["k","aʊ","tʃ"], example: "Sit on the soft couch." },
            { word: "coin", phonemes: ["k","ɔɪ","n"], example: "A gold round coin." },
            { word: "oil", phonemes: ["ɔɪ","l"], example: "A bottle of olive oil." },
            { word: "toy", phonemes: ["t","ɔɪ"], example: "A colorful toy car." },
            { word: "boy", phonemes: ["b","ɔɪ"], example: "The little boy is happy." },
            { word: "nail", phonemes: ["n","eɪ","l"], example: "A sharp metal nail." },
            { word: "snail", phonemes: ["s","n","eɪ","l"], example: "A snail has a shell." },
            { word: "tail", phonemes: ["t","eɪ","l"], example: "The dog wags its tail." },
            { word: "tray", phonemes: ["t","ɹ","eɪ"], example: "Food on a plastic tray." },
            { word: "spray", phonemes: ["s","p","ɹ","eɪ"], example: "A water spray bottle." },
            { word: "seed", phonemes: ["s","iː","d"], example: "Plant a sunflower seed." },
            { word: "tree", phonemes: ["t","ɹ","iː"], example: "A tall green tree." },
            { word: "bee", phonemes: ["b","iː"], example: "A yellow honey bee." },
            { word: "jeep", phonemes: ["dʒ","iː","p"], example: "A green off-road jeep." },
            { word: "meat", phonemes: ["m","iː","t"], example: "A piece of red meat." },
            { word: "leaf", phonemes: ["l","iː","f"], example: "A green maple leaf." },
            { word: "seal", phonemes: ["s","iː","l"], example: "A seal in the ocean." },
            { word: "soap", phonemes: ["s","oʊ","p"], example: "A bar of white soap." },
            { word: "road", phonemes: ["ɹ","oʊ","d"], example: "A long paved road." },
            { word: "toad", phonemes: ["t","oʊ","d"], example: "A brown bumpy toad." },
            { word: "bowl", phonemes: ["b","oʊ","l"], example: "A bowl of hot soup." },
            { word: "snow", phonemes: ["s","n","oʊ"], example: "The white cold snow." },
            { word: "window", phonemes: ["w","ɪ","n","d","oʊ"], example: "Look out the window." },
            { word: "glue", phonemes: ["g","l","uː"], example: "A bottle of white glue." },
            { word: "blue", phonemes: ["b","l","uː"], example: "The sky is bright blue." },
            { word: "suit", phonemes: ["s","uː","t"], example: "A black business suit." },
            { word: "straw", phonemes: ["s","t","ɹ","ɔː"], example: "Drink with a straw." },
            { word: "claw", phonemes: ["k","l","ɔː"], example: "The cat has a sharp claw." },
            { word: "saw", phonemes: ["s","ɔː"], example: "Use a wood hand saw." },
            { word: "paw", phonemes: ["p","ɔː"], example: "A puppy has a soft paw." },
            { word: "ball", phonemes: ["b","ɔː","l"], example: "A round soccer ball." },
            { word: "wall", phonemes: ["w","ɔː","l"], example: "A tall stone wall." },
            { word: "chalk", phonemes: ["tʃ","ɔː","k"], example: "Write with white chalk." },
            { word: "shelf", phonemes: ["ʃ","ɛ","l","f"], example: "A wood book shelf." },
            { word: "bench", phonemes: ["b","ɛ","n","tʃ"], example: "A wooden park bench." },
            { word: "bridge", phonemes: ["b","ɹ","ɪ","dʒ"], example: "A small river bridge." },
            { word: "fridge", phonemes: ["f","ɹ","ɪ","dʒ"], example: "The milk is in the fridge." },
            { word: "badge", phonemes: ["b","æ","dʒ"], example: "A shiny police badge." },
            { word: "fudge", phonemes: ["f","ʌ","dʒ"], example: "A piece of chocolate fudge." },
            { word: "witch", phonemes: ["w","ɪ","tʃ"], example: "The witch has a hat." },
            { word: "watch", phonemes: ["w","ɑ","tʃ"], example: "Check your wrist watch." },
            { word: "patch", phonemes: ["p","æ","tʃ"], example: "A patch on the jeans." }
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