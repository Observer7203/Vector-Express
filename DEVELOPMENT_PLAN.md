# Vector Express - Полный план разработки

## 1. Описание проекта

**Vector Express** - метапоисковик логистических услуг для эффективной обработки стоимости и сроков доставки.

**Слоган**: "Find your best logistics solution" / "Найди лучшее решение с нами"

**Целевая аудитория**:
- B2B: Юридические лица (производство, средний и малый бизнес)
- B2C: Физические лица (редко)

**Бизнес-модель**: Комиссия 5% с каждой продажи

**Технический стек**:
- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 (Composition API)
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis
- **AI/Chat**: OpenAI API
- **Real-time**: Laravel Reverb / Pusher
- **API интеграции**: DHL, FedEx, UPS, Ponyexpress

---

## 2. Роли пользователей

### 2.1 Заказчик (Customer/Shipper)
- Регистрация/авторизация
- Создание заявки на расчет стоимости перевозки
- Просмотр предложений от перевозчиков
- Выбор и оформление заказа
- Отслеживание груза в реальном времени
- Общение с перевозчиком через AI чат-бот
- Просмотр истории заказов
- Оплата (постоплата 15 дней)

### 2.2 Перевозчик (Carrier)
- Регистрация/верификация компании
- Настройка тарифов и маршрутов
- Получение заявок на перевозку
- Обновление статуса доставки
- Общение с заказчиком через AI чат-бот
- Управление документами
- Получение оплаты

### 2.3 Администратор (Admin)
- Верификация компаний
- Модерация контента
- Управление тарифами и комиссиями
- Аналитика и отчеты
- Управление пользователями
- Настройка интеграций с API

---

## 3. Схема базы данных (ERD)

### 3.1 Диаграмма связей

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    users    │────<│  companies  │>────│   carriers  │
└─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │
       ▼                   │                   │
┌─────────────┐           │                   │
│  shipments  │           │                   │
└─────────────┘           │                   │
       │                   │                   │
       ▼                   │                   │
┌─────────────┐           │                   │
│   quotes    │<──────────┘                   │
└─────────────┘                               │
       │                                       │
       ▼                                       │
┌─────────────┐                               │
│   orders    │<──────────────────────────────┘
└─────────────┘
       │
       ├──────────────┐
       ▼              ▼
┌─────────────┐ ┌─────────────┐
│    chats    │ │  invoices   │
└─────────────┘ └─────────────┘
       │
       ▼
┌─────────────┐
│  messages   │
└─────────────┘
```

### 3.2 Таблицы и связи

#### users
```
id, email, password, name, phone, avatar
role: customer | carrier | admin
company_id → companies.id
email_verified_at, created_at, updated_at
```

#### companies
```
id, name, inn, legal_address, actual_address
phone, email, website, logo
type: shipper | carrier
rating, rating_count, verified, verified_at
created_at, updated_at
```

#### carriers
```
id, company_id → companies.id
api_type: dhl | fedex | ups | ponyexpress | manual
api_config (JSON)
supported_transport_types (JSON): [air, sea, rail, road]
supported_countries (JSON)
is_active, created_at, updated_at
```

#### shipments (заявки на расчет)
```
id, user_id → users.id
origin_country, origin_city, origin_address
destination_country, destination_city, destination_address
transport_type: air | sea | rail | road | multimodal
cargo_type: general | dangerous | fragile | perishable
packaging_type: box | pallet | container
total_weight, total_volume, declared_value, currency
insurance_required, customs_clearance, door_to_door
pickup_date, notes
status: draft | calculating | quoted | ordered | expired
created_at, updated_at
```

#### shipment_items (позиции груза)
```
id, shipment_id → shipments.id
length, width, height, weight, quantity, description
created_at
```

#### quotes (предложения)
```
id, shipment_id → shipments.id
carrier_id → carriers.id
price, currency, delivery_days, estimated_delivery_date
transport_type, services_included (JSON)
valid_until, is_selected
created_at, updated_at
```

#### orders (заказы)
```
id, order_number (VE-2025-000001)
quote_id → quotes.id
user_id → users.id
carrier_id → carriers.id
status: pending | confirmed | pickup_scheduled | picked_up | in_transit | customs | out_for_delivery | delivered | cancelled
contact_name, contact_phone, contact_email
pickup_contact_name, pickup_contact_phone, pickup_address
pickup_date, pickup_time_from, pickup_time_to
delivery_contact_name, delivery_contact_phone, delivery_address
tracking_number, carrier_tracking_number
total_amount, commission_amount (5%), currency
notes, confirmed_at, picked_up_at, delivered_at
cancelled_at, cancellation_reason
created_at, updated_at
```

#### tracking_events
```
id, order_id → orders.id
status, location_city, location_country
latitude, longitude, description, event_time
created_at
```

#### chats
```
id, order_id → orders.id
created_at, updated_at
```

#### chat_participants
```
id, chat_id → chats.id
user_id → users.id
role: customer | carrier
last_read_at, created_at
```

#### messages
```
id, chat_id → chats.id
sender_type: customer | carrier | ai | system
sender_id → users.id (nullable для AI/system)
content, is_read, created_at
```

#### message_attachments
```
id, message_id → messages.id
file_name, file_path, file_type, file_size
created_at
```

#### invoices
```
id, invoice_number (INV-2025-000001)
order_id → orders.id
user_id → users.id
subtotal, commission (5%), total, currency
status: pending | paid | overdue | cancelled
due_date (created_at + 15 days)
paid_at, payment_method
created_at, updated_at
```

#### reviews
```
id, order_id → orders.id
user_id → users.id
carrier_id → carriers.id
rating (1-5), comment, created_at
```

---

## 4. API Endpoints

### 4.1 Аутентификация
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
GET    /api/auth/verify-email/{token}
```

### 4.2 Пользователи
```
GET    /api/user/profile
PUT    /api/user/profile
POST   /api/user/avatar
PUT    /api/user/password
```

### 4.3 Компании
```
GET    /api/companies/{id}
PUT    /api/companies/{id}
POST   /api/companies/{id}/logo
POST   /api/companies/{id}/verification
```

### 4.4 Заявки (Shipments)
```
GET    /api/shipments
POST   /api/shipments
GET    /api/shipments/{id}
PUT    /api/shipments/{id}
DELETE /api/shipments/{id}
POST   /api/shipments/{id}/calculate
GET    /api/shipments/{id}/quotes
```

### 4.5 Заказы (Orders)
```
GET    /api/orders
POST   /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}
POST   /api/orders/{id}/cancel
POST   /api/orders/{id}/confirm    (carrier)
PUT    /api/orders/{id}/status     (carrier)
GET    /api/orders/{id}/tracking
```

### 4.6 Чаты
```
GET    /api/chats
GET    /api/chats/{id}
GET    /api/chats/{id}/messages
POST   /api/chats/{id}/messages
POST   /api/chats/{id}/attachments
```

### 4.7 Инвойсы
```
GET    /api/invoices
GET    /api/invoices/{id}
GET    /api/invoices/{id}/pdf
POST   /api/invoices/{id}/pay
```

---

## 5. User Flows

### 5.1 Создание заказа (Customer)

```
1. Авторизация → Логин/Регистрация

2. Создание заявки:
   ├─ Шаг 1: Направление (откуда → куда)
   ├─ Шаг 2: Параметры груза (габариты, вес, тип)
   ├─ Шаг 3: Дополнительные услуги
   └─ Шаг 4: [РАССЧИТАТЬ]

3. Получение предложений:
   ├─ Список от 10+ перевозчиков
   ├─ Сортировка: цена / сроки / рейтинг
   └─ [ЗАКАЗАТЬ]

4. Оформление заказа:
   ├─ Контактные данные
   └─ [ПОДТВЕРДИТЬ]

5. Отслеживание:
   ├─ Статусы в реальном времени
   ├─ Карта
   └─ Чат с перевозчиком
```

### 5.2 Обработка заказа (Carrier)

```
1. Получение уведомления о новом заказе
2. Просмотр деталей → [ПОДТВЕРДИТЬ] / [ОТКЛОНИТЬ]
3. Обновление статусов:
   pending → confirmed → pickup_scheduled → picked_up → in_transit → customs → delivered
4. Получение оплаты
```

### 5.3 Статусы заказа

```
pending          → Ожидает подтверждения
confirmed        → Подтвержден
pickup_scheduled → Назначена дата забора
picked_up        → Груз забран
in_transit       → В пути
customs          → На таможне
out_for_delivery → Доставляется
delivered        → Доставлен
cancelled        → Отменен
```

---

## 6. Структура проекта

### 6.1 Backend (Laravel 12)

```
/backend
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── CompanyController.php
│   │   ├── ShipmentController.php
│   │   ├── QuoteController.php
│   │   ├── OrderController.php
│   │   ├── ChatController.php
│   │   ├── TrackingController.php
│   │   └── InvoiceController.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Carrier.php
│   │   ├── Shipment.php
│   │   ├── Quote.php
│   │   ├── Order.php
│   │   ├── Chat.php
│   │   ├── Message.php
│   │   └── Invoice.php
│   │
│   ├── Services/
│   │   ├── Carriers/
│   │   │   ├── CarrierServiceInterface.php
│   │   │   ├── CarrierServiceFactory.php
│   │   │   ├── DhlCarrierService.php
│   │   │   ├── FedexCarrierService.php
│   │   │   └── MockCarrierService.php
│   │   │
│   │   ├── AI/
│   │   │   └── AIChatService.php
│   │   │
│   │   ├── ShipmentService.php
│   │   ├── QuoteService.php
│   │   └── OrderService.php
│   │
│   ├── Events/
│   ├── Listeners/
│   ├── Jobs/
│   └── Notifications/
│
├── database/migrations/
└── routes/api.php
```

### 6.2 Frontend (Vue.js 3)

```
/frontend
├── src/
│   ├── components/
│   │   ├── common/
│   │   ├── auth/
│   │   ├── shipment/
│   │   ├── quotes/
│   │   ├── order/
│   │   ├── tracking/
│   │   └── chat/
│   │
│   ├── views/
│   │   ├── public/
│   │   ├── dashboard/
│   │   ├── shipments/
│   │   ├── orders/
│   │   └── invoices/
│   │
│   ├── stores/ (Pinia)
│   ├── api/
│   └── router/
```

---

## 7. Интеграция с перевозчиками

### 7.1 Интерфейс CarrierService

```php
interface CarrierServiceInterface
{
    public function getQuotes(Shipment $shipment): array;
    public function createOrder(Order $order): CarrierOrderResponse;
    public function getTrackingStatus(string $trackingNumber): TrackingStatus;
    public function cancelOrder(string $orderNumber): bool;
}
```

### 7.2 Фабрика сервисов

```php
class CarrierServiceFactory
{
    public function make(Carrier $carrier): CarrierServiceInterface
    {
        return match($carrier->api_type) {
            'dhl' => new DhlCarrierService($carrier),
            'fedex' => new FedexCarrierService($carrier),
            'ups' => new UpsCarrierService($carrier),
            'mock' => new MockCarrierService($carrier),
            default => throw new UnknownCarrierException()
        };
    }
}
```

---

## 8. AI Чат-бот

### Функции:
1. Ответы на FAQ (тарифы, сроки, документы)
2. Помощь в заполнении заявки
3. Объяснение статусов доставки
4. Подсказки по таможенному оформлению

### Анонимность:
- До подтверждения заказа: Заказчик видит "Перевозчик DHL", Перевозчик видит "Заказчик #12345"
- После подтверждения: открываются контактные данные

---

## 9. Конкуренты

| Платформа | Виды перевозок | AI | Международные |
|-----------|----------------|-----|--------------|
| **Vector Express** | авиа, авто, ЖД, морские | ✅ | ✅ |
| Della KZ | авто | ❌ | ❌ |
| ATI.SU | авто | ❌ | ✅ |
| InDriver | авто | ❌ | ❌ |

**Преимущества Vector Express**:
- Все виды перевозок
- AI чат-бот
- 10+ международных перевозчиков
- Постоплата 15 дней

---

## 10. Этапы реализации

### Этап 1: Инфраструктура
- [ ] Laravel 12 проект
- [ ] Vue.js 3 проект
- [ ] База данных и миграции
- [ ] Sanctum аутентификация

### Этап 2: Основные модули
- [ ] Регистрация/авторизация
- [ ] Профиль пользователя и компании
- [ ] Создание заявок
- [ ] Расчет стоимости (mock)

### Этап 3: Интеграции
- [ ] DHL API
- [ ] FedEx API
- [ ] Ponyexpress

### Этап 4: Заказы и чат
- [ ] Создание заказов
- [ ] Статусы и отслеживание
- [ ] WebSocket чат
- [ ] AI интеграция

### Этап 5: Биллинг
- [ ] Инвойсы
- [ ] Платежные системы

---

## Источники

- [Freightos](https://www.freightos.com/)
- [Flexport](https://www.flexport.com/)
- [Shippo](https://goshippo.com/)
- [Della KZ](https://www.della.kz/)
- [ATI.SU](https://ati.su/)
