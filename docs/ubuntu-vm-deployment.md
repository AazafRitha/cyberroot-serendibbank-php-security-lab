# Put SerendibBank on an Ubuntu VM

This guide uses the repository's `compose.vm.yaml`. It builds the PHP site into a container, keeps MySQL in a private Docker network, and binds the website only to the VM's `127.0.0.1:8082`. A reverse proxy can later make the site reachable through a domain with HTTPS. The Windows laptop's Docker Desktop is not required on the Ubuntu VM.

Use an isolated VM created for this project. Keep it away from production systems and sensitive networks, use fictional records only, and agree on the testing scope before inviting others. The application intentionally contains vulnerabilities. A Docker container is not a security boundary for a publicly attacked VM.

## 1. Connect to Ubuntu

From PowerShell, replace `VM_IP` and `ubuntu` with your VM's address and SSH user:

```powershell
ssh ubuntu@VM_IP
```

The following commands run **inside the Ubuntu VM**. For an internal VM, first confirm that it has outbound access to Docker Hub, GitHub, and Ubuntu package repositories.

## 2. Install Docker Engine and Compose

Follow [Docker's Ubuntu installation instructions](https://docs.docker.com/engine/install/ubuntu/) if your Ubuntu version differs or a package command fails. The current official repository method is:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker compose version
```

Keep using `sudo docker` in this guide. Docker access grants powerful control of the VM, so do not add untrusted users to the `docker` group.

## 3. Copy the project from GitHub and start it privately

```bash
git clone https://github.com/AazafRitha/cyberroot-serendibbank-php-security-lab.git
cd cyberroot-serendibbank-php-security-lab
cp .env.example .env
nano .env
```

In `.env`, set `APP_PORT=8082`, leave `APP_HTTPS=0` for the first private check, and replace `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD` with two different long random values. `openssl rand -hex 24` can generate each one. Do not post the resulting values in chat or commit `.env` to Git.

```bash
chmod 600 .env
sudo docker compose -f compose.vm.yaml config --quiet
sudo docker compose -f compose.vm.yaml up -d --build
sudo docker compose -f compose.vm.yaml ps
curl -fsS -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8082/
```

Expect HTTP status `200`, a running `web` container, and a healthy `db` container. MySQL has no published host port. If startup fails, run `sudo docker compose -f compose.vm.yaml logs --tail=80 web db`.

To see the VM site privately from your Windows laptop, open a **second PowerShell window** and keep this SSH tunnel running:

```powershell
ssh -L 8090:127.0.0.1:8082 ubuntu@VM_IP
```

Then open <http://127.0.0.1:8090/> on the laptop. Port `8090` is the laptop's temporary viewing port; Burp may continue using `8080`. Close the SSH window to close the tunnel.

## 4. Add a public domain and HTTPS when the VM is ready

First obtain a domain or subdomain and point its DNS `A` record to the VM's public IP. If this is an internal VM behind a router or company firewall, the network administrator must arrange public routing or port forwarding for TCP 80 and 443. Keep SSH limited to administrators; do not expose MySQL, Docker's daemon, or port 8082. Set the VM or cloud firewall to allow only required ingress. Docker-published ports can bypass UFW rules, which is another reason this Compose file binds the application to `127.0.0.1` only. [Docker documents this UFW interaction](https://docs.docker.com/engine/install/ubuntu/#firewall-limitations).

Install [Caddy from its official Ubuntu package instructions](https://caddyserver.com/docs/install#debian-ubuntu-raspbian):

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl gnupg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo chmod o+r /usr/share/keyrings/caddy-stable-archive-keyring.gpg
sudo chmod o+r /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy
sudo nano /etc/caddy/Caddyfile
```

Replace `bank.example.org` with your actual domain in the Caddyfile:

```caddyfile
bank.example.org {
    reverse_proxy 127.0.0.1:8082
}
```

Allow SSH before enabling the VM firewall so you do not lock yourself out. If SSH uses a different port, allow that port instead of the `OpenSSH` profile. Cloud and perimeter firewall rules are separate.

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

In the project's `.env`, change `APP_HTTPS=1`, then recreate the web container and test the domain:

```bash
sudo docker compose -f compose.vm.yaml up -d --force-recreate
curl -fsS -o /dev/null -w '%{http_code}\n' https://bank.example.org/
```

Caddy obtains and renews the HTTPS certificate when DNS points to the server and ports 80/443 are reachable. [Caddy's HTTPS guide](https://caddyserver.com/docs/quick-starts/https) explains those requirements. If HTTPS fails, check `sudo journalctl -u caddy --no-pager -n 80`, DNS, and both firewalls. For the separate management subdomain and company SMTP delivery, follow the [email and management setup](email-and-management.md) before inviting users.

## 5. Operate and update the VM

```bash
cd cyberroot-serendibbank-php-security-lab
sudo docker compose -f compose.vm.yaml ps
sudo docker compose -f compose.vm.yaml logs --tail=80 web
git pull --ff-only
sudo docker compose -f compose.vm.yaml up -d --build
```

The MySQL, upload, OTP, and identity-key volumes survive `docker compose down`. **Do not run `down -v`** on a VM whose records you need. Snapshot or back up the VM before updates; the database and `identity_keys` volume must be restored together for document uniqueness checks to keep working.

### Before inviting public testers

- The default OTP transport writes a code to a local Docker volume. Set up and verify company SMTP delivery before inviting remote users; otherwise they cannot receive their sign-in codes.
- The site is intentionally vulnerable. Keep the VM isolated from other systems, never use real identity or financial information, set clear testing scope and contact details, monitor the VM, and have a way to stop access or restore a snapshot.
- Do not reuse this VM for any other service. The public banking-like interface does not verify real customers or documents.
