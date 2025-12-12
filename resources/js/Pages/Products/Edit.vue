<template>
    <div class="container mx-auto py-8 px-4 max-w-2xl">
        <div class="mb-6">
            <Button variant="outline" @click="$inertia.visit(route('products.index'))">
                ← Back to Products
            </Button>
        </div>

        <h1 class="text-3xl font-bold mb-6">Edit Product</h1>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Name -->
            <div>
                <Label for="name">Product Name *</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.name }"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                </p>
            </div>

            <!-- SKU -->
            <div>
                <Label for="sku">SKU *</Label>
                <Input
                    id="sku"
                    v-model="form.sku"
                    type="text"
                    required
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.sku }"
                />
                <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">
                    {{ form.errors.sku }}
                </p>
            </div>

            <!-- Price -->
            <div>
                <Label for="price">Price *</Label>
                <Input
                    id="price"
                    v-model="form.price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.price }"
                />
                <p v-if="form.errors.price" class="mt-1 text-sm text-red-500">
                    {{ form.errors.price }}
                </p>
            </div>

            <!-- Stock -->
            <div>
                <Label for="stock">Stock Quantity *</Label>
                <Input
                    id="stock"
                    v-model="form.stock"
                    type="number"
                    min="0"
                    required
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.stock }"
                />
                <p v-if="form.errors.stock" class="mt-1 text-sm text-red-500">
                    {{ form.errors.stock }}
                </p>
            </div>

            <!-- Description -->
            <div>
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    :class="{ 'border-red-500': form.errors.description }"
                ></textarea>
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">
                    {{ form.errors.description }}
                </p>
            </div>

            <!-- Current Image -->
            <div v-if="product.image_url && !imagePreview">
                <Label>Current Image</Label>
                <div class="mt-2">
                    <img
                        :src="product.image_url"
                        :alt="product.name"
                        class="w-32 h-32 object-cover rounded border"
                    />
                </div>
            </div>

            <!-- Image Upload -->
            <div>
                <Label for="image">Replace Image</Label>
                <Input
                    id="image"
                    type="file"
                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                    @change="handleImageChange"
                    class="mt-1"
                    :class="{ 'border-red-500': form.errors.image }"
                />
                <p v-if="form.errors.image" class="mt-1 text-sm text-red-500">
                    {{ form.errors.image }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Accepted formats: JPEG, PNG, JPG, GIF, WEBP. Max size: 2MB
                </p>
                <div v-if="imagePreview" class="mt-4">
                    <p class="text-sm text-muted-foreground mb-2">New Image Preview:</p>
                    <img
                        :src="imagePreview"
                        alt="Preview"
                        class="w-32 h-32 object-cover rounded border"
                    />
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Updating...' : 'Update Product' }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    @click="$inertia.visit(route('products.index'))"
                >
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const props = defineProps({
    product: Object,
    errors: Object,
});

const form = useForm({
    name: props.product.name,
    sku: props.product.sku,
    price: props.product.price,
    stock: props.product.stock,
    description: props.product.description || '',
    image: null,
});

const imagePreview = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('products.update', props.product.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const route = (name, id = null) => {
    const routes = {
        'products.index': '/products',
        'products.update': (id) => `/products/${id}`,
    };
    if (typeof routes[name] === 'function') {
        return routes[name](id);
    }
    return routes[name] || '#';
};
</script>

