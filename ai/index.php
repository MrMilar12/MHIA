<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Mental Health AI Assistant</title>
<style>
*{ box-sizing:border-box; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
body{ margin:0; height:100vh; background:linear-gradient(135deg,#0f172a,#020617); display:flex; justify-content:center; align-items:center; color:#e5e7eb; }
.chat-container{ width:min(100%,900px); height:min(100vh,720px); background:#020617; border-radius:16px; display:flex; flex-direction:column; box-shadow:0 20px 50px rgba(0,0,0,.5); overflow:hidden; }
.chat-header{ padding:16px; text-align:center; font-weight:600; background:#0f172a; border-bottom:1px solid rgba(255,255,255,.08); }
.chat-messages{ flex:1; padding:16px; overflow-y:auto; display:flex; flex-direction:column; gap:12px; }
.message{ max-width:75%; padding:12px 14px; border-radius:14px; font-size:14px; line-height:1.5; }
.user{ align-self:flex-end; background:#2563eb; color:white; }
.bot{ align-self:flex-start; background:#0f172a; border-left:4px solid #22c55e; }
.chat-input{ display:flex; gap:10px; padding:12px; border-top:1px solid rgba(255,255,255,.08); }
.chat-input input{ flex:1; padding:12px; border-radius:12px; border:none; background:#0f172a; color:white; }
.chat-input button{ padding:0 18px; border-radius:12px; border:none; background:#22c55e; font-weight:600; cursor:pointer; }
.mic-button{ background:#f87171; border-radius:50%; width:44px; height:44px; border:none; cursor:pointer; font-size:20px; color:white; display:flex; align-items:center; justify-content:center; }
/* Responsive */
@media (max-width:768px){ body{align-items:stretch;} .chat-container{width:100%; height:100vh; border-radius:0;} .message{max-width:85%;} }
</style>
</head>

<body>
<div class="chat-container">
  <div class="chat-header">Mental Health Support AI</div>
  <div class="chat-messages" id="messages">
    <div class="message bot">
      Hello 👋 I'm here to listen and support you. How are you feeling today?
    </div>
  </div>
  <div class="chat-input">
    <input type="text" id="userInput" placeholder="Share what's on your mind..." />
    <button onclick="sendMessage()">Send</button>
    <button class="mic-button" id="micButton">🎤</button>
  </div>
</div>

<script>
const messages = document.getElementById('messages');
const input = document.getElementById('userInput');

// --- Formatting (simple & natural) ---
function formatAIResponse(text){
  if(!text) return "";
  return text.replace(/\n/g,"<br>");
}

// --- Add messages ---
function addMessage(text,type){
  const div = document.createElement('div');
  div.className = `message ${type}`;
  div.innerHTML = formatAIResponse(text);
  messages.appendChild(div);
  messages.scrollTop = messages.scrollHeight;
}

// --- Typing + speech ---
function typeAndSpeak(text){
  const div = document.createElement('div');
  div.className = 'message bot';
  messages.appendChild(div);

  let charIndex = 0;
  function typeChar(){
    if(charIndex <= text.length){
      div.innerHTML = formatAIResponse(text.substring(0, charIndex));
      messages.scrollTop = messages.scrollHeight;
      charIndex++;
      setTimeout(typeChar, 25);
    } else {
      if(window.speechSynthesis){
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 1;
        utterance.pitch = 1;
        utterance.volume = 1;
        speechSynthesis.speak(utterance);
      }
    }
  }
  typeChar();
}

// --- Ask AI ---
async function askAI(prompt){
  try{
    const r = await fetch("ask-ai.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify({prompt})
    });
    const d = await r.json();
    return (d.reply || "I'm here with you. Could you tell me more?").trim();
  }catch{
    return "I'm having trouble connecting right now, but I'm still here for you.";
  }
}

// --- Send message ---
async function sendMessage(textOverride){
  const text = textOverride || input.value.trim();
  if(!text) return;

  input.disabled = true; // prevent overlapping input
  addMessage(text,'user');
  input.value='';

  const thinking = document.createElement('div');
  thinking.className = 'message bot';
  thinking.textContent='Listening...';
  messages.appendChild(thinking);

  const reply = await askAI(text);
  thinking.remove();

  typeAndSpeak(reply);
  input.disabled = false;
}

// --- Enter key ---
input.addEventListener('keydown', e => {
  if(e.key === 'Enter') sendMessage();
});

// --- Microphone button ---
let recognition;
const micButton = document.getElementById('micButton');
if('webkitSpeechRecognition' in window || 'SpeechRecognition' in window){
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  recognition = new SpeechRecognition();
  recognition.lang = 'en-US';
  recognition.interimResults = false;
  recognition.continuous = false;

  recognition.onresult = event => {
    const transcript = event.results[0][0].transcript.trim();
    if(transcript) sendMessage(transcript);
  };

  recognition.onerror = e => console.log("Speech recognition error:", e);

  micButton.addEventListener('click', ()=>{
    recognition.start();
  });
}
</script>
</body>
</html>