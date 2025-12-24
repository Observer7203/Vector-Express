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

*Research conducted: December 2025*
