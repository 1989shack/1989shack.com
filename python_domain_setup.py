name: Domain Setup
on:
    workflow_dispatch:
      inputs:
        google_token:
           description:
             'Google Verification Token'
           required: true
       jobs:
        setup:
        runs-on: ubuntu-latest
        steps:
     -uses: actions/checkout@v2
    -run: |
      python domain_setup.py
   {{inputs.google_token}}
