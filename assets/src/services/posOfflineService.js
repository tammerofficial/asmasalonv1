import Dexie from 'dexie';

// إنشاء قاعدة بيانات Dexie
class POSOfflineDB extends Dexie {
    constructor() {
        super('asmaa_pos_offline');
        this.version(1).stores({
            pending_orders: 'client_side_id, created_at, sync_attempts',
            cart_sessions: 'session_id, customer_id, created_at',
        });
    }
}

const db = new POSOfflineDB();

// توليد UUID فريد - يتم توليده عند فتح السلة وليس عند الدفع فقط
// هذا يضمن ثبات المعرّف حتى لو تكررت محاولات الإرسال
export function generateClientId() {
    return 'pos_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// حفظ client_side_id في sessionStorage عند فتح السلة
export function initializeCartSession() {
    let sessionId = sessionStorage.getItem('pos_cart_session_id');
    if (!sessionId) {
        sessionId = generateClientId();
        sessionStorage.setItem('pos_cart_session_id', sessionId);
    }
    return sessionId;
}

// حفظ طلب في IndexedDB
export async function savePendingOrder(orderData, client_side_id = null) {
    // استخدام client_side_id من sessionStorage إذا لم يتم تمريره
    if (!client_side_id) {
        client_side_id = sessionStorage.getItem('pos_cart_session_id') || generateClientId();
    }
    
    const pendingOrder = {
        client_side_id,
        order_data: orderData,
        created_at: new Date().toISOString(),
        sync_attempts: 0,
        last_sync_attempt: null,
    };
    
    await db.pending_orders.put(pendingOrder);
    return client_side_id;
}

// قراءة جميع الطلبات المحفوظة (باستخدام Dexie Query)
export async function getPendingOrders() {
    return await db.pending_orders
        .orderBy('created_at')
        .toArray();
}

// البحث عن طلبات عميل معين (مثال على قوة Dexie)
export async function getPendingOrdersByCustomer(customerId) {
    return await db.pending_orders
        .where('order_data.customer_id')
        .equals(customerId)
        .toArray();
}

// حذف طلب بعد المزامنة الناجحة
export async function deletePendingOrder(client_side_id) {
    await db.pending_orders.delete(client_side_id);
}

// تحديث عدد محاولات المزامنة
export async function updateSyncAttempt(client_side_id, success) {
    if (success) {
        await db.pending_orders.delete(client_side_id);
    } else {
        const order = await db.pending_orders.get(client_side_id);
        if (order) {
            await db.pending_orders.update(client_side_id, {
                sync_attempts: (order.sync_attempts || 0) + 1,
                last_sync_attempt: new Date().toISOString(),
            });
        }
    }
}

// الحصول على عدد الطلبات المعلقة (للعرض في UI)
export async function getPendingOrdersCount() {
    return await db.pending_orders.count();
}

// مزامنة الطلبات المعلقة مع الخادم
export async function syncPendingOrders(api) {
    const pending = await getPendingOrders();
    if (pending.length === 0) return { synced: 0, failed: 0, results: [] };
    
    try {
        const response = await api.post('/pos/sync-pending', {
            orders: pending.map(p => ({
                client_side_id: p.client_side_id,
                order_data: p.order_data,
            })),
        });
        
        const results = response.data?.results || [];
        const synced = response.data?.synced || 0;
        const failed = response.data?.failed || 0;
        
        // حذف الطلبات الناجحة
        for (const result of results) {
            if (result.success) {
                await deletePendingOrder(result.client_side_id);
            } else {
                await updateSyncAttempt(result.client_side_id, false);
            }
        }
        
        return { synced, failed, results };
    } catch (error) {
        console.error('Error syncing pending orders:', error);
        return { synced: 0, failed: pending.length, results: [] };
    }
}

