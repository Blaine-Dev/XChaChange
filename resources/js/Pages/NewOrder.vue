<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const page = usePage();
const userId = computed(() => page.props.auth?.user?.id || null);

const loading = ref(true);
const submitting = ref(false);
const error = ref('');
const success = ref('');

const sourceCurrency = ref('');
const currencies = ref([]);

const selectedCurrencyId = ref(null);
const foreignAmount = ref('');
const sourceAmount = ref('');
const activeInput = ref(null);

const selectedCurrency = computed(() => {
    return currencies.value.find(c => c.id === Number(selectedCurrencyId.value)) || null;
});

const foreignCurrency = computed(() => selectedCurrency.value?.currency ?? '');

const exchangeRate = computed(() => selectedCurrency.value?.exchange_rate ?? 0);
const surchargePct = computed(() => Number(selectedCurrency.value?.surcharge_percentage ?? 0));

const specialDiscountPct = computed(() => Number(selectedCurrency.value?.special_discount_percentage ?? 0));
const specialDiscountAmount = computed(() => originatingAmount.value * (specialDiscountPct.value / 100));

const parsedForeignAmount = computed(() => {
    const n = Number(foreignAmount.value);  
    return isFinite(n) && n >= 0 ? n : 0;
});

const parsedSourceAmount = computed(() => {
    const n = Number(sourceAmount.value);
    return isFinite(n) && n >= 0 ? n : 0;
});

const originatingAmount = computed(() => parsedSourceAmount.value);

const surchargeAmount = computed(() => originatingAmount.value * (surchargePct.value / 100));
const totalAmount = computed(() => originatingAmount.value + surchargeAmount.value);

async function loadInitialData() {
    loading.value = true;
    error.value = '';
    success.value = '';
    try {
        const [{ data: src }, { data: list }] = await Promise.all([
            axios.get('/api/currencies/source'),
            axios.get('/api/currencies/list'),
        ]);

        sourceCurrency.value = src?.source || '';
        if (!sourceCurrency.value) {
            error.value = 'Source currency is not configured. Please contact support.';
        }

        currencies.value = Array.isArray(list) ? list : [];

        if (!selectedCurrencyId.value && currencies.value.length) {
            selectedCurrencyId.value = currencies.value[0].id;
        }
    } catch (e) {
        console.error(e);
        error.value = 'Failed to load order data. Please try again later.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadInitialData);

function resetForm() {
    foreignAmount.value = '';
    sourceAmount.value = '';
    activeInput.value = null;
    if (currencies.value.length) {
        selectedCurrencyId.value = currencies.value[0].id;
    } else {
        selectedCurrencyId.value = null;
    }
}

watch([sourceAmount, exchangeRate], ([s, rate]) => {
    if (activeInput.value !== 'source') return;
    const r = Number(rate);
    const n = Number(s);
    if (!isFinite(n) || n < 0) {
        foreignAmount.value = '';
        return;
    }
    if (r > 0) {
        const computedForeign = n * r;
        const currentForeign = Number(foreignAmount.value);
        if (!isFinite(currentForeign) || Math.abs(currentForeign - computedForeign) > 0.005) {
            foreignAmount.value = computedForeign.toFixed(2);
        }
    } else {
        foreignAmount.value = '';
    }
});

watch([foreignAmount, exchangeRate], ([f, rate]) => {
    if (activeInput.value !== 'foreign') return;
    const r = Number(rate);
    const n = Number(f);
    if (!isFinite(n) || n < 0) {
        sourceAmount.value = '';
        return;
    }
    if (r > 0) {
        const computedSource = n / r;
        const currentSource = Number(sourceAmount.value);
        if (!isFinite(currentSource) || Math.abs(currentSource - computedSource) > 0.005) {
            sourceAmount.value = computedSource.toFixed(2);
        }
    } else {
        sourceAmount.value = '';
    }
});

function onSourceInput() {
    activeInput.value = 'source';
}

function onForeignInput() {
    activeInput.value = 'foreign';
}

async function submitOrder() {
    if (!userId.value) {
        error.value = 'User information not available.';
        return;
    }
    if (!selectedCurrency.value) {
        error.value = 'Please select a foreign currency.';
        return;
    }
    
    const hasForeignAmount = parsedForeignAmount.value > 0;
    const hasSourceAmount = parsedSourceAmount.value > 0;
    
    if (!hasForeignAmount && !hasSourceAmount) {
        error.value = 'Please enter either a foreign amount or source amount.';
        return;
    }

    submitting.value = true;
    error.value = '';
    success.value = '';

    try {
        const payload = {
            user_id: userId.value,
            foreign_currency_id: selectedCurrency.value.id,
        };

        if (activeInput.value === 'foreign' && hasForeignAmount) {
            payload.foreign_amount = Number(parsedForeignAmount.value.toFixed(2));
        } else if (activeInput.value === 'source' && hasSourceAmount) {
            payload.originating_amount = Number(parsedSourceAmount.value.toFixed(2));
        } else if (hasForeignAmount) {
            payload.foreign_amount = Number(parsedForeignAmount.value.toFixed(2));
        } else {
            payload.originating_amount = Number(parsedSourceAmount.value.toFixed(2));
        }

        const { data } = await axios.post('/api/orders', payload);
        success.value = data?.message || 'Order created successfully';
        resetForm();
    } catch (e) {
        console.error(e);
        if (e.response?.data?.message) {
            error.value = e.response.data.message;
        } else if (e.response?.data?.errors) {
            const errs = Object.values(e.response.data.errors).flat().join(' ');
            error.value = errs || 'Failed to create order. Please check your inputs.';
        } else {
            error.value = 'Failed to create order. Please try again later.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Head title="New Order" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                New Order
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="loading" class="text-gray-600">Loading...</div>
                        <div v-else>
                            <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-700">
                                {{ error }}
                            </div>
                            <div v-if="success" class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-700">
                                {{ success }}
                            </div>

                            <form @submit.prevent="submitOrder" class="space-y-6">
                                <!-- Source currency (read-only, from services config) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Source Currency</label>
                                    <div class="mt-1">
                                      <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-800">
                                        {{ sourceCurrency ? sourceCurrency : (loading ? 'Loading…' : 'Not configured') }}
                                      </span>
                                        <p class="mt-1 text-xs text-gray-500">This is fixed by configuration and cannot be changed.</p>
                                    </div>
                                </div>

                                <!-- Foreign currency selection (active currencies only) -->
                                <div>
                                    <label for="foreignCurrency" class="block text-sm font-medium text-gray-700">Foreign Currency</label>
                                    <select
                                        id="foreignCurrency"
                                        v-model="selectedCurrencyId"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option v-for="c in currencies" :key="c.id" :value="c.id">
                                            {{ c.currency }} — Rate: {{ Number(c.exchange_rate).toFixed(6) }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Source amount input -->
                                <div>
                                    <label for="sourceAmount" class="block text-sm font-medium text-gray-700">Source Amount</label>
                                    <input
                                        id="sourceAmount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="sourceAmount"
                                        @input="onSourceInput"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter amount"
                                    />
                                </div>

                                <!-- Foreign amount input -->
                                <div>
                                    <label for="foreignAmount" class="block text-sm font-medium text-gray-700">Foreign Amount</label>
                                    <input
                                        id="foreignAmount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="foreignAmount"
                                        @input="onForeignInput"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter amount"
                                    />
                                </div>

                                <!-- Preview calculations -->
                                <div class="rounded-md border border-gray-200 p-4">
                                    <h3 class="mb-3 text-sm font-semibold text-gray-800">Preview</h3>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div class="text-sm text-gray-700">Foreign Amount ({{ foreignCurrency }})</div>
                                        <div class="text-sm font-medium text-gray-900">{{ Number(foreignAmount).toFixed(2) }}</div>

                                        <div class="text-sm text-gray-700">Exchange Rate</div>
                                        <div class="text-sm font-medium text-gray-900">{{ Number(exchangeRate).toFixed(6) }}</div>

                                        <div class="text-sm text-gray-700">Surcharge %</div>
                                        <div class="text-sm font-medium text-gray-900">{{ Number(surchargePct).toFixed(2) }}%</div>

                                        <div class="text-sm text-gray-700">Source Amount ({{ sourceCurrency }})</div>
                                        <div class="text-sm font-medium text-gray-900">{{ Number(sourceAmount).toFixed(2) }}</div>

                                        <div class="text-sm text-gray-700">Surcharge Amount</div>
                                        <div class="text-sm font-medium text-gray-900">{{ surchargeAmount.toFixed(2) }}</div>

                                        <div class="text-sm text-gray-700">Special Discount % (not applied)</div>
                                        <div class="text-sm font-medium text-gray-900">{{ Number(specialDiscountPct).toFixed(2) }}%</div>

                                        <div class="text-sm text-gray-700">Special Discount Amount (not applied)</div>
                                        <div class="text-sm font-medium text-gray-900">{{ specialDiscountAmount.toFixed(2) }}</div>

                                        <div class="text-sm text-gray-700">Total</div>
                                        <div class="text-sm font-semibold text-gray-900">{{ totalAmount.toFixed(2) }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end">
                                    <button
                                        type="submit"
                                        :disabled="submitting || !selectedCurrency || parsedForeignAmount <= 0 || !sourceCurrency"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span v-if="!submitting">Place Order</span>
                                        <span v-else>Placing...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>