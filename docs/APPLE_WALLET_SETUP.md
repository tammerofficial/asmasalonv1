# Apple Wallet Integration Setup Guide

## Overview

This plugin now includes full Apple Wallet integration with:
- PKCS7 signed `.pkpass` file generation
- Automatic push notifications via APNs
- Full Web Service compliance
- Support for multiple pass types (Loyalty, Membership, Programs, Commissions)

## Prerequisites

1. **Apple Developer Account** with active membership
2. **Pass Type ID** registered in Apple Developer Portal
3. **Apple Wallet Certificate** (.p12 file) downloaded from Apple Developer
4. **WWDR Certificate** (Apple Worldwide Developer Relations Certificate)

## Setup Steps

### 1. Get Apple Developer Credentials

1. Log in to [Apple Developer Portal](https://developer.apple.com)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Create a **Pass Type ID** (e.g., `pass.com.asmaasalon.loyalty`)
4. Download the **Pass Type Certificate** (.cer file)
5. Convert the certificate to `.p12` format:
   ```bash
   # Import certificate to Keychain
   open certificate.cer
   
   # Export as .p12 from Keychain Access
   # File > Export Items > Format: Personal Information Exchange (.p12)
   ```
6. Download **WWDR Certificate** from [Apple's website](https://www.apple.com/certificateauthority/)

### 2. Upload Certificates

1. Upload your `.p12` certificate file to:
   ```
   /wp-content/uploads/asmaa-salon/certs/
   ```
2. Upload the WWDR certificate (`.cer` file) to the same directory

### 3. Configure Settings

1. Navigate to **Settings > Apple Wallet** in the admin panel
2. Fill in the following fields:
   - **Apple Team ID**: Your Apple Developer Team ID (e.g., `ABC123DEF4`)
   - **Pass Type ID**: Your registered Pass Type ID (e.g., `pass.com.asmaasalon.loyalty`)
   - **Certificate File Path**: Just the filename (e.g., `certificate.p12`)
   - **Certificate Password**: The password you set when exporting the .p12 file
   - **WWDR Certificate Path**: Just the filename (e.g., `AppleWWDRCAG3.cer`)
   - **Sandbox Mode**: Enable for testing, disable for production
3. Click **Save Settings**

### 4. Verify Setup

1. Check the status indicators on the settings page:
   - ✅ **Certificate Ready**: Certificate file found and valid
   - ✅ **WWDR Ready**: WWDR certificate found (optional but recommended)
2. Try creating a test pass from:
   - **Loyalty** section
   - **Memberships** section
   - **Programs** section
   - **Commissions** section

## Pass Types Supported

### 1. Loyalty Pass
- Shows current loyalty points balance
- Displays total visits
- Includes QR code for scanning at POS

### 2. Membership Pass
- Shows membership plan name
- Displays discount percentage
- Shows expiration date

### 3. Programs Pass
- Combined pass showing loyalty and commissions status
- Shows active/inactive status for each program

### 4. Commissions Pass (Staff)
- Shows pending commissions
- Displays approved commissions
- Shows paid commissions

## Automatic Updates

The system automatically:
- **Updates passes** when loyalty points change
- **Updates passes** when membership status changes
- **Sends push notifications** to devices when passes are updated
- **Regenerates signed .pkpass files** on every update

## Web Service Endpoints

The plugin implements Apple's Web Service protocol:

- `GET /wp-json/asmaa-salon/v1/apple-wallet/pass/{serial_number}` - Download pass
- `GET /wp-json/asmaa-salon/v1/apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}` - Get device registrations
- `POST /wp-json/asmaa-salon/v1/apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}` - Register device
- `DELETE /wp-json/asmaa-salon/v1/apple-wallet/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}` - Unregister device
- `GET /wp-json/asmaa-salon/v1/apple-wallet/v1/log` - Log errors from devices

## Troubleshooting

### Pass won't download
- Check certificate file exists in `/wp-content/uploads/asmaa-salon/certs/`
- Verify certificate password is correct
- Check PHP OpenSSL extension is enabled
- Review error logs: `wp-content/debug.log`

### Push notifications not working
- Verify certificate is valid and not expired
- Check sandbox mode matches your testing environment
- Ensure device is registered (happens automatically when pass is added to Wallet)
- Review APNs connection in error logs

### Pass updates not appearing
- Check if push notification was sent (review logs)
- Verify device has internet connection
- Try removing and re-adding the pass to Wallet

## File Structure

```
wp-content/uploads/asmaa-salon/
├── certs/
│   ├── certificate.p12          # Your Apple Wallet certificate
│   └── AppleWWDRCAG3.cer        # WWDR certificate
└── passes/
    └── {serial_number}.pkpass    # Generated pass files
```

## Security Notes

- Certificate files should have restricted permissions (600 or 640)
- Never commit certificate files to version control
- Use environment variables for sensitive data in production
- Regularly rotate certificates before expiration

## Support

For issues or questions:
1. Check error logs in WordPress debug mode
2. Review Apple Wallet documentation
3. Verify all prerequisites are met
4. Test with sandbox mode first before production

