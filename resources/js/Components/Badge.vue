<template>
    <div class="badge px-5 py-1 border-2 rounded flex items-center justify-center gap-1 mobile:block mobile:w-full mobile:m-0 mobile:mb-3" :class="{ 'inline-block': small, 'px-2 w-11': square }" @click="click && click($event);" :title="title || ''">
        <slot />
    </div>
</template>

<script>
export default {
    name: 'Badge',
    props: {
        square: Boolean,
        small: Boolean,
        click: Function,
        title: String,
    }
}
</script>
