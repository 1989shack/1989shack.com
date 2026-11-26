#!/usr/bin/env bash
# WHM/cPanel full setup script
# Run as root on a fresh AlmaLinux/CloudLinux/RHEL server with a valid cPanel license.

set -euo pipefail

###############
# VARIABLES   #
###############
# Server basics
HOSTNAME_FQDN="ns1.1989shack.com"
CONTACT_EMAIL="martinmsheriff@1989shack.com"

# Networking
MAIN_IP="203.0.113.10"              # Server's primary public IP
RESOLVER1="1.1.1.1"                 # DNS resolvers for the server
RESOLVER2="8.8.8.8"
RESOLVER3="9.9.9.9"

# Nameservers (hostnames and their IPs; be sure to create matching A records at your registrar/DNS)
NS1_HOST="ns1.1989shack.com"
NS1_IP="203.0.113.11"
NS2_HOST="ns2.1989shack.com"
NS2_IP="203.0.113.12"

# First cPanel account
ACCT_USERNAME="shackcom"
ACCT_DOMAIN="1989shack.com"
ACCT_PASS="StrongPassw0rd!"
ACCT_EMAIL="martinmsheriff@1989shack.com"

# Shared hosting package
PKG_NAME="Starter"
PKG_QUOTA_MB="10240"     # 10 GB
PKG_BWLIMIT_MB="51200"   # 50 GB
PKG_MAXFTP="10"
PKG_MAXSQL="20"
PKG_MAXPOP="50"
PKG_MAXSUB="50"
PKG_MAXPARK="5"
PKG_MAXADDON="10"
PKG_MAXDOM="20"
PKG_FEATURELIST="default"

#####################
# PRE-INSTALL CHECK #
#####################
if [[ $EUID -ne 0 ]]; then
  echo "Please run as root."
  exit 1
fi

if command -v whmapi1 >/dev/null 2>&1; then
  echo "cPanel/WHM appears to be installed already. Aborting to avoid conflicts."
  exit 1
fi

echo "Setting hostname to ${HOSTNAME_FQDN}..."
hostnamectl set-hostname "${HOSTNAME_FQDN}"

echo "Updating system..."
yum -y update || dnf -y upgrade

# Disable NetworkManager (recommended for cPanel networking consistency)
if systemctl is-active --quiet NetworkManager; then
  echo "Disabling NetworkManager..."
  systemctl stop NetworkManager || true
  systemctl disable NetworkManager || true
fi

echo "Installing prerequisites..."
yum -y install perl curl wget screen tar hostname || dnf -y install perl curl wget screen tar hostname

#####################
# INSTALL CPANEL    #
#####################
echo "Downloading and running cPanel installer..."
cd /home
curl -o latest -L https://securedownloads.cpanel.net/latest
sh latest

echo "Waiting for cPanel services to come up..."
sleep 30
systemctl start cpanel || true

#############################
# BASIC POST-INSTALL CONFIG #
#############################
# Note: whmapi1 is available after install; many settings can be adjusted via these API calls.

echo "Configuring resolvers..."
whmapi1 setresolvers resolver1="${RESOLVER1}" resolver2="${RESOLVER2}" resolver3="${RESOLVER3}"

echo "Configuring contact email..."
whmapi1 set_contact_information contact_email="${CONTACT_EMAIL}"

echo "Setting shared IP (usually main IP for single-IP servers)..."
whmapi1 set_sharedip ip="${MAIN_IP}"

echo "Registering nameservers..."
whmapi1 setnameservers nameserver="${NS1_HOST}" nameserver2="${NS2_HOST}"

echo "Assigning nameserver IPs (glue records must exist at registrar/DNS)..."
whmapi1 set_nameserver_ip nameserver="${NS1_HOST}" ip="${NS1_IP}"
whmapi1 set_nameserver_ip nameserver="${NS2_HOST}" ip="${NS2_IP}"

echo "Tweak basic settings..."
# Examples: enable SMTP tweaks, block nobody mailing, enable AutoSSL queue
whmapi1 set_tweaksetting key=smtpmailgidonly value=1
whmapi1 set_tweaksetting key=blocknobodymail value=1
whmapi1 set_tweaksetting key=track_outgoing_smtp value=1

#########################
# DNS ZONE FOR DOMAIN   #
#########################
echo "Creating DNS zone for ${ACCT_DOMAIN} (if not already exists)..."
whmapi1 adddns domain="${ACCT_DOMAIN}" ip="${MAIN_IP}" trueowner=root

###############################
# CREATE PACKAGE AND ACCOUNT  #
###############################
echo "Creating hosting package ${PKG_NAME}..."
whmapi1 addpkg name="${PKG_NAME}" quota="${PKG_QUOTA_MB}" bwlimit="${PKG_BWLIMIT_MB}" maxftp="${PKG_MAXFTP}" \
  maxsql="${PKG_MAXSQL}" maxpop="${PKG_MAXPOP}" maxsub="${PKG_MAXSUB}" maxpark="${PKG_MAXPARK}" \
  maxaddon="${PKG_MAXADDON}" maxdomains="${PKG_MAXDOM}" featurelist="${PKG_FEATURELIST}"

echo "Creating cPanel account for ${ACCT_DOMAIN}..."
whmapi1 createacct username="${ACCT_USERNAME}" domain="${ACCT_DOMAIN}" password="${ACCT_PASS}" \
  contactemail="${ACCT_EMAIL}" plan="${PKG_NAME}" ip="${MAIN_IP}" cpanel_theme="jupiter"

#####################
# AUTOSSL SETTINGS  #
#####################
# Enable AutoSSL; if Let's Encrypt provider is available, set it as default.
echo "Enabling AutoSSL..."
whmapi1 start_autossl_check
# Attempt to set provider to LetsEncrypt (requires provider installed/enabled)
whmapi1 set_autossl_provider provider=LetsEncrypt || true

#####################
# FIREWALL (CSF)    #
#####################
# Optional: install and enable CSF firewall for basic protection.
echo "Installing CSF firewall (optional)..."
cd /usr/src
curl -O https://download.configserver.com/csf.tgz
tar -xzf csf.tgz
cd csf
sh install.sh
systemctl enable csf || true
systemctl start csf || true

# Open essential ports in CSF
csf -a "${MAIN_IP}" || true
csf -r || true

#####################
# FINAL SUMMARY     #
#####################
echo "===================================================="
echo "WHM/cPanel install and initial setup complete."
echo "WHM URL: https://${MAIN_IP}:2087"
echo "cPanel URL: https://${MAIN_IP}:2083"
echo "Hostname: ${HOSTNAME_FQDN}"
echo "Nameservers: ${NS1_HOST} (${NS1_IP}), ${NS2_HOST} (${NS2_IP})"
echo "Account: ${ACCT_USERNAME} / ${ACCT_DOMAIN}"
echo "AutoSSL enabled; certificates may take several minutes."
echo "===================================================="
