// In server.js
const basicAuth = require('express-basic-auth');

app.use(basicAuth({
    users: { 'admin': 'securepassword' },
    challenge: true
}));
