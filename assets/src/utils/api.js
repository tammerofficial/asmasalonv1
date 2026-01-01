import axios from 'axios';

// Get REST API base URL
function getBaseUrl() {
  // Preferred: Asmaa Salon injected URL
  if (window.AsmaaSalonConfig?.restUrl) {
    return window.AsmaaSalonConfig.restUrl;
  }

  // Fallback: derive from current location
  const origin = window.location.origin || '';
  const pathname = window.location.pathname || '';
  const match = pathname.match(/^(.*?)(\/wp-admin|\/wp-content|\/wp-json|\/$)/);
  const sitePath = match && match[1] ? match[1] : '';

  return `${origin.replace(/\/$/, '')}${sitePath}/wp-json/asmaa-salon/v1/`;
}

// Get nonce
function getRestNonce() {
  if (window.AsmaaSalonConfig?.nonce) {
    return window.AsmaaSalonConfig.nonce;
  }
  
  if (window.wpApiSettings?.nonce) {
    return window.wpApiSettings.nonce;
  }
  
  console.warn('Asmaa Salon: REST nonce not found. Using fallback authentication.');
  return '';
}

// Create axios instance
const api = axios.create({
  baseURL: getBaseUrl(),
  headers: {
    'X-WP-Nonce': getRestNonce(),
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// In-memory cache
const responseCache = new Map();
const cacheExpiration = new Map();
const DEFAULT_CACHE_TTL = 5 * 60 * 1000; // 5 minutes

// Request deduplication
const pendingRequests = new Map();

function getCacheKey(config) {
  const method = (config.method || 'get').toLowerCase();
  const url = config.url || '';
  const params = config.params || {};
  const data = config.data || {};
  
  if (method !== 'get') {
    return null;
  }

  // Allow disabling cache per-request
  if (config.noCache === true) {
    return null;
  }
  
  return `${method}:${url}:${JSON.stringify(params)}:${JSON.stringify(data)}`;
}

function isCacheValid(key) {
  const expiration = cacheExpiration.get(key);
  if (!expiration) return false;
  return Date.now() < expiration;
}

function setCache(key, data, ttl = DEFAULT_CACHE_TTL) {
  responseCache.set(key, data);
  cacheExpiration.set(key, Date.now() + ttl);
}

function getCache(key) {
  if (isCacheValid(key)) {
    return responseCache.get(key);
  }
  responseCache.delete(key);
  cacheExpiration.delete(key);
  return null;
}

export function clearCache(pattern = null) {
  if (!pattern) {
    responseCache.clear();
    cacheExpiration.clear();
    return;
  }
  
  for (const key of responseCache.keys()) {
    if (key.includes(pattern)) {
      responseCache.delete(key);
      cacheExpiration.delete(key);
    }
  }
}

// Request interceptor
api.interceptors.request.use(
  (config) => {
    if (!config.headers['X-WP-Nonce']) {
      const nonce = getRestNonce();
      if (nonce) {
        config.headers['X-WP-Nonce'] = nonce;
      }
    }
    
    if (config.withCredentials === undefined) {
      config.withCredentials = true;
    }
    
    if (['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase())) {
      return config;
    }
    
    const cacheKey = getCacheKey(config);
    if (!cacheKey) {
      return config;
    }
    
    const cached = getCache(cacheKey);
    if (cached) {
      return {
        ...config,
        adapter: () => Promise.resolve({
          data: cached,
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
          fromCache: true,
        }),
      };
    }
    
    if (pendingRequests.has(cacheKey)) {
      return pendingRequests.get(cacheKey);
    }
    
    const requestPromise = Promise.resolve(config);
    pendingRequests.set(cacheKey, requestPromise);
    
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  (response) => {
    const config = response.config;
    
    if (['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase())) {
      return response;
    }
    
    if (response.fromCache) {
      return response;
    }
    
    if (config.method?.toLowerCase() === 'get' && response.status === 200) {
      const cacheKey = getCacheKey(config);
      if (cacheKey) {
        const cacheControl = response.headers['cache-control'];
        let ttl = DEFAULT_CACHE_TTL;
        
        if (cacheControl) {
          const maxAgeMatch = cacheControl.match(/max-age=(\d+)/);
          if (maxAgeMatch) {
            ttl = parseInt(maxAgeMatch[1]) * 1000;
          }
        }
        
        setCache(cacheKey, response.data, ttl);
      }
    }
    
    const cacheKey = getCacheKey(config);
    if (cacheKey) {
      pendingRequests.delete(cacheKey);
    }
    
    return response;
  },
  (error) => {
    if (error.config) {
      const cacheKey = getCacheKey(error.config);
      if (cacheKey) {
        pendingRequests.delete(cacheKey);
      }
    }
    
    if (error.response) {
      const status = error.response.status;
      const url = error.config?.url || 'unknown';
      const method = error.config?.method || 'unknown';

      // Avoid spamming console errors for expected validation/UX flows (like 400 on redeem).
      // Keep logging for auth errors and server failures.
      const shouldLog = status >= 500 || status === 401 || status === 403;

      if (shouldLog) {
        console.error(`Asmaa Salon API Error [${status}]:`, {
          url: `${method.toUpperCase()} ${url}`,
          status,
          statusText: error.response.statusText,
          data: error.response.data,
        });
      }
      
      // Handle 403 - try refresh nonce
      if (status === 403) {
        const config = error.config;
        const retryCount = config.retryCount || 0;
        
        if (retryCount === 0) {
          const freshNonce = getRestNonce();
          if (freshNonce && freshNonce !== config.headers['X-WP-Nonce']) {
            config.headers['X-WP-Nonce'] = freshNonce;
            config.retryCount = 1;
            return api(config);
          }
        }
      }
    }
    
    return Promise.reject(error);
  }
);

// Prefetch function for background data loading
export async function prefetch(endpoint, params = {}) {
  try {
    const response = await api.get(endpoint, {
      params: {
        ...params,
        per_page: params.per_page || 50,
      },
      noCache: false, // Use cache for prefetch
    });
    return response;
  } catch (error) {
    // Silent error handling for prefetch
    console.error(`Prefetch error for ${endpoint}:`, error);
    return { data: null };
  }
}

export default api;
