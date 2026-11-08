<script setup>
import {
  computePosition,
  flip,
  offset,
  shift,
} from '@floating-ui/dom'
import { useLayoutConfigStore } from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'

const props = defineProps({
  popperInlineEnd: {
    type: Boolean,
    required: false,
    default: false,
  },
  tag: {
    type: String,
    required: false,
    default: 'div',
  },
  contentContainerTag: {
    type: String,
    required: false,
    default: 'div',
  },
  isRtl: {
    type: Boolean,
    required: false,
  },
})

const configStore = useLayoutConfigStore()
const refPopperContainer = ref()
const refPopper = ref()

const popperContentStyles = ref({
  left: '0px',
  top: '0px',

})

const updatePopper = async () => {
  if (refPopperContainer.value !== undefined && refPopper.value !== undefined) {
    const { x, y } = await computePosition(refPopperContainer.value, refPopper.value, {
      placement: props.popperInlineEnd ? props.isRtl ? 'left-start' : 'right-start' : 'bottom-start',
      middleware: [
        ...configStore.horizontalNavPopoverOffset ? [offset(configStore.horizontalNavPopoverOffset)] : [],
        flip({
          boundary: document.querySelector('body'),
          padding: { bottom: 16 },
        }),
        shift({
          boundary: document.querySelector('body'),
          padding: { bottom: 16 },
        }),
      ],

    })

    popperContentStyles.value.left = `${ x }px`
    popperContentStyles.value.top = `${ y }px`
  }
}

until(() => configStore.horizontalNavType).toMatch(type => type === 'static').then(() => {
  useEventListener('scroll', updatePopper)

})

const isContentShown = ref(false)

const showContent = () => {
  isContentShown.value = true
  updatePopper()
}

const hideContent = () => {
  isContentShown.value = false
}

onMounted(updatePopper)

watch([
  () => configStore.isAppRTL,
  () => configStore.appContentWidth,
], updatePopper)

const route = useRoute()

watch(() => route.fullPath, hideContent)
</script>

<template>
  <div
    class="nav-popper"
    :class="[{
      'popper-inline-end': popperInlineEnd,
      'show-content': isContentShown,
    }]"
  >
    <div
      ref="refPopperContainer"
      class="popper-triggerer"
      @mouseenter="showContent"
      @mouseleave="hideContent"
    >
      <slot />
    </div>

    <template v-if="!themeConfig.horizontalNav.transition">
      <div
        ref="refPopper"
        class="popper-content"
        :style="popperContentStyles"
        @mouseenter="showContent"
        @mouseleave="hideContent"
      >
        <div>
          <slot name="content" />
        </div>
      </div>
    </template>

    <template v-else-if="typeof themeConfig.horizontalNav.transition === 'string'">
      <Transition :name="themeConfig.horizontalNav.transition">
        <div
          v-show="isContentShown"
          ref="refPopper"
          class="popper-content"
          :style="popperContentStyles"
          @mouseenter="showContent"
          @mouseleave="hideContent"
        >
          <div>
            <slot name="content" />
          </div>
        </div>
      </Transition>
    </template>

    <template v-else>
      <Component :is="themeConfig.horizontalNav.transition">
        <div
          v-show="isContentShown"
          ref="refPopper"
          class="popper-content"
          :style="popperContentStyles"
          @mouseenter="showContent"
          @mouseleave="hideContent"
        >
          <div>
            <slot name="content" />
          </div>
        </div>
      </Component>
    </template>

  </div>
</template>

<style lang="scss">
.popper-content {
  position: absolute;
}
</style>
