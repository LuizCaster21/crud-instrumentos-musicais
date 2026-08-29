const API_BASE_URL = 'http://localhost:8000/api';

class ApiService {
  /**
   * Retorna o token JWT salvo no localStorage
   */
  getToken() {
    return localStorage.getItem('devcaster_jwt_token');
  }

  /**
   * Define o token JWT no localStorage
   */
  setToken(token) {
    if (token) {
      localStorage.setItem('devcaster_jwt_token', token);
    } else {
      localStorage.removeItem('devcaster_jwt_token');
    }
  }

  /**
   * Executa requisições HTTP padronizadas
   */
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
    
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
      ...options,
      headers,
    };

    if (config.body && typeof config.body === 'object') {
      config.body = JSON.stringify(config.body);
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json().catch(() => null);

      if (!response.ok) {
        // Se o token expirou ou é inválido, limpa a sessão
        if (response.status === 401 && !endpoint.includes('/auth/login')) {
          this.setToken(null);
          window.location.reload();
        }
        
        const errorMsg = data?.message || `Erro ${response.status}: Falha na requisição.`;
        const error = new Error(errorMsg);
        error.status = response.status;
        error.data = data;
        throw error;
      }

      return data;
    } catch (error) {
      console.error(`[API Error] ${options.method || 'GET'} ${endpoint}:`, error);
      throw error;
    }
  }

  get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  post(endpoint, body) {
    return this.request(endpoint, { method: 'POST', body });
  }

  put(endpoint, body) {
    return this.request(endpoint, { method: 'PUT', body });
  }

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export const api = new ApiService();
