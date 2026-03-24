<?php
session_start();
require_once 'config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection failed.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $grade    = $_POST['grade'];
    $section  = $_POST['section'];

    $profile_path = 'uploads/default.png';

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM teachers WHERE username = :username");
        $check_stmt->execute([':username' => $username]);

        if ($check_stmt->fetch()) {
            $message = "Username is already taken.";
        } else {
            // Ginamit ang 'grade_handle' para mag-match sa SQL table mo
            $sql = "INSERT INTO teachers (fullname, username, password, grade_handle, section, profile_img) 
                    VALUES (:fullname, :username, :password, :grade, :section, :profile_img)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':fullname'    => $fullname,
                ':username'    => $username,
                ':password'    => $password,
                ':grade'       => $grade,
                ':section'     => $section,
                ':profile_img' => $profile_path
            ]);

            if ($result) {
                header("Location: login.php?registration=success");
                exit();
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = "This Grade and Section is already assigned to another teacher.";
        } else {
            $message = "Application error. Please try again later.";
        }
    }
}

// Binalik lahat ng original quotes mo
$quotes = [
    ["text" => "Your hard work today is the success of a child tomorrow. Take it one breath at a time.", "author" => "Teacher's Heart"],
    ["text" => "It's okay to be tired. It means you've given your heart to something that matters.", "author" => "Unknown"],
    ["text" => "To your students, you are the safe harbor in a stormy world.", "author" => "Unknown"],
    ["text" => "You are doing a better job than you think you are. Be kind to yourself.", "author" => "Unknown"],
    ["text" => "The world needs the light that only you can bring to your classroom.", "author" => "Unknown"],
    ["text" => "Teaching is a work of heart. Your love is never wasted.", "author" => "Unknown"],
    ["text" => "When you feel like you're not making a difference, remember that seeds grow in silence.", "author" => "Unknown"],
    ["text" => "One day, a student will look back and say, 'Because of you, I didn't give up.'", "author" => "Unknown"],
    ["text" => "You are more than just a teacher; you are a builder of dreams.", "author" => "Unknown"],
    ["text" => "Small progress is still progress. You are moving mountains, one student at a time.", "author" => "Unknown"],
    ["text" => "Don't forget to refill your own cup so you can continue to pour into others.", "author" => "Unknown"],
    ["text" => "Your classroom is a garden, and you are the sun. Everything grows better because of you.", "author" => "Unknown"],
    ["text" => "The kids might not remember every lesson, but they will never forget how you made them feel.", "author" => "Maya Angelou"],
    ["text" => "Behind every successful person is a teacher who once believed in them.", "author" => "Unknown"],
    ["text" => "You are the quiet hero the world needs today.", "author" => "Unknown"],
    ["text" => "Take pride in how far you've come and have faith in how far you can go.", "author" => "Unknown"],
    ["text" => "A teacher's smile can change a student's entire day. Thank you for smiling.", "author" => "Unknown"],
    ["text" => "Your patience is your power. Your kindness is your legacy.", "author" => "Unknown"],
    ["text" => "In a world where you can be anything, thank you for choosing to be a teacher.", "author" => "Unknown"],
    ["text" => "The best thing about being a teacher is seeing the light bulb go on in a child's eyes.", "author" => "Unknown"],
    ["text" => "God gave you this calling because He knew you have the heart to handle it.", "author" => "Unknown"],
    ["text" => "Your influence is like a ripple in water; it spreads further than you can see.", "author" => "Unknown"],
    ["text" => "Teaching is the art of planting hope in the soul of a child.", "author" => "Unknown"],
    ["text" => "A great teacher is someone who looks at a 'difficult' child and sees 'hidden potential.'", "author" => "Unknown"],
    ["text" => "You are making a difference, even on the days it doesn't feel like it.", "author" => "Unknown"],
    ["text" => "Teacher, you are enough. Your presence matters more than your perfection.", "author" => "Unknown"],
    ["text" => "The lessons you teach are the wings your students will use to fly.", "author" => "Unknown"],
    ["text" => "Your kindness may be the only kindness a child experiences today.", "author" => "Unknown"],
    ["text" => "Thank you for being the person you needed when you were younger.", "author" => "Unknown"],
    ["text" => "Teaching is hard because it matters. Don't lose heart.", "author" => "Unknown"],
    ["text" => "The seeds you plant today will become the shade for someone else tomorrow.", "author" => "Unknown"],
    ["text" => "Your passion for teaching is the spark that ignites a student's future.", "author" => "Unknown"],
    ["text" => "May you always find joy in the little victories inside your classroom.", "author" => "Unknown"],
    ["text" => "You are not just teaching a subject; you are shaping a soul.", "author" => "Unknown"],
    ["text" => "The world is a better place because you chose to be an educator.", "author" => "Unknown"],
    ["text" => "Even the smallest gesture of care can change a student's life forever.", "author" => "Unknown"],
    ["text" => "You are a rockstar in a cardigan. Keep shining!", "author" => "Unknown"],
    ["text" => "Rest when you are tired, but never forget why you started.", "author" => "Unknown"],
    ["text" => "A teacher's heart is a compass that activates the magnets of curiosity.", "author" => "Unknown"],
    ["text" => "You are the reason someone believes in their own greatness.", "author" => "Unknown"],
    ["text" => "The impact of a great teacher can never be erased from a child's heart.", "author" => "Unknown"],
    ["text" => "You are brave, you are strong, and you are making the world brighter.", "author" => "Unknown"],
    ["text" => "A child's life is a piece of paper on which every person leaves a mark. Make yours beautiful.", "author" => "Chinese Proverb"],
    ["text" => "Your classroom is a place where miracles happen every single day.", "author" => "Unknown"],
    ["text" => "You have the power to turn a 'cannot' into a 'can.'", "author" => "Unknown"],
    ["text" => "Teaching is the greatest act of love anyone can offer society.", "author" => "Unknown"],
    ["text" => "Never underestimate the power of a teacher who cares.", "author" => "Unknown"],
    ["text" => "Success is seeing your students become good human beings.", "author" => "Unknown"],
    ["text" => "You are a lighthouse. Your job is to stay bright so others can find their way.", "author" => "Unknown"],
    ["text" => "Be proud of the work you do, for you are creating the future.", "author" => "Unknown"],
    // --- Resilience & Self-Care ---
    ["text" => "Self-care is not selfish; it is fuel for the heart that pours so much into others.", "author" => "Educator’s Soul"],
    ["text" => "You cannot pour from an empty cup. Take time to refill yours today.", "author" => "Unknown"],
    ["text" => "Breathe. You are handling more than most people could imagine with grace.", "author" => "Unknown"],
    ["text" => "The classroom's noise is just the sound of growth in progress.", "author" => "Unknown"],
    ["text" => "Your worth is not defined by your 'to-do' list, but by the love you give.", "author" => "Unknown"],
    ["text" => "Even on your hardest day, you are still a student’s greatest hope.", "author" => "Unknown"],
    ["text" => "It is okay to rest. The world needs a well-rested version of you.", "author" => "Unknown"],
    ["text" => "Your energy is a gift. Don't let the paperwork dim your shine.", "author" => "Teacher’s Guide"],
    ["text" => "Progress is rarely a straight line; every small step is a massive victory.", "author" => "Unknown"],
    ["text" => "You don't have to be perfect to be the exact teacher a child needs.", "author" => "Unknown"],

    // --- Student Connection & Hope ---
    ["text" => "Every child is a different kind of flower, and together they make a garden.", "author" => "Unknown"],
    ["text" => "You are the calm in the middle of a student’s internal storm.", "author" => "Unknown"],
    ["text" => "Kindness is the most important lesson you will ever model in your classroom.", "author" => "Unknown"],
    ["text" => "When the lesson plan fails, the human connection you've built remains.", "author" => "Unknown"],
    ["text" => "You are the whisper of 'You can' in a world full of 'You can’t.'", "author" => "Unknown"],
    ["text" => "A student’s 'Aha!' moment is the fuel that keeps a teacher’s spirit burning.", "author" => "Unknown"],
    ["text" => "You aren't just teaching a subject; you are teaching a human being how to live.", "author" => "Unknown"],
    ["text" => "Your classroom is a sanctuary for those who have nowhere else to feel safe.", "author" => "Unknown"],
    ["text" => "Believe in them until they have the strength to believe in themselves.", "author" => "Unknown"],
    ["text" => "Every child deserves a champion—an adult who will never give up on them.", "author" => "Rita Pierson"],

    // --- Legacy & Future Impact ---
    ["text" => "Education is the kindling of a flame, not the filling of a vessel.", "author" => "Socrates"],
    ["text" => "You are writing on the tablets of the future every time you pick up a pen.", "author" => "Unknown"],
    ["text" => "The ripples of your kindness extend much further than you will ever see.", "author" => "Unknown"],
    ["text" => "Success is seeing your students become good people, not just good workers.", "author" => "Unknown"],
    ["text" => "You are the bridge between a child’s curiosity and their ultimate destiny.", "author" => "Unknown"],
    ["text" => "The art of teaching is the art of assisting discovery.", "author" => "Mark Van Doren"],
    ["text" => "Teachers are the quiet engines that keep the world moving forward.", "author" => "Unknown"],
    ["text" => "You are molding the clay of the next generation's leaders.", "author" => "Unknown"],
    ["text" => "There is no higher calling than to shape a young and hungry mind.", "author" => "Unknown"],
    ["text" => "You are building the foundation upon which your students will stand forever.", "author" => "Unknown"],

    // --- Short & Powerful ---
    ["text" => "Teaching is the ultimate act of optimism.", "author" => "Colleen Wilcox"],
    ["text" => "Empowered teachers empower students.", "author" => "Unknown"],
    ["text" => "Your smile is a student’s first lesson in compassion.", "author" => "Unknown"],
    ["text" => "To teach is to touch a life forever.", "author" => "Unknown"],
    ["text" => "Your passion is the key that unlocks a child's hidden potential.", "author" => "Unknown"],
    ["text" => "A great teacher makes the difficult look easy and the boring look fun.", "author" => "Unknown"],
    ["text" => "Never forget: You were once the student who needed a teacher like you.", "author" => "Unknown"],
    ["text" => "You are a world-changer in a cardigan.", "author" => "Unknown"],
    ["text" => "Every day is a fresh start for both you and your students.", "author" => "Unknown"],
    ["text" => "The future of the world is sitting in your classroom right now.", "author" => "Ivan Welton Fitzwater"],

    // --- Wisdom & Perspective ---
    ["text" => "A good teacher is like a candle—it consumes itself to light the way for others.", "author" => "Mustafa Kemal Atatürk"],
    ["text" => "Great teaching is one-fourth preparation and three-fourths pure theater.", "author" => "Gail Godwin"],
    ["text" => "Mistakes are proof that you and your students are trying.", "author" => "Unknown"],
    ["text" => "You are a lighthouse in the sea of a child's confusion.", "author" => "Unknown"],
    ["text" => "Your words have the power to build a child’s confidence for a lifetime.", "author" => "Unknown"],
    ["text" => "Teaching is a labor of love that lasts a lifetime.", "author" => "Unknown"],
    ["text" => "You are a dream-catcher for your students.", "author" => "Unknown"],
    ["text" => "The influence of a teacher never stops; it echoes in eternity.", "author" => "Unknown"],
    ["text" => "Your classroom is a garden where the future is grown.", "author" => "Unknown"],
    ["text" => "You are the unsung hero of every success story.", "author" => "Unknown"],
    ["text" => "Teaching is the profession that creates all other professions.", "author" => "Unknown"],
    ["text" => "Every lesson you teach is a gift to the future.", "author" => "Unknown"],
    ["text" => "A teacher’s heart is a compass for the curious.", "author" => "Unknown"],
    ["text" => "You are a star-maker in a world that needs more light.", "author" => "Unknown"],
    ["text" => "Education is the movement from darkness to light.", "author" => "Allan Bloom"],
    ["text" => "You are the spark that lights the fire of imagination.", "author" => "Unknown"],
    ["text" => "A teacher’s impact is eternal.", "author" => "Henry Adams"],
    ["text" => "Your dedication is a masterpiece in the making.", "author" => "Unknown"],
    ["text" => "Teaching is the highest form of service to humanity.", "author" => "Unknown"],
    ["text" => "You are a blessing to every student who walks through your door.", "author" => "Unknown"],
    ["text" => "The world is better because you chose this path.", "author" => "Unknown"],
    ["text" => "You are a captain of a very important ship.", "author" => "Unknown"],
    ["text" => "Your kindness is a curriculum all on its own.", "author" => "Unknown"],
    ["text" => "You are the root of all greatness.", "author" => "Unknown"],
    ["text" => "The heartbeat of society is found in the classroom.", "author" => "Unknown"],
    ["text" => "You are a builder of hope.", "author" => "Unknown"],
    ["text" => "A master teacher is a master of patience.", "author" => "Unknown"],
    ["text" => "You are the wind in their sails as they navigate life.", "author" => "Unknown"],
    ["text" => "Thank you for showing up even when the day feels long.", "author" => "Unknown"],
    ["text" => "You are a light for those who feel lost.", "author" => "Unknown"],
    ["text" => "Teaching is a journey of the soul, not just the mind.", "author" => "Unknown"],
    ["text" => "You are a miracle worker in the classroom.", "author" => "Unknown"],
    ["text" => "Your impact is greater than any standardized test score.", "author" => "Unknown"],
    ["text" => "Every student is a victory waiting to happen.", "author" => "Unknown"],
    ["text" => "You are the reason a child will dream of a better future tonight.", "author" => "Unknown"],
    ["text" => "Your work is the foundation of a civilized world.", "author" => "Unknown"],
    ["text" => "A great teacher inspires hope, ignites the imagination, and instills a love of learning.", "author" => "Brad Henry"],
    ["text" => "You are a hero in your students' eyes.", "author" => "Unknown"],
    ["text" => "Your classroom is where magic meets hard work.", "author" => "Unknown"],
    ["text" => "The gift of learning is the greatest gift of all.", "author" => "Unknown"],
    ["text" => "You are a lighthouse keeper for young minds.", "author" => "Unknown"],
    ["text" => "Teaching is a work of heart that never ends.", "author" => "Unknown"],
    ["text" => "You are a treasure in the lives of many.", "author" => "Unknown"],
    ["text" => "Patience is your silent superpower.", "author" => "Unknown"],
    ["text" => "You are the architect of human potential.", "author" => "Unknown"],
    ["text" => "Every day is a chance to change a life forever.", "author" => "Unknown"],
    ["text" => "You are a champion for those who have no voice.", "author" => "Unknown"],
    ["text" => "The classroom is the birthplace of the future.", "author" => "Unknown"],
    ["text" => "You are a source of strength for your students.", "author" => "Unknown"],
    ["text" => "Your influence is a gift to the generations to come.", "author" => "Unknown"],
    ["text" => "A teacher is a compass that activates the magnets of curiosity.", "author" => "Unknown"],
    ["text" => "You are a builder of character.", "author" => "Unknown"],
    ["text" => "Every child is a story; thank you for being a bright chapter.", "author" => "Unknown"],
    ["text" => "You are the key that unlocks the door to a child's success.", "author" => "Unknown"],
    ["text" => "Teaching is the art of opening minds and hearts.", "author" => "Unknown"],
    ["text" => "You are a guardian of the future.", "author" => "Unknown"],
    ["text" => "Your labor of love is building a legacy of light.", "author" => "Unknown"],
    ["text" => "Thank you for your heart and your dedication.", "author" => "Unknown"],
    ["text" => "You are a life-changer in every sense of the word.", "author" => "Unknown"],
    ["text" => "Teaching: the hardest job you'll ever love.", "author" => "Unknown"],
    ["text" => "You are the spark that starts the flame of change.", "author" => "Unknown"],
    ["text" => "Every student has a gift; thank you for helping them find it.", "author" => "Unknown"],
    ["text" => "You are a beacon of hope in a complicated world.", "author" => "Unknown"],
    ["text" => "The world is a brighter place because you are in it.", "author" => "Unknown"],
    ["text" => "Your work is a masterpiece of patience and care.", "author" => "Unknown"],
    ["text" => "You are a dream-maker for the next generation.", "author" => "Unknown"],
    ["text" => "A great teacher is a treasure to the entire community.", "author" => "Unknown"],
    ["text" => "You are a life-long learner and a life-long giver.", "author" => "Unknown"],
    ["text" => "Teaching is the highest form of generosity.", "author" => "Unknown"],
    ["text" => "You are the reason someone will succeed today.", "author" => "Unknown"],
    ["text" => "Your impact is felt long after the school bell rings.", "author" => "Unknown"],
    ["text" => "You are a leader of the leaders of tomorrow.", "author" => "Unknown"],
    ["text" => "The classroom is your stage, and every student is a star.", "author" => "Unknown"],
    ["text" => "You are a miracle in a child's life.", "author" => "Unknown"],
    ["text" => "Teaching is the heart of human progress.", "author" => "Unknown"],
    ["text" => "You are a source of inspiration for us all.", "author" => "Unknown"],
    ["text" => "Every student is a chance to make a difference.", "author" => "Unknown"],
    ["text" => "You are the reason a child will feel smart today.", "author" => "Unknown"],
    ["text" => "Your patience transforms the world.", "author" => "Unknown"],
    ["text" => "Teaching is a noble calling that echoes in the future.", "author" => "Unknown"],
    ["text" => "You are a gift to the world of education.", "author" => "Unknown"],
    ["text" => "Your dedication is a light that never goes out.", "author" => "Unknown"],
    ["text" => "You are the wind beneath the wings of your students.", "author" => "Unknown"],
    ["text" => "Every student is a star, and you are the sky that holds them.", "author" => "Unknown"],
    ["text" => "You are a teacher, and that is a beautiful thing.", "author" => "Unknown"],
    ["text" => "The future belongs to the students you teach today.", "author" => "Unknown"],
    ["text" => "You are a builder of dreams and a solver of problems.", "author" => "Unknown"],
    ["text" => "Teaching is the art of creating the future.", "author" => "Unknown"],
    ["text" => "You are a light in the lives of your students.", "author" => "Unknown"],
    ["text" => "Your work is the heartbeat of our community.", "author" => "Unknown"],
    ["text" => "Thank you for being the person you are.", "author" => "Unknown"],
    ["text" => "You are a champion for the curious.", "author" => "Unknown"],
    ["text" => "Teaching is the foundation of all other successes.", "author" => "Unknown"],
    ["text" => "You are a source of joy in the classroom.", "author" => "Unknown"],
    ["text" => "Every day you spend teaching is an investment in tomorrow.", "author" => "Unknown"],
    ["text" => "You are a treasure to your students and their families.", "author" => "Unknown"],
    ["text" => "Your influence is a legacy that will never fade.", "author" => "Unknown"],
    ["text" => "Teaching is a masterpiece painted one day at a time.", "author" => "Unknown"],
    ["text" => "You are a light for the generations to follow.", "author" => "Unknown"],
    ["text" => "The world needs more teachers like you.", "author" => "Unknown"],

];
$random_quote = $quotes[array_rand($quotes)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V.E.R.B.A.L | Teacher Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1E3A8A;
            --secondary: #3B82F6;
            --accent: #60A5FA;
            --bg-color: #f0f4f8;
            --gradient: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            --glass: rgba(255, 255, 255, 0.9);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            background-image:
                    radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.08) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.08) 0px, transparent 50%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        .particle {
            position: absolute;
            background: var(--gradient);
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.15;
            z-index: -1;
            animation: floating 20s infinite alternate;
        }
        .p1 { width: 450px; height: 450px; top: -150px; left: -150px; }
        .p2 { width: 350px; height: 350px; bottom: -80px; right: -80px; animation-duration: 15s; }
        .p3 { width: 200px; height: 200px; top: 15%; right: 5%; background: var(--accent); opacity: 0.1; }

        @keyframes floating {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            100% { transform: translate(50px, 50px) scale(1.1) rotate(15deg); }
        }

        .wrapper {
            display: flex;
            width: 100%;
            max-width: 1050px;
            height: auto;
            max-height: 95vh;
            background: var(--glass);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 40px 120px -20px rgba(30, 58, 138, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: zoomIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .quote-side {
            flex: 1;
            background: var(--gradient);
            padding: 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .quote-side::before {
            content: "";
            position: absolute; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
        }

        .quote-side h2 { font-size: 2.5rem; line-height: 1.1; text-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .quote-line { width: 60px; height: 5px; background: var(--accent); margin: 25px 0; border-radius: 10px; box-shadow: 0 0 15px rgba(96, 165, 250, 0.6); }

        .form-side {
            flex: 1.2;
            padding: 35px 60px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 20px;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));
        }

        .form-group { margin-bottom: 12px; position: relative; }
        label { display: block; margin-bottom: 5px; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        input, select { width: 100%; padding: 12px 18px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; transition: 0.3s all ease; font-size: 0.9rem; color: #1e293b; }
        input:focus { outline: none; border-color: var(--secondary); background: white; transform: translateY(-1px); }

        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .btn-submit {
            width: 100%; padding: 16px; background: var(--gradient); color: white; border: none; border-radius: 18px;
            font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.4s; margin-top: 15px;
            box-shadow: 0 15px 30px -10px rgba(30, 58, 138, 0.4);
        }
        .btn-submit:hover { transform: translateY(-3px); filter: brightness(1.1); }

        .alert { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; text-align: center; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #fecaca; }

        .footer { margin-top: 18px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
        .footer a { color: var(--secondary); text-decoration: none; font-weight: 800; }

        @media (max-width: 850px) {
            .wrapper { flex-direction: column; max-height: none; }
            .quote-side { display: none; }
        }
    </style>
</head>
<body>

<div class="particle p1"></div>
<div class="particle p2"></div>
<div class="particle p3"></div>

<div class="wrapper">
    <div class="quote-side">
        <div class="quote-container animate__animated animate__fadeIn">
            <h2 class="animate__animated animate__lightSpeedInLeft">Create Your<br>Teacher Account</h2>
            <div class="quote-line"></div>
            <p style="font-size: 1.1rem; font-style: italic; opacity: 0.9;">"<?php echo $random_quote['text']; ?>"</p>
            <p style="margin-top: 15px; font-weight: 700; color: var(--accent);">— <?php echo $random_quote['author']; ?></p>
        </div>
    </div>

    <div class="form-side">
        <div class="logo animate__animated animate__pulse animate__infinite">V.E.R.B.A.L.</div>

        <?php if ($message): ?>
            <div class="alert animate__animated animate__shakeX"><?php echo $message; ?></div>
        <?php endif; ?>

        <form id="regForm" action="" method="POST" autocomplete="off">
            <div class="form-group animate__animated animate__fadeInLeft">
                <label>Full Name</label>
                <input type="text" name="fullname" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">
                <label>Username</label>
                <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="grid animate__animated animate__fadeInLeft" style="animation-delay: 0.2s;">
                <div class="form-group">
                    <label>Grade Handle</label>
                    <select name="grade" required>
                        <option value="" disabled selected></option>
                        <option value="1">Grade 1</option><option value="2">Grade 2</option><option value="3">Grade 3</option>
                        <option value="4">Grade 4</option><option value="5">Grade 5</option><option value="6">Grade 6</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section Handle</label>
                    <input type="text" name="section" required value="<?php echo isset($_POST['section']) ? htmlspecialchars($_POST['section']) : ''; ?>">
                </div>
            </div>

            <div class="form-group animate__animated animate__fadeInLeft" style="animation-delay: 0.3s;">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="passwordField" required autocomplete="new-password">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePass()"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span>REGISTER ACCOUNT</span>
            </button>
        </form>

        <div class="footer animate__animated animate__fadeInUp">
            Already registered? <a href="login.php">LOG IN HERE</a>
        </div>
    </div>
</div>

<script>
    function togglePass() {
        const passField = document.getElementById('passwordField');
        const icon = document.querySelector('.toggle-password');
        if (passField.type === "password") {
            passField.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passField.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('regForm').onsubmit = function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> CREATING...';
        btn.style.opacity = '0.9';
        btn.style.pointerEvents = 'none';
    };
</script>
</body>
</html>