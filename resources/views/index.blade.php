<!DOCTYPE html>
<html lang="en" x-data="logViewerApp()" :class="{ 'dark': theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel Log Viewer</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#152033',
                            900: '#0f172a',
                            950: '#090d16',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(156, 163, 175, 0.4); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(156, 163, 175, 0.6); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen font-sans antialiased flex flex-col selection:bg-indigo-500 selection:text-white">

    <!-- Top Header Navigation -->
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- App Title & Environment -->
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-indigo-600 text-white rounded-lg shadow-md shadow-indigo-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Laravel Log Viewer</h1>
                        <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400">
                            <span>Environment:</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                {{ app()->environment() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Controls & Polling & Theme -->
                <div class="flex items-center space-x-3">
                    <!-- Live Polling Selector -->
                    <div class="relative flex items-center bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-slate-200 dark:border-slate-700/60 text-xs">
                        <div class="flex items-center space-x-1 px-2 text-slate-600 dark:text-slate-300">
                            <span class="relative flex h-2 w-2">
                                <span :class="pollingInterval > 0 ? 'animate-ping' : ''" class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span :class="pollingInterval > 0 ? 'bg-emerald-500' : 'bg-slate-400'" class="relative inline-flex rounded-full h-2 w-2"></span>
                            </span>
                            <span class="font-medium hidden sm:inline">Live:</span>
                        </div>
                        <select x-model.number="pollingInterval" @change="setupPolling()" class="bg-transparent border-0 text-slate-800 dark:text-slate-200 font-medium py-1 pr-6 pl-1 focus:ring-0 focus:outline-none cursor-pointer text-xs">
                            <option value="0">Off</option>
                            <option value="3">3s</option>
                            <option value="5">5s</option>
                            <option value="10">10s</option>
                        </select>
                    </div>

                    <!-- Theme Switcher -->
                    <button @click="toggleTheme()" class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" title="Toggle Theme">
                        <template x-if="theme === 'dark'">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </template>
                        <template x-if="theme !== 'dark'">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </template>
                    </button>

                    @if(!empty($has_passkey))
                        <!-- Lock Log Viewer Button -->
                        <form method="POST" action="/{{ $route_prefix }}/lock" class="inline">
                            @csrf
                            <button type="submit" class="p-2 text-rose-500 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-lg transition-colors flex items-center space-x-1 text-xs font-semibold" title="Lock Log Viewer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span class="hidden sm:inline">Lock</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-6">

        <!-- Top Bar: Log File Selector & Management Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <!-- Active Log File Picker -->
            <div class="flex items-center space-x-3">
                <label for="fileSelect" class="text-sm font-semibold text-slate-700 dark:text-slate-300">File:</label>
                <div class="relative min-w-[220px]">
                    <select id="fileSelect" x-model="selectedFile" @change="fetchLogs(1)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                        <template x-for="f in files" :key="f.name">
                            <option :value="f.name" x-text="f.name + ' (' + f.size_formatted + ')'" :selected="f.name === selectedFile"></option>
                        </template>
                    </select>
                </div>
                <template x-if="activeFileInfo">
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden md:inline" x-text="'Updated ' + activeFileInfo.updated_at"></span>
                </template>
            </div>

            <!-- Log Actions -->
            <div class="flex items-center space-x-2">
                <button @click="fetchLogs(currentPage)" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Refresh</span>
                </button>

                <a :href="getDownloadUrl()" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg text-xs font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Download</span>
                </a>

                <button @click="confirmClearLog()" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 rounded-lg text-xs font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span>Clear</span>
                </button>

                <button @click="confirmDeleteLog()" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span>Delete</span>
                </button>
            </div>
        </div>

        <!-- Log Level Overview Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-9 gap-3">
            <template x-for="lvl in levelStatsList" :key="lvl.name">
                <button @click="setLevelFilter(lvl.name)" :class="selectedLevel.toLowerCase() === lvl.name.toLowerCase() ? 'ring-2 ring-indigo-500 shadow-md' : 'opacity-85 hover:opacity-100'" class="flex flex-col p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left transition-all cursor-pointer">
                    <span class="text-[10px] font-bold uppercase tracking-wider" :class="lvl.textColor" x-text="lvl.name"></span>
                    <span class="text-lg font-black text-slate-900 dark:text-white mt-0.5" x-text="stats[lvl.name] || 0"></span>
                </button>
            </template>
        </div>

        <!-- Search Bar & Filters -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Text Search Input -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchLogs(1)" placeholder="Search log message, context or stacktrace..." class="w-full bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700/80 rounded-lg pl-9 pr-8 py-2 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <button x-show="searchQuery" @click="searchQuery = ''; fetchLogs(1)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Log Count summary -->
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center space-x-1">
                <span>Showing</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold" x-text="entries.length"></span>
                <span>of</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold" x-text="totalEntries"></span>
                <span>entries</span>
            </div>
        </div>

        <!-- Log Entries List -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Loading Indicator -->
            <div x-show="loading" class="p-8 text-center text-slate-500 dark:text-slate-400 flex flex-col items-center space-y-2">
                <svg class="w-8 h-8 animate-spin text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm font-medium">Loading log entries...</span>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && entries.length === 0" class="p-12 text-center text-slate-500 dark:text-slate-400 flex flex-col items-center justify-center space-y-3">
                <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No log entries found</h3>
                <p class="text-xs max-w-sm">No log entries matched your current level or search filter for file <span class="font-bold text-slate-700 dark:text-slate-300" x-text="selectedFile"></span>.</p>
            </div>

            <!-- Table List of Logs -->
            <div x-show="!loading && entries.length > 0" class="divide-y divide-slate-200 dark:divide-slate-800/80">
                <template x-for="(entry, index) in entries" :key="entry.id">
                    <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-3 cursor-pointer" @click="toggleDetails(entry.id)">
                            
                            <div class="flex items-start space-x-3 flex-1">
                                <!-- Level Badge -->
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider border shadow-sm" :class="getLevelBadgeColor(entry.level)" x-text="entry.level"></span>

                                <!-- Log Info & Message -->
                                <div class="flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-x-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="font-mono text-slate-600 dark:text-slate-300" x-text="entry.timestamp"></span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono text-[10px]" x-text="entry.env"></span>
                                        <span class="text-slate-400 text-[10px]" x-text="'Line #' + entry.line_number"></span>
                                    </div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-100 font-mono break-all leading-snug" x-text="entry.message"></div>
                                </div>
                            </div>

                            <!-- Copy & Expand Controls -->
                            <div class="flex items-center space-x-2 self-end md:self-start">
                                <!-- Copy Log Button -->
                                <button @click.stop="copyLog(entry)" 
                                        class="inline-flex items-center space-x-1 px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-colors"
                                        :title="copiedId === entry.id ? 'Copied to clipboard!' : 'Copy log entry'">
                                    <template x-if="copiedId !== entry.id">
                                        <div class="flex items-center space-x-1">
                                            <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-[11px]">Copy</span>
                                        </div>
                                    </template>
                                    <template x-if="copiedId === entry.id">
                                        <div class="flex items-center space-x-1 text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-[11px] font-bold">Copied!</span>
                                        </div>
                                    </template>
                                </button>

                                <template x-if="entry.context || entry.stack_trace">
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700">
                                        Details
                                    </span>
                                </template>
                                <button class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="Toggle details">
                                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': expandedEntries.includes(entry.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Expanded Details Container (Context & Stack Trace) -->
                        <div x-show="expandedEntries.includes(entry.id)" x-cloak x-transition class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/80 space-y-4">
                            <!-- Context Section -->
                            <template x-if="entry.context">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Context Data:</h4>
                                    <pre class="p-3 bg-slate-900 text-emerald-400 rounded-lg text-xs font-mono overflow-x-auto custom-scrollbar leading-relaxed" x-text="formatContext(entry.context)"></pre>
                                </div>
                            </template>

                            <!-- Stack Trace Section -->
                            <template x-if="entry.stack_trace">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Stack Trace:</h4>
                                    <pre class="p-3 bg-slate-900 text-slate-200 rounded-lg text-xs font-mono overflow-x-auto custom-scrollbar whitespace-pre-wrap leading-relaxed max-h-96" x-text="entry.stack_trace"></pre>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer Pagination Controls -->
            <div x-show="!loading && lastPage > 1" class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button @click="fetchLogs(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md text-xs font-semibold text-slate-700 dark:text-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                    Previous
                </button>
                <div class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                    Page <span x-text="currentPage"></span> of <span x-text="lastPage"></span>
                </div>
                <button @click="fetchLogs(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md text-xs font-semibold text-slate-700 dark:text-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                    Next
                </button>
            </div>
        </div>
    </main>

    <!-- App JavaScript Logic -->
    <script>
        function logViewerApp() {
            return {
                theme: localStorage.getItem('log_viewer_theme') || '{{ $theme }}' || 'auto',
                files: @json($files ?? []),
                selectedFile: '{{ $file["name"] ?? ($files[0]["name"] ?? "") }}',
                entries: @json($entries ?? []),
                stats: @json($stats ?? []),
                totalEntries: {{ $total ?? 0 }},
                currentPage: {{ $current_page ?? 1 }},
                lastPage: {{ $last_page ?? 1 }},
                selectedLevel: '{{ $current_level ?? "all" }}',
                searchQuery: '{{ $search_query ?? "" }}',
                loading: false,
                copiedId: null,
                expandedEntries: [],
                pollingInterval: 0,
                pollingTimer: null,
                routePrefix: '{{ $route_prefix ?? "log-viewer" }}',

                copyLog(entry) {
                    let text = `[${entry.timestamp}] ${entry.env}.${entry.level}: ${entry.message}`;
                    if (entry.context) {
                        text += `\nContext: ` + (typeof entry.context === 'object' ? JSON.stringify(entry.context, null, 2) : entry.context);
                    }
                    if (entry.stack_trace) {
                        text += `\nStacktrace:\n` + entry.stack_trace;
                    }

                    const setCopied = () => {
                        this.copiedId = entry.id;
                        setTimeout(() => {
                            if (this.copiedId === entry.id) {
                                this.copiedId = null;
                            }
                        }, 2000);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(setCopied).catch(() => {
                            this.fallbackCopyText(text);
                            setCopied();
                        });
                    } else {
                        this.fallbackCopyText(text);
                        setCopied();
                    }
                },

                fallbackCopyText(text) {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                },

                levelStatsList: [
                    { name: 'ALL', textColor: 'text-slate-600 dark:text-slate-400' },
                    { name: 'EMERGENCY', textColor: 'text-rose-600 dark:text-rose-400' },
                    { name: 'ALERT', textColor: 'text-rose-600 dark:text-rose-400' },
                    { name: 'CRITICAL', textColor: 'text-rose-600 dark:text-rose-400' },
                    { name: 'ERROR', textColor: 'text-red-600 dark:text-red-400' },
                    { name: 'WARNING', textColor: 'text-amber-600 dark:text-amber-400' },
                    { name: 'NOTICE', textColor: 'text-blue-600 dark:text-blue-400' },
                    { name: 'INFO', textColor: 'text-blue-500 dark:text-blue-400' },
                    { name: 'DEBUG', textColor: 'text-emerald-600 dark:text-emerald-400' }
                ],

                get activeFileInfo() {
                    return this.files.find(f => f.name === this.selectedFile);
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('log_viewer_theme', this.theme);
                },

                setLevelFilter(level) {
                    this.selectedLevel = level;
                    this.fetchLogs(1);
                },

                toggleDetails(id) {
                    if (this.expandedEntries.includes(id)) {
                        this.expandedEntries = this.expandedEntries.filter(e => e !== id);
                    } else {
                        this.expandedEntries.push(id);
                    }
                },

                formatContext(ctx) {
                    if (typeof ctx === 'object' && ctx !== null) {
                        return JSON.stringify(ctx, null, 2);
                    }
                    return ctx;
                },

                getLevelBadgeColor(level) {
                    switch ((level || '').toUpperCase()) {
                        case 'EMERGENCY':
                        case 'ALERT':
                        case 'CRITICAL':
                        case 'ERROR':
                            return 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20';
                        case 'WARNING':
                            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
                        case 'NOTICE':
                        case 'INFO':
                            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
                        case 'DEBUG':
                            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
                        default:
                            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
                    }
                },

                getDownloadUrl() {
                    return '/' + this.routePrefix + '/download/' + encodeURIComponent(this.selectedFile);
                },

                async fetchLogs(page = 1) {
                    this.loading = true;
                    this.currentPage = page;

                    try {
                        const params = new URLSearchParams({
                            file: this.selectedFile,
                            level: this.selectedLevel,
                            q: this.searchQuery,
                            page: this.currentPage
                        });

                        const response = await fetch('/' + this.routePrefix + '/api/logs?' + params.toString(), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.entries = data.entries || [];
                            this.stats = data.stats || {};
                            this.files = data.files || [];
                            this.totalEntries = data.total || 0;
                            this.lastPage = data.last_page || 1;
                            if (data.file && data.file.name) {
                                this.selectedFile = data.file.name;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch logs:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                async confirmClearLog() {
                    if (!confirm('Are you sure you want to clear the contents of ' + this.selectedFile + '?')) {
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('/' + this.routePrefix + '/clear/' + encodeURIComponent(this.selectedFile), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            this.fetchLogs(1);
                        }
                    } catch (e) {
                        console.error('Failed to clear log file:', e);
                    }
                },

                async confirmDeleteLog() {
                    if (!confirm('Are you sure you want to permanently delete ' + this.selectedFile + '?')) {
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch('/' + this.routePrefix + '/delete/' + encodeURIComponent(this.selectedFile), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            this.selectedFile = '';
                            this.fetchLogs(1);
                        }
                    } catch (e) {
                        console.error('Failed to delete log file:', e);
                    }
                },

                setupPolling() {
                    if (this.pollingTimer) {
                        clearInterval(this.pollingTimer);
                        this.pollingTimer = null;
                    }

                    if (this.pollingInterval > 0) {
                        this.pollingTimer = setInterval(() => {
                            this.fetchLogs(this.currentPage);
                        }, this.pollingInterval * 1000);
                    }
                }
            };
        }
    </script>
</body>
</html>
