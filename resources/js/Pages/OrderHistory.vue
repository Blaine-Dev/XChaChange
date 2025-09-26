<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const page = usePage();
const userId = computed(() => page.props.auth?.user?.id || null);

const loading = ref(true);
const error = ref('');
const orders = ref([]);
const currencies = ref([]);

const currencyById = computed(() => {
    const map = new Map();
    for (const c of currencies.value) {
        if (c?.id) map.set(Number(c.id), c);
    }
    return map;
});

const myOrders = computed(() => {
    const list = Array.isArray(orders.value) ? orders.value : [];
    const filtered = userId.value ? list.filter(o => o.user_id === userId.value) : list;
    return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

async function fetchData() {
    loading.value = true;
    error.value = '';
    try {
        const [{ data: orderList }, { data: currencyList }] = await Promise.all([
            axios.get('/api/orders'),
            axios.get('/api/currencies/list'),
        ]);
        orders.value = Array.isArray(orderList) ? orderList : [];
        currencies.value = Array.isArray(currencyList) ? currencyList : [];
    } catch (e) {
        console.error(e);
        error.value = 'Failed to load order history. Please try again later.';
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>

<template>
    <Head title="Order History" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Order History
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="loading" class="text-gray-600">Loading orders…</div>
                        <div v-else>
                            <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-700">
                                {{ error }}
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Foreign</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rate</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Originating</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Surcharge %</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Surcharge Amt</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Special Disc %</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Special Disc Amt</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="o in myOrders" :key="o.id">
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ new Date(o.created_at).toLocaleString() }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 font-medium">
                                                    {{ currencyById.get(o.foreign_currency_id)?.currency || '#' }} {{ Number(o.foreign_amount).toFixed(2) }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(o.exchange_rate).toFixed(6) }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ o.originating_currency }} {{ Number(o.originating_amount).toFixed(2) }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(o.surcharge_percentage ?? 0).toFixed(2) }}%</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(o.surcharge_amount ?? 0).toFixed(2) }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(o.special_discount_percentage ?? 0).toFixed(2) }}%</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(o.special_discount_amount ?? 0).toFixed(2) }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 font-semibold">{{ Number(o.total_amount).toFixed(2) }}</td>
                                            </tr>
                                            <tr v-if="!myOrders.length">
                                                <td colspan="9" class="px-4 py-3 text-sm text-gray-500">No orders found.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
