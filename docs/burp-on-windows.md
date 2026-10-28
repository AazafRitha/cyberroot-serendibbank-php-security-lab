# Test the local site with Burp Suite on Windows

Burp and the bank site must use different local ports. Burp's default proxy listener uses **127.0.0.1:8080**. On this laptop, the project's `.env` sets `APP_PORT=8082`, so the website uses **http://127.0.0.1:8082**. A fresh project copy defaults to 8080 until you change `APP_PORT` in `.env`.

1. Open the project folder in PowerShell or VS Code. If Burp already listens on 8080, set `APP_PORT=8082` in `.env`, then run `docker compose up -d` and `docker compose ps`. The web service should show `127.0.0.1:8082->80/tcp` and the database should be healthy.
2. In Burp, open **Settings → Tools → Proxy → Proxy listeners**. Check that the loopback listener is running on **8080**. You can alternatively move Burp to 8081 and keep the website on 8080; the two ports simply need to differ.
3. Select **Proxy → Intercept → Open browser**. In Burp's browser, enter **http://127.0.0.1:8082/register**. Turn **Intercept off** while browsing normally.
4. Register with invented identity and address details, sign in, and inspect **Proxy → HTTP history** for the registration requests. Do not include passwords, document numbers, session cookies, or CSRF tokens in screenshots you share.
5. Add only the bank URL to **Target scope**. Use **Send to Repeater** on an interesting request to compare an unchanged request with one changed value.

If you see the **Burp certificate page** at `http://burpsuite`, Burp is working as a proxy. Leave that page and enter the bank URL above. The local site uses HTTP, so a certificate is unnecessary for this test. Burp's own browser also works with HTTPS without a separate certificate setup.

If you use Chrome, Edge, or Firefox instead of Burp's browser, configure its HTTP proxy as **127.0.0.1:8080** while the destination URL remains **http://127.0.0.1:8082**. Installing Burp's CA certificate is relevant when that external browser tests HTTPS sites; follow [PortSwigger's browser-specific instructions](https://portswigger.net/burp/documentation/desktop/external-browser-config/certificate) for that case.

If the bank page does not load, check the two ports separately. `docker compose ps` should show the web container on the port specified in `.env`; Burp's listener must use another port and say running. If HTTP history stays empty, confirm that you opened the site in Burp's browser or configured your external browser's proxy to the Burp listener. See [PortSwigger's Proxy listener guide](https://portswigger.net/burp/documentation/desktop/settings/tools/proxy) and [Burp browser guide](https://portswigger.net/burp/documentation/desktop/tools/burps-browser).
