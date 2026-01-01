#!/bin/bash

# Script to help upload CSR to Apple Developer Portal

echo "=========================================="
echo "📤 Upload CSR to Apple Developer Portal"
echo "=========================================="
echo ""

CSR_FILE="includes/certs/tammer.wallet.csr"

if [ ! -f "$CSR_FILE" ]; then
    echo "❌ CSR file not found: $CSR_FILE"
    exit 1
fi

echo "✅ CSR file found: $CSR_FILE"
echo ""
echo "📋 Next Steps:"
echo ""
echo "1. Opening Apple Developer Portal..."
echo ""

# Open Apple Developer Portal
open "https://developer.apple.com/account/resources/certificates/list"

sleep 2

echo "2. In the browser:"
echo "   - Click the '+' button (top left)"
echo "   - Select 'Pass Type ID Certificate'"
echo "   - Choose Pass Type ID: pass.com.tammer.loyaltycard"
echo "   - Click 'Continue'"
echo ""
echo "3. Upload the CSR file:"
echo "   - Click 'Choose File'"
echo "   - Select: $CSR_FILE"
echo "   - Click 'Continue'"
echo ""
echo "4. Download the certificate:"
echo "   - After approval, download the .cer file"
echo "   - Save it to: includes/certs/"
echo ""

echo "=========================================="
echo "📄 CSR Content (for manual copy if needed):"
echo "=========================================="
echo ""
cat "$CSR_FILE"
echo ""
echo "=========================================="
echo ""

# Also copy CSR to clipboard if possible
if command -v pbcopy &> /dev/null; then
    cat "$CSR_FILE" | pbcopy
    echo "✅ CSR content copied to clipboard!"
    echo "   You can paste it directly in Apple Developer Portal"
    echo ""
fi

echo "⏳ Waiting for you to complete the upload..."
echo "   After downloading the certificate, run:"
echo "   ./scripts/install_and_export_cert.sh"
echo ""

