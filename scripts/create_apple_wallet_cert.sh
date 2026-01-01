#!/bin/bash

# Script to help create Apple Wallet certificate
# This script guides you through the process

echo "=========================================="
echo "Apple Wallet Certificate Setup Guide"
echo "=========================================="
echo ""

echo "⚠️  Important: You cannot create a Pass Type ID Certificate from Terminal."
echo "   You must create it from Apple Developer Portal first."
echo ""

echo "📋 Steps to create certificate:"
echo ""
echo "1. Go to: https://developer.apple.com/account"
echo "2. Navigate to: Certificates, Identifiers & Profiles"
echo "3. Click '+' to create new certificate"
echo "4. Select: 'Pass Type ID Certificate'"
echo "5. Choose your Pass Type ID: pass.com.tammer.loyaltycard"
echo "6. Download the certificate (.cer file)"
echo ""

echo "📥 After downloading:"
echo ""
echo "1. Double-click the .cer file (opens in Keychain Access)"
echo "2. Find the certificate in 'My Certificates'"
echo "3. Right-click > Export [certificate name]"
echo "4. Choose format: 'Personal Information Exchange (.p12)'"
echo "5. Enter a password (THIS IS THE PASSWORD YOU NEED!)"
echo "6. Save the .p12 file"
echo ""

echo "🔍 Checking for existing certificates in Keychain..."
echo ""

# Check for existing certificates
CERT_COUNT=$(security find-identity -v -p codesigning 2>/dev/null | grep -c "Apple")

if [ "$CERT_COUNT" -gt 0 ]; then
    echo "✅ Found $CERT_COUNT Apple certificate(s) in Keychain:"
    security find-identity -v -p codesigning 2>/dev/null | grep "Apple"
    echo ""
    echo "💡 Tip: Look for 'Pass Type ID' certificates in Keychain Access"
else
    echo "❌ No Apple certificates found in Keychain"
    echo ""
    echo "You need to:"
    echo "1. Create certificate on Apple Developer Portal"
    echo "2. Download and install it"
    echo "3. Export as .p12 with password"
fi

echo ""
echo "=========================================="
echo "Next Steps:"
echo "=========================================="
echo ""
echo "1. Open Keychain Access (already opened)"
echo "2. Search for 'tammer' or 'wallet' or 'pass'"
echo "3. If found, export it as .p12"
echo "4. Copy the .p12 file to: includes/certs/"
echo "5. Update settings with the password you used"
echo ""

