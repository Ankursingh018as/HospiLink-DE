<!DOCTYPE html>
<html>
<head>
    <title>Update Gemini API Key</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .error-box {
            background: #fee;
            border-left: 4px solid #f44;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box {
            background: #efe;
            border-left: 4px solid #4f4;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Update Gemini API Key</h1>
        
        <div class="error-box">
            <strong>⚠️ Current API Key Issue:</strong>
            <p>Your API key was reported as leaked and has been disabled by Google.</p>
        </div>
        
        <div class="info-box">
            <strong>📋 Steps to Get New API Key:</strong>
            <ol>
                <li>Go to <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
                <li>Click "Create API Key"</li>
                <li>Select your Google Cloud project (or create new)</li>
                <li>Copy the generated API key</li>
                <li>Paste it below and click "Update Key"</li>
            </ol>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key'])) {
            $newKey = trim($_POST['api_key']);
            
            if (empty($newKey)) {
                echo '<div class="error-box">❌ API key cannot be empty!</div>';
            } else {
                $envFile = __DIR__ . '/.env';
                
                if (file_exists($envFile)) {
                    $content = file_get_contents($envFile);
                    
                    // Replace the API key
                    $pattern = '/GEMINI_API_KEY=.*/';
                    $replacement = 'GEMINI_API_KEY=' . $newKey;
                    $newContent = preg_replace($pattern, $replacement, $content);
                    
                    if (file_put_contents($envFile, $newContent)) {
                        echo '<div class="success-box">
                            <strong>✅ API Key Updated Successfully!</strong>
                            <p>The new key has been saved to .env file.</p>
                            <p><a href="test_ai_debug.php">Test AI Prioritizer Now</a></p>
                        </div>';
                    } else {
                        echo '<div class="error-box">❌ Failed to write to .env file. Check permissions.</div>';
                    }
                } else {
                    echo '<div class="error-box">❌ .env file not found!</div>';
                }
            }
        }
        ?>

        <form method="POST">
            <label for="api_key">Enter New Gemini API Key:</label>
            <input type="text" 
                   id="api_key" 
                   name="api_key" 
                   placeholder="AIzaSy..." 
                   required 
                   pattern="AIza[A-Za-z0-9_-]+"
                   title="API key must start with 'AIza'">
            
            <button type="submit">🔄 Update API Key</button>
        </form>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <strong>⚠️ Security Tips:</strong>
            <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                <li>Never commit <code>.env</code> file to Git</li>
                <li>Don't share API keys publicly</li>
                <li>Add <code>.env</code> to <code>.gitignore</code></li>
                <li>Restrict API key usage in Google Cloud Console</li>
            </ul>
        </div>
    </div>
</body>
</html>
