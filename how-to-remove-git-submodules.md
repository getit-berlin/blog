How to remove git submodules? Command init and deinit is very important. Here are some userfull snippets:

`git submodule deinit --all -f`
`git rm sm/my-submodule`

Commit chages:

`git commit -m "Remove submodule sm/my-submodule"`

Clear git cache for this submodule:

`rm -rf .git/modules/sm/my-submodule`
`git status`

If you want to replace removed submodule with corresponding files, then add files. Copy submodule files by hand into specific directory.

`cp ../tmp/my-submodule-file.txt sm/my-submodule-file.txt`
`git add .`

Finalize Repo:

`git submodule deinit --all -f`
`git submodule init`
`git submodule update --recursive --remote`

Repo should be pushed and carefully pulled if necessary.
