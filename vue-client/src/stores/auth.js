import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'
import { disconnectEcho } from '../services/echo'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value)

  const setAuth = (userData, tokenValue) => {
    user.value = userData
    token.value = tokenValue
    localStorage.setItem('token', tokenValue)
  }

  const login = async (email, password) => {
    const response = await api.post('/auth/login', { email, password })
    setAuth(response.data.user, response.data.token)
    return response.data
  }

  const register = async (name, email, password, password_confirmation) => {
    const response = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation
    })
    setAuth(response.data.user, response.data.token)
    return response.data
  }

  const fetchUser = async () => {
    if (!token.value) return null
    try {
      const response = await api.get('/auth/me')
      user.value = response.data
      return response.data
    } catch {
      logout()
      return null
    }
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch {
    }
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    disconnectEcho()
  }

  if (token.value) {
    fetchUser()
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    fetchUser,
    logout
  }
})
