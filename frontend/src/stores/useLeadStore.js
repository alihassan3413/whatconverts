import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

// Account configurations
const ACCOUNTS = {
  account1: {
    id: 'account1',
    name: 'Main Account',
    token: '6362-ac5646e8b7a691bc',
    secret: 'e3fe06878301dd5c1244e8db3225775a',
  },
  account2: {
    id: 'account2',
    name: 'Secondary Account',
    token: '8466-035cefafcf94d90f',
    secret: '3d17deb69503b6daf73e9bbcc682444d',
  },
}

const formatDate = (date) => {
  if (!date || typeof date !== 'string') {
    console.warn(`Invalid date provided: ${date}`)
    return null
  }
  const d = new Date(date)
  if (isNaN(d.getTime())) {
    console.warn(`Invalid date format: ${date}`)
    return null
  }
  return d.toISOString().split('T')[0]
}

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
  'lead_campaign',
  'spotted_keywords',
  'lead_keyword',
]

// Configure Axios with cache-busting headers
axios.defaults.headers.common['Cache-Control'] = 'no-cache, no-store, must-revalidate'
axios.defaults.headers.common['Pragma'] = 'no-cache'
axios.defaults.headers.common['Expires'] = '0'

export const useLeadStore = defineStore('lead', {
  state: () => ({
    leads: ref([]),
    isLoading: ref(false),
    error: ref(null),
    currentPage: ref(1),
    totalPages: ref(0),
    totalLeads: ref(0),
    leadsPerPage: ref(25),
    currentAccount: ref(ACCOUNTS.account1),
    clientMap: ref({}),
  }),

  getters: {
    formattedLeads: (state) => {
      return state.leads.map((lead) => {
        const formattedLead = {}
        LEAD_COLUMNS.forEach((column) => {
          if (column === 'date_created') {
            if (lead[column]) {
              const date = new Date(lead[column])
              formattedLead[column] = isNaN(date.getTime()) ? null : date.toLocaleString()
            } else {
              formattedLead[column] = null
            }
          } else if (column === 'quote_value' || column === 'sales_value') {
            formattedLead[column] = lead[column] ? parseFloat(lead[column]).toFixed(2) : null
          } else {
            formattedLead[column] = lead[column] || null
          }
        })
        return formattedLead
      })
    },

    columnHeaders: () => {
      return LEAD_COLUMNS.map((column) => ({
        key: column,
        label: column
          .split('_')
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' '),
      }))
    },

    availableAccounts: () => [
      { id: 'account1', name: 'Main Account' },
      { id: 'account2', name: 'Secondary Account' },
    ],
  },

  actions: {
    async switchAccount(accountId) {
      if (!accountId) {
        console.error('No accountId provided to switchAccount')
        return false
      }
      if (Object.keys(ACCOUNTS).includes(accountId)) {
        try {
          this.currentAccount = { ...ACCOUNTS[accountId] }
          console.log(
            `Switched to account: ${this.currentAccount.name} (ID: ${accountId}, Token: ${this.currentAccount.token})`,
          )
          this.leads = [] // Reset leads to avoid stale data
          return true
        } catch (error) {
          console.error('Error switching account:', error)
          return false
        }
      } else {
        console.warn(
          `Invalid account ID: ${accountId}. Available IDs: ${Object.keys(ACCOUNTS).join(', ')}`,
        )
        return false
      }
    },

    createApiClient(account) {
      const basicAuth = btoa(`${account.token}:${account.secret}`)
      const headers = {
        Authorization: `Basic ${basicAuth}`,
        Accept: 'application/json',
        'Cache-Control': 'no-cache',
      }
      return axios.create({
        baseURL: 'https://app.whatconverts.com/api/v1',
        headers,
      })
    },

    async fetchLeads(startDate, endDate, page = 1, leadsPerPage = 25) {
      this.isLoading = true
      this.error = null
      this.leads = []
      this.totalLeads = 0
      this.totalPages = 0
      this.currentPage = page

      const formattedStartDate = formatDate(startDate)
      const formattedEndDate = formatDate(endDate)

      if (!formattedStartDate || !formattedEndDate) {
        this.error = 'Invalid start or end date provided'
        console.error('Invalid dates:', { startDate, endDate })
        this.isLoading = false
        return
      }

      try {
        const api = this.createApiClient(this.currentAccount)
        const response = await api.get('/leads', {
          params: {
            start_date: formattedStartDate,
            end_date: formattedEndDate,
            page_number: page,
            leads_per_page: leadsPerPage,
            cache_buster: Date.now(),
          },
        })

        if (response.data && Array.isArray(response.data.leads)) {
          console.log(`Fetched ${response.data.leads.length} leads for ${this.currentAccount.name}`)
          this.leads = response.data.leads
          this.totalPages = response.data.total_pages || 1
          this.totalLeads = response.data.total_leads || this.leads.length
          this.currentPage = page
          console.log('Updated store state:', {
            leads: this.leads.length,
            totalLeads: this.totalLeads,
            totalPages: this.totalPages,
            currentPage: this.currentPage,
          })
        } else {
          throw new Error('Invalid data format received from API')
        }
      } catch (err) {
        this.handleError(err)
        console.error('Error in fetchLeads:', err)
      } finally {
        this.isLoading = false
      }
    },

    async fetchAllLeadsForExport(startDate, endDate, account) {
      this.isLoading = true
      this.error = null
      let allLeads = []

      try {
        const clientsMap = await this.fetchClients()
        console.log(`Fetching leads for account: ${account.name} (ID: ${account.id})`)
        const api = this.createApiClient(account)
        const firstPage = await api.get('/leads', {
          params: {
            start_date: formatDate(startDate),
            end_date: formatDate(endDate),
            page_number: 1,
            leads_per_page: 250,
            cache_buster: Date.now(),
          },
        })

        if (!firstPage.data || !Array.isArray(firstPage.data.leads)) {
          throw new Error('Invalid data format received from API')
        }

        const totalPages = firstPage.data.total_pages || 1
        allLeads = [...firstPage.data.leads]
        console.log(
          `Account ${account.name}: Fetched page 1/${totalPages}, ${allLeads.length} leads`,
        )

        if (totalPages > 1) {
          for (let page = 2; page <= totalPages; page++) {
            await new Promise((resolve) => setTimeout(resolve, 1000))
            const response = await api.get('/leads', {
              params: {
                start_date: formatDate(startDate),
                end_date: formatDate(endDate),
                page_number: page,
                leads_per_page: 250,
                cache_buster: Date.now(),
              },
            })

            if (response.data && Array.isArray(response.data.leads)) {
              allLeads = [...allLeads, ...response.data.leads]
              console.log(
                `Account ${account.name}: Fetched page ${page}/${totalPages}, ${allLeads.length} leads`,
              )
            }
          }
        }

        console.log(`Total leads for ${account.name}: ${allLeads.length}`)
        return allLeads.map((lead) => {
          const clientId = clientsMap[lead.account_id]
          return {
            account_id: clientId || lead.account_id,
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
            lead_campaign: lead.lead_campaign || '-',
            spotted_keywords: lead.spotted_keywords || '-',
            lead_keyword: lead.lead_keyword || '-',
          }
        })
      } catch (err) {
        this.handleError(err)
        console.error('Error in fetchAllLeadsForExport:', err)
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
        this.error = `Error: ${err.message || 'An unexpected error occurred'}`
        console.error('Error Details:', err)
      }
    },

    async updatePageSize(newSize) {
      this.leadsPerPage = newSize
      this.totalPages = Math.ceil(this.totalLeads / newSize)
    },

    async sendEmailFile(formData) {
      try {
        const response = await axios.post(
          `${import.meta.env.VITE_API_BASE_URL}/send-email`,
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-Data',
            },
          },
        )

        if (response.data.status == 'success') {
          alert('Email Sent Successfully')
        } else {
          throw new Error(response.data.message || 'Failed to send email')
        }
      } catch (error) {
        console.log('Error: ', error)
      }
    },

    async fetchClients() {
      if (Object.keys(this.clientMap).length > 0) {
        console.log('Returning cached clientMap')
        return this.clientMap
      }
      try {
        const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/clients`, {
          headers: {
            'Cache-Control': 'no-cache',
          },
        })
        if (response.data && response.data.status === 'success') {
          this.clientMap = response.data.data.reduce((map, client) => {
            map[client.what_converts_id] = client.client_id
            return map
          }, {})
          console.log('Fetched clientMap:', this.clientMap)
          return this.clientMap
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
