<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import GameListItem from '@/Components/Chess/GameListItem.vue'

defineProps({
    games: Object,
})
</script>

<template>
    <Head title="My Games" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

            <div v-if="!games.data.length" class="text-center py-20 text-gray-500">
                <div class="text-6xl mb-4">♟</div>
                <p class="text-lg">No games yet. Start one!</p>
            </div>

            <Pagination :links="games.links" />

            <div class="space-y-3">
                <GameListItem
                    v-for="game in games.data"
                    :key="game.id"
                    :game="game"
                    @click="router.visit(`/game/${game.id}`)"
                />
            </div>

        </div>
    </AuthenticatedLayout>
</template>
