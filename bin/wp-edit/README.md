# NAME

wp-edit: edit your (already existing) WordPress page from the safety of local editor

# SYNOPSIS

wp-edit \[options\] \[uri1 uri2 ...\]

    Options:
       -h|-help               print usage
       --edit|--noedit        whether to directly spawn $EDITOR for editing (default: true)
       --cleanup|--nocleanup  whether to remove the local, temporary copies of edited files (default: true) after closing

URIs take the form of url slugs:

        wp-edit lehre/ezs/uebung will edit https://$wordpressurl/lehre/ezs/uebung

If no URIs are specified, wp-edit spawns FZF for interactive selection. Multiple entries can be selected at once using the tab-key, see `man fzf`.

When a page was selected, `wp-edit.pl` retrieves its contents. From then on, whenever the page is saved locally (detected using Inotify), the new version is pushed to the wordpress instance and published.

wp-edit aquires login-credentials from `$XDG_CONFIG_DIR/i4wp/credentials.pm`. See the `credentials-sample.pm` file in the repo for syntax.

As the script uses Inotify-wait, it probably only works on Linux, patches welcome.

# INSTALLATION

Copy the included `credentials-sample` to `$XDG_CONFIG_DIR/i4wp/credentials.pm` and adapt for your user.

Be sure to have a properly configured `$EDITOR` variable pointing to the editor of your choice.

Easiest way to install the required dependencies is probably to use carton:

        # install carton, e.g. apt install carton
        carton install
        carton exec -- ./wp-edit.pl

If you want to use your distributions libraries, you can find a list of the required perl-libs in the included `cpanfile`; as those are in widespread used, they should be commonly available. Patches with lists for different distributions are welcome.

To use the fuzzy-finder--based page selection, fzf is required as an optional dependency.
