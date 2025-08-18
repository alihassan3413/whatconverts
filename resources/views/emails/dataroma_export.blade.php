@component('mail::message')
# {{ $accountName ?? 'Leads' }} Export - Batch {{ $batchNumber }}

**Date Range:** {{ $dateRangeLabel }}  
**File:** {{ $fileName }}  
**Total Leads:** {{ $totalLeads ?? 'N/A' }}  

@component('mail::panel')
Export details:
- Start Date: {{ $startDate }}
- End Date: {{ $endDate }}
@endcomponent

Thanks,
{{ config('app.name') }}

@endcomponent