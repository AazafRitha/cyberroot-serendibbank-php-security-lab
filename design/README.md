# Offline front-end preview

Open `home.html` directly in a browser. The Accounts link opens `accounts.html`. These are static copies of the current PHP views so visual work can continue without Docker, PHP, or MySQL.

The live site still uses `app/Views/` and the shared stylesheet at `public/assets/style.css`. Keep the static preview aligned with those views while designing, then verify the PHP pages in Docker once it is installed. This `design/` folder is excluded from the Docker image and is outside the public document root.
