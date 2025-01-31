<!-- 

 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stream Chat with GPT-4</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        #streamingResponse {
            background-color: #f8f9fa; /* Light gray background for contrast */
            border: 1px solid #dee2e6; /* Border to separate it visually */
            border-radius: 10px; /* Rounded corners */
            white-space: pre-wrap; /* Preserve formatting and line breaks */
            overflow-y: auto; /* Add scroll if content exceeds height */
            height: 150px; /* Fixed height for better appearance */
            max-height: 250px; /* Set max height for flexibility */
            padding: 15px; /* Padding for better spacing */
            color: #212529; /* Dark text for readability */
        }
        #messageHistory {
            max-height: 300px; /* Limit height for the chat history */
            overflow-y: auto; /* Enable scrolling if content exceeds height */
            background-color: #ffffff; /* White background for contrast */
            border: 1px solid #dee2e6; /* Border for separation */
            border-radius: 10px; /* Rounded corners */
            padding: 10px; /* Add padding for spacing */
        }
        #messageHistory .list-group-item {
            border: none; /* Remove default list item borders */
            padding: 10px 15px; /* Add padding for spacing */
        }
        #messageHistory .list-group-item:nth-child(odd) {
            background-color: #f8f9fa; /* Alternate background for better visibility */
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Chat with GPT-4</h1>

    <div class="mb-4">
        <h5>Chat History</h5>
        <ul class="list-group" id="messageHistory"></ul>
    </div>

    <div class="mt-3">
        <strong>AI Response:</strong>
        <div id="streamingResponse"></div>
    </div>

    <form id="chatForm" class="mt-4">
        <div class="mb-3">
            <label for="message" class="form-label">Your Message:</label>
            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>

   
</div>

<script>
    document.getElementById('chatForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const message = document.getElementById('message').value;

        // Reset UI
        const streamingResponse = document.getElementById('streamingResponse');
        streamingResponse.innerHTML = '';
        const messageHistory = document.getElementById('messageHistory');

        // Open an EventSource for server-sent events
        const eventSource = new EventSource(`{{ route('chat.store') }}?message=${encodeURIComponent(message)}`);

        eventSource.addEventListener('message', function (event) {
            const data = JSON.parse(event.data);

            if (data.done) {
                // Close the stream when done
                eventSource.close();
                messageHistory.innerHTML += `<li class="list-group-item"><strong>You:</strong> ${message}<br><strong>GPT-4:</strong> ${streamingResponse.innerHTML}</li>`;
                document.getElementById('message').value = '';
            } else if (data.message) {
                // Append streamed chunks to the UI
                streamingResponse.innerHTML += data.message;
            }
        });

        eventSource.onerror = function () {
            console.error('An error occurred with the streaming.');
            eventSource.close();
        };
    });
</script>
</body>
</html> -->


{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatGPT-like Chat Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Arial', sans-serif;
        }
        .chat-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 80vh;
            overflow: hidden;
        }
        .chat-header {
            padding: 15px;
            background-color: #007bff;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .chat-history {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        .message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            max-width: 75%;
        }
        .message img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .message.user {
            flex-direction: row-reverse;
            align-self: flex-end;
        }
        .message.user img {
            margin-left: 10px;
            margin-right: 0;
        }
        .message-content {
            background-color: #e9ecef;
            color: #212529;
            border-radius: 10px;
            padding: 10px 15px;
            word-wrap: break-word;
        }
        .message.user .message-content {
            background-color: #007bff;
            color: #ffffff;
        }
        .chat-input {
            padding: 15px;
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
        }
        .chat-input textarea {
            resize: none;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 10px;
            font-size: 1rem;
        }
        .chat-input button {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">Chat with GPT-4</div>
        
        <div id="messageHistory" class="chat-history"></div>
        
        <div class="chat-input">
            <form id="chatForm">
                <textarea id="message" name="message" rows="3" placeholder="Type your message here..." required></textarea>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('chatForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const message = document.getElementById('message').value;
            document.getElementById('message').value = '';
            const messageHistory = document.getElementById('messageHistory');

            const userMessage = document.createElement('div');
            userMessage.className = 'message user';
            userMessage.innerHTML = `
                <img src="{{ asset('images/hassan.jpg') }}" alt="User">
                <div class="message-content">${message.replace(/\n/g, '<br>')}</div>
            `;
            messageHistory.appendChild(userMessage);
            messageHistory.scrollTop = messageHistory.scrollHeight;

            const aiMessage = document.createElement('div');
            aiMessage.className = 'message';
            aiMessage.innerHTML = `
                <img src="{{ asset('images/ai_avatar.png') }}" alt="AI">
                <div class="message-content"></div>
            `;
            const aiMessageContent = aiMessage.querySelector('.message-content');
            messageHistory.appendChild(aiMessage);
            messageHistory.scrollTop = messageHistory.scrollHeight;

            const eventSource = new EventSource(`{{ route('chat.store') }}?message=${encodeURIComponent(message)}`);

            eventSource.addEventListener('message', function (event) {
                const data = JSON.parse(event.data);
                if (data.done) {
                    eventSource.close();
                } else if (data.message) {
                    aiMessageContent.innerHTML += data.message.replace(/\n/g, '<br>');
                    messageHistory.scrollTop = messageHistory.scrollHeight;
                }
            });

            eventSource.onerror = function () {
                console.error('An error occurred with the streaming.');
                eventSource.close();
            };
        });
    </script>
</body>
</html> --}}


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatGPT-like Chat Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <!-- Use marked.js via CDN (ensure it's the latest version) -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/marked@15.0.6/lib/marked.umd.min.js"></script> --}}
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Arial', sans-serif;
        }
        .chat-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 80vh;
            overflow: hidden;
            direction: rtl;
        }
        .chat-header {
            padding: 15px;
            background-color: #007bff;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .chat-history {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        .message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            max-width: 75%;
        }
        .message img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .message.user {
            flex-direction: row-reverse;
            align-self: flex-end;
        }
        .message.user img {
            margin-left: 10px;
            margin-right: 0;
        }
        .message-content {
            background-color: #e9ecef;
            color: #212529;
            border-radius: 10px;
            padding: 10px 15px;
            word-wrap: break-word;
        }
        .message.user .message-content {
            background-color: #007bff;
            color: #ffffff;
        }
        .chat-input {
            padding: 15px;
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
        }
        .chat-input textarea {
            resize: none;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 10px;
            font-size: 1rem;
        }
        .chat-input button {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">Chat with GPT-4</div>
        
        <div id="messageHistory" class="chat-history"></div>
        
        <div class="chat-input">
            <form id="chatForm">
                <textarea id="message" name="message" rows="3" placeholder="عايز ايه يازعيم..." required></textarea>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('chatForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const message = document.getElementById('message').value;
            document.getElementById('message').value = '';
            const messageHistory = document.getElementById('messageHistory');

            const userMessage = document.createElement('div');
            userMessage.className = 'message user';
            userMessage.innerHTML = ` 
                <img src="{{ asset('images/hassan.jpg') }}" alt="User">
                <div class="message-content">${message.replace(/\n/g, '<br>')}</div>
            `;
            messageHistory.appendChild(userMessage);
            messageHistory.scrollTop = messageHistory.scrollHeight;

            const aiMessage = document.createElement('div');
            aiMessage.className = 'message';
            aiMessage.innerHTML = `
                <img src="{{ asset('images/ai_avatar.png') }}" alt="AI">
                <div class="message-content"></div>
            `;
            const aiMessageContent = aiMessage.querySelector('.message-content');
            messageHistory.appendChild(aiMessage);
            messageHistory.scrollTop = messageHistory.scrollHeight;

            const eventSource = new EventSource(`{{ route('chat.store') }}?message=${encodeURIComponent(message)}`);

            eventSource.addEventListener('message', function (event) {
                const data = JSON.parse(event.data);
                if (data.done) {
                    eventSource.close();
                } else if (data.message) {
                    // Use `marked.default` to parse the markdown correctly
                    aiMessageContent.innerHTML += data.message.replace(/\n/g, '<br>');

                    //aiMessageContent.innerHTML += marked.parse(data.message).replace(/\n/g, '<br>');
                    messageHistory.scrollTop = messageHistory.scrollHeight;
                }
            });

            eventSource.onerror = function () {
                console.error('An error occurred with the streaming.');
                eventSource.close();
            };
        });
    </script>
</body>
</html>
