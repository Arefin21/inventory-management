<template>
    <div class="container mx-auto py-8 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Product Inventory</h1>
            <Button @click="$inertia.visit(route('products.create'))">
                Add New Product
            </Button>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <form @submit.prevent="handleSearch" class="flex gap-2">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or SKU..."
                    class="max-w-md"
                />
                <Button type="submit">Search</Button>
                <Button
                    v-if="filters.search"
                    variant="outline"
                    @click="clearSearch"
                >
                    Clear
                </Button>
            </form>
        </div>

        <!-- Success Message -->
        <div
            v-if="$page.props.flash?.success"
            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded"
        >
            {{ $page.props.flash.success }}
        </div>

        <!-- Products Table -->
        <div class="border rounded-lg overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-20">Image</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Price</TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="products.data.length === 0">
                        <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                            No products found.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="product in products.data" :key="product.id">
                        <TableCell>
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="product.name"
                                class="w-16 h-16 object-cover rounded"
                            />
                            <div
                                v-else
                                class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500"
                            >
                                No Image
                            </div>
                        </TableCell>
                        <TableCell class="font-medium">{{ product.name }}</TableCell>
                        <TableCell>{{ product.sku }}</TableCell>
                        <TableCell>${{ parseFloat(product.price).toFixed(2) }}</TableCell>
                        <TableCell>
                            <span
                                :class="[
                                    'px-2 py-1 rounded text-xs font-medium',
                                    product.stock > 10
                                        ? 'bg-green-100 text-green-800'
                                        : product.stock > 0
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-red-100 text-red-800',
                                ]"
                            >
                                {{ product.stock }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="$inertia.visit(route('products.edit', product.id))"
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="openDeleteDialog(product)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="products.links.length > 3" class="mt-6 flex justify-center gap-2">
            <template v-for="(link, index) in products.links" :key="index">
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    @click="link.url && $inertia.visit(link.url)"
                    size="sm"
                >
                    <span v-html="link.label"></span>
                </Button>
                <span v-else class="px-3 py-2 text-muted-foreground">
                    <span v-html="link.label"></span>
                </span>
            </template>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Product</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ selectedProduct?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="handleDelete"
                        :disabled="deleting"
                    >
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Table from '@/components/ui/Table.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableRow from '@/components/ui/TableRow.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableCell from '@/components/ui/TableCell.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DialogContent from '@/components/ui/DialogContent.vue';
import DialogHeader from '@/components/ui/DialogHeader.vue';
import DialogTitle from '@/components/ui/DialogTitle.vue';
import DialogDescription from '@/components/ui/DialogDescription.vue';
import DialogFooter from '@/components/ui/DialogFooter.vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const deleteDialogOpen = ref(false);
const selectedProduct = ref(null);
const deleting = ref(false);

const handleSearch = () => {
    router.get(
        route('products.index'),
        { search: search.value },
        { preserveState: true, replace: true }
    );
};

const clearSearch = () => {
    search.value = '';
    router.get(route('products.index'), {}, { preserveState: true, replace: true });
};

const openDeleteDialog = (product) => {
    selectedProduct.value = product;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!selectedProduct.value) return;

    deleting.value = true;
    router.delete(route('products.destroy', selectedProduct.value.id), {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedProduct.value = null;
        },
    });
};

// Helper function for routes (you might want to use ziggy or define routes differently)
const route = (name, params = {}) => {
    const routes = {
        'products.index': '/products',
        'products.create': '/products/create',
        'products.edit': (id) => `/products/${id}/edit`,
        'products.destroy': (id) => `/products/${id}`,
    };

    if (typeof routes[name] === 'function') {
        return routes[name](params);
    }
    return routes[name] || '#';
};
</script>

