# 📐 Diagram Sistem - myITS Merchandise

Dokumen ini berisi diagram-diagram teknis sistem yang dapat di-render dengan Mermaid.

---

## 1. Use Case Diagram

```mermaid
flowchart TB
    subgraph System["🛒 myITS Merchandise System"]
        UC1[Browse Katalog]
        UC2[Cari Produk]
        UC3[Lihat Detail Produk]
        UC4[Tambah ke Keranjang]
        UC5[Checkout]
        UC6[Bayar Pesanan]
        UC7[Lihat Riwayat Transaksi]
        UC8[Download Invoice]
        UC9[Apply Kupon]
        UC10[Kelola Alamat]
        UC11[Register/Login]
        
        UC20[Kelola Produk]
        UC21[Kelola Pesanan]
        UC22[Input Resi]
        UC23[Kelola Kupon]
        UC24[Lihat Dashboard]
    end
    
    Customer((👤 Customer))
    Admin((👨‍💼 Admin))
    
    Customer --> UC1
    Customer --> UC2
    Customer --> UC3
    Customer --> UC4
    Customer --> UC5
    Customer --> UC6
    Customer --> UC7
    Customer --> UC8
    Customer --> UC9
    Customer --> UC10
    Customer --> UC11
    
    Admin --> UC20
    Admin --> UC21
    Admin --> UC22
    Admin --> UC23
    Admin --> UC24
```

---

## 2. Activity Diagram - Proses Pembelian

```mermaid
flowchart TD
    Start([Start]) --> A[Buka Katalog]
    A --> B[Pilih Produk]
    B --> C[Lihat Detail]
    C --> D{Tambah ke Keranjang?}
    D -->|Ya| E[Masukkan ke Cart]
    D -->|Tidak| B
    E --> F{Lanjut Belanja?}
    F -->|Ya| B
    F -->|Tidak| G[Buka Keranjang]
    G --> H{User Login?}
    H -->|Tidak| I[Login/Register]
    I --> H
    H -->|Ya| J[Checkout]
    J --> K[Pilih Alamat]
    K --> L[Pilih Kurir]
    L --> M{Punya Kupon?}
    M -->|Ya| N[Apply Kupon]
    N --> O[Lihat Total]
    M -->|Tidak| O
    O --> P[Klik Bayar]
    P --> Q[Popup Midtrans]
    Q --> R{Bayar Sukses?}
    R -->|Ya| S[Pesanan Diproses]
    R -->|Tidak| T[Pembayaran Gagal]
    S --> U[Admin Kemas]
    U --> V[Admin Input Resi]
    V --> W[Pesanan Dikirim]
    W --> X[Pesanan Selesai]
    X --> End([End])
    T --> End
```

---

## 3. Sequence Diagram - Checkout Flow

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend
    participant BE as Backend
    participant DB as Database
    participant MT as Midtrans
    participant RO as RajaOngkir
    
    User->>FE: Klik Checkout
    FE->>BE: GET /cart.php
    BE->>DB: SELECT cart items
    DB-->>BE: Cart data
    BE-->>FE: Cart items
    
    User->>FE: Pilih Alamat
    FE->>BE: GET /addresses.php
    BE->>DB: SELECT addresses
    DB-->>BE: Address list
    BE-->>FE: Addresses
    
    User->>FE: Pilih Kurir
    FE->>BE: POST /get_shipping_cost.php
    BE->>RO: Calculate shipping
    RO-->>BE: Shipping options
    BE-->>FE: Costs & ETD
    
    User->>FE: Apply Kupon
    FE->>BE: GET /coupons.php?code=XXX
    BE->>DB: Validate coupon
    DB-->>BE: Coupon data
    BE-->>FE: Discount amount
    
    User->>FE: Klik Bayar
    FE->>BE: POST /checkout.php
    BE->>DB: INSERT order
    BE->>MT: Get Snap Token
    MT-->>BE: Snap Token
    BE-->>FE: Token + Order ID
    
    FE->>MT: Open Snap Popup
    User->>MT: Complete Payment
    MT-->>FE: Payment Success
    FE->>BE: POST /update_payment_status.php
    BE->>DB: UPDATE order status
    DB-->>BE: Success
    BE-->>FE: Confirmation
    FE-->>User: Order Confirmed!
```

---

## 4. Class Diagram (Simplified)

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string email
        +string password
        +string phone
        +string role
        +register()
        +login()
        +updateProfile()
    }
    
    class Product {
        +int id
        +string name
        +string description
        +decimal price
        +int stock
        +int category_id
        +getDetails()
        +updateStock()
    }
    
    class Order {
        +int id
        +int user_id
        +string status
        +decimal total_amount
        +decimal shipping_cost
        +decimal discount_amount
        +create()
        +updateStatus()
        +getDetails()
    }
    
    class Cart {
        +int id
        +int user_id
        +int product_id
        +int quantity
        +add()
        +update()
        +remove()
        +getItems()
    }
    
    class Coupon {
        +int id
        +string code
        +string discount_type
        +decimal discount_value
        +validate()
        +apply()
        +incrementUsage()
    }
    
    class Address {
        +int id
        +int user_id
        +string label
        +string recipient
        +string phone
        +string full_address
        +add()
        +delete()
    }
    
    User "1" --> "*" Order : places
    User "1" --> "*" Cart : has
    User "1" --> "*" Address : has
    Order "1" --> "*" OrderDetail : contains
    Order "*" --> "0..1" Coupon : uses
    Cart "*" --> "1" Product : contains
    OrderDetail "*" --> "1" Product : references
```

---

## 5. State Diagram - Order Status

```mermaid
stateDiagram-v2
    [*] --> Pending : Order Created
    Pending --> Unpaid : Waiting Payment
    Unpaid --> Processing : Payment Received
    Unpaid --> Cancelled : Payment Timeout/Cancel
    Processing --> Shipped : Admin Input Resi
    Shipped --> Completed : Delivered
    Processing --> Cancelled : Admin Cancel
    Shipped --> Cancelled : Return/Refund
    Completed --> [*]
    Cancelled --> [*]
    
    note right of Processing
        Admin mengemas
        pesanan
    end note
    
    note right of Shipped
        Pesanan dalam
        pengiriman
    end note
```

---

## 6. Component Diagram

```mermaid
flowchart TB
    subgraph Presentation["Presentation Layer"]
        HTML[HTML Pages]
        CSS[CSS Styles]
        JS[JavaScript/app.js]
    end
    
    subgraph Business["Business Logic Layer"]
        Auth[Authentication]
        Cart[Cart Management]
        Order[Order Processing]
        Payment[Payment Handler]
        Shipping[Shipping Calculator]
        Coupon[Coupon System]
    end
    
    subgraph Data["Data Access Layer"]
        DB_PHP[db.php]
        MySQL[(MySQL Database)]
    end
    
    subgraph External["External Services"]
        Midtrans[Midtrans API]
        RajaOngkir[RajaOngkir API]
    end
    
    HTML --> JS
    CSS --> HTML
    JS --> Auth
    JS --> Cart
    JS --> Order
    
    Auth --> DB_PHP
    Cart --> DB_PHP
    Order --> DB_PHP
    Order --> Payment
    Order --> Shipping
    Order --> Coupon
    
    Payment --> Midtrans
    Shipping --> RajaOngkir
    
    DB_PHP --> MySQL
    Coupon --> DB_PHP
```

---

## 7. Deployment Diagram

```mermaid
flowchart TB
    subgraph Client["Client Device"]
        Browser[Web Browser]
    end
    
    subgraph WebServer["Web Server"]
        Apache[Apache HTTP Server]
        PHP[PHP 8.x Runtime]
        
        subgraph App["Application"]
            FrontEnd[Static Files - HTML/CSS/JS]
            API[PHP API Endpoints]
        end
    end
    
    subgraph DatabaseServer["Database Server"]
        MySQL[(MySQL 8.x)]
    end
    
    subgraph CloudServices["Cloud Services"]
        Midtrans[Midtrans Payment Gateway]
        RajaOngkir[RajaOngkir Shipping API]
    end
    
    Browser <--> Apache
    Apache --> FrontEnd
    Apache --> PHP
    PHP --> API
    API <--> MySQL
    API <--> Midtrans
    API <--> RajaOngkir
```

---

## 8. Data Flow Diagram (Level 0)

```mermaid
flowchart LR
    Customer((Customer))
    Admin((Admin))
    
    subgraph System["myITS Merchandise System"]
        Process[Process Orders & Transactions]
    end
    
    DB[(Database)]
    Midtrans[Midtrans]
    RajaOngkir[RajaOngkir]
    
    Customer -->|Order, Payment| Process
    Process -->|Confirmation, Invoice| Customer
    
    Admin -->|Manage Products, Orders| Process
    Process -->|Reports, Notifications| Admin
    
    Process <-->|Store/Retrieve Data| DB
    Process <-->|Payment Processing| Midtrans
    Process <-->|Shipping Cost| RajaOngkir
```

---

**Catatan:** Diagram-diagram di atas menggunakan format Mermaid dan dapat di-render di:
- GitHub
- VS Code (dengan ekstensi Markdown Preview Mermaid)
- Mermaid Live Editor (https://mermaid.live)
