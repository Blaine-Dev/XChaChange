<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref } from 'vue';

defineProps({
  canLogin: { type: Boolean, default: true },
  canRegister: { type: Boolean, default: false },
  currentYear: { type: Number, required: true },
  appName: { type: String, required: true },
});

const mobileOpen = ref(false);
</script>

<template>
    <Head title="Home" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-800">
        <!-- Top Navigation -->
        <header class="border-b border-gray-100 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-auto" />
                    <span class="hidden text-lg font-semibold sm:inline">{{ appName }}</span>
                </div>

                <!-- Desktop Nav -->
                <nav v-if="canLogin" class="hidden items-center gap-2 md:flex">
                    <template v-if="$page.props.auth?.user">
                        <Link :href="route('neworder')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Place Order
                        </Link>
                        <Link :href="route('orderhistory')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Order History
                        </Link>
                        <Link :href="route('exchangerates')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Exchange Rates
                        </Link>
                        <Link :href="route('profile.edit')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Profile
                        </Link>
                        <Link :href="route('logout')" method="post" as="button"
                            class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Log out
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')"
                            class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Register
                        </Link>
                    </template>
                </nav>

                <!-- Mobile hamburger -->
                <button
                v-if="canLogin"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-50 md:hidden"
                @click="mobileOpen = !mobileOpen"
                aria-label="Toggle navigation"
                :aria-expanded="mobileOpen ? 'true' : 'false'"
                aria-controls="mobile-menu"
                >
                    <svg v-if="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Mobile Nav: shown when hamburger is open -->
        <nav
        v-if="canLogin && mobileOpen"
        id="mobile-menu"
        class="border-b border-gray-100 bg-white md:hidden"
        aria-label="Mobile navigation"
        >
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <template v-if="$page.props.auth?.user">
            <div class="flex flex-col gap-1">
                <Link @click="mobileOpen = false" :href="route('neworder')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Place Order</Link>
                <Link @click="mobileOpen = false" :href="route('orderhistory')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Order History</Link>
                <Link @click="mobileOpen = false" :href="route('exchangerates')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Exchange Rates</Link>
                <Link @click="mobileOpen = false" :href="route('profile.edit')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Profile</Link>
                <Link @click="mobileOpen = false" :href="route('logout')" method="post" as="button" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">Log out</Link>
            </div>
            </template>
            <template v-else>
            <div class="flex flex-col gap-1">
                <Link @click="mobileOpen = false" :href="route('login')" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Log in</Link>
                <Link @click="mobileOpen = false" v-if="canRegister" :href="route('register')" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">Register</Link>
            </div>
            </template>
        </div>
        </nav>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 -z-10">
            <img src="/images/XChaChange_Banner_bg.svg" alt="" class="mx-auto max-w-5xl opacity-10" />
            </div>
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:py-24 lg:px-8">
            <div>
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                Seamless Foreign Currency Orders.
                </h1>
                <p class="mt-4 text-lg leading-7 text-gray-600">
                Place foreign currency orders with transparent rates, automated surcharge handling, and configurable
                notifications. Built for accuracy, speed, and clarity.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('neworder')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-500"
                >
                    Place an Order
                </Link>
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('orderhistory')"
                    class="inline-flex items-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    View Order History
                </Link>
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('exchangerates')"
                    class="inline-flex items-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    View Exchange Rates
                </Link>

                <template v-if="!$page.props.auth?.user">
                    <Link
                    :href="route('login')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-500"
                    >
                    Get Started
                    </Link>
                    <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="inline-flex items-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                    Create Account
                    </Link>
                </template>
                </div>

                <div class="mt-6 text-sm text-gray-500">
                Source currency is configured centrally. Special per-currency discounts are recorded for reporting and
                transparency, without affecting your payable amount.
                </div>
            </div>

            <div class="relative">
                <div class="mx-auto max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">Why XChaChange</h3>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-600">
                    <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
                    Transparent exchange rates with clear surcharge breakdowns.
                    </li>
                    <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
                    Per-currency controls like email notifications and special discounts.
                    </li>
                    <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600"></span>
                    Built on Laravel + Inertia + Vue for reliability and speed.
                    </li>
                </ul>
                </div>
            </div>
            </div>
        </section>

        <!-- How it Works -->
        <section class="border-t border-gray-100 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-16 sm:px-6 lg:grid-cols-3 lg:gap-12 lg:px-8">
            <div>
            <h2 class="text-2xl font-bold text-gray-900">How it works</h2>
            <p class="mt-2 text-gray-600">Place an order in three simple steps.</p>
            </div>
            <div class="lg:col-span-2">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                <div class="text-sm font-semibold text-gray-900">1. Choose currency</div>
                <p class="mt-2 text-sm text-gray-600">
                    Select the foreign currency and input your desired amount. Source currency is managed centrally.
                </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                <div class="text-sm font-semibold text-gray-900">2. Review preview</div>
                <p class="mt-2 text-sm text-gray-600">
                    See originating amount, surcharge, and recorded special discounts—before submitting.
                </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                <div class="text-sm font-semibold text-gray-900">3. Place order</div>
                <p class="mt-2 text-sm text-gray-600">
                    Submit your order. Configured currencies can trigger notifications (e.g., GBP).
                </p>
                </div>
            </div>
            </div>
        </div>
        </section>

        <!-- Feature Highlights -->
        <section class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="text-sm font-semibold text-gray-900">Reliability</div>
                <p class="mt-2 text-sm text-gray-600">
                Robust data model with precise calculations and clear, auditable fields per order.
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="text-sm font-semibold text-gray-900">Security</div>
                <p class="mt-2 text-sm text-gray-600">
                Authenticated access and environment-based configuration keep your setup safe and maintainable.
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="text-sm font-semibold text-gray-900">Transparency</div>
                <p class="mt-2 text-sm text-gray-600">
                Recorded special discounts and surcharges give you clarity without impacting payable totals.
                </p>
            </div>
            </div>
        </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-100 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-8 text-sm text-gray-500 sm:px-6 lg:px-8">
            <div>© {{ currentYear }} {{ appName }}. All rights reserved.</div>
            <div class="flex items-center gap-4">
            <Link :href="route('exchangerates')" class="hover:text-gray-700">Exchange Rates</Link>
            <Link v-if="$page.props.auth?.user" :href="route('orderhistory')" class="hover:text-gray-700">Order History</Link>
            <Link v-if="$page.props.auth?.user" :href="route('neworder')" class="hover:text-gray-700">Place Order</Link>
            </div>
        </div>
        </footer>
    </div>
</template>