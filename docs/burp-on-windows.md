# Test the local site with Burp Suite on Windows

This guide uses the local Docker site at `http://127.0.0.1:8080`. Keep Burp and the website on different ports. Burp's default proxy listener also uses 8080, so change the Burp listener to **127.0.0.1:8081** while the website remains on **127.0.0.1:8080**.

1. In PowerShell in the project folder, run `docker compose up -d` and `docker compose ps`. Check that `web` is running and `db` is healthy. Open `http://127.0.0.1:8080/` in a normal browser first.
2. In Burp, open **Settings → Tools → Proxy → Proxy listeners**. Edit the loopback listener to use port **8081**. Check that the listener is running. If 8081 is occupied, choose another free port and use that same port in the browser proxy setting.
3. In Burp, select **Proxy → Intercept → Open browser**. Use Burp's browser for the first test. It is preconfigured for Burp. Enter `http://127.0.0.1:8080/register` in its address bar. Turn **Intercept off** while browsing normally.
4. Register with a new fictional email and a password of at least 12 characters. Sign in and inspect **Proxy → HTTP history** for the `GET /register`, `POST /register`, and sign-in requests. Do not put passwords or session cookies in screenshots you share.
5. Add only `http://127.0.0.1:8080` to **Target scope**. Use **Send to Repeater** on an interesting request to compare an unchanged request with one changed value.

If you see the **Burp certificate page** at `http://burpsuite`, Burp is working as a proxy. That page is for downloading Burp's CA certificate. Leave it and enter the SerendibBank URL above. The local SerendibBank site uses HTTP, so you do not need to install a certificate for this test. Burp's own browser also works with HTTPS without a separate certificate setup.

If you use Chrome, Edge, or Firefox instead of Burp's browser, configure that browser's HTTP proxy to **127.0.0.1:8081**, not 8080. The browser's destination URL remains **http://127.0.0.1:8080**. Installing Burp's CA certificate is relevant when that external browser tests HTTPS sites; follow [PortSwigger's browser-specific instructions](https://portswigger.net/burp/documentation/desktop/external-browser-config/certificate) for that case.

If the bank page does not load, check the two ports separately. `docker compose ps` should show `127.0.0.1:8080->80/tcp` for `web`, while Burp's listener should say 8081 and running. If HTTP history stays empty, confirm that you opened the site in Burp's browser or that your external browser is using port 8081 as its proxy. See [PortSwigger's Proxy listener guide](https://portswigger.net/burp/documentation/desktop/settings/tools/proxy) and [Burp browser guide](https://portswigger.net/burp/documentation/desktop/tools/burps-browser).
