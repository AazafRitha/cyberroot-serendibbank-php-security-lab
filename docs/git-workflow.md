# Git workflow

This project has its own `main` branch and points to the new `AazafRitha/cyberroot-serendibbank-php-security-lab` repository. Keep the earlier banking lab repository untouched.

For each feature or security exercise:

```powershell
git switch main
git pull --ff-only
git switch -c feature/short-description
```

Make a focused change, review it with `git diff`, then commit:

```powershell
git add .
git commit -m "Add short description"
git push -u origin feature/short-description
```

Open a pull request into `main`, review the code and scenario boundary, then merge. Keep `.env`, real secrets, generated dependencies, and database data out of commits. The old `serendibbank-banking-security-lab` repository is unrelated and has no remote configured here.
