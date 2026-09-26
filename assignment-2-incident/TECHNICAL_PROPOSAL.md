Assignment 2 - Incident Response Proposal

1. Log Analysis

The server logs show a mix of real customers, important automated services, and hackers running automated bots. We cannot blindly block IP addresses because we might accidentally block real customers. 

Here is how I classify the traffic:

   Hackers & Bots (Hostile Traffic):
       `203.0.113.44`: Looking for WordPress vulnerabilities (`/wp-admin/`, `/xmlrpc.php`).
       `198.51.100.7`: Trying to steal sensitive files like passwords (`/.env`) and database panels (`/phpmyadmin/`).
       `45.146.164.2`: Generic probing for admin panels (`/admin/login.php`) and missing files (`/random-string`).
   Shared IPs (Dangerous to block):
       `110.226.180.5`: This IP successfully requests a real flight (`/flights/mumbai-delhi`) but also requests a malicious file (`/.env`). This is likely a shared public IP (like a mobile phone network) where a real customer and a hacker share the same address. If we ban this IP, we lose a real customer.
   Important Services (Do Not Block):
       `216.144.250.9`: Uptime monitoring (`/health`). If we block this, our alarms will go off.
       `104.18.22.33`: Razorpay payment webhook (`/webhooks/razorpay`). If we block this, we stop receiving payment confirmations.
       `66.249.66.1`: Googlebot indexing our site (`/sitemap.xml`). If we block this, our SEO drops.

2. Immediate Fix (First 2 Hours)

Right now, every time a hacker requests `/.env`, the request goes all the way into the heavy PHP/Yii2 framework just to say "Not Found (404)". This uses up a massive amount of CPU and memory.

The plan:
1.  Check metrics: Quickly look at current CPU and memory usage so we can verify if our fix works.
2.  Stop bad requests at the front door: We will configure Nginx (the web server) to instantly reject requests for obviously bad paths like `/.env` and `/wp-admin/` with a 404 error before they ever reach the Yii2 application.
3.  Monitor: Deploy this simple rule and watch the CPU usage drop. Ensure payments and health checks still work perfectly.

3. The Durable Fix (This Week)

To protect the server permanently without spending a lot of money, we will build a layered defense:

    Layer 1: Nginx Rules (Free):
        Keep the immediate fix running. Nginx will always block obviously bad paths instantly and save our server's CPU.

    Layer 2: Fail2Ban (Free):
        We will install Fail2Ban on the server. If an IP address requests explicitly malicious files (like `/.env`) more than 5 times in a minute, Fail2Ban will block that IP at the firewall for 10 minutes to slow them down. We will not trigger this on normal 404 errors, to avoid blocking real customers who just made a typo in a URL.

    Layer 3: AWS WAF (Paid - Last Resort):
        If the bot attacks are so huge that the server is still struggling, we will put an AWS Web Application Firewall (WAF) in front of the server. AWS WAF automatically drops malicious bot traffic. I am listing this as a last resort because it adds extra AWS monthly costs.

4. The Weakest Point of this Plan

The most dangerous part of my plan is writing the Nginx rules.

If I write a rule that says "block any request containing the word 'env'", I might accidentally block a real customer trying to load a legitimate page like `/flights/my-env-trip`. They would get a broken page before even reaching our app.

To prevent this, I will make sure the Nginx rules are extremely specific (e.g., blocking exactly `/.env` and nothing else). After deploying the fix, I will actively watch the error logs to make sure no legitimate customer paths are being blocked by mistake.
