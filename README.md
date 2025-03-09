# ICT2214-WEBSEC: HONEYSSRF by P2B Group 1.

The **Dashboard** folder contains files relevant to the administrator dashboard of the honeypot. It can only be accessed by the root user and serves as an analysis page to track SSRF requests made to the main site. 

The **Honeypot** folder contains files relevant to the actual site that potential attackers have access to.

To access the root account credentials, please view the **login.php** file.

To access the website, go to our domain at **http://grouponewebsec.eastasia.cloudapp.azure.com/** where you will see a fake e-commerce website.
There are two areas intentionally made vulnerable to SSRF:
1. Item search bar
2. 'Contact Us' page

After a request is made, it will automatically be recorded in the dashboard at the endpoint **/dashboard/dashboard.php** (can only be accessed after entering root credentials).
We have also set up a **Telegram bot (@SSRFgrponeBot)** that sends an alert whenever a potential SSRF request is made, and also display the category of attack (e.g. SQL-based, DNS-based).
