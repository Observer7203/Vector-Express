# Система расчёта стоимости перевозки

## Содержание
1. [Входные данные для расчёта](#входные-данные-для-расчёта)
2. [Типы перевозчиков](#типы-перевозчиков)
3. [Алгоритм расчёта](#алгоритм-расчёта)
4. [Формулы](#формулы)
5. [Структура ответа](#структура-ответа)
6. [База данных](#база-данных)

---

## Входные данные для расчёта

### Данные заявки (Shipment)

| Поле | Тип | Описание | Пример |
|------|-----|----------|--------|
| `origin_country` | string | Страна отправления | "Казахстан" |
| `origin_city` | string | Город отправления | "Астана" |
| `destination_country` | string | Страна назначения | "Китай" |
| `destination_city` | string | Город назначения | "Гуанчжоу" |
| `transport_type` | enum | Тип транспорта | air, sea, rail, road |
| `total_weight` | decimal | Общий вес (кг) | 100.5 |
| `total_volume` | decimal | Общий объём (м³) | 0.5 |
| `declared_value` | decimal | Объявленная стоимость (USD) | 5000 |
| `insurance_required` | boolean | Нужна страховка | true/false |
| `customs_clearance` | boolean | Таможенное оформление | true/false |
| `door_to_door` | boolean | Доставка до двери | true/false |

### Позиции груза (ShipmentItem)

| Поле | Тип | Описание |
|------|-----|----------|
| `length` | decimal | Длина (см) |
| `width` | decimal | Ширина (см) |
| `height` | decimal | Высота (см) |
| `weight` | decimal | Вес (кг) |
| `quantity` | integer | Количество мест |

---

## Типы перевозчиков

### 1. Manual (Ручной)
**Класс:** `ManualCarrierService`

Для перевозчиков без API интеграции. Расчёт по внутренним тарифным картам из базы данных.

**Используется для:** Локальные перевозчики, новые партнёры

### 2. Mock (Тестовый)
**Класс:** `MockCarrierService`

Генерирует тестовые предложения на основе базовых ставок. Используется для:
- Разработки и тестирования
- Перевозчиков с API, но без настроенных credentials

### 3. API-интегрированные
**Классы:** `DhlCarrierService`, `FedexCarrierService`, `UpsCarrierService`, `PonyexpressCarrierService`

Запрос к внешнему API перевозчика для получения актуальных тарифов.

**Статус:** Используют MockCarrierService как fallback (API credentials не настроены)

---

## Алгоритм расчёта

```
┌─────────────────────────────────────────────────────────────┐
│                    ВХОДНЫЕ ДАННЫЕ                           │
│  Shipment + ShipmentItems + Carrier Config                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 1. ПРОВЕРКА МАРШРУТА                                        │
│    - Перевозчик активен?                                    │
│    - Поддерживает страну отправления?                       │
│    - Поддерживает страну назначения?                        │
│    - Поддерживает тип транспорта?                           │
│                                                             │
│    Если НЕТ → return []                                     │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. РАСЧЁТ ТАРИФИЦИРУЕМОГО ВЕСА                              │
│                                                             │
│    actual_weight = shipment.total_weight                    │
│                                                             │
│    volumetric_weight = Σ (L × W × H / DIM_FACTOR) × qty     │
│                                                             │
│    billable_weight = MAX(actual_weight, volumetric_weight)  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. ПОИСК ТАРИФНОЙ КАРТЫ                                     │
│                                                             │
│    - Найти зону отправления по стране/городу                │
│    - Найти зону назначения по стране/городу                 │
│    - Найти rate_card по:                                    │
│      • origin_zone_id                                       │
│      • destination_zone_id                                  │
│      • transport_type                                       │
│      • min_weight <= billable_weight <= max_weight          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. РАСЧЁТ БАЗОВОЙ СТАВКИ                                    │
│                                                             │
│    rate_unit = 'per_kg' | 'flat' | 'per_100kg' | ...        │
│                                                             │
│    base_rate = switch(rate_unit):                           │
│      'flat'      → rate                                     │
│      'per_kg'    → billable_weight × rate                   │
│      'per_100kg' → (billable_weight / 100) × rate           │
│                                                             │
│    base_rate = MAX(base_rate, minimum_charge)               │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. РАСЧЁТ НАДБАВОК (Surcharges)                             │
│                                                             │
│    Для каждой активной надбавки:                            │
│    - fuel (топливо) → всегда                                │
│    - residential → если door_to_door                        │
│    - remote_area → если удалённый район                     │
│    - peak_season → в сезон                                  │
│                                                             │
│    surcharge = switch(calculation_type):                    │
│      'percentage' → base_rate × (value / 100)               │
│      'flat'       → value                                   │
│      'per_kg'     → billable_weight × value                 │
│                                                             │
│    surcharge = CLAMP(surcharge, min_value, max_value)       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. РАСЧЁТ СТРАХОВКИ                                         │
│                                                             │
│    if insurance_required AND declared_value:                │
│      insurance_cost = declared_value × (insurance_rate/100) │
│    else:                                                    │
│      insurance_cost = 0                                     │
│                                                             │
│    По умолчанию insurance_rate = 0.5%                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. ИТОГОВАЯ ЦЕНА                                            │
│                                                             │
│    total_price = base_rate                                  │
│                + surcharges_total                           │
│                + insurance_cost                             │
│                + customs_fee (если customs_clearance)       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ 8. СРОКИ ДОСТАВКИ                                           │
│                                                             │
│    delivery_days_min = rate_card.transit_days_min           │
│    delivery_days_max = rate_card.transit_days_max           │
│                                                             │
│    estimated_delivery = now() + delivery_days_max           │
└─────────────────────────────────────────────────────────────┘
```

---

## Формулы

### Объёмный вес (Volumetric Weight)

```
volumetric_weight = (L × W × H) / DIM_FACTOR × quantity
```

**DIM Factor по типу транспорта:**

| Тип | DIM Factor | Описание |
|-----|------------|----------|
| Air | 5000-6000 | Авиа (стандарт IATA: 6000) |
| Sea | 1000 | Морской |
| Rail | 3000-4000 | Железнодорожный |
| Road | 3000-5000 | Автомобильный |

*Размеры в см, вес в кг*

### Тарифицируемый вес (Chargeable Weight)

```
chargeable_weight = MAX(actual_weight, volumetric_weight)
```

### Базовая ставка

| Тип ставки | Формула |
|------------|---------|
| `flat` | `rate` |
| `per_kg` | `chargeable_weight × rate` |
| `per_lb` | `chargeable_weight × 2.20462 × rate` |
| `per_100kg` | `(chargeable_weight / 100) × rate` |
| `per_100lbs` | `(chargeable_weight × 2.20462 / 100) × rate` |

### Надбавки

| Тип расчёта | Формула |
|-------------|---------|
| `percentage` | `base_rate × (value / 100)` |
| `flat` | `value` |
| `per_kg` | `chargeable_weight × value` |

С ограничениями:
```
final_surcharge = MAX(surcharge, min_value)
final_surcharge = MIN(final_surcharge, max_value)
```

### Страховка

```
insurance_cost = declared_value × (insurance_rate / 100)
```

По умолчанию `insurance_rate = 0.5%`

---

## Типы транспорта

### Air (Авиа)

| Параметр | Значение |
|----------|----------|
| Базовая ставка | ~$12-15/кг |
| Сроки доставки | 3-7 дней |
| DIM Factor | 5000-6000 |
| Особенности | Быстро, дорого, ограничения по опасным грузам |

### Sea (Морской)

| Параметр | Значение |
|----------|----------|
| Базовая ставка | ~$1.5-3/кг |
| Сроки доставки | 30-45 дней |
| DIM Factor | 1000 |
| Особенности | Дёшево, долго, подходит для больших объёмов |

### Rail (Ж/Д)

| Параметр | Значение |
|----------|----------|
| Базовая ставка | ~$3-5/кг |
| Сроки доставки | 15-25 дней |
| DIM Factor | 3000-4000 |
| Особенности | Средняя цена/скорость, Китай-Европа |

### Road (Авто)

| Параметр | Значение |
|----------|----------|
| Базовая ставка | ~$4-8/кг |
| Сроки доставки | 7-14 дней |
| DIM Factor | 3000-5000 |
| Особенности | Гибкость, door-to-door |

---

## Структура ответа

### Quote (Предложение)

```json
{
  "carrier_id": 1,
  "price": 490.90,
  "currency": "USD",
  "base_rate": 400.00,
  "surcharges": {
    "items": [
      {
        "type": "fuel",
        "name": "Fuel Surcharge",
        "amount": 62.00
      },
      {
        "type": "residential",
        "name": "Residential Delivery",
        "amount": 8.00
      }
    ],
    "total": 70.00
  },
  "insurance_cost": 25.00,
  "customs_fee": 150.00,
  "billable_weight": 12.5,
  "delivery_days": 7,
  "delivery_days_min": 3,
  "delivery_days_max": 7,
  "estimated_delivery_date": "2025-12-18",
  "transport_type": "air",
  "services_included": [
    "door_pickup",
    "door_delivery",
    "customs_clearance",
    "insurance"
  ],
  "valid_until": "2025-12-18 06:19:40"
}
```

---

## База данных

### Схема таблиц

```
carriers
├── id
├── company_id → companies
├── api_type: 'manual' | 'dhl' | 'fedex' | 'ups' | 'ponyexpress' | 'mock'
├── api_config (JSON) - credentials для API
├── supported_transport_types (JSON) - ['air', 'sea', 'rail', 'road']
├── supported_countries (JSON) - ['KZ', 'CN', '*']
└── is_active

carrier_pricing_rules
├── id
├── carrier_id → carriers
├── pricing_type: 'zone' | 'distance' | 'weight'
├── dim_factor (default: 5000)
├── minimum_charge
├── insurance_rate (default: 0.5)
├── currency
├── effective_from
└── effective_until

carrier_zones
├── id
├── carrier_id → carriers
├── zone_code: 'Z1', 'Z2', ...
├── zone_name
├── country_code
└── description

carrier_zone_postal_codes
├── id
├── carrier_zone_id → carrier_zones
├── postal_code_prefix
├── postal_code_from / postal_code_to
├── city
├── region
├── country_code
└── is_remote_area

carrier_rate_cards
├── id
├── carrier_id → carriers
├── pricing_rule_id → carrier_pricing_rules
├── origin_zone_id → carrier_zones
├── destination_zone_id → carrier_zones
├── transport_type
├── min_weight / max_weight
├── rate
├── rate_unit: 'per_kg' | 'flat' | 'per_100kg' | ...
├── currency
├── transit_days_min / transit_days_max
├── effective_from / effective_until
└── timestamps

carrier_surcharges
├── id
├── carrier_id → carriers
├── surcharge_type: 'fuel' | 'residential' | 'remote_area' | ...
├── name
├── calculation_type: 'percentage' | 'flat' | 'per_kg'
├── value
├── min_value / max_value
├── currency
├── applies_to_transport_types (JSON)
├── effective_from / effective_until
└── is_active
```

### Связи

```
Carrier
  │
  ├── has many → CarrierPricingRule
  │                 └── has many → CarrierRateCard
  │
  ├── has many → CarrierZone
  │                 └── has many → CarrierZonePostalCode
  │
  ├── has many → CarrierSurcharge
  │
  └── has many → CarrierTerminal
```

---

## Пример расчёта

**Входные данные:**
- Маршрут: Астана → Гуанчжоу
- Тип: Авиа (air)
- Вес: 10 кг
- Размеры: 50×40×30 см
- Страховка: нет
- Таможня: да
- До двери: да

**Расчёт:**

```
1. Объёмный вес:
   volumetric = (50 × 40 × 30) / 5000 = 12 кг

2. Тарифицируемый вес:
   billable = MAX(10, 12) = 12 кг

3. Базовая ставка (rate = $15/кг):
   base_rate = 12 × 15 = $180

4. Надбавки:
   - Fuel (15.5%): 180 × 0.155 = $27.90
   - Residential: $8.00
   Итого надбавки: $35.90

5. Таможня: $150

6. Итого:
   total = 180 + 35.90 + 150 = $365.90

7. Сроки: 3-7 дней
```

---

## API интеграции (будущее)

### DHL Express API

**Endpoint:** `https://express.api.dhl.com/mydhlapi/`

**Методы:**
- `POST /rates` - получение тарифов
- `POST /shipments` - создание заказа
- `GET /shipments/{id}/tracking` - трекинг

### FedEx API

**Endpoint:** `https://apis.fedex.com/`

**Методы:**
- `POST /rate/v1/rates/quotes` - получение тарифов
- `POST /ship/v1/shipments` - создание заказа

### UPS API

**Endpoint:** `https://onlinetools.ups.com/`

**Методы:**
- `POST /api/rating/v1/Rate` - получение тарифов
- `POST /api/shipments/v1/ship` - создание заказа

---

## Файлы исходного кода

| Файл | Описание |
|------|----------|
| `app/Services/Carriers/CarrierServiceInterface.php` | Интерфейс сервиса |
| `app/Services/Carriers/AbstractCarrierService.php` | Базовый класс с общей логикой |
| `app/Services/Carriers/CarrierServiceFactory.php` | Фабрика сервисов |
| `app/Services/Carriers/ManualCarrierService.php` | Ручной расчёт |
| `app/Services/Carriers/MockCarrierService.php` | Тестовый расчёт |
| `app/Services/Carriers/DhlCarrierService.php` | DHL API |
| `app/Services/Carriers/FedexCarrierService.php` | FedEx API |
| `app/Services/Carriers/UpsCarrierService.php` | UPS API |
| `app/Services/Carriers/PonyexpressCarrierService.php` | Ponyexpress API |
