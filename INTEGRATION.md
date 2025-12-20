# Asmaa Salon API Integration Guide

This document explains how external plugins (Booking for bookings, POS plugins for checkout) should integrate with Asmaa Salon's REST API to follow the complete business scenario.

## Base URL

All endpoints are under:
```
/wp-json/asmaa-salon/v1/
```

## Authentication

All endpoints require WordPress authentication (logged-in user with appropriate capabilities).

For API calls, include the WordPress nonce in headers:
```javascript
headers: {
  'X-WP-Nonce': AsmaaSalonConfig.nonce
}
```

---

## 1. Booking Flow

### Create Booking (from Booking or external booking form)

**Endpoint:** `POST /bookings`

**Payload:**
```json
{
  "customer_id": 156,
  "staff_id": 3,
  "service_id": 5,
  "booking_date": "2025-12-15",
  "booking_time": "14:00:00",
  "end_time": "16:00:00",
  "status": "pending",
  "price": 45.000,
  "source": "online"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 892,
    "customer_id": 156,
    "staff_id": 3,
    "service_id": 5,
    "booking_date": "2025-12-15",
    "booking_time": "14:00:00",
    "status": "pending",
    ...
  },
  "message": "Booking created successfully"
}
```

### Confirm Booking (when customer arrives)

**Endpoint:** `POST /bookings/{id}/confirm`

**Response:** Updated booking with `status: "confirmed"` and `confirmed_at` timestamp.

### Start Service

**Endpoint:** `POST /bookings/{id}/start`

**Response:** Updated booking with `status: "in_progress"`.

### Complete Booking

**Endpoint:** `POST /bookings/{id}/complete`

**Response:** Updated booking with `status: "completed"` and `completed_at` timestamp.

---

## 2. Queue Management

### Create Queue Ticket (when customer checks in)

**Endpoint:** `POST /queue`

**Payload:**
```json
{
  "customer_id": 156,
  "service_id": 5,
  "staff_id": 3,
  "booking_id": 892,
  "notes": "Optional notes"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 345,
    "ticket_number": "T-045",
    "customer_id": 156,
    "service_id": 5,
    "staff_id": 3,
    "status": "waiting",
    "check_in_at": "2025-12-15 13:50:00",
    ...
  }
}
```

**Note:** This automatically creates a `worker_call` entry in `asmaa_worker_calls` table, making it appear in Staff Room immediately.

### Call Next Ticket

**Endpoint:** `POST /queue/call-next`

**Response:** The next waiting ticket is updated to `status: "called"` and `called_at` is set. A worker call is also updated/created.

### Start Serving

**Endpoint:** `POST /queue/{id}/start`

**Response:** Ticket status becomes `"serving"` and `serving_started_at` is set. Related booking (if linked) becomes `"in_progress"`.

### Complete Ticket

**Endpoint:** `POST /queue/{id}/complete`

**Response:** Ticket status becomes `"completed"` and `completed_at` is set. Related booking becomes `"completed"`.

---

## 3. Staff Rating

### Submit Rating (after service completion)

**Endpoint:** `POST /ratings`

**Payload:**
```json
{
  "staff_id": 3,
  "customer_id": 156,
  "booking_id": 892,
  "rating": 5,
  "comment": "ممتازة وذوقها حلو والنتيجة أفضل من المتوقع! 💕"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "staff_id": 3,
    "customer_id": 156,
    "booking_id": 892,
    "rating": 5,
    "comment": "...",
    "created_at": "2025-12-15 16:16:00"
  },
  "message": "Rating created successfully"
}
```

**Note:** This automatically updates the staff's aggregated `rating`, `total_ratings`, and `total_services` in `asmaa_staff` table.

**Rating UI:** Customers can rate via `/rating?booking_id=892` (public page, no auth required for simplicity, or add token-based auth if needed).

---

## 4. Order Creation (from POS)

### Create Order

**Endpoint:** `POST /orders`

**Payload:**
```json
{
  "customer_id": 156,
  "staff_id": 3,
  "booking_id": 892,
  "subtotal": 45.000,
  "discount": 0.000,
  "tax": 0.000,
  "total": 45.000,
  "status": "pending",
  "payment_status": "unpaid",
  "items": [
    {
      "item_type": "service",
      "item_id": 5,
      "item_name": "صبغة شعر كاملة",
      "quantity": 1,
      "unit_price": 45.000,
      "discount": 0.000,
      "total": 45.000,
      "staff_id": 3
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 567,
    "order_number": "ORD-2025-001234",
    "customer_id": 156,
    "staff_id": 3,
    "booking_id": 892,
    "subtotal": 45.000,
    "total": 45.000,
    "status": "pending",
    "payment_status": "unpaid",
    "items": [
      {
        "id": 1234,
        "order_id": 567,
        "item_type": "service",
        "item_id": 5,
        "item_name": "صبغة شعر كاملة",
        "quantity": 1,
        "unit_price": 45.000,
        "total": 45.000,
        "staff_id": 3
      }
    ],
    ...
  },
  "message": "Order created successfully"
}
```

**Automatic Actions:**
- Staff commissions are automatically calculated and inserted into `asmaa_staff_commissions`:
  - For each `item` with `item_type = "service"` and a `staff_id`:
    - Commission rate = `staff.commission_rate` OR `commission_settings.service_commission_rate`
    - `base_amount` = `item.total`
    - `commission_amount` = `base_amount * rate / 100`
    - If `booking_id` is provided, latest rating is checked:
      - 5 stars → adds `rating_bonus_5_star` from settings
      - 4 stars → adds `rating_bonus_4_star` from settings
    - `final_amount` = `commission_amount + rating_bonus`
    - Status = `"pending"`

---

## 5. Payment Processing

### Create Payment

**Endpoint:** `POST /payments`

**Payload:**
```json
{
  "customer_id": 156,
  "order_id": 567,
  "amount": 45.000,
  "payment_method": "knet",
  "reference_number": "REF-789456123",
  "status": "completed",
  "payment_date": "2025-12-15 16:20:00"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 891,
    "payment_number": "PAY-2025-005678",
    "order_id": 567,
    "customer_id": 156,
    "invoice_id": 456,
    "amount": 45.000,
    "payment_method": "knet",
    "reference_number": "REF-789456123",
    "status": "completed",
    ...
  },
  "message": "Payment created successfully"
}
```

**Automatic Actions:**
1. **Invoice Auto-Creation:** If `order_id` is provided and no `invoice_id` is given, an invoice is automatically created:
   - Invoice number: `INV-YYYYMMDD-XXXX`
   - Invoice items are copied from order items
   - `paid_amount` = payment amount
   - `due_amount` = order total - payment amount
   - Status = `"paid"` if fully paid, `"partial"` otherwise

2. **Order Update:** Order `payment_status` → `"paid"`, `status` → `"completed"`, `payment_method` is set.

3. **Customer Stats:** `total_visits` +1, `total_spent` += payment amount.

4. **Loyalty Points:** Automatically grants **10 points per 1 KWD**:
   - 45.000 KWD → 450 points
   - Creates `loyalty_transaction` with `type = "earned"`
   - Updates `customers.loyalty_points`

---

## 6. Loyalty Points Redemption (Before Order Creation)

### Redeem Points as Discount

**Endpoint:** `POST /loyalty/redeem`

**Payload:**
```json
{
  "customer_id": 156,
  "points": 1000,
  "reference_type": "order",
  "reference_id": null,
  "description": "استخدام نقاط كخصم"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customer_id": 156,
    "points_redeemed": 1000,
    "balance_after": 850
  },
  "message": "Points redeemed successfully"
}
```

**Usage in POS:**
1. Before creating order, call `/loyalty/redeem` with points to redeem (e.g., 1000 points = 10 KWD discount).
2. Use the discount amount (10.000 KWD) as `discount` when creating the order.
3. After order creation, link the redemption transaction to the order by updating the transaction's `reference_id` (or handle it in your POS logic).

**Points-to-Cash Rule:** Default is **100 points = 1 KWD**. This can be configured in `commission_settings` or a separate settings table if needed.

---

## 7. Worker Calls (Staff Room)

### Get Worker Calls (for Staff Room dashboard)

**Endpoint:** `GET /worker-calls`

**Query Params:**
- `date` (optional, defaults to today)
- `status` (optional)
- `staff_id` (optional)

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 234,
        "staff_id": 3,
        "ticket_id": 345,
        "customer_name": "سارة محمد",
        "status": "pending",
        "ticket_number": "T-045",
        "queue_status": "waiting",
        "service_name": "صبغة شعر كاملة",
        "staff_name": "نورة",
        "staff_position": "Hair Specialist",
        ...
      }
    ],
    "pagination": { ... }
  }
}
```

### Call Customer

**Endpoint:** `POST /worker-calls/{id}/call-customer`

**Response:** Worker call status → `"customer_called"`, queue ticket status → `"called"`.

### Call Staff

**Endpoint:** `POST /worker-calls/{id}/call-staff`

**Response:** Worker call status → `"staff_called"`.

### Accept Call (Staff accepts)

**Endpoint:** `POST /worker-calls/{id}/accept`

**Response:** Worker call status → `"accepted"`, queue ticket → `"serving"`.

---

## 8. Reports

### Daily Sales

**Endpoint:** `GET /reports/daily-sales?date=2025-12-15`

**Response:**
```json
{
  "success": true,
  "data": {
    "date": "2025-12-15",
    "total_orders": 42,
    "total_revenue": 1850.000,
    "avg_order_value": 44.048,
    "payment_methods": [
      { "payment_method": "cash", "count": 15, "total": 647.500 },
      { "payment_method": "knet", "count": 21, "total": 925.000 },
      { "payment_method": "card", "count": 6, "total": 277.500 }
    ],
    "comparison": {
      "yesterday": { "revenue": 1720.000, "change_percent": 7.6 },
      "last_week": { "revenue": 1650.000, "change_percent": 12.1 }
    }
  }
}
```

### Staff Performance

**Endpoint:** `GET /reports/staff-performance?date=2025-12-15`

**Or with range:** `GET /reports/staff-performance?start_date=2025-12-01&end_date=2025-12-31`

**Response:**
```json
{
  "success": true,
  "data": {
    "date": "2025-12-15",
    "data": [
      {
        "id": 3,
        "name": "نورة",
        "services_count": 8,
        "revenue": 385.000,
        "rating": 4.82,
        "total_commissions": 278.500
      },
      ...
    ]
  }
}
```

### Queue Stats

**Endpoint:** `GET /reports/queue-stats?date=2025-12-15`

**Response:**
```json
{
  "success": true,
  "data": {
    "date": "2025-12-15",
    "total_tickets": 42,
    "completed": 42,
    "cancelled": 0,
    "avg_waiting_time": 12.0,
    "avg_service_time": 87.0
  }
}
```

### Commissions Report

**Endpoint:** `GET /reports/commissions?start_date=2025-12-01&end_date=2025-12-31&staff_id=3`

**Response:**
```json
{
  "success": true,
  "data": {
    "start_date": "2025-12-01",
    "end_date": "2025-12-31",
    "data": [
      {
        "staff_id": 3,
        "staff_name": "نورة",
        "total_services": 187,
        "total_revenue": 8735.000,
        "base_commission": 1310.250,
        "total_bonuses": 7850.000,
        "total_commission": 9160.250
      }
    ]
  }
}
```

---

## Complete Scenario Flow (API Calls)

### Step-by-step API sequence matching the scenario:

1. **Booking exists** (created via Booking or `POST /bookings`)

2. **Customer arrives:**
   - `POST /bookings/{id}/confirm` → Booking confirmed
   - `POST /queue` with `booking_id: 892` → Queue ticket created + worker call created

3. **Call customer:**
   - `POST /queue/call-next` → Next ticket called

4. **Staff accepts:**
   - `POST /worker-calls/{id}/accept` → Service starts
   - Or: `POST /queue/{id}/start` → Service starts

5. **Service completes:**
   - `POST /queue/{id}/complete` → Ticket completed, booking completed

6. **Customer rates:**
   - `POST /ratings` with `booking_id: 892, rating: 5` → Rating saved, staff stats updated

7. **Create order (from POS):**
   - `POST /orders` with `booking_id: 892, items: [{item_type: "service", ...}]`
   - **Automatic:** Commissions calculated and inserted (with rating bonus if rating exists)

8. **Process payment:**
   - `POST /payments` with `order_id: 567, amount: 45.000, payment_method: "knet"`
   - **Automatic:**
     - Invoice created
     - Order marked as paid
     - Customer stats updated
     - **450 loyalty points granted** (10 points per 1 KWD)

9. **View reports:**
   - `GET /reports/daily-sales?date=2025-12-15`
   - `GET /reports/staff-performance?date=2025-12-15`
   - `GET /reports/queue-stats?date=2025-12-15`
   - `GET /reports/commissions?start_date=2025-12-01&end_date=2025-12-31`

---

## Display Screen

**Public URL (no auth):**
```
/wp-json/asmaa-salon/v1/display/queue
```

Or via WordPress rewrite:
```
/asmaa-salon-display
```

This loads the Vue app in display mode showing the waiting queue on a TV/monitor.

---

## Notes

- All timestamps are in MySQL datetime format (`Y-m-d H:i:s`).
- All monetary values use 3 decimal places (KWD format: `45.000`).
- Commission calculations happen automatically on order creation.
- Loyalty points are granted automatically on payment completion.
- Invoices are auto-created when payments are made for orders.
- Activity logs and notifications are stored but actual SMS/email delivery requires integration with your SMS gateway or WP mail plugins via WordPress hooks.
