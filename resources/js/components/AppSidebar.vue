<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CircleGauge,
    FileJson2,
    Files,
    LayoutDashboard,
    MessagesSquare,
    ShieldCheck,
    UsersRound,
    WalletCards,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const mainNavItems: NavItem[] = [
    {
        title: t('dashboard'),
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    { title: t('team'), href: '/modules/team', icon: UsersRound },
    { title: t('crm'), href: '/modules/crm', icon: CircleGauge },
    { title: t('booking'), href: '/modules/booking', icon: CalendarDays },
    {
        title: t('communications'),
        href: '/modules/communications',
        icon: MessagesSquare,
    },
    { title: t('documents'), href: '/modules/documents', icon: Files },
    { title: t('finance'), href: '/modules/finance', icon: WalletCards },
    { title: t('risk'), href: '/modules/risk', icon: ShieldCheck },
];

const footerNavItems: NavItem[] = [
    {
        title: t('api'),
        href: '/api/documentation',
        icon: FileJson2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="t('navigation')" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
