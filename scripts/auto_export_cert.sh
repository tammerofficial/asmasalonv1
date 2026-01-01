#!/bin/bash

# Auto export certificate (prompts for password; never hardcode secrets)

echo "=========================================="
echo "Auto Export Certificate"
echo "=========================================="
echo ""

CERT_DIR="includes/certs"
CER_FILE=""
P12_FILE="$CERT_DIR/tammer.wallet.p12"

# Read password from env or prompt (do NOT commit real passwords)
PASSWORD="${ASMAA_WALLET_CERT_PASSWORD:-}"
if [ -z "$PASSWORD" ]; then
  read -s -p "Enter password for the .p12 file: " PASSWORD
  echo ""
fi

# Find .cer file
for file in "$CERT_DIR"/*.cer "$CERT_DIR"/*.cert; do
    if [ -f "$file" ]; then
        CER_FILE="$file"
        break
    fi
done

if [ -z "$CER_FILE" ] || [ ! -f "$CER_FILE" ]; then
    echo "Certificate file (.cer) not found in $CERT_DIR"
    echo ""
    echo "Please:"
    echo "1. Download the certificate from Apple Developer Portal"
    echo "2. Save it to: $CERT_DIR/"
    echo "3. Run this script again"
    echo ""
    exit 1
fi

echo "Found certificate: $CER_FILE"
echo ""

# Install certificate
echo "Installing certificate in Keychain..."
open "$CER_FILE"
sleep 3

echo "Certificate opened (should be installed in Keychain)"
echo ""

# Find certificate in Keychain
echo "Searching for certificate in Keychain..."
CERT_SUBJECT=$(openssl x509 -in "$CER_FILE" -noout -subject 2>/dev/null | sed 's/.*CN=//')

echo "Certificate subject: $CERT_SUBJECT"
echo ""

# Try to export using security command
echo "Exporting certificate as .p12..."
echo "   (Password will NOT be printed)"
echo ""

# Find the certificate hash
CERT_HASH=$(security find-certificate -a -c "$CERT_SUBJECT" 2>/dev/null | grep "SHA-1" | head -1 | awk '{print $NF}')

if [ -z "$CERT_HASH" ]; then
    echo "Could not find certificate hash automatically"
    echo ""
    echo "Please export manually from Keychain Access:"
    echo "1. Open Keychain Access (should be open)"
    echo "2. Search for: 'tammer' or 'loyaltycard' or 'pass'"
    echo "3. Right-click on the certificate"
    echo "4. Select 'Export [certificate name]'"
    echo "5. Choose format: 'Personal Information Exchange (.p12)'"
    echo "6. Enter the password you chose for the .p12 file"
    echo "7. Save to: $P12_FILE"
    echo ""
    
    # Open Keychain Access
    open -a "Keychain Access"
    
    echo "Waiting for manual export..."
    echo "   After exporting, the certificate will be ready to use"
    echo ""
    
    exit 0
fi

echo "Found certificate hash: $CERT_HASH"
echo ""

# Export using security command
security export -k ~/Library/Keychains/login.keychain-db \
  -t identities -f pkcs12 -o "$P12_FILE" \
  -P "$PASSWORD" \
  "$CERT_HASH" 2>&1

if [ $? -eq 0 ] && [ -f "$P12_FILE" ]; then
    chmod 600 "$P12_FILE"
    echo "Certificate exported successfully!"
    echo "   File: $P12_FILE"
    echo ""
    echo "IMPORTANT: Save this password (it will not be printed)."
    echo ""
    echo "Next Steps:"
    echo "1. Go to: Settings > Apple Wallet"
    echo "2. Enter:"
    echo "   - Certificate File Path: tammer.wallet.p12"
    echo "   - Certificate Password: (the password you entered)"
    echo "   - Team ID: 6SGU7C9M42"
    echo "   - Pass Type ID: pass.com.tammer.loyaltycard"
    echo ""
else
    echo "Automatic export failed. Trying alternative method..."
    echo ""
    
    # Alternative: use openssl if we have the key
    if [ -f "$CERT_DIR/tammer.wallet.key" ]; then
        echo "Trying to combine certificate and key..."
        
        # Convert .cer to .pem
        openssl x509 -inform DER -in "$CER_FILE" -out "$CERT_DIR/cert.pem" 2>/dev/null
        
        if [ -f "$CERT_DIR/cert.pem" ]; then
            # Combine cert and key into p12
            openssl pkcs12 -export \
                -out "$P12_FILE" \
                -inkey "$CERT_DIR/tammer.wallet.key" \
                -in "$CERT_DIR/cert.pem" \
                -passout "pass:$PASSWORD" \
                -name "tammer.wallet" 2>&1
            
            if [ -f "$P12_FILE" ]; then
                chmod 600 "$P12_FILE"
                echo "Certificate exported successfully using OpenSSL!"
                echo "   File: $P12_FILE"
                echo ""
            else
                echo "Export failed. Please export manually from Keychain Access"
                open -a "Keychain Access"
            fi
        fi
    else
        echo "Private key not found. Please export manually from Keychain Access"
        open -a "Keychain Access"
    fi
fi

echo ""
echo "=========================================="

