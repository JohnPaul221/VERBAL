<?php
session_start();
require_once('../config/database.php');
global $pdo;

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'No student ID provided']);
    exit();
}

$student_id = intval($_GET['student_id']);

try {
    // 1. Kunin ang mastered words mula sa database
    $query = "SELECT DISTINCT word FROM student_ratings WHERE student_id = ? AND score = 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$student_id]);
    $masteredResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $masteredWordsFromDB = [];
    foreach ($masteredResults as $row) {
        $masteredWordsFromDB[] = strtolower($row['word']);
    }

    /**
     * 2. Comprehensive Word Bank
     * Pinagsama ang beginner at intermediate para sa lookup.
     */
    $wordBank = [
        // Beginner Words
        "cat" => "The cat is on the mat.",
        "dog" => "My dog can bark loud.",
        "sun" => "The sun is very bright.",
        "pig" => "The pig lives on the farm.",
        "ten" => "I have ten colorful pens.",
        "bat" => "He hits the ball with a bat.",
        "cup" => "I drink water from a cup.",
        "net" => "The butterfly is in the net.",
        "bin" => "Put the paper in the bin.",
        "hop" => "I can hop like a rabbit.",
        "map" => "Follow the treasure map.",
        "jet" => "The jet flies in the sky.",
        "lid" => "Put the lid on the jar.",
        "mop" => "Help Mom mop the floor.",
        "bug" => "A tiny bug is on the leaf.",
        "van" => "We go to school in a van.",
        "wet" => "My hair is wet from the rain.",
        "dig" => "I dig a hole for the seed.",
        "box" => "The toys are in the box.",
        "hut" => "They live in a small hut.",
        "fan" => "Turn on the electric fan.",
        "leg" => "An insect has six legs.",
        "pin" => "The pin is very sharp.",
        "hot" => "The soup is too hot.",
        "run" => "Run to the finish line!",
        "jam" => "I like bread with jam.",
        "bed" => "Make your bed every morning.",
        "zip" => "Zip up your jacket.",
        "pot" => "Mom cooks in a big pot.",
        "tub" => "The baby is in the tub.",
        "bag" => "Carry your school bag.",
        "hen" => "The hen laid an egg.",
        "sit" => "Sit down on the chair.",
        "log" => "The turtle sits on a log.",
        "gum" => "Do not swallow your gum.",
        "cap" => "The boy wore a blue cap.",
        "red" => "Apples are usually red.",
        "six" => "A cube has six sides.",
        "top" => "The bird is on top of the tree.",
        "mud" => "The boots are covered in mud.",
        "rat" => "The rat ran into the hole.",
        "pen" => "Use a pen to write.",
        "wig" => "The clown wears a wig.",
        "rod" => "He uses a fishing rod.",
        "nut" => "The squirrel eats a nut.",
        "sad" => "She is sad because she lost her toy.",
        "fit" => "These shoes fit me well.",
        "fox" => "The fox has a bushy tail.",
        "bus" => "I ride the yellow bus.",
        "hat" => "Put on your hat.",
        "ant" => "The ant is carrying food.",
        "egg" => "Birds hatch from an egg.",
        "ink" => "The pen ran out of ink.",
        "owl" => "An owl wakes up at night.",
        "up" => "Look up at the sky.",
        "yak" => "The yak has long hair.",
        "dad" => "My dad is a hero.",
        "mom" => "I love my mom.",
        "win" => "I want to win the game.",
        "tax" => "Adults pay a tax.",
        "rob" => "The thief tried to rob the store.",
        "mad" => "Don't be mad at me.",
        "get" => "Go get your umbrella.",
        "hit" => "Hit the ball hard.",
        "job" => "He has a busy job.",
        "cut" => "Cut the paper with scissors.",
        "fog" => "It is hard to see in the fog.",
        "bad" => "Eating too much candy is bad.",
        "let" => "Let the bird fly away.",
        "mix" => "Mix the colors together.",
        "nod" => "Nod your head if you agree.",
        "rug" => "The cat is on the rug.",
        "set" => "Set the table for dinner.",
        "tip" => "The tip of my pencil broke.",
        "yam" => "A yam is a sweet potato.",
        "gas" => "Cars need gas to run.",
        "kid" => "The kid is playing.",
        "lot" => "I have a lot of toys.",
        "pan" => "Fry the egg in the pan.",
        "rim" => "The rim of the glass.",
        "sub" => "A sub dives deep.",
        "tag" => "Let's play a game of tag.",
        "wax" => "Candles are made of wax.",
        "yes" => "Say yes to your friends.",
        "cob" => "Corn on the cob.",
        "den" => "The lion is in its den.",
        "fin" => "A shark has a big fin.",
        "hug" => "Give your mom a hug.",
        "lab" => "Scientists work in a lab.",
        "men" => "Three men are talking.",
        "nap" => "Take a nap in the afternoon.",
        "pad" => "Write on the paper pad.",
        "rub" => "Rub your hands together.",
        "tan" => "Her skin is tan.",
        "vet" => "The vet helps sick animals.",

        // Intermediate Words
        "frog" => "The frog is green and wet.",
        "star" => "The star shines at night.",
        "ship" => "A ship sails on the sea.",
        "tree" => "The mango tree is tall.",
        "fish" => "A fish can breathe underwater.",
        "clock" => "Check the clock for the time.",
        "brush" => "Brush your teeth daily.",
        "plant" => "The plant grows in the soil.",
        "chair" => "Pull up a chair.",
        "bread" => "I like toasted bread.",
        "smile" => "You have a pretty smile.",
        "grass" => "Do not walk on the grass.",
        "shell" => "I found a shell on the beach.",
        "truck" => "The truck is full of sand.",
        "spoon" => "Eat your soup with a spoon.",
        "flag" => "Our flag has three stars.",
        "cloud" => "The cloud looks like a sheep.",
        "shoes" => "Tie your shoes tightly.",
        "dress" => "She wore a new dress.",
        "light" => "Turn off the light.",
        "black" => "The chalkboard is black.",
        "sweet" => "Candy is very sweet.",
        "train" => "The train moves on tracks.",
        "bench" => "Sit on the park bench.",
        "grape" => "I want a bunch of grapes.",
        "phone" => "The phone is ringing.",
        "snake" => "The snake is very long.",
        "green" => "Leafy vegetables are green.",
        "plate" => "Put the cake on the plate.",
        "swing" => "Push me on the swing.",
        "brick" => "The wall is made of brick.",
        "whale" => "A whale is a huge animal.",
        "lunch" => "What is for lunch?",
        "slide" => "The slide is fast.",
        "flute" => "He plays a silver flute.",
        "shirt" => "My shirt has buttons.",
        "drink" => "Drink your milk.",
        "block" => "I built a block tower.",
        "thumb" => "I have a sore thumb.",
        "sheep" => "The sheep has soft wool.",
        "bring" => "Bring your books to class.",
        "crane" => "The crane is very tall.",
        "chest" => "The treasure is in the chest.",
        "sleep" => "Go to sleep early.",
        "clown" => "The clown has a red nose.",
        "dream" => "I had a dream about flying.",
        "small" => "The ant is very small.",
        "white" => "Snow is pure white.",
        "stair" => "Walk up the stair carefully.",
        "thump" => "The ball fell with a thump.",
        "plane" => "The plane is high.",
        "skate" => "I like to skate.",
        "store" => "Buy milk at the store.",
        "school" => "I go to school.",
        "smoke" => "Smoke from the fire.",
        "snack" => "Eat a healthy snack.",
        "space" => "Stars are in space.",
        "speak" => "Please speak loudly.",
        "sport" => "Tennis is a sport.",
        "spray" => "Spray the water.",
        "stamp" => "Put a stamp on it.",
        "stand" => "Stand in a line.",
        "steam" => "Steam is very hot.",
        "stick" => "Pick up the stick.",
        "stone" => "Throw a small stone.",
        "storm" => "A storm is coming.",
        "story" => "Read me a story.",
        "stove" => "Cook on the stove.",
        "table" => "Sit at the table.",
        "thank" => "Say thank you.",
        "think" => "Think before you act.",
        "three" => "One, two, three.",
        "throw" => "Throw the ball.",
        "tiger" => "The tiger is fast.",
        "toast" => "I want some toast.",
        "today" => "Today is Monday.",
        "tooth" => "I lost a tooth.",
        "torch" => "Use a torch light.",
        "trash" => "Empty the trash.",
        "treat" => "Trick or treat.",
        "trust" => "Trust your friend.",
        "under" => "Look under the bed.",
        "until" => "Wait until noon.",
        "voice" => "Use a soft voice.",
        "watch" => "Watch the movie.",
        "water" => "Drink cold water.",
        "wheel" => "The wheel is round.",
        "where" => "Where are you?",
        "world" => "The world is large."
    ];

    // 3. I-filter ang listahan
    $finalList = [];
    foreach ($masteredWordsFromDB as $word) {
        if (isset($wordBank[$word])) {
            $finalList[] = [
                "word" => $word,
                "example_sentence" => $wordBank[$word]
            ];
        }
    }

    echo json_encode($finalList);

} catch (PDOException $e) {
    error_log("Mastery Fetch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>