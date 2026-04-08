/* [ignoring loop detection] */
const BASE_URL = 'http://127.0.0.1:8000/api/v1';
let pusher, channel, activeSession = null;
let userId, token;

// Utility functions
const el = (id) => document.getElementById(id);

const log = (event, data) => {
    const entry = document.createElement('div');
    entry.className = 'log-entry';
    entry.innerHTML = `
        <span class="log-event">[${new Date().toLocaleTimeString()}] ${event}</span>
        <pre class="log-payload">${JSON.stringify(data, null, 2)}</pre>
    `;
    const container = el('event-logs');
    container.prepend(entry);
};

// Event Handlers
el('btn-connect').onclick = () => {
    token = el('auth-token').value;
    userId = el('user-id').value;

    if (!token || !userId) {
        alert('Please enter both Token and User ID');
        return;
    }

    if (pusher) pusher.disconnect();

    pusher = new Pusher('astrology-key', {
        wsHost: '127.0.0.1',
        wsPort: 8080,
        wssPort: 8080,
        forceTLS: false,
        enabledTransports: ['ws'],
        cluster: 'mt1',
        userAuthentication: {
            endpoint: `${BASE_URL}/broadcasting/auth`,
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        },
        channelAuthorization: {
            endpoint: `${BASE_URL}/broadcasting/auth`,
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }
    });

    pusher.connection.bind('state_change', (states) => {
        el('status-text').innerText = states.current;
        el('status-dot').style.background = states.current === 'connected' ? '#238636' : '#da3633';
    });

    channel = pusher.subscribe(`private-user.${userId}`);
    
    channel.bind('pusher:subscription_succeeded', () => {
        el('btn-pulse').disabled = false;
        log('Subscription', { channel: `private-user.${userId}`, status: 'Success' });
        loadSessions();
    });

    channel.bind('pusher:subscription_error', (status) => {
        log('Subscription Error', status);
        alert('Subscription failed. Check console/logs.');
    });

    // Real-time Event Bindings
    channel.bind('ChatInitiated', (data) => {
        const session = data.session || data;
        log('EVENT: ChatInitiated', session);
        loadSessions();
        
        console.log("Checking Alert Trigger:", { 
            sessionProvider: session.provider_id, 
            currentUserId: userId 
        });

        // Use string comparison to be safe
        if (String(session.provider_id) === String(userId)) {
            // 1. Show HTML Alert
            el('incoming-alert').classList.remove('hide');
            el('alert-text').innerText = `Incoming Chat from User #${session.consumer_id}`;
            
            // 2. Browser Popup (for safety)
            setTimeout(() => { alert(`New Chat Request from User #${session.consumer_id}`); }, 100);

            el('btn-accept-now').onclick = async () => {
                await selectSession(session);
                el('btn-accept').click();
                el('incoming-alert').classList.add('hide');
            };
            
            el('btn-ignore-alert').onclick = () => {
                el('incoming-alert').classList.add('hide');
            };
        }
    });

    channel.bind('ChatAccepted', (data) => {
        log('EVENT: ChatAccepted', data);
        loadSessions();
    });

    channel.bind('MessageSent', (data) => {
        const msg = data.messageData || data.message || data;
        log('EVENT: MessageSent', data);
        
        if (activeSession && String(msg.chat_session_id) === String(activeSession.id)) {
            appendMessage(msg);
            
            // AUTO-SEEN: Notify the sender immediately
            if (String(msg.sender_id) !== String(userId)) {
                api('POST', `/chat/${activeSession.id}/sync-status`, {
                    status: 'seen',
                    message_ids: [msg.id]
                });
            }
        }
    });

    // Try binding with and without namespace to be safe
    const handleStatusUpdate = (data) => {
        log('EVENT: MessageStatusUpdated', data);
        const { message_ids, status } = data;
        message_ids.forEach(id => {
            const statusEl = document.querySelector(`[data-msg-id="${id}"] .msg-status`);
            if (statusEl) {
                statusEl.innerText = status === 'seen' ? '🔵🔵' : (status === 'delivered' ? '✅✅' : '✅');
            }
        });
    };

    channel.bind('MessageStatusUpdated', handleStatusUpdate);
    channel.bind('.App\\Events\\MessageStatusUpdated', handleStatusUpdate);

    // Listen for Whispers (Fast UI Fix - Phase 6)
    channel.bind('client-typing', (data) => {
        if (activeSession && String(data.session_id) === String(activeSession.id)) {
            el('typing-indicator').innerText = data.is_typing ? 'User is typing...' : '';
            el('typing-indicator').classList.toggle('hide', !data.is_typing);
        }
    });

    channel.bind('client-seen', (data) => {
        if (activeSession && String(data.session_id) === String(activeSession.id)) {
            data.message_ids.forEach(id => {
                const statusEl = document.querySelector(`[data-msg-id="${id}"] .msg-status`);
                if (statusEl) statusEl.innerText = '🔵🔵';
            });
        }
    });
};

async function api(method, url, data = null) {
    try {
        const config = {
            method,
            url: BASE_URL + url,
            data,
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        const response = await axios(config);
        return response.data;
    } catch (error) {
        log('API ERROR', error.response?.data || error.message);
        return null;
    }
}

async function loadSessions() {
    const response = await api('GET', '/chat/sessions');
    if (!response) return;

    const list = el('session-list');
    list.innerHTML = '';
    
    let sessions = response.data || [];
    if (response.data && response.data.data) {
        sessions = response.data.data;
    }

    if (sessions.length === 0) {
        list.innerHTML = '<div class="empty-hint">No active chats</div>';
        return;
    }

    sessions.forEach(s => {
        const div = document.createElement('div');
        div.className = 'session-item glass-card';
        div.style.margin = '5px 0';
        div.style.padding = '10px';
        div.style.cursor = 'pointer';
        div.innerHTML = `
            <strong>Session #${s.id}</strong><br>
            <small>${s.status} | ID: ${s.consumer_id == userId ? s.provider_id : s.consumer_id}</small>
        `;
        div.onclick = () => selectSession(s);
        list.appendChild(div);
    });
}

async function selectSession(session) {
    activeSession = session;
    el('recipient-name').innerText = `Chat Session #${session.id}`;
    el('msg-input').disabled = false;
    el('btn-send').disabled = false;

    // UI Updates
    if (session.status === 'initiated' && session.provider_id == userId) {
        el('btn-accept').classList.remove('hide');
    } else {
        el('btn-accept').classList.add('hide');
    }

    // Load History
    const response = await api('GET', `/chat/${session.id}/messages`);
    const container = el('message-container');
    container.innerHTML = '';
    
    if (response && response.data) {
        const messages = response.data.data || response.data || [];
        messages.forEach(m => appendMessage(m));
    }
}

function appendMessage(m) {
    const container = el('message-container');
    const div = document.createElement('div');
    div.className = `message ${String(m.sender_id) === String(userId) ? 'sent' : 'received'}`;
    div.setAttribute('data-msg-id', m.id);
    
    const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const statusStr = m.is_read ? '🔵🔵' : (m.is_delivered ? '✅✅' : '✅');
    const statusHtml = String(m.sender_id) === String(userId) ? `<span class="msg-status">${statusStr}</span>` : '';

    div.innerHTML = `
        <div class="message-bubble">
            ${m.message}
            <div class="message-meta">
                <span class="msg-time">${time}</span>
                ${statusHtml}
            </div>
        </div>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;

    // Trigger Seen Whisper & API Sync (Phase 6 & 7)
    if (String(m.sender_id) !== String(userId)) {
        channel.trigger('client-seen', { session_id: activeSession.id, message_ids: [m.id] });
        api('POST', `/chat/${activeSession.id}/sync-status`, { status: 'seen', message_ids: [m.id] });
    }
}

// Button Actions
el('btn-pulse').onclick = async () => {
    const res = await api('POST', '/presence/pulse');
    if (res) {
        el('btn-pulse').innerText = 'Online ✅';
        el('btn-pulse').classList.replace('btn-success', 'btn-primary');
        log('Action', 'Presence Pulse Sent: Status=Online');
    }
};

el('btn-accept').onclick = async () => {
    const res = await api('POST', `/chat/${activeSession.id}/accept`);
    if (res) {
        el('btn-accept').classList.add('hide');
        loadSessions();
        log('Action', 'Chat Accepted');
    }
};

el('input-msg').oninput = () => {
    if (!activeSession) return;
    channel.trigger('client-typing', {
        session_id: activeSession.id,
        is_typing: el('input-msg').value.length > 0
    });
};

el('btn-send').onclick = async () => {
    const text = el('input-msg').value;
    if (!text || !activeSession) return;

    el('input-msg').value = '';
    el('input-msg').dispatchEvent(new Event('input')); // Stop typing whisper

    const res = await api('POST', `/chat/${activeSession.id}/message`, { message: text, type: 'text' });
    if (res) appendMessage(res.data || res);
};

let typingTimeout;
el('msg-input').oninput = () => {
    if (channel && activeSession) {
        channel.trigger('client-typing', { session_id: activeSession.id, is_typing: true });
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            channel.trigger('client-typing', { session_id: activeSession.id, is_typing: false });
        }, 2000);
    }
};

el('btn-clear-logs').onclick = () => el('event-logs').innerHTML = '';
el('btn-new-chat').onclick = () => {
    const targetId = prompt('Enter User ID to start chat with:');
    if (targetId) api('POST', '/chat/initiate', { provider_id: targetId }).then(loadSessions);
};
