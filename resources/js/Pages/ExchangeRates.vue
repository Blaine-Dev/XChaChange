<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const currencies = ref([]);
const loading = ref(true);
const error = ref('');

async function fetchActiveCurrencies() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get('/api/currencies/list');
        currencies.value = Array.isArray(data) ? data : [];
    } catch (err) {
        console.error(err);
        error.value = 'Failed to load exchange rates. Please try again later.';
    } finally {
        loading.value = false;
    }
}

onMounted(fetchActiveCurrencies);
</script>

<template>
    <Head title="Exchange Rates" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Exchange Rates
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <div v-if="loading" class="text-gray-600">Loading exchange rates...</div>
                        <div v-else>
                            <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-700">
                                {{ error }}
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Currency</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Exchange Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="c in currencies" :key="c.currency">
                                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ c.currency }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ Number(c.exchange_rate).toFixed(6) }}</td>
                                            </tr>
                                            <tr v-if="!currencies.length">
                                                <td colspan="2" class="px-4 py-3 text-sm text-gray-500">No active currencies found.</td>
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
