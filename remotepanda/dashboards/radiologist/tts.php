<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Input</title>
</head>
<body>
    <button id="startButton">Start Recording</button>
    <div id="transcription"></div>

    <script>
        const startButton = document.getElementById('startButton');
        const transcription = document.getElementById('transcription');
        const recognition = new webkitSpeechRecognition(); // For Chrome, use the webkit prefix

        recognition.onresult = function (event) {
            const result = event.results[event.resultIndex];
            const transcript = result[0].transcript;
            transcription.textContent = transcript;
        };

        startButton.addEventListener('click', function () {
            recognition.start();
        });
    </script>
</body>
</html>
