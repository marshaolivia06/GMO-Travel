<template>
    <div>
      <div class="text-primary text-caption font-weight-bold mb-1">
        Business Trip
      </div>
  
      <h1 class="text-h5 font-weight-medium mb-1">
        Pilih jenis perjalanan
      </h1>
  
      <p class="text-body-2 text-medium-emphasis mb-6">
        Pilih perjalanan individual atau group.
      </p>
  
      <VRow>
        <VCol
          v-for="item in tripTypes"
          :key="item.title"
          cols="12"
          sm="6"
        >
          <VCard
            class="pa-5"
            variant="outlined"
            rounded="lg"
            hover
            :to="{ name: item.route }"
          >
            <div class="d-flex align-center justify-space-between mb-5">
              <VAvatar
                color="primary"
                variant="tonal"
                size="46"
              >
                {{ item.icon }}
              </VAvatar>
  
              <VIcon icon="ri-arrow-right-line" color="primary" />
            </div>
  
            <div class="text-subtitle-1 font-weight-bold">
              {{ item.title }}
            </div>
  
            <div class="text-body-2 text-medium-emphasis mt-1">
              {{ item.description }}
            </div>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </template>
  
  <script setup>
  const tripTypes = [
    {
      title: 'Individual',
      description: 'Perjalanan dinas untuk satu orang.',
      icon: '🧍',
      route: 'travel-order-form',
    },
    {
      title: 'Group',
      description: 'Perjalanan dinas untuk beberapa orang sekaligus.',
      icon: '👥',
      route: 'select-group-trip',
    },
  ]
  </script>