import { api } from './api.js';

const USER_STORAGE_KEY = 'devcaster_user_info';

class AuthService {
  /**
   * Realiza login e armazena o token e dados do usuário
   */
  async login(email, senha) {
    const response = await api.post('/auth/login', { email, senha });
    
    if (response.data?.token) {
      api.setToken(response.data.token);
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(response.data.usuario));
    }
    
    return response.data;
  }

  /**
   * Cadastra um novo usuário
   */
  async register(nome, email, senha) {
    const response = await api.post('/auth/register', { nome, email, senha });
    return response.data;
  }

  /**
   * Obtém o perfil do usuário logado via API
   */
  async getMe() {
    try {
      const response = await api.get('/auth/me');
      if (response.data?.usuario) {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(response.data.usuario));
        return response.data.usuario;
      }
      return null;
    } catch {
      return null;
    }
  }

  /**
   * Retorna os dados do usuário salvos no localStorage
   */
  getUser() {
    const user = localStorage.getItem(USER_STORAGE_KEY);
    try {
      return user ? JSON.parse(user) : null;
    } catch {
      return null;
    }
  }

  /**
   * Verifica se o usuário possui sessão ativa
   */
  isAuthenticated() {
    return !!api.getToken();
  }

  /**
   * Encerra a sessão do usuário
   */
  logout() {
    api.setToken(null);
    localStorage.removeItem(USER_STORAGE_KEY);
  }
}

export const auth = new AuthService();
