show_config() {
    ynh_return "YNH_CONFIG_MAIN_IS_PUBLIC_IS_PUBLIC=1"
}

apply_config() {
    value=$YNH_CONFIG_MAIN_IS_PUBLIC_IS_PUBLIC
    # do some stuff with value
}

case $1 in
    show) show_config;;
    apply) apply_config;;
esac
Or if you want a full useless simple 