<x-filament::page>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div class="space-y-6">
        {{-- Dashboard Header with Location Filter --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-2">
                <p class="text-gray-600 dark:text-gray-300">Welcome back! Here's a snapshot of your rental business.</p>
            </div>
            
            {{-- Location Filter --}}
            <div class="w-full sm:w-64">
                <label for="location_filter" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                    Filter by Location
                </label>
                <select 
                    wire:model.live="location_id" 
                    id="location_filter"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- KPI Cards - Top Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Revenue --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Revenue</p>
                        <p class="text-3xl font-bold mt-2">KES {{ number_format($totalRevenue) }}</p>
                        <p class="text-blue-100 text-xs mt-1">All time</p>
                    </div>
                    <div class="text-blue-200 opacity-50">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Occupancy Rate --}}
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Occupancy Rate</p>
                        <p class="text-3xl font-bold mt-2">{{ $occupancyRate }}%</p>
                        <p class="text-green-100 text-xs mt-1">{{ $occupiedHouses }} of {{ $totalHouses }} houses</p>
                    </div>
                    <div class="text-green-200 opacity-50">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Outstanding Balance --}}
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Outstanding Balance</p>
                        <p class="text-3xl font-bold mt-2">KES {{ number_format($outstandingBalance) }}</p>
                        <p class="text-orange-100 text-xs mt-1">To be collected</p>
                    </div>
                    <div class="text-orange-200 opacity-50">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Active Tenants --}}
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Active Tenants</p>
                        <p class="text-3xl font-bold mt-2">{{ $totalTenants }}</p>
                        <p class="text-purple-100 text-xs mt-1">{{ $newTenantsThisMonth }} new this month</p>
                    </div>
                    <div class="text-purple-200 opacity-50">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M13 16h2M9 20h5v-2a3 3 0 00-5.856-1.487M9 10h.01M7 20h5v-2a3 3 0 00-5.856-1.487M7 10h.01"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        @if(!$location_id)
        {{-- Charts Row 1 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:key="charts-{{ $location_id ?? 'all' }}">
            {{-- Monthly Revenue Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue Trend (Last 7 Months)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="revenueChart-{{ $location_id ?? 'all' }}"></canvas>
                </div>
            </div>

            {{-- Invoice Status Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Invoice Status Distribution</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="statusChart-{{ $location_id ?? 'all' }}"></canvas>
                </div>
            </div>
        </div>

        {{-- Charts Row 2 --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Activity Trend --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6" wire:key="activity-{{ $location_id ?? 'all' }}">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Activity Trend (Last 7 Days)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="activityChart-{{ $location_id ?? 'all' }}"></canvas>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Stats</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 dark:text-gray-300">Total Invoices</span>
                        <span class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $totalInvoices }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-600"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-green-700 dark:text-green-400">Paid</span>
                        <span class="font-bold text-lg text-green-700 dark:text-green-400">{{ $paidInvoices }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-700 dark:text-red-400">Unpaid</span>
                        <span class="font-bold text-lg text-red-700 dark:text-red-400">{{ $unpaidInvoices }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-700 dark:text-yellow-400">Partial</span>
                        <span class="font-bold text-lg text-yellow-700 dark:text-yellow-400">{{ $partialInvoices }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Recent Payments Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Payments</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tenant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @forelse ($recentPayments as $payment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $payment->tenant->tenant_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-700 dark:text-green-400 font-semibold">
                                        KES {{ number_format($payment->amount_paid) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $payment->reference ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-300">No recent payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let revenueChart, statusChart, activityChart;

        function initCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e5e7eb' : '#1f2937';
            const gridColor = isDark ? '#4b5563' : '#e5e7eb';
            const chartKey = '{{ $location_id ?? "all" }}';

            // Destroy existing charts if they exist
            if (revenueChart) {
                revenueChart.destroy();
            }
            if (statusChart) {
                statusChart.destroy();
            }
            if (activityChart) {
                activityChart.destroy();
            }

            // Revenue Chart
            const revenueCanvas = document.getElementById('revenueChart-' + chartKey);
            if (revenueCanvas) {
                revenueChart = new Chart(revenueCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($monthlyRevenueData['months']),
                        datasets: [{
                            label: 'Revenue (KES)',
                            data: @json($monthlyRevenueData['revenues']),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: isDark ? '#1f2937' : '#fff',
                            pointBorderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: textColor,
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: Math.max(...@json($monthlyRevenueData['revenues'])) * 1.1 || 10000,
                                ticks: {
                                    color: textColor,
                                    stepSize: Math.ceil(Math.max(...@json($monthlyRevenueData['revenues'])) / 5) || 2000,
                                },
                                grid: {
                                    color: gridColor,
                                }
                            },
                            x: {
                                ticks: {
                                    color: textColor,
                                },
                                grid: {
                                    color: gridColor,
                                }
                            }
                        }
                    }
                });
            }

            // Invoice Status Chart
            const statusCanvas = document.getElementById('statusChart-' + chartKey);
            if (statusCanvas) {
                const paidCount = {{ $invoiceStatusData['paid'] }};
                const unpaidCount = {{ $invoiceStatusData['unpaid'] }};
                const partialCount = {{ $invoiceStatusData['partial'] }};
                const totalInvoices = paidCount + unpaidCount + partialCount;
                
                statusChart = new Chart(statusCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: [
                            `Paid (${paidCount})`,
                            `Unpaid (${unpaidCount})`,
                            `Partial (${partialCount})`
                        ],
                        datasets: [{
                            data: [paidCount, unpaidCount, partialCount],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.85)',
                                'rgba(239, 68, 68, 0.85)',
                                'rgba(234, 179, 8, 0.85)',
                            ],
                            borderColor: [
                                '#22c55e',
                                '#ef4444',
                                '#eab308',
                            ],
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: textColor,
                                    padding: 15,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const percentage = totalInvoices > 0 ? ((value / totalInvoices) * 100).toFixed(1) : 0;
                                        return `${label}: ${percentage}%`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Activity Chart
            const activityCanvas = document.getElementById('activityChart-' + chartKey);
            if (activityCanvas) {
                activityChart = new Chart(activityCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($activityTrendData['days']),
                        datasets: [{
                            label: 'Activities',
                            data: @json($activityTrendData['activities']),
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderColor: '#8b5cf6',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: textColor,
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: Math.max(...@json($activityTrendData['activities'])) * 1.2 || 10,
                                ticks: {
                                    color: textColor,
                                    stepSize: Math.max(1, Math.ceil(Math.max(...@json($activityTrendData['activities'])) / 4)),
                                },
                                grid: {
                                    color: gridColor,
                                }
                            },
                            x: {
                                ticks: {
                                    color: textColor,
                                },
                                grid: {
                                    color: gridColor,
                                }
                            }
                        }
                    }
                });
            }
        }

        // Initialize charts on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCharts, 100);
        });
        
        // Re-initialize charts when Livewire updates (Livewire 3 syntax)
        document.addEventListener('livewire:navigated', function () {
            setTimeout(initCharts, 100);
        });

        // Listen for Livewire updates
        Livewire.hook('morph.updated', ({ el, component }) => {
            setTimeout(initCharts, 150);
        });
    </script>
</x-filament::page>

