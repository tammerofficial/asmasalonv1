/**
 * Asmaa Salon Admin Placeholder
 * This will be replaced with Vue/CoreUI SPA later
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const root = document.getElementById('asmaa-salon-vue-root');
        if (!root) return;

        // Test API connection
        fetch(AsmaaSalonConfig.restUrl + 'ping', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': AsmaaSalonConfig.nonce
            }
        })
        .then(response => response.json())
        .then(data => {
            root.innerHTML = `
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 24px; background: #fff; max-width: 800px;">
                    <h2 style="color: ${AsmaaSalonConfig.primaryColor}; margin-bottom: 16px;">
                        📊 لوحة تحكم صالون أسماء الجارالله
                    </h2>
                    <div style="margin-bottom: 16px;">
                        <p><strong>✅ البلاجن يعمل بنجاح!</strong></p>
                        <p>الـ REST API جاهزة على: <code>${AsmaaSalonConfig.restUrl}</code></p>
                        <p>الإصدار: ${AsmaaSalonConfig.version}</p>
                    </div>
                    <div style="background: #f5f5f5; padding: 16px; border-radius: 4px; margin-top: 16px;">
                        <h3 style="margin-top: 0;">🔧 الخطوات التالية:</h3>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li>بناء واجهة Vue + CoreUI</li>
                            <li>إضافة باقي الـ Controllers (Staff, Services, Bookings, etc.)</li>
                            <li>تنفيذ التدفقات الأساسية (الحجز، قائمة الانتظار، إلخ)</li>
                        </ul>
                    </div>
                    <div style="margin-top: 16px;">
                        <button id="test-customers-btn" style="background: ${AsmaaSalonConfig.primaryColor}; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                            اختبار API العملاء
                        </button>
                    </div>
                    <div id="api-test-result" style="margin-top: 16px;"></div>
                </div>
            `;
        })
        .catch(error => {
            root.innerHTML = `
                <div style="border: 1px solid #f00; border-radius: 8px; padding: 24px; background: #fff; max-width: 800px;">
                    <h2 style="color: #f00;">❌ خطأ في الاتصال بالـ API</h2>
                    <p>${error.message}</p>
                </div>
            `;
        });
    });

    // Test Customers API - attach event listener properly
    setTimeout(function() {
        const testBtn = document.getElementById('test-customers-btn');
        if (testBtn) {
            testBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const resultDiv = document.getElementById('api-test-result');
                if (!resultDiv) return;
                
                resultDiv.innerHTML = '<p>جاري الاختبار...</p>';

                fetch(AsmaaSalonConfig.restUrl + 'customers', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': AsmaaSalonConfig.nonce
                    }
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = `
                        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px;">
                            <strong>✅ نجح الاتصال!</strong>
                            <pre style="margin-top: 8px; overflow-x: auto;">${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px;">
                            <strong>❌ فشل الاتصال:</strong> ${error.message}
                        </div>
                    `;
                });
            });
        }
    }, 100);
})();
