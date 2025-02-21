<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Replace this -->
    <div
      v-if="loading"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <!-- With this -->
    <LoadingBar :is-loading="loading" />

    <div class="max-w-[1600px] mx-auto p-6">
      <!-- Header Section -->
      <header class="mb-8">
        <div
          class="flex justify-between items-center gap-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4"
        >
          <!-- Logo and Title Section -->
          <div class="flex items-center gap-3">
            <div class="bg-blue-600 text-white p-2 rounded-lg">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-6 h-6"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"
                />
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-semibold text-gray-900">Leads Overview</h1>
              <p class="text-sm text-gray-500">{{ formatDateRange(startDate, endDate) }}</p>
            </div>
          </div>

          <!-- Controls and Logout -->
          <div class="flex items-center gap-4">
            <!-- Date Range with Calendar Icon -->
            <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-200">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5 text-gray-500"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"
                />
              </svg>
              <input
                type="date"
                v-model="startDate"
                class="px-2 py-1 border-0 bg-transparent focus:ring-0 text-sm"
              />
              <span class="text-gray-400">to</span>
              <input
                type="date"
                v-model="endDate"
                class="px-2 py-1 border-0 bg-transparent focus:ring-0 text-sm"
              />
            </div>

            <!-- Action Buttons with Icons -->
            <button
              @click="fetchLeads"
              class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-4 h-4 mr-2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                />
              </svg>
              <span class="hidden md:inline">Update Data</span>
            </button>

            <button
              @click="exportToExcel"
              class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-4 h-4 mr-2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                />
              </svg>
              <span class="hidden md:inline">Export</span>
            </button>

            <!-- Logout Button -->
            <button
              @click="handleLogout"
              class="inline-flex items-center justify-center px-4 py-2 bg-red-50 text-red-600 border border-red-100 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-4 h-4 mr-2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                />
              </svg>
              <span class="hidden md:inline">Logout</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500">Total Leads</h3>
            <span
              class="flex items-center text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs font-medium"
            >
              +12% ↑
            </span>
          </div>
          <p class="text-2xl font-semibold text-gray-900">{{ leads.length }}</p>
          <p class="text-sm text-gray-500 mt-2">From {{ leads.length }} total records</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500">Sales Value</h3>
            <span
              class="flex items-center text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium"
            >
              +8% ↑
            </span>
          </div>
          <p class="text-2xl font-semibold text-gray-900">${{ totalSalesValue }}</p>
          <p class="text-sm text-gray-500 mt-2">Average ${{ averageSaleValue }}</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500">Quotable Leads</h3>
            <span
              class="flex items-center text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium"
            >
              +15% ↑
            </span>
          </div>
          <p class="text-2xl font-semibold text-gray-900">{{ quotableLeads }}</p>
          <p class="text-sm text-gray-500 mt-2">{{ quotablePercentage }}% of total leads</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-500">Quote Value</h3>
            <span
              class="flex items-center text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs font-medium"
            >
              +5% ↑
            </span>
          </div>
          <p class="text-2xl font-semibold text-gray-900">${{ totalQuoteValue }}</p>
          <p class="text-sm text-gray-500 mt-2">Average ${{ averageQuoteValue }}</p>
        </div>
      </div>

      <!-- Table Section -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Leads Details</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  v-for="column in columns"
                  :key="column"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  {{ formatHeader(column) }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="lead in leads"
                :key="lead.lead_id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td
                  v-for="column in columns"
                  :key="column"
                  class="px-6 py-4 whitespace-nowrap text-sm"
                >
                  <template v-if="column === 'lead_status'">
                    <span
                      :class="getStatusClass(lead[column])"
                      class="inline-flex px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ lead[column] }}
                    </span>
                  </template>
                  <template v-else-if="column === 'date_created'">
                    <span class="text-gray-600">{{ formatDate(lead[column]) }}</span>
                  </template>
                  <template v-else-if="column.includes('value')">
                    <span class="text-gray-900 font-medium">${{ lead[column] || '-' }}</span>
                  </template>
                  <template v-else>
                    <span class="text-gray-600">{{ lead[column] || '-' }}</span>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="totalPages > 1"
          class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 gap-4"
        >
          <div class="flex items-center gap-4 w-full sm:w-auto">
            <!-- Page Size Selector -->
            <select
              v-model="pageSize"
              @change="handlePageSizeChange"
              class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 py-2 px-5"
            >
              <option v-for="size in pageSizeOptions" :key="size" :value="size">
                {{ size }} per page
              </option>
            </select>

            <!-- Results Counter -->
            <span class="text-sm text-gray-500">
              Showing {{ startIndex }} to {{ endIndex }} of {{ totalLeads }} results
            </span>
          </div>

          <!-- Enhanced Pagination Controls -->
          <div class="flex items-center gap-2">
            <!-- First Page -->
            <button
              @click="goToPage(1)"
              :disabled="currentPage === 1"
              class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :class="paginationButtonClass(currentPage === 1)"
            >
              ««
            </button>

            <!-- Previous Page -->
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :class="paginationButtonClass(currentPage === 1)"
            >
              «
            </button>

            <!-- Page Numbers -->
            <div class="flex gap-1">
              <template v-for="pageNum in displayedPages" :key="pageNum">
                <span v-if="pageNum === '...'" class="px-2 py-1 text-gray-400"> ... </span>
                <button
                  v-else
                  @click="goToPage(pageNum)"
                  class="px-3 py-1 rounded text-sm font-medium transition-colors"
                  :class="paginationButtonClass(currentPage === pageNum)"
                >
                  {{ pageNum }}
                </button>
              </template>
            </div>

            <!-- Next Page -->
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :class="paginationButtonClass(currentPage === totalPages)"
            >
              »
            </button>

            <!-- Last Page -->
            <button
              @click="goToPage(totalPages)"
              :disabled="currentPage === totalPages"
              class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :class="paginationButtonClass(currentPage === totalPages)"
            >
              »»
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useLeadStore } from '../stores/useLeadStore'
import LoadingBar from '../components/Loading.vue'
import * as XLSX from 'xlsx'
import { useRouter } from 'vue-router'

const router = useRouter()
const leadStore = useLeadStore()

const pageSize = ref(25)
const pageSizeOptions = [25, 50, 100, 150, 200, 250]

// State
const startDate = ref(new Date().toISOString().split('T')[0])
const endDate = ref(new Date().toISOString().split('T')[0])
const columns = ref([
  'account_id',
  'account',
  'profile',
  'lead_type',
  'lead_status',
  'date_created',
  'quotable',
  'quote_value',
  'sales_value',
  'lead_source',
  'lead_medium',
])

const handleLogout = () => {
  // Remove token from localStorage
  localStorage.removeItem('token')

  // Redirect to login page
  router.push({ name: 'login' })
}

// Computed
const leads = computed(() => leadStore.leads)
const totalSalesValue = computed(() =>
  leads.value
    .reduce((sum, lead) => sum + (Number(lead.sales_value) || 0), 0)
    .toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }),
)
const totalQuoteValue = computed(() =>
  leads.value
    .reduce((sum, lead) => sum + (Number(lead.quote_value) || 0), 0)
    .toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }),
)
const quotableLeads = computed(() => leads.value.filter((lead) => lead.quotable === 'Yes').length)
const quotablePercentage = computed(() =>
  ((quotableLeads.value / leads.value.length) * 100).toFixed(1),
)

const averageSaleValue = computed(() =>
  (
    leads.value.reduce((sum, lead) => sum + (Number(lead.sales_value) || 0), 0) / leads.value.length
  ).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }),
)
const averageQuoteValue = computed(() =>
  (
    leads.value.reduce((sum, lead) => sum + (Number(lead.quote_value) || 0), 0) / leads.value.length
  ).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }),
)
const isLoading = computed(() => leadStore.isLoading)
const error = computed(() => leadStore.error)
const totalPages = computed(() => leadStore.totalPages)
const currentPage = computed(() => leadStore.currentPage)
const totalLeads = computed(() => leadStore.totalLeads)
const loading = ref(false) // Add this to track loading state

const fetchLeads = async (page = 1) => {
  try {
    loading.value = true // Start loading
    await leadStore.fetchLeads(startDate.value, endDate.value, page, pageSize.value)
  } catch (error) {
    console.error('Error fetching leads:', error)
    alert('An error occurred while fetching leads. Please try again.')
  } finally {
    loading.value = false // Stop loading
  }
}

const exportToExcel = async () => {
  try {
    loading.value = true // Start loading
    const allLeads = await leadStore.fetchAllLeadsForExport(startDate.value, endDate.value)

    if (allLeads.length) {
      const exportData = allLeads.map((lead) => ({
        'Account ID': lead.account_id,
        Account: lead.account,
        'Profile ID': lead.profile_id,
        Profile: lead.profile,
        'Lead ID': lead.lead_id,
        'Lead Type': lead.lead_type,
        'Lead Status': lead.lead_status,
        'Date Created': lead.date_created
          ? new Date(lead.date_created).toISOString().split('T')[0]
          : 'Invalid Date',
        Quotable: lead.quotable,
        'Quote Value': lead.quote_value,
        'Sales Value': lead.sales_value,
        'Lead Source': lead.lead_source,
        'Lead Medium': lead.lead_medium,
      }))

      const worksheet = XLSX.utils.json_to_sheet(exportData)
      const workbook = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Leads')
      XLSX.writeFile(workbook, `leads_${startDate.value}_to_${endDate.value}.xlsx`)
    }
  } catch (error) {
    console.error('Error exporting data:', error)
    alert('An error occurred while exporting data. Please try again.')
  } finally {
    loading.value = false // Stop loading
  }
}

const formatDate = (date) => new Date(date).toLocaleDateString()

const formatDateRange = (start, end) => {
  const startFormatted = new Date(start).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
  const endFormatted = new Date(end).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
  return `${startFormatted} - ${endFormatted}`
}

const getStatusClass = (status) =>
  ({
    Unique: 'bg-green-50 text-green-700',
    Duplicate: 'bg-yellow-50 text-yellow-700',
    Invalid: 'bg-red-50 text-red-700',
  })[status] || 'bg-gray-50 text-gray-700'

const formatHeader = (header) =>
  header
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')

watch([startDate, endDate], () => {
  fetchLeads(1) // Reset to first page when dates change
})

const startIndex = computed(() => {
  return (leadStore.currentPage - 1) * pageSize.value + 1
})

const endIndex = computed(() => {
  return Math.min(leadStore.currentPage * pageSize.value, leadStore.totalLeads)
})

const displayedPages = computed(() => {
  const totalPages = leadStore.totalPages
  const currentPage = leadStore.currentPage
  let pages = []

  if (totalPages <= 7) {
    // Show all pages if total pages are 7 or less
    pages = Array.from({ length: totalPages }, (_, i) => i + 1)
  } else {
    // Always show first page
    pages.push(1)

    if (currentPage > 3) {
      pages.push('...')
    }

    // Show pages around current page
    for (
      let i = Math.max(2, currentPage - 2);
      i <= Math.min(totalPages - 1, currentPage + 2);
      i++
    ) {
      pages.push(i)
    }

    if (currentPage < totalPages - 2) {
      pages.push('...')
    }

    // Always show last page
    pages.push(totalPages)
  }

  return pages
})

// Methods
const handlePageSizeChange = async () => {
  await leadStore.updatePageSize(pageSize.value)
  await fetchLeads(1) // Reset to first page when changing page size
}
const goToPage = async (page) => {
  if (page === leadStore.currentPage || page < 1 || page > leadStore.totalPages) return
  await fetchLeads(page)
}

const paginationButtonClass = (isActive) => {
  return isActive ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'
}

// Initialize
onMounted(() => {
  fetchLeads()
})
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
