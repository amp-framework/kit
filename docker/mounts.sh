# Sourced by the scripts that run the checkout in a container (docker/tooling, docker/test-integration, docker/mutation):
# `project_dir=…; . "$project_dir/docker/mounts.sh"` sets $mounts to whole `-v` options for what Composer has not
# installed from a release, which are empty when there is nothing of the kind. Word splitting is wanted: use $mounts
# unquoted. A path with a blank in it does not work.
#
# - AMPF_KIT_AMPF_CHECKOUT names a checkout of ampf (a local one, with changes that are not released): it is mounted read-only over
#   vendor/amp-framework/ampf; vendor/ and composer.lock stay as they are. Install first without it: a mount over a
#   directory that does not exist yet makes the directory, as root.
mounts=

if [ -n "${AMPF_KIT_AMPF_CHECKOUT:-}" ]; then
    if [ ! -f "$AMPF_KIT_AMPF_CHECKOUT/src/Bootstrap/ApplicationContext.php" ]; then
        echo "AMPF_KIT_AMPF_CHECKOUT is no ampf checkout: $AMPF_KIT_AMPF_CHECKOUT" >&2
        exit 1
    fi
    mounts="$mounts -v $AMPF_KIT_AMPF_CHECKOUT:/app/vendor/amp-framework/ampf:ro"
fi
