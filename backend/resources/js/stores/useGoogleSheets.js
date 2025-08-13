import { defineStore } from 'pinia'
import api from '../api'

export const useGoogleSheets = defineStore('googleSheets', {
  state: () => ({
    isLoading: false,
    error: null,
  }),

  actions: {
    async fetchSheets() {
      this.isLoading = true
      try {
        await api.get('/fetch-google-sheet')
      } catch (error) {
        this.error = error
        throw error
      } finally {
        this.isLoading = false
      }
    },
  },
})
