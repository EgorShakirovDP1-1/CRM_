<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

const { locale } = useI18n();

function toggle(): void {
    router.post(
        '/locale',
        { locale: locale.value === 'ru' ? 'en' : 'ru' },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
}
</script>

<template>
    <Button variant="ghost" size="sm" class="gap-2" @click="toggle">
        <Languages class="size-4" />
        {{ locale === 'ru' ? 'EN' : 'RU' }}
    </Button>
</template>
