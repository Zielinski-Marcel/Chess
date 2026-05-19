<script setup>
import { useI18n } from 'vue-i18n'
import { ref, watch } from 'vue'

const { locale } = useI18n()

const selectedLanguage = ref(
    localStorage.getItem('language') || locale.value
)

watch(selectedLanguage, (value) => {
    locale.value = value

    localStorage.setItem('language', value)
})
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white">
                {{($t('languageSettings'))}}
            </h2>

            <p class="mt-1 text-sm text-gray-300">
                {{($t('languageSettingsInfo'))}}
            </p>
        </header>

        <div class="mt-6">
            <label
                for="language"
                class="block text-sm font-medium text-white"
            >
                {{($t('language'))}}
            </label>

            <select
                id="language"
                v-model="selectedLanguage"
                class="mt-1 block w-full rounded-md border-gray-300 bg-secondary text-black shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="pl">Polski</option>
                <option value="en">English</option>
            </select>
        </div>
    </section>
</template>

<style scoped>
select {
    min-height: 42px;
}
</style>
