<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My DealerDeck Website</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 40px;
            color: #334155;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #0f172a;
            margin-top: 0;
        }

        .highlight {
            background-color: #fef08a;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Welcome to Your Website!</h1>
        <p>This is a demonstration of how easy it is to integrate the DealerDeck AI Support widget.</p>
        <p>If you look in the bottom right corner of this page, you will see a blue chat button. Clicking it opens the beautiful light-themed support interface you requested.</p>
        
        <h3>How to add this to any PHP page:</h3>
        <p>Just add this <span class="highlight">one line of code</span> right before your closing <code>&lt;/body&gt;</code> tag:</p>
        <code>&lt;?php include 'chat_widget.php'; ?&gt;</code>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">
        
        <h3>Admin: Update Knowledge Base</h3>
        <p>Upload a new PDF to instantly update the AI's knowledge base.</p>
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <input type="file" id="kb-upload" accept=".pdf" style="flex: 1; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-family: inherit;">
            <button id="kb-upload-btn" style="background: #10b981; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: inherit;">Upload PDF</button>
        </div>
        <p id="kb-status" style="margin-top: 10px; font-size: 14px; font-weight: 500;"></p>
    </div>

    <!-- THIS IS ALL IT TAKES TO ADD THE CHAT WIDGET! -->
    <?php include 'chat_widget.php'; ?>

    <!-- Admin Upload Logic -->
    <script>
        document.getElementById('kb-upload-btn').addEventListener('click', async () => {
            const fileInput = document.getElementById('kb-upload');
            const statusText = document.getElementById('kb-status');
            
            if (!fileInput.files.length) {
                statusText.textContent = "Please select a PDF file first.";
                statusText.style.color = "#ef4444";
                return;
            }
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            
            statusText.textContent = "Uploading and rebuilding knowledge base...";
            statusText.style.color = "#3b82f6";
            
            try {
                const response = await fetch('<?php echo $apiUrl; ?>/upload-knowledge-base', {
                    method: 'POST',
                    headers: {
                        'X-API-Key': '<?php echo $apiKey; ?>'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.detail || 'Upload failed');
                }
                
                const data = await response.json();
                statusText.textContent = "Success! " + data.message;
                statusText.style.color = "#10b981";
                fileInput.value = "";
            } catch (error) {
                console.error(error);
                statusText.textContent = "Error: " + error.message;
                statusText.style.color = "#ef4444";
            }
        });
    </script>
</body>
</html>
