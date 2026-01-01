# Apple Wallet Certificates

This directory contains Apple Wallet certificates for pass generation.

## Files

- `tammer.wallet.p12` - Apple Wallet Pass Type Certificate
- `AppleWWDRCAG3.cer` - Apple Worldwide Developer Relations Certificate

## Security Note

⚠️ **IMPORTANT**: These certificate files are excluded from version control for security reasons.

The certificates are stored here for local development. In production, they should be:
1. Stored in a secure location outside the web root
2. Protected with proper file permissions (600)
3. Never committed to version control

## Usage

The certificates are automatically detected by the Apple Wallet service when configured in:
**Settings > Apple Wallet**

Simply enter the filenames in the settings page:
- Certificate File Path: `tammer.wallet.p12`
- WWDR Certificate Path: `AppleWWDRCAG3.cer`

## File Permissions

Certificates should have restricted permissions:
```bash
chmod 600 includes/certs/*.p12
chmod 600 includes/certs/*.cer
```

