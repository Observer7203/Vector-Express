# Payment Systems in Kazakhstan - Integration Research

## Overview

Research on Kazakhstan payment gateways that support online registration and sandbox/test mode without preliminary contract signing.

---

## Recommended Payment Systems

### 1. Kassa24 Business ⭐ (Best Option)

**Website:** https://business.kassa24.kz/online-payments

**Key Features:**
- **Demo mode WITHOUT contract** - start testing immediately
- Commission: only for successful transactions (depends on turnover)
- API integration without complex development
- Personal manager assigned
- Fiscalization included in tariff
- PCI DSS, 3D Secure protection

**Integration Process:**
1. Submit application
2. Upload documents and pass verification
3. Prepare site for accepting payments

**Pros:**
- No subscription fees
- Flexible withdrawal schedule
- Works with websites, apps, and social media

---

### 2. Robokassa.kz ⭐

**Website:** https://robokassa.kz/

**Key Features:**
- **Test mode** - payments without actual charges
- Online registration, launch in 1 day
- No subscription fees
- 72 ready modules for any CMS (15 min integration)
- Apple Pay, Samsung Pay, mobile balance payments
- 6 years in market, 8,500+ clients
- Accepts payments from 249 countries

**Integration Process:**
1. Account registration
2. Site configuration
3. Payment testing
4. Activation request submission

**Support:** 24/7 technical support, API integration

**Contact:** 8 800 500-25-57 | 8 727 277-77-97

---

### 3. TipTop Pay

**Website:** https://tiptoppay.kz

**API Documentation:** https://developers.tiptoppay.kz/

**Key Features:**
- **Sandbox/test environment** provided
- Commission from 2.5%
- Clear API + integration support
- Ready modules: WordPress, Joomla, OpenCart, Tilda, Wix
- PCI DSS Level 1 certification
- 37 currencies supported
- Visa, Mastercard, Apple Pay, Google Pay

**Pros:**
- Developer-friendly documentation
- Integration accompaniment
- Simple connection process

---

### 4. CloudPayments Kazakhstan

**Website:** https://cloudpayments.kz/

**Key Features:**
- **Test environment** available
- PCI DSS Level 1, 3D Secure, TLS 1.2, RSA 2048-bit
- 30 currencies
- 99.9% SLA uptime guarantee
- CMS modules + API
- Personal account manager

**Services:**
- Payment widget for websites and mobile apps
- Apple Pay and Google Pay integration
- Payment link generation (SMS, messengers, email)
- Subscription and one-click payments

**Contact:**
- Sales: +7 707 817 48 80
- Support: support@cloudpayments.kz

---

### 5. Paybox.kz

**Website:** https://paybox.money/

**Documentation:** https://paybox.ru/documentation/merchant-api/

**Key Features:**
- **Test mode by default** for new stores
- Parameter `pg_testing_mode=1` for test transactions
- Test cards for verification
- Recurring payments support
- SDK for iOS and Android
- PCI DSS compliance

**Test Mode Details:**
- All new stores start in test mode
- Use test payment methods only in test mode
- After signing Activation Act, store switches to production
- Can still run individual test transactions with `pg_testing_mode=1`

**Test Cards:**
- Can use real cards (no actual charges)
- Can use cards following Luhn algorithm
- Test cards don't validate fraud monitoring module

**Requirements:**
- Merchant ID (unique identifier)
- Secret key for data protection

**Note:** Requires signing Activation Act to switch to production mode

---

## Comparison Table

| System | Test Without Contract | Commission | Quick Start | API Docs |
|--------|----------------------|------------|-------------|----------|
| **Kassa24** | ✅ Demo mode | From turnover | ✅ | ✅ |
| **Robokassa** | ✅ Test mode | - | ✅ 1 day | ✅ |
| **TipTop Pay** | ✅ Sandbox | From 2.5% | ✅ | ✅ Excellent |
| **CloudPayments** | ✅ Test env | - | ✅ | ✅ |
| **Paybox** | ⚠️ Need account | - | ⚠️ | ✅ |

---

## Recommendation for Vector Express

For a B2B logistics marketplace, **Kassa24** or **TipTop Pay** are the best options:

1. **Immediate sandbox access** without bureaucracy
2. **Good API documentation** for custom integration
3. **Recurring payments support** (for potential subscription features)
4. **Reasonable commission rates**
5. **Multi-currency support** (important for international logistics)

### Suggested Integration Approach:

1. Start with **TipTop Pay sandbox** for development/testing
2. Implement payment flow in Laravel backend
3. Create payment service abstraction layer (for easy provider switching)
4. Test full payment cycle in sandbox
5. Sign contract and go live

---

## API Integration Notes

### Laravel Integration Example Structure:

```
app/
├── Services/
│   └── Payment/
│       ├── PaymentServiceInterface.php
│       ├── TipTopPayService.php
│       ├── Kassa24Service.php
│       └── PaymentFactory.php
├── Http/Controllers/
│   └── PaymentController.php
└── Models/
    └── Payment.php
```

### Key Endpoints to Implement:

- `POST /api/payments/init` - Initialize payment
- `POST /api/payments/callback` - Payment provider webhook
- `GET /api/payments/{id}/status` - Check payment status
- `POST /api/payments/{id}/refund` - Process refund

---

## Resources

- [Kassa24 Business](https://business.kassa24.kz/online-payments)
- [Robokassa.kz](https://robokassa.kz/)
- [TipTop Pay KZ](https://tiptoppay.kz)
- [TipTop Pay API Docs](https://developers.tiptoppay.kz/)
- [CloudPayments KZ](https://cloudpayments.kz/)
- [Paybox Documentation](https://paybox.ru/documentation/merchant-api/)

---

*Research conducted: December 2025*
