# خطوات إنشاء شهادة Apple Wallet من Terminal

## ⚠️ ملاحظة مهمة

**لا يمكن إنشاء شهادة Pass Type ID Certificate من Terminal مباشرة.**

يجب إنشاؤها من [Apple Developer Portal](https://developer.apple.com/account) أولاً، ثم تصديرها من Keychain Access.

## 📋 الخطوات الكاملة

### الخطوة 1: إنشاء الشهادة على Apple Developer Portal

1. اذهب إلى: https://developer.apple.com/account
2. سجل الدخول بحساب Apple Developer
3. اذهب إلى: **Certificates, Identifiers & Profiles**
4. اضغط على **Certificates** في القائمة الجانبية
5. اضغط على زر **"+"** لإنشاء شهادة جديدة
6. اختر **"Pass Type ID Certificate"**
7. اختر Pass Type ID: `pass.com.tammer.loyaltycard`
8. اتبع التعليمات لإنشاء Certificate Signing Request (CSR)
9. حمّل الشهادة (.cer file)

### الخطوة 2: تثبيت الشهادة في Keychain

```bash
# افتح ملف .cer (يفتح تلقائياً في Keychain)
open /path/to/your/certificate.cer
```

أو:
```bash
# من Terminal
security add-certificates /path/to/certificate.cer
```

### الخطوة 3: تصدير الشهادة كـ .p12 (هنا تُكتب كلمة المرور)

#### الطريقة 1: من Keychain Access (GUI)
1. افتح Keychain Access
2. ابحث عن الشهادة في "My Certificates"
3. اضغط بزر الماوس الأيمن
4. اختر "Export [اسم الشهادة]"
5. اختر التنسيق: "Personal Information Exchange (.p12)"
6. **اكتب كلمة مرور** (هذه هي الكلمة التي تحتاجها!)
7. احفظ الملف

#### الطريقة 2: من Terminal (إذا كانت الشهادة موجودة)

```bash
# ابحث عن الشهادة أولاً
security find-identity -v -p codesigning | grep -i "pass\|wallet"

# تصدير الشهادة (يطلب كلمة مرور)
security export -k ~/Library/Keychains/login.keychain-db \
  -t identities -f pkcs12 -o includes/certs/tammer.wallet.p12 \
  -P "YOUR_PASSWORD_HERE"
```

**ملاحظة**: يجب أن تعرف اسم الشهادة أو hash الخاص بها.

### الخطوة 4: نسخ الشهادة إلى المشروع

```bash
# إذا صدرتها إلى مكان آخر
cp ~/Downloads/tammer.wallet.p12 includes/certs/
chmod 600 includes/certs/tammer.wallet.p12
```

## 🔍 البحث عن شهادة موجودة

إذا كانت الشهادة موجودة في Keychain:

```bash
# ابحث عن جميع شهادات Apple
security find-identity -v -p codesigning

# ابحث عن شهادات محددة
security find-certificate -a -c "tammer" -p
security find-certificate -a -c "wallet" -p
security find-certificate -a -c "pass" -p
```

## 📝 السكريبتات المساعدة

تم إنشاء سكريبتات مساعدة في:
- `scripts/create_apple_wallet_cert.sh` - دليل إنشاء الشهادة
- `scripts/find_and_export_cert.sh` - البحث عن شهادة موجودة

## 🎯 الخلاصة

1. **إنشاء الشهادة**: من Apple Developer Portal (لا يمكن من Terminal)
2. **تثبيت الشهادة**: تلقائياً عند فتح ملف .cer
3. **تصدير الشهادة**: من Keychain Access أو Terminal
4. **كلمة المرور**: تُكتب عند التصدير (أنت تختارها)

---

**الآن**: افتح Keychain Access وابحث عن شهادة "tammer" أو "wallet" أو "pass". إذا وجدتها، صدرها كـ .p12 مع كلمة مرور تعرفها.

