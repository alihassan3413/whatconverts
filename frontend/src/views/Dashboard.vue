<template>
  <div class="min-h-screen bg-gray-50">
    <div
      v-if="loading"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <LoadingBar :is-loading="loading" />

    <div class="max-w-[1600px] mx-auto p-6">
      <DashboardHeader
        :start-date="startDate"
        :end-date="endDate"
        :date-range="formatDateRange(startDate, endDate)"
        @update:startDate="val => startDate = val"
        @update:endDate="val => endDate = val"
        @refresh="fetchLeads"
        @import="importData"
        @export="exportToExcel"
        @logout="handleLogout"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <MetricsCard
          title="Total Leads"
          :value="leads.length"
          trend-text="+12% ↑"
          trend-type="positive"
          :secondary-text="`From ${leads.length} total records`"
        />
        <MetricsCard
          title="Sales Value"
          :value="`$${totalSalesValue}`"
          trend-text="+8% ↑"
          trend-type="positive"
          :secondary-text="`Average $${averageSaleValue}`"
        />
        <MetricsCard
          title="Quotable Leads"
          :value="quotableLeads"
          trend-text="+15% ↑"
          trend-type="positive"
          :secondary-text="`${quotablePercentage}% of total leads`"
        />
        <MetricsCard
          title="Quote Value"
          :value="`$${totalQuoteValue}`"
          trend-text="+5% ↑"
          trend-type="neutral"
          :secondary-text="`Average $${averageQuoteValue}`"
        />
      </div>

      <LeadsTable
        :columns="columns"
        :leads="leads"
        @account-switch="handleAccountSwitch"
        :current-page="currentPage"
        :total-pages="totalPages"
        :page-size="pageSize"
        :total-leads="totalLeads"
        @page-size-change="handlePageSizeChange"
        @page-change="goToPage"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useLeadStore } from '../stores/useLeadStore';
import { useGoogleSheets } from '../stores/useGoogleSheets';
import LoadingBar from '../components/Loading.vue';
import DashboardHeader from '../components/DashboardHeader.vue';
import MetricsCard from '../components/MetricsCard.vue';
import LeadsTable from '../components/LeadsTable.vue';
import * as XLSX from 'xlsx';

const router = useRouter();
const leadStore = useLeadStore();
const currentAccount = computed(() => leadStore.currentAccount);
const { fetchSheets } = useGoogleSheets();

// Reactive state
const startDate = ref(new Date().toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);
const pageSize = ref(25);
const loading = ref(false);
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
  'lead_campaign',
  'spotted_keywords',
  'lead_keyword'
]);

// Computed properties
const leads = computed(() => leadStore.leads);
const totalSalesValue = computed(() =>
  leads.value
    .reduce((sum, lead) => sum + (Number(lead.sales_value) || 0), 0)
    .toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
);
const totalQuoteValue = computed(() =>
  leads.value
    .reduce((sum, lead) => sum + (Number(lead.quote_value) || 0), 0)
    .toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
);
const quotableLeads = computed(() => leads.value.filter(lead => lead.quotable === 'Yes').length);
const quotablePercentage = computed(() =>
  ((quotableLeads.value / leads.value.length) * 100 || 0).toFixed(1)
);
const averageSaleValue = computed(() => {
  const total = leads.value.reduce((sum, lead) => sum + (Number(lead.sales_value) || 0), 0);
  return (total / (leads.value.length || 1)).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
});
const averageQuoteValue = computed(() => {
  const total = leads.value.reduce((sum, lead) => sum + (Number(lead.quote_value) || 0), 0);
  return (total / (leads.value.length || 1)).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
});
const totalPages = computed(() => leadStore.totalPages);
const currentPage = computed(() => leadStore.currentPage);
const totalLeads = computed(() => leadStore.totalLeads);

// Methods
const formatDateRange = (start, end) => {
  const startDate = new Date(start);
  const endDate = new Date(end);
  return `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
};

const handlePageSizeChange = async (newSize) => {
  pageSize.value = Number(newSize);
  await fetchLeads(1);
};

const goToPage = async (page) => {
  if (page < 1 || page > totalPages.value) return;
  await fetchLeads(page);
};

async function handleAccountSwitch(account) {
  console.log('handleAccountSwitch called with account:', account);
  if (!account || !account.id) {
    console.error('Invalid account provided to handleAccountSwitch:', account);
    return;
  }
  try {
    const result = await leadStore.switchAccount(account.id);
    console.log('switchAccount result:', result);
    if (result) {
      await new Promise((resolve) => setTimeout(resolve, 100));
      console.log(`Fetching leads for account: ${account.id}`);
      console.log(`Current account in store: ${leadStore.currentAccount.name} (Token: ${leadStore.currentAccount.token})`);
      await leadStore.fetchLeads(startDate.value, endDate.value, 1, leadStore.leadsPerPage);
      console.log('Post-fetch store state:', {
        leads: leadStore.leads.length,
        totalLeads: leadStore.totalLeads,
        totalPages: leadStore.totalPages,
      });
    }
  } catch (error) {
    console.error('Error in handleAccountSwitch:', error);
  }
}

const fetchLeads = async (page = 1) => {
  try {
    loading.value = true;
    await leadStore.fetchLeads(
      startDate.value,
      endDate.value,
      page,
      pageSize.value
    );
  } catch (error) {
    console.error('Error fetching leads:', error);
  } finally {
    loading.value = false;
  }
};

const importData = async () => {
  try {
    loading.value = true;
    await fetchSheets();
    await fetchLeads(1);
  } catch (error) {
    console.error('Import error:', error);
    alert('Failed to import data. Please check your connection.');
  } finally {
    loading.value = false;
  }
};

const exportToExcel = async () => {
  try {
    loading.value = true;
    console.log(`Exporting leads for account: ${currentAccount.value.name} (ID: ${currentAccount.value.id})`);
    const allLeads = await leadStore.fetchAllLeadsForExport(startDate.value, endDate.value, currentAccount.value);

    if (allLeads.length === 0) {
      alert('No data to export');
      return;
    }

    console.log(`Exporting ${allLeads.length} leads`, allLeads.slice(0, 5)); // Log first 5 leads for debugging

    const exportData = allLeads.map(lead => ({
      'Client ID': lead.account_id, // account_id is already mapped to client_id in fetchAllLeadsForExport
      Account: lead.account,
      'Profile ID': lead.profile_id,
      Profile: lead.profile,
      'Lead ID': lead.lead_id,
      'Lead Type': lead.lead_type,
      'Lead Status': lead.lead_status,
      'Date Created': lead.date_created ? new Date(lead.date_created).toISOString().split('T')[0] : '-',
      Quotable: lead.quotable,
      'Quote Value': lead.quote_value,
      'Sales Value': lead.sales_value,
      'Lead Source': lead.lead_source,
      'Lead Medium': lead.lead_medium,
      'Lead Campaign': lead.lead_campaign?.trim() || '-',
      'Spotted Keywords': lead.spotted_keywords?.trim() || '-',
      'Lead Keyword': lead.lead_keyword?.trim() || '-'
    }));

    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Leads');
    XLSX.writeFile(workbook, `${currentAccount.value.name.replace(/\s+/g, '_')}_leads_${startDate.value}_to_${endDate.value}.xlsx`);
  } catch (error) {
    console.error('Export error:', error);
    alert('Failed to export data. Please try again.');
  } finally {
    loading.value = false;
  }
};

const handleLogout = () => {
  localStorage.removeItem('token');
  router.push({ name: 'login' });
};

// Watchers and lifecycle hooks
watch([startDate, endDate], () => fetchLeads(1));
onMounted(() => fetchLeads());
</script>
