# Offline front-end preview

Open `home.html` directly in a browser. Its navigation opens static previews of Personal, Cards, Offers, Digital banking, Locations, Support, Login, Verify, and Accounts. The offer and location filters work in the preview. This lets visual work continue without Docker, PHP, or MySQL.

The live site uses `app/Views/` and the shared assets in `public/assets/`. After editing a marketing view or layout, run `powershell -ExecutionPolicy Bypass -File scripts/build-preview.ps1` from the project root to refresh the static snapshots. Sign-in, applications, transfers, support requests, saved conversations, and staff actions require the live PHP/MySQL app. This `design/` folder is outside the public document root.
