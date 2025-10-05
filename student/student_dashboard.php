<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pronunciation Learning System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #87CEEB 0%, #ccf2ff 100%);
            font-family: "Comic Sans MS", "Poppins", sans-serif;
            padding: 16px;
            color: #333;
        }

        .container { max-width: 1024px; margin: 0 auto; }
        header { text-align: center; margin-bottom: 32px; }

        h1 {
            font-size: 2.8rem;
            font-weight: bold;
            color: #004e89;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.15);
        }

        h2 {
            color: #0077b6;
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }

        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            border: 3px solid #e6f7ff;
        }
        .card:hover { transform: translateY(-5px); }

        .progress-bar {
            height: 14px;
            background: #e0f7ff;
            border-radius: 9999px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .progress-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #00b4d8, #48cae4);
            border-radius: 9999px;
            transition: width 0.5s ease-in-out;
        }

        .waveform {
            height: 100px;
            background: linear-gradient(to right, #fdfd96, #ffd6a5, #caffbf);
            border-radius: 16px;
        }

        button {
            padding: 12px 22px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: 0.3s ease;
        }
        .btn-primary {
            background: #ffb703;
            color: #fff;
        }
        .btn-primary:hover {
            background: #ff9f1c;
        }
        .btn-secondary {
            background: #caf0f8;
            color: #0077b6;
        }
        .btn-secondary:hover {
            background: #ade8f4;
        }

        .feedback-container { margin-bottom: 24px; }
        .stats-container {
            background: #f0fbff;
            padding: 16px;
            border-radius: 16px;
            border: 2px dashed #87CEEB;
        }

        /* Mic button */
        .mic-container { width: 110px; height: 110px; margin: 20px auto; }
        .mic-icon {
            width: 100%; height: 100%;
            background-color: #48cae4;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            color: white;
            font-size: 42px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(72,202,228,0.5);
        }
        .mic-icon.listening {
            background-color: #ff6b6b;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,107,107,0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255,107,107,0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,107,107,0); }
        }

        .status {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #005f99;
            text-align: center;
        }

        .transcript {
            font-size: 1.4rem;
            font-weight: bold;
            color: #03045e;
            margin: 10px auto;
            text-align: center;
            padding: 14px;
            border-radius: 14px;
            background-color: #e6faff;
        }

        .rating-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 12px 0;
        }
        .rating {
            font-size: 1.6rem;
            font-weight: bold;
            margin-right: 10px;
            color: #0077b6;
        }
        .star { color: #ddd; font-size: 1.8rem; margin: 0 3px; }
        .star.filled { color: #ffb703; }
    </style>



</head>
<body>
<header> <h1>🎤 Pronunciation Learning System 🎉</h1></header>
<div class="container">
    <main>
        <div class="grid mb-6">
            <!-- Student Section -->
            <div class="card">
                <h2>Reading Practice</h2>
                <div class="flex justify-between mb-4">
                    <div>Level: <span id="levelDisplay">Beginner</span></div>
                    <div>Progress: <span id="progressText">0</span>%</div>
                </div>
                <div class="progress-bar"><div class="progress-bar-inner" style="width: 0%"></div></div>
                <label for="difficulty">Select Difficulty</label>
                <select id="difficulty" class="w-full p-2 border border-gray-300 rounded-md mb-4">
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>

                <!-- Mic Button -->
                <div class="mic-container">
                    <div id="micBtn" class="mic-icon"><i class="fa-solid fa-microphone"></i></div>
                </div>
                <div id="status" class="status"></div>
                <div id="waveform" class="waveform mb-4"></div>

                <div class="flex justify-center space-x-4">
                    <button id="nextBtn" class="btn-secondary" disabled>Next Word</button>
                </div>
            </div>

            <!-- Instructor Section -->
            <div class="card">
                <h2>Reading Material</h2>
                <div class="bg-indigo-50 p-4 rounded-lg mb-4">
                    <div id="wordDisplay" class="text-3xl text-center font-bold text-indigo-800 py-4">Ready to begin</div>
                    <div class="flex justify-center mt-2">
                        <button id="playWordBtn" class="btn-primary">
                            <i class="fa-solid fa-volume-high"></i> Listen
                        </button>
                    </div>
                    <div id="phonemeDisplay" class="text-center text-gray-600"></div>
                </div>

                <!-- Transcript + Rating -->
                <div class="transcript" id="transcript">...</div>
                <div class="rating-container">
                    <div class="rating" id="rating">0</div>
                    <div id="stars"></div>
                </div>

                <div class="feedback-container">
                    <h3>Feedback</h3>
                    <div id="feedbackMessage" class="p-3 rounded-md bg-gray-100 text-gray-800 min-h-10">
                        Practice sentence will appear here when you start.
                    </div>
                </div>

                <div class="stats-container">
                    <h3>Session Stats</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div><span>Words Attempted:</span><span id="attemptedCount" class="block text-xl font-bold">0</span></div>
                        <div><span>Accuracy:</span><span id="accuracyRate" class="block text-xl font-bold">0%</span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    const wordBank = {
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

    const wordDisplay=document.getElementById('wordDisplay');
    const phonemeDisplay=document.getElementById('phonemeDisplay');
    const feedbackMessage=document.getElementById('feedbackMessage');
    const nextBtn=document.getElementById('nextBtn');
    const difficultySelect=document.getElementById('difficulty');
    const levelDisplay=document.getElementById('levelDisplay');
    const progressText=document.getElementById('progressText');
    const progressBar=document.querySelector('.progress-bar-inner');
    const attemptedCount=document.getElementById('attemptedCount');
    const accuracyRate=document.getElementById('accuracyRate');
    const micBtn=document.getElementById('micBtn');
    const statusEl=document.getElementById('status');
    const transcriptEl=document.getElementById('transcript');
    const ratingEl=document.getElementById('rating');
    const starsEl=document.getElementById('stars');
    const playWordBtn=document.getElementById('playWordBtn');

    let currentWord=null;
    let wordsAttempted=0, wordsCorrect=0;
    let recognition;

    function init(){
        difficultySelect.addEventListener('change',updateDifficulty);
        nextBtn.addEventListener('click',loadNextWord);
        playWordBtn.addEventListener('click',()=>{ if(currentWord){speakWord(currentWord.word);} });

        if('webkitSpeechRecognition' in window){
            recognition=new webkitSpeechRecognition();
            recognition.continuous=false;
            recognition.interimResults=false;
            recognition.lang='en-US';

            recognition.onresult=(event)=>{
                const transcript=event.results[0][0].transcript;
                transcriptEl.textContent=transcript;
                checkPronunciation(transcript);
            };
            recognition.onerror=(event)=>{
                statusEl.textContent="Error: "+event.error;
                micBtn.classList.remove("listening");
            };
            recognition.onend=()=>{micBtn.classList.remove("listening");};
        }else{
            statusEl.textContent="Speech Recognition not supported in this browser.";
        }
        loadNextWord();
    }

    function updateDifficulty(){
        const diff=difficultySelect.value;
        levelDisplay.textContent=diff.charAt(0).toUpperCase()+diff.slice(1);
        loadNextWord();
    }

    function loadNextWord(){
        const difficulty=difficultySelect.value;
        const wordList=wordBank[difficulty];
        currentWord=wordList[Math.floor(Math.random()*wordList.length)];
        wordDisplay.textContent=currentWord.word;
        phonemeDisplay.textContent=currentWord.phonemes.join("·");
        feedbackMessage.textContent="Read the word aloud when ready.";
        nextBtn.disabled=true;
    }

    micBtn.addEventListener("click",()=>{
        if(!currentWord){alert("Pick a word first!");return;}
        if(recognition){recognition.start();}
        micBtn.classList.add("listening");
        statusEl.textContent="Listening...";
    });

    function checkPronunciation(spoken){
        wordsAttempted++;
        attemptedCount.textContent=wordsAttempted;

        let score=ratePronunciation(spoken,currentWord.word,currentWord.phonemes);
        ratingEl.textContent=score;
        renderStars(score);

        if(score>=4){
            feedbackMessage.textContent=`Awesome! You said "${currentWord.word}" perfectly 👏 Example: ${currentWord.example}`;
            feedbackMessage.className='p-3 rounded-md bg-green-100 text-green-800';
            wordsCorrect++;
        }else if(score===3){
            feedbackMessage.textContent=`Good try! Just a little clearer 👍 Example: ${currentWord.example}`;
            feedbackMessage.className='p-3 rounded-md bg-blue-100 text-blue-800';
        }else{
            feedbackMessage.textContent=`Hmm, try again slowly 🎯 Sounds: ${currentWord.phonemes.join("-")}`;
            feedbackMessage.className='p-3 rounded-md bg-red-100 text-red-800';
        }

        const accuracy = Math.round((wordsCorrect / wordsAttempted) * 100);
        accuracyRate.textContent = accuracy + "%";
        progressText.textContent = accuracy;
        progressBar.style.width = accuracy + "%";

        nextBtn.disabled = false;
    }

    // ---- IMPROVED PHONEME-AWARE SCORING ----
    function ratePronunciation(spoken, target, targetPhonemes) {
        spoken = spoken.toLowerCase().trim();
        target = target.toLowerCase().trim();
        spoken = spoken.replace(/\b(a|the|an)\b/g, "").trim();

        if (spoken === target) return 5;

        // ---- Levenshtein ----
        function levenshtein(a, b) {
            const matrix = Array.from({ length: a.length + 1 }, () => []);
            for (let i = 0; i <= a.length; i++) matrix[i][0] = i;
            for (let j = 0; j <= b.length; j++) matrix[0][j] = j;
            for (let i = 1; i <= a.length; i++) {
                for (let j = 1; j <= b.length; j++) {
                    if (a[i - 1] === b[j - 1]) {
                        matrix[i][j] = matrix[i - 1][j - 1];
                    } else {
                        matrix[i][j] = Math.min(
                            matrix[i - 1][j] + 1,
                            matrix[i][j - 1] + 1,
                            matrix[i - 1][j - 1] + 2 // stronger penalty
                        );
                    }
                }
            }
            return matrix[a.length][b.length];
        }

        // ---- phoneme map ----
        const phonemeMap = {
            a: ["æ","ɑ","ə"],
            e: ["ɛ","i","ɪ"],
            i: ["ɪ","aɪ"],
            o: ["ɔ","oʊ"],
            u: ["ʌ","uː"],
            c: ["k","s"],
            k: ["k"], g: ["g","dʒ"],
            p: ["p"], b: ["b"],
            d: ["d"], t: ["t"],
            s: ["s","ʃ"], z: ["z"],
            r: ["ɹ"], l: ["l"],
            m: ["m"], n: ["n"],
            f: ["f"], v: ["v"],
            h: ["h"], w: ["w"],
            y: ["j"]
        };

        const guess = [];
        for (let ch of spoken) {
            if (phonemeMap[ch]) guess.push(phonemeMap[ch][0]);
        }

        const distPh = levenshtein(guess.join(""), targetPhonemes.join(""));
        const simPh = 1 - distPh / Math.max(guess.length, targetPhonemes.length);

        const distText = levenshtein(spoken, target);
        const simText = 1 - distText / Math.max(spoken.length, target.length);

        let penalty = 0;
        if (guess.length !== targetPhonemes.length) penalty += 0.3;
        if (guess[0] && targetPhonemes[0] && guess[0] !== targetPhonemes[0]) penalty += 0.25;
        if (guess.at(-1) && targetPhonemes.at(-1) && guess.at(-1) !== targetPhonemes.at(-1)) penalty += 0.2;

        let similarity = (simPh * 0.75) + (simText * 0.25);
        similarity -= penalty;
        similarity = Math.max(0, similarity);

        if (similarity > 0.97) return 5;
        if (similarity > 0.85) return 4;
        if (similarity > 0.65) return 3;
        if (similarity > 0.45) return 2;
        if (similarity > 0.25) return 1;
        return 0;
    }

    function renderStars(score) {
        starsEl.innerHTML = "";
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement("i");
            star.className = "fa-star fa " + (i <= score ? "star filled" : "star");
            starsEl.appendChild(star);
        }
    }

    function speakWord(word){
        if('speechSynthesis' in window){
            const utterance = new SpeechSynthesisUtterance(word);
            utterance.lang = 'en-US';
            const voices = speechSynthesis.getVoices();
            const usVoice = voices.find(v => v.lang === "en-US" && v.name.includes("Google"))
                || voices.find(v => v.lang === "en-US");
            if(usVoice) utterance.voice = usVoice;
            utterance.rate = 0.9;
            speechSynthesis.speak(utterance);
        }else{
            alert("Your browser does not support speech synthesis.");
        }
    }

    if(speechSynthesis.onvoiceschanged!==undefined){
        speechSynthesis.onvoiceschanged=()=>{};
    }

    window.onload = init;
</script>
</body>
</html>

