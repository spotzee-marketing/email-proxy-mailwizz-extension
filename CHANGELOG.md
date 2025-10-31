# Changelog

All notable changes to the Spotzee Email Proxy API Extension will be documented in this file.

## [0.1.0] - 2025-01-31

### Added
- Initial public release for transparency
- Email sending integration with Spotzee Email Proxy API
- Automatic bounce handling via webhooks
- Automatic complaint/abuse report handling via webhooks
- Support for email attachments with automatic MIME type detection
- Custom header support (X- prefixed headers)
- Return-path generation for campaign and transactional emails
- Webhook endpoint for processing delivery events
- Support for both single and batch webhook event formats

### Features
- **Email Delivery**: Send emails via Spotzee Email Proxy API with full HTML and plain text support
- **Bounce Processing**: Automatic detection and logging of hard bounces, soft bounces, and delivery failures
- **Complaint Processing**: Automatic handling of abuse and fraud reports
- **Subscriber Management**: Automatic blacklisting on hard bounces and complaints
- **Duplicate Prevention**: Built-in duplicate event detection to prevent reprocessing
- **Event Types Supported**:
  - `delivery.dsn-perm-fail` - Hard bounces
  - `delivery.dsn-temp-fail` - Soft bounces
  - `delivery.failed` - Internal delivery failures
  - `incoming-report.abuse-report` - Abuse complaints
  - `incoming-report.fraud-report` - Fraud reports

### Technical Details
- Minimum MailWizz version: 2.0.0
- PHP strict types enabled
- Uses GuzzleHTTP for API requests
- Basic authentication with username/password
- Webhook URL format: `https://yourdomain.com/dswh/spotzee-api/{server_id}`
