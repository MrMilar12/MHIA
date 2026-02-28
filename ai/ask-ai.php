<?php
header("Content-Type: application/json");

// Get user input
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = trim($input["prompt"] ?? "");

// Detect potential crisis keywords
$crisisWords = ["suicide","kill myself","hang myself","hanging","want to die","end my life","no reason to live","self harm","self-harm","i want to die"];
$isCrisis = false;
foreach($crisisWords as $word){
    if(stripos($userMessage, $word) !== false){
        $isCrisis = true;
        break;
    }
}

// Compassionate AI prompt
$systemPrompt = "You are a caring and empathetic mental health assistant.
Respond naturally and gently. Reflect the user's feelings, listen attentively, and offer supportive advice.";

// If a potential crisis is detected, add urgent guidance
if($isCrisis){
    $systemPrompt .= "
The user may be in emotional crisis. Respond with extra empathy.
Encourage contacting a trusted friend, family member, counselor, or hotline immediately.
Do not provide harmful instructions.
";
}

$finalPrompt = $systemPrompt . "\nUser message:\n" . $userMessage;

// Prepare API payload
$payload = json_encode([
    "model" => "llama3",
    "prompt" => $finalPrompt,
    "stream" => false,
    "options" => [
        "num_predict" => 1350,
        "temperature" => 0.6,
        "top_p" => 0.85
    ]
]);

// Call Ollama API
$ch = curl_init("http://192.168.19.152:11434/api/generate");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_CONNECTTIMEOUT => 2
]);

$response = curl_exec($ch);
curl_close($ch);

// Parse AI response
$data = json_decode($response, true);
$rawReply = $data["response"] ?? "I'm here to listen. Please tell me what's on your mind.";

// Return JSON
echo json_encode([
    "reply" => $rawReply,
    "crisis_detected" => $isCrisis
]);
?>