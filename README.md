Raw RDAP Registry Data

{
  "objectClassName": "domain",
  "handle": "2936241139_DOMAIN_COM-VRSN",
  "ldhName": "1989SHACK.COM",
  "links": [
    {
      "value": "https://rdap.verisign.com/com/v1/domain/1989SHACK.COM",
      "rel": "self",
      "href": "https://rdap.verisign.com/com/v1/domain/1989SHACK.COM",
      "type": "application/rdap+json"
    },
    {
      "value": "https://opensrs.rdap.tucows.com/domain/1989SHACK.COM",
      "rel": "related",
      "href": "https://opensrs.rdap.tucows.com/domain/1989SHACK.COM",
      "type": "application/rdap+json"
    }
  ],
  "status": [
    "active"
  ],
  "entities": [
    {
      "objectClassName": "entity",
      "handle": "69",
      "roles": [
        "registrar"
      ],
      "links": [
        {
          "href": "http://www.tucows.com",
          "type": "text/html",
          "value": "https://opensrs.rdap.tucows.com/",
          "rel": "about"
        }
      ],
      "publicIds": [
        {
          "type": "IANA Registrar ID",
          "identifier": "69"
        }
      ],
      "vcardArray": [
        "vcard",
        [
          [
            "version",
            {},
            "text",
            "4.0"
          ],
          [
            "fn",
            {},
            "text",
            "Tucows Domains Inc."
          ]
        ]
      ],
      "entities": [
        {
          "objectClassName": "entity",
          "roles": [
            "abuse"
          ],
          "vcardArray": [
            "vcard",
            [
              [
                "version",
                {},
                "text",
                "4.0"
              ],
              [
                "fn",
                {},
                "text",
                ""
              ],
              [
                "tel",
                {
                  "type": "voice"
                },
                "uri",
                "tel:+1.4165350123"
              ],
              [
                "email",
                {},
                "text",
                "domainabuse@tucows.com"
              ]
            ]
          ]
        }
      ]
    }
  ],
  "events": [
    {
      "eventAction": "registration",
      "eventDate": "2024-11-22T18:01:08Z"
    },
    {
      "eventAction": "expiration",
      "eventDate": "2026-11-22T18:01:08Z"
    },
    {
      "eventAction": "last changed",
      "eventDate": "2025-11-26T06:34:24Z"
    },
    {
      "eventAction": "last update of RDAP database",
      "eventDate": "2025-12-21T06:09:46Z"
    }
  ],
  "secureDNS": {
    "delegationSigned": false
  },
  "nameservers": [
    {
      "objectClassName": "nameserver",
      "ldhName": "NS1.RENEWYOURNAME.NET"
    },
    {
      "objectClassName": "nameserver",
      "ldhName": "NS2.RENEWYOURNAME.NET"
    }
  ],
  "rdapConformance": [
    "rdap_level_0",
    "icann_rdap_technical_implementation_guide_1",
    "icann_rdap_response_profile_1"
  ],
  "notices": [
    {
      "title": "Terms of Service",
      "description": [
        "Service subject to Terms of Use."
      ],
      "links": [
        {
          "href": "https://www.verisign.com/domain-names/registration-data-access-protocol/terms-service/index.xhtml",
          "type": "text/html",
          "value": "https://rdap.verisign.com/com/v1/domain/1989shack.com?jcard=1",
          "rel": "terms-of-service"
        }
      ]
    },
    {
      "title": "Status Codes",
      "description": [
        "For more information on domain status codes, please visit https://icann.org/epp"
      ],
      "links": [
        {
          "href": "https://icann.org/epp",
          "type": "text/html"
        }
      ]
    },
    {
      "title": "RDDS Inaccuracy Complaint Form",
      "description": [
        "URL of the ICANN RDDS Inaccuracy Complaint Form: https://icann.org/wicf"
      ],
      "links": [
        {
          "href": "https://icann.org/wicf",
          "type": "text/html",
          "value": "https://rdap.verisign.com/com/v1/domain/1989shack.com?jcard=1",
          "rel": "help"
        }
      ]
    }
  ]
}
