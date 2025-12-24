/**
 * Geocoding Service
 * Hybrid approach: LocationIQ as primary (worldwide), with reverse geocoding fallback
 *
 * Free tiers:
 * - LocationIQ: 5,000 requests/day
 */

const LOCATIONIQ_API_KEY = import.meta.env.VITE_LOCATIONIQ_API_KEY || ''

// Debounce helper
let debounceTimer = null
const debounce = (fn, delay) => {
  return (...args) => {
    clearTimeout(debounceTimer)
    return new Promise((resolve) => {
      debounceTimer = setTimeout(async () => {
        resolve(await fn(...args))
      }, delay)
    })
  }
}

/**
 * Search addresses using LocationIQ
 * @param {string} query - Search query
 * @param {object} options - Search options
 * @returns {Promise<Array>} - Array of address suggestions
 */
async function searchLocationIQ(query, options = {}) {
  if (!LOCATIONIQ_API_KEY) {
    console.warn('LocationIQ API key not configured')
    return []
  }

  const params = new URLSearchParams({
    key: LOCATIONIQ_API_KEY,
    q: query,
    format: 'json',
    addressdetails: 1,
    limit: options.limit || 5,
    dedupe: 1
  })

  // Add country filter if specified
  if (options.countryCode) {
    params.append('countrycodes', options.countryCode)
  }

  // Add viewbox bias for Kazakhstan if no country specified
  if (!options.countryCode && options.biasToKZ !== false) {
    // Kazakhstan bounding box
    params.append('viewbox', '46.5,40.5,87.5,55.5')
    params.append('bounded', 0)
  }

  try {
    const response = await fetch(
      `https://us1.locationiq.com/v1/autocomplete?${params}`
    )

    if (!response.ok) {
      throw new Error(`LocationIQ error: ${response.status}`)
    }

    const data = await response.json()

    return data.map(item => ({
      id: item.place_id,
      displayName: item.display_name,
      address: formatAddress(item),
      lat: parseFloat(item.lat),
      lng: parseFloat(item.lon),
      country: item.address?.country || '',
      countryCode: item.address?.country_code?.toUpperCase() || '',
      city: item.address?.city || item.address?.town || item.address?.village || item.address?.state || '',
      street: item.address?.road || item.address?.street || '',
      houseNumber: item.address?.house_number || '',
      postcode: item.address?.postcode || '',
      raw: item
    }))
  } catch (error) {
    console.error('LocationIQ search error:', error)
    return []
  }
}

/**
 * Reverse geocoding - get address from coordinates
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {Promise<object|null>} - Address object or null
 */
async function reverseGeocode(lat, lng) {
  if (!LOCATIONIQ_API_KEY) {
    console.warn('LocationIQ API key not configured')
    return null
  }

  const params = new URLSearchParams({
    key: LOCATIONIQ_API_KEY,
    lat: lat,
    lon: lng,
    format: 'json',
    addressdetails: 1
  })

  try {
    const response = await fetch(
      `https://us1.locationiq.com/v1/reverse?${params}`
    )

    if (!response.ok) {
      throw new Error(`LocationIQ reverse error: ${response.status}`)
    }

    const item = await response.json()

    return {
      id: item.place_id,
      displayName: item.display_name,
      address: formatAddress(item),
      lat: parseFloat(item.lat),
      lng: parseFloat(item.lon),
      country: item.address?.country || '',
      countryCode: item.address?.country_code?.toUpperCase() || '',
      city: item.address?.city || item.address?.town || item.address?.village || item.address?.state || '',
      street: item.address?.road || item.address?.street || '',
      houseNumber: item.address?.house_number || '',
      postcode: item.address?.postcode || '',
      raw: item
    }
  } catch (error) {
    console.error('Reverse geocoding error:', error)
    return null
  }
}

/**
 * Format address from LocationIQ response
 */
function formatAddress(item) {
  const addr = item.address || {}
  const parts = []

  if (addr.house_number && addr.road) {
    parts.push(`${addr.road} ${addr.house_number}`)
  } else if (addr.road) {
    parts.push(addr.road)
  }

  const city = addr.city || addr.town || addr.village
  if (city) {
    parts.push(city)
  }

  if (addr.state && addr.state !== city) {
    parts.push(addr.state)
  }

  if (addr.country) {
    parts.push(addr.country)
  }

  return parts.join(', ')
}

/**
 * Main search function with debouncing
 */
const searchAddresses = debounce(async (query, options = {}) => {
  if (!query || query.length < 3) {
    return []
  }

  return await searchLocationIQ(query, options)
}, 300)

/**
 * Geocode a specific address string
 * @param {string} address - Full address string
 * @returns {Promise<object|null>} - Geocoded result or null
 */
async function geocodeAddress(address) {
  const results = await searchLocationIQ(address, { limit: 1 })
  return results.length > 0 ? results[0] : null
}

export {
  searchAddresses,
  reverseGeocode,
  geocodeAddress,
  searchLocationIQ
}
