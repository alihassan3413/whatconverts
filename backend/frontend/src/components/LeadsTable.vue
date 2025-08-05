<template>
  <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
      <h2 class="text-lg font-medium text-gray-900">Leads Details</h2>
      <div class="flex gap-2">
            <button
              v-for="account in accounts"
              :key="account.id"
              @click="() => switchAccount(account)"
              :class="[
                'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                selectedAccount?.id === account.id
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ account.name }}
            </button>
          </div>
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
                  :class="statusClass(lead[column])"
                  class="inline-flex px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ lead[column] }}
                </span>
              </template>
              <template v-else-if="column === 'date_created'">
                {{ formatDate(lead[column]) }}
              </template>
              <template v-else-if="column.includes('value')">
                ${{ lead[column] || '-' }}
              </template>
              <template v-else>
                {{ lead[column] || '-' }}
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationControls
      v-if="totalPages > 1"
      :current-page="currentPage"
      :total-pages="totalPages"
      :page-size="pageSize"
      :total-items="totalLeads"
      @page-size-change="$emit('page-size-change', $event)"
      @page-change="$emit('page-change', $event)"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useLeadStore } from '../stores/useLeadStore'
import PaginationControls from './PaginationControl.vue'

const props = defineProps({
  columns: {
    type: Array,
    default: () => [
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
      'lead_campaign',
      'spotted_keywords',
      'lead_keyword'
    ]
  },
  leads: Array,
  currentPage: Number,
  totalPages: Number,
  pageSize: Number,
  totalLeads: Number
})

const emit = defineEmits([
  'update:startDate',
  'update:endDate',
  'refresh',
  'import',
  'export',
  'logout',
  'switch-account',
  'account-switch'
])

const leadStore = useLeadStore()

// Account management
const accounts = computed(() => leadStore.availableAccounts)
const selectedAccount = computed(() => leadStore.currentAccount)

// const selectedAccount = ref(accounts.value[0])

const switchAccount = async (account) => {
  await leadStore.switchAccount(account.id)
  emit('account-switch', account)
}

const formatHeader = (header) => {
  return header.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const statusClass = (status) => {
  switch (status) {
    case 'Unique':
      return 'bg-green-50 text-green-700'
    case 'Duplicate':
      return 'bg-yellow-50 text-yellow-700'
    case 'Invalid':
      return 'bg-red-50 text-red-700'
    default:
      return 'bg-gray-50 text-gray-700'
  }
}
</script>
