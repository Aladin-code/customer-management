<?php
// Standalone screen sharing page for viewers
$sessionId = isset($_GET['session']) ? htmlspecialchars($_GET['session']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Share Viewer</title>
    <link rel="stylesheet" href="css/screenshare.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .viewer-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .viewer-header {
            padding: 20px;
            background: #667eea;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .viewer-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .viewer-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #000;
        }
        .connection-info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        .disconnect-btn {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .disconnect-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        #remote-video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .loading-message {
            color: white;
            text-align: center;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="viewer-container">
        <div class="viewer-header">
            <h1>Customer Information - Screen Share</h1>
            <div class="connection-info">
                <span id="connection-status">Connecting...</span>
                <button class="disconnect-btn" onclick="window.close()">Disconnect</button>
            </div>
        </div>
        <div class="viewer-content">
            <video id="remote-video" autoplay style="display: none;"></video>
            <div id="loading-message" class="loading-message">
                <div class="loading-spinner"></div>
                Connecting to screen share session...
            </div>
        </div>
    </div>

    <script>
        class ScreenShareViewer {
            constructor(sessionId) {
                this.sessionId = sessionId;
                this.peerConnection = null;
                this.socket = null;
                this.isConnected = false;

                this.configuration = {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                };

                this.init();
            }

            init() {
                if (!this.sessionId) {
                    this.showError('No session ID provided');
                    return;
                }

                this.setupWebSocket();
                this.joinSession();
            }

            setupWebSocket() {
                // Use HTTP-based signaling server for cross-network compatibility
                this.signalingUrl = window.location.origin + window.location.pathname.replace(/[^/]*$/, '') + 'signaling-server.php';
                this.lastMessageCheck = 0;

                this.socket = {
                    send: (data) => {
                        this.sendToSignalingServer(JSON.parse(data));
                    },
                    onmessage: null,
                    readyState: 1
                };

                // Poll for messages from signaling server
                setInterval(() => this.pollForMessages(), 1000);
            }

            async sendToSignalingServer(message) {
                try {
                    const response = await fetch(this.signalingUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(message)
                    });

                    if (!response.ok) {
                        console.error('Signaling server error:', response.status);
                    }
                } catch (error) {
                    console.error('Failed to send message to signaling server:', error);
                }
            }

            async pollForMessages() {
                if (!this.sessionId) return;

                try {
                    const url = `${this.signalingUrl}?session=${this.sessionId}&since=${this.lastMessageCheck}`;
                    const response = await fetch(url);

                    if (response.ok) {
                        const data = await response.json();
                        this.lastMessageCheck = data.timestamp;

                        data.messages.forEach(message => {
                            if (this.socket.onmessage) {
                                this.socket.onmessage({ data: JSON.stringify(message) });
                            }
                        });
                    }
                } catch (error) {
                    console.error('Failed to poll signaling server:', error);
                }
            }

            async joinSession() {
                try {
                    this.peerConnection = new RTCPeerConnection(this.configuration);

                    // Handle ICE candidates
                    this.peerConnection.addEventListener('icecandidate', (event) => {
                        if (event.candidate) {
                            this.sendMessage({
                                type: 'ice-candidate',
                                candidate: event.candidate,
                                sessionId: this.sessionId
                            });
                        }
                    });

                    // Handle remote stream
                    this.peerConnection.addEventListener('track', (event) => {
                        console.log('Received remote stream');
                        const remoteVideo = document.getElementById('remote-video');
                        remoteVideo.srcObject = event.streams[0];
                        remoteVideo.style.display = 'block';
                        document.getElementById('loading-message').style.display = 'none';
                        this.updateConnectionStatus('Connected');
                        this.isConnected = true;
                    });

                    // Handle connection state changes
                    this.peerConnection.addEventListener('connectionstatechange', () => {
                        console.log('Connection state:', this.peerConnection.connectionState);
                        switch (this.peerConnection.connectionState) {
                            case 'connecting':
                                this.updateConnectionStatus('Connecting...');
                                break;
                            case 'connected':
                                this.updateConnectionStatus('Connected');
                                break;
                            case 'disconnected':
                                this.updateConnectionStatus('Disconnected');
                                break;
                            case 'failed':
                                this.showError('Connection failed');
                                break;
                        }
                    });

                    // Setup message handler
                    this.socket.onmessage = async (event) => {
                        const message = JSON.parse(event.data);
                        console.log('Received message:', message);

                        if (message.sessionId !== this.sessionId) return;

                        try {
                            switch (message.type) {
                                case 'offer':
                                    if (this.peerConnection.signalingState === 'stable' || this.peerConnection.signalingState === 'have-local-offer') {
                                        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));

                                        if (this.peerConnection.signalingState === 'have-remote-offer') {
                                            const answer = await this.peerConnection.createAnswer();
                                            await this.peerConnection.setLocalDescription(answer);

                                            this.sendMessage({
                                                type: 'answer',
                                                answer: answer,
                                                sessionId: this.sessionId
                                            });
                                        }
                                    }
                                    break;
                                case 'ice-candidate':
                                    if (this.peerConnection.remoteDescription) {
                                        await this.peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                                    }
                                    break;
                            }
                        } catch (error) {
                            console.error('Error handling message:', error);
                            // Reset connection on error
                            this.showError('Connection error. Please refresh to try again.');
                        }
                    };

                    // Send join request
                    this.sendMessage({
                        type: 'join-request',
                        sessionId: this.sessionId
                    });

                    this.updateConnectionStatus('Requesting to join...');

                } catch (error) {
                    console.error('Error joining session:', error);
                    this.showError('Failed to join session: ' + error.message);
                }
            }

            sendMessage(message) {
                if (this.socket && this.socket.readyState === 1) {
                    this.socket.send(JSON.stringify(message));
                }
            }

            updateConnectionStatus(status) {
                document.getElementById('connection-status').textContent = status;
            }

            showError(error) {
                document.getElementById('loading-message').innerHTML = `
                    <div style="color: #ff6b6b;">
                        <strong>Error:</strong> ${error}
                        <br><br>
                        <button onclick="location.reload()" style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Retry
                        </button>
                    </div>
                `;
            }
        }

        // Initialize viewer when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = '<?php echo $sessionId; ?>';
            if (sessionId) {
                new ScreenShareViewer(sessionId);
            } else {
                document.getElementById('loading-message').innerHTML = `
                    <div style="color: #ff6b6b;">
                        <strong>Error:</strong> No session ID provided
                        <br><br>
                        Please use a valid screen share URL.
                    </div>
                `;
            }
        });
    </script>
</body>
</html>