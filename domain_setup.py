import requests
import os
from cloudflare import CloudFlare

# 1. Cloudflare Setup
cf = CloudFlare(email='your@email.com', token='your-api-key')
zone_id = cf.zones.post(data={'name':'1989shack.com'})['id']

# 2. Add DNS Records
cf.zones.dns_records.post(zone_id, data={
    'type':'A',
    'name':'@',
    'content':'YOUR_SERVER_IP',
    'proxied':True
})

# 3. Google Verification (requires manual step first)
verification_code = 'google-site-verification=XXXXXX'
cf.zones.dns_records.post(zone_id, data={
    'type':'TXT',
    'name':'@',
    'content':verification_code
})

print("Done! Allow 24-48 hours for propagation.")
