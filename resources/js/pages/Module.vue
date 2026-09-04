<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, Database, Download, Plus, Upload, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';

const props = defineProps<{
    module: string;
    counts: Record<string, number>;
    recent: Record<string, unknown>[];
}>();
const { locale, t } = useI18n();
const showCreate = ref(false);
const customerForm = useForm({
    type: 'person',
    display_name: '',
    preferred_language: locale.value,
});
const importForm = useForm<{ file: File | null }>({ file: null });
const labels = computed<Record<string, string>>(() => ({
    team: t('team'),
    crm: t('crm'),
    booking: t('booking'),
    communications: t('communications'),
    documents: t('documents'),
    finance: t('finance'),
    risk: t('risk'),
}));
const tableLabels: Record<string, { ru: string; en: string }> = {
    employees: { ru: 'Сотрудники', en: 'Employees' },
    work_schedules: { ru: 'Графики', en: 'Schedules' },
    work_plans: { ru: 'Планы', en: 'Plans' },
    audit_logs: { ru: 'Аудит', en: 'Audit events' },
    customers: { ru: 'Клиенты', en: 'Customers' },
    leads: { ru: 'Лиды', en: 'Leads' },
    deals: { ru: 'Сделки', en: 'Deals' },
    tasks: { ru: 'Задачи', en: 'Tasks' },
    services: { ru: 'Услуги', en: 'Services' },
    appointments: { ru: 'Записи', en: 'Appointments' },
    waitlist_entries: { ru: 'Лист ожидания', en: 'Waitlist' },
    reviews: { ru: 'Отзывы', en: 'Reviews' },
    communication_threads: { ru: 'Диалоги', en: 'Threads' },
    messages: { ru: 'Сообщения', en: 'Messages' },
    pending_message_cases: { ru: 'Ждут ответа', en: 'Pending replies' },
    notification_deliveries: { ru: 'Уведомления', en: 'Notifications' },
    document_templates: { ru: 'Шаблоны', en: 'Templates' },
    documents: { ru: 'Документы', en: 'Documents' },
    approval_workflows: { ru: 'Маршруты', en: 'Approval flows' },
    signature_requests: { ru: 'Подписи', en: 'Signatures' },
    customer_orders: { ru: 'Заказы', en: 'Orders' },
    invoices: { ru: 'Счета', en: 'Invoices' },
    payments: { ru: 'Платежи', en: 'Payments' },
    suppliers: { ru: 'Поставщики', en: 'Suppliers' },
    stock_movements: { ru: 'Движения', en: 'Stock movements' },
    risk_assessments: { ru: 'Оценки риска', en: 'Risk assessments' },
    customer_forecasts: { ru: 'Прогнозы', en: 'Forecasts' },
    consents: { ru: 'Согласия', en: 'Consents' },
    data_subject_requests: { ru: 'DSR-запросы', en: 'DSR requests' },
};
const tableLabel = (key: string) => tableLabels[key]?.[locale.value] ?? key;
const recordTitle = (row: Record<string, unknown>) =>
    String(
        row.display_name ??
            row.name ??
            row.title ??
            row.subject ??
            row.number ??
            row.type ??
            row.id ??
            '—',
    );
const recordDate = (row: Record<string, unknown>) =>
    String(
        row.starts_at ?? row.due_at ?? row.created_at ?? row.received_at ?? '',
    );
function submitCustomer(): void {
    customerForm.post('/crm/customers', {
        preserveScroll: true,
        onSuccess: () => {
            customerForm.reset('display_name');
            showCreate.value = false;
        },
    });
}
function selectCsv(event: Event): void {
    importForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function submitImport(): void {
    if (importForm.file)
        importForm.post('/crm/customers/import', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => importForm.reset(),
        });
}
</script>

<template>
    <Head :title="labels[module]" />
    <main class="crm-page">
        <section class="crm-hero">
            <div>
                <p class="crm-kicker">NEXUS / {{ module.toUpperCase() }}</p>
                <h1>{{ labels[module] }}</h1>
                <p>
                    {{ t('objects') }}:
                    {{ Object.values(counts).reduce((a, b) => a + b, 0) }}
                </p>
            </div>
            <div
                v-if="module === 'crm'"
                class="flex flex-wrap items-center gap-2"
            >
                <a href="/crm/customers/export" class="welcome-secondary"
                    ><Download class="mr-2 size-4" />{{ t('exportCsv') }}</a
                >
                <form
                    class="flex items-center gap-2"
                    @submit.prevent="submitImport"
                >
                    <label
                        for="customer-csv"
                        class="welcome-secondary cursor-pointer"
                        ><Upload class="mr-2 size-4" />{{
                            importForm.file?.name ?? t('chooseCsv')
                        }}</label
                    >
                    <input
                        id="customer-csv"
                        type="file"
                        accept=".csv,text/csv,text/plain"
                        class="sr-only"
                        @change="selectCsv"
                    />
                    <Button
                        v-if="importForm.file"
                        type="submit"
                        variant="outline"
                        :disabled="importForm.processing"
                        >{{ t('importNow') }}</Button
                    >
                </form>
                <Button
                    class="crm-primary-action"
                    @click="showCreate = !showCreate"
                    ><X v-if="showCreate" class="size-4" /><Plus
                        v-else
                        class="size-4"
                    />
                    {{ t('createCustomer') }}</Button
                >
            </div>
        </section>
        <p
            v-if="importForm.errors.file"
            class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300"
        >
            {{ importForm.errors.file }}
        </p>
        <form
            v-if="showCreate && module === 'crm'"
            class="crm-panel grid gap-4 md:grid-cols-[1fr_180px_130px_auto] md:items-end"
            @submit.prevent="submitCustomer"
        >
            <div class="grid gap-2">
                <Label for="customer-name">{{ t('name') }}</Label
                ><Input
                    id="customer-name"
                    v-model="customerForm.display_name"
                    required
                    autofocus
                />
                <p
                    v-if="customerForm.errors.display_name"
                    class="text-xs text-red-600"
                >
                    {{ customerForm.errors.display_name }}
                </p>
            </div>
            <div class="grid gap-2">
                <Label for="customer-type">Type</Label
                ><select
                    id="customer-type"
                    v-model="customerForm.type"
                    class="crm-select"
                >
                    <option value="person">{{ t('person') }}</option>
                    <option value="company">{{ t('company') }}</option>
                    <option value="sole_trader">{{ t('soleTrader') }}</option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="customer-locale">{{ t('language') }}</Label
                ><select
                    id="customer-locale"
                    v-model="customerForm.preferred_language"
                    class="crm-select"
                >
                    <option value="ru">RU</option>
                    <option value="en">EN</option>
                </select>
            </div>
            <Button type="submit" :disabled="customerForm.processing">{{
                t('save')
            }}</Button>
        </form>
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="(count, table) in counts"
                :key="table"
                class="crm-metric"
                data-tone="blue"
            >
                <div class="crm-metric-icon"><Database class="size-5" /></div>
                <div>
                    <p>{{ tableLabel(table) }}</p>
                    <strong>{{ count }}</strong>
                </div>
            </article>
        </section>
        <section class="crm-panel">
            <header>
                <div>
                    <span class="crm-eyebrow">{{ module.toUpperCase() }}</span>
                    <h2>{{ t('recent') }}</h2>
                </div>
            </header>
            <div v-if="recent.length" class="divide-border/70 divide-y">
                <div
                    v-for="row in recent"
                    :key="String(row.id)"
                    class="crm-row"
                >
                    <div class="crm-avatar"><Database class="size-4" /></div>
                    <strong class="min-w-0 flex-1 truncate">{{
                        recordTitle(row)
                    }}</strong
                    ><span
                        class="text-muted-foreground hidden text-sm md:inline"
                        >{{ recordDate(row) }}</span
                    ><span v-if="row.status" class="crm-status">{{
                        row.status
                    }}</span
                    ><ArrowRight class="text-muted-foreground size-4" />
                </div>
            </div>
            <p v-else class="crm-empty">{{ t('noData') }}</p>
        </section>
    </main>
</template>
