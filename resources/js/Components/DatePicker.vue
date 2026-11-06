<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    placeholder: {
        type: String,
        default: 'MM/DD/YYYY',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const containerRef = ref(null);

// Parse incoming value into { month (1-12), day (1-31), year (number) }
const parseDate = (val) => {
    if (!val) return null;
    const str = String(val).trim();
    if (!str) return null;

    // Format MM/DD/YYYY
    const mmddyyyy = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (mmddyyyy) {
        return {
            month: parseInt(mmddyyyy[1], 10),
            day: parseInt(mmddyyyy[2], 10),
            year: parseInt(mmddyyyy[3], 10),
        };
    }

    // Format YYYY-MM-DD
    const yyyymmdd = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (yyyymmdd) {
        return {
            month: parseInt(yyyymmdd[2], 10),
            day: parseInt(yyyymmdd[3], 10),
            year: parseInt(yyyymmdd[1], 10),
        };
    }

    // 4-digit Year only (e.g. "1800" or "1990")
    const yearOnly = str.match(/^(\d{4})$/);
    if (yearOnly) {
        return {
            month: 1,
            day: 1,
            year: parseInt(yearOnly[1], 10),
        };
    }

    return null;
};

// Current view in calendar (month: 1-12, year: number)
const today = new Date();
const currentParsed = computed(() => parseDate(props.modelValue));

const viewMonth = ref(today.getMonth() + 1);
const viewYear = ref(today.getFullYear());

// Sync view when modelValue changes or calendar opens
const syncViewFromModel = () => {
    const p = currentParsed.value;
    if (p) {
        viewMonth.value = p.month;
        viewYear.value = p.year;
    } else {
        viewMonth.value = today.getMonth() + 1;
        viewYear.value = today.getFullYear();
    }
};

watch(() => props.modelValue, () => {
    syncViewFromModel();
}, { immediate: true });

const formattedDisplay = computed(() => {
    const p = currentParsed.value;
    if (!p) return props.modelValue || '';
    const mm = String(p.month).padStart(2, '0');
    const dd = String(p.day).padStart(2, '0');
    const yyyy = String(p.year).padStart(4, '0');
    return `${mm}/${dd}/${yyyy}`;
});

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

// Calculate calendar days for viewMonth and viewYear
const calendarDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value; // 1-indexed

    // First day of current month (0: Sunday, 1: Monday, ...)
    const firstDayIndex = new Date(year, month - 1, 1).getDay();
    // Days in current month
    const daysInMonth = new Date(year, month, 0).getDate();
    // Days in previous month
    const daysInPrevMonth = new Date(year, month - 1, 0).getDate();

    const days = [];

    // Previous month filler days
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        days.push({
            day: daysInPrevMonth - i,
            month: month === 1 ? 12 : month - 1,
            year: month === 1 ? year - 1 : year,
            isCurrentMonth: false,
        });
    }

    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
        const isSelected = currentParsed.value &&
            currentParsed.value.day === d &&
            currentParsed.value.month === month &&
            currentParsed.value.year === year;

        const isToday = today.getDate() === d &&
            today.getMonth() + 1 === month &&
            today.getFullYear() === year;

        days.push({
            day: d,
            month,
            year,
            isCurrentMonth: true,
            isSelected,
            isToday,
        });
    }

    // Next month filler days to complete 6 rows (or minimum 35/42 slots)
    const remainingSlots = 42 - days.length;
    for (let d = 1; d <= remainingSlots; d++) {
        days.push({
            day: d,
            month: month === 12 ? 1 : month + 1,
            year: month === 12 ? year + 1 : year,
            isCurrentMonth: false,
        });
    }

    return days;
});

const prevMonth = () => {
    if (viewMonth.value === 1) {
        viewMonth.value = 12;
        viewYear.value--;
    } else {
        viewMonth.value--;
    }
};

const nextMonth = () => {
    if (viewMonth.value === 12) {
        viewMonth.value = 1;
        viewYear.value++;
    } else {
        viewMonth.value++;
    }
};

const selectDate = (item) => {
    const mm = String(item.month).padStart(2, '0');
    const dd = String(item.day).padStart(2, '0');
    const yyyy = String(item.year).padStart(4, '0');
    const formatted = `${mm}/${dd}/${yyyy}`;
    emit('update:modelValue', formatted);
    isOpen.value = false;
};

const setToday = () => {
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const yyyy = String(today.getFullYear());
    const formatted = `${mm}/${dd}/${yyyy}`;
    emit('update:modelValue', formatted);
    isOpen.value = false;
};

const clearDate = () => {
    emit('update:modelValue', '');
    isOpen.value = false;
};

const toggleOpen = () => {
    if (props.disabled) return;
    if (!isOpen.value) {
        syncViewFromModel();
    }
    isOpen.value = !isOpen.value;
};

// Handle manual input in text field
const onManualInput = (e) => {
    const val = e.target.value.trim();
    emit('update:modelValue', val);
};

// Close when clicking outside
const handleClickOutside = (e) => {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Input Display Field -->
        <div class="relative flex items-center">
            <input
                type="text"
                :value="formattedDisplay"
                @input="onManualInput"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full bg-gray-800 border border-white/10 rounded-xl pl-4 pr-20 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-primary-500/50 transition-colors tracking-wide"
                @focus="isOpen = true"
            />

            <!-- Actions: Clear button and Calendar Icon -->
            <div class="absolute right-2.5 flex items-center gap-1.5">
                <button
                    v-if="modelValue"
                    type="button"
                    @click="clearDate"
                    class="p-1 text-gray-500 hover:text-red-400 transition-colors text-xs rounded-md hover:bg-white/5"
                    title="Hapus Tanggal"
                >
                    ✕
                </button>
                <button
                    type="button"
                    @click="toggleOpen"
                    class="p-1.5 text-primary-400 hover:text-primary-300 transition-colors rounded-lg hover:bg-primary-500/10 active:scale-95"
                    title="Buka Kalender"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Floating Date Picker Modal / Dropdown -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 mt-2 z-50 w-72 sm:w-80 bg-gray-900 border border-primary-500/30 rounded-2xl p-4 shadow-2xl shadow-black/80 backdrop-blur-xl"
            >
                <!-- Header: Month & Year Selector -->
                <div class="flex items-center justify-between gap-1 mb-3 pb-3 border-b border-white/10">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-white/10 transition-colors"
                        title="Bulan Sebelumnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-1.5">
                        <!-- Month Dropdown -->
                        <select
                            v-model="viewMonth"
                            class="bg-gray-800 border border-white/10 rounded-lg px-2 py-1 text-white text-xs font-semibold focus:outline-none focus:border-primary-500/50 cursor-pointer"
                        >
                            <option v-for="(name, idx) in monthNames" :key="idx" :value="idx + 1">
                                {{ name }}
                            </option>
                        </select>

                        <!-- Year Input (Supports any year, e.g. 1800, 1990) -->
                        <input
                            v-model.number="viewYear"
                            type="number"
                            min="1000"
                            max="2100"
                            class="w-20 bg-gray-800 border border-white/10 rounded-lg px-2 py-1 text-white text-xs font-semibold text-center focus:outline-none focus:border-primary-500/50"
                        />
                    </div>

                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-white/10 transition-colors"
                        title="Bulan Selanjutnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Days of Week Header -->
                <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-gray-400 mb-1.5">
                    <div v-for="d in dayNames" :key="d" :class="d === 'Min' ? 'text-red-400' : ''">
                        {{ d }}
                    </div>
                </div>

                <!-- Calendar Days Grid -->
                <div class="grid grid-cols-7 gap-1 text-center">
                    <button
                        v-for="(item, idx) in calendarDays"
                        :key="idx"
                        type="button"
                        @click="selectDate(item)"
                        :class="[
                            'h-8 w-8 mx-auto flex items-center justify-center rounded-lg text-xs transition-all font-medium',
                            !item.isCurrentMonth ? 'text-gray-600 hover:text-gray-400' : 'text-gray-200',
                            item.isSelected
                                ? 'bg-primary-500 text-gray-950 font-bold shadow-md shadow-primary-500/20 scale-105'
                                : 'hover:bg-white/10',
                            item.isToday && !item.isSelected ? 'border border-primary-500/50 text-primary-300 font-bold' : ''
                        ]"
                    >
                        {{ item.day }}
                    </button>
                </div>

                <!-- Footer Quick Actions -->
                <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="setToday"
                        class="text-primary-400 hover:text-primary-300 font-medium px-2 py-1 rounded hover:bg-primary-500/10 transition-colors"
                    >
                        Hari Ini
                    </button>
                    <span class="text-[10px] text-gray-500 font-mono">Format: MM/DD/YYYY</span>
                    <button
                        type="button"
                        @click="clearDate"
                        class="text-gray-400 hover:text-red-400 font-medium px-2 py-1 rounded hover:bg-white/5 transition-colors"
                    >
                        Kosongkan
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
