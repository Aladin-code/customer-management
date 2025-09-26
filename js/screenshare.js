class ScreenShareManager {
    constructor() {
        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.socket = null;
        this.isSharing = false;
        this.isViewing = false;
        this.sessionId = null;

        this.configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        this.initializeUI();
        this.setupWebSocket();
    }

    initializeUI() {
        const container = document.createElement('div');
        container.id = 'screenshare-container';
        container.className = 'screenshare-container';
        container.innerHTML = `
            <div class="screenshare-controls">
                <h3>Screen Share Utility</h3>
                <div class="control-buttons">
                    <button id="start-share-btn" class="btn btn-primary">Start Sharing</button>
                    <button id="stop-share-btn" class="btn btn-secondary" style="display: none;">Stop Sharing</button>
                    <button id="copy-url-btn" class="btn btn-info" style="display: none;">Copy Share URL</button>
                </div>
                <div id="share-status" class="status-message"></div>
                <div id="share-url-display" style="display: none;">
                    <p>Share this URL with co-workers:</p>
                    <input type="text" id="share-url-input" readonly>
                </div>
            </div>
            <div id="video-container" class="video-container">
                <video id="local-video" autoplay muted style="display: none;"></video>
                <video id="remote-video" autoplay style="display: none;"></video>
            </div>
        `;

        document.body.appendChild(container);
        this.bindEvents();
    }

    bindEvents() {
        document.getElementById('start-share-btn').addEventListener('click', () => this.startScreenShare());
        document.getElementById('stop-share-btn').addEventListener('click', () => this.stopScreenShare());
        document.getElementById('copy-url-btn').addEventListener('click', () => this.copyShareUrl());
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

    async startScreenShare() {
        try {
            this.localStream = await navigator.mediaDevices.getDisplayMedia({
                video: true,
                audio: true
            });

            const localVideo = document.getElementById('local-video');
            localVideo.srcObject = this.localStream;
            localVideo.style.display = 'block';

            this.sessionId = this.generateSessionId();
            this.isSharing = true;

            this.updateUI();
            this.setupPeerConnection();
            this.updateStatus('Screen sharing started. Waiting for viewers...');

            // Handle stream end
            this.localStream.getVideoTracks()[0].addEventListener('ended', () => {
                this.stopScreenShare();
            });

        } catch (error) {
            console.error('Error starting screen share:', error);
            this.updateStatus('Error starting screen share: ' + error.message);
        }
    }

    stopScreenShare() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }

        this.isSharing = false;
        this.sessionId = null;

        document.getElementById('local-video').style.display = 'none';
        document.getElementById('remote-video').style.display = 'none';

        this.updateUI();
        this.updateStatus('Screen sharing stopped.');
    }

    setupPeerConnection() {
        this.peerConnection = new RTCPeerConnection(this.configuration);
        this.iceCandidatesQueue = []; // Queue for early ICE candidates

        // Add local stream to peer connection
        this.localStream.getTracks().forEach(track => {
            console.log('Adding track to peer connection:', track.kind, track.label);
            this.peerConnection.addTrack(track, this.localStream);
        });

        // Handle ICE candidates
        this.peerConnection.addEventListener('icecandidate', (event) => {
            if (event.candidate) {
                this.sendSignalingMessage({
                    type: 'ice-candidate',
                    candidate: event.candidate,
                    sessionId: this.sessionId
                });
            }
        });

        // Handle remote stream
        this.peerConnection.addEventListener('track', (event) => {
            console.log('Received track:', event.track.kind, 'with streams:', event.streams.length);
            const remoteVideo = document.getElementById('remote-video');
            if (event.streams.length > 0) {
                remoteVideo.srcObject = event.streams[0];
                remoteVideo.style.display = 'block';
                console.log('Set remote video source');
            }
        });

        // Setup signaling message handler
        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);

            if (message.sessionId !== this.sessionId) return;

            switch (message.type) {
                case 'join-request':
                    await this.handleJoinRequest();
                    break;
                case 'answer':
                    console.log('Received answer, setting remote description');
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
                    console.log('Remote description set, processing queued ICE candidates');

                    // Process any queued ICE candidates
                    while (this.iceCandidatesQueue.length > 0) {
                        const candidate = this.iceCandidatesQueue.shift();
                        await this.peerConnection.addIceCandidate(candidate);
                        console.log('Added queued ICE candidate');
                    }
                    break;
                case 'ice-candidate':
                    const candidate = new RTCIceCandidate(message.candidate);
                    if (this.peerConnection.remoteDescription) {
                        console.log('Adding ICE candidate immediately');
                        await this.peerConnection.addIceCandidate(candidate);
                    } else {
                        console.log('Queueing ICE candidate (no remote description yet)');
                        this.iceCandidatesQueue.push(candidate);
                    }
                    break;
            }
        };
    }

    async handleJoinRequest() {
        try {
            console.log('Creating offer for join request, tracks:', this.peerConnection.getSenders().length);

            // Create offer with explicit options
            const offer = await this.peerConnection.createOffer({
                offerToReceiveVideo: false,
                offerToReceiveAudio: false
            });

            console.log('Offer created:', offer);
            await this.peerConnection.setLocalDescription(offer);

            this.sendSignalingMessage({
                type: 'offer',
                offer: offer,
                sessionId: this.sessionId
            });

            this.updateStatus('Co-worker joined the screen share.');
        } catch (error) {
            console.error('Error handling join request:', error);
        }
    }

    async joinScreenShare(sessionId) {
        this.sessionId = sessionId;
        this.isViewing = true;

        this.peerConnection = new RTCPeerConnection(this.configuration);

        // Handle ICE candidates
        this.peerConnection.addEventListener('icecandidate', (event) => {
            if (event.candidate) {
                this.sendSignalingMessage({
                    type: 'ice-candidate',
                    candidate: event.candidate,
                    sessionId: this.sessionId
                });
            }
        });

        // Handle remote stream
        this.peerConnection.addEventListener('track', (event) => {
            const remoteVideo = document.getElementById('remote-video');
            remoteVideo.srcObject = event.streams[0];
            remoteVideo.style.display = 'block';
            this.updateStatus('Connected to screen share.');
        });

        // Setup signaling message handler
        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);

            if (message.sessionId !== this.sessionId) return;

            switch (message.type) {
                case 'offer':
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));
                    const answer = await this.peerConnection.createAnswer();
                    await this.peerConnection.setLocalDescription(answer);

                    this.sendSignalingMessage({
                        type: 'answer',
                        answer: answer,
                        sessionId: this.sessionId
                    });
                    break;
                case 'ice-candidate':
                    await this.peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
                    break;
            }
        };

        // Send join request
        this.sendSignalingMessage({
            type: 'join-request',
            sessionId: this.sessionId
        });

        this.updateStatus('Joining screen share...');
    }

    sendSignalingMessage(message) {
        if (this.socket && this.socket.readyState === 1) {
            this.socket.send(JSON.stringify(message));
        }
    }

    generateSessionId() {
        return Math.random().toString(36).substr(2, 9);
    }

    getShareUrl() {
        const baseUrl = window.location.origin + window.location.pathname.replace(/[^/]*$/, '') + 'screenshare.php';
        return `${baseUrl}?session=${this.sessionId}`;
    }

    copyShareUrl() {
        const shareUrl = this.getShareUrl();
        const urlInput = document.getElementById('share-url-input');
        urlInput.value = shareUrl;
        urlInput.select();
        document.execCommand('copy');

        this.updateStatus('Share URL copied to clipboard!');
        setTimeout(() => {
            if (this.isSharing) {
                this.updateStatus('Screen sharing active. Waiting for viewers...');
            }
        }, 2000);
    }

    updateUI() {
        const startBtn = document.getElementById('start-share-btn');
        const stopBtn = document.getElementById('stop-share-btn');
        const copyBtn = document.getElementById('copy-url-btn');
        const urlDisplay = document.getElementById('share-url-display');
        const urlInput = document.getElementById('share-url-input');

        if (this.isSharing) {
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-block';
            copyBtn.style.display = 'inline-block';
            urlDisplay.style.display = 'block';
            urlInput.value = this.getShareUrl();
        } else {
            startBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            copyBtn.style.display = 'none';
            urlDisplay.style.display = 'none';
        }
    }

    updateStatus(message) {
        document.getElementById('share-status').textContent = message;
    }
}

// Initialize screen share manager when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.screenShareManager = new ScreenShareManager();

    // Check if this is a screen share viewer URL
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('screenshare');

    if (sessionId) {
        // Hide the form and show only the screen share viewer
        document.querySelector('.container').style.display = 'none';
        document.getElementById('screenshare-container').classList.add('viewer-mode');
        window.screenShareManager.joinScreenShare(sessionId);
    }
});