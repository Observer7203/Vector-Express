# Исследование калькуляторов логистики

## Обзор

Исследование проведено на основе анализа ведущих платформ: Freightos, Flexport, Shippo, Ship.com, ShipEngine.

---

## 1. Данные от клиента для расчёта

### 1.1 Адрес отправления/назначения

**Уровни детализации:**
- **Полный адрес** (Shippo, FedEx, UPS): улица, город, область, почтовый индекс, страна
- **Почтовый индекс** - для зонального ценообразования
- **Город/Порт** (Flexport, Freightos) - для terminal-to-terminal

**Валидация адресов:**
- PostGrid, ZipcodeStack, Radar - API валидации
- Время ответа: <100ms
- 247 стран покрытия

### 1.2 Параметры груза

**Обязательные:**
- Длина, ширина, высота (см или дюймы)
- Вес (кг или фунты)
- Количество мест

**Расчёт объёмного веса (DIM):**
```
DIM вес = (Длина × Ширина × Высота) / DIM-фактор

DIM-факторы 2025:
- FedEx: 139
- UPS: 166 (розница), 139 (бизнес)
- DHL: 166
- Метрическая система: 5000 (для см/кг)
```

**Правило:** Оплата по большему из двух - реальный вес или объёмный.

### 1.3 Тип груза

**Классификация ООН (9 классов):**
1. Взрывчатые вещества
2. Газы
3. Легковоспламеняющиеся жидкости
4. Легковоспламеняющиеся твёрдые вещества
5. Окисляющие вещества
6. Токсичные/инфекционные вещества
7. Радиоактивные материалы
8. Коррозионные вещества
9. Прочие опасные грузы

**Категории:**
- Обычный груз
- Опасный (требует UN номер)
- Хрупкий
- Скоропортящийся
- Ценный
- Температурный режим

### 1.4 Инкотермс

**Основные (Incoterms 2020):**
- **FOB** (Free on Board) - продавец до погрузки на судно
- **CIF** (Cost, Insurance, Freight) - продавец оплачивает доставку + страховку до порта назначения
- **DDP** (Delivered Duty Paid) - продавец оплачивает всё включая таможню
- **EXW** (Ex Works) - покупатель забирает от продавца
- **FCA** (Free Carrier) - продавец до перевозчика
- **DAP** (Delivered at Place) - продавец до места, покупатель таможня
- **CPT** (Carriage Paid To) - продавец оплачивает фрахт до назначения

### 1.5 Дополнительные услуги

- Страхование груза (0.5-2% от стоимости)
- Таможенное оформление
- HS код (10 цифр)
- Door-to-door / Terminal-to-terminal
- Liftgate (погрузчик)
- Внутренняя доставка
- Жилая доставка
- Доставка по записи
- White glove service

---

## 2. Данные от перевозчика при регистрации

### 2.1 Терминалы и хабы

**Что указывается:**
- Адрес терминала/склада
- Координаты (lat/lng)
- Тип: pickup / delivery / hub / warehouse
- Радиус обслуживания
- Часы работы
- Пропускная способность

### 2.2 Зоны обслуживания

- Список стран
- Регионы/области
- Почтовые индексы
- Удалённые районы (с надбавкой)
- Исключённые зоны

### 2.3 Модель ценообразования

**Зональная:**
- Почтовый индекс → Зона (1-8+)
- Матрица зона × зона = цена
- FedEx Freight: зоны 101-150

**По расстоянию:**
- Формула Хаверсина для расчёта расстояния
- Точность: ±0.5% до нескольких сотен км
- Тариф за км × расстояние

**Весовые пороги:**
- 1 кг, 5 кг, 10 кг, 25 кг, 50 кг, 100 кг, 500 кг, 1000+ кг
- LTL: цена за 100 фунтов (CWT)

### 2.4 Загрузка тарифов

**Структура Rate Card (3 уровня):**
1. Rate Card - привязка к клиенту
2. Rate Set - группа таблиц (обычно на год)
3. Rate Table - конкретные ставки по весу/зоне

**Форматы загрузки:**
- Excel, CSV, PDF
- API импорт
- Email/скриншоты (Freightify)

---

## 3. Логика ценообразования

### 3.1 Зональная vs По расстоянию

**Зональная:**
- ✅ Предсказуемая, простая
- ✅ Стандарт индустрии
- ❌ Менее точная для edge cases

**По расстоянию:**
- ✅ Точнее отражает затраты
- ✅ Лучше для нестандартных маршрутов
- ❌ Требует геокодинг

**Рекомендация:** Гибридный подход - зональная для стандартных перевозчиков, по расстоянию для региональных.

### 3.2 Надбавки (Surcharges)

**Топливная надбавка:**
- Процент от базовой ставки
- Обновляется еженедельно
- FedEx: по цене авиатоплива USGC
- Типичная ставка: >30% при дизеле ~$3.75/галлон

**Удалённые районы (2025):**
- FedEx: $14.25 за посылку
- Жилая доставка: $5.85 за посылку
- Определяется по почтовому индексу

**Пиковый сезон (2025):**
- USPS: 5 окт 2025 - 18 янв 2026
- UPS: 28 сент 2025 - 17 янв 2026
- FedEx: 29 сент 2025 - 18 янв 2026

**Прочие надбавки:**
- Дополнительная обработка: +4.1%
- Негабарит: $240-305 (+18.5%)
- Сверхразмер: $1,775 за посылку
- Суббота/воскресенье
- Подпись при получении
- Коррекция адреса

### 3.3 Terminal-to-Terminal vs Door-to-Door

**Door-to-Door:**
- Дороже на 10-20% ($100-300)
- Удобство, быстрее
- Рекомендуется для городов

**Terminal-to-Terminal:**
- Дешевле на 10-20%
- Скрытые расходы:
  - Транспорт до/от терминала: $40-80
  - Хранение при задержке: $5-20/день
- Добавляет 1-3 дня

**Рекомендация:** Показывать оба варианта с "истинной стоимостью" включая скрытые расходы.

---

## 4. API интеграции

### 4.1 Основные API перевозчиков

**FedEx:**
- Аутентификация: OAuth 2.0
- Сервисы: расчёт, отслеживание, этикетки
- developer.fedex.com

**DHL:**
- ShipIT (этикетки), RateiT (тарифы)
- XML формат

**UPS:**
- OAuth 2.0 (access keys устарели с авг 2024)
- Rate shopping, tracking, labels

**Агрегаторы:**
- ShipEngine: 100+ перевозчиков
- Shippo: 7¢ за этикетку после бесплатных 30K
- AfterShip, ReachShip

### 4.2 Real-time vs Кэширование

**Real-time:**
- ✅ Самые точные данные
- ❌ Зависимость от API, лимиты
- Цель: <100ms ответ

**Кэш:**
- ✅ Быстро, работает при сбоях
- ❌ Может устареть
- TTL: 15 минут для ставок, 1 неделя для зон

**Рекомендация:** Гибрид - кэш для частых маршрутов, real-time для уникальных.

### 4.3 Обработка сбоев API

**Статистика 2025:**
- Q1 2024: 34 мин/неделю простоя
- Q1 2025: 55 мин/неделю (+62%)
- 47% инцидентов стоят >$100K

**Стратегии fallback:**
1. Резервный перевозчик (ShipperHQ)
2. Мгновенные ошибки вместо таймаутов (EasyPost)
3. Кэшированные исторические ставки
4. HTTP 40x/50x для триггера backup
5. Exponential backoff с retry

**Мониторинг:**
- LexiConn Shipping API Monitor (бесплатный)
- Email уведомления о сбоях

---

## 5. Рекомендации для Vector Express

### 5.1 Форма расчёта для клиента

```javascript
{
  // Шаг 1: Маршрут
  origin: {
    addressType: 'full' | 'postal' | 'city',
    address, city, state, postalCode, country,
    isResidential: Boolean
  },
  destination: { /* аналогично */ },

  // Шаг 2: Груз
  packages: [{
    length, width, height,
    dimensionUnit: 'cm' | 'in',
    weight, weightUnit: 'kg' | 'lb',
    quantity
  }],
  cargoType: 'general' | 'dangerous' | 'fragile' | 'perishable',
  packagingType: 'box' | 'pallet' | 'container',
  declaredValue, currency,

  // Шаг 3: Опции
  incoterm: 'FOB' | 'CIF' | 'DDP' | 'EXW' | 'FCA' | 'DAP',
  insurance: Boolean,
  customsClearance: Boolean,
  doorToDoor: Boolean,
  pickupDate: Date
}
```

### 5.2 Регистрация перевозчика

```javascript
{
  company: { name, inn, legalAddress, certificates, logo },

  serviceAreas: {
    countries: [],
    regions: [],
    postalCodes: [],
    remoteAreaCodes: [],
    excludedCodes: []
  },

  terminals: [{
    type: 'pickup' | 'delivery' | 'hub',
    address, city, postalCode, country,
    latitude, longitude,
    serviceRadius, operatingHours
  }],

  pricingModel: {
    type: 'zone' | 'distance' | 'hybrid',
    dimFactor: 139,
    zones: [{ zoneId, postalCodes, baseRate, weightBreaks }],
    distanceRates: [{ maxDistance, ratePerKm }],
    minimumCharge,
    insuranceRate
  },

  surcharges: {
    fuel: { type: 'percentage', value, updateFrequency },
    residential, remoteArea, oversized
  },

  serviceLevel: {
    transitDays: { domestic, international },
    cutoffTime, pickupLeadTime
  },

  apiIntegration: {
    type: 'manual' | 'dhl' | 'fedex' | 'ups' | 'custom',
    apiKey, apiSecret, webhookUrl
  }
}
```

### 5.3 Алгоритм расчёта

```javascript
calculateQuote(shipment, carrier) {
  // 1. Billable weight
  const actualWeight = getTotalWeight(packages)
  const dimWeight = getDimWeight(packages, carrier.dimFactor)
  const billableWeight = Math.max(actualWeight, dimWeight)

  // 2. Базовая ставка
  let baseCost
  if (carrier.pricingModel.type === 'zone') {
    const zone = getZone(origin, destination, carrier)
    baseCost = getZoneRate(zone, billableWeight)
  } else {
    const distance = haversine(origin, destination)
    baseCost = getDistanceRate(distance, billableWeight)
  }

  // 3. Надбавки
  let surcharges = 0
  surcharges += baseCost * carrier.fuelSurcharge
  if (destination.isResidential) surcharges += 5.85
  if (isRemoteArea(destination)) surcharges += 14.25
  if (isOversized(packages)) surcharges += 240
  if (isPeakSeason(pickupDate)) surcharges += peakSurcharge

  // 4. Страховка
  const insurance = shipment.insurance
    ? shipment.declaredValue * carrier.insuranceRate
    : 0

  // 5. Минимальная ставка
  const subtotal = Math.max(baseCost + surcharges, carrier.minimumCharge)

  // 6. Комиссия (5%)
  const total = subtotal + insurance
  const commission = total * 0.05

  return {
    carrier: carrier.name,
    baseRate: baseCost,
    surcharges,
    insurance,
    total,
    commission,
    customerPays: total,
    carrierReceives: total - commission,
    estimatedDays: getTransitDays(shipment, carrier),
    validUntil: addDays(now, 7)
  }
}
```

### 5.4 Дополнительные таблицы БД

```sql
-- Правила ценообразования
CREATE TABLE carrier_pricing_rules (
  id BIGINT PRIMARY KEY,
  carrier_id BIGINT,
  pricing_type ENUM('zone', 'distance', 'hybrid'),
  dim_factor DECIMAL(8,2) DEFAULT 139,
  minimum_charge DECIMAL(10,2),
  insurance_rate DECIMAL(5,2),
  effective_from DATE,
  effective_until DATE,
  config JSON
);

-- Зоны
CREATE TABLE carrier_zones (
  id BIGINT PRIMARY KEY,
  carrier_id BIGINT,
  zone_code VARCHAR(10),
  zone_name VARCHAR(100)
);

-- Почтовые индексы → зоны
CREATE TABLE carrier_zone_postal_codes (
  id BIGINT PRIMARY KEY,
  zone_id BIGINT,
  postal_code_prefix VARCHAR(10),
  country_code VARCHAR(2),
  is_remote_area BOOLEAN DEFAULT FALSE
);

-- Тарифные карты
CREATE TABLE carrier_rate_cards (
  id BIGINT PRIMARY KEY,
  pricing_rule_id BIGINT,
  origin_zone_id BIGINT,
  destination_zone_id BIGINT,
  min_weight DECIMAL(10,2),
  max_weight DECIMAL(10,2),
  rate DECIMAL(10,2),
  rate_unit ENUM('per_kg', 'per_100kg', 'flat')
);

-- Надбавки
CREATE TABLE carrier_surcharges (
  id BIGINT PRIMARY KEY,
  carrier_id BIGINT,
  surcharge_type VARCHAR(50),
  value DECIMAL(10,2),
  value_type ENUM('fixed', 'percentage'),
  applies_from DATE,
  applies_until DATE,
  conditions JSON
);

-- Терминалы
CREATE TABLE carrier_terminals (
  id BIGINT PRIMARY KEY,
  carrier_id BIGINT,
  terminal_type ENUM('pickup', 'delivery', 'hub'),
  name VARCHAR(255),
  address TEXT,
  city, postal_code, country VARCHAR,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  service_radius INT,
  operating_hours JSON,
  is_active BOOLEAN DEFAULT TRUE
);

-- Кэш ставок
CREATE TABLE cached_rates (
  id BIGINT PRIMARY KEY,
  carrier_id BIGINT,
  route_hash VARCHAR(64),
  rate_data JSON,
  created_at TIMESTAMP,
  expires_at TIMESTAMP
);
```

---

## 6. Ключевые отличия Vector Express

1. **Прозрачность надбавок** - конкуренты скрывают, мы показываем детальную разбивку
2. **Калькулятор "истинной стоимости"** - для terminal-to-terminal показываем скрытые расходы
3. **Умный fallback** - при 55 мин/неделю простоя API это критично
4. **AI чат для вопросов** - "Почему перевозчик A дороже?" → объясняем надбавки
5. **Self-service для перевозчиков** - загрузка тарифов, аналитика конкурентоспособности

---

## Источники

- Freightos Freight Calculator
- Flexport Help Center
- Shippo API Documentation
- FedEx Developer Portal
- DHL APIs
- UPS Developer Kit
- ShipEngine Multi-carrier API
- GoBolt Carrier Rates Guide 2025
- ICC Logistics Rate Impact Report 2025
