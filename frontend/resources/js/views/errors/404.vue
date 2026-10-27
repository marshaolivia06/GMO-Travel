<script setup>
import misc404 from '@images/pages/404.png'
</script>

<template>
  <div class="misc-wrapper">
    <ErrorHeader
      status-code="404"
      title="Page Not Found ⚠️"
      description="We couldn't find the page you are looking for."
    />

    <VBtn
      class="mb-11"
      to="/"
    >
      Back to Home
    </VBtn>

    <div class="misc-avatar w-100 text-center">
      <VImg
        :src="misc404"
        alt="Page Not Found ⚠️"
        :max-width="400"
        class="mx-auto"
      />
    </div>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/misc.scss";
</style>
