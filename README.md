

---

## ⚙️ Requirements

- **OS:** AlmaLinux 8/9, CloudLinux, or RHEL (fresh install, no control panels).
- **License:** Valid cPanel/WHM license tied to your server’s public IP.
- **Networking:** Static IP, open ports (22, 80, 443, 2087, 2083).
- **Hostname:** Fully qualified domain name (e.g., `server.yourdomain.com`).
- **Nameservers:** Two nameservers with glue records (e.g., `ns1.yourdomain.com`, `ns2.yourdomain.com`).

---

## 🔒 Security

Sensitive values (IP, passwords, domains) are **not hardcoded** in the script.  
Instead, they are injected at runtime using **GitHub Secrets**.

### Example secrets:
- `HOSTNAME_FQDN`
- `CONTACT_EMAIL`
- `MAIN_IP`
- `NS1_HOST`
- `NS1_IP`
- `NS2_HOST`
- `NS2_IP`
- `ACCT_USERNAME`
- `ACCT_DOMAIN`
- `ACCT_PASS`
- `ACCT_EMAIL`
- `PKG_NAME`

Add these under:  
**Repo → Settings → Secrets and variables → Actions → New repository secret**

---

## 🚀 Usage

### 1. Clone the repo
```bash
git clone https://github.com/yourusername/cpanel-automation.git
cd cpanel-automation
