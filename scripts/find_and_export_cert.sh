#!/bin/bash

# Script to find and export Apple Wallet Pass Type ID Certificate

echo "=========================================="
echo "🔍 Searching for Pass Type ID Certificates"
echo "=========================================="
echo ""

# Search for certificates
echo "Searching Keychain for certificates..."
echo ""

# List all certificates
security find-certificate -a 2>/dev/null | while IFS= read -r line; do
    if [[ $line == *"keychain:"* ]]; then
        cert_path=$(echo "$line" | sed 's/.*keychain: //')
        
        # Try to get certificate info
        cert_info=$(security find-certificate -c "$cert_path" -p 2>/dev/null | openssl x509 -noout -subject -issuer 2>/dev/null 2>&1)
        
        if echo "$cert_info" | grep -qi "pass\|wallet\|tammer"; then
            echo "✅ Found potential certificate:"
            echo "$cert_info"
            echo ""
        fi
    fi
done

echo ""
echo "=========================================="
echo "📋 Manual Steps (if certificate found):"
echo "=========================================="
echo ""
echo "1. Open Keychain Access (already opened)"
echo "2. Click on 'My Certificates' in left sidebar"
echo "3. Search for: 'tammer', 'wallet', or 'pass'"
echo "4. If you find a Pass Type ID certificate:"
echo "   - Right-click on it"
echo "   - Select 'Export [certificate name]'"
echo "   - Choose format: 'Personal Information Exchange (.p12)'"
echo "   - Enter password (remember this password!)"
echo "   - Save to: includes/certs/tammer.wallet.p12"
echo ""

echo "=========================================="
echo "🆕 If certificate NOT found:"
echo "=========================================="
echo ""
echo "You need to create it from Apple Developer Portal:"
echo ""
echo "1. Go to: https://developer.apple.com/account"
echo "2. Certificates, Identifiers & Profiles"
echo "3. Certificates > '+' button"
echo "4. Select 'Pass Type ID Certificate'"
echo "5. Choose Pass Type ID: pass.com.tammer.loyaltycard"
echo "6. Download .cer file"
echo "7. Double-click to install in Keychain"
echo "8. Export as .p12 with password"
echo ""

echo "=========================================="
echo "💡 Quick Command to Open Keychain:"
echo "=========================================="
echo ""
echo "Keychain Access is already open."
echo "Search for: 'tammer' or 'wallet' or 'pass'"
echo ""

