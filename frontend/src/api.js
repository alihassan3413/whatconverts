import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api', // Use Vite's environment variable
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Add a request interceptor to include the token in headers
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    console.log('Adding token to headers:', token) // Debugging
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
// Add a response interceptor to handle errors globally
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Check for 401 Unauthorized and avoid infinite loops
    if (error.response && error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true // Mark the request as retried

      try {
        // Call the refresh token endpoint
        const refreshResponse = await api.post('/refresh-token', {
          refreshToken: localStorage.getItem('refreshToken'),
        })

        // Update the token in localStorage
        localStorage.setItem('token', refreshResponse.data.token)

        // Retry the original request with the new token
        originalRequest.headers.Authorization = `Bearer ${refreshResponse.data.token}`
        return api(originalRequest)
      } catch (refreshError) {
        // If refresh fails, log out the user
        localStorage.removeItem('token')
        localStorage.removeItem('refreshToken')
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  },
)

export default api
