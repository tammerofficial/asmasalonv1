# البحث في محادثات Cursor

## 🔍 البحث المكثف

تم البحث في:
- ✅ مستودع `loyaity` (https://github.com/tammerofficial/loyaity)
- ✅ مستودع `wallet` (https://github.com/tammerofficial/wallet)
- ✅ جميع ملفات `.md`, `.php`, `.sh`, `.ps1`, `.json`
- ✅ ملفات الإعداد والتوثيق
- ❌ **محادثات Cursor**: غير موجودة في المستودعات (عادة محلية فقط)

## ❌ النتيجة

**محادثات Cursor غير موجودة** في المستودعات العامة لأنها عادة تُحفظ محلياً فقط ولا تُرفع إلى git.

### ما تم العثور عليه:

1. **كلمة مرور السيرفر:**
   - `Ali@kuwait@90` (لكن هذه للسيرفر، وليست للشهادة)
   - موجودة في: `fix-500-error.sh`, `deploy.sh`, `cloudways-fix.sh`

2. **Placeholders فقط:**
   - `your_password`
   - `your_certificate_password`
   - `change_me`
   - (فارغة)

3. **معلومات أخرى:**
   - ✅ Team ID: `6SGU7C9M42`
   - ✅ Pass Type ID: `pass.com.tammer.loyaltycard`
   - ✅ Certificate File: `tammer.wallet.p12`
   - ✅ WWDR Certificate: `AppleWWDRCAG3.cer`

## 💡 الحلول المتبقية

### الحل 1: البحث محلياً في Cursor
إذا كان لديك المشروع محلياً:
1. افتح المشروع في Cursor
2. ابحث في محادثات Cursor المحلية
3. ابحث عن: "certificate password", "p12 password", "wallet password"

### الحل 2: البحث في السيرفر
إذا كان لديك وصول للسيرفر:
```bash
# ابحث في ملف .env على السيرفر
grep APPLE_WALLET_CERTIFICATE_PASSWORD /path/to/.env

# أو في أي ملفات أخرى
find /path/to/project -name "*.env*" -exec grep -l "CERTIFICATE_PASSWORD" {} \;
```

### الحل 3: إنشاء شهادة جديدة (الأفضل)
1. اذهب إلى [Apple Developer Portal](https://developer.apple.com/account)
2. Certificates, Identifiers & Profiles > Certificates
3. أنشئ شهادة جديدة من نوع "Pass Type ID Certificate"
4. استخدم Pass Type ID: `pass.com.tammer.loyaltycard`
5. صدر الشهادة من Keychain مع كلمة مرور تعرفها
6. استبدل `tammer.wallet.p12` بالشهادة الجديدة

## 📋 الإعدادات الجاهزة

بمجرد الحصول على كلمة المرور:

| الحقل | القيمة |
|-------|--------|
| **Apple Team ID** | `6SGU7C9M42` ✅ |
| **Pass Type ID** | `pass.com.tammer.loyaltycard` ✅ |
| **Certificate File Path** | `tammer.wallet.p12` ✅ |
| **WWDR Certificate Path** | `AppleWWDRCAG3.cer` ✅ |
| **Certificate Password** | ❓ **مطلوب** |

## 🎯 التوصية

**الأفضل**: إنشاء شهادة جديدة من Apple Developer Portal مع كلمة مرور تعرفها. هذا أسرع وأكثر أماناً.

---

**ملاحظة**: محادثات Cursor عادة تكون محلية فقط ولا تُرفع إلى git لأسباب أمنية.

