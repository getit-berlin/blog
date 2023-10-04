If you commit very big files and want to remove them from the git repo then use this usefull snippet:

i.e. if you want to remove `system/backup_V1.sql`

`git filter-branch --force --index-filter \`  
`'git rm --cached --ignore-unmatch system/backup_V1.sql' \`  
`--prune-empty --tag-name-filter cat -- --all`  

After removing the file you have to overwrite the remote repo.

You can use this snippet to remove unwanted files from git including all commits! This might be usefull to clean a repo from allready ignored files.  
This might be also usefull if you want to remove credentials from commited files.
