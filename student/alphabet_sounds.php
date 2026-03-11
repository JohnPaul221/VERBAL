    <?php
    session_start();
    $username = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : "Student";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pro Phonics Sounds 🔊</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary: #FF4757;
                --kids-blue: #1E90FF;
                --kids-yellow: #FFC312;
                --kids-green: #2ED573;
                --kids-purple: #A29BFE;
                --bg-gradient: linear-gradient(135deg, #A29BFE 0%, #FFFFFF 100%);
            }

            body {
                height: 100vh;
                background: var(--bg-gradient);
                font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
                margin: 0;
                padding: 0 10px;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            /* --- RESPONSIVE HEADER --- */
            .page-header {
                height: 60px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-shrink: 0;
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
            }

            #usernameDisplay {
                font-size: clamp(0.7rem, 2vw, 0.9rem);
                font-weight: 700;
                color: white;
                background: var(--kids-blue);
                padding: 5px 12px;
                border-radius: 10px;
                border: 2px solid #0984E3;
                white-space: nowrap;
            }

            .back-btn {
                background: var(--primary);
                color: white;
                border: 2px solid #B33939;
                padding: 5px 12px;
                border-radius: 10px;
                font-weight: 800;
                cursor: pointer;
                text-decoration: none;
                font-size: clamp(0.7rem, 2vw, 0.8rem);
            }

            /* --- MAIN CONTAINER --- */
            .main-container {
                flex: 1;
                width: 100%;
                max-width: 1200px;
                background: #FFFFFF;
                border-radius: 20px;
                padding: 15px;
                box-shadow: 0 8px 0px #CED6E0;
                border: 4px solid var(--kids-blue);
                margin: 0 auto 10px auto;
                overflow-y: auto;
                text-align: center;
                box-sizing: border-box;
            }

            .instruction {
                margin: 5px 0 15px 0;
                font-weight: 800;
                color: #2f3542;
                background: #EBF7FF;
                padding: 10px 30px;
                border-radius: 40px;
                border: 2px dashed var(--kids-blue);
                display: inline-block;
                font-size: clamp(0.8rem, 3vw, 1.1rem);
            }

            /* --- RESPONSIVE GRID --- */
            .alphabet-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(clamp(80px, 20vw, 110px), 1fr));
                gap: clamp(10px, 2vw, 20px);
                padding: 5px;
            }

            .letter-card {
                background: #F1F2F6;
                border-radius: clamp(15px, 3vw, 25px);
                aspect-ratio: 1 / 1;
                display: flex;
                justify-content: center;
                align-items: center;
                cursor: pointer;
                border: 4px solid #DFE4EA;
                transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                box-shadow: 0 6px 0px #DFE4EA;
            }

            .letter-main {
                font-size: clamp(2.5rem, 8vw, 4.5rem);
                font-weight: 900;
            }

            /* Rainbow Colors */
            .letter-card:nth-child(4n+1) .letter-main { color: var(--primary); }
            .letter-card:nth-child(4n+2) .letter-main { color: var(--kids-blue); }
            .letter-card:nth-child(4n+3) .letter-main { color: var(--kids-green); }
            .letter-card:nth-child(4n+4) .letter-main { color: var(--kids-purple); }

            .letter-card:hover {
                transform: translateY(-5px);
                border-color: var(--kids-yellow);
                box-shadow: 0 10px 0px var(--kids-yellow);
            }

            .speaking {
                animation: pulse-card 0.5s infinite alternate;
                background: #FFF9E6;
            }

            @keyframes pulse-card {
                from { transform: scale(1.02); }
                to { transform: scale(1.08); }
            }

            .main-container::-webkit-scrollbar { width: 8px; }
            .main-container::-webkit-scrollbar-thumb { background: var(--kids-blue); border-radius: 10px; }
        </style>
    </head>
    <body>

    <header class="page-header">
        <span id="usernameDisplay">Hi, <?php echo htmlspecialchars($username); ?>!</span>
        <a href="verbal_practice.php" class="back-btn">
            <i class="fa-solid fa-house"></i> Home
        </a>
    </header>

    <main class="main-container">
        <div class="instruction">Tap a letter to hear its sound!</div>

        <div class="alphabet-grid">
            <?php
            foreach (range('A', 'Z') as $char) {
                echo "<div class='letter-card' id='card-$char' onclick=\"playPhonics('$char')\">
                        <div class='letter-main'>$char</div>
                      </div>";
            }
            ?>
        </div>
    </main>

    <script>
        const synth = window.speechSynthesis;
        let googleVoice = null;

        // Phonics Data (Sounds base sa request mo)
        const phonicsData = {
            'A': 'ahh',
            'B': 'ba',
            'C': 'ka',
            'D': 'da',
            'E': 'eh',
            'F': 'fah',
            'G': 'ga',
            'H': 'ha',
            'I': 'i',
            'J': 'ja',
            'K': 'ka',
            'L': 'la',
            'M': 'ma',
            'N': 'na',
            'O': 'o',
            'P': 'pa',
            'Q': 'kwa',
            'R': 'ra',
            'S': 'sa',
            'T': 'ta',
            'U': 'u',
            'V': 'va',
            'W': 'wa',
            'X': 'ksa',
            'Y': 'ya',
            'Z': 'za'
        };

        function setGoogleVoice() {
            const voices = synth.getVoices();
            // Priority: Google US English (Female)
            googleVoice = voices.find(v => v.name === 'Google US English');
            if (!googleVoice) googleVoice = voices.find(v => v.lang === 'en-US');
        }

        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = setGoogleVoice;
        }

        function playPhonics(letter) {
            synth.cancel();
            const card = document.getElementById(`card-${letter}`);
            card.classList.add('speaking');

            const utter = new SpeechSynthesisUtterance();

            // Magsasalita ng Letter Name + Phonics Sound
            utter.text = `${letter} . . . ${phonicsData[letter]}`;

            if (googleVoice) utter.voice = googleVoice;
            utter.lang = 'en-US';
            utter.rate = 0.6; // Mabagal para sa Phonics
            utter.pitch = 1.2;

            utter.onend = () => card.classList.remove('speaking');
            synth.speak(utter);
        }

        window.onload = setGoogleVoice;
    </script>

    </body>
    </html>