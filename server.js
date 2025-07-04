const express = require('express');
const app = express();
const port = process.env.PORT || 3000;

app.use(express.json());

// Get webhook URL from environment variable
const WEBHOOK_URL = process.env.WEBHOOK_URL;
if (!WEBHOOK_URL) {
    console.error('WEBHOOK_URL environment variable is not set');
    process.exit(1);
}

// API endpoint: /verify/:username
app.get('/verify/:username', async (req, res) => {
    const username = req.params.username.toLowerCase();

    if (!username) {
        return res.status(400).json({ error: 'Username is required' });
    }

    try {
        // Use native fetch API
        const response = await fetch(`https://voxiom.io/profile/player/${encodeURIComponent(username)}`, {
            method: 'POST'
        });
        const data = await response.json();

        // Log the raw data for debugging (remove in production)
        console.log('API Response:', data);

        // Validate username (case-insensitive comparison with nickname)
        if (data && data.success && data.data && data.data.nickname.toLowerCase() === username) {
            // Username is valid, send to webhook
            try {
                const webhookResponse = await fetch(WEBHOOK_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username })
                });

                console.log('Webhook Response Status:', webhookResponse.status);
                console.log('Webhook Response Headers:', webhookResponse.headers.raw());
                console.log('Webhook Response Text:', await webhookResponse.text());

                if (webhookResponse.ok) {
                    return res.status(200).json({ message: `Username "${username}" is valid and sent to webhook` });
                } else {
                    return res.status(500).json({ error: `Failed to send to webhook. Status: ${webhookResponse.status}` });
                }
            } catch (webhookError) {
                console.error('Webhook error:', webhookError);
                return res.status(500).json({ error: 'Webhook request failed' });
            }
        } else {
            return res.status(404).json({ error: 'Invalid username' });
        }
    } catch (error) {
        console.error('API error:', error);
        return res.status(500).json({ error: 'Error verifying username' });
    }
});

app.listen(port, () => {
    console.log(`Server running on port ${port}`);
});

