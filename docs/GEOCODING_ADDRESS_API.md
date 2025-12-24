# Geocoding & Address Autocomplete APIs Research

## Overview

Research on free/affordable geocoding and address autocomplete APIs for Kazakhstan, CIS countries, and worldwide coverage for door-to-door domestic shipments.

---

## Best Options for Kazakhstan & CIS

### 1. DaData ⭐ (Best for CIS)

**Website:** https://dadata.ru/api/suggest/address/

**Coverage:**
- **Russia** — full address down to apartment
- **Kazakhstan, Belarus, Uzbekistan** — down to house number (from OpenStreetMap)
- **Other countries** — down to city level (from GeoNames, cities 1000+ population)

**Free Tier:**
- **10,000 requests/day** (combined across all suggestion types)
- Shows DaData logo in dropdown (removed on paid plans)

**Features:**
- Address suggestions as you type
- Company search for Kazakhstan (legal entities, sole proprietors)
- Uses OpenStreetMap identifier (fias_id) for KZ/BY/UZ addresses

**How to Enable International:**
```json
{
  "query": "Алматы",
  "locations": [{ "country": "*" }]
}
```

**Limitations:**
- Address standardization only for Russian addresses
- KZ addresses are from OpenStreetMap (may have gaps)

---

### 2. Yandex Maps API (Geocoder + Geosuggest)

**Website:** https://yandex.ru/maps-api/products/geocoder-api

**Coverage:**
- Russia, Kazakhstan, Belarus, Ukraine, Georgia, Armenia, Azerbaijan, Kyrgyzstan, Tajikistan, Uzbekistan, Moldova, Turkey, Abkhazia

**Free Tier (до ноября 2024):**
- 25,000 requests/day total
- 1,000 requests/day for HTTP Geocoder
- 50 RPS limit

**⚠️ WARNING (November 2024 update):**
- Yandex tightened policy — keys get blocked for minor violations
- Some users report blocking after just 100 requests/day
- Makes free tier practically unusable

**Paid Plans:**
- Starting from 16,000 RUB/month (~$160)
- Not cost-effective for small projects

**Features:**
- AI-powered geocoder (handles typos, abbreviations)
- Supports Kazakh (Cyrillic + Latin), Russian, English
- Supports KATO classifier (Kazakhstan administrative codes)
- Geosuggest for autocomplete

**Verdict:** Good quality but expensive and risky free tier

---

### 3. 2GIS API

**Website:** https://dev.2gis.ru/

**Coverage:**
- Russia, Kazakhstan, UAE, Bahrain, Saudi Arabia, Qatar, Oman, Kuwait, Egypt, Cyprus, Italy, Chile, Kyrgyzstan, Uzbekistan, Azerbaijan

**Free Tier:**
- Demo key available for testing
- Students and non-profit projects can get extended demo access

**Features:**
- Geocoder API (forward and reverse)
- Address suggestions
- Detailed building/organization info (paid extra)

**Limitations:**
- Requires account registration
- Some features only on paid plans
- Demo key has time limits

---

## Worldwide Coverage (with Free Tiers)

### 4. LocationIQ ⭐ (Best Free Worldwide)

**Website:** https://locationiq.com/

**Free Tier:**
- **5,000 requests/day**
- 2 RPS rate limit
- Commercial use allowed (with attribution link)

**Paid Plans:**
- $49/month — 10,000 requests/day, 15 RPS
- $950/month — 1,000,000 requests/day

**Features:**
- Forward & reverse geocoding
- **Autocomplete API** ✅
- Map tiles (raster & vector)
- Routing & distance matrix
- Data from OpenStreetMap + OpenAddresses

**Special Programs:**
- 50% off for startups (<2 years old)
- Special pricing for students & non-profits

---

### 5. Geoapify

**Website:** https://www.geoapify.com/address-autocomplete/

**Free Tier:**
- **3,000 requests/day**
- Zero cost to start

**Features:**
- Address autocomplete with suggestions
- Forward & reverse geocoding
- Zero-dependency JavaScript library
- Framework-agnostic

**GitHub:** https://github.com/geoapify/geocoder-autocomplete

---

### 6. PlaceKit

**Website:** https://placekit.io/

**Free Tier:**
- **10,000 requests/month**

**Features:**
- Worldwide places search
- Address autofill
- Two-way geocoding
- Hand-curated OpenStreetMap data + country-specific datasets

---

### 7. Photon (Self-Hosted) ⭐ (Best Free Unlimited)

**Website:** https://photon.komoot.io/

**GitHub:** https://github.com/komoot/photon

**Cost:** FREE (self-hosted) or use public API with limits

**Features:**
- Open-source geocoder for OpenStreetMap
- **Search-as-you-type (autocomplete)** ✅
- Multilingual support
- Elasticsearch-based

**Public API:**
- Free but throttled for extensive usage
- No guarantee of availability

**Self-Hosted Requirements:**
- ~220GB disk space (planet-wide database)
- 64GB RAM recommended
- SSD/NVME storage recommended
- Weekly database dumps available from GraphHopper

**Docker Setup:**
```bash
docker run -p 2322:2322 komoot/photon
```

**Cost Comparison:**
- Google Maps: $2,100/month for 500K requests
- Self-hosted Photon: ~$40/month server cost

---

### 8. Nominatim (OpenStreetMap)

**Website:** https://nominatim.org/

**Public API:**
- Free but **autocomplete is NOT allowed**
- Max 1 request/second
- Gets banned after 1,500-3,000 requests without delays

**Self-Hosted:**
- Unlimited requests
- Full control
- Requires significant server resources

**Verdict:** Not suitable for autocomplete on public instance

---

## Comparison Table

| Service | Free Limit | Autocomplete | Kazakhstan | CIS | Worldwide | Best For |
|---------|-----------|--------------|------------|-----|-----------|----------|
| **DaData** | 10K/day | ✅ | ✅ House | ✅ House | City only | CIS focus |
| **Yandex** | 1K/day* | ✅ | ✅ Full | ✅ Full | ❌ | CIS (risky) |
| **2GIS** | Demo | ✅ | ✅ Full | Partial | ❌ | KZ cities |
| **LocationIQ** | 5K/day | ✅ | ✅ OSM | ✅ OSM | ✅ | Best free worldwide |
| **Geoapify** | 3K/day | ✅ | ✅ OSM | ✅ OSM | ✅ | Simple integration |
| **PlaceKit** | 10K/month | ✅ | ✅ OSM | ✅ OSM | ✅ | Curated data |
| **Photon** | Unlimited* | ✅ | ✅ OSM | ✅ OSM | ✅ | Self-hosted |

*Yandex free tier unreliable since Nov 2024
*Photon unlimited when self-hosted

---

## Recommendation for Vector Express

### For Domestic Kazakhstan Shipments (Door-to-Door):

**Option A: DaData (Easiest)**
- 10K requests/day free
- Good Kazakhstan coverage (down to house)
- Easy JavaScript widget
- Russian/Kazakh language support

**Option B: LocationIQ + 2GIS Hybrid**
- LocationIQ for general geocoding (5K/day free)
- 2GIS for detailed Kazakhstan city addresses
- Better building/entrance details from 2GIS

### For International Shipments:

**LocationIQ** — best free worldwide coverage with autocomplete

### For Scale (Production):

**Self-hosted Photon**
- $40/month server vs $2,100/month Google Maps
- Unlimited requests
- Full control
- OpenStreetMap data quality

---

## Implementation Architecture

```
frontend/
├── src/
│   ├── components/
│   │   └── AddressAutocomplete.vue    # Reusable component
│   └── services/
│       └── geocoding.js               # API service layer

backend/
├── app/
│   └── Services/
│       └── Geocoding/
│           ├── GeocodingServiceInterface.php
│           ├── DaDataService.php
│           ├── LocationIQService.php
│           └── GeocodingFactory.php
```

### Frontend Component Features:
- Debounced input (300ms)
- Minimum 3 characters before search
- Dropdown with address suggestions
- Coordinates extraction on selection
- Language detection (RU/KZ/EN)

### Backend Validation:
- Verify coordinates are within service area
- Cache geocoding results (Redis)
- Fallback between providers
- Rate limiting per user

---

## Resources

- [DaData API Docs](https://dadata.ru/api/suggest/address/)
- [DaData International Support](https://support.dadata.ru/knowledge-bases/4/articles/3424-mezhdunarodnyie-podskazki-i-inostrannyie-goroda)
- [Yandex Geocoder API](https://yandex.ru/maps-api/products/geocoder-api)
- [Yandex API Pricing](https://yandex.ru/maps-api/tariffs)
- [2GIS API](https://dev.2gis.ru/)
- [LocationIQ](https://locationiq.com/)
- [LocationIQ Pricing](https://locationiq.com/pricing)
- [Geoapify](https://www.geoapify.com/address-autocomplete/)
- [PlaceKit](https://placekit.io/)
- [Photon GitHub](https://github.com/komoot/photon)
- [Nominatim](https://nominatim.org/)

---

## User Reviews & Accuracy Analysis

### Accuracy Testing Results (Habr.com 2020 Study)

Independent test of 6 geocoders across 15 addresses (Russia, Kazakhstan, Belarus, Ukraine, Hungary, USA):

**Scoring:** 3 points = exact house, 2 = neighboring house, 1 = street level, 0 = miss

| Geocoder | Russia Score | Max Points | Accuracy |
|----------|-------------|------------|----------|
| **Yandex** | 20/21 | 21 | **95.2%** |
| **Google** | 19/21 | 21 | **90.5%** |
| **DaData** | 17/21 | 21 | **81.0%** |
| OSM/Nominatim | Lower | 21 | ~70% |
| GraphHopper | Lower | 21 | ~60% |
| MapBox | Lowest | 21 | ~50% |

**Key Finding:** Yandex best for Russia/CIS, Google best for international, DaData good but OSM-dependent.

---

### DaData — Reviews & Accuracy

**Coverage (official):**
- Moscow: 97% houses, 95% streets
- St. Petersburg: 91% houses, 94% streets
- Other major cities: 69% houses, 81% streets
- Rest of Russia: 47% houses, 70% streets

**Pros:**
- ✅ Stated accuracy: 99.99% (used by banks)
- ✅ Quality code (qc_geo) shows exact precision level
- ✅ Doesn't hide low accuracy — honestly reports if only street/city found
- ✅ 10K free requests/day

**Cons:**
- ⚠️ KZ/BY/UZ data from OpenStreetMap (gaps in rural areas)
- ⚠️ No address standardization for non-Russian addresses
- ⚠️ Shows logo on free tier

**Source:** [DaData Blog](https://dadata.ru/blog/), [Mappa Logistics](https://mappa-logistics.ru/)

---

### Yandex Geocoder — Reviews & Problems

**Pros:**
- ✅ Best accuracy for Russia/CIS (95%+ in tests)
- ✅ AI-powered — handles typos, abbreviations, mixed languages
- ✅ Supports KATO (Kazakhstan administrative codes)
- ✅ Kazakh language (Cyrillic + Latin)

**Cons (CRITICAL):**
- ❌ **November 2024:** Free tier became unusable
- ❌ Keys blocked for exceeding ~100 requests/day (not 1000 as stated)
- ❌ 50% timeout rate reported by some users
- ❌ Paid plans: from 16,000 RUB/month (~$160) — expensive
- ❌ Cache only 30 days allowed

**User Complaints:**
> "Ключ блокируется за малейшее нарушение, что делает его практически бесполезным"
> "Запросы стали завершаться по timeout почти в 50% случаев"

**Verdict:** ⛔ Not recommended for new projects unless paying

**Source:** [Yandex API Blog](https://yandex.ru/blog/mapsapi/), [Pobedasoft](https://pobedasoft.ru/api-key-yandex/)

---

### 2GIS — Reviews & Accuracy

**Pros:**
- ✅ 95-99% accuracy in covered cities
- ✅ Staff physically verify addresses (unique feature)
- ✅ Detailed building info (entrances, floors)
- ✅ Organization search included
- ✅ Handles typos well

**Cons:**
- ⚠️ Only works in 2GIS cities (~11,000 locations)
- ⚠️ No rural coverage
- ⚠️ Demo key has time limits
- ⚠️ Paid for production use

**Source:** [2GIS Dev](https://dev.2gis.ru/), [Navixy](https://www.navixy.ru/blog/choose-geocoder/)

---

### LocationIQ — Reviews & Ratings

**Capterra Rating:** ⭐ 4.9/5 (131 reviews)
- Ease of use: 4.9/5
- Value for money: 4.9/5
- Customer support: 4.9/5
- Functionality: 4.8/5

**Pros:**
- ✅ "JSON response is amazingly fast as well as accurate"
- ✅ "Saving 90% compared to Google Maps"
- ✅ "Service just works all the time"
- ✅ Generous free tier (5K/day)
- ✅ Good documentation

**Cons:**
- ⚠️ "Accuracy lacks in dense urban areas — address returned was 2 streets away"
- ⚠️ "Post codes are not always correct"
- ⚠️ "Data quality in rural regions is weak"
- ⚠️ "Not same quality as Google in Germany"

**Verdict:** ✅ Best value for money, but verify accuracy for your region

**Source:** [Capterra](https://www.capterra.com/p/164206/LocationIQ/reviews/), [G2](https://www.g2.com/products/locationiq/reviews)

---

### PlaceKit — Reviews

**Product Hunt Rating:** Positive reviews

**Pros:**
- ✅ "100% accurate autocomplete" for French addresses (bis/ter complements)
- ✅ "Found location in very remote area where Google cannot"
- ✅ Successor of Algolia Places
- ✅ Sub-50ms response time

**Testing vs Google:**
- 1,000,000 test queries: 0.006% error rate
- All 60 failed tests traced back to Google being incorrect

**Cons:**
- ⚠️ Some issues with "new or unknown locations"
- ⚠️ Only 10K requests/month on free tier

**Source:** [Product Hunt](https://www.producthunt.com/products/placekit/reviews), [PlaceKit Blog](https://placekit.io/blog/)

---

### Geoapify — Reviews

**Pros:**
- ✅ Building-level matching in Europe/North America
- ✅ Confidence scores help assess accuracy
- ✅ Zero-dependency JS library
- ✅ 3K requests/day free

**Cons:**
- ⚠️ Limited independent reviews available
- ⚠️ Street-level only in less detailed regions
- ⚠️ "No service guarantees 100% precision"

**Source:** [Geoapify Docs](https://www.geoapify.com/geocoding-api/)

---

### Photon — Reviews

**Pros:**
- ✅ Free & open-source
- ✅ Typo tolerance
- ✅ Search-as-you-type
- ✅ Multilingual
- ✅ Self-hosted = unlimited

**Cons:**
- ⚠️ Public API throttled & unreliable
- ⚠️ Self-hosting requires 64GB RAM, 220GB disk
- ⚠️ OSM data quality varies by region

**Source:** [GitHub](https://github.com/komoot/photon), [Geoapify Comparison](https://www.geoapify.com/nominatim-vs-photon-geocoder/)

---

## Summary: Reviews Count & Ratings

| Service | Reviews | Rating | Accuracy | Recommended |
|---------|---------|--------|----------|-------------|
| **LocationIQ** | 131 (Capterra) | ⭐ 4.9/5 | Good (urban issues) | ✅ Yes |
| **DaData** | N/A | N/A | 81% (test), 99.99% (claimed) | ✅ Yes (CIS) |
| **Yandex** | N/A | N/A | 95% (test) | ⛔ No (blocking issues) |
| **2GIS** | N/A | N/A | 95-99% (cities only) | ✅ Yes (KZ cities) |
| **PlaceKit** | Few (Product Hunt) | Positive | High (beat Google in tests) | ✅ Yes |
| **Geoapify** | Few | N/A | Good (Europe/NA) | ✅ Yes |
| **Photon** | N/A | N/A | OSM-dependent | ✅ Yes (self-hosted) |

---

## Final Recommendation for Vector Express

Based on reviews and accuracy:

1. **Primary (KZ domestic):** DaData — good accuracy, honest quality codes, 10K/day free
2. **Fallback (worldwide):** LocationIQ — 4.9/5 rating, 5K/day free, proven reliability
3. **Avoid:** Yandex — blocking issues since Nov 2024
4. **Future (scale):** Self-hosted Photon — unlimited, $40/month server

---

## Resources

- [Habr Geocoder Test](https://habr.com/ru/articles/505500/)
- [Mappa Geocoder Comparison](https://mappa-logistics.ru/tpost/bmgh0h3z81-sravnenie-geokoderov-kakoi-luchshe-vibra)
- [Navixy Geocoder Guide](https://www.navixy.ru/blog/choose-geocoder/)
- [LocationIQ Reviews - Capterra](https://www.capterra.com/p/164206/LocationIQ/reviews/)
- [LocationIQ Reviews - G2](https://www.g2.com/products/locationiq/reviews)
- [PlaceKit Reviews - Product Hunt](https://www.producthunt.com/products/placekit/reviews)
- [DaData API Docs](https://dadata.ru/api/suggest/address/)
- [DaData International Support](https://support.dadata.ru/knowledge-bases/4/articles/3424-mezhdunarodnyie-podskazki-i-inostrannyie-goroda)
- [Yandex Geocoder API](https://yandex.ru/maps-api/products/geocoder-api)
- [Yandex API Pricing](https://yandex.ru/maps-api/tariffs)
- [2GIS API](https://dev.2gis.ru/)
- [LocationIQ](https://locationiq.com/)
- [LocationIQ Pricing](https://locationiq.com/pricing)
- [Geoapify](https://www.geoapify.com/address-autocomplete/)
- [PlaceKit](https://placekit.io/)
- [Photon GitHub](https://github.com/komoot/photon)
- [Nominatim](https://nominatim.org/)

---

*Research conducted: December 2025*
