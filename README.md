# Spotzee HTTP/SMTP Email Proxy - MailWizz Extension

Smart email proxy for **cold email marketing** and outreach campaigns. Routes through multiple SMTP providers (Gmail, Outlook, Yahoo) with smart rate limiting and automatic failover. **100% email deliverability** through reliable queueing. Pay only $0.00007 per email ($0.07 per 1,000 emails) with no monthly fees.

## Quick Start Guide

### 1. Create Your Spotzee Account (Free)
Sign up for a [Spotzee](https://app.spotzee.com/sign-up) account

### 2. Add Funds
- Go to [Organization Billing](https://app.spotzee.com/organization/billing)
- Top up your account and/or configure auto top-up

### 3. Configure Email Providers
- Open your project (or create a new one)
- Go to **Settings → Email Proxy** tab
- Click **Import Providers** to add your SMTP accounts
- Configure each provider:
  - Quotas (per second, minute, hour, day)
  - Tracking domain
  - From email, From name
  - Reply-to email, Reply-to name
  - Traffic percentage (100 = highest priority)

### 4. Generate API Credentials for MailWizz
- Click the **Setup** button
- Generate a new API key
- The Setup modal displays your **Username** and **API Key**
- Copy these credentials - you'll use them in MailWizz:
  - Username → MailWizz username field
  - API Key → MailWizz password field

### 5. Configure Webhooks
- Go to **Event Webhooks** tab
- Click **Add New Webhook**
- Select **"Bounces"** events
- Enter the webhook URL from your MailWizz delivery server settings (shown in the info modal)

### 6. Add to MailWizz
- In MailWizz, go to **Delivery Servers → Create New**
- Select **Spotzee Web API**
- Enter your username and password from step 4
- Save

### 7. Start Sending
Send campaigns through MailWizz as normal. View delivery logs in Spotzee by clicking **Email Proxy → View Logs**.

---

## MailWizz Integration

This extension seamlessly integrates MailWizz with Spotzee HTTP/SMTP Email Proxy API:

- **Simple Setup** - Add your Spotzee credentials as a delivery server in MailWizz
- **Automatic Bounce Handling** - Hard/soft bounces are logged and subscribers are blacklisted automatically
- **Complaint Processing** - Abuse reports unsubscribe recipients and protect your sender reputation
- **Real-Time Webhooks** - Delivery events update campaign statistics instantly
- **No Manual Configuration** - Webhook URLs are generated automatically

Send campaigns through MailWizz as usual - all bounce and complaint handling happens automatically.

## How It Works

```mermaid
graph LR
    A[Your Application] -->|SMTP or HTTP API| B[Spotzee Proxy]
    B -->|Smart Routing| C[Provider/Email Account #1]
    B -->|Smart Routing| D[Provider/Email Account #2]
    B -->|Smart Routing| E[Provider/Email Account #3]
    B -->|Auto-Failover| F[Provider/Email Account #N]
    C -->|Delivered| G[Recipients]
    D -->|Delivered| G
    E -->|Delivered| G
    F -->|Delivered| G
```

## Supported Providers

### SMTP Email Accounts (Available Now)
Connect unlimited SMTP accounts from any provider:
- **Gmail** - Personal and Google Workspace accounts
- **Outlook/Office 365** - Microsoft email accounts
- **Yahoo Mail** - Yahoo SMTP accounts
- **Custom SMTP** - Any SMTP provider (Mailgun, SparkPost, etc.)

### API Email Providers (Coming Soon)
Direct API integration for enhanced deliverability:
- **SendGrid**
- **Mailgun**
- **Amazon SES**
- **Resend**
- **Postmark**
- **SparkPost**
- **Mandrill**
- **Mailjet**
- **Brevo** (Sendinblue)
- **SMTP.com**
- **SocketLabs**
- **Elastic Email**
- **Pepipost**
- **MailerSend**
- **MailerLite**
- **ActiveCampaign**
- **Mailchimp**
- **Klaviyo**
- **Campaign Monitor**
- **Moosend**
- **And more...**

---

**One Endpoint, Multiple Providers**
- Send via SMTP server (port 587/2525) or HTTP API endpoint
- Your emails route automatically across all configured providers
- Failed providers bypassed instantly with zero downtime

**Human-Like Sending**
- Configure rate limit ranges (e.g., "2-3 per hour", "20-25 per day")
- System randomizes sending rate every 5 minutes within your range
- Appears natural to email providers, improves deliverability

## Smart Routing & Groups

**Group Your Providers by Purpose**

Organize email accounts into custom groups (Marketing, Transactional, Customer Support, etc.) and route emails intelligently based on email headers.

**How It Works:**
- Create groups like "Marketing", "Transactional", "Notifications"
- Assign provider accounts to each group
- Add custom email header `X-Delivery-Route` with the group ID
- Emails automatically route through the designated group's providers

**Use Cases:**
- **Marketing Campaigns** - Route through dedicated marketing provider accounts
- **Transactional Emails** - Send receipts and notifications through high-priority transactional accounts
- **Customer Support** - Separate reply/support emails from bulk campaigns
- **Client Segregation** - Agencies can isolate client campaigns for better reputation management

This ensures marketing campaigns never impact transactional email deliverability, and you maintain separate sending reputations for different email types.

## Key Benefits

### 💰 Pay Only for What You Send
- **$0.00007 per email** ($0.07 per 1,000 emails)
- No monthly fees, no minimums, no hidden costs

### 🚀 100% Deliverability
- Reliable message queueing ensures every email is delivered
- Automatic failover when providers fail
- Multi-provider redundancy with smart routing

### ⚡ Built for Scale
- **10+ million emails per day** capacity
- **1,000+ emails per second** at peak load
- Automatic scaling as your volume grows

### 🔧 Dual Integration Options
- **SMTP Server** - Standard port 587 and Non-Standard port 2525
- **HTTP REST API** - For programmatic sending
- Same authentication for both methods

### 🤖 Human-Like Sending Patterns
- Configure rate limit ranges (e.g., "5-10 per hour")
- System randomizes within range every 5 minutes
- Mimics natural sending behavior for better inbox placement

### 🎯 Flexible Routing
- Percentage-based distribution across providers
- Group-based routing for specific campaigns
- Per-second, per-minute, per-hour, and per-day quotas
- Custom domain policies

### 🛡️ Enterprise-Grade Security
- Encrypted provider credentials
- TLS/SSL email transmission
- DDoS protection with rate limiting
- Complete audit logging

### 📊 Complete Visibility
- Real-time delivery tracking
- Provider performance metrics
- Transparent billing to the cent
- Detailed delivery logs

## Who It's For

**Cold Email Marketers** - Scale outreach campaigns across Gmail, Outlook, Yahoo accounts with smart rate limiting that mimics human sending patterns. Prevent provider bans and maximize inbox placement.

**Cold Email Agencies** - Manage multiple client campaigns with per-account quotas, automatic failover, and transparent per-email billing. No monthly fees mean you only pay for active campaigns.

**Lead Generation Teams** - B2B outbound sales automation with multi-SMTP rotation, bounce handling, and detailed delivery tracking for every prospect interaction.

**SaaS Applications** - Transactional emails (password resets, notifications) delivered reliably.

**Marketing Teams** - Campaign emails distributed across providers automatically.

**E-commerce Platforms** - Order confirmations and shipping notifications with 100% deliverability.

**Developers** - Drop-in SMTP replacement or REST API integration.

## Getting Started

### Quick Setup

1. **Add Providers** - Configure your SMTP accounts with rate limits
2. **Get Credentials** - Receive your API key
3. **Start Sending** - Use SMTP (port 587/2525) or HTTP API

### Pricing Example

**100,000 emails per month:**
- Cost: 100,000 × $0.00007 = **$7.00/month** ($0.07 per 1,000 emails)
- No base fees, no per-provider charges
- Pay only for successful deliveries

**1 million emails per month:**
- Cost: 1,000,000 × $0.00007 = **$70.00/month** ($0.07 per 1,000 emails)

## Why Choose Spotzee HTTP/SMTP Email Proxy?

✅ **100% Email Deliverability** - Reliable queueing ensures delivery  
✅ **SMTP or HTTP API** - Use whichever fits your workflow  
✅ **Human-Like Sending** - Randomized rates look natural  
✅ **Transparent Pricing** - $0.07 per 1,000 emails, nothing else  
✅ **Zero Downtime** - Automatic failover keeps emails flowing  
✅ **Battle-Tested** - Proven at millions of emails daily  

---

**Ready to improve your email deliverability?**

[Get Started](https://spotzee.com) | [View Documentation](https://help.spotzee.com) | [Contact Support](mailto:contact@spotzee.com)