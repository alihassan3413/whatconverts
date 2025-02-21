// stores/useLeadStore.js
import { defineStore } from 'pinia'
import axios from 'axios'

const API_TOKEN = '6362-ac5646e8b7a691bc'
const API_SECRET = 'e3fe06878301dd5c1244e8db3225775a'
const basicAuth = btoa(`${API_TOKEN}:${API_SECRET}`)

const formatDate = (date) => {
  if (!date) return null
  const d = new Date(date)
  return d.toISOString().split('T')[0]
}

// Define the columns we want to display
const LEAD_COLUMNS = [
  'account_id',
  'account',
  'profile_id',
  'profile',
  'lead_id',
  'lead_type',
  'lead_status',
  'date_created',
  'quotable',
  'quote_value',
  'sales_value',
  'lead_source',
  'lead_medium',
]

const api = axios.create({
  baseURL: 'https://app.whatconverts.com/api/v1',
  headers: {
    Authorization: `Basic ${basicAuth}`,
    Accept: 'application/json',
  },
})

export const useLeadStore = defineStore('lead', {
  state: () => ({
    leads: [],
    isLoading: false,
    error: null,
    currentPage: 1,
    totalPages: 0,
    totalLeads: 0,
    leadsPerPage: 25,
  }),

  getters: {
    // Format leads data to only include required fields
    formattedLeads: (state) => {
      return state.leads.map((lead) => {
        const formattedLead = {}
        LEAD_COLUMNS.forEach((column) => {
          if (column === 'date_created') {
            formattedLead[column] = lead[column] ? new Date(lead[column]).toLocaleString() : null
          } else if (column === 'quote_value' || column === 'sales_value') {
            formattedLead[column] = lead[column] ? parseFloat(lead[column]).toFixed(2) : null
          } else {
            formattedLead[column] = lead[column] || null
          }
        })
        return formattedLead
      })
    },

    // Get displayable column headers
    columnHeaders: () => {
      return LEAD_COLUMNS.map((column) => ({
        key: column,
        label: column
          .split('_')
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' '),
      }))
    },
  },

  actions: {
    async fetchLeads(startDate, endDate, page = 1, leadsPerPage = 25) {
      this.isLoading = true
      this.error = null

      try {
        const response = await api.get('/leads', {
          params: {
            start_date: formatDate(startDate),
            end_date: formatDate(endDate),
            page_number: page,
            leads_per_page: leadsPerPage,
          },
        })

        if (response.data && Array.isArray(response.data.leads)) {
          this.leads = response.data.leads
          this.totalPages = response.data.total_pages || 1
          this.totalLeads = response.data.total_leads || this.leads.length
          this.currentPage = page
        } else {
          throw new Error('Invalid data format received from API')
        }
      } catch (err) {
        this.handleError(err)
      } finally {
        this.isLoading = false
      }
    },

    async fetchAllLeadsForExport(startDate, endDate) {
      this.isLoading = true
      this.error = null
      let allLeads = []

      try {
        // Fetch clients data
        const clientsMap = await this.fetchClients()
        console.log('Clients Map:', clientsMap)

        // Fetch leads data
        const firstPage = await api.get('/leads', {
          params: {
            start_date: formatDate(startDate),
            end_date: formatDate(endDate),
            page_number: 1,
            leads_per_page: 250, // Fetch maximum leads per page
          },
        })

        if (!firstPage.data || !Array.isArray(firstPage.data.leads)) {
          throw new Error('Invalid data format received from API')
        }

        const totalPages = firstPage.data.total_pages || 1
        allLeads = [...firstPage.data.leads]

        if (totalPages > 1) {
          for (let page = 2; page <= totalPages; page++) {
            // Add a delay between requests to avoid rate limiting
            await new Promise((resolve) => setTimeout(resolve, 1000)) // 1-second delay

            const response = await api.get('/leads', {
              params: {
                start_date: formatDate(startDate),
                end_date: formatDate(endDate),
                page_number: page,
                leads_per_page: 250, // Fetch maximum leads per page
              },
            })

            if (response.data && Array.isArray(response.data.leads)) {
              allLeads = [...allLeads, ...response.data.leads]
            }
          }
        }

        // Format all leads for export
        return allLeads.map((lead) => {
          // Check if account_id matches any what_converts_id in clients data
          const clientId = clientsMap[lead.account_id]

          return {
            account_id: clientId || lead.account_id, // Use client_id if found, otherwise use account_id
            account: lead.account,
            profile_id: lead.profile_id,
            profile: lead.profile,
            lead_id: lead.lead_id,
            lead_type: lead.lead_type,
            lead_status: lead.lead_status,
            date_created: lead.date_created
              ? new Date(lead.date_created).toISOString().split('T')[0]
              : 'Invalid Date',
            quotable: lead.quotable,
            quote_value: lead.quote_value,
            sales_value: lead.sales_value,
            lead_source: lead.lead_source,
            lead_medium: lead.lead_medium,
          }
        })
      } catch (err) {
        this.handleError(err)
        return []
      } finally {
        this.isLoading = false
      }
    },
    handleError(err) {
      if (err.response) {
        this.error = `API Error: ${err.response.status} - ${err.response.data?.message || 'Unknown error'}`
        console.warn('API Error Details:', {
          status: err.response.status,
          data: err.response.data,
          headers: err.response.headers,
        })
      } else if (err.request) {
        this.error = 'No response received from server'
        console.error('No response received:', err.request)
      } else {
        this.error = err.message || 'An unexpected error occurred'
        console.error('Error:', err.message)
      }
    },

    async updatePageSize(newSize) {
      this.leadsPerPage = newSize
      this.totalPages = Math.ceil(this.totalLeads / newSize)
    },

    async fetchClients() {
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/clients`)
        if (response.data && response.data.status === 'success') {
          // Create a map for quick lookup: { what_converts_id: client_id }
          return response.data.data.reduce((map, client) => {
            map[client.what_converts_id] = client.client_id
            return map
          }, {})
        } else {
          throw new Error('Invalid data format received from clients API')
        }
      } catch (err) {
        console.error('Error fetching clients data:', err)
        throw err
      }
    },
  },
})
