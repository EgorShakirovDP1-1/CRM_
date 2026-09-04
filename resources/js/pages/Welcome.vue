<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Bot, Layers3, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LocaleSwitch from '@/components/LocaleSwitch.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const page = usePage();
</script>

<template>
    <Head title="Nexus CRM" />
    <main class="welcome-shell">
        <nav class="welcome-nav">
            <Link href="/" class="flex items-center gap-3 font-semibold"
                ><span class="welcome-logo"
                    ><AppLogoIcon class="size-6 fill-current" /></span
                >Nexus CRM</Link
            >
            <div class="flex items-center gap-2">
                <LocaleSwitch /><Link
                    v-if="page.props.auth.user"
                    href="/dashboard"
                    class="crm-primary-action"
                    >{{ t('dashboard') }} <ArrowRight class="size-4" /></Link
                ><template v-else
                    ><Link
                        href="/login"
                        class="px-3 py-2 text-sm font-medium"
                        >{{ t('signIn') }}</Link
                    ><Link href="/register" class="crm-primary-action"
                        >{{ t('register') }} <ArrowRight class="size-4" /></Link
                ></template>
            </div>
        </nav>
        <section class="welcome-hero">
            <div class="welcome-copy">
                <p class="crm-kicker">EU-FIRST / MULTI-TENANT / API-READY</p>
                <h1>{{ t('productTitle') }}</h1>
                <p>{{ t('productCopy') }}</p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="
                            page.props.auth.user ? '/dashboard' : '/register'
                        "
                        class="crm-primary-action"
                        >{{
                            page.props.auth.user
                                ? t('dashboard')
                                : t('register')
                        }}
                        <ArrowRight class="size-4" /></Link
                    ><Link
                        href="/api/documentation"
                        class="welcome-secondary"
                        >{{ t('api') }}</Link
                    >
                </div>
            </div>
            <div class="welcome-orbit">
                <div class="orbit-card orbit-main">
                    <Layers3 /><strong>360°</strong
                    ><span>Customer workspace</span>
                </div>
                <div class="orbit-card orbit-a">
                    <ShieldCheck /><span>{{ t('security') }}</span>
                </div>
                <div class="orbit-card orbit-b">
                    <Bot /><span>{{ t('automation') }}</span>
                </div>
            </div>
        </section>
        <section class="welcome-strip">
            <span>CRM</span><span>BOOKING</span><span>INBOX</span
            ><span>DOCS + eID</span><span>FINANCE</span><span>RISK</span
            ><span>GDPR</span>
        </section>
    </main>
</template>
