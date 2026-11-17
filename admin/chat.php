<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Chat AI Interface</title>
    <style>
        :root { --bg: #f4f7f6; --admin-bg: #007bff; --user-bg: #ffffff; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); }
        
        #chat-container { max-width: 600px; margin: 50px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; height: 80vh; }
        
        
        .chat-header { padding: 20px; background: #fff; border-bottom: 1px solid #eee; font-weight: bold; display: flex; align-items: center; }
        .status-dot { height: 10px; width: 10px; background: #2ecc71; border-radius: 50%; margin-right: 10px; }

        
        #chat-window { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: #f9f9fb; }
        
        .msg { max-width: 80%; padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.4; position: relative; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .admin { align-self: flex-end; background: var(--admin-bg); color: white; border-bottom-right-radius: 2px; }
        .customer { align-self: flex-start; background: var(--user-bg); color: #333; border-bottom-left-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        
        .time { font-size: 10px; opacity: 0.7; margin-top: 5px; display: block; text-align: right; }

        
        .input-area { padding: 20px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; }
        input { flex: 1; border: 1px solid #ddd; padding: 12px; border-radius: 25px; outline: none; transition: border 0.2s; }
        input:focus { border-color: var(--admin-bg); }
        button { background: var(--admin-bg); color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div id="chat-container">
    <div class="chat-header">
        <div class="status-dot"></div>
        Chatting with Customer #<?php echo $_GET['id'] ?? '1'; ?>
    </div>

    <div id="chat-window">
        <div class="msg customer">Hello! I need help with my order. <span class="time">10:00</span></div>
    </div>

    <form class="input-area" id="chat-form">
        <input type="hidden" id="customer_id" value="<?php echo $_GET['id'] ?? '1'; ?>">
        <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
        <button type="submit">Send</button>
    </form>
</div>

<script>
const chatForm = document.getElementById('chat-form');
const chatWindow = document.getElementById('chat-window');
const messageInput = document.getElementById('message-input');

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const msg = messageInput.value.trim();
    const customerId = document.getElementById('customer_id').value;
    
    if (!msg) return;

    
    appendMessage('admin', msg, 'Sending...');
    messageInput.value = '';

    
    formData.append('customer_id', customerId);
    formData.append('message', msg);

    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'success') {
            
            const lastTime = chatWindow.lastElementChild.querySelector('.time');
            lastTime.innerText = data.time;
        } else {
            rgToast("Error", data.message || "Send failed", "error");
        }
    } catch (err) {
        console.error("Failed to send:", err);
    }
});

function appendMessage(sender, text, time) {
    const div = document.createElement('div');
    div.classList.add('msg', sender);
    div.innerHTML = `${text} <span class="time">${time}</span>`;
    chatWindow.appendChild(div);
    chatWindow.scrollTop = chatWindow.scrollHeight; 
}
</script>

<script src="../assets/js/admin.js?v=<?= filemtime(__DIR__.'/../assets/js/admin.js') ?>"></script>
</body>
</html>
