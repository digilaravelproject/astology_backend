const WebSocket = require('ws');

const ws = new WebSocket('ws://127.0.0.1:8080/app/astrology-key?protocol=7&client=js&version=8.0.1&flash=false');

ws.on('open', function open() {
  console.log('✅ Connection established from Node.js (V3)');
  ws.close();
});

ws.on('error', function error(err) {
  console.error('❌ Connection error:', err.message);
});
