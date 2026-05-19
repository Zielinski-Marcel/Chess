<script setup>
defineProps({
    users:        { type: Array,   required: true },
    isSuperAdmin: { type: Boolean, default: false },
})

const emit = defineEmits(['delete', 'restore', 'toggle-admin'])
</script>

<template>
    <div class="rounded-xl overflow-hidden bg-background">
        <table class="w-full text-sm">
            <thead>
            <tr class="text-white text-xs uppercase tracking-widest border-b border-secondary/20">
                <th class="px-4 py-3 text-left">{{ $t('user') }}</th>
                <th class="px-4 py-3 text-left">{{ $t('role') }}</th>
                <th class="px-4 py-3 text-center">{{ $t('games') }}</th>
                <th class="px-4 py-3 text-right">{{ $t('actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="user in users"
                :key="user.id"
                class="border-b border-secondary/10 last:border-0 transition hover:bg-secondary/5"
                :class="{ 'opacity-50': user.deleted_at }"
            >
                <td class="px-4 py-3">
                    <p class="text-white font-medium">{{ user.name }}</p>
                    <p class="text-secondary/50 text-xs">{{ user.email }}</p>
                    <p class="text-secondary/30 text-xs">od {{ user.created_at }}</p>
                </td>

                <td class="px-4 py-3">
                    <p class="text-white font-medium">{{ user.roles.includes('admin') ? $t('admin') : $t('user') }}</p>
                </td>

                <td class="px-4 py-3 text-center text-white text-xs">
                    <span class="block">{{ $t('white:') }} {{ user.white_games_count }}</span>
                    <span class="block">{{ $t('black:') }} {{ user.black_games_count }}</span>
                </td>

                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button
                            v-if="user.deleted_at"
                            @click="emit('restore', user)"
                            class="px-3 py-1 rounded-lg text-xs font-medium bg-secondary hover:bg-secondary/50 text-black transition"
                        >
                            {{$t('restore')}}
                        </button>
                        <template v-else>
                            <button
                                v-if="isSuperAdmin"
                                @click="emit('toggle-admin', user)"
                                class="px-3 py-1 rounded-lg text-xs font-medium transition"
                                :class="user.roles.includes('admin')
                                        ? 'bg-red-800 hover:bg-bg-red-500 text-white transition'
                                        : 'bg-secondary hover:bg-secondary/50 text-black'"
                            >
                                {{ user.roles.includes('admin') ? $t('revokeAdmin') : $t('grantAdmin') }}
                            </button>
                            <button
                                @click="emit('delete', user)"
                                class="px-3 py-1 rounded-lg text-xs font-medium bg-red-800 hover:bg-red-500 text-white transition"
                            >
                                {{$t('delete')}}
                            </button>
                        </template>
                    </div>
                </td>
            </tr>

            <tr v-if="!users.length">
                <td colspan="5" class="px-4 py-8 text-center text-secondary/40 text-sm">
                    {{$t('noUsersFound')}}
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
