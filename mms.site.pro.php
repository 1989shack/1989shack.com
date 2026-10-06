Site.pro

Plugins - Hosting Panel - cPanel
Plugin installation → Hosting Panel
cPanel
Installation / Update
Open cPanel server console SSH and login as root (it is required for the user to be exactly "root", other users - even with root privileges - may not have all required permissions for plugin installation).
Execute installation command:
cd ~ && curl -s -o spinstall https://sync.site.pro/My-Licenses/installer/eyJpZCI6IjEwNzc2MzIiLCJ1c2VySWQiOiI0MDE4MTIiLCJzYWx0IjoiMTU5Y2ExMTkifQ2/WHM_cPanel/siteprobuilder > /dev/null && sh spinstall && wget -O sitebuilder.tar.gz https://sync.site.pro/My-Licenses/installer/eyJpZCI6IjEwNzc2MzIiLCJ1c2VySWQiOiI0MDE4MTIiLCJzYWx0IjoiMTU5Y2ExMTkifQ2/WHM_cPanel/plugin && /usr/local/cpanel/scripts/install_plugin sitebuilder.tar.gz --theme=jupiter && rm -f sitebuilder.tar.gz