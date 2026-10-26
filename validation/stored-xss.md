# Stored XSS: secure messages

1. In `scenarios/stored-xss`, run `docker compose up --build -d`.
2. Visit <http://localhost:8083>, enter an ordinary message, and submit it. It appears in Conversation.
3. Enter `<img src=x onerror=alert(1)>` and submit it.
4. **Expected flaw:** the browser executes the image error handler when the saved message is rendered. It will recur on reload within the same browser session. Use only this local exercise.
5. Compare with the core support request and staff dashboard. Those views must escape message text before placing it in HTML.

Reset: clear the browser cookies for `localhost:8083` or restart the browser session; then `docker compose down`. Fix concept: context-appropriate output encoding and a restrictive Content Security Policy.
