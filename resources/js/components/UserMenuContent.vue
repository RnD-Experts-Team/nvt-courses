<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();

// Since your User model has isAdmin() method that checks role === 'admin'
// We need to check the role property directly since methods aren't serialized
const isUserAdmin = computed(() => {
    // Direct role check since Laravel sends user data as JSON without methods
    return props.user?.role === 'admin';
});

// Debug logging - remove in production
console.log('User object:', props.user);
console.log('User role:', props.user?.role);
console.log('Is admin:', isUserAdmin.value);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="props.user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuItem>
        <Link
            class="flex items-center w-full text-left px-2 py-1.5"
            method="post"
            :href="route('logout')"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>

    <!-- Conditional menu items based on user role -->
    <template v-if="isUserAdmin">
        <DropdownMenuItem>
            <!-- Use regular anchor tag for external/docs links that need to open in new tab -->
            <a
                class="flex items-center w-full text-left px-2 py-1.5"
                :href="route('docs', { path: 'admin' })"
                target="_blank"
                rel="noopener noreferrer"
            >
                <Settings class="mr-2 h-4 w-4" />
                Admin Docs
            </a>
        </DropdownMenuItem>
    </template>

    <template v-else>
        <DropdownMenuItem>
            <!-- Use regular anchor tag for external/docs links that need to open in new tab -->
            <a
                class="flex items-center w-full text-left px-2 py-1.5"
                :href="route('docs', { path: 'user' })"
                target="_blank"
                rel="noopener noreferrer"
            >
                <Settings class="mr-2 h-4 w-4" />
                User Docs
            </a>
        </DropdownMenuItem>
    </template>
</template>
