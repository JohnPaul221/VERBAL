<?php global $pdo;
/** * BACKEND LOGIC: Dito pinu-process ang data mula sa camera.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    $action = $data['action'] ?? '';
    $user = $data['user'] ?? 'User';
    $inputDesc = $data['descriptor'] ?? [];

    if ($action === 'register') {
        $stmt = $pdo->prepare("INSERT INTO users (username, descriptor) VALUES (?, ?)");
        $stmt->execute([$user, json_encode($inputDesc)]);
        echo json_encode(['success' => true, 'message' => "Registered: $user"]);
        exit;
    }

    if ($action === 'verify') {
        $stmt = $pdo->query("SELECT username, descriptor FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            $dbDesc = json_decode($u['descriptor']);
            $dist = 0;
            for ($i = 0; $i < count($inputDesc); $i++) {
                $dist += pow($inputDesc[$i] - $dbDesc[$i], 2);
            }
            if (sqrt($dist) < 0.5) {
                echo json_encode(['success' => true, 'message' => "Access Granted: Welcome " . $u['username']]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => "Warning: Identity Not Recognized!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verbal Face Access</title>
    <script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a1a; color: white; text-align: center; }
        .camera-container { display: flex; justify-content: center; gap: 15px; margin: 20px 0; }
        video, canvas { background: #000; border: 3px solid #444; border-radius: 10px; width: 320px; height: 240px; }
        .controls { background: #2a2a2a; padding: 20px; border-radius: 15px; display: inline-block; }
        input { padding: 10px; border-radius: 5px; border: none; width: 200px; margin-bottom: 10px; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-camera { background: #4caf50; color: white; }
        .btn-snap { background: #ff9800; color: black; }
        .btn-action { background: #007bff; color: white; }
        #status { font-weight: bold; margin-top: 15px; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>

<h1>Biometric Face Login</h1>
<p id="status">Initializing AI Models...</p>

<div class="camera-container">
    <div>
        <p>Live Camera Feed</p>
        <video id="video" autoplay playsinline muted></video>
    </div>
    <div>
        <p>Last Captured Snapshot</p>
        <canvas id="canvas"></canvas>
    </div>
</div>

<div class="controls">
    <input type="text" id="username" placeholder="Enter Full Name"><br>
    <button class="btn-camera" onclick="openCamera()">OPEN CAMERA</button>
    <button class="btn-snap" onclick="takeSnapshot()">TAKE SNAPSHOT</button><br>
    <button class="btn-action" onclick="process('register')">REGISTER FACE</button>
    <button class="btn-action" onclick="process('verify')">VERIFY IDENTITY</button>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const status = document.getElementById('status');

    // FUNCTION 1: I-load ang AI Models
    async function loadModels() {
        try {
            const MODEL_URL = './models';
            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            status.innerText = "Models Loaded. Click 'OPEN CAMERA'.";
        } catch (err) {
            status.innerText = "Error: models/ folder files not found.";
        }
    }

    // FUNCTION 2: Buksan ang Camera
    async function openCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240 }
            });
            video.srcObject = stream;
            status.innerText = "Camera Active! Take a snapshot.";
        } catch (err) {
            status.innerText = "Camera Error: " + err.message;
            status.className = "error";
        }
    }

    // FUNCTION 3: Mag-capture ng Image
    function takeSnapshot() {
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, 320, 240);
        status.innerText = "Snapshot Captured!";
    }

    // FUNCTION 4: I-send sa Database
    async function process(action) {
        const user = document.getElementById('username').value;
        const detection = await faceapi.detectSingleFace(canvas).withFaceLandmarks().withFaceDescriptor();

        if (!detection) {
            status.innerText = "Error: No face detected in snapshot!";
            return;
        }

        const res = await fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                user: user,
                descriptor: Array.from(detection.descriptor)
            })
        });

        const result = await res.json();
        alert(result.message);
    }

    window.onload = loadModels;
</script>
</body>
</html>