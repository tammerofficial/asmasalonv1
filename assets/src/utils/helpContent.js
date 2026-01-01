/**
 * Help/FAQ Content for Asmaa Salon Pages
 * Rule #7: FAQ/Help Content - MANDATORY FOR NEW PAGES
 */

export const helpContent = {
  // Unified Data Journey
  unifiedDataJourney: {
    title: 'رحلة البيانات الموحدة',
    description: 'كيف تعمل جميع أقسام النظام معاً في رحلة بيانات واحدة مترابطة',
    faqs: [
      {
        question: 'ما هي رحلة البيانات الموحدة؟',
        answer: 'رحلة البيانات الموحدة هي نظام مترابط يربط جميع أقسام صالون أسماء. تبدأ من الكتالوج (الخدمات والمنتجات) والأشخاص (العملاء والموظفون)، ثم التفاعل (الحجوزات أو الطابور)، وصولاً إلى معالجة الطلب وإنشاء الفواتير والمدفوعات، مع تحديث تلقائي للعمولات ونقاط الولاء والمخزون.',
        icon: 'cil-info',
      },
      {
        question: 'كيف يمكنني إنشاء طلب من حجز؟',
        answer: 'بعد إكمال الخدمة للحجز، ستظهر زر "Checkout" (الدفع). اضغط عليه لإنشاء طلب WooCommerce تلقائياً مع الفاتورة والدفع، وسيتم تحديث العمولات ونقاط الولاء تلقائياً.',
        icon: 'cil-cart',
      },
      {
        question: 'كيف يمكنني إنشاء طلب من تذكرة الطابور؟',
        answer: 'بعد بدء الخدمة أو إكمالها لتذكرة الطابور، ستظهر زر "Checkout" (الدفع). اضغط عليه لإنشاء طلب كامل مع جميع التفاصيل المالية.',
        icon: 'cil-list',
      },
      {
        question: 'ما الذي يحدث تلقائياً عند إنشاء طلب؟',
        answer: 'عند إنشاء أي طلب (من POS أو الحجوزات أو الطابور)، يتم تلقائياً: إنشاء طلب WooCommerce، إنشاء فاتورة صالون أسماء، تسجيل الدفع، تحديث المخزون، حساب عمولات الموظفين، منح نقاط الولاء للعميل، وإرسال الإشعارات.',
        icon: 'cil-sync',
      },
      {
        question: 'كيف يتم ربط البيانات بين الأقسام؟',
        answer: 'جميع المعاملات تمر عبر Unified_Order_Service الذي يضمن إنشاء سجلات مترابطة في جميع الجداول (WooCommerce Orders، الفواتير، المدفوعات، العمولات، نقاط الولاء) مع الحفاظ على التكامل والاتساق.',
        icon: 'cil-link',
      },
    ],
  },
  
  // Dashboard
  dashboard: {
    title: 'لوحة التحكم',
    description: 'نظرة عامة على جميع أنشطة الصالون',
    faqs: [
      {
        question: 'كيف يتم تحميل البيانات في لوحة التحكم؟',
        answer: 'يتم تحميل البيانات الأساسية (الإحصائيات، الحجوزات الأخيرة، الطابور) فوراً. ثم يتم تحميل البيانات الأخرى (الخدمات، الموظفون، الإشعارات) في الخلفية بعد تحميل اللوحة لضمان سرعة الأداء.',
        icon: 'cil-speedometer',
      },
    ],
  },
  
  // Bookings
  bookings: {
    title: 'الحجوزات',
    description: 'إدارة حجوزات العملاء',
    faqs: [
      {
        question: 'كيف أنشئ طلب من حجز مكتمل؟',
        answer: 'بعد إكمال الخدمة للحجز، اضغط على زر "Checkout" (الدفع) الموجود في قائمة الحجوزات. سيتم إنشاء طلب كامل تلقائياً.',
        icon: 'cil-cart',
      },
    ],
  },
  
  // Queue
  queue: {
    title: 'الطابور',
    description: 'إدارة تذاكر الانتظار',
    faqs: [
      {
        question: 'كيف أنشئ طلب من تذكرة طابور؟',
        answer: 'بعد بدء الخدمة أو إكمالها، اضغط على زر "Checkout" (الدفع) الموجود في قائمة التذاكر. سيتم إنشاء طلب كامل مع الفاتورة والدفع.',
        icon: 'cil-cart',
      },
    ],
  },
  
  // POS
  pos: {
    title: 'نقطة البيع (POS)',
    description: 'معالجة المبيعات مباشرة',
    faqs: [
      {
        question: 'كيف يعمل POS مع باقي النظام؟',
        answer: 'عند معالجة طلب POS، يتم إنشاء طلب WooCommerce تلقائياً مع الفاتورة والدفع، وتحديث المخزون، وحساب العمولات ونقاط الولاء. كل هذا يحدث في رحلة بيانات واحدة موحدة.',
        icon: 'cil-cart',
      },
    ],
  },
};

/**
 * Get help content for a specific page
 * @param {string} pageKey - Page identifier (e.g., 'bookings', 'queue', 'pos')
 * @returns {object|null} Help content object or null if not found
 */
export function getHelpContent(pageKey) {
  return helpContent[pageKey] || helpContent.unifiedDataJourney;
}

export default helpContent;

