<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CalendarClock,
    CircleGauge,
    Clock3,
    Euro,
    Inbox,
    ListChecks,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';

type Metrics = {
    customers: number;
    openDeals: number;
    pipelineValue: number;
    todayAppointments: number;
    openTasks: number;
    pendingMessages: number;
};
type Appointment = {
    id: string;
    starts_at: string;
    status: string;
    customer?: { display_name: string };
};
type Lead = { id: string; name: string; status: string; created_at: string };
type Task = {
    id: string;
    title: string;
    status: string;
    priority: string;
    due_at: string | null;
};

const props = defineProps<{
    metrics: Metrics;
    upcomingAppointments: Appointment[];
    recentLeads: Lead[];
    taskQueue: Task[];
    channels: Record<string, number>;
}>();
const { locale, t } = useI18n();
const money = computed(
    () =>
        new Intl.NumberFormat(locale.value, {
            style: 'currency',
            currency: 'EUR',
            maximumFractionDigits: 0,
        }),
);
const date = (value: string) =>
    new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const metricCards = computed(() => [
    {
        label: t('customers'),
        value: props.metrics.customers,
        icon: UsersRound,
        tone: 'mint',
    },
    {
        label: t('openDeals'),
        value: props.metrics.openDeals,
        icon: CircleGauge,
        tone: 'blue',
    },
    {
        label: t('pipelineValue'),
        value: money.value.format(props.metrics.pipelineValue),
        icon: Euro,
        tone: 'amber',
    },
    {
        label: t('todayAppointments'),
        value: props.metrics.todayAppointments,
        icon: CalendarClock,
        tone: 'violet',
    },
    {
        label: t('openTasks'),
        value: props.metrics.openTasks,
        icon: ListChecks,
        tone: 'rose',
    },
    {
        label: t('pendingMessages'),
        value: props.metrics.pendingMessages,
        icon: Inbox,
        tone: 'cyan',
    },
]);
</script>

<template>
    <Head :title="t('dashboard')" />
    <main class="crm-page">
        <section class="crm-hero">
            <div>
                <p class="crm-kicker">NEXUS / LIVE</p>
                <h1>{{ t('dashboard') }}</h1>
                <p>
                    {{
                        new Intl.DateTimeFormat(locale, {
                            dateStyle: 'full',
                        }).format(new Date())
                    }}
                </p>
            </div>
            <Link href="/modules/crm" class="crm-primary-action"
                >{{ t('viewModule') }} <ArrowUpRight class="size-4"
            /></Link>
        </section>
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="card in metricCards"
                :key="card.label"
                class="crm-metric"
                :data-tone="card.tone"
            >
                <div class="crm-metric-icon">
                    <component :is="card.icon" class="size-5" />
                </div>
                <div>
                    <p>{{ card.label }}</p>
                    <strong>{{ card.value }}</strong>
                </div>
            </article>
        </section>
        <section class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
            <article class="crm-panel">
                <header>
                    <div>
                        <span class="crm-eyebrow">TODAY + NEXT</span>
                        <h2>{{ t('upcoming') }}</h2>
                    </div>
                    <CalendarClock class="size-5 text-teal-600" />
                </header>
                <div
                    v-if="upcomingAppointments.length"
                    class="divide-border/70 divide-y"
                >
                    <div
                        v-for="item in upcomingAppointments"
                        :key="item.id"
                        class="crm-row"
                    >
                        <div class="crm-time">
                            <Clock3 class="size-4" />{{ date(item.starts_at) }}
                        </div>
                        <strong>{{ item.customer?.display_name ?? '—' }}</strong
                        ><span class="crm-status">{{ item.status }}</span>
                    </div>
                </div>
                <p v-else class="crm-empty">{{ t('noData') }}</p>
            </article>
            <article class="crm-panel">
                <header>
                    <div>
                        <span class="crm-eyebrow">PIPELINE</span>
                        <h2>{{ t('newestLeads') }}</h2>
                    </div>
                </header>
                <div v-if="recentLeads.length" class="space-y-2">
                    <div
                        v-for="lead in recentLeads"
                        :key="lead.id"
                        class="crm-compact-row"
                    >
                        <span class="crm-avatar">{{
                            lead.name.slice(0, 2).toUpperCase()
                        }}</span
                        ><strong class="min-w-0 flex-1 truncate">{{
                            lead.name
                        }}</strong
                        ><span class="crm-status">{{ lead.status }}</span>
                    </div>
                </div>
                <p v-else class="crm-empty">{{ t('noData') }}</p>
            </article>
        </section>
        <section class="crm-panel">
            <header>
                <div>
                    <span class="crm-eyebrow">FOCUS</span>
                    <h2>{{ t('taskQueue') }}</h2>
                </div>
            </header>
            <div v-if="taskQueue.length" class="grid gap-2 lg:grid-cols-2">
                <div v-for="task in taskQueue" :key="task.id" class="crm-task">
                    <span
                        class="crm-priority"
                        :data-priority="task.priority"
                    ></span
                    ><strong>{{ task.title }}</strong
                    ><span>{{ task.due_at ? date(task.due_at) : '—' }}</span>
                </div>
            </div>
            <p v-else class="crm-empty">{{ t('noData') }}</p>
        </section>
    </main>
</template>
