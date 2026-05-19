<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { computed } from 'vue'
import Pagination from "@/Components/Pagination.vue";
import UserTable from "@/Pages/Admin/Partials/UserTable.vue"
import UserFilters from "@/Pages/Admin/Partials/UserFilters.vue";

const props = defineProps({
    admin:        Object,
    isSuperAdmin: Boolean,
    users:        Object,
    filters:      Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash ?? {})

const search   = ref(props.filters?.search   ?? '')
const showOnly = ref(props.filters?.filter   ?? 'all')

watch([search, showOnly], () => {
    router.get('/admin', {
        search: search.value  || undefined,
        filter: showOnly.value !== 'all' ? showOnly.value : undefined,
    }, { preserveState: true, replace: true })
})


function deleteUser(user) {
    if (!confirm(`Czy na pewno chcesz usunąć konto ${user.name}?`)) return
    router.delete(`/admin/users/${user.id}`)
}

function restoreUser(user) {
    router.post(`/admin/users/${user.id}/restore`)
}

function toggleAdmin(user) {
    const action = user.roles.includes('admin') ? 'odebrać' : 'nadać'
    if (!confirm(`Czy na pewno chcesz ${action} rolę admina użytkownikowi ${user.name}?`)) return
    router.post(`/admin/users/${user.id}/toggle-admin`)
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>

        <div class="py-10 max-w-6xl mx-auto px-4 space-y-6">

            <!-- Flash -->
            <div v-if="flash.success"
                 class="px-4 py-3 rounded-xl bg-green-900/40 border border-green-500/30 text-green-300 text-sm">
                {{ flash.success }}
            </div>
            <div v-if="flash.error || ($page.props.errors?.error)"
                 class="px-4 py-3 rounded-xl bg-red-900/40 border border-red-500/30 text-red-300 text-sm">
                {{ flash.error ?? $page.props.errors?.error }}
            </div>

            <div class="rounded-2xl bg-primary p-6 space-y-6">
                <header>
                    <h2 class="text-lg font-medium text-white">{{($t('manageUsers'))}}</h2>
                    <p class="mt-1 text-sm text-secondary/70">{{($t('manageUsersInfo'))}}</p>
                </header>

                <UserFilters
                    v-model:search="search"
                    v-model:show-only="showOnly"
                />

                <Pagination
                    :links="users.links"
                    :params="{ search: search || undefined, filter: showOnly !== 'all' ? showOnly : undefined }"
                />

                <UserTable
                    :users="users.data"
                    :is-super-admin="isSuperAdmin"
                    @delete="deleteUser"
                    @restore="restoreUser"
                    @toggle-admin="toggleAdmin"
                />
            </div>
        </div>

    </AuthenticatedLayout>
</template>
