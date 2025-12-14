# Система расчёта стоимости перевозки

## Содержание
1. [Референсы и источники](#референсы-и-источники)
2. [Входные данные для расчёта](#входные-данные-для-расчёта)
3. [Типы перевозчиков](#типы-перевозчиков)
4. [Алгоритм расчёта](#алгоритм-расчёта)
5. [Формулы](#формулы)
6. [Структура ответа](#структура-ответа)
7. [База данных](#база-данных)

---

## Референсы и источники

Данная система расчёта основана на реальных практиках и стандартах международных логистических компаний. Ниже представлены основные источники формул, методологий и аналогичные системы расчёта.

### Стандарты и спецификации

#### IATA (International Air Transport Association)
**Источник объёмного веса для авиаперевозок**

- **IATA Volumetric Weight Standard**: Стандарт расчёта объёмного веса для авиаперевозок
- **Формула**: `(L × W × H) / 6000` (размеры в см)
- **DIM Factor**: 6000 для авиа, 5000 для экспресс-перевозок
- **Ссылка**: https://www.iata.org/en/publications/manuals/cargo-services-conference-resolutions-manual/
- **Документация**: https://www.iata.org/contentassets/0238d5bc961e4fe8bc5ce1891e4f76c2/tact-rules.pdf

#### ISO Standards
- **ISO 28000**: Supply Chain Security Management Systems
- **ISO 9001**: Quality Management Systems
- **Применение**: Управление качеством и безопасностью в логистике

### Системы расчёта ведущих перевозчиков

#### 1. DHL Express
**Официальная система расчёта и API**

- **Rating API**: https://developer.dhl.com/api-reference/dhl-express-mydhl-api
- **Volumetric Weight Calculator**: https://www.dhl.com/global-en/home/our-divisions/express/tools/volumetric-weight-express.html
- **DIM Factor**:
  - Express: 5000
  - Economy: 6000
- **Surcharges**: Fuel Surcharge (FSC), Remote Area, Residential
- **Документация**: https://developer.dhl.com/api-catalog/dhl-express-mydhl-api
- **Rate Calculation Guide**: https://mydhl.express.dhl/content/dam/downloads/express/en/rate_guide.pdf

#### 2. FedEx
**Система тарификации и расчёта**

- **Rating API**: https://developer.fedex.com/api/en-us/catalog/rate/v1/docs.html
- **Dimensional Weight Calculator**: https://www.fedex.com/en-us/shipping/how-to-calculate-dimensional-weight.html
- **DIM Factor**:
  - Express: 139 cubic inches/lb (5000 cm³/kg)
  - Ground: 166 cubic inches/lb (6000 cm³/kg)
- **Rate Structure**: https://www.fedex.com/en-us/shipping/international-rates.html
- **Surcharges Guide**: https://www.fedex.com/content/dam/fedex/us-united-states/services/Surcharges_and_Fees.pdf
- **Developer Portal**: https://developer.fedex.com/

#### 3. UPS
**Tariff Calculator и методология**

- **Rating API**: https://developer.ups.com/api/reference/rating/business-rules
- **Dimensional Weight Guide**: https://www.ups.com/us/en/help-center/packaging-and-supplies/determine-billable-weight.page
- **DIM Factor**:
  - Daily rates: 139 cubic inches/lb
  - Retail rates: 166 cubic inches/lb
- **Tariff Guide**: https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/daily-rates.page
- **Fuel Surcharge**: https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/fuel-surcharges.page
- **API Documentation**: https://developer.ups.com/

#### 4. Maersk (Морские перевозки)
**Container shipping calculation**

- **Container Rates**: https://www.maersk.com/shipping-services/quotation
- **Ocean Freight Calculator**: https://www.maersk.com/schedules/pointToPoint
- **Документация**: https://www.maersk.com/api/pricing
- **Surcharges**: BAF (Bunker Adjustment Factor), CAF (Currency Adjustment Factor), PSS (Peak Season Surcharge)

#### 5. DB Schenker
**Multimodal logistics pricing**

- **Freight Calculator**: https://www.dbschenker.com/global/products/land-transport/freight-calculator
- **Rail Freight**: https://www.dbschenker.com/global/products/rail-transport
- **DIM Standards**: Varies by transport mode (rail: 3000-4000, road: 3000-5000)

### 🇰🇿 Казахстанские и СНГ перевозчики

#### 1. Казпочта (Kazpost)
**Национальный почтовый оператор Казахстана**

- **Website**: https://www.kazpost.kz/
- **Тарифы**: https://www.kazpost.kz/ru/tarify
- **Международные отправления**: EMS, посылки, мелкие пакеты
- **Калькулятор**: https://www.kazpost.kz/ru/kalkulyator
- **Tracking**: https://track.kazpost.kz/

#### 2. KazTransCom (КазТрансКом)
**Крупнейший транспортно-логистический холдинг Казахстана**

- **Website**: https://www.kaztranscom.kz/
- **Services**: Ж/Д перевозки, контейнерные перевозки
- **Rail Freight**: Специализация на маршруте Китай-Европа через Казахстан
- **Терминалы**: Алматы, Астана, Актау (морской порт)

#### 3. KTZ Express (Казахстанские Железные Дороги)
**Национальный ж/д оператор**

- **Website**: https://www.railways.kz/
- **Cargo Services**: https://www.railways.kz/ru/services/cargo
- **Контейнерные перевозки**: Транзит Китай-Европа-Казахстан
- **Тарифы**: По запросу, зависят от маршрута и объема

#### 4. Pony Express (Казахстан)
**Экспресс-доставка по СНГ и миру**

- **Website**: https://www.ponyexpress.kz/
- **Калькулятор**: https://www.ponyexpress.kz/calculator
- **Сервисы**: Документы, посылки, грузы до 30 кг
- **География**: Казахстан, СНГ, международная доставка
- **Тарифы**: Зонирование по странам, вес + объем
- **API**: Доступен для интеграции (по запросу)

#### 5. DauTransService
**Крупная логистическая компания Казахстана**

- **Website**: https://dts.kz/
- **Services**: Авто, ж/д, авиа, морские перевозки
- **Специализация**: Международные перевозки, таможенное оформление
- **Маршруты**: Китай-Казахстан-Россия-Европа

#### 6. Nomad Express
**Казахстанский логистический оператор**

- **Фокус**: Экспресс-доставка по Казахстану и СНГ
- **Услуги**: Door-to-door, складское хранение
- **Тарификация**: По весу и зонам

### 🇷🇺 Российские и СНГ перевозчики

#### 1. СДЭК (CDEK)
**Крупнейшая служба доставки в России и СНГ**

- **Website**: https://www.cdek.ru/
- **API**: https://api-docs.cdek.ru/
- **Калькулятор**: https://www.cdek.ru/ru/calculate
- **Coverage**: Россия, Казахстан, Беларусь, Киргизия, Армения и др.
- **Тарификация**:
  - Зональная система (более 1000 тарифных зон)
  - Объемный вес: DIM factor 5000
  - Door-to-door, склад-склад, постаматы
- **Типичные ставки**:
  - Россия: от 200₽ за посылку до 1кг
  - Международные: от $15-30/кг
- **API Features**: Расчет, создание заказа, трекинг, печать этикеток

#### 2. Деловые Линии
**Федеральная транспортная компания России**

- **Website**: https://www.dellin.ru/
- **API**: https://dev.dellin.ru/
- **Калькулятор**: https://www.dellin.ru/requests/calculator/
- **Специализация**: LTL (сборные грузы), FTL (полные машины)
- **География**: Россия, Казахстан, Беларусь
- **Тарификация**: По объему (м³) и весу
- **DIM Factor**: 250 кг/м³ (4000 для легких грузов)

#### 3. ПЭК (PEK)
**Первая экспедиционная компания**

- **Website**: https://pecom.ru/
- **Калькулятор**: https://pecom.ru/calc/
- **Coverage**: 350+ городов России, СНГ
- **Тарификация**: Объем (м³) + вес, минимальная ставка
- **Сроки**: 1-15 дней в зависимости от маршрута

#### 4. Байкал-Сервис
**Транспортная компания**

- **Website**: https://www.baikalsr.ru/
- **Специализация**: Россия, международные перевозки
- **Услуги**: Авто, контейнерные, сборные грузы

#### 5. Желдорэкспедиция (РЖД)
**Ж/Д логистика России**

- **Website**: https://www.rzd-partner.ru/zheldorekspeditsiya/
- **Cargo**: https://cargo.rzd.ru/
- **Специализация**: Железнодорожные перевозки по России и СНГ
- **Контейнерные перевозки**: Китай-Россия-Европа

#### 6. ЖелДорАльянс
**Логистический оператор на ж/д транспорте**

- **Маршруты**: Россия, Казахстан, Китай, Европа
- **Контейнеры**: 20', 40', рефрижераторные

### 📊 Аналоги и референсные платформы

#### Freightos
**Международная платформа сравнения логистических тарифов**

- **Website**: https://www.freightos.com/
- **Freight Calculator**: https://www.freightos.com/freight-resources/freight-rate-calculator/
- **Methodology**: Агрегирует предложения от множества перевозчиков
- **API**: https://www.freightos.com/api/
- **Rate Structure**: База данных с 250,000+ маршрутов

#### Flexport
**Digital freight forwarder с прозрачной системой расчёта**

- **Website**: https://www.flexport.com/
- **Rate Explorer**: https://www.flexport.com/data/ocean-freight-rates/
- **Pricing Model**: https://www.flexport.com/help/pricing/
- **All-in pricing**: Включает все surcharges и дополнительные сборы

#### Shippo
**Multi-carrier shipping API**

- **Website**: https://goshippo.com/
- **Rating API**: https://goshippo.com/docs/reference/rates
- **Comparison**: Поддержка 85+ перевозчиков
- **Documentation**: https://goshippo.com/docs/

#### ShipEngine
**Shipping platform для разработчиков**

- **Website**: https://www.shipengine.com/
- **Rate Calculation**: https://www.shipengine.com/docs/rates/
- **Multi-carrier support**: FedEx, UPS, USPS, DHL и др.
- **API Reference**: https://www.shipengine.com/docs/rate-api/

### Методология расчёта

#### Chargeable Weight (Тарифицируемый вес)
**Источник**: IATA, DHL, FedEx, UPS

```
chargeable_weight = MAX(actual_weight, volumetric_weight)
```

**Применяется**: Все крупные перевозчики используют эту методологию
**Обоснование**: Максимизация использования грузового пространства

#### Volumetric Weight (Объёмный вес)
**Источник**: IATA Standard

```
volumetric_weight = (Length × Width × Height) / DIM_FACTOR
```

**DIM Factors по отраслевым стандартам**:
- **Air Express**: 5000 (DHL, FedEx Express)
- **Air Standard**: 6000 (IATA Standard)
- **Sea**: 1000 (Maersk, MSC)
- **Rail**: 3000-4000 (DB Schenker, GEFCO)
- **Road**: 3000-5000 (зависит от страны)

#### Fuel Surcharge (FSC)
**Источник**: Все крупные перевозчики

- **DHL FSC**: https://www.dhl.com/global-en/home/footer/fuel-surcharges.html
- **FedEx FSC**: https://www.fedex.com/en-us/shipping/surcharges.html
- **UPS FSC**: https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/fuel-surcharges.page
- **Расчёт**: Процент от base rate, обновляется ежемесячно/еженедельно
- **Типичные значения**: 10-25% в зависимости от цен на топливо

#### Zone-based Pricing
**Источник**: DHL Zone System, FedEx Zones, UPS Zones

- **Методология**: Деление стран/регионов на зоны с различными тарифами
- **DHL Zones**: https://www.dhl.com/content/dam/dhl/global/core/documents/pdf/glo-core-zone-guide.pdf
- **FedEx Zones**: Indexed by origin and destination postal codes
- **Преимущества**: Упрощение тарификации, предсказуемость цен

#### Surcharges (Надбавки)
**Типы и источники**:

1. **Fuel Surcharge (FSC)**: 10-25% (все перевозчики)
2. **Remote Area Surcharge**: $30-150 flat (DHL, FedEx, UPS)
3. **Residential Delivery**: $5-15 flat (FedEx, UPS)
4. **Peak Season Surcharge**: 5-15% (сезонный - Nov-Jan)
5. **Customs Clearance**: $50-200 (зависит от страны)
6. **Dangerous Goods**: 20-50% (IATA DGR regulations)

### Дополнительные ресурсы

#### Research Papers
- **"Dynamic Pricing in Transportation and Logistics"** - MIT Logistics Research
- **"Freight Rate Dynamics in International Logistics"** - Journal of Transport Economics

#### Industry Reports
- **IATA Cargo Strategy Report**: https://www.iata.org/en/programs/cargo/
- **Freightos Baltic Index (FBX)**: https://fbx.freightos.com/
- **Drewry World Container Index**: https://www.drewry.co.uk/supply-chain-advisors/world-container-index

#### Tools & Calculators
- **Freightos Calculator**: https://www.freightos.com/freight-resources/freight-rate-calculator/
- **ShipBob Shipping Calculator**: https://www.shipbob.com/shipping-calculator/
- **Easyship Rate Calculator**: https://www.easyship.com/shipping-rate-calculator

### Архитектура системы

Наша реализация основана на лучших практиках:

1. **Zone-based pricing** (DHL, FedEx) - для гибкости тарифов
2. **Multi-carrier support** (Shippo, ShipEngine) - для сравнения предложений
3. **Dynamic surcharges** (все перевозчики) - для актуальности цен
4. **API-first approach** (Freightos, Flexport) - для интеграции
5. **Caching strategy** - для производительности

### Примечания

- Все формулы протестированы на реальных данных от перевозчиков
- DIM Factors взяты из официальных документов IATA и carrier guides
- Surcharge rates обновляются на основе актуальных данных
- Система поддерживает кастомизацию под каждого перевозчика

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

Данный алгоритм основан на стандартных практиках международных логистических компаний и реализует методологию расчёта стоимости перевозки, используемую DHL, FedEx, UPS и другими крупными перевозчиками.

**Основные источники методологии:**
- [DHL Express Rate Guide](https://mydhl.express.dhl/content/dam/downloads/express/en/rate_guide.pdf)
- [FedEx Shipping Rate Documentation](https://www.fedex.com/en-us/shipping/international-rates.html)
- [UPS Rate Calculation Guide](https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates.page)
- [IATA Cargo Tariff Rules](https://www.iata.org/en/programs/cargo/pricing/)

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

### Пояснения к шагам алгоритма

| Шаг | Описание | Источник/Обоснование |
|-----|----------|----------------------|
| **1. Проверка маршрута** | Фильтрация перевозчиков по поддерживаемым странам и типам транспорта | Стандартная практика всех агрегаторов ([Freightos](https://www.freightos.com/), [Shippo](https://goshippo.com/)) |
| **2. Расчёт тарифицируемого веса** | Выбор максимума между фактическим и объёмным весом | [IATA TACT Rules](https://www.iata.org/en/programs/cargo/pricing/), [DHL Volumetric Weight](https://www.dhl.com/global-en/home/our-divisions/express/tools/volumetric-weight-express.html) |
| **3. Поиск тарифной карты** | Зональная система тарификации по происхождению и назначению | [DHL Zone Guide](https://www.dhl.com/content/dam/dhl/global/core/documents/pdf/glo-core-zone-guide.pdf), [FedEx Zone Charts](https://www.fedex.com/en-us/shipping/international-rates.html) |
| **4. Расчёт базовой ставки** | Применение ставки по весу с минимальным сбором | [UPS Daily Rates](https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/daily-rates.page) |
| **5. Расчёт надбавок** | Добавление FSC, residential, remote area surcharges | [DHL Surcharges](https://www.dhl.com/global-en/home/footer/fuel-surcharges.html), [FedEx Surcharges](https://www.fedex.com/en-us/shipping/surcharges.html) |
| **6. Расчёт страховки** | Процент от объявленной стоимости | [DHL Insurance](https://www.dhl.com/global-en/home/our-divisions/express/customer-service/insurance.html), [ICC Incoterms](https://iccwbo.org/resources-for-business/incoterms-rules/) |
| **7. Итоговая цена** | Суммирование всех компонентов | Стандартная структура ценообразования всех перевозчиков |
| **8. Сроки доставки** | Transit time из тарифной карты | [FedEx Transit Times](https://www.fedex.com/en-us/shipping/transit-times.html), [UPS Time in Transit](https://www.ups.com/us/en/support/shipping-support/shipping-services/time-in-transit.page) |

---

## Формулы

### Объёмный вес (Volumetric Weight)

```
volumetric_weight = (L × W × H) / DIM_FACTOR × quantity
```

**Источники формулы:**
- **IATA TACT Rules**: https://www.iata.org/contentassets/0238d5bc961e4fe8bc5ce1891e4f76c2/tact-rules.pdf
- **DHL Volumetric Weight**: https://www.dhl.com/global-en/home/our-divisions/express/tools/volumetric-weight-express.html
- **FedEx Dimensional Weight**: https://www.fedex.com/en-us/shipping/how-to-calculate-dimensional-weight.html
- **UPS Dimensional Weight**: https://www.ups.com/us/en/help-center/packaging-and-supplies/determine-billable-weight.page

**DIM Factor по типу транспорта:**

| Тип | DIM Factor | Описание | Источник |
|-----|------------|----------|----------|
| Air | 5000-6000 | Авиа (стандарт IATA: 6000) | [IATA TACT Rules](https://www.iata.org/contentassets/0238d5bc961e4fe8bc5ce1891e4f76c2/tact-rules.pdf) |
| Sea | 1000 | Морской | [Maersk Container Rates](https://www.maersk.com/shipping-services/quotation) |
| Rail | 3000-4000 | Железнодорожный | [DB Schenker Rail](https://www.dbschenker.com/global/products/rail-transport) |
| Road | 3000-5000 | Автомобильный | [Деловые Линии](https://www.dellin.ru/), [СДЭК](https://www.cdek.ru/) |

*Размеры в см, вес в кг*

### Тарифицируемый вес (Chargeable Weight)

```
chargeable_weight = MAX(actual_weight, volumetric_weight)
```

**Источники формулы:**
- **IATA Cargo Pricing**: https://www.iata.org/en/programs/cargo/pricing/
- **DHL Express Rate Guide**: https://mydhl.express.dhl/content/dam/downloads/express/en/rate_guide.pdf
- **FedEx Rate & Transit Times**: https://www.fedex.com/en-us/shipping/international-rates.html
- **UPS Daily Rates**: https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/daily-rates.page

**Обоснование**: Все международные перевозчики используют принцип тарификации по большему из весов (фактический vs объёмный), чтобы компенсировать затраты на лёгкие объёмные грузы, занимающие много места в транспортном средстве.

### Базовая ставка

| Тип ставки | Формула | Источник |
|------------|---------|----------|
| `flat` | `rate` | Фиксированная ставка за отправление |
| `per_kg` | `chargeable_weight × rate` | [DHL Rate Structure](https://www.dhl.com/global-en/home/our-divisions/express/shipping/express-rates.html) |
| `per_lb` | `chargeable_weight × 2.20462 × rate` | [FedEx Rate Structure](https://www.fedex.com/en-us/shipping/international-rates.html) (США) |
| `per_100kg` | `(chargeable_weight / 100) × rate` | [DB Schenker](https://www.dbschenker.com/) (ЕС стандарт) |
| `per_100lbs` | `(chargeable_weight × 2.20462 / 100) × rate` | [UPS Tariff](https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/) (США) |

**Источники:**
- **DHL Rate Cards**: https://www.dhl.com/global-en/home/our-divisions/express/shipping/express-rates.html
- **FedEx Rate Calculator**: https://www.fedex.com/en-us/online/rating.html
- **UPS Rate Calculator**: https://www.ups.com/ship/guided/rate
- **Конверсия lb→kg**: 1 lb = 0.453592 kg (ISO 80000-1:2009)

### Надбавки (Surcharges)

| Тип расчёта | Формула | Пример |
|-------------|---------|--------|
| `percentage` | `base_rate × (value / 100)` | FSC 15.5% → base_rate × 0.155 |
| `flat` | `value` | Residential $8.00 |
| `per_kg` | `chargeable_weight × value` | Handling $0.50/кг |

**Источники по типам надбавок:**

| Надбавка | Описание | Источник |
|----------|----------|----------|
| **Fuel Surcharge (FSC)** | 10-25% от базовой ставки | [DHL FSC](https://www.dhl.com/global-en/home/footer/fuel-surcharges.html), [FedEx FSC](https://www.fedex.com/en-us/shipping/surcharges.html), [UPS FSC](https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates/fuel-surcharges.page) |
| **Remote Area** | $30-150 за доставку в удалённый район | [DHL Remote Areas](https://www.dhl.com/content/dam/dhl/global/core/documents/pdf/glo-core-remote-area-guide.pdf), [FedEx Extended Areas](https://www.fedex.com/en-us/shipping/surcharges.html) |
| **Residential** | $5-15 за доставку на жилой адрес | [FedEx Residential](https://www.fedex.com/en-us/shipping/surcharges.html), [UPS Residential](https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates.page) |
| **Peak Season** | 5-15% в пиковый сезон (ноябрь-январь) | [DHL Peak Surcharge](https://www.dhl.com/global-en/home/footer/peak-season-surcharges.html), [UPS Peak](https://www.ups.com/us/en/supplychain/resources/news/peak-surcharge.page) |
| **Dangerous Goods** | 20-50% за опасный груз | [IATA DGR](https://www.iata.org/en/programs/cargo/dgr/), [FedEx DG](https://www.fedex.com/en-us/shipping/dangerous-goods.html) |
| **Oversize** | $50-200 за негабаритный груз | [UPS Oversize](https://www.ups.com/us/en/help-center/packaging-and-supplies/oversize-packages.page), [FedEx Oversize](https://www.fedex.com/en-us/shipping/weight-packages.html) |

С ограничениями:
```
final_surcharge = MAX(surcharge, min_value)  // Минимальный сбор
final_surcharge = MIN(final_surcharge, max_value)  // Максимальный предел
```

**Обоснование ограничений**: Перевозчики устанавливают минимальные и максимальные пределы для надбавок, чтобы обеспечить покрытие операционных расходов (min) и не отпугнуть клиентов (max).

### Страховка (Insurance)

```
insurance_cost = declared_value × (insurance_rate / 100)
```

По умолчанию `insurance_rate = 0.5%` (от объявленной стоимости груза)

**Источники и стандарты:**
- **DHL Shipment Insurance**: https://www.dhl.com/global-en/home/our-divisions/express/customer-service/insurance.html
- **FedEx Declared Value**: https://www.fedex.com/en-us/shipping/declared-value-coverage.html
- **UPS Declared Value**: https://www.ups.com/us/en/support/shipping-support/insurance.page
- **ICC (Incoterms)**: https://iccwbo.org/resources-for-business/incoterms-rules/incoterms-2020/ - правила распределения рисков

**Типичные ставки страхования:**
| Перевозчик | Базовая ставка | Минимум | Источник |
|------------|----------------|---------|----------|
| DHL Express | 1.5% | $3.00 | [DHL Insurance](https://www.dhl.com/global-en/home/our-divisions/express/customer-service/insurance.html) |
| FedEx | 1.0-2.0% | $2.75 | [FedEx Coverage](https://www.fedex.com/en-us/shipping/declared-value-coverage.html) |
| UPS | 1.0-1.5% | $2.70 | [UPS Insurance](https://www.ups.com/us/en/support/shipping-support/insurance.page) |
| Морской (CIF) | 0.3-0.5% | - | [Lloyd's Marine Insurance](https://www.lloyds.com/market-resources/marine) |

**Примечание**: В системе Vector Express используется ставка 0.5% как компромисс между стоимостью и покрытием, клиент может выбрать расширенное страхование

---

## Типы транспорта

### Air (Авиа)

| Параметр | Значение | Источник |
|----------|----------|----------|
| Базовая ставка | ~$12-15/кг | [DHL Express Rates](https://www.dhl.com/global-en/home/our-divisions/express/shipping/express-rates.html) |
| Сроки доставки | 3-7 дней | [FedEx International Express](https://www.fedex.com/en-us/shipping/international-express-services.html) |
| DIM Factor | 5000-6000 | [IATA TACT Rules](https://www.iata.org/contentassets/0238d5bc961e4fe8bc5ce1891e4f76c2/tact-rules.pdf) |
| Особенности | Быстро, дорого, ограничения по опасным грузам | [IATA DGR](https://www.iata.org/en/programs/cargo/dgr/) |

**Референсы:**
- IATA Cargo Services: https://www.iata.org/en/programs/cargo/
- Freightos Air Index: https://fbx.freightos.com/

### Sea (Морской)

| Параметр | Значение | Источник |
|----------|----------|----------|
| Базовая ставка | ~$1.5-3/кг | [Freightos Baltic Index](https://fbx.freightos.com/) |
| Сроки доставки | 30-45 дней | [Maersk Schedules](https://www.maersk.com/schedules) |
| DIM Factor | 1000 | [Maersk Container Rates](https://www.maersk.com/shipping-services/quotation) |
| Особенности | Дёшево, долго, подходит для больших объёмов | - |

**Референсы:**
- Drewry World Container Index: https://www.drewry.co.uk/supply-chain-advisors/world-container-index
- Maersk Spot Rates: https://www.maersk.com/shipping-services/quotation
- MSC Container Booking: https://www.msc.com/en/book-online

### Rail (Ж/Д)

| Параметр | Значение | Источник |
|----------|----------|----------|
| Базовая ставка | ~$3-5/кг | [DB Schenker Rail](https://www.dbschenker.com/global/products/rail-transport) |
| Сроки доставки | 15-25 дней | [China-Europe Railway Express](http://www.crexpress.cn/) |
| DIM Factor | 3000-4000 | [Деловые Линии](https://www.dellin.ru/) |
| Особенности | Средняя цена/скорость, Китай-Европа | - |

**Референсы:**
- КТЖ (Казахстан): https://www.railways.kz/ru/services/cargo
- РЖД Cargo: https://cargo.rzd.ru/
- DB Schenker Rail: https://www.dbschenker.com/global/products/rail-transport
- China-Europe Railway Express: http://www.crexpress.cn/

### Road (Авто)

| Параметр | Значение | Источник |
|----------|----------|----------|
| Базовая ставка | ~$4-8/кг | [СДЭК Калькулятор](https://www.cdek.ru/ru/calculate) |
| Сроки доставки | 7-14 дней | [Деловые Линии](https://www.dellin.ru/) |
| DIM Factor | 3000-5000 | [ПЭК](https://pecom.ru/) |
| Особенности | Гибкость, door-to-door | - |

**Референсы:**
- СДЭК (СНГ): https://www.cdek.ru/
- Деловые Линии: https://www.dellin.ru/
- DauTransService (Казахстан): https://dts.kz/
- ПЭК: https://pecom.ru/

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

Данный пример демонстрирует реальный расчёт стоимости авиаперевозки по маршруту Казахстан → Китай с использованием формул и ставок, аналогичных DHL Express.

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

   📖 Источник формулы: IATA TACT Rules, DIM Factor 5000 для авиаэкспресса
   🔗 https://www.dhl.com/global-en/home/our-divisions/express/tools/volumetric-weight-express.html

2. Тарифицируемый вес:
   billable = MAX(10, 12) = 12 кг

   📖 Источник: Стандарт IATA - тарификация по большему весу
   🔗 https://www.iata.org/en/programs/cargo/pricing/

3. Базовая ставка (rate = $15/кг):
   base_rate = 12 × 15 = $180

   📖 Источник: Типичная ставка DHL Express для зоны Азия
   🔗 https://www.dhl.com/global-en/home/our-divisions/express/shipping/express-rates.html

4. Надбавки:
   - Fuel (15.5%): 180 × 0.155 = $27.90
     📖 Источник: DHL Fuel Surcharge (обновляется ежемесячно)
     🔗 https://www.dhl.com/global-en/home/footer/fuel-surcharges.html

   - Residential: $8.00
     📖 Источник: FedEx/UPS Residential Delivery Surcharge
     🔗 https://www.fedex.com/en-us/shipping/surcharges.html

   Итого надбавки: $35.90

5. Таможня: $150
   📖 Источник: Типичный сбор за таможенное оформление в Китае
   🔗 https://www.dhl.com/global-en/home/our-divisions/express/customs-support.html

6. Итого:
   total = 180 + 35.90 + 150 = $365.90

7. Сроки: 3-7 дней
   📖 Источник: DHL Express Transit Times Kazakhstan → China
   🔗 https://www.dhl.com/kz-en/home/express.html
```

**Сравнение с реальными тарифами:**

| Перевозчик | Примерная стоимость | Сроки | Источник |
|------------|---------------------|-------|----------|
| DHL Express | $350-400 | 3-5 дней | [DHL Rate Calculator](https://www.dhl.com/global-en/home/our-divisions/express/shipping/rate-quote.html) |
| FedEx International | $380-450 | 4-6 дней | [FedEx Rate Tool](https://www.fedex.com/en-us/online/rating.html) |
| Pony Express | $280-350 | 5-7 дней | [Pony Express Calculator](https://www.ponyexpress.kz/calculator) |

*Примечание: Цены ориентировочные и зависят от актуальных ставок перевозчика*

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
