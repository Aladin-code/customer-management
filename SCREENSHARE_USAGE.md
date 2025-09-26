# Screen Share Utility - Usage Guide

## Overview
The screen share utility allows staff members to share their screen with co-workers when viewing customer information. This enables real-time collaboration and assistance when reviewing customer data.

## Features
- **Real-time screen sharing**: Share your entire screen with co-workers
- **Secure WebRTC connection**: Peer-to-peer connection for privacy
- **Easy URL sharing**: Generate a shareable URL for co-workers to join
- **Chrome API integration**: Uses the latest Chrome screen capture API
- **Responsive design**: Works on desktop and mobile devices

## How to Use

### Starting a Screen Share Session

1. **Navigate to the customer information page** (`index.php` or `review.php`)
2. **Look for the Screen Share Utility** in the top-right corner of the page
3. **Click "Start Sharing"** button
4. **Grant screen capture permission** when prompted by Chrome
5. **Select what to share**:
   - Entire screen (recommended)
   - Application window
   - Browser tab
6. **Copy the share URL** using the "Copy Share URL" button
7. **Send the URL** to your co-worker via email, chat, or any messaging platform

### Joining a Screen Share Session

**Option 1: Direct URL**
1. **Click the shared URL** received from your co-worker
2. **Wait for connection** - you'll automatically see their screen

**Option 2: Manual Navigation**
1. **Go to** `screenshare.php?session=SESSION_ID`
2. **Replace SESSION_ID** with the session ID from the URL

### Ending a Session

1. **Click "Stop Sharing"** to end the session
2. **Close the browser tab** on the viewer side
3. **The connection will automatically disconnect** when the sharer stops

## Technical Requirements

### Browser Support
- **Chrome 72+** (required for screen sharing)
- **Firefox 66+** (viewer only)
- **Safari 13+** (viewer only)
- **Edge 79+** (full support)

### Permissions Required
- **Screen capture permission** (granted when starting a share)
- **Camera/microphone access** (optional, for audio sharing)

### Network Requirements
- **HTTPS connection** (required for screen capture API)
- **Open firewall** for WebRTC traffic
- **STUN server access** for connection establishment

## Security Features

### Privacy Protection
- **Peer-to-peer connection** - no data stored on servers
- **Temporary session IDs** - expire when session ends
- **Local signaling** - uses browser storage for message passing
- **No recording** - screen content is not saved or logged

### Access Control
- **Unique session URLs** - each session has a unique identifier
- **Manual permission** - sharer must explicitly start sharing
- **Easy disconnection** - either party can end the session

## Troubleshooting

### Common Issues

**"Screen sharing not supported"**
- Update to Chrome 72 or later
- Ensure you're on HTTPS (required for screen capture)
- Check browser permissions

**"Connection failed"**
- Check firewall settings
- Ensure both users are on stable internet connections
- Try refreshing the page and starting a new session

**"Permission denied"**
- Grant screen capture permission in Chrome
- Check browser privacy settings
- Ensure the page is loaded over HTTPS

**"No video appears"**
- Wait a few seconds for connection establishment
- Check that the sharer has started sharing
- Verify the session ID in the URL is correct

### Browser Permissions

**Chrome Settings:**
1. Go to `chrome://settings/content/camera`
2. Ensure the site is allowed to access camera/screen
3. Check `chrome://settings/content/microphone` for audio

**Firewall Settings:**
- Allow WebRTC traffic (UDP ports 16384-32767)
- Ensure STUN server access (stun.l.google.com:19302)

## Use Cases

### Customer Support Scenarios
1. **Complex customer cases** - Get immediate help from supervisors
2. **Training new staff** - Show processes in real-time
3. **Quality assurance** - Review customer interactions together
4. **Technical issues** - Get IT support while maintaining customer context

### Best Practices
1. **Always ask permission** before sharing sensitive customer data
2. **End sessions promptly** when collaboration is complete
3. **Use secure communication** to share URLs
4. **Keep sessions brief** to minimize data exposure
5. **Document any decisions** made during shared sessions

## API Integration

### For Developers

The screen sharing functionality can be extended with additional features:

```javascript
// Access the screen share manager
const screenShare = window.screenShareManager;

// Custom event listeners
screenShare.addEventListener('shareStarted', (event) => {
    console.log('Screen share started:', event.sessionId);
});

screenShare.addEventListener('viewerJoined', (event) => {
    console.log('Viewer joined:', event.viewerId);
});
```

### Server-Side Integration

For production deployments, consider implementing:
- **WebSocket signaling server** for real-time message passing
- **Session management** with database storage
- **User authentication** integration
- **Audit logging** for compliance

## Support

For technical issues or feature requests:
1. Check browser console for error messages
2. Verify network connectivity and permissions
3. Test with different browsers if issues persist
4. Contact your system administrator for firewall-related issues

---

*This screen sharing utility is designed for internal business use with customer information. Always follow your organization's privacy and data protection policies when sharing screens containing sensitive data.*