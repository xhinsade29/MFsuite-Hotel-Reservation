# MFsuite Hotel Reservation System - Architecture Documentation

## 1. System Architecture Diagram (Level 0)

### Overview
The MFsuite Hotel Reservation System is a multi-tier web application designed for hotel room reservation management with integrated payment processing and guest wallet functionality.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          PRESENTATION LAYER (Client)                        │
│                                                                             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐          │
│  │  Guest Portal    │  │  Admin Portal    │  │  Public Website  │          │
│  │  (User Pages)    │  │  (Admin Pages)   │  │  (Landing Page)  │          │
│  │                  │  │                  │  │                  │          │
│  │ • Booking Form   │  │ • Dashboard      │  │ • About          │          │
│  │ • Reservations   │  │ • Reservations   │  │ • Rooms Info     │          │
│  │ • Wallet Mgmt    │  │ • Guests Mgmt    │  │ • Services       │          │
│  │ • Notifications  │  │ • Payments       │  │ • Contact        │          │
│  │ • Profile        │  │ • Reports        │  │                  │          │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘          │
│         │                       │                       │                   │
│         └───────────────────────┼───────────────────────┘                   │
│                                 │                                           │
└─────────────────────────────────┼───────────────────────────────────────────┘
                                  │ HTTP/AJAX Requests
                                  │ (Bootstrap 5 UI, JavaScript)
                                  │
┌─────────────────────────────────▼───────────────────────────────────────────┐
│                       APPLICATION LAYER (Server)                            │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    PHP Backend Layer                                │   │
│  │                                                                      │   │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌─────────────────┐  │   │
│  │  │ User Module      │  │ Admin Module     │  │ Common Module   │  │   │
│  │  ├──────────────────┤  ├──────────────────┤  ├─────────────────┤  │   │
│  │  │ • Auth (Login)   │  │ • Auth Mgmt      │  │ • DB Connect    │  │   │
│  │  │ • Registration   │  │ • Dashboard      │  │ • Notifications │  │   │
│  │  │ • Profile Mgmt   │  │ • Reservations   │  │ • Payment Help. │  │   │
│  │  │ • Booking        │  │ • Guest Mgmt     │  │ • Wallet Logic  │  │   │
│  │  │ • Reservation    │  │ • Payment Mgmt   │  │ • Room Avail.   │  │   │
│  │  │   Details        │  │ • Status Updates │  │ • Logging       │  │   │
│  │  │ • Wallet/TopUp   │  │ • Cancellations  │  │                 │  │   │
│  │  │ • Notifications  │  │ • Reports        │  │                 │  │   │
│  │  │ • Settings       │  │                  │  │                 │  │   │
│  │  └──────────────────┘  └──────────────────┘  └─────────────────┘  │   │
│  │                                                                      │   │
│  └──────────────────────────────────────────────────────────────────┬─┘   │
│                                                                       │     │
│  ┌──────────────────────────────────────────────────────────────────┴─┐   │
│  │                   API Endpoints (AJAX)                             │   │
│  │                                                                     │   │
│  │  • /functions/get_wallet_balance.php (JSON)                       │   │
│  │  • /functions/get_wallet_reference.php (JSON)                     │   │
│  │  • /admin/ajax_reservations_table.php (JSON)                      │   │
│  │  • /admin/ajax_room_status_table.php (JSON)                       │   │
│  │  • /admin/process_update_status.php (Form)                        │   │
│  │  • /admin/process_cancellation.php (Form)                         │   │
│  │  • /admin/dashboard_details.php (JSON)                            │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
└───────────────────────────────────────┬───────────────────────────────────┘
                                        │ SQL Queries
                                        │ (MySQLi Connection)
                                        │
┌───────────────────────────────────────▼───────────────────────────────────┐
│                         DATA LAYER (Database)                             │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │          MySQL Database: db_mfsuite_reservation                      │ │
│  │                                                                        │ │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │ │
│  │  │ Guest Tables    │  │ Booking Tables  │  │ Payment Tables      │  │ │
│  │  ├─────────────────┤  ├─────────────────┤  ├─────────────────────┤  │ │
│  │  │ • tbl_guest     │  │ • tbl_room      │  │ • tbl_payment       │  │ │
│  │  │ • tbl_admin     │  │ • tbl_room_type │  │ • tbl_payment_types │  │ │
│  │  │ • tbl_wallet    │  │ • tbl_amenities │  │ • wallet_           │  │ │
│  │  │   _transactions │  │                 │  │   transactions      │  │ │
│  │  │                 │  │                 │  │                     │  │ │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────────┘  │ │
│  │                                                                        │ │
│  │  ┌──────────────────────────────┐  ┌─────────────────────────────┐  │ │
│  │  │ Reservation Tables           │  │ Service/Notification Tables │  │ │
│  │  ├──────────────────────────────┤  ├─────────────────────────────┤  │ │
│  │  │ • tbl_reservation            │  │ • tbl_services              │  │ │
│  │  │ • tbl_cancellation_requests  │  │ • reservation_services      │  │ │
│  │  │ • tbl_deleted_reservations   │  │ • tbl_notifications         │  │ │
│  │  │                              │  │                             │  │ │
│  │  └──────────────────────────────┘  └─────────────────────────────┘  │ │
│  │                                                                        │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Detailed Component Description

### **PRESENTATION LAYER**

#### **A. Guest Portal** (`/pages/`)
- **Booking System**: Room selection, date picker, guest count selection
- **Reservation Management**: View, modify, cancel reservations
- **Wallet System**: Top-up funds, view balance, transaction history
- **Notifications**: Real-time alerts for booking status, cancellations
- **User Profile**: Personal info, payment methods (GCash, Bank, PayPal, Credit Card)
- **Authentication**: Register, login, password reset, logout

**Key Files**:
- `booking_form.php` - Booking interface
- `reservations.php` - Reservation list
- `reservation_details.php` - Booking details
- `process_topup.php` - Wallet top-up processing
- `process_reservation.php` - Booking submission

---

#### **B. Admin Portal** (`/admin/`)
- **Dashboard**: Revenue, booking stats, guest analytics
- **Reservation Management**: Approve, deny, cancel bookings
- **Guest Management**: View guest profiles, activity
- **Payment Tracking**: Transaction history, payment methods
- **Room Status**: Occupancy, maintenance tracking
- **Amenities Management**: Room types, services

**Key Files**:
- `dashboard.php` - Admin dashboard
- `reservations.php` - Reservation management
- `process_update_status.php` - Status updates
- `admin_wallet_transactions.php` - Wallet monitoring

---

#### **C. Public Website** (`/`)
- **Landing Page**: Hero section, room showcase
- **Room Gallery**: Room types, pricing, amenities
- **Services Display**: Hotel services overview
- **About Section**: Hotel information
- **Navigation**: Responsive menu

**Key Files**:
- `index.php` - Landing page
- `rooms.php` - Room catalog

---

### **APPLICATION LAYER**

#### **A. User Module** (`/pages/`)
**Functionality**:
1. **Authentication**
   - Registration with validation
   - Login/logout with session management
   - Password reset via email

2. **Booking Management**
   - Room availability checking
   - Dynamic pricing calculation (nightly_rate × number_of_nights)
   - Multi-payment method support
   - Reference number generation
   - Booking confirmation

3. **Wallet System**
   - Balance tracking per guest
   - Top-up processing
   - Transaction logging
   - Sufficient funds validation

4. **Reservation Lifecycle**
   - Create reservation
   - View details
   - Cancel with refund
   - Receive status notifications

---

#### **B. Admin Module** (`/admin/`)
**Functionality**:
1. **Authentication**
   - Admin login
   - Role-based access control

2. **Reservation Management**
   - View all reservations (pending, approved, completed)
   - Approve/deny reservations
   - Automatic room assignment
   - Status updates

3. **Payment Processing**
   - Approve cash payments
   - Verify transaction references
   - Process refunds
   - Credit guest wallet on refund

4. **Guest Management**
   - View guest profiles
   - Monitor activity
   - Support

5. **Reporting**
   - Revenue reports
   - Booking analytics
   - Guest statistics

---

#### **C. Common Functions** (`/functions/`)

| File | Purpose |
|------|---------|
| `db_connect.php` | Database connection (MySQLi) |
| `bookings.php` | Booking logic, wallet deduction |
| `check_room_availability.php` | Room availability queries |
| `get_wallet_balance.php` | JSON API for wallet balance |
| `get_wallet_reference.php` | Fetch payment reference number |
| `payment_helpers.php` | Auto-approve paid reservations |
| `notify.php` | Create guest notifications |

---

### **DATA LAYER**

#### **Database Schema Overview**

**Guest & Authentication**:
```
tbl_guest
├── guest_id (PK)
├── first_name, last_name
├── user_email, phone_number
├── wallet_balance
├── wallet_id
└── payment_accounts (bank, paypal, gcash, credit_card)

tbl_admin
├── admin_id (PK)
├── admin_username, password
└── role
```

**Rooms & Types**:
```
tbl_room_type
├── room_type_id (PK)
├── type_name
├── description
└── nightly_rate

tbl_room
├── room_id (PK)
├── room_type_id (FK)
├── room_number
└── status (Available, Occupied, Maintenance)

tbl_amenities
├── amenity_id (PK)
└── amenity_name
```

**Reservations**:
```
tbl_reservation
├── reservation_id (PK)
├── guest_id (FK)
├── room_id (FK)
├── check_in, check_out
├── status (pending, approved, completed, cancelled, denied)
├── payment_id (FK)
└── date_created

reservation_services
├── reservation_id (FK)
└── service_id (FK)
```

**Payments**:
```
tbl_payment
├── payment_id (PK)
├── amount
├── payment_method (Cash, GCash, Bank, PayPal, Credit Card, Wallet)
├── payment_status (Pending, Paid, Refunded)
├── reference_number
└── payment_created

tbl_payment_types
├── payment_type_id (PK)
└── payment_name

wallet_transactions
├── transaction_id (PK)
├── guest_id (FK)
├── amount, type (payment, topup, refund)
└── reference_number
```

**Other**:
```
tbl_services
├── service_id (PK)
└── service_name

tbl_cancellation_requests
├── request_id (PK)
├── reservation_id (FK)
└── status

tbl_notifications
├── notification_id (PK)
├── guest_id (FK)
├── message, type
└── is_read

tbl_deleted_reservations
├── reservation_id (PK)
└── deleted_data (JSON)
```

---

## 3. Payment Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                      PAYMENT PROCESSING FLOW                     │
└──────────────────────────────────────────────────────────────────┘

    User selects payment method
           │
           ▼
    ┌─────────────────────────────┐
    │ Payment Method Selected     │
    ├─────────────────────────────┤
    │ • Cash                      │
    │ • GCash                     │
    │ • Bank Transfer             │
    │ • PayPal                    │
    │ • Credit Card               │
    │ • Wallet                    │
    └────────┬────────────────────┘
             │
        ┌────┴─────────────────────────────────┐
        │                                       │
        ▼ (Cash)                               ▼ (Non-Cash)
    ┌─────────────┐                      ┌──────────────────┐
    │ Generate    │                      │ Display Payment  │
    │ Reference # │                      │ Details          │
    │ Show Modal  │                      │ (Account Info)   │
    └──────┬──────┘                      │ Generate Ref #   │
           │                             │ Await Payment    │
           │                             └────────┬─────────┘
           │                                      │
           └──────────────────┬───────────────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │ Submit Booking      │
                    │ Record Payment      │
                    │ Update Reservation  │
                    └──────────┬──────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼ (Wallet)             ▼ (Cash)              ▼ (Other)
    ┌─────────────┐       ┌────────────┐        ┌──────────────┐
    │ Deduct from │       │ Mark as    │        │ Mark as      │
    │ Wallet      │       │ 'Pending'  │        │ 'Pending'    │
    │ Create      │       │ Await Admin│        │ Await Admin  │
    │ Reservation │       │ Approval   │        │ Verification │
    │ (Auto-ok)   │       └────────────┘        │ & Approval   │
    └─────────────┘                             └──────────────┘
         │                      │                      │
         └──────────────────────┴──────────────────────┘
                                │
                                ▼
                    ┌─────────────────────┐
                    │ Admin Reviews       │
                    │ Approves/Denies     │
                    │ Assigns Room        │
                    │ Updates Status      │
                    └──────────┬──────────┘
                               │
                        ┌──────┴─────────┐
                        │                │
                    ▼ (Approved)      ▼ (Denied)
            ┌──────────────────┐  ┌─────────────┐
            │ Reservation Live │  │ Process     │
            │ Room Marked      │  │ Refund      │
            │ Occupied         │  │ Return $    │
            │ Send Notif.      │  │ to Wallet   │
            └──────────────────┘  └─────────────┘
```

---

## 4. User Workflow Diagram

### **Guest User Journey**

```
┌─────────────┐
│ Browse      │  ──→ View Rooms & Amenities
│ Website     │
└──────┬──────┘
       │
       ▼
┌──────────────────────┐
│ Register/Login       │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Browse Rooms         │ ──→ View Room Types & Pricing
│ (Booking Form)       │     Check Availability
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Select Dates         │ ──→ Check-in/Check-out
│ & Guests             │     Update Price
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Choose Payment       │ ──→ Select Method
│ Method               │     View Account Info
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Review & Submit      │ ──→ Confirm Details
│ Booking              │     Generate Reference
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Payment Processing   │ ──→ Wallet: Immediate Deduction
│                      │     Other: Reference Number Display
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Booking Pending      │ ──→ Await Admin Approval
│ (Notification)       │     For Cash/Manual Payment
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Booking Approved     │ ──→ Room Assigned
│ (Notification)       │     Status: Confirmed
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Check-in             │ ──→ Guest Arrives
│                      │     Room Status: Occupied
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Stay at Hotel        │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Check-out            │ ──→ Guest Leaves
│ Completes Booking    │     Room Status: Available
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Optional: Cancel     │ ──→ Request Cancellation
│ Before Check-in      │     Get Refund
└──────────────────────┘
```

---

## 5. Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Frontend** | HTML5, CSS3, JavaScript | UI & Interactivity |
| **Frontend Framework** | Bootstrap 5.3.0 | Responsive Design |
| **Icons** | Bootstrap Icons 1.10.5 | UI Elements |
| **Fonts** | Google Fonts (Poppins) | Typography |
| **Backend** | PHP 7+ | Server Logic |
| **Database** | MySQL 5.7+ | Data Storage |
| **Database Driver** | MySQLi | DB Connection |
| **Server** | Apache (XAMPP) | Web Server |
| **Session Management** | PHP Sessions | User Authentication |
| **API Communication** | AJAX/Fetch API | Client-Server |
| **Data Format** | JSON | API Responses |

---

## 6. Key Integrations & External Entities

### **Payment Methods Integration**
- **Cash**: Manual verification by admin
- **GCash**: Account number provided by user
- **Bank Transfer**: Account number provided by user
- **PayPal**: Email provided by user
- **Credit Card**: Card number provided by user
- **Wallet**: Internal system (Funds pre-loaded)

### **Email System**
- Password reset notifications
- Booking confirmation emails
- Cancellation notifications
- Status update alerts

### **Image Storage**
- **User Avatars**: UI Avatars API (`ui-avatars.com`)
- **Room Images**: Local storage (`/assets/rooms/`)
- **Service Images**: Local storage (`/assets/services/`)
- **Profile Pictures**: Local upload (`/uploads/profile_pictures/`)

---

## 7. Security Considerations

```
┌────────────────────────────────────────────────────────────┐
│              SECURITY ARCHITECTURE                          │
│                                                              │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ Authentication & Authorization                       │  │
│ ├──────────────────────────────────────────────────────┤  │
│ │ • Session-based authentication                       │  │
│ │ • Role-based access control (Guest vs Admin)         │  │
│ │ • Password hashing (recommended: bcrypt)             │  │
│ │ • Login page with credential validation              │  │
│ └──────────────────────────────────────────────────────┘  │
│                                                              │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ Input Validation & Sanitization                      │  │
│ ├──────────────────────────────────────────────────────┤  │
│ │ • htmlspecialchars() for XSS prevention              │  │
│ │ • Prepared statements for SQL injection prevention   │  │
│ │ • Type validation for numeric inputs                 │  │
│ └──────────────────────────────────────────────────────┘  │
│                                                              │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ Data Protection                                      │  │
│ ├──────────────────────────────────────────────────────┤  │
│ │ • Wallet transactions logged separately              │  │
│ │ • Payment info stored encrypted (recommended)         │  │
│ │ • Guest data isolated by session                     │  │
│ │ • Sensitive data not exposed in URLs                 │  │
│ └──────────────────────────────────────────────────────┘  │
│                                                              │
└────────────────────────────────────────────────────────────┘
```

---

## 8. Database Relationships Diagram

```
                    ┌─────────────────┐
                    │   tbl_guest     │
                    ├─────────────────┤
                    │ PK: guest_id    │
                    │ wallet_balance  │
                    │ wallet_id       │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
         ▼                   ▼                   ▼
    ┌──────────────┐  ┌─────────────────┐  ┌──────────────┐
    │tbl_          │  │tbl_             │  │wallet_       │
    │reservation   │  │cancellation_    │  │transactions  │
    ├──────────────┤  │requests         │  ├──────────────┤
    │PK: res_id    │  ├─────────────────┤  │PK: trans_id  │
    │FK: guest_id  │  │FK: res_id       │  │FK: guest_id  │
    │FK: room_id   │  └─────────────────┘  └──────────────┘
    │FK: payment_id│
    └──────┬───────┘
           │
    ┌──────┼──────────────┐
    │      │              │
    ▼      ▼              ▼
┌────┐ ┌─────────────┐ ┌──────────────┐
│tbl_│ │tbl_         │ │tbl_          │
│room│ │payment      │ │notifications │
├────┤ ├─────────────┤ ├──────────────┤
│PK: │ │PK: pay_id   │ │PK: notif_id  │
│r_id│ │FK: pay_type │ │FK: guest_id  │
└────┘ │payment_stat │ └──────────────┘
   │   │reference #  │
   │   └─────────────┘
   │
   ▼
┌──────────────────┐
│tbl_room_type     │
├──────────────────┤
│PK: room_type_id  │
│type_name         │
│nightly_rate      │
└──────────────────┘
```

---

## 9. API Endpoints Summary

### **User-Facing APIs** (GET/POST)

| Endpoint | Method | Purpose | Response |
|----------|--------|---------|----------|
| `/functions/get_wallet_balance.php` | GET | Get current wallet balance | JSON |
| `/functions/get_wallet_reference.php` | GET | Get payment reference | JSON |
| `/pages/process_reservation.php` | POST | Submit booking | Redirect |
| `/pages/process_topup.php` | POST | Process wallet top-up | Redirect |
| `/pages/process_login.php` | POST | User login | Redirect |
| `/pages/process_register.php` | POST | User registration | Redirect |

### **Admin APIs**

| Endpoint | Method | Purpose | Response |
|----------|--------|---------|----------|
| `/admin/ajax_reservations_table.php` | GET | Fetch reservations | HTML Table |
| `/admin/ajax_room_status_table.php` | GET | Fetch room status | HTML Table |
| `/admin/dashboard_details.php` | GET | Dashboard metrics | JSON/HTML |
| `/admin/process_update_status.php` | POST | Update reservation | Redirect |
| `/admin/process_cancellation.php` | POST | Handle cancellation | Redirect |
| `/admin/process_admin_register.php` | POST | Admin registration | Redirect |

---

## 10. System Features Summary

### **Core Features**

✅ **User Management**
- Registration with validation
- Secure login/logout
- Profile management
- Password reset

✅ **Room Booking**
- Real-time availability checking
- Dynamic pricing calculation
- Multi-date selection
- Room type filtering

✅ **Payment Processing**
- Multiple payment methods
- Wallet system with top-ups
- Reference number generation
- Transaction logging

✅ **Reservation Management**
- Create, view, modify, cancel reservations
- Status tracking (pending, approved, completed, cancelled)
- Automatic room assignment
- Refund processing

✅ **Admin Features**
- Dashboard with analytics
- Reservation approval workflow
- Payment verification
- Guest management
- Room occupancy tracking

✅ **Notifications**
- Real-time alerts
- Booking status updates
- Cancellation notifications
- Unread count tracking

---

## 11. Deployment Architecture

```
┌──────────────────────────────────┐
│     Production Environment        │
├──────────────────────────────────┤
│                                  │
│  ┌────────────────────────────┐  │
│  │ Web Server (Apache)        │  │
│  │ - Document Root: /htdocs/  │  │
│  │ - Port: 80/443             │  │
│  └────────────┬───────────────┘  │
│               │                  │
│  ┌────────────▼───────────────┐  │
│  │ PHP Engine (7.x+)          │  │
│  │ - File System: /xampp/      │  │
│  │ - Session Storage           │  │
│  └────────────┬───────────────┘  │
│               │                  │
│  ┌────────────▼───────────────┐  │
│  │ MySQL Database             │  │
│  │ - Host: localhost           │  │
│  │ - Database: db_mfsuite_     │  │
│  │   reservation               │  │
│  │ - User: root (admin)        │  │
│  └────────────────────────────┘  │
│                                  │
│  ┌────────────────────────────┐  │
│  │ File Storage               │  │
│  │ - /uploads/profile_pics/   │  │
│  │ - /assets/rooms/           │  │
│  │ - /assets/services/        │  │
│  │ - /logs/                   │  │
│  └────────────────────────────┘  │
│                                  │
└──────────────────────────────────┘
```

---

## 12. Performance Considerations

1. **Database Indexing**: Add indexes on frequently queried columns (guest_id, reservation_id, status)
2. **Caching**: Implement caching for room availability checks
3. **Session Management**: Clean up expired sessions regularly
4. **Image Optimization**: Compress room and service images
5. **Query Optimization**: Use LIMIT for table displays, pagination for large datasets
6. **AJAX Caching**: Prevent browser caching for dynamic data

---

## 13. Future Enhancement Opportunities

- [ ] Mobile application (React Native/Flutter)
- [ ] Real-time booking notifications (WebSockets/Socket.io)
- [ ] AI-powered room recommendations
- [ ] Integration with third-party payment gateways (Stripe, PayMongo)
- [ ] Email notification system
- [ ] SMS notifications
- [ ] Advanced analytics and reporting
- [ ] Loyalty program system
- [ ] Multi-language support
- [ ] API for third-party integrations

---

---

## 14. Data Flow Diagram (DFD) - Level 0 (Context Diagram)

### Overview
The DFD Level 0 shows the system boundary, external entities, major data stores, and key data flows entering and leaving the MFsuite Hotel Reservation System.

```
                          ┌─────────────────────────────────┐
                          │     Email Service               │
                          │  (Password Reset, Confirmations)│
                          └────────────┬────────────────────┘
                                       │
                                       │ password_reset_link
                                       │ confirmation_email
                                       │
                   ┌───────────────────┼───────────────────┐
                   │                   │                   │
                   ▼                   │                   ▼
        ┌──────────────────┐           │          ┌──────────────────┐
        │                  │           │          │                  │
        │  GUEST USERS     │           │          │  ADMIN USERS     │
        │  (External)      │           │          │  (External)      │
        │                  │           │          │                  │
        │ • Browse Rooms   │           │          │ • Manage System  │
        │ • Make Bookings  │           │          │ • Process Pays   │
        │ • Manage Wallet  │           │          │ • Approve Books  │
        │ • View Profile   │           │          │ • Generate Rpts  │
        │                  │           │          │                  │
        └────────┬─────────┘           │          └────┬─────────────┘
                 │                     │              │
         ┌───────┴──────────────┬──────┴──────┬────────┴───────┐
         │                      │             │                │
    ┌────▼─────────────┐    ┌──▼──────┐  ┌──▼──────┐    ┌────▼─────────────┐
    │ Booking Requests │    │ Payment │  │Profile  │    │Reservation       │
    │ Reservation Data │    │ Details │  │ Updates │    │Status Updates    │
    │ Wallet Top-ups   │    │ Ref #   │  │Payment  │    │Reports           │
    │ Login/Register   │    │         │  │Methods  │    │Approvals/Denials │
    │                  │    │         │  │         │    │                  │
    └────┬─────────────┘    └──┬──────┘  └──┬──────┘    └────┬─────────────┘
         │                     │           │                │
         │                     │           │                │
         ├─────────────────────┼───────────┼────────────────┤
         │                     │           │                │
         │ ┌──────────────────────────────────────────────────────────┐
         │ │                                                          │
         │ │   ╔════════════════════════════════════════════════╗    │
         │ │   ║   MFsuite Hotel Reservation System            ║    │
         │ │   ║   (System Boundary - DFD 0)                   ║    │
         │ │   ╚════════════════════════════════════════════════╝    │
         │ │                                                          │
         │ │     ┌──────────────────────────────────────────┐        │
         │ │     │  PHP Application Engine                 │        │
         │ │     │  (Booking, Payments, Notifications)     │        │
         │ │     └──────────────────────────────────────────┘        │
         │ │                      │                                   │
         │ │     ┌────────────────▼────────────────┐                │
         │ │     │   Data Processing & Validation  │                │
         │ │     │   - Check Availability         │                │
         │ │     │   - Calculate Prices           │                │
         │ │     │   - Process Payments           │                │
         │ │     │   - Update Status              │                │
         │ │     │   - Log Transactions           │                │
         │ │     └────────────────┬────────────────┘                │
         │ │                      │                                   │
         │ │     ┌────────────────▼───────────────────────────────┐ │
         │ │     │   Data Stores (Database Layer)                │ │
         │ │     │   ┌──────────────────────────────────────┐    │ │
         │ │     │   │ D1: Guest Profiles                  │    │ │
         │ │     │   │ D2: Rooms & Availability            │    │ │
         │ │     │   │ D3: Reservations & Bookings         │    │ │
         │ │     │   │ D4: Payments & Transactions         │    │ │
         │ │     │   │ D5: Wallet Accounts & History       │    │ │
         │ │     │   │ D6: Admin Users & Settings          │    │ │
         │ │     │   │ D7: Notifications                   │    │ │
         │ │     │   │ D8: Services & Amenities            │    │ │
         │ │     │   └──────────────────────────────────────┘    │ │
         │ │     └────────────────────────────────────────────────┘ │
         │ │                                                          │
         │ └──────────────────────────────────────────────────────────┘
         │                     │           │                │
         └─────────────────────┼───────────┼────────────────┘
                               │           │
                        ┌──────▼──────┐┌──▼──────┐
                        │Booking      ││Response │
                        │Confirmation ││Data     │
                        │Status       ││(JSON)   │
                        │Availability ││         │
                        └─────────────┘└─────────┘
```

---

### DFD Level 0 - Component Description

#### **External Entities** (Outside System Boundary)

1. **Guest Users**
   - **Inputs**: Booking requests, registration data, payment info, profile updates, wallet top-ups
   - **Outputs**: Booking confirmations, reservation details, wallet balance, notifications, reports

2. **Admin Users**
   - **Inputs**: Status update commands, approval/denial decisions, report requests
   - **Outputs**: System status, booking reports, payment reports, guest data

3. **Email Service** (External)
   - **Inputs**: Email requests (password reset, confirmation)
   - **Outputs**: Emails sent to users

#### **Data Stores** (Inside System Boundary)

| Store | Purpose | Data Held |
|-------|---------|-----------|
| **D1: Guest Profiles** | User account information | Guest ID, Name, Email, Phone, Payment Methods |
| **D2: Rooms & Availability** | Room inventory | Room ID, Type, Status, Nightly Rate, Amenities |
| **D3: Reservations & Bookings** | Booking records | Reservation ID, Dates, Status, Room Assignment |
| **D4: Payments & Transactions** | Payment records | Payment ID, Amount, Method, Status, Reference |
| **D5: Wallet Accounts & History** | Guest wallet system | Wallet ID, Balance, Transaction History |
| **D6: Admin Users & Settings** | System administration | Admin ID, Credentials, Permissions, Settings |
| **D7: Notifications** | User alerts | Notification ID, Type, Message, Status |
| **D8: Services & Amenities** | Hotel services | Service ID, Name, Description, Pricing |

#### **Major Data Flows**

```
Input Flows (Guest Users):
  ▼ Booking requests (room_id, check_in, check_out, guest_count)
  ▼ Payment information (payment_method, reference_number, amount)
  ▼ Wallet top-ups (amount, payment_method, reference)
  ▼ Login/registration credentials (email, password)
  ▼ Profile updates (personal info, payment methods)

Output Flows (Guest Users):
  ◄ Booking confirmation (reservation_id, status, room_assigned)
  ◄ Availability data (available_rooms, pricing, dates)
  ◄ Wallet balance (current_balance, transaction_history)
  ◄ Notifications (booking_status, cancellations, alerts)
  ◄ Reservation details (booking_info, payment_status)

Input Flows (Admin Users):
  ▼ Approval/denial (reservation_id, action, reason)
  ▼ Payment approval (payment_id, status, notes)
  ▼ Room status updates (room_id, new_status)
  ▼ Report requests (report_type, date_range, filters)

Output Flows (Admin Users):
  ◄ System dashboard (metrics, stats, pending_items)
  ◄ Reservation reports (list, filtering, sorting)
  ◄ Payment reports (transactions, methods, status)
  ◄ Guest data (profiles, activity, contact_info)
```

---

## 15. Data Flow Diagram (DFD) - Level 1 (Process Decomposition)

### Overview
DFD Level 1 decomposes the system into major processes showing how data flows between them and the data stores.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    MFsuite DFD Level 1 - Process Decomposition               │
└──────────────────────────────────────────────────────────────────────────────┘

EXTERNAL ENTITIES:
┌──────────────┐                                                  ┌──────────────┐
│ Guest Users  │                                                  │ Admin Users  │
└──────────────┘                                                  └──────────────┘
        │                                                                 │
        │ registration_data                                              │
        │ booking_request                                                │
        │ payment_info                                                   │
        │ wallet_topup                                                   │
        │ profile_update                                                 │
        └─┐                                                              │
          │                                    ┌─────────────────┐      │
          └──────────────────────────────┬────►│  P1: USER AUTH  │◄─────┘
                                        │     │  & PROFILE MGT  │
                                        │     └────────┬────────┘
                                        │              │
                        ┌───────────────┘              │ user_session
                        │                             │ profile_data
          ┌─────────────┴──────────┐                  │
          │                        ▼                  │
          │     ┌──────────────────────────────┐     ▼
          │     │  P2: ROOM AVAILABILITY       │  ┌─────────────┐
          │     │  & BOOKING PROCESSING        │  │ D1: Guest   │
          │     └──────────────────────────────┘  │ Profiles    │
          │              │                        └─────────────┘
          │        room_availability              
          │        nightly_rates                 ┌─────────────┐
          │        booking_confirmation          │ D2: Rooms & │
          │              │                        │ Availability│
          │              │                        └─────────────┘
          │              │
          │              ▼
          │     ┌──────────────────────────────┐
          ├────►│  P3: PAYMENT PROCESSING      │
                │  & WALLET MANAGEMENT         │
                └──────────────────────────────┘
                         │
            ┌────────────┼────────────┐
            │            │            │
      payment_status  wallet_deduction
      transaction_log  balance_update
            │            │            │
            │            │            ▼
            │            │     ┌─────────────┐
            │            │     │ D4: Payment │
            │            │     │ Transactions│
            │            │     └─────────────┘
            │            │
            │            ▼
            │     ┌─────────────┐
            │     │ D5: Wallet  │
            │     │ Accounts    │
            │     └─────────────┘
            │
            ▼
   ┌──────────────────────────────┐
   │  P4: RESERVATION STATUS      │
   │  & APPROVAL MANAGEMENT       │
   └──────────────────────────────┘
            │
    ┌───────┴────────┐
    │                │
  status_update   room_assignment
  approval_data   notification_trigger
    │                │
    │                ▼
    │        ┌─────────────┐
    │        │ D3: Reserv. │
    │        │ Bookings    │
    │        └─────────────┘
    │
    ▼
┌──────────────────────────────┐
│  P5: NOTIFICATION SYSTEM     │
└──────────────────────────────┘
         │
     notification_message
     delivery_status
         │
         ▼
    ┌─────────────┐
    │ D7: Notif.  │
    │ Queue       │
    └─────────────┘

┌──────────────────────────────┐
│  P6: REPORTING & ANALYTICS   │
└──────────────────────────────┘
         │
     report_data
     metrics
         │
         ▼ (No new data store - reads from D3, D4, D5)
```

---

## 16. Detailed Data Flow Sequences

### **Sequence 1: Guest Booking Flow**

```
Guest User                Application              Data Stores
    │                        │                          │
    │─── Browse Rooms ──────►│                          │
    │                        │──────► Query D2 ────────►│
    │◄─ Room List (JSON) ────│◄─────────────────────────│
    │                        │                          │
    │─ Enter Booking Details─►│                          │
    │ (check_in, check_out)   │──► Check Availability  │
    │                        │    in D2, D3 ───────────►│
    │◄─ Available Rooms ─────│◄─────────────────────────│
    │ & Pricing              │                          │
    │                        │                          │
    │─ Select Payment Method ►│                          │
    │ & Confirm Booking      │──► Validate Payment ────►│ D1
    │                        │    Generate Reference   │
    │◄─ Reference Number ────│◄──────────────────────────│
    │                        │                          │
    │─ Submit Payment ───────►│                          │
    │ (Payment Method)        │──► Process Payment ─────►│ D4, D5
    │                        │    Deduct Wallet (if)  │
    │◄─ Confirmation ────────│◄──────────────────────────│
    │ (Reservation ID)        │                          │
    │                        │──► Create Reservation ──►│ D3
    │                        │    Update Status ─────────►│
    │◄─ Success Notification ─│◄──────────────────────────│
    │                        │    Send Notification ───►│ D7
    │                        │                          │
```

### **Sequence 2: Admin Approval Flow**

```
Admin User                 Application              Data Stores
    │                        │                          │
    │─ Login ───────────────►│                          │
    │                        │──► Verify Admin ───────►│ D1, D6
    │◄─ Dashboard ───────────│◄──────────────────────────│
    │ (Pending Reservations) │    (Fetch pending) ────►│ D3
    │                        │                          │
    │─ Select Reservation ──►│                          │
    │ to Review              │──► Get Details ────────►│ D3, D1
    │◄─ Booking Details ─────│◄──────────────────────────│
    │ (Guest, Room, Payment) │                          │
    │                        │                          │
    │─ Click Approve ───────►│                          │
    │                        │──► Update Status ──────►│ D3
    │                        │    Assign Room ────────►│ D2
    │                        │    Create Notification ►│ D7
    │◄─ Success Message ─────│◄──────────────────────────│
    │                        │    Notify Guest ─────────►│
    │                        │                          │
```

### **Sequence 3: Payment Processing Flow**

```
Guest                  Payment Module          Wallet Module         Databases
  │                        │                      │                      │
  │─ Submit Booking ──────►│                      │                      │
  │ (with payment method)  │                      │                      │
  │                        │───► Validate ────────────────────────────────►│
  │                        │     Payment D1 D4                            │
  │                        │                      │                      │
  │◄─ Reference # ──────── │◄─ Generate Ref ──────┴──────────────────────│
  │                        │                      │                      │
  │                        │◄─ Check if Wallet ── │                      │
  │                        │   or External ──────►│ Query D5 ───────────►│
  │                        │                      │                      │
  │                        │─ If Wallet: ────────►│─ Deduct Amount ────► │ D5
  │                        │   Deduct Funds       │                      │
  │                        │                      │                      │
  │                        │─ If External: ──────►│─ Create Log ───────► │ D4
  │                        │   Record Payment     │                      │
  │                        │   (awaiting approval)│                      │
  │                        │                      │                      │
  │◄─ Payment Status ───── │◄─ Confirmation ───── │◄──────────────────────│
  │ (Pending/Completed)    │                      │                      │
  │                        │                      │                      │
```

---

## 17. Data Store Schema (DFD Reference)

```
┌─────────────────────────────────────────────────────────────────┐
│                    DATA STORES REFERENCE                         │
└─────────────────────────────────────────────────────────────────┘

D1: Guest Profiles
├── guest_id (PK)
├── first_name, last_name
├── user_email
├── phone_number
├── password_hash
├── wallet_id
├── wallet_balance
├── bank_account_number
├── paypal_email
├── gcash_number
├── credit_card_number
└── created_at, updated_at

D2: Rooms & Availability
├── room_id (PK)
├── room_type_id (FK)
├── room_number
├── status (Available, Occupied, Maintenance)
├── nightly_rate
├── description
├── type_name
└── amenities (JSON array)

D3: Reservations & Bookings
├── reservation_id (PK)
├── guest_id (FK)
├── room_id (FK)
├── check_in_date
├── check_out_date
├── number_of_nights
├── status (pending, approved, completed, cancelled, denied)
├── payment_id (FK)
├── total_amount
└── date_created, date_updated

D4: Payments & Transactions
├── payment_id (PK)
├── reservation_id (FK)
├── amount
├── payment_method
├── payment_status (Pending, Paid, Refunded)
├── reference_number
├── payment_type_id (FK)
└── payment_created, payment_updated

D5: Wallet Accounts & History
├── wallet_id (PK)
├── guest_id (FK)
├── current_balance
└── wallet_transactions
    ├── transaction_id (PK)
    ├── wallet_id (FK)
    ├── amount
    ├── type (payment, topup, refund)
    ├── description
    ├── reference_number
    └── created_at

D6: Admin Users & Settings
├── admin_id (PK)
├── admin_username
├── password_hash
├── role
├── email
├── permissions (JSON)
└── created_at

D7: Notifications
├── notification_id (PK)
├── guest_id (FK)
├── type (booking, cancellation, payment, alert)
├── message
├── is_read (0, 1)
└── created_at

D8: Services & Amenities
├── service_id (PK)
├── service_name
├── description
├── base_price (optional)
└── room_services (junction table)
    ├── service_id (FK)
    └── room_type_id (FK)
```

---

## 18. Critical Data Flow Paths

### **Path 1: Booking Request to Confirmation**
```
Guest → Registration/Auth → Room Selection → Availability Check → Payment → 
Reservation Create → Notification → Guest Confirmation
```

### **Path 2: Payment Approval by Admin**
```
Guest Payment Submit → Record in D4 → Admin Review → Approval Decision → 
Update Reservation Status → Update Room Status → Notify Guest
```

### **Path 3: Wallet Top-up and Deduction**
```
Guest Top-up Request → Payment Processing → Wallet Credit → Transaction Log
                                           ↓
                          Guest Book Room (Wallet Method)
                                           ↓
                          Validate Balance → Deduct Amount → Create Transaction Log
```

### **Path 4: Cancellation and Refund**
```
Guest Cancel Request → Admin Review → Approve/Deny → 
If Approved: Calculate Refund → Credit Wallet → Update D5 → Notify Guest
If Denied: Notify Guest
```

---

## 19. Data Validation & Constraints (DFD Considerations)

```
Process: P1 (User Auth & Profile Management)
├── Validate email format
├── Check email uniqueness (D1)
├── Validate password strength
├── Check guest exists before profile update
└── Sanitize user inputs

Process: P2 (Room Availability & Booking)
├── Check room availability (D2, D3)
├── Validate date range (check_out > check_in)
├── Calculate nightly_rate × number_of_nights
├── Verify room_type exists
└── Check for double bookings

Process: P3 (Payment & Wallet)
├── Validate payment method exists (D6)
├── Check wallet balance if payment_method = 'wallet' (D5)
├── Validate amount > 0
├── Verify reference number format if required
├── Prevent duplicate payments
└── Log all transactions for audit

Process: P4 (Reservation Status)
├── Validate status transition (pending → approved → completed)
├── Check room availability before assignment
├── Update room status to 'Occupied'
├── Create notification if status changes
└── Prevent status changes for completed/cancelled

Process: P5 (Notifications)
├── Check notification type is valid
├── Verify guest_id exists (D1)
├── Mark notifications as read only by guest
└── Fetch unread count for display

Process: P6 (Reporting)
├── Apply date filters
├── Aggregate data from D3, D4, D5
├── Calculate metrics (total_revenue, booking_count)
└── Format for display/export
```

---

## 20. System Boundary & Context

```
┌───────────────────────────────────────────────────────────────┐
│                   EXTERNAL SYSTEMS                            │
├───────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌────────────────────────┐    ┌─────────────────────────┐   │
│  │ Email System           │    │ Payment Gateway         │   │
│  │ (Future Integration)   │    │ (Future: Stripe, etc)   │   │
│  │                        │    │                         │   │
│  │ • Password reset       │    │ • Credit card process   │   │
│  │ • Confirmations        │    │ • Online payment verify │   │
│  │ • Notifications        │    │ • Refund processing     │   │
│  └────────────────────────┘    └─────────────────────────┘   │
│                                                                │
└───────────────────────────────────────────────────────────────┘
                             ▲
                             │
                  ┌──────────┼──────────┐
                  │          │          │
                  │          │          │
          ┌───────┴──┐  ┌────┴────┐  ┌─┴────────┐
          │ Guests   │  │ Admins   │  │System    │
          │          │  │          │  │          │
          └───────┬──┘  └────┬─────┘  └─┬────────┘
                  │          │          │
                  └──────────┼──────────┘
                             │
             ┌───────────────┼───────────────┐
             │               │               │
        ┌────▼────┐      ┌───▼────┐     ┌──▼────┐
        │ Web UI  │      │ Mobile │     │ API   │
        │(Browser)│      │(Future)│     │(Future)
        └────┬────┘      └───┬────┘     └──┬────┘
             │               │             │
             └───────────────┼─────────────┘
                             │
             ╔═══════════════╩════════════════╗
             ║  MFSuite Reservation System    ║
             ║  (System Under Study - DFD 0) ║
             ╚═══════════════╬════════════════╝
                             │
             ┌───────────────┼───────────────┐
             │               │               │
         ┌───▼──────┐    ┌───▼──────┐  ┌──▼────────┐
         │ Session  │    │ Business │  │ Database  │
         │ Manager  │    │ Logic    │  │ (MySQL)   │
         └──────────┘    └──────────┘  └───────────┘
```

---

## 21. Use Case Diagram

### Overview
This section lists the primary actors, major system use cases, and their relationships (include, extend, generalization). The Use Case Diagram captures the functional requirements at a high level for the MFsuite Hotel Reservation System.

```
                                    +-----------------------+
                                    |   Payment Provider    |  (External)
                                    +-----------+-----------+
                                                    |
                                                    | Verifies / Confirms Payment
                                                    |
          +---------------+    +--------v---------+    +----------------+
          |  Guest User   |    |  MFsuite System  |    |   Admin User   |
          |  (Primary)    |    |  (System)        |    |  (Primary)     |
          +---------------+    +------------------+    +----------------+
                 |   ^                 |   ^   |                 |  ^
                 |   |                 |   |   |                 |  |
  Browse ----+   |                 |   |   +-- Manage Rooms -+  |
  Rooms           |                 |   |                     |
  Register/Login  |                 |   +-- View Reports -----+  Approve
  Manage Profile  |                 |   |                     |  / Assign
  Check Avail.    |                 |   |                     |  Reservations
  Make Reservation +-----------------> Make Reservation ----->+  
         |           |   <<include>>   |   (includes: Check     |
         |           |   Check Avail   |    Availability,        |
         |           |                 |    Calculate Price)    |
         |           |                 |                        |
         |           +-----------------> Make Payment ---------->+  <<include>>
         |               <<include>>   |   (includes: Generate  |  Verify Payment
         |                              |    Reference)          |
         |                              |                        |
         |                              +-- Manage Wallet ------>|
         |                                 (Top-up, View, Deduct) |
         |                                                      |
         +-- View Reservation <---------------------------------+
         |                                                      |
         +-- Cancel Reservation --<<extend>>--> Request Refund   |
                                             (extends: Calculate Refund)  |

Notes on relationships:
- "Make Reservation" <<include>> "Check Availability" and <<include>> "Calculate Price" — these actions are required when booking.
- "Make Payment" <<include>> "Generate Reference" — reference creation is part of non-wallet payments.
- "Cancel Reservation" <<extend>> "Request Refund" — refunds are conditional, triggered when cancellation is approved.
- Admin User has privileged use cases: "Approve Reservation", "Assign Room", "Manage Rooms", "Manage Payments", "View Reports".
- Payment Provider is an external actor that verifies and confirms payments; it is not part of the system boundary.

### Primary Actors
- Guest User: Browses rooms, creates reservations, manages profile and wallet, requests cancellations.
- Admin User: Reviews/approves reservations, assigns rooms, manages payments and room inventory, views reports.
- Payment Provider (External): Confirms external payments (bank, GCash, PayPal, credit card).
- Email Service (External): Sends password resets, booking confirmations, notifications.

### Major Use Cases (high level)
- Register / Login / Logout
- Manage Profile (update contact, payment account info)
- Browse Rooms & Check Availability
- Make Reservation (includes availability check, price calculation)
- Make Payment (includes generate reference; wallet or external)
- View Reservation Details
- Cancel Reservation (may extend to Request Refund)
- Manage Wallet (Top-up, View Balance, Transactions)
- Notifications (send booking/payment/status updates)
- Admin Approve/Assign Reservation
- Manage Rooms & Services
- Manage Payments & Refunds
- Generate Reports & Dashboard

### Generalization and Notes
- Admin User is specialized from a generic Staff actor (if needed) — not shown separately here. If future roles (e.g., Front Desk) are added, they can be modeled as specializations of Admin.
- Some use cases can be further decomposed (e.g., "Make Payment" -> "Wallet Payment" vs "External Payment") for a Level 2 diagram.

---

**Document Version**: 1.2  
**Last Updated**: November 27, 2025  
**System**: MFsuite Hotel Reservation System  
**Architecture Level**: 0-2 (Context, Process, Use Cases)

## 22. Activity Diagram — Payment Processing

### Purpose
This activity diagram shows the flow for processing a booking payment. It includes decision points (payment method, wallet sufficiency, payment confirmation), activities, start/end nodes, and parallel actions (logging and notification).

```
Start
  |
  v
[User submits booking + payment selection]
  |
  v
{Decision: Payment method == 'Wallet'?}
  |-- Yes --> [Check wallet balance]
  |             |
  |             v
  |         {Decision: Balance >= amount?}
  |             |-- No --> [Show insufficient funds message]
  |             |            |
  |             |            v
  |             |        [Offer Top-up / Abort] --> End (if aborted)
  |             |
  |             |-- Yes --> [Deduct wallet amount]
  |                         |
  |                         v
  |                     [Record payment as 'Paid']
  |                         |
  |                         v
  |                === Fork (parallel) ===
  |                |                   |
  |                v                   v
  |         [Log transaction]     [Send confirmation email / notif]
  |                |                   |
  |                v                   v
  |               === Join (sync) ===
  |                         |
  |                         v
  |                 [Create reservation record]
  |                         |
  |                         v
  |                      End
  |
  |-- No (External payment) --> [Generate payment reference]
                                     |
                                     v
                              [Show payment instructions to user]
                                     |
                                     v
                              [Wait for payment confirmation]
                                     |
                              {Decision: Payment confirmed?}
                                     |-- No --> [Mark reservation as 'Pending']
                                     |            |
                                     |            v
                                     |     [Notify user: Awaiting payment] --> End
                                     |
                                     |-- Yes --> [Record payment as 'Paid']
                                                   |
                                                   v
                                        === Fork (parallel) ===
                                        |                   |
                                        v                   v
                                [Log transaction]    [Send confirmation email / notif]
                                        |                   |
                                        v                   v
                                       === Join (sync) ===
                                                   |
                                                   v
                                       [Create reservation record]
                                                   |
                                                   v
                                                  End

Notes:
- Parallel actions use a fork/join pattern: transaction logging and notification are performed concurrently and synchronized before the reservation is finalized.
- Wallet path performs immediate deduction and auto-confirms the payment; external payments wait for an asynchronous confirmation (webhook, admin verification, or manual upload of reference number).
- The "Offer Top-up" step can extend into the Wallet Top-up flow; if the user tops up, the diagram loops back to "Check wallet balance." (extension)

---

**Document Version**: 1.3  
**Last Updated**: November 27, 2025  
**System**: MFsuite Hotel Reservation System  
**Architecture Level**: 0-2 (Context, Process, Use Cases)

## 23. Sequence Diagram — Make Reservation (External Payment)

### Selected Use Case
Make Reservation with External Payment (e.g., bank transfer, GCash, PayPal). This sequence covers both synchronous server checks and asynchronous payment confirmation (webhook or admin verification).

### ASCII Sequence (high level)
```
Guest -> Browser: Fill booking form (dates, guests, payment method)
Browser -> Server(PHP): POST /pages/process_reservation.php (booking + payment selection)
Server -> DB(MySQL): Check room availability (synchronous)
DB --> Server: Availability result
Server -> Server: Validate input, calculate total price
alt payment == wallet
       Server -> DB: Check wallet balance
       DB --> Server: Balance
       alt balance >= total
              Server -> DB: Deduct wallet, record payment as 'Paid'
              Server -> DB: Create reservation (Confirmed)
              Server -> EmailService: Send confirmation (async)
              Server --> Browser: Booking confirmed
       else
              Server --> Browser: Insufficient wallet balance (show error)
       end
else (external payment)
       Server -> Server: Generate reference_number
       Server -> DB: Insert payment record (status = 'Pending', reference)
       Server --> Browser: Show payment instructions + reference
       par
              Guest -> PaymentProvider: Make external payment (outside system)
       and
              Guest -> Browser -> Server: Optionally upload reference / notify payment made
       end
       alt PaymentProvider supports webhook
              PaymentProvider -> Server: POST /payment_webhook (payment confirmed) [async]
              Server -> DB: Update payment status = 'Paid'
              Server -> DB: Create reservation (Confirmed)
              par
                     Server -> DB: Log transaction
              and
                     Server -> EmailService: Send confirmation
              end
              Server --> Browser: Booking confirmed
       else Manual/Admin verification
              Admin -> Server(Admin UI): Verify payment / approve
              Server -> DB: Update payment status = 'Paid'
              Server -> DB: Create reservation (Confirmed)
              Server -> EmailService: Send confirmation
              Server --> Browser: Booking confirmed (after admin action)
       end
end
```

### PlantUML Sequence (copy & render locally)
```
@startuml
actor Guest
participant Browser
participant Server as "PHP Backend"
participant DB as "MySQL Database"
participant Payment as "Payment Provider (External)"
participant Admin
participant Email as "Email Service"

Guest -> Browser: Fill booking form
Browser -> Server: POST /pages/process_reservation.php (booking + payment)
Server -> DB: SELECT availability
DB --> Server: availability result
Server -> Server: validate inputs / calculate total

alt payment == "wallet"
       Server -> DB: SELECT wallet_balance
       DB --> Server: wallet_balance
       alt wallet_balance >= total
              Server -> DB: UPDATE wallet_balance (deduct)
              DB --> Server: OK
              Server -> DB: INSERT tbl_payment (status='Paid')
              Server -> DB: INSERT tbl_reservation (confirmed)
              par
                     Server -> DB: INSERT wallet_transaction (log)
              and
                     Server -> Email: send booking confirmation (async)
              end
              Server --> Browser: 200 OK (Booking Confirmed)
       else
              Server --> Browser: 400 Insufficient Wallet Funds
       end
else
       Server -> Server: generate reference_number
       Server -> DB: INSERT tbl_payment (status='Pending', reference)
       Server --> Browser: 200 Payment Instructions (reference)

       par
              Guest -> Payment: Make external transfer (outside system)
       and
              Guest -> Browser: (optional) Upload payment proof / submit reference
              Browser -> Server: POST /pages/upload_payment_proof.php
       end

       alt Payment Provider webhook
              Payment -> Server: POST /payment_webhook (payment confirmed) [async]
              Server -> DB: UPDATE tbl_payment (status='Paid')
              Server -> DB: INSERT tbl_reservation (confirmed)
              par
                     Server -> DB: INSERT payment_log
              and
                     Server -> Email: send booking confirmation
              end
              Server --> Browser: 200 OK (Booking Confirmed)
       else Admin verifies
              Admin -> Server: Approve payment in admin UI
              Server -> DB: UPDATE tbl_payment (status='Paid')
              Server -> DB: INSERT tbl_reservation (confirmed)
              Server -> Email: send booking confirmation
              Server --> Browser: 200 OK (Booking Confirmed after admin action)
       end
end

@enduml
```

### Notes on synchronous/asynchronous messages
- Database interactions are synchronous RPC-like calls (request/response).
- External payment confirmation is asynchronous (webhook) — Payment Provider -> Server is asynchronous.
- Notifications and logging are performed in parallel (can be done asynchronously) using fork/join.
- Admin verification is a manual synchronous action from the admin's perspective, but the end-to-end confirmation is effectively asynchronous for the guest.

---

**Document Version**: 1.4  
**Last Updated**: November 27, 2025  
**System**: MFsuite Hotel Reservation System  
**Architecture Level**: 0-2 (Context, Process, Use Cases)

## 24. Written Description — Brief Narrative

Purpose of the system and diagrams
- The MFsuite Hotel Reservation System is a web-based platform that enables guests to browse room inventory, make reservations, and manage payments (including an internal wallet), while providing hotel staff and administrators with tools to approve bookings, manage rooms and payments, and view operational reports. The diagrams in this document (Level 0 architecture, DFD Level 0/1, Use Case, Activity, and Sequence) collectively express the system boundary, major components, actors, data stores, and the runtime interactions required to complete key business flows such as booking and payment.

Problems it solves
- Simplifies booking workflow: the system centralizes availability checks, pricing calculation, and reservation recording to prevent double-bookings and reduce manual coordination.
- Supports multiple payment flows: it unifies wallet-based payments and external payment methods (bank/Gcash/PayPal/credit card) while offering administrative verification and refund handling—reducing manual reconciliation overhead.
- Improves operational visibility: admin dashboards and reporting modules surface revenue, occupancy, and transaction status for timely decision-making.
- Provides user self-service: guests can manage profiles, view booking history, top up wallets, and receive status notifications without staff intervention.
- Ensures traceability and auditability: all payments and wallet transactions are logged for audits and for processing refunds correctly.

Key features and modules
- Presentation Layer (Guest/Admin/Public): Responsive pages implemented with Bootstrap and JavaScript hosting booking forms, admin dashboards, wallet interfaces, and notifications.
- Authentication & Profile Module: Session-based auth, registration, profile and payment account management.
- Booking & Availability Module: Central logic to validate date ranges, check room availability, calculate total price (nightly rate × nights), and create reservations.
- Payment & Wallet Module: Supports wallet top-ups, immediate wallet deductions, external payment flows with reference numbers, transaction logging, and refund processing.
- Admin Module: Reservation approval, room assignment, payment verification, reporting, and guest management.
- Notification Module: Stores and delivers notifications (email + in-app) for booking confirmations, payment updates, and status changes.
- Data Layer (MySQL): Structured stores for guests, rooms, reservations, payments, wallet transactions, notifications, and services.

How the diagrams represent system flow and integration
- Level 0 Architecture Diagram: Presents a high-level view of the system split into Presentation, Application, and Data layers. It highlights the main integration points (web UI ↔ PHP backend ↔ MySQL), and external resources (image hosting, email) to give stakeholders immediate context about where components live and interact.
- Data Flow Diagram (DFD) Level 0 & Level 1: Shows the system boundary, external actors (Guest, Admin, Email Service), major data stores (D1–D8), and primary flows (booking requests, payment records, wallet changes, notifications). Level 1 decomposes core processes (Auth/Profile, Availability & Booking, Payment & Wallet, Reservation Approval, Notifications, Reporting) and demonstrates how data flows between processes and stores—useful for identifying validation points, concurrency risks, and opportunities for caching or batching.
- Use Case Diagram: Enumerates primary actors and their use cases, and models relationships such as <<include>> (e.g., "Make Reservation" includes "Check Availability") and <<extend>> (e.g., "Cancel Reservation" may extend to "Request Refund"). This diagram connects user goals to system functionality and clarifies scope for functional requirements and test scenarios.
- Activity Diagram (Payment Processing): Focuses on a single core workflow—processing a payment. It shows decision nodes (wallet vs external, sufficient funds, payment confirmation), parallel tasks (logging and notification), and start/end nodes. This clarifies concurrency (fork/join), failure points (insufficient balance, unconfirmed payment), and extension points (top-up flow), which are essential for implementation and exception handling.
- Sequence Diagram (Make Reservation — External Payment): Presents an ordered timeline of interactions among actors and components (Guest, Browser, PHP Backend, MySQL DB, Payment Provider, Admin, Email Service). It differentiates synchronous calls (DB queries) from asynchronous confirmation (payment webhook or manual admin verification) and shows how and when the reservation and payment records are updated. This is valuable for designing API endpoints, webhook handlers, and ensuring correct transactional behavior.

Summary
- Together, the diagrams and narrative provide a compact but complete representation of the MFsuite system. They show how user actions translate into backend processing, how critical decisions are made and validated, where external dependencies exist, and how data is persisted and notified. This collection supports system design, implementation planning, testing, and onboarding by offering both conceptual and operational viewpoints useful to developers, QA, and operations teams.

---

**Document Version**: 1.5  
**Last Updated**: November 27, 2025  
**System**: MFsuite Hotel Reservation System  
**Architecture Level**: 0-2 (Context, Process, Use Cases)
