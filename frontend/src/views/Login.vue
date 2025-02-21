<template>
  <div
    class="min-h-screen flex items-center justify-center bg-white divide-y divide-gray-200 from-blue-50 to-slate-50 px-4 sm:px-0"
  >
    <div class="w-full max-w-md">
      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-100">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Welcome Back</h1>
          <p class="text-slate-500 text-sm">Sign in to your account to continue</p>
        </div>

        <!-- Login Form -->
        <form class="space-y-6" @submit.prevent="handleLogin">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2"> Email </label>
            <div class="relative">
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="Enter your email"
                class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all placeholder-slate-400 text-slate-700"
                :class="{ 'border-red-400': errors.email }"
              />
              <svg
                v-if="!errors.email"
                class="absolute right-3 top-3.5 h-5 w-5 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                />
              </svg>
            </div>
            <p v-if="errors.email" class="mt-2 text-sm text-red-600">
              {{ errors.email }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <div class="flex justify-between mb-2">
              <label for="password" class="block text-sm font-medium text-slate-700">
                Password
              </label>
              <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                Forgot password?
              </a>
            </div>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Enter your password"
                class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all placeholder-slate-400 text-slate-700 pr-12"
                :class="{ 'border-red-400': errors.password }"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    v-if="showPassword"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                  />
                  <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-2 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
              />
              <span class="text-sm text-slate-600">Remember me</span>
            </label>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex justify-center items-center py-3 px-4 bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-white font-semibold rounded-lg transition-all disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ loading ? 'Signing in...' : 'Sign In' }}
            </button>
          </div>

          <!-- Error Message -->
          <div
            v-if="loginError"
            class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
          >
            ⚠️ {{ loginError }}
          </div>
        </form>

        <!-- Footer Links -->
        <div class="mt-6 text-center text-sm text-slate-500">
          Don't have an account?
          <router-link to="/signup" class="font-medium text-blue-600 hover:text-blue-700">
            Sign up
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api' // Import your axios instance

const router = useRouter()
const loading = ref(false)
const loginError = ref('')
const showPassword = ref(false)

// Define `errors` as a reactive object
const errors = reactive({
  email: '',
  password: '',
})

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const handleLogin = async () => {
  loading.value = true
  loginError.value = ''
  errors.email = ''
  errors.password = ''

  try {
    const response = await api.post('/login', form)

    // Store the token
    localStorage.setItem('token', response.data.data.token)
    console.log('Token stored:', response.data.token) // Debugging

    // Redirect to dashboard AFTER storing the token
    router.push({ name: 'dashboard' })
  } catch (error) {
    if (error.response) {
      if (error.response.status === 422) {
        // Validation errors
        const validationErrors = error.response.data.errors
        if (validationErrors.email) {
          errors.email = validationErrors.email[0]
        }
        if (validationErrors.password) {
          errors.password = validationErrors.password[0]
        }
      } else {
        // General error
        loginError.value = error.response.data.message || 'An error occurred during login'
      }
    } else {
      loginError.value = 'Unable to connect to the server'
    }
  } finally {
    loading.value = false
  }
}
</script>
