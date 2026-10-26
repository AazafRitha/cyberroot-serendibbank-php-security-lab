# Git workflow

Open the repository folder in VS Code and use its Source Control panel or PowerShell. For each change:

```powershell
git status
git add .
git commit -m "Describe the change"
git push
```

Review the changed files before committing. Keep `.env`, generated passwords, database files, and personal information out of commits. Use a branch and pull request when you want to review a change before it reaches `main`.
