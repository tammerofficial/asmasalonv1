#!/bin/bash

# Script to install and export Apple Wallet certificate

echo "=========================================="
echo "📥 Install and Export Certificate"
echo "=========================================="
echo ""

CERT_DIR="includes/certs"
CER_FILE="$CERT_DIR/pass.cer"
P12_FILE="$CERT_DIR/tammer.wallet.p12"

# Check if .cer file exists
if [ ! -f "$CER_FILE" ]; then
    echo "❌ Certificate file not found: $CER_FILE"
    echo ""
    echo "Please:"
    echo "1. Download the certificate from Apple Developer Portal"
    echo "2. Save it as: $CER_FILE"
    echo "3. Run this script again"
    echo ""
    exit 1
fi

echo "✅ Certificate file found: $CER_FILE"
echo ""

# Install certificate in Keychain
echo "📥 Installing certificate in Keychain..."
security add-certificates "$CER_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Certificate installed successfully"
else
    echo "⚠️  Certificate may already be installed, or trying alternative method..."
    # Try opening it instead
    open "$CER_FILE"
    sleep 2
    echo "✅ Certificate opened in Keychain Access"
fi

echo ""
echo "=========================================="
echo "📤 Export Certificate as .p12"
echo "=========================================="
echo ""

# Find the certificate
CERT_NAME=$(security find-certificate -a -c "pass.com.tammer.loyaltycard" -p 2>/dev/null | openssl x509 -noout -subject 2>/dev/null | sed 's/.*CN=//' | head -1)

if [ -z "$CERT_NAME" ]; then
    echo "⚠️  Could not find certificate automatically"
    echo ""
    echo "Please export manually from Keychain Access:"
    echo "1. Open Keychain Access"
    echo "2. Search for 'tammer' or 'loyaltycard'"
    echo "3. Right-click > Export"
    echo "4. Choose .p12 format"
    echo "5. Enter password"
    echo "6. Save to: $P12_FILE"
    echo ""
    
    # Open Keychain Access
    open -a "Keychain Access"
    
    exit 0
fi

echo "✅ Found certificate: $CERT_NAME"
echo ""

# Try to export using security command
echo "Enter password for .p12 file (you'll need this in settings):"
read -s PASSWORD

echo ""
echo "📤 Exporting certificate..."

# Export certificate
security export -k ~/Library/Keychains/login.keychain-db \
  -t identities -f pkcs12 -o "$P12_FILE" \
  -P "$PASSWORD" \
  -c "$CERT_NAME" 2>&1

if [ $? -eq 0 ] && [ -f "$P12_FILE" ]; then
    chmod 600 "$P12_FILE"
    echo "✅ Certificate exported successfully!"
    echo "   File: $P12_FILE"
    echo "   Password: [the password you just entered]"
    echo ""
    echo "📝 Save this password - you'll need it in settings!"
    echo ""
else
    echo "⚠️  Automatic export failed. Please export manually:"
    echo ""
    echo "1. Open Keychain Access"
    echo "2. Find the certificate"
    echo "3. Right-click > Export"
    echo "4. Choose .p12 format"
    echo "5. Enter password: $PASSWORD"
    echo "6. Save to: $P12_FILE"
    echo ""
    
    open -a "Keychain Access"
fi

echo ""
echo "=========================================="
echo "✅ Next Steps:"
echo "=========================================="
echo ""
echo "1. Go to: Settings > Apple Wallet"
echo "2. Enter:"
echo "   - Certificate File Path: tammer.wallet.p12"
echo "   - Certificate Password: [the password you used]"
echo "   - Team ID: 6SGU7C9M42"
echo "   - Pass Type ID: pass.com.tammer.loyaltycard"
echo ""

